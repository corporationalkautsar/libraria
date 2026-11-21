<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\Member;
use App\Support\ActivityLogger;
use App\Support\DateFilter;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::with(['member', 'user']);

        if ($q = $request->get('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('loan_number', 'like', "%{$q}%")
                    ->orWhereHas('member', fn ($m) => $m->where('full_name', 'like', "%{$q}%")
                        ->orWhere('member_code', 'like', "%{$q}%"));
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($petugas = $request->get('petugas')) {
            $query->where('user_id', $petugas);
        }
        DateFilter::apply($query, 'loan_date', $request);

        $sort = in_array($request->get('sort'), ['loan_number', 'loan_date', 'due_date', 'status', 'created_at']) ? $request->get('sort') : 'loan_date';
        $query->orderBy($sort, $request->get('direction') === 'asc' ? 'asc' : 'desc');

        return view('loans.index', [
            'loans' => $query->paginate(15)->withQueryString(),
            'staff' => \App\Models\User::where('role', 'petugas')->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->isPetugas(), 403);

        $member = $request->get('member_id') ? Member::find($request->get('member_id')) : null;
        $memberResults = null;
        if ($q = $request->get('member_q')) {
            $memberResults = Member::where('full_name', 'like', "%{$q}%")
                ->orWhere('member_code', 'like', "%{$q}%")->limit(10)->get();
        }

        $books = collect();
        if ($member) {
            $bookQuery = Book::where('available', '>', 0)->where('status', '!=', 'tidak_aktif');
            if ($bq = $request->get('book_q')) {
                $bookQuery->where(function ($sub) use ($bq) {
                    $sub->where('title', 'like', "%{$bq}%")->orWhere('isbn', 'like', "%{$bq}%")
                        ->orWhere('book_code', 'like', "%{$bq}%")
                        ->orWhereHas('author', fn ($a) => $a->where('name', 'like', "%{$bq}%"));
                });
            }
            $books = $bookQuery->with(['author', 'category'])->orderBy('title')->limit(50)->get();
        }

        $borrowedCount = $member ? LoanDetail::whereNull('returned_at')
            ->whereHas('loan', fn ($q) => $q->whereIn('status', ['dipinjam', 'terlambat'])
                ->where('member_id', $member->id))->count() : 0;

        return view('loans.create', [
            'member' => $member,
            'memberResults' => $memberResults,
            'books' => $books,
            'borrowedCount' => $borrowedCount,
            'maxBooks' => Settings::maxBooks(),
            'loanDays' => Settings::loanDays(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isPetugas(), 403);

        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'book_ids' => ['required', 'array', 'min:1'],
            'book_ids.*' => ['required', 'distinct', 'exists:books,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'member_id.required' => 'Anggota wajib dipilih.',
            'book_ids.required' => 'Minimal satu buku harus dipilih.',
            'book_ids.min' => 'Minimal satu buku harus dipilih.',
            'book_ids.*.distinct' => 'Buku yang sama tidak boleh dipilih dua kali.',
        ]);

        $member = Member::findOrFail($data['member_id']);
        if ($member->is_blocked) {
            return back()->withInput()->with('error', 'Anggota sedang diblokir karena pinjaman terlambat atau denda belum lunas.');
        }

        $borrowedCount = LoanDetail::whereNull('returned_at')
            ->whereHas('loan', fn ($q) => $q->whereIn('status', ['dipinjam', 'terlambat'])->where('member_id', $member->id))
            ->count();
        if ($borrowedCount + count($data['book_ids']) > Settings::maxBooks()) {
            return back()->withInput()->with('error', 'Maksimal '.Settings::maxBooks().' buku yang dapat dipinjam oleh satu anggota.');
        }

        $loan = DB::transaction(function () use ($data, $member) {
            $books = Book::whereIn('id', $data['book_ids'])->lockForUpdate()->get();
            if ($books->count() !== count($data['book_ids'])) {
                throw new \RuntimeException('Salah satu buku tidak ditemukan.');
            }
            foreach ($books as $book) {
                if ($book->available < 1 || $book->status === 'tidak_aktif') {
                    throw new \RuntimeException('Buku "'.$book->title.'" tidak tersedia.');
                }
            }

            $loan = Loan::create([
                'member_id' => $member->id,
                'user_id' => auth()->id(),
                'loan_date' => today(),
                'due_date' => today()->addDays(Settings::loanDays()),
                'status' => 'dipinjam',
                'total_books' => $books->count(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($books as $book) {
                LoanDetail::create(['loan_id' => $loan->id, 'book_id' => $book->id, 'status' => 'dipinjam']);
                $book->decrement('available');
                $book->syncStatus();
                $book->save();
            }

            return $loan;
        });

        ActivityLogger::log('Transaksi Peminjaman', 'Petugas '.auth()->user()->name.' mencatat peminjaman '.$loan->loan_number.' untuk anggota '.$member->full_name.'.');

        return redirect()->route('loans.show', $loan)->with('success', 'Peminjaman '.$loan->loan_number.' berhasil dicatat. Stok buku telah berkurang otomatis.');
    }

    public function show(Loan $loan)
    {
        $loan->load(['member', 'user', 'details.book', 'returns.details.book', 'fine.payments']);
        return view('loans.show', compact('loan'));
    }

    public function cancel(Loan $loan)
    {
        abort_unless(auth()->user()->isPetugas(), 403);

        if ($loan->details()->whereNotNull('returned_at')->exists()) {
            return back()->with('error', 'Transaksi yang sebagian bukunya sudah dikembalikan tidak dapat dibatalkan.');
        }
        if (in_array($loan->status, ['selesai', 'dibatalkan'])) {
            return back()->with('error', 'Transaksi ini tidak dapat dibatalkan.');
        }

        DB::transaction(function () use ($loan) {
            foreach ($loan->details as $detail) {
                $book = $detail->book;
                $book->increment('available');
                $book->syncStatus();
                $book->save();
                $detail->update(['status' => 'dikembalikan', 'returned_at' => now()]);
            }
            $loan->update(['status' => 'dibatalkan']);
        });

        ActivityLogger::log('Pembatalan Peminjaman', 'Petugas '.auth()->user()->name.' membatalkan transaksi '.$loan->loan_number.'.');

        return back()->with('success', 'Transaksi berhasil dibatalkan dan stok dikembalikan.');
    }

    public function transactions(Request $request)
    {
        $response = $this->index($request);
        return $response->with('mode', 'transactions');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\BookReturn;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Support\ActivityLogger;
use App\Support\DateFilter;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = BookReturn::with(['member', 'user', 'loan']);

        if ($q = $request->get('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('return_number', 'like', "%{$q}%")
                    ->orWhere('loan_id', 'like', "%{$q}%")
                    ->orWhereHas('member', fn ($m) => $m->where('full_name', 'like', "%{$q}%"));
            });
        }
        if ($status = $request->get('status')) {
            $query->whereHas('loan', fn ($l) => $l->where('status', $status));
        }
        if ($petugas = $request->get('petugas')) {
            $query->where('user_id', $petugas);
        }
        DateFilter::apply($query, 'return_date', $request);

        $sort = in_array($request->get('sort'), ['return_number', 'return_date', 'total_fine']) ? $request->get('sort') : 'return_date';
        $query->orderBy($sort, $request->get('direction') === 'asc' ? 'asc' : 'desc');

        return view('returns.index', [
            'returns' => $query->paginate(15)->withQueryString(),
            'staff' => \App\Models\User::where('role', 'petugas')->orderBy('name')->get(),
            'totals' => [
                'count' => (clone $query)->count(),
                'fine' => (float) (clone $query)->sum('total_fine'),
            ],
        ]);
    }

    public function create(Request $request)
    {
        abort_unless(auth()->user()->isPetugas(), 403);

        $loans = collect();
        $loan = null;
        if ($q = $request->get('q')) {
            $loans = Loan::with('member')
                ->where('loan_number', 'like', "%{$q}%")
                ->orWhereHas('member', fn ($m) => $m->where('full_name', 'like', "%{$q}%")
                    ->orWhere('member_code', 'like', "%{$q}%"))
                ->limit(10)->get();
        }
        if ($id = $request->get('loan_id')) {
            $loan = Loan::with(['member', 'user', 'details.book'])->findOrFail($id);
        }

        return view('returns.create', [
            'loans' => $loans,
            'loan' => $loan,
            'finePerDay' => Settings::finePerDay(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isPetugas(), 403);

        $data = $request->validate([
            'loan_id' => ['required', 'exists:loans,id'],
            'detail_ids' => ['required', 'array', 'min:1'],
            'detail_ids.*' => ['required', 'exists:loan_details,id'],
        ], [
            'loan_id.required' => 'Transaksi wajib dipilih.',
            'detail_ids.required' => 'Pilih minimal satu buku yang dikembalikan.',
            'detail_ids.min' => 'Pilih minimal satu buku yang dikembalikan.',
        ]);

        $loan = Loan::with('details.book')->findOrFail($data['loan_id']);

        if (in_array($loan->status, ['selesai', 'dibatalkan'])) {
            return back()->with('error', 'Transaksi ini sudah selesai atau dibatalkan.');
        }

        $returnDate = now();
        $result = DB::transaction(function () use ($loan, $data, $returnDate) {
            $details = LoanDetail::whereIn('id', $data['detail_ids'])
                ->where('loan_id', $loan->id)->lockForUpdate()->get();

            if ($details->count() !== count(array_unique($data['detail_ids']))) {
                throw new \RuntimeException('Salah satu buku tidak termasuk dalam transaksi ini.');
            }
            if ($details->whereNotNull('returned_at')->isNotEmpty()) {
                throw new \RuntimeException('Ada buku yang sudah dikembalikan sebelumnya.');
            }

            $totalFine = 0;
            $return = BookReturn::create([
                'loan_id' => $loan->id,
                'member_id' => $loan->member_id,
                'user_id' => auth()->id(),
                'return_date' => $returnDate,
                'total_books' => $details->count(),
                'total_fine' => 0,
            ]);

            foreach ($details as $detail) {
                $lateDays = $returnDate->toDateString() > $loan->due_date->toDateString()
                    ? $loan->due_date->diffInDays($returnDate->toDateString())
                    : 0;
                $fineAmount = $lateDays * Settings::finePerDay();
                $totalFine += $fineAmount;

                $return->details()->create([
                    'loan_detail_id' => $detail->id,
                    'book_id' => $detail->book_id,
                    'fine_amount' => $fineAmount,
                    'late_days' => $lateDays,
                ]);

                $detail->update([
                    'returned_at' => $returnDate,
                    'status' => 'dikembalikan',
                    'fine_amount' => $fineAmount,
                ]);

                $book = $detail->book;
                $book->increment('available');
                $book->syncStatus();
                $book->save();
            }

            $return->update(['total_fine' => $totalFine]);

            $allReturned = LoanDetail::where('loan_id', $loan->id)->whereNull('returned_at')->doesntExist();
            $newStatus = $allReturned
                ? 'selesai'
                : ($loan->due_date->lt(today()) ? 'terlambat' : 'dipinjam');
            $loan->update(['status' => $newStatus]);

            if ($totalFine > 0) {
                $fine = Fine::firstOrCreate(
                    ['loan_id' => $loan->id],
                    ['member_id' => $loan->member_id, 'amount' => 0, 'paid_amount' => 0, 'status' => 'belum_dibayar']
                );
                $fine->increment('amount', $totalFine);
                $fine->refreshStatus();
            }

            return $return;
        });

        ActivityLogger::log('Transaksi Pengembalian', 'Petugas '.auth()->user()->name.' memproses pengembalian '.$result->return_number.' ('.$result->total_books.' buku).');

        return redirect()->route('returns.show', $result)->with('success', 'Pengembalian berhasil dicatat. Stok buku bertambah otomatis'.($result->total_fine > 0 ? ' dan denda dihitung '.rupiah($result->total_fine).'.' : '.'));
    }

    public function show(BookReturn $book_return)
    {
        $book_return->load(['member', 'user', 'loan', 'details.book', 'loan.fine']);
        return view('returns.show', ['bookReturn' => $book_return]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Member;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $query = Member::query();

        if ($q = $request->get('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('full_name', 'like', "%{$q}%")
                    ->orWhere('member_code', 'like', "%{$q}%");
            });
        }
        if ($school = $request->get('school')) {
            $query->where('school', $school);
        }
        if ($origin = $request->get('origin')) {
            $query->where('origin', $origin);
        }

        $sort = in_array($request->get('sort'), ['full_name', 'school', 'origin', 'member_code', 'created_at']) ? $request->get('sort') : 'id';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction);

        return view('members.index', [
            'members' => $query->paginate(15)->withQueryString(),
            'schools' => Member::distinct()->orderBy('school')->pluck('school'),
            'origins' => Member::distinct()->orderBy('origin')->pluck('origin'),
        ]);
    }

    public function create()
    {
        abort_unless(auth()->user()->isPetugas(), 403);
        return view('members.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isPetugas(), 403);
        $data = $this->validateMember($request);

        $member = DB::transaction(fn () => Member::create($data));
        ActivityLogger::log('Penambahan Anggota', 'Menambahkan anggota '.$member->full_name.' ('.$member->member_code.').');

        return redirect()->route('members.show', $member)->with('success', 'Anggota berhasil ditambahkan dengan ID '.$member->member_code.'.');
    }

    public function show(Member $member, Request $request)
    {
        $loans = Loan::where('member_id', $member->id)->with('user');

        if ($status = $request->get('status')) {
            $loans->where('status', $status);
        }
        if ($request->get('date_filter')) {
            \App\Support\DateFilter::apply($loans, 'loan_date', $request);
        }

        return view('members.show', [
            'member' => $member,
            'loans' => $loans->latest('loan_date')->paginate(10)->withQueryString(),
        ]);
    }

    public function edit(Member $member)
    {
        abort_unless(auth()->user()->isPetugas(), 403);
        return view('members.edit', compact('member'));
    }

    public function update(Request $request, Member $member)
    {
        abort_unless(auth()->user()->isPetugas(), 403);
        $data = $this->validateMember($request);
        $member->update($data);
        ActivityLogger::log('Pengeditan Anggota', 'Mengedit data anggota '.$member->full_name.' ('.$member->member_code.').');

        return redirect()->route('members.show', $member)->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Member $member)
    {
        abort_unless(auth()->user()->isPetugas(), 403);

        if ($member->active_loans_count > 0) {
            return back()->with('error', 'Anggota tidak dapat dihapus karena masih memiliki pinjaman aktif.');
        }
        if ($member->loans()->exists()) {
            return back()->with('error', 'Anggota tidak dapat dihapus karena memiliki riwayat transaksi. Riwayat harus tetap tersimpan.');
        }

        $name = $member->full_name;
        $member->delete();
        ActivityLogger::log('Penghapusan Anggota', 'Menghapus data anggota '.$name.'.');

        return redirect()->route('members.index')->with('success', 'Data anggota berhasil dihapus.');
    }

    private function validateMember(Request $request): array
    {
        return $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'school' => ['required', 'string', 'max:150'],
            'origin' => ['required', 'string', 'max:100'],
        ], [
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'school.required' => 'Sekolah wajib diisi.',
            'origin.required' => 'Asal wajib diisi.',
        ]);
    }
}

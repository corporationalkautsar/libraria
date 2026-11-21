@extends('layouts.app')
@section('title', 'Peminjaman Baru')
@section('subtitle', 'Catat transaksi peminjaman buku')

@section('content')
<x-page-head title="Peminjaman Baru" subtitle="Cari anggota, pilih buku, lalu konfirmasi transaksi." />
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Langkah 1 — Cari Anggota</h3></div>
            <div class="card-body-soft">
                <form method="GET" class="mb-3">
                    <label class="form-label">Nama atau ID Anggota</label>
                    <div class="input-group">
                        <input name="member_q" class="form-control" value="{{ request('member_q') }}" placeholder="Cari anggota...">
                        <button class="btn btn-teal">{!! icon('search',15) !!}</button>
                    </div>
                </form>

                @if($memberResults)
                    <div class="list-group mb-3">
                        @forelse($memberResults as $result)
                        <a href="{{ route('loans.create', ['member_q'=>request('member_q'),'member_id'=>$result->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span><strong>{{ $result->full_name }}</strong><br><small class="text-muted">{{ $result->member_code }} · {{ $result->school }}</small></span>
                            {!! icon('chevron-right',16) !!}
                        </a>
                        @empty<div class="alert alert-warning alert-soft">Anggota tidak ditemukan.</div>@endforelse
                    </div>
                @endif

                @if($member)
                    <div class="p-3 rounded-3" style="background:var(--brand-50);border:1px solid var(--brand-100)">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="table-avatar">{{ Str::charAt($member->full_name,0) }}</span>
                            <div><div class="cell-primary">{{ $member->full_name }}</div><div class="cell-secondary">{{ $member->member_code }}</div></div>
                        </div>
                        <div class="small mb-1"><span class="text-muted">Sekolah:</span> <strong>{{ $member->school }}</strong></div>
                        <div class="small mb-1"><span class="text-muted">Asal:</span> <strong>{{ $member->origin }}</strong></div>
                        <div class="small mb-2"><span class="text-muted">Sedang dipinjam:</span> <strong>{{ $borrowedCount }} / {{ $maxBooks }} buku</strong></div>
                        @if($member->is_blocked)
                            <div class="alert alert-danger alert-soft small mb-0">{!! icon('ban',14) !!} Anggota sedang diblokir.</div>
                        @else
                            <span class="badge badge-success">Dapat meminjam</span>
                        @endif
                        <a href="{{ route('loans.create') }}" class="btn btn-soft btn-sm mt-3 d-block">Ganti Anggota</a>
                    </div>
                @else
                    <div class="text-center text-muted py-4">
                        {!! icon('user-search', 30) !!}<div class="mt-2 small">Cari dan pilih anggota terlebih dahulu.</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Langkah 2 — Pilih Buku</h3>
                @if($member)<span class="badge badge-info">Jatuh tempo: {{ tanggal(today()->addDays($loanDays)) }}</span>@endif</div>
            <div class="card-body-soft">
                @error('member_id')<div class="alert alert-danger alert-soft small">{{ $message }}</div>@enderror
                @if(!$member)
                    <div class="text-center text-muted py-5">{!! icon('book-open', 32) !!}<div class="mt-2">Pilih anggota untuk menampilkan daftar buku.</div></div>
                @else
                    <form method="GET" class="mb-3">
                        <input type="hidden" name="member_id" value="{{ $member->id }}">
                        <div class="input-group">
                            <input name="book_q" class="form-control" value="{{ request('book_q') }}" placeholder="Cari judul, ISBN, atau penulis...">
                            <button class="btn btn-outline-teal">{!! icon('search',15) !!} Cari Buku</button>
                        </div>
                    </form>
                <form method="POST" action="{{ route('loans.store') }}">
                    @csrf
                    <input type="hidden" name="member_id" value="{{ $member->id }}">
                    <div class="table-wrap" style="max-height:400px;overflow-y:auto">
                        <table class="table-soft">
                            <thead><tr><th style="width:36px"></th><th>Judul Buku</th><th>Penulis</th><th>Katalog</th><th>Tersedia</th></tr></thead>
                            <tbody>
                                @forelse($books as $book)
                                <tr>
                                    <td><input class="form-check-input" type="checkbox" name="book_ids[]" value="{{ $book->id }}" id="book{{ $book->id }}"
                                        {{ in_array($book->id, old('book_ids', [])) ? 'checked' : '' }}></td>
                                    <td><label for="book{{ $book->id }}" class="cell-primary mb-0">{{ $book->title }}</label>
                                        <div class="cell-secondary">{{ $book->book_code }}</div></td>
                                    <td>{{ $book->author->name }}</td>
                                    <td>{{ $book->category->name }}</td>
                                    <td><span class="badge badge-success">{{ $book->available }}</span></td>
                                </tr>
                                @empty<tr><td colspan="5" class="table-empty">Tidak ada buku tersedia.</td></tr>@endforelse
                            </tbody>
                        </table>
                    </div>
                    @error('book_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('book_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="mt-3">
                        <label class="form-label">Catatan (opsional)</label>
                        <input name="notes" class="form-control" value="{{ old('notes') }}" maxlength="500" placeholder="Catatan transaksi">
                    </div>
                    <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-3">
                        <span class="cell-secondary">Maksimal {{ $maxBooks }} buku · Durasi {{ $loanDays }} hari</span>
                        <button class="btn btn-teal" {!! icon('check-check',16) !!} Konfirmasi Peminjaman</button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

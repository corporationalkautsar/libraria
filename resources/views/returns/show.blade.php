@extends('layouts.app')
@section('title', 'Detail Pengembalian')
@section('subtitle', 'Nomor '.$bookReturn->return_number)

@section('content')
<div class="page-head">
    <div><h1>Detail Pengembalian</h1><p>{{ $bookReturn->return_number }} · {{ tanggal($bookReturn->return_date, true) }}</p></div>
    <div class="d-flex gap-2">
        <a href="{{ route('returns.index') }}" class="btn btn-soft">{!! icon('arrow-left',16) !!} Kembali</a>
        <a href="{{ route('loans.show', $bookReturn->loan) }}" class="btn btn-outline-teal">{!! icon('book-open',16) !!} Lihat Pinjaman</a>
        @if($bookReturn->total_fine > 0 && $bookReturn->loan->fine)
            <a href="{{ route('fines.show', $bookReturn->loan->fine) }}" class="btn btn-teal">{!! icon('banknote',16) !!} Lihat Denda</a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><x-stat-card label="Jumlah Buku" :value="$bookReturn->total_books" icon="book-open" tone="teal" /></div>
    <div class="col-md-3"><x-stat-card label="Total Denda" :value="rupiah($bookReturn->total_fine)" icon="banknote" tone="red" /></div>
    <div class="col-md-3"><x-stat-card label="Jatuh Tempo" :value="tanggal($bookReturn->loan->due_date)" icon="calendar-clock" tone="amber" /></div>
    <div class="col-md-3"><x-stat-card label="Status Pinjaman" :value="$bookReturn->loan->status_label" icon="activity" tone="blue" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-soft">
            <div class="card-head"><h3 class="card-title">Buku yang Dikembalikan</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>No</th><th>Judul Buku</th><th>Hari Terlambat</th><th>Denda</th></tr></thead>
                    <tbody>
                        @foreach($bookReturn->details as $i => $detail)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td class="cell-primary">{{ $detail->book->title }}</td>
                            <td>{{ $detail->late_days ? $detail->late_days.' hari' : 'Tepat waktu' }}</td>
                            <td><strong class="{{ $detail->fine_amount>0 ? 'text-danger' : 'text-success' }}">{{ rupiah($detail->fine_amount) }}</strong></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card-soft">
            <div class="card-head"><h3 class="card-title">Informasi</h3></div>
            <div class="card-body-soft">
                <div class="mb-2"><div class="cell-secondary">Anggota</div><div class="cell-primary">{{ $bookReturn->member->full_name }}</div></div>
                <div class="mb-2"><div class="cell-secondary">Sekolah</div><div class="cell-primary">{{ $bookReturn->member->school }}</div></div>
                <div class="mb-2"><div class="cell-secondary">Asal</div><div class="cell-primary">{{ $bookReturn->member->origin }}</div></div>
                <div class="mb-2"><div class="cell-secondary">Petugas Penerima</div><div class="cell-primary">{{ $bookReturn->user->name }}</div></div>
                <div class="mb-2"><div class="cell-secondary">Waktu Pengembalian</div><div class="cell-primary">{{ tanggal($bookReturn->return_date, true) }}</div></div>
            </div>
        </div>
    </div>
</div>
@endsection

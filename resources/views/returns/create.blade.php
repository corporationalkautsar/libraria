@extends('layouts.app')
@section('title', 'Proses Pengembalian')
@section('subtitle', 'Pilih transaksi dan buku yang dikembalikan')

@section('content')
<x-page-head title="Proses Pengembalian" subtitle="Dukung pengembalian sebagian. Denda dihitung otomatis saat konfirmasi." />
<form class="filter-bar" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-6"><label>Cari Transaksi</label>
            <input name="q" class="form-control" value="{{ request('q') }}" placeholder="No. transaksi, nama, atau ID anggota"></div>
        <div class="col-md-2"><button class="btn btn-teal">{!! icon('search',15) !!} Cari</button></div>
    </div>
</form>

@if($loans->isNotEmpty())
<div class="card-soft mb-3">
    <div class="card-head"><h3 class="card-title">Hasil Pencarian</h3></div>
    <div class="list-group list-group-flush">
        @foreach($loans as $item)
        <a href="{{ route('returns.create', ['loan_id'=>$item->id]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <span><strong>{{ $item->loan_number }}</strong> — {{ $item->member->full_name }}<br><small class="text-muted">{{ $item->member->member_code }} · {{ $item->total_books }} buku</small></span>
            {!! status_badge($item->status) !!}
        </a>
        @endforeach
    </div>
</div>
@endif

@if($loan)
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Detail Transaksi</h3></div>
            <div class="card-body-soft">
                <div class="mb-2"><div class="cell-secondary">Nomor Transaksi</div><div class="cell-primary">{{ $loan->loan_number }}</div></div>
                <div class="mb-2"><div class="cell-secondary">Anggota</div><div class="cell-primary">{{ $loan->member->full_name }}</div></div>
                <div class="mb-2"><div class="cell-secondary">Sekolah / Asal</div><div class="cell-primary">{{ $loan->member->school }} · {{ $loan->member->origin }}</div></div>
                <div class="mb-2"><div class="cell-secondary">Tanggal Pinjam</div><div class="cell-primary">{{ tanggal($loan->loan_date) }}</div></div>
                <div class="mb-2"><div class="cell-secondary">Jatuh Tempo</div><div class="cell-primary">{{ tanggal($loan->due_date) }}</div></div>
                <div class="mb-2"><div class="cell-secondary">Petugas Peminjaman</div><div class="cell-primary">{{ $loan->user->name }}</div></div>
                <div>{!! status_badge($loan->status) !!}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <form method="POST" action="{{ route('returns.store') }}" class="card-soft h-100">
            @csrf
            <input type="hidden" name="loan_id" value="{{ $loan->id }}">
            <div class="card-head"><h3 class="card-title">Pilih Buku yang Dikembalikan</h3>
                <span class="badge badge-info">Tarif: {{ rupiah($finePerDay) }}/hari</span></div>
            <div class="card-body-soft">
                @error('detail_ids')<div class="alert alert-danger alert-soft small">{{ $message }}</div>@enderror
                <div class="table-wrap">
                    <table class="table-soft">
                        <thead><tr><th style="width:36px"></th><th>Judul Buku</th><th>Status</th><th>Keterlambatan</th><th>Estimasi Denda</th></tr></thead>
                        <tbody>
                            @foreach($loan->details as $detail)
                            @php
                                $late = today()->toDateString() > $loan->due_date->toDateString()
                                    ? $loan->due_date->diffInDays(today()->toDateString()) : 0;
                            @endphp
                            <tr>
                                <td>
                                    @if($detail->returned_at)
                                        {!! icon('circle-check',16) !!}
                                    @else
                                    <input class="form-check-input" type="checkbox" name="detail_ids[]" value="{{ $detail->id }}" id="detail{{ $detail->id }}">
                                    @endif
                                </td>
                                <td><label for="detail{{ $detail->id }}" class="cell-primary mb-0">{{ $detail->book->title }}</label></td>
                                <td>{!! status_badge($detail->status) !!}</td>
                                <td>{{ $detail->returned_at ? 'Sudah dikembalikan' : ($late ? $late.' hari' : 'Tepat waktu') }}</td>
                                <td>{{ $detail->returned_at ? '-' : rupiah($late*$finePerDay) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info alert-soft mt-3 small mb-0">
                    {!! icon('info',15) !!} Denda dihitung otomatis berdasarkan tanggal jatuh tempo dan tarif per hari.
                    Stok buku bertambah setelah transaksi disimpan.
                </div>
            </div>
            <div class="card-body-soft border-top d-flex justify-content-between align-items-center">
                <a href="{{ route('returns.create') }}" class="btn btn-soft">Ganti Transaksi</a>
                <button class="btn btn-teal" {!! icon('check-check',16) !!} Konfirmasi Pengembalian</button>
            </div>
        </form>
    </div>
</div>
@else
<div class="card-soft">
    <div class="card-body-soft text-center text-muted py-5">
        {!! icon('search', 32) !!}<div class="mt-2">Cari transaksi peminjaman untuk memproses pengembalian.</div>
    </div>
</div>
@endif
@endsection

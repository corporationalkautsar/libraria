@extends('layouts.app')
@section('title', auth()->user()->isAdmin() ? 'Monitoring Denda' : 'Data Denda')
@section('subtitle', 'Denda keterlambatan dihitung otomatis')

@section('content')
@php $isPetugas = auth()->user()->isPetugas(); @endphp
<div class="page-head">
    <div><h1>{{ $isPetugas ? 'Data Denda' : 'Monitoring Denda' }}</h1>
        <p>Pantau denda anggota dan catat pembayaran yang diterima petugas.</p></div>
    <div class="d-flex gap-2">
        <a href="{{ route('fines.history') }}" class="btn btn-soft">{!! icon('receipt',16) !!} Riwayat Pembayaran</a>
        @if($isPetugas)<a href="{{ route('returns.create') }}" class="btn btn-teal">{!! icon('rotate-ccw',16) !!} Proses Pengembalian</a>@endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><x-stat-card label="Total Denda" :value="rupiah($totals['total'])" icon="banknote" tone="blue" /></div>
    <div class="col-md-4"><x-stat-card label="Sudah Dibayar" :value="rupiah($totals['paid'])" icon="circle-check" tone="teal" /></div>
    <div class="col-md-4"><x-stat-card label="Belum Dibayar" :value="rupiah($totals['unpaid'])" icon="triangle-alert" tone="red" /></div>
</div>

<form class="filter-bar" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label>Cari</label><input name="q" class="form-control" value="{{ request('q') }}" placeholder="No. denda / nama anggota"></div>
        <div class="col-md-2"><label>Status</label>
            <select name="status" class="form-select"><option value="">Semua Status</option>
                @foreach(['belum_dibayar','dibayar_sebagian','lunas'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label>Filter Tanggal</label>
            <select name="date_filter" class="form-select">
                @foreach(App\Support\DateFilter::OPTIONS as $key=>$label)<option value="{{ $key }}" @selected(request('date_filter')===$key)>{{ $label }}</option>@endforeach
            </select></div>
        @if(request('date_filter')==='custom')
        <div class="col-md-2"><label>Dari</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-2"><label>Sampai</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        @endif
        <div class="col-md-2 filter-actions"><button class="btn btn-teal">{!! icon('filter',15) !!} Filter</button><a href="{{ route('fines.index') }}" class="btn btn-soft">Reset</a></div>
    </div>
</form>

<div class="card-soft">
    <div class="card-head"><h3 class="card-title">Daftar Denda ({{ $fines->total() }})</h3></div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr>
                <th>{!! sort_link('No. Denda','fine_number') !!}</th><th>Anggota</th><th>No. Pinjaman</th>
                <th>{!! sort_link('Total','amount') !!}</th><th>{!! sort_link('Dibayar','paid_amount') !!}</th><th>Sisa</th>
                <th>{!! sort_link('Status','status') !!}</th><th class="text-end">Aksi</th>
            </tr></thead>
            <tbody>
                @forelse($fines as $fine)
                <tr>
                    <td class="cell-primary">{{ $fine->fine_number }}</td>
                    <td>{{ $fine->member->full_name }}</td>
                    <td class="cell-secondary">{{ $fine->loan->loan_number }}</td>
                    <td>{{ rupiah($fine->amount) }}</td>
                    <td class="text-success">{{ rupiah($fine->paid_amount) }}</td>
                    <td><strong class="text-danger">{{ rupiah($fine->remaining) }}</strong></td>
                    <td>{!! status_badge($fine->status) !!}</td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('fines.show', $fine) }}" class="btn btn-outline-teal btn-sm">{!! icon('eye',14) !!}</a>
                            @if($isPetugas && $fine->remaining > 0)
                                <a href="{{ route('fines.pay', $fine) }}" class="btn btn-teal btn-sm">{!! icon('banknote',14) !!} Bayar</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty<tr><td colspan="8" class="table-empty">Tidak ada data denda.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body-soft border-top">{{ $fines->links() }}</div>
</div>
@endsection

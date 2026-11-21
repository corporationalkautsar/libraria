@extends('layouts.app')
@section('title', 'Riwayat Pembayaran Denda')
@section('subtitle', 'Seluruh pembayaran yang dicatat petugas')

@section('content')
<x-page-head title="Riwayat Pembayaran Denda" subtitle="Bukti pembayaran dapat dicetak dari halaman detail denda." />
<div class="row g-3 mb-3">
    <div class="col-md-4"><x-stat-card label="Total Pembayaran (Filter)" :value="rupiah($total)" icon="banknote" tone="teal" /></div>
    <div class="col-md-4"><x-stat-card label="Jumlah Transaksi" :value="$payments->total()" icon="receipt" tone="blue" /></div>
    <div class="col-md-4"><x-stat-card label="Tarif Denda / Hari" :value="rupiah(setting('fine_per_day'))" icon="coins" tone="amber" /></div>
</div>

<form class="filter-bar" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-4"><label>Cari</label><input name="q" class="form-control" value="{{ request('q') }}" placeholder="No. bukti / nama anggota"></div>
        <div class="col-md-2"><label>Filter Tanggal</label>
            <select name="date_filter" class="form-select">
                @foreach(App\Support\DateFilter::OPTIONS as $key=>$label)<option value="{{ $key }}" @selected(request('date_filter')===$key)>{{ $label }}</option>@endforeach
            </select></div>
        @if(request('date_filter')==='custom')
        <div class="col-md-2"><label>Dari</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-2"><label>Sampai</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        @endif
        <div class="col-md-2"><label>Petugas</label>
            <select name="petugas" class="form-select"><option value="">Semua</option>
                @foreach($staff as $user)<option value="{{ $user->id }}" @selected(request('petugas')==$user->id)>{{ $user->name }}</option>@endforeach
            </select></div>
        <div class="col-md-2 filter-actions"><button class="btn btn-teal">Filter</button><a href="{{ route('fines.history') }}" class="btn btn-soft">Reset</a></div>
    </div>
</form>

<div class="card-soft">
    <div class="card-head"><h3 class="card-title">Daftar Pembayaran</h3></div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr><th>No. Bukti</th><th>Anggota</th><th>No. Denda</th><th>Jumlah</th><th>Metode</th><th>Tanggal</th><th>Petugas</th><th></th></tr></thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td class="cell-primary">{{ $payment->receipt_number }}</td>
                    <td>{{ $payment->fine->member->full_name }}</td>
                    <td class="cell-secondary">{{ $payment->fine->fine_number }}</td>
                    <td><strong class="text-success">{{ rupiah($payment->amount) }}</strong></td>
                    <td>{{ ucfirst($payment->method) }}</td>
                    <td>{{ tanggal($payment->payment_date, true) }}</td>
                    <td class="cell-secondary">{{ $payment->user->name }}</td>
                    <td><a href="{{ route('fines.receipt', [$payment->fine, $payment]) }}" class="btn btn-outline-teal btn-sm">{!! icon('printer',14) !!}</a></td>
                </tr>
                @empty<tr><td colspan="8" class="table-empty">Belum ada pembayaran.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body-soft border-top">{{ $payments->links() }}</div>
</div>
@endsection

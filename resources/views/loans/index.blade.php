@extends('layouts.app')
@section('title', auth()->user()->isAdmin() ? 'Riwayat Peminjaman' : 'Riwayat Transaksi')
@section('subtitle', 'Semua transaksi peminjaman tersimpan permanen')

@section('content')
@php $isPetugas = auth()->user()->isPetugas(); @endphp
<div class="page-head">
    <div><h1>{{ $isPetugas ? 'Riwayat Transaksi' : 'Riwayat Peminjaman' }}</h1>
        <p>Filter berdasarkan tanggal, status, dan petugas. Klik detail untuk melihat buku yang dipinjam.</p></div>
    @if($isPetugas)<div><a href="{{ route('loans.create') }}" class="btn btn-teal">{!! icon('plus',16) !!} Peminjaman Baru</a></div>@endif
</div>

<form class="filter-bar" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label>Cari Transaksi / Anggota</label>
            <input name="q" class="form-control" value="{{ request('q') }}" placeholder="PIN-... atau nama anggota"></div>
        <div class="col-md-2"><label>Status</label>
            <select name="status" class="form-select"><option value="">Semua Status</option>
                @foreach(['dipinjam','terlambat','selesai','dibatalkan'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach
            </select></div>
        <div class="col-md-2"><label>Filter Tanggal</label>
            <select name="date_filter" class="form-select">
                @foreach(App\Support\DateFilter::OPTIONS as $key=>$label)<option value="{{ $key }}" @selected(request('date_filter')===$key)>{{ $label }}</option>@endforeach
            </select></div>
        @if(request('date_filter')==='custom')
        <div class="col-md-2"><label>Dari</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div class="col-md-2"><label>Sampai</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        @endif
        <div class="col-md-2"><label>Petugas</label>
            <select name="petugas" class="form-select"><option value="">Semua Petugas</option>
                @foreach($staff as $user)<option value="{{ $user->id }}" @selected(request('petugas')==$user->id)>{{ $user->name }}</option>@endforeach
            </select></div>
        <div class="col-md-2 filter-actions">
            <button class="btn btn-teal">{!! icon('filter',15) !!} Filter</button>
            <a href="{{ route(request()->routeIs('transactions.*') ? 'transactions.index' : 'loans.index') }}" class="btn btn-soft">Reset</a>
        </div>
    </div>
</form>

<div class="card-soft">
    <div class="card-head"><h3 class="card-title">Daftar Transaksi ({{ $loans->total() }})</h3></div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr>
                <th>{!! sort_link('No. Transaksi','loan_number') !!}</th>
                <th>Anggota</th><th>Sekolah</th>
                <th>{!! sort_link('Tanggal Pinjam','loan_date') !!}</th>
                <th>{!! sort_link('Jatuh Tempo','due_date') !!}</th>
                <th>Jml</th><th>Petugas</th><th>{!! sort_link('Status','status') !!}</th><th></th>
            </tr></thead>
            <tbody>
                @forelse($loans as $loan)
                <tr>
                    <td class="cell-primary nowrap">{{ $loan->loan_number }}</td>
                    <td class="cell-primary">{{ $loan->member->full_name }}</td>
                    <td class="cell-secondary">{{ $loan->member->school }}</td>
                    <td class="nowrap">{{ tanggal($loan->loan_date) }}</td>
                    <td class="nowrap">{{ tanggal($loan->due_date) }}</td>
                    <td>{{ $loan->total_books }}</td>
                    <td class="cell-secondary">{{ $loan->user->name }}</td>
                    <td>{!! status_badge($loan->status) !!}</td>
                    <td><a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-teal btn-sm">{!! icon('eye',14) !!}</a></td>
                </tr>
                @empty<tr><td colspan="9" class="table-empty">Tidak ada transaksi yang cocok.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body-soft border-top">{{ $loans->links() }}</div>
</div>
@endsection

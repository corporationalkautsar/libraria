@extends('layouts.app')
@section('title', 'Riwayat Pengembalian')
@section('subtitle', 'Seluruh transaksi pengembalian buku')

@section('content')
@php $isPetugas = auth()->user()->isPetugas(); @endphp
<div class="page-head">
    <div><h1>Riwayat Pengembalian</h1><p>Data pengembalian dan denda yang dihitung otomatis.</p></div>
    @if($isPetugas)<div><a href="{{ route('returns.create') }}" class="btn btn-teal">{!! icon('rotate-ccw',16) !!} Pengembalian Baru</a></div>@endif
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4"><x-stat-card label="Total Pengembalian (Filter)" :value="$totals['count']" icon="rotate-ccw" tone="teal" /></div>
    <div class="col-md-4"><x-stat-card label="Total Denda Terkumpul" :value="rupiah($totals['fine'])" icon="banknote" tone="amber" /></div>
    <div class="col-md-4"><x-stat-card label="Tarif Denda / Hari" :value="rupiah(setting('fine_per_day'))" icon="coins" tone="blue" /></div>
</div>

<form class="filter-bar" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-3"><label>Cari</label><input name="q" class="form-control" value="{{ request('q') }}" placeholder="No. pengembalian / nama anggota"></div>
        <div class="col-md-2"><label>Status Pinjaman</label>
            <select name="status" class="form-select"><option value="">Semua</option>
                @foreach(['selesai','terlambat','dipinjam'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach
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
            <select name="petugas" class="form-select"><option value="">Semua</option>
                @foreach($staff as $user)<option value="{{ $user->id }}" @selected(request('petugas')==$user->id)>{{ $user->name }}</option>@endforeach
            </select></div>
        <div class="col-md-2 filter-actions"><button class="btn btn-teal">{!! icon('filter',15) !!} Filter</button><a href="{{ route('returns.index') }}" class="btn btn-soft">Reset</a></div>
    </div>
</form>

<div class="card-soft">
    <div class="card-head"><h3 class="card-title">Daftar Pengembalian ({{ $returns->total() }})</h3></div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr>
                <th>{!! sort_link('No. Pengembalian','return_number') !!}</th><th>Anggota</th><th>No. Pinjaman</th>
                <th>{!! sort_link('Tanggal','return_date') !!}</th><th>Jml Buku</th><th>{!! sort_link('Denda','total_fine') !!}</th><th>Petugas</th><th></th>
            </tr></thead>
            <tbody>
                @forelse($returns as $return)
                <tr>
                    <td class="cell-primary">{{ $return->return_number }}</td>
                    <td>{{ $return->member->full_name }}</td>
                    <td class="cell-secondary">{{ $return->loan->loan_number }}</td>
                    <td>{{ tanggal($return->return_date, true) }}</td>
                    <td>{{ $return->total_books }}</td>
                    <td><strong class="{{ $return->total_fine>0 ? 'text-danger' : 'text-success' }}">{{ rupiah($return->total_fine) }}</strong></td>
                    <td class="cell-secondary">{{ $return->user->name }}</td>
                    <td><a href="{{ route('returns.show', $return) }}" class="btn btn-outline-teal btn-sm">{!! icon('eye',14) !!}</a></td>
                </tr>
                @empty<tr><td colspan="8" class="table-empty">Belum ada pengembalian yang cocok.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body-soft border-top">{{ $returns->links() }}</div>
</div>
@endsection

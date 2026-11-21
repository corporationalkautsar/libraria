@extends('layouts.app')
@section('title', 'Monitoring Petugas')
@section('subtitle', 'Pantau kinerja dan aktivitas petugas perpustakaan')

@section('content')
<x-page-head title="Monitoring Petugas" subtitle="Ringkasan kinerja setiap petugas berdasarkan transaksi dan aktivitasnya." />

<form method="GET" action="{{ route('monitoring.index') }}" class="filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="search">Cari Petugas</label>
            <input type="search" id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Nama atau email petugas">
        </div>
        <div class="col-md-3">
            <label for="date_filter">Periode Transaksi</label>
            <select id="date_filter" name="date_filter" class="form-select">
                @foreach(\App\Support\DateFilter::OPTIONS as $value => $label)
                    <option value="{{ $value }}" @selected((string) request('date_filter', '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="date_from">Dari Tanggal</label>
            <input type="date" id="date_from" name="date_from" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-2">
            <label for="date_to">Sampai Tanggal</label>
            <input type="date" id="date_to" name="date_to" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-1">
            <div class="filter-actions">
                <button type="submit" class="btn btn-teal" title="Terapkan filter">{!! icon('filter', 15) !!}</button>
            </div>
        </div>
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="cell-secondary">Filter tanggal diterapkan pada tanggal transaksi dan aktivitas.</div>
            <a href="{{ route('monitoring.index') }}" class="btn btn-soft btn-sm">Reset Filter</a>
        </div>
    </div>
</form>

<div class="card-soft">
    <div class="card-head">
        <h3 class="card-title">Kinerja Petugas</h3>
        <span class="cell-secondary">{{ $staff->total() }} petugas ditemukan</span>
    </div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead>
                <tr>
                    <th>{!! sort_link('Petugas', 'name') !!}</th>
                    <th>{!! sort_link('Peminjaman', 'loans') !!}</th>
                    <th>{!! sort_link('Pengembalian', 'returns') !!}</th>
                    <th>{!! sort_link('Anggota Didaftarkan', 'members') !!}</th>
                    <th>{!! sort_link('Pembayaran', 'payments') !!}</th>
                    <th>{!! sort_link('Aktivitas', 'activity') !!}</th>
                    <th>{!! sort_link('Total', 'total') !!}</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $row)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="table-avatar">{{ strtoupper(Str::charAt($row->name, 0)) }}</span>
                            <div>
                                <div class="cell-primary">{{ $row->name }}</div>
                                <div class="cell-secondary">{{ $row->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ (int) $row->loans_count }}</td>
                    <td>{{ (int) $row->returns_count }}</td>
                    <td>{{ (int) $row->registered_members }}</td>
                    <td>{{ (int) $row->fine_payments_count }}</td>
                    <td>{{ (int) $row->activity_logs_count }}</td>
                    <td><span class="badge badge-success">{{ $row->total_activity }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('monitoring.show', $row->id) }}" class="btn btn-outline-teal btn-sm">
                            {!! icon('eye', 14) !!} Detail
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="table-empty">Belum ada petugas yang sesuai dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($staff->hasPages())
        <div class="card-body-soft d-flex justify-content-end">{{ $staff->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection

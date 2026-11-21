@extends('layouts.app')
@section('title', 'Log Aktivitas')
@section('subtitle', 'Riwayat seluruh aktivitas pengguna perpustakaan')

@section('content')
<x-page-head title="Log Aktivitas" subtitle="Pantau aktivitas admin dan petugas. Data log bersifat read-only." />

<form method="GET" action="{{ route('logs.index') }}" class="filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="search">Cari Log</label>
            <input type="search" id="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Aktivitas, deskripsi, atau IP">
        </div>
        <div class="col-md-3">
            <label for="activity">Jenis Aktivitas</label>
            <select id="activity" name="activity" class="form-select">
                <option value="">Semua Aktivitas</option>
                @foreach($activities as $option)
                    <option value="{{ $option }}" @selected(request('activity') === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="user_id">Pengguna</label>
            <select id="user_id" name="user_id" class="form-select">
                <option value="">Semua Pengguna</option>
                @foreach($users as $id => $name)
                    <option value="{{ $id }}" @selected((string) request('user_id') === (string) $id)>{{ $name }}</option>
                @endforeach
                <option value="sistem" @selected(request('user_id') === 'sistem')>Sistem</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="date_filter">Periode</label>
            <select id="date_filter" name="date_filter" class="form-select">
                @foreach(\App\Support\DateFilter::OPTIONS as $value => $label)
                    <option value="{{ $value }}" @selected((string) request('date_filter', '') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="date_from">Dari Tanggal</label>
            <input type="date" id="date_from" name="date_from" class="form-control" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-3">
            <label for="date_to">Sampai Tanggal</label>
            <input type="date" id="date_to" name="date_to" class="form-control" value="{{ request('date_to') }}">
        </div>
        <div class="col-md-2">
            <div class="filter-actions">
                <button type="submit" class="btn btn-teal">{!! icon('filter', 15) !!} Filter</button>
                <a href="{{ route('logs.index') }}" class="btn btn-soft">Reset</a>
            </div>
        </div>
    </div>
</form>

<div class="card-soft">
    <div class="card-head">
        <h3 class="card-title">Riwayat Aktivitas</h3>
        <span class="cell-secondary">{{ $logs->total() }} entri ditemukan</span>
    </div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead>
                <tr>
                    <th>{!! sort_link('Waktu', 'created_at') !!}</th>
                    <th>{!! sort_link('Jenis Aktivitas', 'activity') !!}</th>
                    <th>{!! sort_link('Pengguna', 'user_name') !!}</th>
                    <th>Deskripsi</th>
                    <th>{!! sort_link('IP Address', 'ip_address') !!}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="cell-secondary nowrap">{{ tanggal($log->created_at, true) }}</td>
                    <td><span class="badge badge-info">{{ $log->activity }}</span></td>
                    <td>
                        <div class="cell-primary">{{ $log->user_name ?: 'Sistem' }}</div>
                        <div class="cell-secondary">{{ $log->role ?: '-' }}</div>
                    </td>
                    <td>{{ $log->description }}</td>
                    <td class="cell-secondary nowrap">{{ $log->ip_address ?: '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="table-empty">Tidak ada log aktivitas yang sesuai dengan filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="card-body-soft d-flex justify-content-end">{{ $logs->links('pagination::bootstrap-5') }}</div>
    @endif
</div>
@endsection

@extends('layouts.app')
@section('title', 'Detail Petugas')
@section('subtitle', 'Rincian kinerja dan aktivitas petugas')

@section('content')
<x-page-head title="Detail Petugas" subtitle="Rincian transaksi dan aktivitas {{ $petugas->name }}." />

<div class="mb-3"><a href="{{ route('monitoring.index') }}" class="btn btn-soft btn-sm">{!! icon('arrow-left', 15) !!} Kembali ke Monitoring</a></div>

<div class="card-soft mb-3">
    <div class="card-body-soft d-flex flex-wrap align-items-center gap-3">
        <div class="avatar" style="width:62px;height:62px;font-size:24px">{{ strtoupper(Str::charAt($petugas->name, 0)) }}</div>
        <div class="flex-grow-1">
            <div class="cell-primary fs-5">{{ $petugas->name }}</div>
            <div class="cell-secondary">{{ $petugas->email }}</div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            {!! status_badge($petugas->role) !!}
            {!! $petugas->is_active ? status_badge('aktif') : status_badge('nonaktif') !!}
            <span class="cell-secondary">Bergabung {{ tanggal($petugas->created_at) }}</span>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Peminjaman" :value="$stats['loans']" icon="book-marked" tone="teal" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Pengembalian" :value="$stats['returns']" icon="rotate-ccw" tone="blue" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Anggota Didaftarkan" :value="$stats['members']" icon="user-plus" tone="purple" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Pembayaran" :value="$stats['payments']" icon="banknote" tone="amber" /></div>
    <div class="col-6 col-md-4 col-xl-4"><x-stat-card label="Total Aktivitas" :value="$stats['total']" icon="activity" tone="slate" foot="Peminjaman + pengembalian + anggota + pembayaran" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Peminjaman Terbaru</h3>
                <a href="{{ route('loans.index') }}" class="small">Lihat semua</a></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>No. Transaksi</th><th>Anggota</th><th>Tanggal</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentLoans as $loan)
                        <tr>
                            <td class="cell-primary">{{ $loan->loan_number }}</td>
                            <td>{{ $loan->member?->full_name ?? '-' }}</td>
                            <td class="cell-secondary nowrap">{{ tanggal($loan->loan_date) }}</td>
                            <td>{!! status_badge($loan->status) !!}</td>
                        </tr>
                        @empty<tr><td colspan="4" class="table-empty">Belum ada peminjaman.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Pengembalian Terbaru</h3>
                <a href="{{ route('returns.index') }}" class="small">Lihat semua</a></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>No. Pengembalian</th><th>Anggota</th><th>Tanggal</th><th>Denda</th></tr></thead>
                    <tbody>
                        @forelse($recentReturns as $return)
                        <tr>
                            <td class="cell-primary">{{ $return->return_number }}</td>
                            <td>{{ $return->member?->full_name ?? '-' }}</td>
                            <td class="cell-secondary nowrap">{{ tanggal($return->return_date) }}</td>
                            <td>{{ rupiah($return->total_fine) }}</td>
                        </tr>
                        @empty<tr><td colspan="4" class="table-empty">Belum ada pengembalian.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Pembayaran Denda Terbaru</h3>
                <a href="{{ route('fines.index') }}" class="small">Lihat semua</a></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>No. Kwitansi</th><th>Anggota</th><th>Tanggal</th><th>Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($recentPayments as $payment)
                        <tr>
                            <td class="cell-primary">{{ $payment->receipt_number }}</td>
                            <td>{{ $payment->fine?->member?->full_name ?? '-' }}</td>
                            <td class="cell-secondary nowrap">{{ tanggal($payment->payment_date) }}</td>
                            <td class="cell-primary">{{ rupiah($payment->amount) }}</td>
                        </tr>
                        @empty<tr><td colspan="4" class="table-empty">Belum ada pembayaran.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Aktivitas Terbaru</h3>
                <a href="{{ route('logs.index', ['user_id' => $petugas->id]) }}" class="small">Lihat log</a></div>
            <div class="card-body-soft">
                <ul class="mini-list">
                    @forelse($recentActivities as $log)
                    <li>
                        <span class="mini-rank">{!! icon('activity', 14) !!}</span>
                        <div class="flex-grow-1">
                            <div class="cell-primary">{{ $log->activity }}</div>
                            <div class="cell-secondary">{{ $log->description }}</div>
                        </div>
                        <span class="cell-secondary nowrap">{{ tanggal($log->created_at, true) }}</span>
                    </li>
                    @empty<li class="table-empty">Belum ada aktivitas.</li>@endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')
@section('title', 'Dashboard Petugas')
@section('subtitle', 'Pantau operasional perpustakaan hari ini')

@section('content')
<div class="page-head">
    <div>
        <h1>Selamat bertugas, {{ auth()->user()->name }} 👋</h1>
        <p>{{ tanggal(now()) }} · Semua transaksi dicatat secara langsung ke database lokal.</p>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Total Anggota" :value="$stats['total_members']" icon="users" tone="purple" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Peminjaman Hari Ini" :value="$stats['loans_today']" icon="book-marked" tone="teal" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Pengembalian Hari Ini" :value="$stats['returns_today']" icon="rotate-ccw" tone="blue" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Sedang Dipinjam" :value="$stats['borrowed']" icon="book-open" tone="amber" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Terlambat" :value="$stats['overdue']" icon="triangle-alert" tone="red" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Denda Belum Dibayar" :value="rupiah($stats['unpaid'])" icon="banknote" tone="red" /></div>
</div>

<div class="card-soft mb-3">
    <div class="card-head"><h3 class="card-title">Aksi Cepat</h3></div>
    <div class="card-body-soft">
        <div class="row g-3">
            <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('members.create') }}" class="quick-action h-100"><span class="qa-icon">{!! icon('user-plus', 18) !!}</span><span><span class="qa-title d-block">Tambah Anggota</span><span class="qa-desc">Data baru</span></span></a></div>
            <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('loans.create') }}" class="quick-action h-100"><span class="qa-icon">{!! icon('book-marked', 18) !!}</span><span><span class="qa-title d-block">Peminjaman Baru</span><span class="qa-desc">Catat transaksi</span></span></a></div>
            <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('returns.create') }}" class="quick-action h-100"><span class="qa-icon">{!! icon('rotate-ccw', 18) !!}</span><span><span class="qa-title d-block">Pengembalian</span><span class="qa-desc">Kembalikan buku</span></span></a></div>
            <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('fines.index') }}" class="quick-action h-100"><span class="qa-icon">{!! icon('banknote', 18) !!}</span><span><span class="qa-title d-block">Pembayaran Denda</span><span class="qa-desc">Catat pembayaran</span></span></a></div>
            <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('catalog.index') }}" class="quick-action h-100"><span class="qa-icon">{!! icon('search', 18) !!}</span><span><span class="qa-title d-block">Cari Buku</span><span class="qa-desc">Katalog online</span></span></a></div>
            <div class="col-6 col-md-4 col-xl-2"><a href="{{ route('transactions.index') }}" class="quick-action h-100"><span class="qa-icon">{!! icon('history', 18) !!}</span><span><span class="qa-title d-block">Riwayat</span><span class="qa-desc">Semua transaksi</span></span></a></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Buku Mendekati Jatuh Tempo</h3>
                <a href="{{ route('loans.index') }}" class="small">Lihat semua</a></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>Anggota</th><th>No. Transaksi</th><th>Jatuh Tempo</th><th>Sisa</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($dueSoon as $loan)
                        @php $days = now()->startOfDay()->diffInDays($loan->due_date, false); @endphp
                        <tr>
                            <td class="cell-primary">{{ $loan->member->full_name }}</td>
                            <td>{{ $loan->loan_number }}</td>
                            <td>{{ tanggal($loan->due_date) }}</td>
                            <td>{!! $days < 0 ? '<span class="badge badge-danger"> Terlambat '.abs($days).' hari</span>' : '<span class="badge badge-warning">'.$days.' hari</span>' !!}</td>
                            <td>{!! status_badge($loan->status) !!}</td>
                        </tr>
                        @empty<tr><td colspan="5" class="table-empty">Tidak ada buku yang jatuh tempo dalam 3 hari.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Aktivitas Transaksi Terbaru</h3></div>
            <div class="card-body-soft">
                <ul class="mini-list">
                    @forelse($recentLoans as $loan)
                    <li>
                        <span class="mini-rank">{!! icon('arrow-right-left', 14) !!}</span>
                        <div class="flex-grow-1">
                            <div class="cell-primary">{{ $loan->member->full_name }}</div>
                            <div class="cell-secondary">{{ $loan->loan_number }} · {{ $loan->total_books }} buku</div>
                        </div>
                        <span class="cell-secondary nowrap">{{ $loan->created_at->diffForHumans() }}</span>
                    </li>
                    @empty<li class="table-empty">Belum ada transaksi.</li>@endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Pengembalian Terbaru</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>No. Pengembalian</th><th>Anggota</th><th>Tanggal</th><th>Denda</th></tr></thead>
                    <tbody>
                        @forelse($recentReturns as $return)
                        <tr><td class="cell-primary">{{ $return->return_number }}</td><td>{{ $return->member->full_name }}</td><td>{{ tanggal($return->return_date, true) }}</td><td>{{ rupiah($return->total_fine) }}</td></tr>
                        @empty<tr><td colspan="4" class="table-empty">Belum ada pengembalian.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Aktivitas Saya</h3></div>
            <div class="card-body-soft">
                <ul class="mini-list">
                    @forelse($recentActivities as $log)
                    <li>
                        <span class="mini-rank">{!! icon('activity', 14) !!}</span>
                        <div class="flex-grow-1">
                            <div class="cell-primary">{{ $log->activity }}</div>
                            <div class="cell-secondary">{{ $log->description }}</div>
                        </div>
                        <span class="cell-secondary nowrap">{{ $log->created_at->diffForHumans() }}</span>
                    </li>
                    @empty<li class="table-empty">Belum ada aktivitas.</li>@endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

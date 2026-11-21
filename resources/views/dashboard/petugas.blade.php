@extends('layouts.app')
@section('title', 'Dashboard Petugas')
@section('subtitle', 'Pantau operasional perpustakaan hari ini')

@section('content')
<section class="hero-panel">
    <div class="hero-orb hero-orb-a"></div>
    <div class="hero-orb hero-orb-b"></div>
    <div class="hero-content">
        <span class="hero-eyebrow">{!! icon('zap', 13) !!} SHIFT OPERASIONAL</span>
        <h1>Selamat bertugas, {{ auth()->user()->name }} <span aria-hidden="true">👋</span></h1>
        <p>Catat peminjaman dan pengembalian dengan cepat. Semua transaksi tersimpan langsung ke database lokal.</p>
        <div class="hero-actions">
            <a href="{{ route('loans.create') }}" class="btn hero-btn-primary">{!! icon('book-marked', 16) !!} Peminjaman Baru</a>
            <a href="{{ route('returns.create') }}" class="btn hero-btn-ghost">{!! icon('rotate-ccw', 16) !!} Pengembalian</a>
            <a href="{{ route('members.create') }}" class="btn hero-btn-ghost">{!! icon('user-plus', 16) !!} Tambah Anggota</a>
        </div>
    </div>
    <div class="hero-side">
        <div class="hero-glass">
            <div class="hero-metric">
                <span class="hm-ico">{!! icon('book-marked', 17) !!}</span>
                <span><strong>{{ $stats['loans_today'] }}</strong><span>Peminjaman hari ini</span></span>
            </div>
        </div>
        <div class="hero-glass">
            <div class="hero-metric">
                <span class="hm-ico">{!! icon('rotate-ccw', 17) !!}</span>
                <span><strong>{{ $stats['returns_today'] }}</strong><span>Pengembalian hari ini</span></span>
            </div>
        </div>
        <div class="hero-glass">
            <div class="hero-metric">
                <span class="hm-ico">{!! icon('triangle-alert', 17) !!}</span>
                <span><strong>{{ $stats['overdue'] }}</strong><span>Buku terlambat</span></span>
            </div>
        </div>
        <div class="hero-date">{!! icon('calendar-days', 12) !!} {{ tanggal(now()) }}</div>
    </div>
</section>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Total Anggota" :value="$stats['total_members']" icon="users" tone="purple" foot="Terdaftar" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Pinjam Hari Ini" :value="$stats['loans_today']" icon="book-marked" tone="teal" foot="Transaksi baru" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Kembali Hari Ini" :value="$stats['returns_today']" icon="rotate-ccw" tone="blue" foot="Diterima" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Sedang Dipinjam" :value="$stats['borrowed']" icon="book-open" tone="amber" foot="Belum kembali" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Terlambat" :value="$stats['overdue']" icon="triangle-alert" tone="red" foot="Perlu ditagih" /></div>
    <div class="col-6 col-md-4 col-xl-2"><x-stat-card label="Denda Belum Dibayar" :value="rupiah($stats['unpaid'])" icon="banknote" tone="rose" foot="Outstanding" /></div>
</div>

<div class="panel tone-teal mb-3">
    <div class="panel-head">
        <div class="panel-heading">
            <span class="panel-icon">{!! icon('sparkles', 17) !!}</span>
            <div><h3 class="panel-title">Aksi Cepat</h3><div class="panel-sub">Pintasan kerja sehari-hari</div></div>
        </div>
    </div>
    <div class="panel-body">
        <div class="qa-grid">
            <a href="{{ route('members.create') }}" class="qa-tile tone-purple"><span class="qa-icon">{!! icon('user-plus', 18) !!}</span><span class="qa-title">Tambah Anggota</span><span class="qa-desc">Data anggota baru</span>{!! icon('arrow-up-right', 13) !!}</a>
            <a href="{{ route('loans.create') }}" class="qa-tile tone-teal"><span class="qa-icon">{!! icon('book-marked', 18) !!}</span><span class="qa-title">Peminjaman Baru</span><span class="qa-desc">Catat transaksi</span>{!! icon('arrow-up-right', 13) !!}</a>
            <a href="{{ route('returns.create') }}" class="qa-tile tone-blue"><span class="qa-icon">{!! icon('rotate-ccw', 18) !!}</span><span class="qa-title">Pengembalian</span><span class="qa-desc">Terima buku kembali</span>{!! icon('arrow-up-right', 13) !!}</a>
            <a href="{{ route('fines.index') }}" class="qa-tile tone-red"><span class="qa-icon">{!! icon('banknote', 18) !!}</span><span class="qa-title">Pembayaran Denda</span><span class="qa-desc">Catat pembayaran</span>{!! icon('arrow-up-right', 13) !!}</a>
            <a href="{{ route('catalog.index') }}" class="qa-tile tone-amber"><span class="qa-icon">{!! icon('search', 18) !!}</span><span class="qa-title">Cari Buku</span><span class="qa-desc">Telusuri katalog</span>{!! icon('arrow-up-right', 13) !!}</a>
            <a href="{{ route('transactions.index') }}" class="qa-tile tone-indigo"><span class="qa-icon">{!! icon('history', 18) !!}</span><span class="qa-title">Riwayat</span><span class="qa-desc">Semua transaksi</span>{!! icon('arrow-up-right', 13) !!}</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="panel tone-amber">
            <div class="panel-head">
                <div class="panel-heading">
                    <span class="panel-icon">{!! icon('alarm-clock', 17) !!}</span>
                    <div><h3 class="panel-title">Mendekati Jatuh Tempo</h3><div class="panel-sub">Buku dalam 3 hari ke depan</div></div>
                </div>
                <a href="{{ route('loans.index') }}" class="panel-link">Semua {!! icon('arrow-up-right', 13) !!}</a>
            </div>
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
                            <td>{!! $days < 0 ? '<span class="badge badge-danger"><span class="status-dot"></span>Terlambat '.abs($days).' hari</span>' : '<span class="badge badge-warning"><span class="status-dot"></span>'.$days.' hari</span>' !!}</td>
                            <td>{!! status_badge($loan->status) !!}</td>
                        </tr>
                        @empty<tr><td colspan="5" class="table-empty">Tidak ada buku yang jatuh tempo dalam 3 hari.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel tone-purple">
            <div class="panel-head">
                <div class="panel-heading">
                    <span class="panel-icon">{!! icon('activity', 17) !!}</span>
                    <div><h3 class="panel-title">Aktivitas Transaksi</h3><div class="panel-sub">Peminjaman terbaru</div></div>
                </div>
            </div>
            <div class="panel-body tight">
                <ul class="timeline">
                    @forelse($recentLoans as $i => $loan)
                    <li class="{{ ['tone-teal','tone-blue','tone-purple','tone-amber','tone-emerald','tone-rose'][$i % 6] }}">
                        <div class="timeline-time">{{ $loan->created_at->diffForHumans() }}</div>
                        <div class="timeline-title">{{ $loan->member->full_name }}</div>
                        <div class="timeline-desc">{{ $loan->loan_number }} · {{ $loan->total_books }} buku</div>
                    </li>
                    @empty<li class="table-empty">Belum ada transaksi.</li>@endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="panel tone-emerald">
            <div class="panel-head">
                <div class="panel-heading">
                    <span class="panel-icon">{!! icon('rotate-ccw', 17) !!}</span>
                    <div><h3 class="panel-title">Pengembalian Terbaru</h3><div class="panel-sub">Buku diterima kembali</div></div>
                </div>
            </div>
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
        <div class="panel tone-blue">
            <div class="panel-head">
                <div class="panel-heading">
                    <span class="panel-icon">{!! icon('circle-check', 17) !!}</span>
                    <div><h3 class="panel-title">Aktivitas Saya</h3><div class="panel-sub">Riwayat tindakan Anda</div></div>
                </div>
            </div>
            <div class="panel-body tight">
                <ul class="timeline">
                    @forelse($recentActivities as $i => $log)
                    <li class="{{ ['tone-teal','tone-blue','tone-purple','tone-amber','tone-emerald','tone-rose'][$i % 6] }}">
                        <div class="timeline-time">{{ $log->created_at->diffForHumans() }}</div>
                        <div class="timeline-title">{{ $log->activity }}</div>
                        <div class="timeline-desc">{{ $log->description }}</div>
                    </li>
                    @empty<li class="table-empty">Belum ada aktivitas.</li>@endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

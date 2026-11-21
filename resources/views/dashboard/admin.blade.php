@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('subtitle', 'Ringkasan menyeluruh kondisi perpustakaan')

@section('content')
@php
    $loanNow = end($monthlyLoans);
    $loanPrev = $monthlyLoans[count($monthlyLoans) - 2] ?? 0;
    $loanDelta = $loanPrev > 0 ? (int) round(($loanNow - $loanPrev) / $loanPrev * 100) : 0;
    $topPeak = max(1, (int) ($topBooks->max('loan_details_count') ?? 1));
    $staffPeak = max(1, (int) ($staffPerformance->max(fn ($s) => $s->loans_count + $s->returns_count + $s->fine_payments_count) ?? 1));
    $finePct = $fineSummary['total'] > 0 ? (int) round($fineSummary['paid'] / $fineSummary['total'] * 100) : 0;
@endphp

<section class="hero-panel">
    <div class="hero-orb hero-orb-a"></div>
    <div class="hero-orb hero-orb-b"></div>
    <div class="hero-content">
        <span class="hero-eyebrow">{!! icon('sparkles', 13) !!} RINGKASAN EKSEKUTIF</span>
        <h1>Selamat datang kembali, {{ auth()->user()->name }} <span aria-hidden="true">👋</span></h1>
        <p>Pantau koleksi, sirkulasi, dan performa tim dari satu layar. Semua data tersimpan lokal di perpustakaan.</p>
        <div class="hero-actions">
            <a href="{{ route('reports.index') }}" class="btn hero-btn-primary">{!! icon('file-chart-column', 16) !!} Lihat Laporan</a>
            <a href="{{ route('books.create') }}" class="btn hero-btn-ghost">{!! icon('plus', 16) !!} Tambah Buku</a>
            <a href="{{ route('monitoring.index') }}" class="btn hero-btn-ghost">{!! icon('activity', 16) !!} Monitoring</a>
        </div>
    </div>
    <div class="hero-side">
        <div class="hero-glass">
            <div class="hero-metric">
                <span class="hm-ico">{!! icon('book-open-check', 17) !!}</span>
                <span><strong>{{ $stats['borrowed'] }}</strong><span>Buku sedang dipinjam</span></span>
            </div>
        </div>
        <div class="hero-glass">
            <div class="hero-metric">
                <span class="hm-ico">{!! icon('triangle-alert', 17) !!}</span>
                <span><strong>{{ $stats['overdue'] }}</strong><span>Perlu ditindaklanjuti</span></span>
            </div>
        </div>
        <div class="hero-glass">
            <div class="hero-metric">
                <span class="hm-ico">{!! icon('banknote', 17) !!}</span>
                <span><strong>{{ rupiah($stats['unpaid']) }}</strong><span>Denda belum dibayar</span></span>
            </div>
        </div>
        <div class="hero-date">{!! icon('calendar-days', 12) !!} {{ tanggal(now()) }}</div>
    </div>
</section>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Eksemplar" :value="$stats['total_books']" icon="book-open" tone="teal" foot="Seluruh stok buku" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Judul Buku" :value="$stats['total_titles']" icon="library" tone="blue" foot="Judul berbeda" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Anggota" :value="$stats['total_members']" icon="users" tone="purple" foot="Terdaftar" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Katalog" :value="$stats['total_categories']" icon="library" tone="amber" foot="Kumpulan buku sejenis" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Peminjaman" :value="$stats['total_loans']" icon="book-marked" tone="indigo" foot="Sepanjang waktu" :delta="($loanDelta >= 0 ? '+' : '').$loanDelta.'%'" :direction="$loanDelta < 0 ? 'down' : 'up'" :series="$monthlyLoans" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Sedang Dipinjam" :value="$stats['borrowed']" icon="book-open-check" tone="teal" foot="Belum dikembalikan" :series="$monthlyLoans" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Buku Terlambat" :value="$stats['overdue']" icon="triangle-alert" tone="red" foot="Perlu ditindaklanjuti" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Denda Belum Dibayar" :value="rupiah($stats['unpaid'])" icon="banknote" tone="rose" foot="Outstanding" /></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="panel tone-teal">
            <div class="panel-head">
                <div class="panel-heading">
                    <span class="panel-icon">{!! icon('chart-no-axes-combined', 17) !!}</span>
                    <div><h3 class="panel-title">Sirkulasi Peminjaman</h3><div class="panel-sub">Perbandingan peminjaman dan pengembalian</div></div>
                </div>
                <span class="panel-link">{!! icon('calendar-days', 13) !!} 6 bulan terakhir</span>
            </div>
            <div class="panel-body"><div class="chart-box-v2"><canvas id="loanChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="panel tone-amber">
            <div class="panel-head">
                <div class="panel-heading">
                    <span class="panel-icon">{!! icon('chart-pie', 17) !!}</span>
                    <div><h3 class="panel-title">Katalog Terpopuler</h3><div class="panel-sub">Berdasarkan jumlah peminjaman</div></div>
                </div>
            </div>
            <div class="panel-body"><div class="chart-box-v2 sm"><canvas id="categoryChart"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="panel tone-amber">
            <div class="panel-head">
                <div class="panel-heading">
                    <span class="panel-icon">{!! icon('flame', 17) !!}</span>
                    <div><h3 class="panel-title">Buku Paling Dicari</h3><div class="panel-sub">Top 5 dari seluruh transaksi</div></div>
                </div>
                <a href="{{ route('reports.index') }}" class="panel-link">Detail {!! icon('arrow-up-right', 13) !!}</a>
            </div>
            <div class="panel-body tight">
                <ul class="rank-list">
                    @forelse($topBooks as $i => $book)
                    <li class="tone-teal">
                        <span class="rank-medal">{{ $i + 1 }}</span>
                        <div class="rank-main">
                            <div class="cell-primary text-truncate">{{ $book->title }}</div>
                            <div class="cell-secondary">{{ $book->author?->name ?? 'Penulis tidak diketahui' }}</div>
                            <div class="rank-bar"><span style="width:{{ round($book->loan_details_count / $topPeak * 100) }}%"></span></div>
                        </div>
                        <span class="rank-count">{{ $book->loan_details_count }}x</span>
                    </li>
                    @empty<li class="table-empty">Belum ada data peminjaman.</li>@endforelse
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="panel tone-purple">
            <div class="panel-head">
                <div class="panel-heading">
                    <span class="panel-icon">{!! icon('activity', 17) !!}</span>
                    <div><h3 class="panel-title">Aktivitas Terbaru</h3><div class="panel-sub">Jejak aktivitas di sistem</div></div>
                </div>
                <a href="{{ route('logs.index') }}" class="panel-link">Semua {!! icon('arrow-up-right', 13) !!}</a>
            </div>
            <div class="panel-body tight">
                <ul class="timeline">
                    @forelse($recentActivities as $i => $log)
                    <li class="{{ ['tone-teal','tone-blue','tone-purple','tone-amber','tone-emerald','tone-rose'][$i % 6] }}">
                        <div class="timeline-time">{{ $log->created_at->diffForHumans() }} · {{ $log->user?->name ?? 'Sistem' }}</div>
                        <div class="timeline-title">{{ $log->activity }}</div>
                        <div class="timeline-desc">{{ $log->description }}</div>
                    </li>
                    @empty<li class="table-empty">Belum ada aktivitas.</li>@endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="panel tone-blue">
            <div class="panel-head">
                <div class="panel-heading">
                    <span class="panel-icon">{!! icon('history', 17) !!}</span>
                    <div><h3 class="panel-title">Transaksi Terbaru</h3><div class="panel-sub">Peminjaman terkini</div></div>
                </div>
                <a href="{{ route('loans.index') }}" class="panel-link">Lihat semua {!! icon('arrow-up-right', 13) !!}</a>
            </div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>No. Transaksi</th><th>Anggota</th><th>Tanggal</th><th>Jatuh Tempo</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($recentLoans as $loan)
                        <tr>
                            <td class="cell-primary">{{ $loan->loan_number }}</td>
                            <td>{{ $loan->member->full_name }}</td>
                            <td>{{ tanggal($loan->loan_date) }}</td>
                            <td>{{ tanggal($loan->due_date) }}</td>
                            <td>{!! status_badge($loan->status) !!}</td>
                        </tr>
                        @empty<tr><td colspan="5" class="table-empty">Belum ada transaksi.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel tone-emerald">
            <div class="panel-head">
                <div class="panel-heading">
                    <span class="panel-icon">{!! icon('banknote', 17) !!}</span>
                    <div><h3 class="panel-title">Pembayaran Denda</h3><div class="panel-sub">Ringkasan outstanding</div></div>
                </div>
            </div>
            <div class="panel-body">
                <div class="d-flex align-items-center gap-4 flex-wrap">
                    <div class="ring" style="--value:{{ $finePct }}">
                        <div><strong>{{ $finePct }}%</strong><small>terbayar</small></div>
                    </div>
                    <div class="flex-grow-1" style="min-width:170px">
                        <div class="mini-metric"><span>Total denda</span><strong>{{ rupiah($fineSummary['total']) }}</strong></div>
                        <div class="mini-metric"><span>Sudah dibayar</span><strong class="text-success">{{ rupiah($fineSummary['paid']) }}</strong></div>
                        <div class="mini-metric"><span>Belum dibayar</span><strong class="text-danger">{{ rupiah($fineSummary['unpaid']) }}</strong></div>
                    </div>
                </div>
                <hr class="my-3">
                <div class="cell-primary mb-2" style="font-size:12px">Pembayaran terbaru</div>
                @forelse($recentPayments as $payment)
                <div class="mini-metric">
                    <span>{{ $payment->fine->member->full_name }}</span>
                    <strong>{{ rupiah($payment->amount) }}</strong>
                </div>
                @empty<div class="cell-secondary">Belum ada pembayaran.</div>@endforelse
            </div>
        </div>
    </div>
</div>

<div class="panel tone-indigo">
    <div class="panel-head">
        <div class="panel-heading">
            <span class="panel-icon">{!! icon('user-cog', 17) !!}</span>
            <div><h3 class="panel-title">Statistik Kinerja Petugas</h3><div class="panel-sub">Kontribusi transaksi per petugas</div></div>
        </div>
        <a href="{{ route('monitoring.index') }}" class="panel-link">Monitoring {!! icon('arrow-up-right', 13) !!}</a>
    </div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr><th>Nama Petugas</th><th>Peminjaman</th><th>Pengembalian</th><th>Anggota Didaftarkan</th><th>Pembayaran Dicatat</th><th>Total Transaksi</th></tr></thead>
            <tbody>
                @forelse($staffPerformance as $staff)
                @php($staffTotal = $staff->loans_count + $staff->returns_count + $staff->fine_payments_count)
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="table-avatar">{{ Str::charAt($staff->name, 0) }}</span>
                            <div><div class="cell-primary">{{ $staff->name }}</div><div class="cell-secondary">{{ $staff->email }}</div></div>
                        </div>
                    </td>
                    <td>{{ $staff->loans_count }}</td>
                    <td>{{ $staff->returns_count }}</td>
                    <td>{{ $staff->registered_members }}</td>
                    <td>{{ $staff->fine_payments_count }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge badge-success">{{ $staffTotal }}</span>
                            <span class="rank-bar" style="width:70px"><span style="width:{{ round($staffTotal / $staffPeak * 100) }}%"></span></span>
                        </div>
                    </td>
                </tr>
                @empty<tr><td colspan="6" class="table-empty">Belum ada petugas.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/js/chart.umd.js') }}"></script>
<script src="{{ asset('js/chart-theme.js') }}"></script>
<script>
    const chartLabels = @json($chartLabels);
    const categoryLabels = @json($categoryPopularity->pluck('name'));

    new Chart(document.getElementById('loanChart'), {
        type: 'bar',
        data: {
            labels: chartLabels,
            datasets: [
                Object.assign({ label: 'Peminjaman' }, ChartTheme.bar(@json($monthlyLoans), '#96603a', '#5d361f')),
                Object.assign({ label: 'Pengembalian' }, ChartTheme.bar(@json($monthlyReturns), '#ddb995', '#c99668'))
            ]
        },
        options: ChartTheme.barOptions()
    });

    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: @json($categoryPopularity->pluck('total')),
                backgroundColor: ChartTheme.palette.slice(0, Math.max(categoryLabels.length, 1)),
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: ChartTheme.doughnutOptions()
    });
</script>
@endpush

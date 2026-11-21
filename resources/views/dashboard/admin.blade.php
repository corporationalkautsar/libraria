@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('subtitle', 'Ringkasan menyeluruh kondisi perpustakaan')

@section('content')
<div class="page-head">
    <div>
        <h1>Selamat datang, {{ auth()->user()->name }} 👋</h1>
        <p>Berikut ringkasan aktivitas perpustakaan per {{ tanggal(now()) }}.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.index') }}" class="btn btn-outline-teal">{!! icon('file-chart-column', 16) !!} Lihat Laporan</a>
        <a href="{{ route('books.create') }}" class="btn btn-teal">{!! icon('plus', 16) !!} Tambah Buku</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Buku" :value="$stats['total_books']" icon="book-open" tone="teal" foot="Seluruh eksemplar" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Judul Buku" :value="$stats['total_titles']" icon="library" tone="blue" foot="Judul berbeda" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Anggota" :value="$stats['total_members']" icon="users" tone="purple" foot="Terdaftar" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Kategori" :value="$stats['total_categories']" icon="tags" tone="amber" foot="Koleksi kategori" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Peminjaman" :value="$stats['total_loans']" icon="book-marked" tone="teal" foot="Semua transaksi" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Buku Sedang Dipinjam" :value="$stats['borrowed']" icon="book-open-check" tone="blue" foot="Belum dikembalikan" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Buku Terlambat" :value="$stats['overdue']" icon="triangle-alert" tone="red" foot="Perlu ditindaklanjuti" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Denda Belum Dibayar" :value="rupiah($stats['unpaid'])" icon="banknote" tone="red" foot="Outstanding" /></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card-soft h-100">
            <div class="card-head">
                <h3 class="card-title">Statistik Peminjaman & Pengembalian</h3>
                <span class="cell-secondary">6 bulan terakhir</span>
            </div>
            <div class="card-body-soft"><div class="chart-box"><canvas id="loanChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Kategori Terpopuler</h3></div>
            <div class="card-body-soft"><div class="chart-box"><canvas id="categoryChart"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Buku Paling Sering Dipinjam</h3>
                <a href="{{ route('reports.index') }}" class="small">Detail</a></div>
            <div class="card-body-soft">
                <ul class="mini-list">
                    @forelse($topBooks as $i => $book)
                    <li>
                        <span class="mini-rank">{{ $i + 1 }}</span>
                        <div class="flex-grow-1">
                            <div class="cell-primary">{{ $book->title }}</div>
                            <div class="cell-secondary">{{ $book->author?->name }}</div>
                        </div>
                        <span class="badge badge-primary">{{ $book->loan_details_count }}x</span>
                    </li>
                    @empty<li class="table-empty">Belum ada data peminjaman.</li>@endforelse
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Aktivitas Petugas Terbaru</h3>
                <a href="{{ route('logs.index') }}" class="small">Lihat semua</a></div>
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

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Riwayat Transaksi Terbaru</h3>
                <a href="{{ route('loans.index') }}" class="small">Lihat semua</a></div>
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
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Ringkasan Pembayaran Denda</h3></div>
            <div class="card-body-soft">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Total denda</span><strong>{{ rupiah($fineSummary['total']) }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Sudah dibayar</span><strong class="text-success">{{ rupiah($fineSummary['paid']) }}</strong></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-muted">Belum dibayar</span><strong class="text-danger">{{ rupiah($fineSummary['unpaid']) }}</strong></div>
                @php $pct = $fineSummary['total'] > 0 ? round($fineSummary['paid'] / $fineSummary['total'] * 100) : 0; @endphp
                <div class="progress"><div class="progress-bar" style="width:{{ $pct }}%"></div></div>
                <div class="cell-secondary mt-1 text-end">{{ $pct }}% terbayar</div>
                <hr>
                <div class="cell-primary mb-2">Pembayaran terbaru</div>
                @forelse($recentPayments as $payment)
                <div class="d-flex justify-content-between small mb-1">
                    <span>{{ $payment->fine->member->full_name }}</span>
                    <strong>{{ rupiah($payment->amount) }}</strong>
                </div>
                @empty<div class="cell-secondary">Belum ada pembayaran.</div>@endforelse
            </div>
        </div>
    </div>
</div>

<div class="card-soft">
    <div class="card-head"><h3 class="card-title">Statistik Kinerja Petugas</h3></div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr><th>Nama Petugas</th><th>Peminjaman</th><th>Pengembalian</th><th>Anggota Didaftarkan</th><th>Pembayaran Dicatat</th><th>Total Transaksi</th></tr></thead>
            <tbody>
                @forelse($staffPerformance as $staff)
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
                    <td><span class="badge badge-success">{{ $staff->loans_count + $staff->returns_count + $staff->fine_payments_count }}</span></td>
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

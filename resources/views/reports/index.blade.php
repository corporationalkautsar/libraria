@extends('layouts.app')
@section('title', 'Laporan Perpustakaan')
@section('subtitle', 'Laporan menyeluruh koleksi, anggota, transaksi, dan kinerja petugas')

@section('content')
@php
    $exportParams = request()->only(['date_filter', 'date_from', 'date_to', 'petugas', 'kategori', 'status']);
@endphp

<div class="page-head">
    <div>
        <h1>Laporan Perpustakaan</h1>
        <p>{{ $filters['label'] }} · Diperbarui {{ $generated_at }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('reports.print', $exportParams) }}" class="btn btn-outline-teal">{!! icon('printer', 16) !!} Cetak</a>
        <a href="{{ route('reports.pdf', $exportParams) }}" class="btn btn-outline-teal">{!! icon('file-text', 16) !!} PDF</a>
        <a href="{{ route('reports.excel', $exportParams) }}" class="btn btn-teal">{!! icon('file-spreadsheet', 16) !!} Excel</a>
    </div>
</div>

<form method="GET" action="{{ route('reports.index') }}" class="filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-md-3">
            <label for="date_filter">Periode Transaksi</label>
            <select name="date_filter" id="date_filter" class="form-select">
                @foreach($options['dateFilters'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['date_filter'] === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 {{ $filters['date_filter'] === 'custom' ? '' : 'd-none' }}" id="customDates">
            <label for="date_from">Rentang Tanggal Khusus</label>
            <div class="d-flex gap-1">
                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $filters['date_from'] }}" placeholder="Dari">
                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $filters['date_to'] }}" placeholder="Sampai">
            </div>
        </div>
        <div class="col-md-2">
            <label for="petugas">Petugas</label>
            <select name="petugas" id="petugas" class="form-select">
                <option value="">Semua Petugas</option>
                @foreach($options['petugas'] as $user)
                    <option value="{{ $user->id }}" @selected($filters['petugas'] === (string) $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="kategori">Katalog</label>
            <select name="kategori" id="kategori" class="form-select">
                <option value="">Semua Katalog</option>
                @foreach($options['kategori'] as $category)
                    <option value="{{ $category->id }}" @selected($filters['kategori'] === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label for="status">Status</label>
            <select name="status" id="status" class="form-select">
                @foreach($options['status'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3 flex-wrap">
        <button type="submit" class="btn btn-teal">{!! icon('filter', 15) !!} Terapkan</button>
        <a href="{{ route('reports.index') }}" class="btn btn-soft">{!! icon('rotate-ccw', 15) !!} Reset</a>
        <span class="ms-auto align-self-center cell-secondary">Filter berlaku pada transaksi; katalog juga menyaring data koleksi.</span>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Judul Buku" :value="$stats['total_titles']" icon="library" tone="teal" foot="Koleksi terdaftar" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Eksemplar Buku" :value="$stats['total_copies']" icon="book-open" tone="blue" foot="Total stok" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Eksemplar Tersedia" :value="$stats['available_copies']" icon="book-open-check" tone="teal" foot="Siap dipinjam" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Sedang Dipinjam" :value="$stats['borrowed_copies']" icon="book-marked" tone="amber" foot="Belum kembali" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Total Anggota" :value="$stats['total_members']" icon="users" tone="purple" foot="Terdaftar" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Peminjaman" :value="$stats['total_loans']" icon="history" tone="teal" foot="Periode terfilter" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Pengembalian" :value="$stats['total_returns']" icon="rotate-ccw" tone="blue" foot="Periode terfilter" /></div>
    <div class="col-6 col-md-4 col-xl-3"><x-stat-card label="Denda Belum Dibayar" :value="rupiah($stats['unpaid_fine'])" icon="banknote" tone="red" foot="Outstanding" /></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card-soft h-100">
            <div class="card-head">
                <h3 class="card-title">Peminjaman & Pengembalian per Bulan</h3>
                <span class="cell-secondary">Data sesuai filter</span>
            </div>
            <div class="card-body-soft"><div class="chart-box"><canvas id="reportLoanChart"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Distribusi Koleksi per Katalog</h3></div>
            <div class="card-body-soft"><div class="chart-box"><canvas id="reportCategoryChart"></canvas></div></div>
        </div>
    </div>
</div>

{{-- Buku --}}
<div class="card-soft mb-3">
    <div class="card-head">
        <h3 class="card-title">Laporan Buku</h3>
        <span class="cell-secondary">{{ $buku['titles'] }} judul · {{ $buku['copies'] }} eksemplar · {{ $buku['available'] }} tersedia</span>
    </div>
    <div class="card-body-soft">
        <div class="row g-2 mb-3">
            <div class="col-md-3"><div class="quick-action"><div class="qa-icon">{!! icon('book-open', 17) !!}</div><div><div class="qa-title">{{ $buku['borrowed'] }}</div><div class="qa-desc">Eksemplar dipinjam</div></div></div></div>
            <div class="col-md-3"><div class="quick-action"><div class="qa-icon">{!! icon('book-x', 17) !!}</div><div><div class="qa-title">{{ $buku['out_of_stock'] }}</div><div class="qa-desc">Stok habis</div></div></div></div>
            <div class="col-md-3"><div class="quick-action"><div class="qa-icon">{!! icon('circle-slash', 17) !!}</div><div><div class="qa-title">{{ $buku['inactive'] }}</div><div class="qa-desc">Tidak aktif</div></div></div></div>
            <div class="col-md-3"><div class="quick-action"><div class="qa-icon">{!! icon('tags', 17) !!}</div><div><div class="qa-title">{{ $buku['per_category']->count() }}</div><div class="qa-desc">Katalog tercatat</div></div></div></div>
        </div>
    </div>
    <div class="row g-0">
        <div class="col-lg-4">
            <div class="card-head"><h3 class="card-title">Per Katalog</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>Katalog</th><th>Judul</th><th>Eksemplar</th><th>Tersedia</th></tr></thead>
                    <tbody>
                        @forelse($buku['per_category'] as $item)
                        <tr><td class="cell-primary">{{ $item->name }}</td><td>{{ (int) $item->books_count }}</td><td>{{ (int) $item->total_stock }}</td><td>{{ (int) $item->total_available }}</td></tr>
                        @empty<tr><td colspan="4" class="table-empty">Belum ada katalog.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-head"><h3 class="card-title">Per Penerbit</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>Penerbit</th><th>Judul</th><th>Eksemplar</th></tr></thead>
                    <tbody>
                        @forelse($buku['per_publisher'] as $item)
                        <tr><td class="cell-primary">{{ $item->name }}</td><td>{{ (int) $item->total_judul }}</td><td>{{ (int) $item->total_eksemplar }}</td></tr>
                        @empty<tr><td colspan="3" class="table-empty">Belum ada penerbit.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-head"><h3 class="card-title">Buku Terpopuler</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>Judul</th><th>Dipinjam</th></tr></thead>
                    <tbody>
                        @forelse($buku['popular'] as $item)
                        <tr><td class="cell-primary">{{ $item->title }}</td><td><span class="badge badge-primary">{{ (int) $item->loan_details_count }}x</span></td></tr>
                        @empty<tr><td colspan="2" class="table-empty">Belum ada data peminjaman.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Anggota --}}
<div class="card-soft mb-3">
    <div class="card-head">
        <h3 class="card-title">Laporan Anggota</h3>
        <span class="cell-secondary">{{ $anggota['total'] }} anggota · {{ $anggota['ever_borrowed'] }} pernah pinjam</span>
    </div>
    <div class="row g-0">
        <div class="col-lg-4">
            <div class="card-head"><h3 class="card-title">Per Sekolah</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>Sekolah</th><th>Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($anggota['per_school'] as $item)
                        <tr><td class="cell-primary">{{ $item->school }}</td><td>{{ (int) $item->total }}</td></tr>
                        @empty<tr><td colspan="2" class="table-empty">Belum ada anggota.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-head"><h3 class="card-title">Per Asal</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>Asal</th><th>Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($anggota['per_origin'] as $item)
                        <tr><td class="cell-primary">{{ $item->origin }}</td><td>{{ (int) $item->total }}</td></tr>
                        @empty<tr><td colspan="2" class="table-empty">Belum ada anggota.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-head"><h3 class="card-title">Anggota Paling Sering Pinjam</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>Nama</th><th>Pinjaman</th></tr></thead>
                    <tbody>
                        @forelse($anggota['borrowers'] as $item)
                        <tr><td><div class="cell-primary">{{ $item->full_name }}</div><div class="cell-secondary">{{ $item->member_code }}</div></td><td><span class="badge badge-success">{{ (int) $item->loans_count }}</span></td></tr>
                        @empty<tr><td colspan="2" class="table-empty">Belum ada anggota yang meminjam.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Peminjaman --}}
<div class="card-soft mb-3">
    <div class="card-head">
        <h3 class="card-title">Laporan Peminjaman</h3>
        <span class="cell-secondary">{{ $peminjaman['total'] }} transaksi sesuai filter</span>
    </div>
    <div class="row g-0">
        <div class="col-lg-4">
            <div class="card-head"><h3 class="card-title">Per Hari</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>Tanggal</th><th>Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($peminjaman['per_hari'] as $item)
                        <tr><td>{{ tanggal($item->periode) }}</td><td>{{ (int) $item->total }}</td></tr>
                        @empty<tr><td colspan="2" class="table-empty">Belum ada transaksi.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-head"><h3 class="card-title">Per Bulan</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>Periode</th><th>Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($peminjaman['per_bulan'] as $item)
                        <tr><td>{{ \Illuminate\Support\Carbon::parse($item->periode.'-01')->translatedFormat('F Y') }}</td><td>{{ (int) $item->total }}</td></tr>
                        @empty<tr><td colspan="2" class="table-empty">Belum ada transaksi.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-head"><h3 class="card-title">Per Tahun</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>Tahun</th><th>Jumlah</th></tr></thead>
                    <tbody>
                        @forelse($peminjaman['per_tahun'] as $item)
                        <tr><td>{{ $item->periode }}</td><td>{{ (int) $item->total }}</td></tr>
                        @empty<tr><td colspan="2" class="table-empty">Belum ada transaksi.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="card-head"><h3 class="card-title">Daftar Transaksi</h3></div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr><th>No. Transaksi</th><th>Anggota</th><th>Petugas</th><th>Tanggal Pinjam</th><th>Jatuh Tempo</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($peminjaman['transactions'] as $item)
                <tr>
                    <td class="cell-primary">{{ $item->loan_number }}</td>
                    <td>{{ $item->member?->full_name ?? '-' }}</td>
                    <td>{{ $item->user?->name ?? '-' }}</td>
                    <td class="nowrap">{{ tanggal($item->loan_date) }}</td>
                    <td class="nowrap">{{ tanggal($item->due_date) }}</td>
                    <td>{!! status_badge($item->status) !!}</td>
                </tr>
                @empty<tr><td colspan="6" class="table-empty">Tidak ada transaksi pada periode ini.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="card-head"><h3 class="card-title">Buku Paling Sering Dipinjam (Periode Ini)</h3></div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr><th>Judul Buku</th><th>Jumlah Peminjaman</th></tr></thead>
            <tbody>
                @forelse($peminjaman['top_books'] as $item)
                <tr><td class="cell-primary">{{ $item->title }}</td><td><span class="badge badge-primary">{{ (int) $item->total }}x</span></td></tr>
                @empty<tr><td colspan="2" class="table-empty">Belum ada data.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Pengembalian --}}
<div class="card-soft mb-3">
    <div class="card-head">
        <h3 class="card-title">Laporan Pengembalian</h3>
        <span class="cell-secondary">{{ $pengembalian['on_time'] }} tepat waktu · {{ $pengembalian['late'] }} terlambat</span>
    </div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr><th>No. Pengembalian</th><th>Anggota</th><th>Tanggal Kembali</th><th>Jatuh Tempo</th><th>Keterangan</th><th>Petugas</th><th>Denda</th></tr></thead>
            <tbody>
                @forelse($pengembalian['history'] as $item)
                <tr>
                    <td class="cell-primary">{{ $item->return_number }}</td>
                    <td>{{ $item->member?->full_name ?? '-' }}</td>
                    <td class="nowrap">{{ tanggal($item->return_date, true) }}</td>
                    <td class="nowrap">{{ tanggal($item->loan?->due_date) }}</td>
                    <td>
                        @if($item->timing === 'terlambat')
                            <span class="badge badge-danger">Terlambat {{ (int) $item->late_days }} hari</span>
                        @elseif($item->timing === 'tepat_waktu')
                            <span class="badge badge-success">Tepat waktu</span>
                        @else
                            <span class="badge badge-secondary">Tanpa data</span>
                        @endif
                    </td>
                    <td>{{ $item->user?->name ?? '-' }}</td>
                    <td class="nowrap">{{ rupiah($item->total_fine) }}</td>
                </tr>
                @empty<tr><td colspan="7" class="table-empty">Tidak ada pengembalian pada periode ini.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Denda --}}
<div class="card-soft mb-3">
    <div class="card-head">
        <h3 class="card-title">Laporan Denda</h3>
        <span class="cell-secondary">Total {{ rupiah($denda['total_amount']) }} · Belum dibayar {{ rupiah($denda['unpaid_amount']) }}</span>
    </div>
    <div class="card-body-soft">
        <div class="row g-2">
            <div class="col-md-3"><div class="quick-action"><div class="qa-icon">{!! icon('banknote', 17) !!}</div><div><div class="qa-title">{{ rupiah($denda['paid_amount']) }}</div><div class="qa-desc">Sudah dibayar</div></div></div></div>
            <div class="col-md-3"><div class="quick-action"><div class="qa-icon">{!! icon('circle-alert', 17) !!}</div><div><div class="qa-title">{{ rupiah($denda['unpaid_amount']) }}</div><div class="qa-desc">Belum dibayar</div></div></div></div>
            <div class="col-md-3"><div class="quick-action"><div class="qa-icon">{!! icon('receipt', 17) !!}</div><div><div class="qa-title">{{ rupiah($denda['payment_total']) }}</div><div class="qa-desc">Total pembayaran</div></div></div></div>
            <div class="col-md-3"><div class="quick-action"><div class="qa-icon">{!! icon('list-checks', 17) !!}</div><div><div class="qa-title">{{ $denda['paid_count'] }}/{{ $denda['total_count'] }}</div><div class="qa-desc">Denda lunas</div></div></div></div>
        </div>
    </div>
    <div class="card-head"><h3 class="card-title">Daftar Denda</h3></div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr><th>No. Denda</th><th>Anggota</th><th>No. Pinjaman</th><th>Jumlah</th><th>Dibayar</th><th>Sisa</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($denda['fines'] as $item)
                <tr>
                    <td class="cell-primary">{{ $item->fine_number }}</td>
                    <td>{{ $item->member?->full_name ?? '-' }}</td>
                    <td>{{ $item->loan?->loan_number ?? '-' }}</td>
                    <td>{{ rupiah($item->amount) }}</td>
                    <td>{{ rupiah($item->paid_amount) }}</td>
                    <td>{{ rupiah($item->remaining) }}</td>
                    <td>{!! status_badge($item->status) !!}</td>
                </tr>
                @empty<tr><td colspan="7" class="table-empty">Tidak ada data denda.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="card-head"><h3 class="card-title">Riwayat Pembayaran</h3></div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr><th>No. Kwitansi</th><th>Anggota</th><th>Petugas</th><th>Tanggal</th><th>Jumlah</th><th>Metode</th></tr></thead>
            <tbody>
                @forelse($denda['payments'] as $item)
                <tr>
                    <td class="cell-primary">{{ $item->receipt_number }}</td>
                    <td>{{ $item->fine?->member?->full_name ?? '-' }}</td>
                    <td>{{ $item->user?->name ?? '-' }}</td>
                    <td class="nowrap">{{ tanggal($item->payment_date, true) }}</td>
                    <td>{{ rupiah($item->amount) }}</td>
                    <td>{{ ucfirst($item->method) }}</td>
                </tr>
                @empty<tr><td colspan="6" class="table-empty">Belum ada pembayaran.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Kinerja petugas --}}
<div class="card-soft mb-3">
    <div class="card-head">
        <h3 class="card-title">Kinerja Petugas</h3>
        <span class="cell-secondary">{{ $kinerja['total_served'] }} total transaksi dilayani</span>
    </div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr><th>Nama Petugas</th><th>Peminjaman</th><th>Pengembalian</th><th>Anggota Didaftarkan</th><th>Pembayaran</th><th>Total Dilayani</th></tr></thead>
            <tbody>
                @forelse($kinerja['petugas'] as $item)
                <tr>
                    <td class="cell-primary">{{ $item['name'] }}</td>
                    <td>{{ $item['loans'] }}</td>
                    <td>{{ $item['returns'] }}</td>
                    <td>{{ $item['registered'] }}</td>
                    <td>{{ $item['payments'] }}</td>
                    <td><span class="badge badge-success">{{ $item['total'] }}</span></td>
                </tr>
                @empty<tr><td colspan="6" class="table-empty">Belum ada petugas.</td></tr>@endforelse
                @if($kinerja['petugas']->isNotEmpty())
                <tr class="fw-bold">
                    <td>TOTAL</td>
                    <td>{{ $kinerja['total_loans'] }}</td>
                    <td>{{ $kinerja['total_returns'] }}</td>
                    <td>{{ $kinerja['total_registered'] }}</td>
                    <td>{{ $kinerja['total_payments'] }}</td>
                    <td>{{ $kinerja['total_served'] }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/js/chart.umd.js') }}"></script>
<script src="{{ asset('js/chart-theme.js') }}"></script>
<script>
    const dateFilterSelect = document.getElementById('date_filter');
    const customDates = document.getElementById('customDates');
    const toggleCustomDates = () => customDates.classList.toggle('d-none', dateFilterSelect.value !== 'custom');
    dateFilterSelect.addEventListener('change', toggleCustomDates);
    toggleCustomDates();

    const reportLabels = @json($charts['labels']);
    const reportCategoryLabels = @json($charts['categoryLabels']);

    new Chart(document.getElementById('reportLoanChart'), {
        type: 'bar',
        data: {
            labels: reportLabels,
            datasets: [
                Object.assign({ label: 'Peminjaman' }, ChartTheme.bar(@json($charts['loans']), '#96603a', '#5d361f')),
                Object.assign({ label: 'Pengembalian' }, ChartTheme.bar(@json($charts['returns']), '#ddb995', '#c99668'))
            ]
        },
        options: ChartTheme.barOptions()
    });

    new Chart(document.getElementById('reportCategoryChart'), {
        type: 'doughnut',
        data: {
            labels: reportCategoryLabels,
            datasets: [{
                data: @json($charts['categoryData']),
                backgroundColor: ChartTheme.palette.slice(0, Math.max(reportCategoryLabels.length, 1)),
                borderWidth: 0,
                hoverOffset: 6
            }]
        },
        options: ChartTheme.doughnutOptions()
    });
</script>
@endpush

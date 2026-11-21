<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Perpustakaan — {{ $library['library_name'] }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: 'DejaVu Serif', serif; font-size: 8.5px; color: #222; margin: 0; }
        h1 { font-size: 16px; margin: 0 0 2px 0; color: #5d361f; }
        h2 { font-size: 11px; margin: 12px 0 5px 0; color: #5d361f; border-bottom: 1px solid #96603a; padding-bottom: 2px; }
        h3 { font-size: 9px; margin: 8px 0 3px 0; color: #333; }
        p { margin: 2px 0; }
        .muted { color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
        th { background: #fbf4ed; color: #5d361f; border: 1px solid #e3d0bf; padding: 3px 4px; text-align: left; font-size: 7.5px; }
        td { border: 1px solid #d4dddf; padding: 3px 4px; vertical-align: top; }
        tr { page-break-inside: avoid; }
        .num { text-align: right; }
        .label { background: #fdf8f3; color: #5d361f; font-weight: bold; }
        .total { font-weight: bold; }
        .empty { text-align: center; color: #777; font-style: italic; }
        .header-table { border: 0; margin-bottom: 6px; }
        .header-table td { border: 0; padding: 0; }
        .summary td { padding: 4px; }
        .sign { margin-top: 18px; }
        .sign td { border: 0; width: 50%; text-align: center; padding-top: 34px; }
        .footer { margin-top: 12px; border-top: 1px solid #ddd; padding-top: 4px; text-align: center; color: #666; font-size: 7.5px; }
    </style>
</head>
<body>
<table class="header-table">
    <tr>
        <td>
            <h1>{{ $library['library_name'] }}</h1>
            <p>{{ $library['library_address'] }}</p>
            <p class="muted">Laporan Perpustakaan · Dicetak {{ $generated_at }} oleh {{ auth()->user()->name }}</p>
            <p class="muted">Periode: {{ $filters['label'] }}</p>
        </td>
    </tr>
</table>

<h2>Ringkasan Umum</h2>
<table class="summary">
    <tr>
        <td class="label">Judul Buku</td><td>{{ $stats['total_titles'] }}</td>
        <td class="label">Eksemplar</td><td>{{ $stats['total_copies'] }}</td>
        <td class="label">Tersedia</td><td>{{ $stats['available_copies'] }}</td>
        <td class="label">Dipinjam</td><td>{{ $stats['borrowed_copies'] }}</td>
    </tr>
    <tr>
        <td class="label">Anggota</td><td>{{ $stats['total_members'] }}</td>
        <td class="label">Peminjaman</td><td>{{ $stats['total_loans'] }}</td>
        <td class="label">Pengembalian</td><td>{{ $stats['total_returns'] }}</td>
        <td class="label">Terlambat</td><td>{{ $stats['late_returns'] }}</td>
    </tr>
    <tr>
        <td class="label" colspan="2">Denda Belum Dibayar</td>
        <td colspan="2">{{ rupiah($stats['unpaid_fine']) }}</td>
        <td class="label" colspan="2">Total Pembayaran Denda</td>
        <td colspan="2">{{ rupiah($denda['payment_total']) }}</td>
    </tr>
</table>

<h2>1. Laporan Buku</h2>
<p>Total {{ $buku['titles'] }} judul, {{ $buku['copies'] }} eksemplar, {{ $buku['available'] }} tersedia, {{ $buku['borrowed'] }} dipinjam, {{ $buku['out_of_stock'] }} stok habis, {{ $buku['inactive'] }} tidak aktif.</p>
<h3>Buku per Katalog</h3>
<table>
    <thead><tr><th>Katalog</th><th class="num">Judul</th><th class="num">Eksemplar</th><th class="num">Tersedia</th></tr></thead>
    <tbody>
        @forelse($buku['per_category'] as $item)
        <tr><td>{{ $item->name }}</td><td class="num">{{ (int) $item->books_count }}</td><td class="num">{{ (int) $item->total_stock }}</td><td class="num">{{ (int) $item->total_available }}</td></tr>
        @empty<tr><td colspan="4" class="empty">Belum ada data katalog.</td></tr>@endforelse
    </tbody>
</table>
<h3>Buku per Penerbit</h3>
<table>
    <thead><tr><th>Penerbit</th><th class="num">Judul</th><th class="num">Eksemplar</th><th class="num">Tersedia</th></tr></thead>
    <tbody>
        @forelse($buku['per_publisher'] as $item)
        <tr><td>{{ $item->name }}</td><td class="num">{{ (int) $item->total_judul }}</td><td class="num">{{ (int) $item->total_eksemplar }}</td><td class="num">{{ (int) $item->total_tersedia }}</td></tr>
        @empty<tr><td colspan="4" class="empty">Belum ada data penerbit.</td></tr>@endforelse
    </tbody>
</table>
<h3>Buku Paling Sering Dipinjam</h3>
<table>
    <thead><tr><th>Judul</th><th class="num">Jumlah Peminjaman</th></tr></thead>
    <tbody>
        @forelse($buku['popular'] as $item)
        <tr><td>{{ $item->title }}</td><td class="num">{{ (int) $item->loan_details_count }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada data peminjaman.</td></tr>@endforelse
    </tbody>
</table>

<h2>2. Laporan Anggota</h2>
<p>Total {{ $anggota['total'] }} anggota, {{ $anggota['ever_borrowed'] }} di antaranya pernah meminjam pada periode ini.</p>
<h3>Anggota per Sekolah</h3>
<table>
    <thead><tr><th>Sekolah</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($anggota['per_school'] as $item)
        <tr><td>{{ $item->school }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada anggota.</td></tr>@endforelse
    </tbody>
</table>
<h3>Anggota per Asal</h3>
<table>
    <thead><tr><th>Asal</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($anggota['per_origin'] as $item)
        <tr><td>{{ $item->origin }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada anggota.</td></tr>@endforelse
    </tbody>
</table>
<h3>Anggota Paling Sering Meminjam</h3>
<table>
    <thead><tr><th>Kode</th><th>Nama</th><th class="num">Peminjaman</th></tr></thead>
    <tbody>
        @forelse($anggota['borrowers'] as $item)
        <tr><td>{{ $item->member_code }}</td><td>{{ $item->full_name }}</td><td class="num">{{ (int) $item->loans_count }}</td></tr>
        @empty<tr><td colspan="3" class="empty">Belum ada anggota yang meminjam.</td></tr>@endforelse
    </tbody>
</table>

<h2>3. Laporan Peminjaman</h2>
<p>Total {{ $peminjaman['total'] }} transaksi. Rekap: {{ $peminjaman['per_hari']->count() }} hari, {{ $peminjaman['per_bulan']->count() }} bulan, {{ $peminjaman['per_tahun']->count() }} tahun.</p>
<h3>Rekap per Bulan</h3>
<table>
    <thead><tr><th>Periode</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($peminjaman['per_bulan'] as $item)
        <tr><td>{{ \Illuminate\Support\Carbon::parse($item->periode.'-01')->translatedFormat('F Y') }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada transaksi.</td></tr>@endforelse
    </tbody>
</table>
<h3>Rekap per Tahun</h3>
<table>
    <thead><tr><th>Tahun</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($peminjaman['per_tahun'] as $item)
        <tr><td>{{ $item->periode }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada transaksi.</td></tr>@endforelse
    </tbody>
</table>
<h3>Daftar Transaksi Peminjaman</h3>
<table>
    <thead><tr><th>No. Transaksi</th><th>Anggota</th><th>Petugas</th><th>Tanggal Pinjam</th><th>Jatuh Tempo</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($peminjaman['transactions'] as $item)
        <tr>
            <td>{{ $item->loan_number }}</td>
            <td>{{ $item->member?->full_name ?? '-' }}</td>
            <td>{{ $item->user?->name ?? '-' }}</td>
            <td>{{ tanggal($item->loan_date) }}</td>
            <td>{{ tanggal($item->due_date) }}</td>
            <td>{{ $item->status_label }}</td>
        </tr>
        @empty<tr><td colspan="6" class="empty">Tidak ada transaksi pada periode ini.</td></tr>@endforelse
    </tbody>
</table>
<h3>Buku Paling Sering Dipinjam (Periode Ini)</h3>
<table>
    <thead><tr><th>Judul Buku</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($peminjaman['top_books'] as $item)
        <tr><td>{{ $item->title }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada data.</td></tr>@endforelse
    </tbody>
</table>

<h2>4. Laporan Pengembalian</h2>
<p>Total {{ $pengembalian['total'] }} pengembalian: {{ $pengembalian['on_time'] }} tepat waktu dan {{ $pengembalian['late'] }} terlambat (dibandingkan tanggal jatuh tempo dengan waktu pengembalian).</p>
<table>
    <thead><tr><th>No. Pengembalian</th><th>Anggota</th><th>Tanggal Kembali</th><th>Jatuh Tempo</th><th>Keterangan</th><th>Petugas</th><th class="num">Denda</th></tr></thead>
    <tbody>
        @forelse($pengembalian['history'] as $item)
        <tr>
            <td>{{ $item->return_number }}</td>
            <td>{{ $item->member?->full_name ?? '-' }}</td>
            <td>{{ tanggal($item->return_date, true) }}</td>
            <td>{{ tanggal($item->loan?->due_date) }}</td>
            <td>
                @if($item->timing === 'terlambat') Terlambat {{ (int) $item->late_days }} hari
                @elseif($item->timing === 'tepat_waktu') Tepat waktu
                @else Tanpa data @endif
            </td>
            <td>{{ $item->user?->name ?? '-' }}</td>
            <td class="num">{{ rupiah($item->total_fine) }}</td>
        </tr>
        @empty<tr><td colspan="7" class="empty">Tidak ada pengembalian pada periode ini.</td></tr>@endforelse
    </tbody>
</table>

<h2>5. Laporan Denda</h2>
<table class="summary">
    <tr>
        <td class="label">Total Denda</td><td>{{ rupiah($denda['total_amount']) }}</td>
        <td class="label">Sudah Dibayar</td><td>{{ rupiah($denda['paid_amount']) }}</td>
        <td class="label">Belum Dibayar</td><td>{{ rupiah($denda['unpaid_amount']) }}</td>
        <td class="label">Total Pembayaran</td><td>{{ rupiah($denda['payment_total']) }}</td>
    </tr>
</table>
<h3>Daftar Denda</h3>
<table>
    <thead><tr><th>No. Denda</th><th>Anggota</th><th>No. Pinjaman</th><th class="num">Jumlah</th><th class="num">Dibayar</th><th class="num">Sisa</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($denda['fines'] as $item)
        <tr>
            <td>{{ $item->fine_number }}</td>
            <td>{{ $item->member?->full_name ?? '-' }}</td>
            <td>{{ $item->loan?->loan_number ?? '-' }}</td>
            <td class="num">{{ rupiah($item->amount) }}</td>
            <td class="num">{{ rupiah($item->paid_amount) }}</td>
            <td class="num">{{ rupiah($item->remaining) }}</td>
            <td>{{ ucwords(str_replace('_', ' ', $item->status)) }}</td>
        </tr>
        @empty<tr><td colspan="7" class="empty">Tidak ada data denda.</td></tr>@endforelse
    </tbody>
</table>
<h3>Riwayat Pembayaran</h3>
<table>
    <thead><tr><th>No. Kwitansi</th><th>Anggota</th><th>Petugas</th><th>Tanggal</th><th class="num">Jumlah</th><th>Metode</th></tr></thead>
    <tbody>
        @forelse($denda['payments'] as $item)
        <tr>
            <td>{{ $item->receipt_number }}</td>
            <td>{{ $item->fine?->member?->full_name ?? '-' }}</td>
            <td>{{ $item->user?->name ?? '-' }}</td>
            <td>{{ tanggal($item->payment_date, true) }}</td>
            <td class="num">{{ rupiah($item->amount) }}</td>
            <td>{{ ucfirst($item->method) }}</td>
        </tr>
        @empty<tr><td colspan="6" class="empty">Belum ada pembayaran.</td></tr>@endforelse
    </tbody>
</table>

<h2>6. Kinerja Petugas</h2>
<table>
    <thead><tr><th>Nama Petugas</th><th class="num">Peminjaman</th><th class="num">Pengembalian</th><th class="num">Anggota Didaftarkan</th><th class="num">Pembayaran</th><th class="num">Total Dilayani</th></tr></thead>
    <tbody>
        @forelse($kinerja['petugas'] as $item)
        <tr>
            <td>{{ $item['name'] }}</td>
            <td class="num">{{ $item['loans'] }}</td>
            <td class="num">{{ $item['returns'] }}</td>
            <td class="num">{{ $item['registered'] }}</td>
            <td class="num">{{ $item['payments'] }}</td>
            <td class="num">{{ $item['total'] }}</td>
        </tr>
        @empty<tr><td colspan="6" class="empty">Belum ada petugas.</td></tr>@endforelse
        @if($kinerja['petugas']->isNotEmpty())
        <tr class="total">
            <td>TOTAL</td>
            <td class="num">{{ $kinerja['total_loans'] }}</td>
            <td class="num">{{ $kinerja['total_returns'] }}</td>
            <td class="num">{{ $kinerja['total_registered'] }}</td>
            <td class="num">{{ $kinerja['total_payments'] }}</td>
            <td class="num">{{ $kinerja['total_served'] }}</td>
        </tr>
        @endif
    </tbody>
</table>

<table class="sign">
    <tr>
        <td>Mengetahui,<br><strong>Kepala {{ $library['library_name'] }}</strong><br><br>( ........................................ )</td>
        <td>{{ $library['library_name'] }}, {{ tanggal(now()) }}<br><strong>Petugas Pelapor</strong><br><br>( {{ auth()->user()->name }} )</td>
    </tr>
</table>
<div class="footer">Dokumen ini dihasilkan otomatis oleh sistem · Data bersumber dari database perpustakaan.</div>
</body>
</html>

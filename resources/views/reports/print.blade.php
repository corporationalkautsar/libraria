<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perpustakaan — {{ $library['library_name'] }}</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; color: #1f2937; margin: 0; padding: 18px; background: #fff; font-size: 11px; }
        .no-print { text-align: right; margin-bottom: 12px; }
        .no-print button { background: #96603a; color: #fff; border: 0; border-radius: 8px; padding: 9px 16px; font-size: 12px; cursor: pointer; }
        .report-header { display: table; width: 100%; border-bottom: 2px solid #96603a; padding-bottom: 10px; margin-bottom: 12px; }
        .report-header .logo-cell { display: table-cell; width: 80px; vertical-align: middle; }
        .report-header .text-cell { display: table-cell; vertical-align: middle; }
        .report-header img { width: 64px; height: 64px; object-fit: contain; }
        .logo-fallback { width: 60px; height: 60px; border-radius: 12px; background: #f6ede4; color: #96603a; font-size: 26px; font-weight: 700; text-align: center; line-height: 60px; }
        .report-header h1 { margin: 0; font-size: 19px; color: #5d361f; }
        .report-header p { margin: 3px 0 0; color: #5b6b78; font-size: 11px; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta td { border: 0; padding: 2px 0; font-size: 10.5px; }
        .meta td.label { width: 90px; color: #6b7280; font-weight: 700; }
        h2.section { font-size: 12.5px; color: #5d361f; margin: 16px 0 6px; border-left: 4px solid #96603a; padding-left: 8px; }
        h3.subsection { font-size: 10.5px; margin: 10px 0 4px; color: #374151; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 9.5px; }
        table.data thead { display: table-header-group; }
        table.data th { background: #fbf4ed; color: #5d361f; text-align: left; border: 1px solid #e6d6c7; padding: 5px 6px; font-size: 9px; text-transform: uppercase; }
        table.data td { border: 1px solid #dfe7e9; padding: 4px 6px; vertical-align: top; }
        table.data tr { page-break-inside: avoid; }
        table.data td.num, table.data th.num { text-align: right; }
        .empty { text-align: center; color: #6b7280; font-style: italic; padding: 8px !important; }
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .summary td { border: 1px solid #e6d6c7; padding: 6px 8px; font-size: 10px; }
        .summary td.label { background: #fdf8f3; color: #5d361f; font-weight: 700; width: 16%; }
        .summary td.value { font-weight: 700; }
        .signatures { margin-top: 28px; page-break-inside: avoid; }
        .signatures table { width: 100%; border-collapse: collapse; }
        .signatures td { border: 0; width: 50%; text-align: center; padding-top: 60px; font-size: 11px; }
        .footer { margin-top: 18px; border-top: 1px solid #e5e7eb; padding-top: 6px; text-align: center; color: #6b7280; font-size: 9px; }
    </style>
</head>
<body>
<div class="no-print">
    <button type="button" onclick="window.print()">Cetak Ulang</button>
</div>

<div class="report-header">
    <div class="logo-cell">
        @if(!empty($library['library_logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($library['library_logo']))
            <img src="{{ \Illuminate\Support\Facades\Storage::url($library['library_logo']) }}" alt="Logo Perpustakaan">
        @else
            <div class="logo-fallback">{{ strtoupper(\Illuminate\Support\Str::charAt($library['library_name'], 0)) }}</div>
        @endif
    </div>
    <div class="text-cell">
        <h1>{{ $library['library_name'] }}</h1>
        <p>{{ $library['library_address'] }}</p>
        <p>Laporan komprehensif kondisi perpustakaan · Sistem Informasi Manajemen Perpustakaan</p>
    </div>
</div>

<table class="meta">
    <tr><td class="label">Periode</td><td>{{ $filters['label'] }}</td></tr>
    <tr><td class="label">Dicetak</td><td>{{ $generated_at }} oleh {{ auth()->user()->name }}</td></tr>
</table>

<table class="summary">
    <tr>
        <td class="label">Judul Buku</td><td class="value">{{ $stats['total_titles'] }}</td>
        <td class="label">Eksemplar</td><td class="value">{{ $stats['total_copies'] }}</td>
        <td class="label">Tersedia</td><td class="value">{{ $stats['available_copies'] }}</td>
        <td class="label">Dipinjam</td><td class="value">{{ $stats['borrowed_copies'] }}</td>
    </tr>
    <tr>
        <td class="label">Anggota</td><td class="value">{{ $stats['total_members'] }}</td>
        <td class="label">Peminjaman</td><td class="value">{{ $stats['total_loans'] }}</td>
        <td class="label">Pengembalian</td><td class="value">{{ $stats['total_returns'] }}</td>
        <td class="label">Denda Belum Dibayar</td><td class="value">{{ rupiah($stats['unpaid_fine']) }}</td>
    </tr>
</table>

<h2 class="section">1. Laporan Buku</h2>
<table class="summary">
    <tr>
        <td class="label">Total Judul</td><td class="value">{{ $buku['titles'] }}</td>
        <td class="label">Total Eksemplar</td><td class="value">{{ $buku['copies'] }}</td>
        <td class="label">Tersedia</td><td class="value">{{ $buku['available'] }}</td>
        <td class="label">Stok Habis / Tidak Aktif</td><td class="value">{{ $buku['out_of_stock'] }} / {{ $buku['inactive'] }}</td>
    </tr>
</table>

<h3 class="subsection">Buku per Katalog</h3>
<table class="data">
    <thead><tr><th>Katalog</th><th class="num">Judul</th><th class="num">Eksemplar</th><th class="num">Tersedia</th></tr></thead>
    <tbody>
        @forelse($buku['per_category'] as $item)
        <tr><td>{{ $item->name }}</td><td class="num">{{ (int) $item->books_count }}</td><td class="num">{{ (int) $item->total_stock }}</td><td class="num">{{ (int) $item->total_available }}</td></tr>
        @empty<tr><td colspan="4" class="empty">Belum ada data katalog.</td></tr>@endforelse
    </tbody>
</table>

<h3 class="subsection">Buku per Penerbit</h3>
<table class="data">
    <thead><tr><th>Penerbit</th><th class="num">Judul</th><th class="num">Eksemplar</th><th class="num">Tersedia</th></tr></thead>
    <tbody>
        @forelse($buku['per_publisher'] as $item)
        <tr><td>{{ $item->name }}</td><td class="num">{{ (int) $item->total_judul }}</td><td class="num">{{ (int) $item->total_eksemplar }}</td><td class="num">{{ (int) $item->total_tersedia }}</td></tr>
        @empty<tr><td colspan="4" class="empty">Belum ada data penerbit.</td></tr>@endforelse
    </tbody>
</table>

<h3 class="subsection">Buku Paling Sering Dipinjam</h3>
<table class="data">
    <thead><tr><th>Judul</th><th class="num">Jumlah Peminjaman</th></tr></thead>
    <tbody>
        @forelse($buku['popular'] as $item)
        <tr><td>{{ $item->title }}</td><td class="num">{{ (int) $item->loan_details_count }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada data peminjaman.</td></tr>@endforelse
    </tbody>
</table>

<h2 class="section">2. Laporan Anggota</h2>
<table class="summary">
    <tr>
        <td class="label">Total Anggota</td><td class="value">{{ $anggota['total'] }}</td>
        <td class="label">Pernah Meminjam</td><td class="value">{{ $anggota['ever_borrowed'] }}</td>
        <td class="label">Jumlah Sekolah</td><td class="value">{{ $anggota['per_school']->count() }}</td>
        <td class="label">Jumlah Asal</td><td class="value">{{ $anggota['per_origin']->count() }}</td>
    </tr>
</table>

<h3 class="subsection">Anggota per Sekolah</h3>
<table class="data">
    <thead><tr><th>Sekolah</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($anggota['per_school'] as $item)
        <tr><td>{{ $item->school }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada anggota.</td></tr>@endforelse
    </tbody>
</table>

<h3 class="subsection">Anggota per Asal</h3>
<table class="data">
    <thead><tr><th>Asal</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($anggota['per_origin'] as $item)
        <tr><td>{{ $item->origin }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada anggota.</td></tr>@endforelse
    </tbody>
</table>

<h3 class="subsection">Anggota Paling Sering Meminjam</h3>
<table class="data">
    <thead><tr><th>Kode</th><th>Nama Anggota</th><th class="num">Jumlah Peminjaman</th></tr></thead>
    <tbody>
        @forelse($anggota['borrowers'] as $item)
        <tr><td>{{ $item->member_code }}</td><td>{{ $item->full_name }}</td><td class="num">{{ (int) $item->loans_count }}</td></tr>
        @empty<tr><td colspan="3" class="empty">Belum ada anggota yang meminjam.</td></tr>@endforelse
    </tbody>
</table>

<h2 class="section">3. Laporan Peminjaman</h2>
<table class="summary">
    <tr>
        <td class="label">Total Transaksi</td><td class="value">{{ $peminjaman['total'] }}</td>
        <td class="label">Per Hari</td><td class="value">{{ $peminjaman['per_hari']->count() }} hari</td>
        <td class="label">Per Bulan</td><td class="value">{{ $peminjaman['per_bulan']->count() }} bulan</td>
        <td class="label">Per Tahun</td><td class="value">{{ $peminjaman['per_tahun']->count() }} tahun</td>
    </tr>
</table>

<h3 class="subsection">Rekap per Hari</h3>
<table class="data">
    <thead><tr><th>Tanggal</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($peminjaman['per_hari'] as $item)
        <tr><td>{{ tanggal($item->periode) }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada transaksi.</td></tr>@endforelse
    </tbody>
</table>

<h3 class="subsection">Rekap per Bulan</h3>
<table class="data">
    <thead><tr><th>Periode</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($peminjaman['per_bulan'] as $item)
        <tr><td>{{ \Illuminate\Support\Carbon::parse($item->periode.'-01')->translatedFormat('F Y') }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada transaksi.</td></tr>@endforelse
    </tbody>
</table>

<h3 class="subsection">Rekap per Tahun</h3>
<table class="data">
    <thead><tr><th>Tahun</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($peminjaman['per_tahun'] as $item)
        <tr><td>{{ $item->periode }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada transaksi.</td></tr>@endforelse
    </tbody>
</table>

<h3 class="subsection">Daftar Transaksi Peminjaman</h3>
<table class="data">
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

<h3 class="subsection">Buku Paling Sering Dipinjam (Periode Ini)</h3>
<table class="data">
    <thead><tr><th>Judul Buku</th><th class="num">Jumlah</th></tr></thead>
    <tbody>
        @forelse($peminjaman['top_books'] as $item)
        <tr><td>{{ $item->title }}</td><td class="num">{{ (int) $item->total }}</td></tr>
        @empty<tr><td colspan="2" class="empty">Belum ada data.</td></tr>@endforelse
    </tbody>
</table>

<h2 class="section">4. Laporan Pengembalian</h2>
<table class="summary">
    <tr>
        <td class="label">Total Pengembalian</td><td class="value">{{ $pengembalian['total'] }}</td>
        <td class="label">Tepat Waktu</td><td class="value">{{ $pengembalian['on_time'] }}</td>
        <td class="label">Terlambat</td><td class="value">{{ $pengembalian['late'] }}</td>
        <td class="label">Tanpa Data Return</td><td class="value">{{ max(0, $pengembalian['total'] - $pengembalian['on_time'] - $pengembalian['late']) }}</td>
    </tr>
</table>
<table class="data">
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

<h2 class="section">5. Laporan Denda</h2>
<table class="summary">
    <tr>
        <td class="label">Total Denda</td><td class="value">{{ rupiah($denda['total_amount']) }}</td>
        <td class="label">Sudah Dibayar</td><td class="value">{{ rupiah($denda['paid_amount']) }}</td>
        <td class="label">Belum Dibayar</td><td class="value">{{ rupiah($denda['unpaid_amount']) }}</td>
        <td class="label">Total Pembayaran</td><td class="value">{{ rupiah($denda['payment_total']) }}</td>
    </tr>
</table>

<h3 class="subsection">Daftar Denda</h3>
<table class="data">
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

<h3 class="subsection">Riwayat Pembayaran</h3>
<table class="data">
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

<h2 class="section">6. Kinerja Petugas</h2>
<table class="data">
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
        <tr><td><strong>TOTAL</strong></td><td class="num"><strong>{{ $kinerja['total_loans'] }}</strong></td><td class="num"><strong>{{ $kinerja['total_returns'] }}</strong></td><td class="num"><strong>{{ $kinerja['total_registered'] }}</strong></td><td class="num"><strong>{{ $kinerja['total_payments'] }}</strong></td><td class="num"><strong>{{ $kinerja['total_served'] }}</strong></td></tr>
        @endif
    </tbody>
</table>

<div class="signatures">
    <table>
        <tr>
            <td>Mengetahui,<br><strong>Kepala {{ $library['library_name'] }}</strong><br><br><br>( ........................................ )</td>
            <td>{{ $library['library_name'] }}, {{ tanggal(now()) }}<br><strong>Petugas Pelapor</strong><br><br><br>( {{ auth()->user()->name }} )</td>
        </tr>
    </table>
</div>

<div class="footer">Dokumen ini dihasilkan otomatis oleh sistem pada {{ $generated_at }} · Data bersumber dari database perpustakaan.</div>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>
</body>
</html>

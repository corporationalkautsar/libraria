@php
    $operator = auth()->user()?->name ?: '-';
    $logoSource = null;

    try {
        $logoName = (string) ($library['library_logo'] ?? '');
        if ($logoName !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoName)) {
            $logoPath = \Illuminate\Support\Facades\Storage::disk('public')->path($logoName);
            $mime = function_exists('mime_content_type') ? mime_content_type($logoPath) : null;
            $mime = $mime ?: 'image/png';
            $logoSource = 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($logoPath));
        }
    } catch (\Throwable $e) {
        $logoSource = null;
    }

    $tone = static function (?string $text): string {
        $text = strtolower((string) $text);
        if (str_contains($text, 'terlambat') || str_contains($text, 'belum dibayar') || str_contains($text, 'tidak aktif') || str_contains($text, 'stok habis')) {
            return 'danger';
        }
        if (str_contains($text, 'lunas') || str_contains($text, 'tepat waktu') || str_contains($text, 'selesai') || str_contains($text, 'tersedia') || str_contains($text, 'aktif') || str_contains($text, 'diminati') || str_contains($text, 'baik')) {
            return 'success';
        }
        if (str_contains($text, 'sebagian') || str_contains($text, 'dipinjam') || str_contains($text, 'perlu perhatian') || str_contains($text, 'masih berjalan')) {
            return 'warning';
        }

        return 'neutral';
    };

    $badge = static function (?string $text, ?string $forcedTone = null) use ($tone): string {
        $text = $text ?: '-';
        $class = 'badge badge-'.($forcedTone ?: $tone($text));

        return '<span class="'.$class.'">'.e($text).'</span>';
    };

    $autoBadge = static fn (?string $text): string => $badge($text);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Perpustakaan — {{ $library['library_name'] }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm 9mm 13mm; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 8px; color: #2b1b12; margin: 0; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; }
        .cover { margin-bottom: 7px; }
        .cover td { border: 0; vertical-align: middle; }
        .cover-brand { width: 58px; }
        .cover-logo { width: 52px; height: 52px; object-fit: contain; }
        .cover-initial { width: 48px; height: 48px; background: #c68b59; color: #fff; font-size: 21px; font-weight: 700; text-align: center; line-height: 48px; border-radius: 8px; }
        .cover-copy { padding-left: 4px; }
        .eyebrow { font-size: 7px; letter-spacing: 1.1px; color: #c68b59; font-weight: 700; text-transform: uppercase; }
        .cover-title { font-size: 19px; font-weight: 700; color: #4a2e1b; letter-spacing: .6px; margin-top: 1px; }
        .cover-library { font-size: 10px; font-weight: 700; color: #6f4e37; margin-top: 1px; }
        .cover-address { font-size: 7.5px; color: #7a6a5f; margin-top: 1px; }
        .cover-stamp { width: 150px; text-align: right; padding-right: 4px; }
        .stamp-label { font-size: 6.5px; letter-spacing: 1px; color: #7a6a5f; font-weight: 700; }
        .stamp-date { font-size: 9px; color: #4a2e1b; font-weight: 700; margin-top: 2px; }
        .stamp-operator { font-size: 7.5px; color: #7a6a5f; margin-top: 1px; }

        .meta { margin-bottom: 8px; border-top: 2px solid #4a2e1b; border-bottom: 1px solid #eedfcc; }
        .meta td { padding: 5px 6px; border: 0; vertical-align: middle; font-size: 7.5px; }
        .meta .meta-label { width: 62px; color: #7a6a5f; font-weight: 700; text-transform: uppercase; font-size: 6.5px; }
        .meta .meta-value { color: #2b1b12; font-weight: 700; }

        .section-heading { margin: 12px 0 5px; page-break-inside: avoid; }
        .section-heading td { border: 0; vertical-align: middle; }
        .section-no { width: 26px; height: 20px; background: #c68b59; color: #fff; font-size: 9px; font-weight: 700; text-align: center; line-height: 20px; }
        .section-name { padding-left: 7px; font-size: 10.5px; font-weight: 700; color: #4a2e1b; text-transform: uppercase; letter-spacing: .5px; white-space: nowrap; }
        .section-line { border-bottom: 1px solid #eedfcc; padding: 0; }
        .page-break { page-break-before: always; }

        .subheading { font-size: 8px; font-weight: 700; color: #6f4e37; margin: 8px 0 3px; padding: 2px 0 2px 6px; border-left: 3px solid #c68b59; page-break-inside: avoid; }
        .intro { color: #7a6a5f; font-size: 7.5px; margin: 2px 0 5px; }

        .kpi td { width: 25%; padding: 5px; border: 1px solid #eedfcc; vertical-align: top; }
        .kpi .kpi-card { background: #fbf5ee; padding: 6px 7px; border-top: 3px solid #c68b59; }
        .kpi .kpi-label { font-size: 6.5px; font-weight: 700; color: #7a6a5f; text-transform: uppercase; letter-spacing: .5px; }
        .kpi .kpi-value { font-size: 14px; font-weight: 700; color: #4a2e1b; margin-top: 2px; }
        .kpi .kpi-note { font-size: 6.5px; color: #7a6a5f; margin-top: 1px; }

        .callout { margin-top: 7px; background: #fbf5ee; border-left: 3px solid #c68b59; padding: 6px 8px; font-size: 7.5px; color: #6f4e37; }
        .callout strong { color: #4a2e1b; }

        table.data { margin-bottom: 5px; table-layout: auto; }
        table.data thead { display: table-header-group; }
        table.data th { background: #4a2e1b; color: #fff; font-size: 6.8px; font-weight: 700; text-transform: uppercase; letter-spacing: .35px; text-align: left; padding: 4px 5px; border: 0; }
        table.data td { font-size: 7.3px; padding: 3.5px 5px; border-bottom: 1px solid #eedfcc; vertical-align: top; word-wrap: break-word; }
        table.data tr { page-break-inside: avoid; }
        table.data tbody tr.alt td { background: #fbf5ee; }
        table.data tfoot td { background: #eedfcc; color: #4a2e1b; font-weight: 700; border-top: 1px solid #c68b59; }
        .num { text-align: right; white-space: nowrap; }
        .empty { text-align: center; color: #7a6a5f; font-style: italic; }

        .badge { display: inline-block; padding: 1.5px 5px; border-radius: 7px; font-size: 6.5px; font-weight: 700; white-space: nowrap; }
        .badge-success { background: #e3f1eb; color: #1f7a5c; }
        .badge-warning { background: #fbf0dc; color: #b77a18; }
        .badge-danger { background: #f9e5e3; color: #b63b35; }
        .badge-neutral { background: #efe9e2; color: #6f4e37; }

        .summary-row td { border: 1px solid #eedfcc; padding: 5px 6px; font-size: 7.5px; }
        .summary-row .summary-label { background: #fbf5ee; color: #6f4e37; font-weight: 700; width: 13%; }
        .summary-row .summary-value { font-weight: 700; color: #4a2e1b; }

        .signatures { margin-top: 22px; page-break-inside: avoid; }
        .signatures td { border: 0; width: 50%; text-align: center; padding-top: 38px; font-size: 8px; }
        .signatures .sign-name { font-weight: 700; color: #4a2e1b; }
        .signatures .sign-space { color: #7a6a5f; }

        .page-footer { position: fixed; bottom: -9mm; left: 0; right: 0; border-top: 1px solid #eedfcc; padding-top: 3px; text-align: center; font-size: 6.5px; color: #7a6a5f; }
    </style>
</head>
<body>
<table class="cover">
    <tr>
        <td class="cover-brand">
            @if($logoSource)
                <img class="cover-logo" src="{{ $logoSource }}" alt="Logo">
            @else
                <div class="cover-initial">{{ strtoupper(\Illuminate\Support\Str::charAt($library['library_name'], 0)) }}</div>
            @endif
        </td>
        <td class="cover-copy">
            <div class="eyebrow">Sistem Informasi Manajemen Perpustakaan</div>
            <div class="cover-title">LAPORAN PERPUSTAKAAN</div>
            <div class="cover-library">{{ $library['library_name'] }}</div>
            <div class="cover-address">{{ $library['library_address'] }}</div>
        </td>
        <td class="cover-stamp">
            <div class="stamp-label">DITERBITKAN</div>
            <div class="stamp-date">{{ $generated_at }}</div>
            <div class="stamp-operator">Oleh {{ $operator }}</div>
        </td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td class="meta-label">Periode</td>
        <td class="meta-value">{{ $filters['label'] }}</td>
        <td class="meta-label">Katalog</td>
        <td class="meta-value">{{ $options['kategori']->firstWhere('id', (int) $filters['kategori'])?->name ?? 'Semua Katalog' }}</td>
        <td class="meta-label">Status</td>
        <td class="meta-value">{{ $filters['status'] !== '' ? ucwords(str_replace('_', ' ', $filters['status'])) : 'Semua Status' }}</td>
    </tr>
</table>

<table class="section-heading"><tr><td class="section-no">00</td><td class="section-name">Ringkasan Eksekutif</td><td class="section-line"></td></tr></table>
<table class="kpi">
    <tr>
        <td><div class="kpi-card"><div class="kpi-label">Judul Buku</div><div class="kpi-value">{{ number_format($stats['total_titles'], 0, ',', '.') }}</div><div class="kpi-note">Koleksi terdaftar</div></div></td>
        <td><div class="kpi-card" style="border-top-color:#356c93"><div class="kpi-label">Eksemplar</div><div class="kpi-value">{{ number_format($stats['total_copies'], 0, ',', '.') }}</div><div class="kpi-note">Total stok buku</div></div></td>
        <td><div class="kpi-card" style="border-top-color:#1f7a5c"><div class="kpi-label">Anggota</div><div class="kpi-value">{{ number_format($stats['total_members'], 0, ',', '.') }}</div><div class="kpi-note">Terdaftar di sistem</div></div></td>
        <td><div class="kpi-card" style="border-top-color:#b77a18"><div class="kpi-label">Peminjaman</div><div class="kpi-value">{{ number_format($stats['total_loans'], 0, ',', '.') }}</div><div class="kpi-note">Periode terfilter</div></div></td>
    </tr>
    <tr>
        <td><div class="kpi-card" style="border-top-color:#1f7a5c"><div class="kpi-label">Tersedia</div><div class="kpi-value">{{ number_format($stats['available_copies'], 0, ',', '.') }}</div><div class="kpi-note">Siap dipinjam</div></div></td>
        <td><div class="kpi-card" style="border-top-color:#b77a18"><div class="kpi-label">Sedang Dipinjam</div><div class="kpi-value">{{ number_format($stats['borrowed_copies'], 0, ',', '.') }}</div><div class="kpi-note">Belum kembali</div></div></td>
        <td><div class="kpi-card" style="border-top-color:#b63b35"><div class="kpi-label">Kembali Terlambat</div><div class="kpi-value">{{ number_format($stats['late_returns'], 0, ',', '.') }}</div><div class="kpi-note">Perlu tindak lanjut</div></div></td>
        <td><div class="kpi-card" style="border-top-color:#b63b35"><div class="kpi-label">Denda Belum Dibayar</div><div class="kpi-value" style="font-size:12px">{{ rupiah($stats['unpaid_fine']) }}</div><div class="kpi-note">Outstanding</div></div></td>
    </tr>
</table>
<div class="callout">
    <strong>Sorotan:</strong> {{ $pengembalian['total'] }} pengembalian tercatat dengan {{ $pengembalian['late'] }} di antaranya terlambat. Total pembayaran denda pada periode ini sebesar <strong>{{ rupiah($denda['payment_total']) }}</strong>.
</div>

<table class="section-heading"><tr><td class="section-no">01</td><td class="section-name">Laporan Buku</td><td class="section-line"></td></tr></table>
<p class="intro">{{ $buku['titles'] }} judul · {{ $buku['copies'] }} eksemplar · {{ $buku['available'] }} tersedia · {{ $buku['borrowed'] }} dipinjam · {{ $buku['out_of_stock'] }} stok habis · {{ $buku['inactive'] }} tidak aktif.</p>

<div class="subheading">Buku per Katalog</div>
<table class="data">
    <thead><tr><th>Katalog</th><th class="num">Judul</th><th class="num">Eksemplar</th><th class="num">Tersedia</th><th class="num">Stok Habis</th><th class="num">Bagian</th></tr></thead>
    <tbody>
        @php $categoryTotal = max(1, (int) $buku['per_category']->sum('books_count')); @endphp
        @forelse($buku['per_category'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td>{{ $item->name }}</td>
            <td class="num">{{ number_format((int) $item->books_count, 0, ',', '.') }}</td>
            <td class="num">{{ number_format((int) $item->total_stock, 0, ',', '.') }}</td>
            <td class="num">{{ number_format((int) $item->total_available, 0, ',', '.') }}</td>
            <td class="num">{{ number_format(max(0, (int) $item->total_stock - (int) $item->total_available), 0, ',', '.') }}</td>
            <td class="num">{{ number_format((int) $item->books_count / $categoryTotal * 100, 1, ',', '.') }}%</td>
        </tr>
        @empty<tr><td colspan="6" class="empty">Belum ada data katalog.</td></tr>@endforelse
    </tbody>
</table>

<div class="subheading">Buku per Penerbit</div>
<table class="data">
    <thead><tr><th>Penerbit</th><th class="num">Judul</th><th class="num">Eksemplar</th><th class="num">Tersedia</th><th>Keterangan</th></tr></thead>
    <tbody>
        @forelse($buku['per_publisher'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td>{{ $item->name }}</td>
            <td class="num">{{ number_format((int) $item->total_judul, 0, ',', '.') }}</td>
            <td class="num">{{ number_format((int) $item->total_eksemplar, 0, ',', '.') }}</td>
            <td class="num">{{ number_format((int) $item->total_tersedia, 0, ',', '.') }}</td>
            <td>{!! $badge((int) $item->total_tersedia === 0 ? 'Stok habis' : ((int) $item->total_eksemplar > (int) $item->total_tersedia ? 'Sebagian dipinjam' : 'Semua tersedia')) !!}</td>
        </tr>
        @empty<tr><td colspan="5" class="empty">Belum ada data penerbit.</td></tr>@endforelse
    </tbody>
</table>

<div class="subheading">Buku Paling Sering Dipinjam (Sepanjang Waktu)</div>
<table class="data">
    <thead><tr><th class="num">#</th><th>Judul Buku</th><th>Katalog</th><th class="num">Jumlah Peminjaman</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($buku['popular'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $item->title }}</td>
            <td>{{ $item->category?->name ?? 'Tanpa katalog' }}</td>
            <td class="num">{{ number_format((int) $item->loan_details_count, 0, ',', '.') }}</td>
            <td>{!! $autoBadge((int) $item->loan_details_count > 0 ? 'Diminati' : 'Belum dipinjam') !!}</td>
        </tr>
        @empty<tr><td colspan="5" class="empty">Belum ada data peminjaman.</td></tr>@endforelse
    </tbody>
</table>

<table class="section-heading"><tr><td class="section-no">02</td><td class="section-name">Laporan Anggota</td><td class="section-line"></td></tr></table>
<table class="summary-row">
    <tr>
        <td class="summary-label">Total Anggota</td><td class="summary-value">{{ number_format($anggota['total'], 0, ',', '.') }}</td>
        <td class="summary-label">Pernah Meminjam</td><td class="summary-value">{{ number_format($anggota['ever_borrowed'], 0, ',', '.') }}</td>
        <td class="summary-label">Jumlah Sekolah</td><td class="summary-value">{{ $anggota['per_school']->count() }}</td>
        <td class="summary-label">Jumlah Asal</td><td class="summary-value">{{ $anggota['per_origin']->count() }}</td>
    </tr>
</table>

<div class="subheading">Anggota per Sekolah</div>
<table class="data">
    <thead><tr><th class="num">#</th><th>Sekolah</th><th class="num">Jumlah Anggota</th><th class="num">Bagian</th><th>Keterangan</th></tr></thead>
    <tbody>
        @php $memberTotal = max(1, (int) $anggota['total']); @endphp
        @forelse($anggota['per_school'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $item->school ?: 'Tanpa data' }}</td>
            <td class="num">{{ number_format((int) $item->total, 0, ',', '.') }}</td>
            <td class="num">{{ number_format((int) $item->total / $memberTotal * 100, 1, ',', '.') }}%</td>
            <td>{!! $autoBadge('Terdata') !!}</td>
        </tr>
        @empty<tr><td colspan="5" class="empty">Belum ada anggota.</td></tr>@endforelse
    </tbody>
</table>

<div class="subheading">Anggota per Asal</div>
<table class="data">
    <thead><tr><th class="num">#</th><th>Asal</th><th class="num">Jumlah Anggota</th><th class="num">Bagian</th><th>Keterangan</th></tr></thead>
    <tbody>
        @forelse($anggota['per_origin'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $item->origin ?: 'Tanpa data' }}</td>
            <td class="num">{{ number_format((int) $item->total, 0, ',', '.') }}</td>
            <td class="num">{{ number_format((int) $item->total / $memberTotal * 100, 1, ',', '.') }}%</td>
            <td>{!! $autoBadge('Terdata') !!}</td>
        </tr>
        @empty<tr><td colspan="5" class="empty">Belum ada anggota.</td></tr>@endforelse
    </tbody>
</table>

<div class="subheading">Anggota Paling Sering Meminjam</div>
<table class="data">
    <thead><tr><th class="num">#</th><th>Kode Anggota</th><th>Nama Anggota</th><th class="num">Jumlah Peminjaman</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($anggota['borrowers'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $item->member_code }}</td>
            <td>{{ $item->full_name }}</td>
            <td class="num">{{ number_format((int) $item->loans_count, 0, ',', '.') }}</td>
            <td>{!! $autoBadge('Aktif meminjam') !!}</td>
        </tr>
        @empty<tr><td colspan="5" class="empty">Belum ada anggota yang meminjam.</td></tr>@endforelse
    </tbody>
</table>

<table class="section-heading page-break"><tr><td class="section-no">03</td><td class="section-name">Laporan Peminjaman</td><td class="section-line"></td></tr></table>
<table class="summary-row">
    <tr>
        <td class="summary-label">Total Transaksi</td><td class="summary-value">{{ number_format($peminjaman['total'], 0, ',', '.') }}</td>
        <td class="summary-label">Hari Aktif</td><td class="summary-value">{{ $peminjaman['per_hari']->count() }}</td>
        <td class="summary-label">Bulan Aktif</td><td class="summary-value">{{ $peminjaman['per_bulan']->count() }}</td>
        <td class="summary-label">Tahun Aktif</td><td class="summary-value">{{ $peminjaman['per_tahun']->count() }}</td>
    </tr>
</table>

<div class="subheading">Rekap per Bulan</div>
<table class="data">
    <thead><tr><th>Periode</th><th class="num">Jumlah Transaksi</th><th>Keterangan</th></tr></thead>
    <tbody>
        @forelse($peminjaman['per_bulan'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}"><td>{{ \Illuminate\Support\Carbon::parse($item->periode.'-01')->translatedFormat('F Y') }}</td><td class="num">{{ number_format((int) $item->total, 0, ',', '.') }}</td><td>{!! $autoBadge((int) $item->total > 0 ? 'Aktif' : 'Tidak ada') !!}</td></tr>
        @empty<tr><td colspan="3" class="empty">Belum ada transaksi.</td></tr>@endforelse
    </tbody>
</table>

<div class="subheading">Rekap per Tahun</div>
<table class="data">
    <thead><tr><th>Tahun</th><th class="num">Jumlah Transaksi</th><th>Keterangan</th></tr></thead>
    <tbody>
        @forelse($peminjaman['per_tahun'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}"><td>{{ $item->periode }}</td><td class="num">{{ number_format((int) $item->total, 0, ',', '.') }}</td><td>{!! $autoBadge((int) $item->total > 0 ? 'Aktif' : 'Tidak ada') !!}</td></tr>
        @empty<tr><td colspan="3" class="empty">Belum ada transaksi.</td></tr>@endforelse
    </tbody>
</table>

<div class="subheading">Daftar Transaksi Peminjaman</div>
<table class="data">
    <thead><tr><th>No. Transaksi</th><th>Anggota</th><th>Petugas</th><th>Tanggal Pinjam</th><th>Jatuh Tempo</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($peminjaman['transactions'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td>{{ $item->loan_number }}</td>
            <td>{{ $item->member?->full_name ?? '-' }}</td>
            <td>{{ $item->user?->name ?? '-' }}</td>
            <td>{{ tanggal($item->loan_date) }}</td>
            <td>{{ tanggal($item->due_date) }}</td>
            <td>{!! $autoBadge($item->status_label) !!}</td>
        </tr>
        @empty<tr><td colspan="6" class="empty">Tidak ada transaksi pada periode ini.</td></tr>@endforelse
    </tbody>
</table>

<div class="subheading">Buku Paling Sering Dipinjam (Periode Ini)</div>
<table class="data">
    <thead><tr><th class="num">#</th><th>Judul Buku</th><th>Katalog</th><th class="num">Jumlah</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($peminjaman['top_books'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td class="num">{{ $loop->iteration }}</td>
            <td>{{ $item->title }}</td>
            <td>{{ $item->book?->category?->name ?? 'Tanpa katalog' }}</td>
            <td class="num">{{ number_format((int) $item->total, 0, ',', '.') }}</td>
            <td>{!! $autoBadge((int) $item->total > 0 ? 'Diminati' : 'Belum dipinjam') !!}</td>
        </tr>
        @empty<tr><td colspan="5" class="empty">Belum ada data.</td></tr>@endforelse
    </tbody>
</table>

<table class="section-heading"><tr><td class="section-no">04</td><td class="section-name">Laporan Pengembalian</td><td class="section-line"></td></tr></table>
<table class="summary-row">
    <tr>
        <td class="summary-label">Total Pengembalian</td><td class="summary-value">{{ number_format($pengembalian['total'], 0, ',', '.') }}</td>
        <td class="summary-label">Tepat Waktu</td><td class="summary-value">{{ number_format($pengembalian['on_time'], 0, ',', '.') }}</td>
        <td class="summary-label">Terlambat</td><td class="summary-value">{{ number_format($pengembalian['late'], 0, ',', '.') }}</td>
        <td class="summary-label">Tanpa Data Return</td><td class="summary-value">{{ max(0, $pengembalian['total'] - $pengembalian['on_time'] - $pengembalian['late']) }}</td>
    </tr>
</table>
<table class="data">
    <thead><tr><th>No. Pengembalian</th><th>Anggota</th><th>Tanggal Kembali</th><th>Jatuh Tempo</th><th>Keterangan</th><th>Petugas</th><th class="num">Denda</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($pengembalian['history'] as $item)
        @php
            $keterangan = $item->timing === 'terlambat' ? 'Terlambat '.(int) $item->late_days.' hari' : ($item->timing === 'tepat_waktu' ? 'Tepat waktu' : 'Tanpa data');
        @endphp
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td>{{ $item->return_number }}</td>
            <td>{{ $item->member?->full_name ?? '-' }}</td>
            <td>{{ tanggal($item->return_date, true) }}</td>
            <td>{{ tanggal($item->loan?->due_date) }}</td>
            <td>{{ $keterangan }}</td>
            <td>{{ $item->user?->name ?? '-' }}</td>
            <td class="num">{{ rupiah($item->total_fine) }}</td>
            <td>{!! $autoBadge($keterangan) !!}</td>
        </tr>
        @empty<tr><td colspan="8" class="empty">Tidak ada pengembalian pada periode ini.</td></tr>@endforelse
    </tbody>
</table>

<table class="section-heading page-break"><tr><td class="section-no">05</td><td class="section-name">Laporan Denda</td><td class="section-line"></td></tr></table>
<table class="summary-row">
    <tr>
        <td class="summary-label">Total Denda</td><td class="summary-value">{{ rupiah($denda['total_amount']) }}</td>
        <td class="summary-label">Sudah Dibayar</td><td class="summary-value">{{ rupiah($denda['paid_amount']) }}</td>
        <td class="summary-label">Belum Dibayar</td><td class="summary-value">{{ rupiah($denda['unpaid_amount']) }}</td>
        <td class="summary-label">Total Pembayaran</td><td class="summary-value">{{ rupiah($denda['payment_total']) }}</td>
    </tr>
</table>

<div class="subheading">Daftar Denda</div>
<table class="data">
    <thead><tr><th>No. Denda</th><th>Anggota</th><th>No. Pinjaman</th><th class="num">Jumlah</th><th class="num">Dibayar</th><th class="num">Sisa</th><th>Status</th><th>Keterangan</th></tr></thead>
    <tbody>
        @forelse($denda['fines'] as $item)
        @php $status = ucwords(str_replace('_', ' ', $item->status)); @endphp
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td>{{ $item->fine_number }}</td>
            <td>{{ $item->member?->full_name ?? '-' }}</td>
            <td>{{ $item->loan?->loan_number ?? '-' }}</td>
            <td class="num">{{ rupiah($item->amount) }}</td>
            <td class="num">{{ rupiah($item->paid_amount) }}</td>
            <td class="num">{{ rupiah($item->remaining) }}</td>
            <td>{!! $autoBadge($status) !!}</td>
            <td>{!! $autoBadge($item->status === 'lunas' ? 'Selesai' : 'Masih berjalan') !!}</td>
        </tr>
        @empty<tr><td colspan="8" class="empty">Tidak ada data denda.</td></tr>@endforelse
    </tbody>
</table>

<div class="subheading">Riwayat Pembayaran</div>
<table class="data">
    <thead><tr><th>No. Kwitansi</th><th>Anggota</th><th>Petugas</th><th>Tanggal</th><th class="num">Jumlah</th><th>Metode</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($denda['payments'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td>{{ $item->receipt_number }}</td>
            <td>{{ $item->fine?->member?->full_name ?? '-' }}</td>
            <td>{{ $item->user?->name ?? '-' }}</td>
            <td>{{ tanggal($item->payment_date, true) }}</td>
            <td class="num">{{ rupiah($item->amount) }}</td>
            <td>{{ ucfirst((string) $item->method) }}</td>
            <td>{!! $autoBadge('Lunas') !!}</td>
        </tr>
        @empty<tr><td colspan="7" class="empty">Belum ada pembayaran.</td></tr>@endforelse
    </tbody>
</table>

<table class="section-heading"><tr><td class="section-no">06</td><td class="section-name">Kinerja Petugas</td><td class="section-line"></td></tr></table>
<table class="data">
    <thead><tr><th>Nama Petugas</th><th class="num">Peminjaman</th><th class="num">Pengembalian</th><th class="num">Anggota Didaftarkan</th><th class="num">Pembayaran</th><th class="num">Total Dilayani</th><th>Status</th></tr></thead>
    <tbody>
        @forelse($kinerja['petugas'] as $item)
        <tr class="{{ $loop->even ? 'alt' : '' }}">
            <td>{{ $item['name'] }}</td>
            <td class="num">{{ number_format($item['loans'], 0, ',', '.') }}</td>
            <td class="num">{{ number_format($item['returns'], 0, ',', '.') }}</td>
            <td class="num">{{ number_format($item['registered'], 0, ',', '.') }}</td>
            <td class="num">{{ number_format($item['payments'], 0, ',', '.') }}</td>
            <td class="num">{{ number_format($item['total'], 0, ',', '.') }}</td>
            <td>{!! $autoBadge($item['total'] > 0 ? 'Aktif' : 'Belum ada aktivitas') !!}</td>
        </tr>
        @empty<tr><td colspan="7" class="empty">Belum ada petugas.</td></tr>@endforelse
        @if($kinerja['petugas']->isNotEmpty())
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="num">{{ number_format($kinerja['total_loans'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format($kinerja['total_returns'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format($kinerja['total_registered'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format($kinerja['total_payments'], 0, ',', '.') }}</td>
                <td class="num">{{ number_format($kinerja['total_served'], 0, ',', '.') }}</td>
                <td>Seluruh petugas</td>
            </tr>
        </tfoot>
        @endif
    </tbody>
</table>

<table class="signatures">
    <tr>
        <td>
            <div>Mengetahui,</div>
            <div class="sign-name">Kepala {{ $library['library_name'] }}</div>
            <div class="sign-space">( ........................................ )</div>
        </td>
        <td>
            <div>{{ $library['library_name'] }}, {{ tanggal(now()) }}</div>
            <div class="sign-name">Petugas Pelapor</div>
            <div class="sign-space">( {{ $operator }} )</div>
        </td>
    </tr>
</table>

<div class="page-footer">Dokumen ini dihasilkan otomatis oleh Sistem Informasi Manajemen Perpustakaan Libraria · Data bersumber dari database lokal perpustakaan.</div>
</body>
</html>

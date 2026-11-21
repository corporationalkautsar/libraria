<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pembayaran {{ $payment->receipt_number }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/css/bootstrap.min.css') }}">
    <style>
        body { font-family: "Segoe UI", Arial, sans-serif; color: #1e293b; padding: 30px; background:#fff; }
        .receipt { max-width: 620px; margin: 0 auto; border: 2px solid #96603a; border-radius: 12px; padding: 28px; }
        .brand { color:#78482a; font-weight:800; font-size:19px; }
        .muted { color:#64748b; font-size:12px; }
        table { width:100%; border-collapse: collapse; margin-top:18px; }
        td { padding:9px 6px; border-bottom:1px solid #e8eef1; font-size:13px; }
        .total { font-size:17px; font-weight:800; color:#78482a; }
        .sign { margin-top:45px; text-align:center; font-size:12px; }
        @media print { .no-print { display:none } body { padding:0 } .receipt { border:none } }
    </style>
</head>
<body>
<div class="no-print mb-3 text-center">
    <button class="btn btn-teal" onclick="window.print()">{!! icon('printer',16) !!} Cetak Bukti</button>
    <a href="{{ route('fines.show', $fine) }}" class="btn btn-soft">Kembali</a>
</div>
<div class="receipt">
    <div class="text-center">
        <div class="brand">{{ $library['library_name'] }}</div>
        <div class="muted">{{ $library['library_address'] }}</div>
        <hr style="border-color:#96603a">
        <h5 class="mt-3">BUKTI PEMBAYARAN DENDA</h5>
        <div class="muted">No. Bukti: {{ $payment->receipt_number }}</div>
    </div>
    <table>
        <tr><td>Dibayar oleh</td><td class="text-end fw-bold">{{ $fine->member->full_name }}</td></tr>
        <tr><td>ID Anggota</td><td class="text-end">{{ $fine->member->member_code }}</td></tr>
        <tr><td>Sekolah</td><td class="text-end">{{ $fine->member->school }}</td></tr>
        <tr><td>No. Denda</td><td class="text-end">{{ $fine->fine_number }}</td></tr>
        <tr><td>No. Pinjaman</td><td class="text-end">{{ $fine->loan->loan_number }}</td></tr>
        <tr><td>Metode</td><td class="text-end">{{ ucfirst($payment->method) }}</td></tr>
        <tr><td>Tanggal</td><td class="text-end">{{ tanggal($payment->payment_date, true) }}</td></tr>
        <tr><td>Petugas Penerima</td><td class="text-end">{{ $payment->user->name }}</td></tr>
        @if($payment->note)<tr><td>Catatan</td><td class="text-end">{{ $payment->note }}</td></tr>@endif
    </table>
    <div class="d-flex justify-content-between align-items-center mt-4 p-3" style="background:#eefaf7;border-radius:9px">
        <span class="fw-bold">JUMLAH DIBAYAR</span>
        <span class="total">{{ rupiah($payment->amount) }}</span>
    </div>
    <div class="mt-3 muted">Sisa denda setelah pembayaran ini: <strong>{{ rupiah($fine->fresh()->remaining) }}</strong></div>
    <div class="sign">
        <div>{{ $library['library_name'] }}, {{ tanggal($payment->payment_date) }}</div>
        <div style="margin-top:55px">________________________</div>
        <div>Petugas Perpustakaan</div>
    </div>
</div>
<script>window.addEventListener('load', () => {});</script>
</body>
</html>

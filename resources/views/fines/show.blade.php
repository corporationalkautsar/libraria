@extends('layouts.app')
@section('title', 'Detail Denda')
@section('subtitle', $fine->fine_number)

@section('content')
<div class="page-head">
    <div><h1>Detail Denda</h1><p>{{ $fine->fine_number }} · {{ $fine->member->full_name }}</p></div>
    <div class="d-flex gap-2">
        <a href="{{ route('fines.index') }}" class="btn btn-soft">{!! icon('arrow-left',16) !!} Kembali</a>
        @if(auth()->user()->isPetugas() && $fine->remaining > 0)
            <a href="{{ route('fines.pay', $fine) }}" class="btn btn-teal">{!! icon('banknote',16) !!} Catat Pembayaran</a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><x-stat-card label="Total Denda" :value="rupiah($fine->amount)" icon="banknote" tone="blue" /></div>
    <div class="col-md-3"><x-stat-card label="Sudah Dibayar" :value="rupiah($fine->paid_amount)" icon="circle-check" tone="teal" /></div>
    <div class="col-md-3"><x-stat-card label="Sisa" :value="rupiah($fine->remaining)" icon="wallet" tone="red" /></div>
    <div class="col-md-3"><x-stat-card label="Status" :value="ucfirst(str_replace('_',' ',$fine->status))" icon="activity" tone="amber" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Informasi Denda</h3></div>
            <div class="card-body-soft">
                @foreach([
                    'No. Denda' => $fine->fine_number,
                    'Anggota' => $fine->member->full_name,
                    'ID Anggota' => $fine->member->member_code,
                    'Sekolah' => $fine->member->school,
                    'Asal' => $fine->member->origin,
                    'No. Pinjaman' => $fine->loan->loan_number,
                    'Jatuh Tempo' => tanggal($fine->loan->due_date),
                    'Dibuat' => tanggal($fine->created_at, true),
                ] as $label => $value)
                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">{{ $label }}</span><strong class="text-end">{{ $value }}</strong></div>
                @endforeach
                <div class="mt-3">{!! status_badge($fine->status) !!}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Riwayat Pembayaran</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>No. Bukti</th><th>Jumlah</th><th>Metode</th><th>Tanggal</th><th>Petugas</th><th></th></tr></thead>
                    <tbody>
                        @forelse($fine->payments as $payment)
                        <tr>
                            <td class="cell-primary">{{ $payment->receipt_number }}</td>
                            <td class="text-success">{{ rupiah($payment->amount) }}</td>
                            <td>{{ ucfirst($payment->method) }}</td>
                            <td>{{ tanggal($payment->payment_date, true) }}</td>
                            <td>{{ $payment->user->name }}</td>
                            <td><a href="{{ route('fines.receipt', [$fine, $payment]) }}" class="btn btn-outline-teal btn-sm">{!! icon('printer',14) !!}</a></td>
                        </tr>
                        @empty<tr><td colspan="6" class="table-empty">Belum ada pembayaran.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

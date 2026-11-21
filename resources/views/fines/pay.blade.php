@extends('layouts.app')
@section('title', 'Catat Pembayaran Denda')
@section('subtitle', $fine->fine_number)

@section('content')
<x-page-head title="Pembayaran Denda" subtitle="Catat jumlah denda yang dibayar anggota." />
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card-soft mb-3">
            <div class="card-head"><h3 class="card-title">Ringkasan Denda</h3></div>
            <div class="card-body-soft">
                <div class="row">
                    <div class="col-4"><div class="cell-secondary">Anggota</div><div class="cell-primary">{{ $fine->member->full_name }}</div></div>
                    <div class="col-4"><div class="cell-secondary">Total Denda</div><div class="cell-primary">{{ rupiah($fine->amount) }}</div></div>
                    <div class="col-4"><div class="cell-secondary">Sisa Dibayar</div><div class="cell-primary text-danger">{{ rupiah($fine->remaining) }}</div></div>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('fines.payment', $fine) }}" class="card-soft">
            @csrf
            <div class="card-head"><h3 class="card-title">Formulir Pembayaran</h3></div>
            <div class="card-body-soft">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Jumlah Pembayaran <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" value="{{ old('amount', $fine->remaining) }}" min="1" max="{{ $fine->remaining }}" step="1" required>
                        <div class="form-text">Maksimal {{ rupiah($fine->remaining) }}. Boleh dibayar sebagian.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Metode Pembayaran</label>
                        <select name="method" class="form-select" required>
                            <option value="tunai">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Pembayaran</label>
                        <input type="date" name="payment_date" class="form-control" value="{{ old('payment_date', today()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Catatan (opsional)</label>
                        <input name="note" class="form-control" value="{{ old('note') }}" maxlength="255" placeholder="Contoh: Bayar tunai di loket">
                    </div>
                </div>
            </div>
            <div class="card-body-soft border-top d-flex gap-2">
                <button class="btn btn-teal">{!! icon('save',16) !!} Simpan Pembayaran</button>
                <a href="{{ route('fines.show', $fine) }}" class="btn btn-soft">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

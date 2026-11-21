@extends('layouts.app')
@section('title', 'Detail Buku')
@section('subtitle', 'Informasi lengkap buku dan riwayat peminjamannya')

@section('content')
@php
    $action = '<a href="'.route('books.index').'" class="btn btn-soft">'.icon('arrow-left', 16).' Kembali</a>'
        .'<a href="'.route('books.edit', $book).'" class="btn btn-teal">'.icon('pencil', 16).' Edit Buku</a>';
@endphp
<x-page-head title="Detail Buku" subtitle="Data bibliografi, ketersediaan, dan riwayat peminjaman." :action="$action" />

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card-soft card-body-soft h-100 text-center">
            @if($book->cover)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($book->cover) }}" alt="Sampul {{ $book->title }}" style="max-width:190px;max-height:250px;object-fit:cover;border-radius:12px;border:1px solid #e8eef1">
            @else
                <div class="table-avatar mx-auto mb-3" style="width:120px;height:160px;border-radius:12px">{!! icon('book-open', 42) !!}</div>
            @endif
            <h4 class="mt-3 mb-1">{{ $book->title }}</h4>
            <div class="cell-secondary mb-2">{{ $book->author?->name ?? '-' }}</div>
            {!! status_badge($book->status) !!}
            <hr>
            <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Kode Buku</span><strong>{{ $book->book_code }}</strong></div>
            <div class="d-flex justify-content-between small"><span class="text-muted">ISBN</span><strong>{{ $book->isbn ?: '—' }}</strong></div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Informasi Bibliografi</h3></div>
            <div class="card-body-soft">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="cell-secondary">Penulis</div>
                        <div class="cell-primary">{{ $book->author?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="cell-secondary">Penerbit</div>
                        <div class="cell-primary">{{ $book->publisher?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="cell-secondary">Katalog</div>
                        <div class="cell-primary">{{ $book->category?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="cell-secondary">Tahun Terbit</div>
                        <div class="cell-primary">{{ $book->year }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="cell-secondary">Ditambahkan</div>
                        <div class="cell-primary">{{ tanggal($book->created_at, true) }}</div>
                    </div>
                    <div class="col-12">
                        <div class="cell-secondary">Deskripsi / Sinopsis</div>
                        <div class="mt-1">{{ $book->description ?: 'Tidak ada deskripsi.' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><x-stat-card label="Total Stok" :value="$book->stock" icon="package" tone="teal" foot="Seluruh eksemplar" /></div>
    <div class="col-6 col-md-3"><x-stat-card label="Tersedia" :value="$book->available" icon="check" tone="blue" foot="Siap dipinjam" /></div>
    <div class="col-6 col-md-3"><x-stat-card label="Sedang Dipinjam" :value="max(0, $book->stock - $book->available)" icon="book-marked" tone="amber" foot="Belum kembali" /></div>
    <div class="col-6 col-md-3"><x-stat-card label="Total Peminjaman" :value="$loanHistory->total()" icon="history" tone="purple" foot="Sepanjang riwayat" /></div>
</div>

<div class="card-soft">
    <div class="card-head">
        <h3 class="card-title">Riwayat Peminjaman</h3>
        <span class="cell-secondary">{{ $loanHistory->total() }} transaksi</span>
    </div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Nama Anggota</th>
                    <th>Tanggal Pinjam</th>
                    <th>Jatuh Tempo</th>
                    <th>Tanggal Kembali</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loanHistory as $detail)
                <tr>
                    <td class="cell-primary nowrap">{{ $detail->loan?->loan_number ?? '-' }}</td>
                    <td>{{ $detail->loan?->member?->full_name ?? '-' }}</td>
                    <td class="nowrap">{{ tanggal($detail->loan?->loan_date) }}</td>
                    <td class="nowrap">{{ tanggal($detail->loan?->due_date) }}</td>
                    <td class="nowrap">{{ tanggal($detail->returned_at, true) }}</td>
                    <td>{!! status_badge($detail->status) !!}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="table-empty">Belum ada riwayat peminjaman untuk buku ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($loanHistory->hasPages())
        <div class="card-body-soft d-flex justify-content-end">{{ $loanHistory->links() }}</div>
    @endif
</div>
@endsection

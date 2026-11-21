@extends('layouts.app')
@section('title', 'Detail Buku')
@section('subtitle', 'Informasi buku dalam katalog perpustakaan')

@section('content')
@php
    $backButton = '<a href="'.route('catalog.index').'" class="btn btn-outline-teal">'.icon('arrow-left', 16).' Kembali ke Katalog</a>';
@endphp
<x-page-head title="Detail Buku" subtitle="Informasi lengkap buku dan ketersediaannya." :action="$backButton" />

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-soft card-body-soft h-100 text-center">
            @if($book->cover)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($book->cover) }}" alt="Sampul {{ $book->title }}" style="max-width:190px;max-height:250px;object-fit:cover;border-radius:12px;border:1px solid #e8eef1">
            @else
                <div class="table-avatar mx-auto mb-3" style="width:120px;height:160px;border-radius:12px">{!! icon('book-open', 42) !!}</div>
            @endif
            <h4 class="mt-3 mb-1">{{ $book->title }}</h4>
            <div class="cell-primary">{{ $book->author?->name ?? '-' }}</div>
            <div class="cell-secondary mb-3">{{ $book->publisher?->name ?? '-' }}</div>
            {!! status_badge($book->status) !!}
            @if($book->available > 0)
                <div class="mt-2"><span class="badge badge-success">Tersedia ({{ $book->available }} eksemplar)</span></div>
            @else
                <div class="mt-2"><span class="badge badge-danger">Stok Habis</span></div>
            @endif
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-soft mb-3">
            <div class="card-head">
                <h3 class="card-title">Informasi Buku</h3>
                <span class="cell-secondary">Kode: {{ $book->book_code }}</span>
            </div>
            <div class="card-body-soft">
                <div class="row g-3">
                    <div class="col-md-6"><div class="cell-secondary">Penulis</div><div class="cell-primary">{{ $book->author?->name ?? '-' }}</div></div>
                    <div class="col-md-6"><div class="cell-secondary">Penerbit</div><div class="cell-primary">{{ $book->publisher?->name ?? '-' }}</div></div>
                    <div class="col-md-6"><div class="cell-secondary">Katalog</div><div class="cell-primary">{{ $book->category?->name ?? '-' }}</div></div>
                    <div class="col-md-6"><div class="cell-secondary">Tahun Terbit</div><div class="cell-primary">{{ $book->year }}</div></div>
                    <div class="col-md-6"><div class="cell-secondary">ISBN</div><div class="cell-primary">{{ $book->isbn ?: '—' }}</div></div>
                    <div class="col-12"><div class="cell-secondary">Deskripsi / Sinopsis</div><div class="mt-1">{{ $book->description ?: 'Tidak ada deskripsi.' }}</div></div>
                </div>
            </div>
        </div>
        <div class="card-soft">
            <div class="card-head"><h3 class="card-title">Buku Terkait</h3></div>
            <div class="card-body-soft">
                <ul class="mini-list">
                    @forelse($related as $item)
                    <li>
                        <span class="mini-rank">{!! icon('book-open', 14) !!}</span>
                        <div class="flex-grow-1">
                            <div class="cell-primary">{{ $item->title }}</div>
                            <div class="cell-secondary">{{ $item->author?->name ?? '-' }}</div>
                        </div>
                        <a href="{{ route('catalog.show', $item) }}" class="btn btn-soft btn-sm">Detail</a>
                    </li>
                    @empty<li class="table-empty">Belum ada buku lain dalam katalog ini.</li>@endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

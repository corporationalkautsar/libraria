@extends('layouts.app')
@section('title', 'Katalog Buku')
@section('subtitle', 'Jelajahi koleksi buku perpustakaan')

@section('content')
<x-page-head title="Katalog Buku" subtitle="Cari dan lihat ketersediaan buku dalam koleksi perpustakaan." />

<form method="GET" action="{{ route('catalog.index') }}" class="filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-6 col-lg-6">
            <label for="q">Cari Buku</label>
            <input id="q" type="text" name="q" class="form-control" placeholder="Judul, ISBN, penulis, penerbit, atau kategori" value="{{ request('q') }}">
        </div>
        <div class="col-6 col-md-3 col-lg-3">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id" class="form-select">
                <option value="">Semua kategori</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-1">
            <label for="availability">Ketersediaan</label>
            <select id="availability" name="availability" class="form-select">
                <option value="">Semua</option>
                <option value="tersedia" @selected(request('availability') === 'tersedia')>Ada</option>
                <option value="habis" @selected(request('availability') === 'habis')>Habis</option>
            </select>
        </div>
        <div class="col-12 col-lg-2 filter-actions">
            <button type="submit" class="btn btn-teal btn-sm w-100">{!! icon('search', 15) !!} Cari</button>
            <a href="{{ route('catalog.index') }}" class="btn btn-soft btn-sm w-100">{!! icon('rotate-ccw', 15) !!} Reset</a>
        </div>
    </div>
</form>

<div class="card-soft">
    <div class="card-head">
        <h3 class="card-title">Daftar Koleksi</h3>
        <span class="cell-secondary">Menampilkan {{ $books->firstItem() ?? 0 }}–{{ $books->lastItem() ?? 0 }} dari {{ $books->total() }} buku</span>
    </div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead>
                <tr>
                    <th>Sampul</th>
                    <th>{!! sort_link('Judul Buku', 'title') !!}</th>
                    <th>Penulis</th>
                    <th>Penerbit</th>
                    <th>Kategori</th>
                    <th>{!! sort_link('Tahun', 'year') !!}</th>
                    <th>{!! sort_link('Stok', 'stock') !!}</th>
                    <th>Ketersediaan</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $book)
                <tr>
                    <td>
                        @if($book->cover)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($book->cover) }}" alt="Sampul {{ $book->title }}" style="width:42px;height:54px;object-fit:cover;border-radius:7px;border:1px solid #e8eef1">
                        @else
                            <span class="table-avatar" style="width:42px;height:54px;border-radius:7px">{!! icon('book-open', 16) !!}</span>
                        @endif
                    </td>
                    <td>
                        <div class="cell-primary">{{ $book->title }}</div>
                        <div class="cell-secondary">{{ $book->isbn ?: $book->book_code }}</div>
                    </td>
                    <td>{{ $book->author?->name ?? '-' }}</td>
                    <td>{{ $book->publisher?->name ?? '-' }}</td>
                    <td>{{ $book->category?->name ?? '-' }}</td>
                    <td>{{ $book->year }}</td>
                    <td class="text-center">{{ $book->stock }}</td>
                    <td>
                        @if($book->available > 0)
                            <span class="badge badge-success">Tersedia ({{ $book->available }})</span>
                        @else
                            <span class="badge badge-danger">Stok Habis</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <a href="{{ route('catalog.show', $book) }}" class="btn btn-outline-teal btn-sm">{!! icon('eye', 14) !!} Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="table-empty">Tidak ada buku yang cocok dengan pencarian Anda.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($books->hasPages())
        <div class="card-body-soft d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="cell-secondary">Halaman {{ $books->currentPage() }} dari {{ $books->lastPage() }}</span>
            {{ $books->links() }}
        </div>
    @endif
</div>
@endsection

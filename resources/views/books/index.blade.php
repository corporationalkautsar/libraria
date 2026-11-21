@extends('layouts.app')
@section('title', 'Data Buku')
@section('subtitle', 'Kelola koleksi dan ketersediaan buku perpustakaan')

@section('content')
@php
    $createButton = '<a href="'.route('books.create').'" class="btn btn-teal">'.icon('plus', 16).' Tambah Buku</a>';
@endphp
<x-page-head title="Data Buku" subtitle="Kelola koleksi, stok, dan status buku perpustakaan." :action="$createButton" />

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><x-stat-card label="Total Judul" :value="$stats['titles']" icon="library" tone="teal" foot="Judul buku terdaftar" /></div>
    <div class="col-6 col-md-3"><x-stat-card label="Total Eksemplar" :value="$stats['copies']" icon="book-open" tone="blue" foot="Seluruh stok buku" /></div>
    <div class="col-6 col-md-3"><x-stat-card label="Stok Tersedia" :value="$stats['available']" icon="check" tone="purple" foot="Siap dipinjam" /></div>
    <div class="col-6 col-md-3"><x-stat-card label="Stok Habis" :value="$stats['empty']" icon="triangle-alert" tone="amber" foot="Perlu ditambah stok" /></div>
</div>

<form method="GET" action="{{ route('books.index') }}" class="filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-6 col-lg-3">
            <label for="q">Cari Buku</label>
            <input id="q" type="text" name="q" class="form-control" placeholder="Judul, ISBN, atau kode buku" value="{{ request('q') }}">
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="category_id">Kategori</label>
            <select id="category_id" name="category_id" class="form-select">
                <option value="">Semua</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="author_id">Penulis</label>
            <select id="author_id" name="author_id" class="form-select">
                <option value="">Semua</option>
                @foreach($authors as $author)
                    <option value="{{ $author->id }}" @selected(request('author_id') == $author->id)>{{ $author->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-2">
            <label for="publisher_id">Penerbit</label>
            <select id="publisher_id" name="publisher_id" class="form-select">
                <option value="">Semua</option>
                @foreach($publishers as $publisher)
                    <option value="{{ $publisher->id }}" @selected(request('publisher_id') == $publisher->id)>{{ $publisher->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-1">
            <label for="year">Tahun</label>
            <select id="year" name="year" class="form-select">
                <option value="">Semua</option>
                @foreach($years as $year)
                    <option value="{{ $year }}" @selected(request('year') == $year)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-1">
            <label for="status">Status</label>
            <select id="status" name="status" class="form-select">
                <option value="">Semua</option>
                @foreach(['tersedia' => 'Tersedia', 'dipinjam' => 'Dipinjam', 'stok_habis' => 'Stok Habis', 'tidak_aktif' => 'Nonaktif'] as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3 col-lg-1">
            <label for="availability">Ketersediaan</label>
            <select id="availability" name="availability" class="form-select">
                <option value="">Semua</option>
                <option value="tersedia" @selected(request('availability') === 'tersedia')>Ada</option>
                <option value="sebagian" @selected(request('availability') === 'sebagian')>Sebagian</option>
                <option value="habis" @selected(request('availability') === 'habis')>Habis</option>
            </select>
        </div>
        <div class="col-6 col-md-6 col-lg-2 filter-actions">
            <button type="submit" class="btn btn-teal btn-sm">{!! icon('search', 15) !!} Filter</button>
            <a href="{{ route('books.index') }}" class="btn btn-soft btn-sm">{!! icon('rotate-ccw', 15) !!} Reset</a>
        </div>
    </div>
</form>

<div class="card-soft">
    <div class="card-head">
        <h3 class="card-title">Daftar Buku</h3>
        <span class="cell-secondary">Menampilkan {{ $books->firstItem() ?? 0 }}–{{ $books->lastItem() ?? 0 }} dari {{ $books->total() }} buku</span>
    </div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead>
                <tr>
                    <th>Sampul</th>
                    <th>Kode / ISBN</th>
                    <th>{!! sort_link('Judul Buku', 'title') !!}</th>
                    <th>Penulis</th>
                    <th>Kategori</th>
                    <th>Rak</th>
                    <th>{!! sort_link('Tahun', 'year') !!}</th>
                    <th>{!! sort_link('Stok', 'stock') !!}</th>
                    <th>{!! sort_link('Tersedia', 'available') !!}</th>
                    <th>Status</th>
                    <th>{!! sort_link('Ditambahkan', 'created_at') !!}</th>
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
                        <div class="cell-primary nowrap">{{ $book->book_code }}</div>
                        <div class="cell-secondary nowrap">{{ $book->isbn ?: '—' }}</div>
                    </td>
                    <td>
                        <div class="cell-primary">{{ $book->title }}</div>
                        <div class="cell-secondary">{{ $book->publisher?->name ?? '-' }}</div>
                    </td>
                    <td>{{ $book->author?->name ?? '-' }}</td>
                    <td>{{ $book->category?->name ?? '-' }}</td>
                    <td class="nowrap">{{ $book->shelf?->code ?? '-' }}{{ $book->shelf_number ? ' / '.$book->shelf_number : '' }}</td>
                    <td>{{ $book->year }}</td>
                    <td class="text-center">{{ $book->stock }}</td>
                    <td class="text-center">
                        <span class="{{ $book->available > 0 ? 'text-success' : 'text-danger' }} fw-semibold">{{ $book->available }}</span>
                    </td>
                    <td>{!! status_badge($book->status) !!}</td>
                    <td class="cell-secondary nowrap">{{ tanggal($book->created_at) }}</td>
                    <td>
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="{{ route('books.show', $book) }}" class="btn btn-soft btn-sm" title="Detail buku">{!! icon('eye', 14) !!} Detail</a>
                            <a href="{{ route('books.edit', $book) }}" class="btn btn-outline-teal btn-sm" title="Edit buku">{!! icon('pencil', 14) !!} Edit</a>
                            <form method="POST" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Yakin ingin menghapus buku {{ addslashes($book->title) }}?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger-soft btn-sm" title="Hapus buku">{!! icon('trash-2', 14) !!} Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="12" class="table-empty">Belum ada buku yang cocok dengan pencarian Anda.</td></tr>
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

@extends('layouts.app')
@section('title', 'Edit Buku')
@section('subtitle', 'Perbarui data buku dalam koleksi perpustakaan')

@section('content')
@php
    $statuses = [
        'tersedia' => 'Tersedia',
        'dipinjam' => 'Dipinjam',
        'stok_habis' => 'Stok Habis',
        'tidak_aktif' => 'Tidak Aktif',
    ];
@endphp
<x-page-head title="Edit Buku" subtitle="Ubah informasi buku: {{ $book->title }}." />

<form method="POST" action="{{ route('books.update', $book) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-soft mb-3">
                <div class="card-head">
                    <h3 class="card-title">Informasi Buku</h3>
                    <span class="cell-secondary">Kode: {{ $book->book_code }}</span>
                </div>
                <div class="card-body-soft">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Judul Buku <span class="text-danger">*</span></label>
                            <input id="title" type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $book->title) }}" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="isbn" class="form-label">ISBN</label>
                            <input id="isbn" type="text" name="isbn" class="form-control @error('isbn') is-invalid @enderror" value="{{ old('isbn', $book->isbn) }}" placeholder="Opsional">
                            @error('isbn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="author_id" class="form-label">Penulis <span class="text-danger">*</span></label>
                            <select id="author_id" name="author_id" class="form-select @error('author_id') is-invalid @enderror" required>
                                <option value="">— Pilih Penulis —</option>
                                @foreach($authors as $author)
                                    <option value="{{ $author->id }}" @selected(old('author_id', $book->author_id) == $author->id)>{{ $author->name }}</option>
                                @endforeach
                            </select>
                            @error('author_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="publisher_id" class="form-label">Penerbit <span class="text-danger">*</span></label>
                            <select id="publisher_id" name="publisher_id" class="form-select @error('publisher_id') is-invalid @enderror" required>
                                <option value="">— Pilih Penerbit —</option>
                                @foreach($publishers as $publisher)
                                    <option value="{{ $publisher->id }}" @selected(old('publisher_id', $book->publisher_id) == $publisher->id)>{{ $publisher->name }}</option>
                                @endforeach
                            </select>
                            @error('publisher_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="category_id" class="form-label">Katalog <span class="text-danger">*</span></label>
                            <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">— Pilih Katalog —</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $book->category_id) == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Contoh: Novel, Cerpen, Sejarah.</div>
                        </div>
                        <div class="col-md-4">
                            <label for="year" class="form-label">Tahun Terbit <span class="text-danger">*</span></label>
                            <input id="year" type="number" name="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', $book->year) }}" min="1900" max="{{ now()->year + 1 }}" required>
                            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="stock" class="form-label">Jumlah Stok <span class="text-danger">*</span></label>
                            <input id="stock" type="number" name="stock" class="form-control @error('stock') is-invalid @enderror" value="{{ old('stock', $book->stock) }}" min="1" required>
                            @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Saat ini: {{ $book->available }} dari {{ $book->stock }} eksemplar tersedia.</div>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Deskripsi / Sinopsis</label>
                            <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Ringkasan singkat mengenai buku...">{{ old('description', $book->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-soft mb-3">
                <div class="card-head"><h3 class="card-title">Sampul Buku</h3></div>
                <div class="card-body-soft">
                    @if($book->cover)
                        <div class="text-center mb-3">
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($book->cover) }}" alt="Sampul {{ $book->title }}" style="max-width:130px;max-height:165px;object-fit:cover;border-radius:10px;border:1px solid #e8eef1">
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="remove_cover" id="remove_cover" value="1" @checked(old('remove_cover'))>
                            <label class="form-check-label" for="remove_cover">Hapus sampul saat ini</label>
                        </div>
                    @endif
                    <label for="cover" class="form-label">Ganti Sampul</label>
                    <input id="cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="form-control @error('cover') is-invalid @enderror">
                    @error('cover')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text mt-2">Format JPG, PNG, atau WebP. Maksimal 2 MB.</div>
                </div>
            </div>
            <div class="card-soft">
                <div class="card-head"><h3 class="card-title">Status Buku</h3></div>
                <div class="card-body-soft">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected(old('status', $book->status) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text mt-2">Status akan disesuaikan otomatis mengikuti stok, kecuali ditetapkan sebagai tidak aktif.</div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-teal">{!! icon('save', 16) !!} Simpan Perubahan</button>
        <a href="{{ route('books.show', $book) }}" class="btn btn-outline-teal">{!! icon('eye', 16) !!} Lihat Detail</a>
        <a href="{{ route('books.index') }}" class="btn btn-soft">{!! icon('x', 16) !!} Batal</a>
    </div>
</form>
@endsection

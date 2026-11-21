@extends('layouts.app')
@section('title', 'Tambah Buku')
@section('subtitle', 'Tambahkan buku baru ke koleksi perpustakaan')

@section('content')
<x-page-head title="Tambah Buku Baru" subtitle="Lengkapi informasi bibliografi buku sebelum disimpan." />

<form method="POST" action="{{ route('books.store') }}" enctype="multipart/form-data">
    @csrf
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card-soft mb-3">
                <div class="card-head"><h3 class="card-title">Informasi Buku</h3></div>
                <div class="card-body-soft">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="title" class="form-label">Judul Buku <span class="text-danger">*</span></label>
                            <input id="title" type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="Contoh: Laskar Pelangi" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="isbn" class="form-label">ISBN</label>
                            <input id="isbn" type="text" name="isbn" class="form-control @error('isbn') is-invalid @enderror" value="{{ old('isbn') }}" placeholder="Opsional">
                            @error('isbn')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="author_id" class="form-label">Penulis <span class="text-danger">*</span></label>
                            <select id="author_id" name="author_id" class="form-select @error('author_id') is-invalid @enderror" required>
                                <option value="">— Pilih Penulis —</option>
                                @foreach($authors as $author)
                                    <option value="{{ $author->id }}" @selected(old('author_id') == $author->id)>{{ $author->name }}</option>
                                @endforeach
                            </select>
                            @error('author_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="publisher_id" class="form-label">Penerbit <span class="text-danger">*</span></label>
                            <select id="publisher_id" name="publisher_id" class="form-select @error('publisher_id') is-invalid @enderror" required>
                                <option value="">— Pilih Penerbit —</option>
                                @foreach($publishers as $publisher)
                                    <option value="{{ $publisher->id }}" @selected(old('publisher_id') == $publisher->id)>{{ $publisher->name }}</option>
                                @endforeach
                            </select>
                            @error('publisher_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="category_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">— Pilih Kategori —</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="shelf_id" class="form-label">Rak Buku <span class="text-danger">*</span></label>
                            <select id="shelf_id" name="shelf_id" class="form-select @error('shelf_id') is-invalid @enderror" required>
                                <option value="">— Pilih Rak —</option>
                                @foreach($shelves as $shelf)
                                    <option value="{{ $shelf->id }}" @selected(old('shelf_id') == $shelf->id)>{{ $shelf->code }} — {{ $shelf->name }}</option>
                                @endforeach
                            </select>
                            @error('shelf_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="shelf_number" class="form-label">Nomor Rak</label>
                            <input id="shelf_number" type="text" name="shelf_number" class="form-control @error('shelf_number') is-invalid @enderror" value="{{ old('shelf_number') }}" placeholder="Contoh: A-12">
                            @error('shelf_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="year" class="form-label">Tahun Terbit <span class="text-danger">*</span></label>
                            <input id="year" type="number" name="year" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', now()->year) }}" min="1900" max="{{ now()->year + 1 }}" required>
                            @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="stock" class="form-label">Jumlah Stok <span class="text-danger">*</span></label>
                            <input id="stock" type="number" name="stock" class="form-control @error('stock') is-invalid @enderror" value="{{ old('stock', 1) }}" min="1" required>
                            @error('stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Stok tersedia akan diisi otomatis sesuai jumlah stok.</div>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Deskripsi / Sinopsis</label>
                            <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="4" placeholder="Ringkasan singkat mengenai buku...">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-soft">
                <div class="card-head"><h3 class="card-title">Sampul Buku</h3></div>
                <div class="card-body-soft">
                    <label for="cover" class="form-label">Foto Sampul</label>
                    <input id="cover" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="form-control @error('cover') is-invalid @enderror">
                    @error('cover')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text mt-2">Format JPG, PNG, atau WebP. Maksimal 2 MB.</div>
                    <div class="alert alert-info alert-soft small mt-3 mb-0">
                        {!! icon('info', 15) !!} Kode buku akan dibuat otomatis setelah data disimpan.
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-teal">{!! icon('save', 16) !!} Simpan Buku</button>
        <a href="{{ route('books.index') }}" class="btn btn-soft">{!! icon('x', 16) !!} Batal</a>
    </div>
</form>
@endsection

@extends('layouts.app')
@section('title', 'Data '.$title)
@section('subtitle', 'Kelola master data '.$title.' perpustakaan')

@section('content')
<x-page-head title="Data {{ $title }}" subtitle="Tambah, ubah, dan hapus data master {{ strtolower($title) }}." />

<div class="row g-3 mb-3">
    @foreach($types as $key => $meta)
        <div class="col-6 col-lg-4"><x-stat-card label="{{ $meta['title'] }}" :value="$counts[$key]" icon="{{ $meta['icon'] }}" tone="{{ $type === $key ? 'teal' : 'slate' }}" foot="Master {{ strtolower($meta['title']) }}" /></div>
    @endforeach
</div>

<div class="card-soft mb-3">
    <div class="card-head"><h3 class="card-title">Tambah {{ $title }}</h3></div>
    <div class="card-body-soft">
        <form method="POST" action="{{ route('lookups.store', $type) }}">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label for="name" class="form-label">{{ $config['label'] }}</label>
                    <input id="name" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="{{ $config['hint'] }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="description" class="form-label">Deskripsi</label>
                    <input id="description" type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}" placeholder="Opsional">
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-teal w-100">{!! icon('plus', 16) !!} Tambah</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="GET" action="{{ route('lookups.index', $type) }}" class="filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-md-10">
            <label for="q">Cari Data</label>
            <input id="q" type="text" name="q" class="form-control" placeholder="Cari berdasarkan nama atau deskripsi" value="{{ request('q') }}">
        </div>
        <div class="col-md-2 filter-actions">
            <button type="submit" class="btn btn-teal btn-sm w-100">{!! icon('search', 15) !!} Cari</button>
            <a href="{{ route('lookups.index', $type) }}" class="btn btn-soft btn-sm w-100">Reset</a>
        </div>
    </div>
</form>

<div class="card-soft">
    <div class="card-head">
        <h3 class="card-title">Daftar {{ $title }}</h3>
        <span class="cell-secondary">Menampilkan {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} dari {{ $records->total() }} data</span>
    </div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Deskripsi</th>
                    <th>Jumlah Buku</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                <tr>
                    <td class="cell-secondary">{{ $records->firstItem() + $loop->index }}</td>
                    <td class="cell-primary">{{ $record->name }}</td>
                    <td>{{ $record->description ?: '-' }}</td>
                    <td><span class="badge badge-primary">{{ $record->books()->count() }}</span></td>
                    <td>
                        <div class="d-flex gap-1 justify-content-center">
                            <button type="button" class="btn btn-outline-teal btn-sm" data-bs-toggle="modal" data-bs-target="#editLookup{{ $record->id }}">{!! icon('pencil', 14) !!} Edit</button>
                            <form method="POST" action="{{ route('lookups.destroy', [$type, $record->id]) }}" onsubmit="return confirm('Yakin ingin menghapus data ini?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger-soft btn-sm">{!! icon('trash-2', 14) !!} Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="table-empty">Belum ada data {{ strtolower($title) }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="card-body-soft d-flex justify-content-end">{{ $records->links() }}</div>
    @endif
</div>

@foreach($records as $record)
<div class="modal fade" id="editLookup{{ $record->id }}" tabindex="-1" aria-labelledby="editLookupLabel{{ $record->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('lookups.update', [$type, $record->id]) }}">
                @csrf @method('PUT')
                <input type="hidden" name="edit_id" value="{{ $record->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="editLookupLabel{{ $record->id }}">Edit {{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name_{{ $record->id }}" class="form-label">{{ $config['label'] }}</label>
                        <input id="name_{{ $record->id }}" type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $record->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="description_{{ $record->id }}" class="form-label">Deskripsi</label>
                        <textarea id="description_{{ $record->id }}" name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $record->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-teal">{!! icon('save', 16) !!} Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@if(old('edit_id'))
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = document.getElementById('editLookup{{ old('edit_id') }}');
        if (modal && window.bootstrap) {
            new bootstrap.Modal(modal).show();
        }
    });
</script>
@endpush
@endif
@endsection

@extends('layouts.app')
@section('title', 'Pengaturan Perpustakaan')
@section('subtitle', 'Kelola identitas perpustakaan dan aturan peminjaman')

@section('content')
<x-page-head title="Pengaturan Perpustakaan" subtitle="Perbarui identitas, logo, dan aturan operasional perpustakaan." />

<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card-soft h-100">
                <div class="card-head">
                    <h3 class="card-title">Identitas Perpustakaan</h3>
                    {!! icon('library-big', 18) !!}
                </div>
                <div class="card-body-soft">
                    <div class="mb-3">
                        <label class="form-label">Nama Perpustakaan</label>
                        <input type="text" name="library_name" class="form-control" value="{{ old('library_name', $settings['library_name']) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Perpustakaan</label>
                        <input type="text" name="library_address" class="form-control" value="{{ old('library_address', $settings['library_address']) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo Perpustakaan</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            @if(! empty($settings['library_logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($settings['library_logo']))
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['library_logo']) }}" alt="Logo perpustakaan" style="max-height:76px;max-width:140px;object-fit:contain;border:1px solid var(--line);border-radius:10px;padding:6px;background:#fff">
                            @else
                                <div class="table-avatar" style="width:64px;height:64px">{!! icon('image', 24) !!}</div>
                            @endif
                            <div class="cell-secondary">
                                Logo saat ini. Format gambar, maksimal 2 MB.<br>Kosongkan input di bawah jika tidak ingin mengganti.
                            </div>
                        </div>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Informasi Tambahan</label>
                        <textarea name="library_info" class="form-control" rows="3" placeholder="Visi, misi, atau informasi lainnya">{{ old('library_info', $settings['library_info']) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card-soft h-100">
                <div class="card-head">
                    <h3 class="card-title">Aturan Peminjaman</h3>
                    {!! icon('book-marked', 18) !!}
                </div>
                <div class="card-body-soft">
                    <div class="mb-3">
                        <label class="form-label">Maksimal Buku per Anggota</label>
                        <input type="number" name="max_books" class="form-control" min="1" max="20" value="{{ old('max_books', $settings['max_books']) }}" required>
                        <div class="cell-secondary mt-1">Rentang 1 sampai 20 buku.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lama Peminjaman (hari)</label>
                        <input type="number" name="loan_days" class="form-control" min="1" max="90" value="{{ old('loan_days', $settings['loan_days']) }}" required>
                        <div class="cell-secondary mt-1">Rentang 1 sampai 90 hari.</div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Denda per Hari</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" name="fine_per_day" class="form-control" min="0" step="1000" value="{{ old('fine_per_day', $settings['fine_per_day']) }}" required>
                        </div>
                        <div class="cell-secondary mt-1">Isi 0 jika tidak ada denda keterlambatan.</div>
                    </div>
                    <div class="alert alert-light border small">
                        {!! icon('circle-alert', 15) !!} Perubahan aturan berlaku untuk transaksi baru.
                    </div>
                    <button type="submit" class="btn btn-teal w-100">{!! icon('save', 16) !!} Simpan Pengaturan</button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

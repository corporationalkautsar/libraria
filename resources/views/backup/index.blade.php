@extends('layouts.app')
@section('title', 'Backup Database')
@section('subtitle', 'Kelola cadangan dan pemulihan database perpustakaan')

@section('content')
<x-page-head title="Backup Database" subtitle="Buat, unduh, hapus, dan pulihkan cadangan database perpustakaan." />

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-soft h-100">
            <div class="card-head">
                <h3 class="card-title">Daftar Backup</h3>
                <span class="cell-secondary">{{ $backups->count() }} berkas</span>
            </div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead>
                        <tr><th>Nama Berkas</th><th>Ukuran</th><th>Terakhir Dibuat</th><th class="text-end">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($backups as $backup)
                        @php
                            $kb = $backup['size'] / 1024;
                            $size = $kb >= 1024 ? number_format($kb / 1024, 2).' MB' : number_format($kb, 1).' KB';
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="mini-rank">{!! icon('database', 14) !!}</span>
                                    <span class="cell-primary">{{ $backup['filename'] }}</span>
                                </div>
                            </td>
                            <td class="cell-secondary nowrap">{{ $size }}</td>
                            <td class="cell-secondary nowrap">{{ tanggal($backup['modified'], true) }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('backup.download', $backup['filename']) }}" class="btn btn-outline-teal btn-sm" title="Unduh backup">{!! icon('download', 14) !!} Unduh</a>
                                    <form method="POST" action="{{ route('backup.destroy', $backup['filename']) }}"
                                          onsubmit="return confirm('Hapus berkas backup {{ $backup['filename'] }}? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger-soft btn-sm" title="Hapus backup">{!! icon('trash-2', 14) !!}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="table-empty">Belum ada berkas backup. Buat backup pertama Anda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Buat Backup Baru</h3></div>
            <div class="card-body-soft">
                <p class="cell-secondary">
                    Cadangkan seluruh tabel database ke dalam berkas SQL. Simpan berkas di tempat lain secara berkala.
                </p>
                <form method="POST" action="{{ route('backup.store') }}"
                      onsubmit="return confirm('Buat backup database sekarang?')">
                    @csrf
                    <button type="submit" class="btn btn-teal w-100">{!! icon('database-backup', 16) !!} Buat Backup Sekarang</button>
                </form>
                <hr>
                <div class="small text-muted">
                    <div class="cell-primary mb-1">Informasi</div>
                    <div class="d-flex justify-content-between mb-1"><span>Lokasi penyimpanan</span><span>public/backups</span></div>
                    <div class="d-flex justify-content-between"><span>Format berkas</span><span>.sql</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-soft mt-3">
    <div class="card-head">
        <h3 class="card-title">Pulihkan Database</h3>
        {!! icon('archive-restore', 18) !!}
    </div>
    <div class="card-body-soft">
        <div class="alert alert-danger alert-soft mb-3">
            <strong>{!! icon('triangle-alert', 16) !!} PERINGATAN: PEMULIHAN AKAN MENIMPA SELURUH DATA SAAT INI.</strong>
            <div class="mt-1">
                Seluruh data yang tersimpan sekarang akan digantikan oleh isi berkas backup yang dipilih.
                Pastikan Anda telah membuat backup terbaru sebelum melanjutkan. Proses ini tidak dapat dibatalkan.
            </div>
        </div>
        <form method="POST" action="{{ route('backup.restore') }}"
              onsubmit="return confirm('Yakin memulihkan database? Seluruh data saat ini akan ditimpa.')">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Berkas Backup</label>
                    <select name="file" class="form-select" required @if($backups->isEmpty()) disabled @endif>
                        <option value="">-- Pilih berkas backup --</option>
                        @foreach($backups as $backup)
                            <option value="{{ $backup['filename'] }}">{{ $backup['filename'] }} · {{ tanggal($backup['modified'], true) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Ketik RESTORE untuk mengonfirmasi</label>
                    <input type="text" name="confirmation" class="form-control" placeholder="RESTORE" autocomplete="off" required @if($backups->isEmpty()) disabled @endif>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-danger-soft w-100" @if($backups->isEmpty()) disabled @endif>
                        {!! icon('rotate-ccw', 16) !!} Pulihkan Database
                    </button>
                </div>
            </div>
        </form>
        @if($backups->isEmpty())
            <div class="cell-secondary mt-2">Buat backup terlebih dahulu sebelum melakukan pemulihan.</div>
        @endif
    </div>
</div>
@endsection

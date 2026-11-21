@extends('layouts.app')
@section('title', 'Tambah Anggota')
@section('subtitle', 'Data anggota hanya tiga informasi')

@section('content')
<x-page-head title="Tambah Anggota" subtitle="Isi tiga data berikut. ID anggota dibuat otomatis oleh sistem." />
<div class="row justify-content-center">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('members.store') }}" class="card-soft">
            @csrf
            <div class="card-head"><h3 class="card-title">Formulir Anggota Baru</h3>
                <span class="badge badge-success">Hanya 3 data</span></div>
            <div class="card-body-soft">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input name="full_name" value="{{ old('full_name') }}" class="form-control" placeholder="Contoh: Ahmad Fauzi" maxlength="150" required autofocus>
                    @error('full_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                    <input name="school" value="{{ old('school') }}" class="form-control" placeholder="Contoh: SMP Nurul Jadid" maxlength="150" required>
                    @error('school')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Asal <span class="text-danger">*</span></label>
                    <input name="origin" value="{{ old('origin') }}" class="form-control" placeholder="Contoh: Probolinggo" maxlength="100" required>
                    @error('origin')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="alert alert-info alert-soft mb-0 small">
                    {!! icon('info', 15) !!} Sistem akan membuat ID anggota otomatis (AGT-0001, AGT-0002, ...).
                    Data pribadi lain sengaja tidak dikumpulkan sesuai kebijakan aplikasi.
                </div>
            </div>
            <div class="card-body-soft border-top d-flex gap-2">
                <button class="btn btn-teal">{!! icon('save', 16) !!} Simpan Anggota</button>
                <a href="{{ route('members.index') }}" class="btn btn-soft">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

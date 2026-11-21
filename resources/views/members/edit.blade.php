@extends('layouts.app')
@section('title', 'Edit Anggota')
@section('subtitle', 'Perbarui data anggota')

@section('content')
<x-page-head title="Edit Anggota" subtitle="ID anggota {{ $member->member_code }} tidak dapat diubah." />
<div class="row justify-content-center">
    <div class="col-lg-7">
        <form method="POST" action="{{ route('members.update', $member) }}" class="card-soft">
            @csrf @method('PUT')
            <div class="card-head"><h3 class="card-title">Formulir Edit Anggota</h3></div>
            <div class="card-body-soft">
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input name="full_name" value="{{ old('full_name', $member->full_name) }}" class="form-control" maxlength="150" required>
                    @error('full_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Sekolah <span class="text-danger">*</span></label>
                    <input name="school" value="{{ old('school', $member->school) }}" class="form-control" maxlength="150" required>
                    @error('school')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Asal <span class="text-danger">*</span></label>
                    <input name="origin" value="{{ old('origin', $member->origin) }}" class="form-control" maxlength="100" required>
                    @error('origin')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="card-body-soft border-top d-flex gap-2">
                <button class="btn btn-teal">{!! icon('save', 16) !!} Simpan Perubahan</button>
                <a href="{{ route('members.show', $member) }}" class="btn btn-soft">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection

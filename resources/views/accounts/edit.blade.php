@extends('layouts.app')
@section('title', 'Edit Akun Petugas')
@section('subtitle', 'Perbarui data akun petugas perpustakaan')

@section('content')
<x-page-head title="Edit Akun Petugas" subtitle="Perbarui data akun {{ $account->name }}." />

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-soft">
            <div class="card-head"><h3 class="card-title">Data Akun</h3></div>
            <div class="card-body-soft">
                <form method="POST" action="{{ route('accounts.update', $account->id) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $account->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password Baru <span class="cell-secondary">(kosongkan jika tidak diubah)</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password baru">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Akun</label>
                            @php $currentStatus = (string) old('is_active', $account->is_active ? '1' : '0'); @endphp
                            <select name="is_active" class="form-select">
                                <option value="1" @selected($currentStatus === '1')>Aktif</option>
                                <option value="0" @selected($currentStatus === '0')>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <div class="form-control d-flex align-items-center">{!! status_badge('petugas') !!}</div>
                            <div class="cell-secondary mt-1">Role petugas tidak dapat diubah menjadi admin.</div>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-teal">{!! icon('save', 16) !!} Simpan Perubahan</button>
                        <a href="{{ route('accounts.index') }}" class="btn btn-soft">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-soft">
            <div class="card-head"><h3 class="card-title">Informasi Akun</h3></div>
            <div class="card-body-soft">
                <div class="text-center mb-3">
                    <div class="avatar mx-auto mb-2" style="width:64px;height:64px;font-size:24px">{{ strtoupper(Str::charAt($account->name, 0)) }}</div>
                    <div class="cell-primary">{{ $account->name }}</div>
                    <div class="cell-secondary">{{ $account->email }}</div>
                </div>
                <hr>
                <div class="small text-muted">
                    <div class="d-flex justify-content-between mb-1"><span>Bergabung</span><span>{{ tanggal($account->created_at) }}</span></div>
                    <div class="d-flex justify-content-between mb-1"><span>Status saat ini</span><span>{!! $account->is_active ? status_badge('aktif') : status_badge('nonaktif') !!}</span></div>
                    <div class="d-flex justify-content-between"><span>Peminjaman dicatat</span><span>{{ $account->loans()->count() }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

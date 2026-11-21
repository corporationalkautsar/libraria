@extends('layouts.app')
@section('title', 'Profil Pengguna')
@section('subtitle', 'Kelola data pribadi dan password akun')

@section('content')
<x-page-head title="Profil Saya" subtitle="Perbarui informasi akun dan keamanan password." />
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card-soft card-body-soft text-center">
            <div class="avatar mx-auto mb-3" style="width:74px;height:74px;font-size:26px">{{ strtoupper(Str::charAt($user->name, 0)) }}</div>
            <h5 class="mb-1">{{ $user->name }}</h5>
            <p class="text-muted small mb-2">{{ $user->email }}</p>
            {!! status_badge($user->role) !!}
            <hr>
            <div class="small text-muted text-start">
                <div class="d-flex justify-content-between"><span>Bergabung</span><span>{{ tanggal($user->created_at) }}</span></div>
                <div class="d-flex justify-content-between"><span>Status</span><span>{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-soft mb-3">
            <div class="card-head"><h3 class="card-title">Data Profil</h3></div>
            <div class="card-body-soft">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap</label>
                            <input name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                    </div>
                    <div class="mt-3"><button class="btn btn-teal">{!! icon('save', 16) !!} Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
        <div class="card-soft">
            <div class="card-head"><h3 class="card-title">Ganti Password</h3></div>
            <div class="card-body-soft">
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Password Lama</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <div class="mt-3"><button class="btn btn-outline-teal">{!! icon('key-round', 16) !!} Ubah Password</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

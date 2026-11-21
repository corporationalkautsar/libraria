@extends('layouts.app')
@section('title', 'Manajemen Akun')
@section('subtitle', 'Kelola akun petugas dan status akses perpustakaan')

@section('content')
<x-page-head title="Manajemen Akun Petugas" subtitle="Tambah, ubah, dan kelola akun petugas perpustakaan." />

<div class="row g-3 mb-3">
    <div class="col-6 col-md-4"><x-stat-card label="Total Petugas" :value="$stats['total']" icon="users" tone="teal" foot="Akun terdaftar" /></div>
    <div class="col-6 col-md-4"><x-stat-card label="Akun Aktif" :value="$stats['aktif']" icon="user-check" tone="blue" foot="Dapat login" /></div>
    <div class="col-6 col-md-4"><x-stat-card label="Akun Nonaktif" :value="$stats['nonaktif']" icon="user-x" tone="slate" foot="Akses ditangguhkan" /></div>
</div>

<form method="GET" action="{{ route('accounts.index') }}" class="filter-bar">
    <div class="row g-2 align-items-end">
        <div class="col-md-8 col-lg-9">
            <label for="search">Cari Petugas</label>
            <input type="search" id="search" name="search" class="form-control" value="{{ $search }}" placeholder="Nama atau email petugas">
        </div>
        <div class="col-md-4 col-lg-3">
            <div class="filter-actions">
                <button type="submit" class="btn btn-teal">{!! icon('search', 15) !!} Cari</button>
                <a href="{{ route('accounts.index') }}" class="btn btn-soft">Reset</a>
            </div>
        </div>
    </div>
</form>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card-soft h-100">
            <div class="card-head">
                <h3 class="card-title">Daftar Akun Petugas</h3>
                <span class="cell-secondary">{{ $accounts->total() }} akun ditemukan</span>
            </div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead>
                        <tr>
                            <th>{!! sort_link('Nama', 'name') !!}</th>
                            <th>{!! sort_link('Email', 'email') !!}</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>{!! sort_link('Bergabung', 'created_at') !!}</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($accounts as $account)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="table-avatar">{{ strtoupper(Str::charAt($account->name, 0)) }}</span>
                                    <div>
                                        <div class="cell-primary">{{ $account->name }}</div>
                                        @if((int) $account->id === (int) auth()->id())<div class="cell-secondary">Akun Anda</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="cell-secondary">{{ $account->email }}</td>
                            <td>{!! status_badge($account->role) !!}</td>
                            <td>{!! $account->is_active ? status_badge('aktif') : status_badge('nonaktif') !!}</td>
                            <td class="cell-secondary nowrap">{{ tanggal($account->created_at) }}</td>
                            <td>
                                <div class="d-flex justify-content-end gap-1">
                                    <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-outline-teal btn-sm" title="Ubah akun">{!! icon('pencil', 14) !!} Edit</a>
                                    @if((int) $account->id !== (int) auth()->id())
                                        <form method="POST" action="{{ route('accounts.toggle', $account->id) }}"
                                              onsubmit="return confirm('{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $account->name }}?')">
                                            @csrf
                                            <button type="submit" class="btn btn-soft btn-sm" title="{{ $account->is_active ? 'Nonaktifkan' : 'Aktifkan' }} akun">
                                                {!! icon($account->is_active ? 'toggle-left' : 'toggle-right', 14) !!}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('accounts.destroy', $account->id) }}"
                                              onsubmit="return confirm('Hapus akun {{ $account->name }}? Tindakan ini tidak dapat dibatalkan.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger-soft btn-sm" title="Hapus akun">{!! icon('trash-2', 14) !!}</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="table-empty">Belum ada akun petugas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($accounts->hasPages())
                <div class="card-body-soft d-flex justify-content-end">{{ $accounts->links('pagination::bootstrap-5') }}</div>
            @endif
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card-soft">
            <div class="card-head"><h3 class="card-title">Tambah Akun Petugas</h3></div>
            <div class="card-body-soft">
                <form method="POST" action="{{ route('accounts.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Nama petugas" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="petugas@perpustakaan.id" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password" required>
                    </div>
                    <div class="alert alert-light border small mb-3">
                        {!! icon('shield-check', 15) !!} Akun baru otomatis berstatus <strong>petugas</strong> dan aktif. Role admin tidak dapat dibuat dari halaman ini.
                    </div>
                    <button type="submit" class="btn btn-teal w-100">{!! icon('user-plus', 16) !!} Tambah Akun</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

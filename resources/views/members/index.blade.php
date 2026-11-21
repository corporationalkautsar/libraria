@extends('layouts.app')
@section('title', 'Data Anggota')
@section('subtitle', 'Kelola data anggota perpustakaan')

@section('content')
@php $canManage = auth()->user()->isPetugas(); @endphp
<div class="page-head">
    <div>
        <h1>Data Anggota</h1>
        <p>Anggota hanya memiliki tiga data: nama lengkap, sekolah, dan asal.</p>
    </div>
    @if($canManage)
    <div><a href="{{ route('members.create') }}" class="btn btn-teal">{!! icon('user-plus',16) !!} Tambah Anggota</a></div>
    @endif
</div>

<form class="filter-bar" method="GET">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label>Cari Anggota</label>
            <input type="text" name="q" class="form-control" value="{{ request('q') }}" placeholder="Nama atau ID anggota (AGT-0001)">
        </div>
        <div class="col-md-3">
            <label>Sekolah</label>
            <select name="school" class="form-select">
                <option value="">Semua Sekolah</option>
                @foreach($schools as $school)<option value="{{ $school }}" @selected(request('school') === $school)>{{ $school }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label>Asal</label>
            <select name="origin" class="form-select">
                <option value="">Semua Asal</option>
                @foreach($origins as $origin)<option value="{{ $origin }}" @selected(request('origin') === $origin)>{{ $origin }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-3 filter-actions">
            <button class="btn btn-teal">{!! icon('filter', 15) !!} Filter</button>
            <a href="{{ route('members.index') }}" class="btn btn-soft">Reset</a>
        </div>
    </div>
</form>

<div class="card-soft">
    <div class="card-head">
        <h3 class="card-title">Daftar Anggota ({{ $members->total() }})</h3>
        <span class="cell-secondary">Klik detail untuk melihat riwayat peminjaman</span>
    </div>
    <div class="table-wrap">
        <table class="table-soft">
            <thead>
                <tr>
                    <th>No</th>
                    <th>{!! sort_link('ID Anggota', 'member_code') !!}</th>
                    <th>{!! sort_link('Nama Lengkap', 'full_name') !!}</th>
                    <th>{!! sort_link('Sekolah', 'school') !!}</th>
                    <th>{!! sort_link('Asal', 'origin') !!}</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $i => $member)
                <tr>
                    <td class="cell-secondary">{{ $members->firstItem() + $i }}</td>
                    <td class="cell-primary">{{ $member->member_code }}</td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="table-avatar">{{ Str::charAt($member->full_name, 0) }}</span>
                            <span class="cell-primary">{{ $member->full_name }}</span>
                        </div>
                    </td>
                    <td>{{ $member->school }}</td>
                    <td>{{ $member->origin }}</td>
                    <td>
                        @if($member->is_blocked)<span class="badge badge-danger">Diblokir</span>
                        @else<span class="badge badge-success">Aktif</span>@endif
                    </td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('members.show', $member) }}" class="btn btn-outline-teal btn-sm" title="Detail">{!! icon('eye', 14) !!}</a>
                            @if($canManage)
                                <a href="{{ route('members.edit', $member) }}" class="btn btn-soft btn-sm" title="Edit">{!! icon('pencil', 14) !!}</a>
                                <form method="POST" action="{{ route('members.destroy', $member) }}" onsubmit="return confirm('Yakin ingin menghapus anggota {{ addslashes($member->full_name) }}?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-danger-soft btn-sm" title="Hapus">{!! icon('trash-2', 14) !!}</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="table-empty">{!! icon('users', 28) !!}<div class="mt-2">Tidak ada anggota yang cocok dengan pencarian.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body-soft border-top">{{ $members->links() }}</div>
</div>
@endsection

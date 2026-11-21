@extends('layouts.app')
@section('title', 'Detail Anggota')
@section('subtitle', 'Profil dan riwayat peminjaman anggota')

@section('content')
<div class="page-head">
    <div>
        <h1>Detail Anggota</h1>
        <p>Data anggota dan seluruh riwayat transaksinya.</p>
    </div>
    @if(auth()->user()->isPetugas())
    <div><a href="{{ route('members.edit', $member) }}" class="btn btn-teal">{!! icon('pencil',16) !!} Edit Anggota</a></div>
    @endif
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card-soft card-body-soft text-center">
            <div class="avatar mx-auto mb-3" style="width:70px;height:70px;font-size:25px">{{ strtoupper(Str::charAt($member->full_name, 0)) }}</div>
            <h5 class="mb-1">{{ $member->full_name }}</h5>
            <div class="cell-secondary mb-3">{{ $member->member_code }}</div>
            @if($member->is_blocked)<span class="badge badge-danger">Diblokir</span>@else<span class="badge badge-success">Aktif</span>@endif
            <hr>
            <div class="text-start small">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Sekolah</span><strong>{{ $member->school }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Asal</span><strong>{{ $member->origin }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Terdaftar</span><strong>{{ tanggal($member->created_at) }}</strong></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Pinjaman aktif</span><strong>{{ $member->active_loans_count }}</strong></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card-soft h-100">
            <div class="card-head"><h3 class="card-title">Statistik Anggota</h3></div>
            <div class="card-body-soft">
                <div class="row g-3">
                    <div class="col-6"><x-stat-card label="Total Transaksi" :value="$member->loans()->count()" icon="history" tone="teal" /></div>
                    <div class="col-6"><x-stat-card label="Pinjaman Selesai" :value="$member->loans()->where('status','selesai')->count()" icon="circle-check" tone="blue" /></div>
                    <div class="col-6"><x-stat-card label="Pernah Terlambat" :value="$member->loans()->where('status','terlambat')->count()" icon="triangle-alert" tone="red" /></div>
                    <div class="col-6"><x-stat-card label="Total Denda" :value="rupiah($member->fines()->sum('amount'))" icon="banknote" tone="amber" /></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-soft">
    <div class="card-head"><h3 class="card-title">Riwayat Peminjaman</h3></div>
    <form class="filter-bar m-3" method="GET">
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label>Status</label>
                <select name="status" class="form-select"><option value="">Semua Status</option>
                    @foreach(['dipinjam','terlambat','selesai','dibatalkan'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3"><label>Filter Tanggal</label>
                <select name="date_filter" class="form-select">
                    @foreach(App\Support\DateFilter::OPTIONS as $key=>$label)<option value="{{ $key }}" @selected(request('date_filter')===$key)>{{ $label }}</option>@endforeach
                </select>
            </div>
            @if(request('date_filter')==='custom')
            <div class="col-md-2"><label>Dari</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
            <div class="col-md-2"><label>Sampai</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
            @endif
            <div class="col-md-2 filter-actions"><button class="btn btn-teal">Filter</button></div>
        </div>
    </form>
    <div class="table-wrap">
        <table class="table-soft">
            <thead><tr><th>No. Transaksi</th><th>Tanggal Pinjam</th><th>Jatuh Tempo</th><th>Jumlah Buku</th><th>Petugas</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($loans as $loan)
                <tr>
                    <td class="cell-primary">{{ $loan->loan_number }}</td>
                    <td>{{ tanggal($loan->loan_date) }}</td>
                    <td>{{ tanggal($loan->due_date) }}</td>
                    <td>{{ $loan->total_books }}</td>
                    <td>{{ $loan->user->name }}</td>
                    <td>{!! status_badge($loan->status) !!}</td>
                    <td><a href="{{ route('loans.show', $loan) }}" class="btn btn-outline-teal btn-sm">{!! icon('eye',14) !!}</a></td>
                </tr>
                @empty<tr><td colspan="7" class="table-empty">Belum ada riwayat peminjaman.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body-soft border-top">{{ $loans->links() }}</div>
</div>
@endsection

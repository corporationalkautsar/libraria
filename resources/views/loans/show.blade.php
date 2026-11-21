@extends('layouts.app')
@section('title', 'Detail Peminjaman')
@section('subtitle', 'Nomor transaksi '.$loan->loan_number)

@section('content')
<div class="page-head">
    <div><h1>Detail Peminjaman</h1><p>{{ $loan->loan_number }} · Dicatat oleh {{ $loan->user->name }}</p></div>
    <div class="d-flex gap-2">
        <a href="{{ route('loans.index') }}" class="btn btn-soft">{!! icon('arrow-left',16) !!} Kembali</a>
        @if(auth()->user()->isPetugas() && $loan->returns()->exists())
            <a href="{{ route('returns.show', $loan->returns->first()) }}" class="btn btn-outline-teal">{!! icon('rotate-ccw',16) !!} Lihat Pengembalian</a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><x-stat-card label="Tanggal Pinjam" :value="tanggal($loan->loan_date)" icon="calendar-days" tone="teal" /></div>
    <div class="col-md-3"><x-stat-card label="Jatuh Tempo" :value="tanggal($loan->due_date)" icon="calendar-clock" tone="amber" /></div>
    <div class="col-md-3"><x-stat-card label="Jumlah Buku" :value="$loan->total_books" icon="book-open" tone="blue" /></div>
    <div class="col-md-3"><x-stat-card label="Status" :value="$loan->status_label" icon="activity" tone="{{ $loan->status==='terlambat' ? 'red' : 'teal' }}" /></div>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card-soft mb-3">
            <div class="card-head"><h3 class="card-title">Data Anggota</h3></div>
            <div class="card-body-soft">
                <div class="row">
                    <div class="col-md-4 mb-2"><div class="cell-secondary">ID Anggota</div><div class="cell-primary">{{ $loan->member->member_code }}</div></div>
                    <div class="col-md-4 mb-2"><div class="cell-secondary">Nama Lengkap</div><div class="cell-primary">{{ $loan->member->full_name }}</div></div>
                    <div class="col-md-4 mb-2"><div class="cell-secondary">Sekolah</div><div class="cell-primary">{{ $loan->member->school }}</div></div>
                    <div class="col-md-4 mb-2"><div class="cell-secondary">Asal</div><div class="cell-primary">{{ $loan->member->origin }}</div></div>
                    <div class="col-md-4 mb-2"><div class="cell-secondary">Petugas</div><div class="cell-primary">{{ $loan->user->name }}</div></div>
                    <div class="col-md-4 mb-2"><div class="cell-secondary">Catatan</div><div class="cell-primary">{{ $loan->notes ?: '-' }}</div></div>
                </div>
            </div>
        </div>

        <div class="card-soft">
            <div class="card-head"><h3 class="card-title">Buku yang Dipinjam</h3></div>
            <div class="table-wrap">
                <table class="table-soft">
                    <thead><tr><th>No</th><th>Judul Buku</th><th>Penulis</th><th>ISBN</th><th>Status</th><th>Denda</th></tr></thead>
                    <tbody>
                        @foreach($loan->details as $i => $detail)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td class="cell-primary">{{ $detail->book->title }}</td>
                            <td>{{ $detail->book->author->name }}</td>
                            <td class="cell-secondary">{{ $detail->book->isbn ?: '-' }}</td>
                            <td>{!! status_badge($detail->status) !!}</td>
                            <td>{{ rupiah($detail->fine_amount) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(auth()->user()->isPetugas() && in_array($loan->status, ['dipinjam','terlambat']))
            <div class="card-body-soft border-top d-flex justify-content-between align-items-center">
                <span class="cell-secondary">Selesaikan transaksi ini setelah buku dikembalikan.</span>
                <div class="d-flex gap-2">
                    <a href="{{ route('returns.create', ['loan_id'=>$loan->id]) }}" class="btn btn-teal">{!! icon('rotate-ccw',16) !!} Proses Pengembalian</a>
                    <form method="POST" action="{{ route('loans.cancel', $loan) }}" onsubmit="return confirm('Yakin membatalkan transaksi ini? Stok buku akan dikembalikan.')">
                        @csrf<button class="btn btn-danger-soft">{!! icon('ban',16) !!} Batalkan</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card-soft mb-3">
            <div class="card-head"><h3 class="card-title">Pengembalian</h3></div>
            <div class="card-body-soft">
                @forelse($loan->returns as $return)
                    <div class="mb-2"><div class="cell-secondary">No. Pengembalian</div><div class="cell-primary">{{ $return->return_number }}</div></div>
                    <div class="mb-2"><div class="cell-secondary">Tanggal</div><div class="cell-primary">{{ tanggal($return->return_date, true) }}</div></div>
                    <div class="mb-2"><div class="cell-secondary">Jumlah Buku</div><div class="cell-primary">{{ $return->total_books }}</div></div>
                    <div class="mb-2"><div class="cell-secondary">Total Denda</div><div class="cell-primary">{{ rupiah($return->total_fine) }}</div></div>
                @empty
                    <div class="cell-secondary">Belum ada buku yang dikembalikan.</div>
                @endforelse
            </div>
        </div>
        @if($loan->fine)
        <div class="card-soft">
            <div class="card-head"><h3 class="card-title">Denda</h3></div>
            <div class="card-body-soft">
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Nomor</span><strong>{{ $loan->fine->fine_number }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Total</span><strong>{{ rupiah($loan->fine->amount) }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Dibayar</span><strong class="text-success">{{ rupiah($loan->fine->paid_amount) }}</strong></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-muted">Sisa</span><strong class="text-danger">{{ rupiah($loan->fine->remaining) }}</strong></div>
                {!! status_badge($loan->fine->status) !!}
                @if(auth()->user()->isPetugas() && $loan->fine->remaining > 0)
                    <a href="{{ route('fines.pay', $loan->fine) }}" class="btn btn-teal w-100 mt-3">{!! icon('banknote',16) !!} Catat Pembayaran</a>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

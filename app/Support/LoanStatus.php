<?php

namespace App\Support;

use App\Models\Fine;
use App\Models\Loan;
use App\Models\LoanDetail;
use Illuminate\Support\Facades\DB;

class LoanStatus
{
    /**
     * Sinkronkan status berdasarkan tanggal nyata. Dipanggil pada setiap request
     * sehingga status tetap benar walaupun aplikasi baru dinyalakan kembali.
     */
    public static function refresh(): void
    {
        $today = now()->toDateString();

        LoanDetail::where('status', 'dipinjam')
            ->whereHas('loan', fn ($q) => $q->where('due_date', '<', $today))
            ->update(['status' => 'terlambat']);

        Loan::whereIn('status', ['dipinjam', 'terlambat'])
            ->where('due_date', '<', $today)
            ->update(['status' => 'terlambat']);

        Loan::whereIn('status', ['dipinjam', 'terlambat'])
            ->whereDoesntHave('details', fn ($q) => $q->whereNull('returned_at'))
            ->whereHas('details')
            ->update(['status' => 'selesai']);

        Fine::where('amount', '<=', 0)
            ->where('status', 'belum_dibayar')
            ->update(['status' => 'lunas']);

        foreach (Fine::whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])->get() as $fine) {
            $paid = (float) $fine->paid_amount;
            $amount = (float) $fine->amount;
            $expected = $paid <= 0 ? 'belum_dibayar' : ($paid >= $amount ? 'lunas' : 'dibayar_sebagian');
            if ($fine->status !== $expected) {
                $fine->update(['status' => $expected]);
            }
        }

        DB::table('books')->whereNotIn('status', ['tidak_aktif'])
            ->update([
                'status' => DB::raw("CASE
                    WHEN available <= 0 THEN 'stok_habis'
                    WHEN available < stock THEN 'dipinjam'
                    ELSE 'tersedia' END"),
            ]);
    }
}

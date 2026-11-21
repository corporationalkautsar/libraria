<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Models\FinePayment;
use App\Support\ActivityLogger;
use App\Support\DateFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FineController extends Controller
{
    public function index(Request $request)
    {
        $query = Fine::with(['member', 'loan', 'payments']);

        if ($q = $request->get('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('fine_number', 'like', "%{$q}%")
                    ->orWhereHas('member', fn ($m) => $m->where('full_name', 'like', "%{$q}%"));
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        DateFilter::apply($query, 'created_at', $request);

        $sort = in_array($request->get('sort'), ['fine_number', 'amount', 'paid_amount', 'status', 'created_at']) ? $request->get('sort') : 'created_at';
        $query->orderBy($sort, $request->get('direction') === 'asc' ? 'asc' : 'desc');

        $all = Fine::query();
        DateFilter::apply($all, 'created_at', $request);

        return view('fines.index', [
            'fines' => $query->paginate(15)->withQueryString(),
            'totals' => [
                'total' => (float) (clone $all)->sum('amount'),
                'paid' => (float) (clone $all)->sum('paid_amount'),
                'unpaid' => (float) (clone $all)->sum(DB::raw('amount - paid_amount')),
            ],
        ]);
    }

    public function history(Request $request)
    {
        $query = FinePayment::with(['fine.member', 'user']);

        if ($q = $request->get('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('receipt_number', 'like', "%{$q}%")
                    ->orWhereHas('fine.member', fn ($m) => $m->where('full_name', 'like', "%{$q}%"));
            });
        }
        if ($petugas = $request->get('petugas')) {
            $query->where('user_id', $petugas);
        }
        DateFilter::apply($query, 'payment_date', $request);

        return view('fines.history', [
            'payments' => $query->latest('payment_date')->paginate(15)->withQueryString(),
            'staff' => \App\Models\User::where('role', 'petugas')->orderBy('name')->get(),
            'total' => (float) (clone $query)->sum('amount'),
        ]);
    }

    public function show(Fine $fine)
    {
        $fine->load(['member', 'loan.user', 'payments.user']);
        return view('fines.show', compact('fine'));
    }

    public function pay(Fine $fine)
    {
        abort_unless(auth()->user()->isPetugas(), 403);
        if ($fine->remaining <= 0) {
            return redirect()->route('fines.show', $fine)->with('error', 'Denda ini sudah lunas.');
        }
        return view('fines.pay', compact('fine'));
    }

    public function payment(Request $request, Fine $fine)
    {
        abort_unless(auth()->user()->isPetugas(), 403);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1', 'max:'.$fine->remaining],
            'method' => ['required', 'in:tunai,transfer,other'],
            'note' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
        ], [
            'amount.max' => 'Jumlah pembayaran tidak boleh melebihi sisa denda ('.rupiah($fine->remaining).').',
            'amount.min' => 'Jumlah pembayaran minimal Rp1.',
        ]);

        $payment = DB::transaction(function () use ($fine, $data) {
            $payment = FinePayment::create([
                'fine_id' => $fine->id,
                'user_id' => auth()->id(),
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'].' '.now()->format('H:i:s'),
                'method' => $data['method'],
                'note' => $data['note'] ?? null,
            ]);

            $fine->increment('paid_amount', $data['amount']);
            $fine->refresh();
            $fine->refreshStatus();

            return $payment;
        });

        ActivityLogger::log('Pembayaran Denda', 'Petugas '.auth()->user()->name.' mencatat pembayaran denda '.$fine->fine_number.' sebesar '.rupiah($payment->amount).'.');

        return redirect()->route('fines.receipt', [$fine, $payment])
            ->with('success', 'Pembayaran berhasil dicatat. Status denda: '.ucfirst(str_replace('_', ' ', $fine->status)).'.');
    }

    public function receipt(Fine $fine, FinePayment $payment)
    {
        abort_unless($payment->fine_id === $fine->id, 404);
        $fine->load(['member', 'loan']);
        $payment->load('user');

        return view('fines.receipt', compact('fine', 'payment'));
    }
}

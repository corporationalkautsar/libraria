<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FinePayment;
use App\Models\Loan;
use App\Models\BookReturn;
use App\Models\User;
use App\Support\DateFilter;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $query = User::query()
            ->select('users.*')
            ->where('users.role', 'petugas')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            });

        // Filter tanggal diterapkan pada masing-masing aktivitas, bukan pada tanggal daftar akun.
        $loanCount = Loan::query()->selectRaw('count(*)')->whereColumn('loans.user_id', 'users.id');
        DateFilter::apply($loanCount, 'loan_date', $request);
        $query->selectSub($loanCount, 'loans_count');

        $returnCount = BookReturn::query()->selectRaw('count(*)')->whereColumn('returns.user_id', 'users.id');
        DateFilter::apply($returnCount, 'return_date', $request);
        $query->selectSub($returnCount, 'returns_count');

        $paymentCount = FinePayment::query()->selectRaw('count(*)')->whereColumn('fine_payments.user_id', 'users.id');
        DateFilter::apply($paymentCount, 'payment_date', $request);
        $query->selectSub($paymentCount, 'fine_payments_count');

        $memberCount = ActivityLog::query()
            ->selectRaw('count(*)')
            ->whereColumn('activity_logs.user_id', 'users.id')
            ->where('activity', 'Penambahan Anggota');
        DateFilter::apply($memberCount, 'created_at', $request);
        $query->selectSub($memberCount, 'registered_members');

        $activityCount = ActivityLog::query()->selectRaw('count(*)')->whereColumn('activity_logs.user_id', 'users.id');
        DateFilter::apply($activityCount, 'created_at', $request);
        $query->selectSub($activityCount, 'activity_logs_count');

        $sortable = [
            'name' => 'users.name',
            'email' => 'users.email',
            'loans' => 'loans_count',
            'returns' => 'returns_count',
            'members' => 'registered_members',
            'payments' => 'fine_payments_count',
            'activity' => 'activity_logs_count',
            'total' => '(loans_count + returns_count + fine_payments_count + registered_members)',
        ];
        $requestedSort = (string) $request->get('sort', '');
        $sort = array_key_exists($requestedSort, $sortable) ? $requestedSort : 'name';
        $direction = $requestedSort !== ''
            ? ($request->get('direction') === 'asc' ? 'asc' : 'desc')
            : 'asc';
        $query->orderByRaw($sortable[$sort].' '.$direction);

        $staff = $query->paginate(15)->withQueryString();
        foreach ($staff as $row) {
            $row->total_activity = (int) $row->loans_count + (int) $row->returns_count
                + (int) $row->registered_members + (int) $row->fine_payments_count;
        }

        return view('monitoring.index', compact('staff', 'search'));
    }

    public function show(Request $request, string $user)
    {
        $petugas = User::where('role', 'petugas')->whereKey($user)->firstOrFail();

        $loans = Loan::query()->where('user_id', $petugas->id)->with('member');
        DateFilter::apply($loans, 'loan_date', $request);
        $recentLoans = (clone $loans)->latest('loan_date')->take(8)->get();

        $returns = BookReturn::query()->where('user_id', $petugas->id)->with('member');
        DateFilter::apply($returns, 'return_date', $request);
        $recentReturns = (clone $returns)->latest('return_date')->take(8)->get();

        $payments = FinePayment::query()->where('user_id', $petugas->id)->with('fine.member');
        DateFilter::apply($payments, 'payment_date', $request);
        $recentPayments = (clone $payments)->latest('payment_date')->take(8)->get();

        $activities = ActivityLog::query()->where('user_id', $petugas->id);
        DateFilter::apply($activities, 'created_at', $request);
        $recentActivities = (clone $activities)->latest('created_at')->take(8)->get();

        $stats = [
            'loans' => $loans->count(),
            'returns' => $returns->count(),
            'payments' => $payments->count(),
            'members' => (clone $activities)->where('activity', 'Penambahan Anggota')->count(),
            'activity' => $activities->count(),
        ];
        $stats['total'] = $stats['loans'] + $stats['returns'] + $stats['members'] + $stats['payments'];

        return view('monitoring.show', compact(
            'petugas', 'stats', 'recentLoans', 'recentReturns', 'recentPayments', 'recentActivities'
        ));
    }
}

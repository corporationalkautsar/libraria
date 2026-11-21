<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Book;
use App\Models\BookReturn;
use App\Models\Category;
use App\Models\Fine;
use App\Models\FinePayment;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return auth()->user()->isAdmin() ? $this->admin() : $this->petugas();
    }

    private function admin()
    {
        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));
        $labels = $months->map(fn ($m) => $m->translatedFormat('M Y'))->all();

        $loansPerMonth = Loan::where('loan_date', '>=', $months->first()->startOfMonth())
            ->selectRaw('DATE_FORMAT(loan_date, "%Y-%m") as period, COUNT(*) as total')
            ->groupBy('period')->pluck('total', 'period')->all();
        $returnsPerMonth = BookReturn::where('return_date', '>=', $months->first()->startOfMonth())
            ->selectRaw('DATE_FORMAT(return_date, "%Y-%m") as period, COUNT(*) as total')
            ->groupBy('period')->pluck('total', 'period')->all();

        $monthlyLoans = $months->map(fn ($m) => (int) ($loansPerMonth[$m->format('Y-m')] ?? 0))->all();
        $monthlyReturns = $months->map(fn ($m) => (int) ($returnsPerMonth[$m->format('Y-m')] ?? 0))->all();

        $categoryStats = Category::withCount(['books as total_books'])
            ->get()->sortByDesc('total_books')->take(6);

        $categoryPopularity = DB::table('loan_details')
            ->join('books', 'books.id', '=', 'loan_details.book_id')
            ->join('categories', 'categories.id', '=', 'books.category_id')
            ->select('categories.name', DB::raw('COUNT(*) as total'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')->limit(6)->get();

        $topBooks = Book::withCount('loanDetails')
            ->orderByDesc('loan_details_count')->take(5)->get();

        $staffPerformance = User::where('role', 'petugas')
            ->withCount(['loans', 'returns', 'finePayments'])
            ->get();
        // Anggota tidak memiliki relasi user; dihitung dari log aktivitas
        foreach ($staffPerformance as $staff) {
            $staff->registered_members = ActivityLog::where('user_id', $staff->id)
                ->where('activity', 'Penambahan Anggota')->count();
        }

        $stats = [
            'total_books' => (int) Book::sum('stock'),
            'total_titles' => Book::count(),
            'total_members' => Member::count(),
            'total_categories' => Category::count(),
            'total_loans' => Loan::count(),
            'borrowed' => (int) LoanDetail::whereNull('returned_at')->count(),
            'overdue' => Loan::where('status', 'terlambat')->count(),
            'unpaid' => (float) Fine::whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])->sum(DB::raw('amount - paid_amount')),
        ];

        return view('dashboard.admin', [
            'stats' => $stats,
            'chartLabels' => $labels,
            'monthlyLoans' => $monthlyLoans,
            'monthlyReturns' => $monthlyReturns,
            'categoryPopularity' => $categoryPopularity,
            'categoryStats' => $categoryStats,
            'topBooks' => $topBooks,
            'staffPerformance' => $staffPerformance,
            'recentActivities' => ActivityLog::with('user')->latest('created_at')->take(6)->get(),
            'recentLoans' => Loan::with(['member', 'user'])->latest('loan_date')->take(6)->get(),
            'recentPayments' => FinePayment::with(['fine.member', 'user'])->latest('payment_date')->take(5)->get(),
            'fineSummary' => [
                'total' => (float) Fine::sum('amount'),
                'paid' => (float) FinePayment::sum('amount'),
                'unpaid' => (float) Fine::whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])->sum(DB::raw('amount - paid_amount')),
            ],
        ]);
    }

    private function petugas()
    {
        $dueSoon = Loan::with('member')
            ->whereIn('status', ['dipinjam', 'terlambat'])
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(3)->toDateString()])
            ->orderBy('due_date')->take(6)->get();

        $stats = [
            'total_members' => Member::count(),
            'loans_today' => Loan::whereDate('loan_date', today())->count(),
            'returns_today' => BookReturn::whereDate('return_date', today())->count(),
            'borrowed' => (int) LoanDetail::whereNull('returned_at')->count(),
            'overdue' => Loan::where('status', 'terlambat')->count(),
            'unpaid' => (float) Fine::whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])->sum(DB::raw('amount - paid_amount')),
        ];

        return view('dashboard.petugas', [
            'stats' => $stats,
            'dueSoon' => $dueSoon,
            'recentLoans' => Loan::with(['member', 'user'])->latest('loan_date')->take(8)->get(),
            'recentReturns' => BookReturn::with(['member', 'user'])->latest('return_date')->take(5)->get(),
            'recentActivities' => ActivityLog::where('user_id', auth()->id())->latest('created_at')->take(5)->get(),
        ]);
    }
}

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
use App\Models\Publisher;
use App\Models\User;
use App\Support\DateFilter;
use App\Support\Settings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private const LOAN_STATUSES = ['dipinjam', 'terlambat', 'selesai', 'dibatalkan'];

    private const FINE_STATUSES = ['belum_dibayar', 'dibayar_sebagian', 'lunas'];

    public function index(Request $request)
    {
        return view('reports.index', $this->buildData($request));
    }

    public function print(Request $request)
    {
        return view('reports.print', $this->buildData($request));
    }

    public function pdf(Request $request)
    {
        $data = $this->buildData($request);

        return Pdf::loadView('reports.pdf', $data)
            ->setPaper('a4', 'landscape')
            ->download('laporan-perpustakaan-'.date('Ymd_His').'.pdf');
    }

    public function excel(Request $request)
    {
        $data = $this->buildData($request);
        $rows = $this->excelRows($data);

        $lines = array_map(function (array $row): string {
            return implode(';', array_map([$this, 'csvCell'], $row));
        }, $rows);

        // BOM + CRLF + semicolon makes the file open correctly in Indonesian Excel.
        $csv = "\xEF\xBB\xBF".implode("\r\n", $lines)."\r\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="laporan-perpustakaan-'.date('Ymd_His').'.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    private function buildData(Request $request): array
    {
        $dateFilter = (string) $request->get('date_filter', '');
        $dateFrom = (string) $request->get('date_from', '');
        $dateTo = (string) $request->get('date_to', '');
        $petugas = $request->get('petugas');
        $kategori = $request->get('kategori');
        $status = $request->get('status');

        $petugasList = User::where('role', 'petugas')->orderBy('name')->get(['id', 'name']);
        $kategoriList = Category::orderBy('name')->get(['id', 'name']);

        // ------------------------------------------------------------------
        // Reusable filters. Date filters only touch transaction dates.
        // ------------------------------------------------------------------
        $loanScope = function (Builder $q) use ($request, $petugas, $kategori, $status): void {
            if ($petugas) {
                $q->where('loans.user_id', $petugas);
            }
            if ($kategori) {
                $q->whereHas('details.book', fn (Builder $b) => $b->where('books.category_id', $kategori));
            }
            DateFilter::apply($q, 'loans.loan_date', $request);

            if (in_array($status, self::LOAN_STATUSES, true)) {
                $q->where('loans.status', $status);
            } elseif (in_array($status, self::FINE_STATUSES, true)) {
                $q->whereHas('fine', fn (Builder $f) => $f->where('fines.status', $status));
            }
        };

        $returnScope = function (Builder $q) use ($request, $petugas, $kategori, $status): void {
            DateFilter::apply($q, 'returns.return_date', $request);
            if ($petugas) {
                $q->where('returns.user_id', $petugas);
            }
            if ($kategori) {
                $q->whereHas('details.book', fn (Builder $b) => $b->where('books.category_id', $kategori));
            }
            if (in_array($status, self::LOAN_STATUSES, true)) {
                $q->whereHas('loan', fn (Builder $l) => $l->where('loans.status', $status));
            } elseif (in_array($status, self::FINE_STATUSES, true)) {
                $q->whereHas('loan.fine', fn (Builder $f) => $f->where('fines.status', $status));
            }
        };

        $fineScope = function (Builder $q) use ($request, $petugas, $kategori, $status): void {
            DateFilter::apply($q, 'fines.created_at', $request);
            if ($petugas) {
                $q->whereHas('loan', fn (Builder $l) => $l->where('loans.user_id', $petugas));
            }
            if ($kategori) {
                $q->whereHas('loan.details.book', fn (Builder $b) => $b->where('books.category_id', $kategori));
            }
            if (in_array($status, self::LOAN_STATUSES, true)) {
                $q->whereHas('loan', fn (Builder $l) => $l->where('loans.status', $status));
            } elseif (in_array($status, self::FINE_STATUSES, true)) {
                $q->where('fines.status', $status);
            }
        };

        $paymentScope = function (Builder $q) use ($request, $petugas, $kategori, $status): void {
            DateFilter::apply($q, 'fine_payments.payment_date', $request);
            if ($petugas) {
                $q->where('fine_payments.user_id', $petugas);
            }
            if ($kategori) {
                $q->whereHas('fine.loan.details.book', fn (Builder $b) => $b->where('books.category_id', $kategori));
            }
            if (in_array($status, self::LOAN_STATUSES, true)) {
                $q->whereHas('fine.loan', fn (Builder $l) => $l->where('loans.status', $status));
            } elseif (in_array($status, self::FINE_STATUSES, true)) {
                $q->whereHas('fine', fn (Builder $f) => $f->where('fines.status', $status));
            }
        };

        $activityScope = function (Builder $q) use ($request, $petugas): void {
            $q->where('activity_logs.activity', 'Penambahan Anggota');
            DateFilter::apply($q, 'activity_logs.created_at', $request);
            if ($petugas) {
                $q->where('activity_logs.user_id', $petugas);
            }
        };

        // ------------------------------------------------------------------
        // Buku
        // ------------------------------------------------------------------
        $bookQuery = Book::query();
        if ($kategori) {
            $bookQuery->where('books.category_id', $kategori);
        }

        $totalJudul = (clone $bookQuery)->count();
        $totalEksemplar = (int) (clone $bookQuery)->sum('books.stock');
        $totalTersedia = (int) (clone $bookQuery)->sum('books.available');
        $stokHabis = (clone $bookQuery)->where('books.available', 0)->count();
        $tidakAktif = (clone $bookQuery)->where('books.status', 'tidak_aktif')->count();

        $perKategori = Category::query()
            ->when($kategori, fn (Builder $q) => $q->where('categories.id', $kategori))
            ->withCount('books')
            ->withSum('books as total_stock', 'stock')
            ->withSum('books as total_available', 'available')
            ->get()
            ->sortByDesc('books_count')
            ->values();

        $perPenerbit = Book::query()
            ->join('publishers', 'publishers.id', '=', 'books.publisher_id')
            ->when($kategori, fn (Builder $q) => $q->where('books.category_id', $kategori))
            ->select(
                'publishers.id',
                'publishers.name',
                DB::raw('COUNT(*) as total_judul'),
                DB::raw('SUM(books.stock) as total_eksemplar'),
                DB::raw('SUM(books.available) as total_tersedia')
            )
            ->groupBy('publishers.id', 'publishers.name')
            ->orderByDesc('total_judul')
            ->get();

        $bukuPopuler = Book::query()
            ->withCount('loanDetails')
            ->when($kategori, fn (Builder $q) => $q->where('books.category_id', $kategori))
            ->orderByDesc('loan_details_count')
            ->limit(10)
            ->get();

        // ------------------------------------------------------------------
        // Peminjaman
        // ------------------------------------------------------------------
        $loansQuery = Loan::query();
        $loanScope($loansQuery);

        $transactions = (clone $loansQuery)
            ->with([
                'member:id,full_name,member_code',
                'user:id,name',
            ])
            ->orderByDesc('loan_date')
            ->orderByDesc('id')
            ->get();
        $totalPeminjaman = (clone $loansQuery)->count();

        $loanPerHari = (clone $loansQuery)
            ->selectRaw('DATE(loans.loan_date) as periode, COUNT(*) as total')
            ->groupByRaw('DATE(loans.loan_date)')
            ->orderByRaw('DATE(loans.loan_date) DESC')
            ->get();

        $loanPerBulan = (clone $loansQuery)
            ->selectRaw('DATE_FORMAT(loans.loan_date, "%Y-%m") as periode, COUNT(*) as total')
            ->groupByRaw('DATE_FORMAT(loans.loan_date, "%Y-%m")')
            ->orderByDesc('periode')
            ->get();

        $loanPerTahun = (clone $loansQuery)
            ->selectRaw('YEAR(loans.loan_date) as periode, COUNT(*) as total')
            ->groupByRaw('YEAR(loans.loan_date)')
            ->orderByDesc('periode')
            ->get();

        $topBooks = LoanDetail::query()
            ->join('books', 'books.id', '=', 'loan_details.book_id')
            ->whereHas('loan', $loanScope)
            ->when($kategori, fn (Builder $q) => $q->where('books.category_id', $kategori))
            ->select('books.id', 'books.title', DB::raw('COUNT(*) as total'))
            ->groupBy('books.id', 'books.title')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ------------------------------------------------------------------
        // Pengembalian (tepat waktu vs terlambat dari due_date vs returned_at)
        // ------------------------------------------------------------------
        $returnsQuery = BookReturn::query();
        $returnScope($returnsQuery);

        $returns = (clone $returnsQuery)
            ->with([
                'member:id,full_name,member_code',
                'user:id,name',
                'loan:id,loan_number,due_date',
                'loan.details:id,loan_id,returned_at',
            ])
            ->orderByDesc('return_date')
            ->get();
        $totalPengembalian = (clone $returnsQuery)->count();

        $terlambat = (clone $returnsQuery)
            ->whereHas('loan.details', function (Builder $d): void {
                $d->whereNotNull('loan_details.returned_at')
                    ->whereRaw('DATE(loan_details.returned_at) > loans.due_date');
            })
            ->count();

        $tepatWaktu = (clone $returnsQuery)
            ->whereHas('loan.details', function (Builder $d): void {
                $d->whereNotNull('loan_details.returned_at')
                    ->whereRaw('DATE(loan_details.returned_at) <= loans.due_date');
            })
            ->whereDoesntHave('loan.details', function (Builder $d): void {
                $d->whereNotNull('loan_details.returned_at')
                    ->whereRaw('DATE(loan_details.returned_at) > loans.due_date');
            })
            ->count();

        $returns->each(function (BookReturn $return): void {
            $due = $return->loan?->due_date;
            $latest = $return->loan?->details
                ?->filter(fn ($detail) => $detail->returned_at !== null)
                ->sortByDesc('returned_at')
                ->first();

            if (! $latest || ! $due) {
                $return->timing = 'tanpa_data';
                $return->late_days = null;

                return;
            }

            $returnDate = $latest->returned_at->toDateString();
            $dueDate = $due->toDateString();
            $return->timing = $returnDate > $dueDate ? 'terlambat' : 'tepat_waktu';
            $return->late_days = $return->timing === 'terlambat'
                ? max(1, (int) $due->copy()->startOfDay()->diffInDays($latest->returned_at->copy()->startOfDay(), false))
                : 0;
        });

        // ------------------------------------------------------------------
        // Denda & pembayaran
        // ------------------------------------------------------------------
        $finesQuery = Fine::query();
        $fineScope($finesQuery);

        $fines = (clone $finesQuery)
            ->with([
                'member:id,full_name,member_code',
                'loan:id,loan_number',
            ])
            ->orderByDesc('id')
            ->get();

        $totalDenda = (float) (clone $finesQuery)->sum('fines.amount');
        $dendaTerbayar = (float) (clone $finesQuery)->sum('fines.paid_amount');
        $dendaBelumDibayar = max(0.0, $totalDenda - $dendaTerbayar);
        $fineCounts = (clone $finesQuery)
            ->selectRaw('fines.status as status, COUNT(*) as total')
            ->groupBy('fines.status')
            ->pluck('total', 'status');

        $paymentsQuery = FinePayment::query();
        $paymentScope($paymentsQuery);

        $payments = (clone $paymentsQuery)
            ->with([
                'user:id,name',
                'fine:id,fine_number,member_id',
                'fine.member:id,full_name',
            ])
            ->orderByDesc('payment_date')
            ->get();
        $totalPembayaran = (float) (clone $paymentsQuery)->sum('fine_payments.amount');

        // ------------------------------------------------------------------
        // Anggota
        // ------------------------------------------------------------------
        $totalAnggota = Member::count();
        $perSekolah = Member::query()
            ->select('school', DB::raw('COUNT(*) as total'))
            ->groupBy('school')
            ->orderByDesc('total')
            ->get();
        $perAsal = Member::query()
            ->select('origin', DB::raw('COUNT(*) as total'))
            ->groupBy('origin')
            ->orderByDesc('total')
            ->get();
        $pernahPinjam = Member::whereHas('loans', $loanScope)->count();
        $borrowers = Member::query()
            ->withCount(['loans' => $loanScope])
            ->get()
            ->filter(fn (Member $member) => $member->loans_count > 0)
            ->sortByDesc('loans_count')
            ->take(10)
            ->values();

        // ------------------------------------------------------------------
        // Kinerja petugas (agregat, bukan N+1)
        // ------------------------------------------------------------------
        $loansByUser = (clone $loansQuery)
            ->selectRaw('loans.user_id as user_id, COUNT(*) as total')
            ->groupBy('loans.user_id')
            ->pluck('total', 'user_id');
        $returnsByUser = (clone $returnsQuery)
            ->selectRaw('returns.user_id as user_id, COUNT(*) as total')
            ->groupBy('returns.user_id')
            ->pluck('total', 'user_id');
        $paymentsByUser = (clone $paymentsQuery)
            ->selectRaw('fine_payments.user_id as user_id, COUNT(*) as total')
            ->groupBy('fine_payments.user_id')
            ->pluck('total', 'user_id');

        $activityQuery = ActivityLog::query();
        $activityScope($activityQuery);
        $registeredByUser = (clone $activityQuery)
            ->selectRaw('activity_logs.user_id as user_id, COUNT(*) as total')
            ->groupBy('activity_logs.user_id')
            ->pluck('total', 'user_id');

        $kinerja = $petugasList->map(function (User $user) use (
            $loansByUser,
            $returnsByUser,
            $paymentsByUser,
            $registeredByUser
        ): array {
            $id = (int) $user->id;
            $loans = (int) ($loansByUser[$id] ?? 0);
            $returns = (int) ($returnsByUser[$id] ?? 0);
            $payments = (int) ($paymentsByUser[$id] ?? 0);
            $registered = (int) ($registeredByUser[$id] ?? 0);

            return [
                'id' => $id,
                'name' => $user->name,
                'loans' => $loans,
                'returns' => $returns,
                'registered' => $registered,
                'payments' => $payments,
                'total' => $loans + $returns + $registered + $payments,
            ];
        })->sortByDesc('total')->values();

        // ------------------------------------------------------------------
        // Grafik
        // ------------------------------------------------------------------
        $months = $this->chartMonths($request);
        $loanMonthly = (clone $loansQuery)
            ->selectRaw('DATE_FORMAT(loans.loan_date, "%Y-%m") as periode, COUNT(*) as total')
            ->groupByRaw('DATE_FORMAT(loans.loan_date, "%Y-%m")')
            ->pluck('total', 'periode');
        $returnMonthly = (clone $returnsQuery)
            ->selectRaw('DATE_FORMAT(returns.return_date, "%Y-%m") as periode, COUNT(*) as total')
            ->groupByRaw('DATE_FORMAT(returns.return_date, "%Y-%m")')
            ->pluck('total', 'periode');

        $chartLabels = $months->map(fn (Carbon $m) => $m->translatedFormat('M Y'))->all();
        $chartLoans = $months->map(fn (Carbon $m) => (int) ($loanMonthly[$m->format('Y-m')] ?? 0))->all();
        $chartReturns = $months->map(fn (Carbon $m) => (int) ($returnMonthly[$m->format('Y-m')] ?? 0))->all();

        $chartCategorySource = $perKategori->take(8);
        $chartCategoryLabels = $chartCategorySource->pluck('name')->all();
        $chartCategoryData = $chartCategorySource
            ->map(fn ($category) => (int) ($category->books_count ?? 0))
            ->all();
        $otherCategories = (int) $perKategori->skip(8)->sum('books_count');
        if ($otherCategories > 0) {
            $chartCategoryLabels[] = 'Lainnya';
            $chartCategoryData[] = $otherCategories;
        }

        // ------------------------------------------------------------------
        // Label filter & ringkasan
        // ------------------------------------------------------------------
        $periodLabel = DateFilter::OPTIONS[$dateFilter] ?? 'Semua Tanggal';
        if ($dateFilter === 'custom' && ($dateFrom || $dateTo)) {
            $periodLabel .= ' ('.($dateFrom ?: '...').' s/d '.($dateTo ?: '...').')';
        }
        $filterParts = [$periodLabel];
        if ($petugas) {
            $filterParts[] = 'Petugas: '.($petugasList->firstWhere('id', (int) $petugas)?->name ?? '-');
        }
        if ($kategori) {
            $filterParts[] = 'Kategori: '.($kategoriList->firstWhere('id', (int) $kategori)?->name ?? '-');
        }
        if ($status) {
            $filterParts[] = 'Status: '.ucwords(str_replace('_', ' ', $status));
        }

        $filterValues = [
            'date_filter' => $dateFilter,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'petugas' => (string) $petugas,
            'kategori' => (string) $kategori,
            'status' => (string) $status,
        ];

        $stats = [
            'total_titles' => (int) $totalJudul,
            'total_copies' => $totalEksemplar,
            'available_copies' => $totalTersedia,
            'borrowed_copies' => max(0, $totalEksemplar - $totalTersedia),
            'total_members' => (int) $totalAnggota,
            'total_loans' => (int) $totalPeminjaman,
            'total_returns' => (int) $totalPengembalian,
            'late_returns' => (int) $terlambat,
            'unpaid_fine' => $dendaBelumDibayar,
        ];

        return [
            // Same shape as the middleware-shared $library, so views stay consistent.
            'library' => Settings::all(),
            'generated_at' => now()->translatedFormat('d F Y, H:i'),
            'filters' => [
                'date_filter' => $dateFilter,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'petugas' => (string) $petugas,
                'kategori' => (string) $kategori,
                'status' => (string) $status,
                'label' => implode(' · ', $filterParts),
                'query' => http_build_query(array_filter($filterValues, fn ($value) => $value !== '')),
            ],
            'options' => [
                'dateFilters' => DateFilter::OPTIONS,
                'petugas' => $petugasList,
                'kategori' => $kategoriList,
                'status' => [
                    '' => 'Semua Status',
                    'dipinjam' => 'Dipinjam',
                    'terlambat' => 'Terlambat',
                    'selesai' => 'Selesai',
                    'dibatalkan' => 'Dibatalkan',
                    'belum_dibayar' => 'Denda Belum Dibayar',
                    'dibayar_sebagian' => 'Denda Dibayar Sebagian',
                    'lunas' => 'Denda Lunas',
                ],
            ],
            'stats' => $stats,
            'buku' => [
                'titles' => (int) $totalJudul,
                'copies' => $totalEksemplar,
                'available' => $totalTersedia,
                'borrowed' => max(0, $totalEksemplar - $totalTersedia),
                'out_of_stock' => (int) $stokHabis,
                'inactive' => (int) $tidakAktif,
                'per_category' => $perKategori,
                'per_publisher' => $perPenerbit,
                'popular' => $bukuPopuler,
            ],
            'anggota' => [
                'total' => (int) $totalAnggota,
                'per_school' => $perSekolah,
                'per_origin' => $perAsal,
                'ever_borrowed' => (int) $pernahPinjam,
                'borrowers' => $borrowers,
            ],
            'peminjaman' => [
                'total' => (int) $totalPeminjaman,
                'per_hari' => $loanPerHari,
                'per_bulan' => $loanPerBulan,
                'per_tahun' => $loanPerTahun,
                'transactions' => $transactions,
                'top_books' => $topBooks,
            ],
            'pengembalian' => [
                'total' => (int) $totalPengembalian,
                'on_time' => (int) $tepatWaktu,
                'late' => (int) $terlambat,
                'history' => $returns,
            ],
            'denda' => [
                'total_amount' => $totalDenda,
                'paid_amount' => $dendaTerbayar,
                'unpaid_amount' => $dendaBelumDibayar,
                'total_count' => (int) $fines->count(),
                'paid_count' => (int) ($fineCounts->get('lunas', 0)),
                'partial_count' => (int) ($fineCounts->get('dibayar_sebagian', 0)),
                'unpaid_count' => (int) ($fineCounts->get('belum_dibayar', 0)),
                'payment_total' => $totalPembayaran,
                'fines' => $fines,
                'payments' => $payments,
            ],
            'kinerja' => [
                'petugas' => $kinerja,
                'total_loans' => (int) $loansByUser->sum(),
                'total_returns' => (int) $returnsByUser->sum(),
                'total_registered' => (int) $registeredByUser->sum(),
                'total_payments' => (int) $paymentsByUser->sum(),
                'total_served' => (int) $kinerja->sum('total'),
            ],
            'charts' => [
                'labels' => $chartLabels,
                'loans' => $chartLoans,
                'returns' => $chartReturns,
                'categoryLabels' => $chartCategoryLabels,
                'categoryData' => $chartCategoryData,
            ],
        ];
    }

    private function chartMonths(Request $request): \Illuminate\Support\Collection
    {
        $end = now()->startOfMonth();
        $start = $end->copy()->subMonths(11);

        if ($request->get('date_filter') === 'custom') {
            $from = $request->get('date_from');
            $to = $request->get('date_to');

            if ($from || $to) {
                $end = $to ? Carbon::parse($to)->startOfMonth() : $end;
                $start = $from ? Carbon::parse($from)->startOfMonth() : $end->copy()->subMonths(11);
                if ($end->diffInMonths($start) > 23) {
                    $start = $end->copy()->subMonths(23);
                }
            }
        }

        $months = collect();
        for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
            $months->push($month->copy());
        }

        return $months;
    }

    private function excelRows(array $data): array
    {
        $rows = [];
        $buku = $data['buku'];
        $anggota = $data['anggota'];
        $peminjaman = $data['peminjaman'];
        $pengembalian = $data['pengembalian'];
        $denda = $data['denda'];
        $kinerja = $data['kinerja'];

        $rows[] = ['LAPORAN PERPUSTAKAAN'];
        $rows[] = ['Nama Perpustakaan', $data['library']['library_name']];
        $rows[] = ['Alamat', $data['library']['library_address']];
        $rows[] = ['Periode', $data['filters']['label']];
        $rows[] = ['Dicetak', $data['generated_at']];
        $rows[] = [];

        $rows[] = ['RINGKASAN'];
        $rows[] = ['Total Judul Buku', $data['stats']['total_titles']];
        $rows[] = ['Total Eksemplar', $data['stats']['total_copies']];
        $rows[] = ['Eksemplar Tersedia', $data['stats']['available_copies']];
        $rows[] = ['Eksemplar Dipinjam', $data['stats']['borrowed_copies']];
        $rows[] = ['Total Anggota', $data['stats']['total_members']];
        $rows[] = ['Total Peminjaman', $data['stats']['total_loans']];
        $rows[] = ['Total Pengembalian', $data['stats']['total_returns']];
        $rows[] = ['Pengembalian Terlambat', $data['stats']['late_returns']];
        $rows[] = ['Denda Belum Dibayar', rupiah($data['stats']['unpaid_fine'])];
        $rows[] = [];

        $rows[] = ['BUKU'];
        $rows[] = ['Total Judul', $buku['titles'], 'Total Eksemplar', $buku['copies'], 'Tersedia', $buku['available']];
        $rows[] = ['Stok Habis', $buku['out_of_stock'], 'Tidak Aktif', $buku['inactive']];
        $rows[] = [];
        $rows[] = ['Buku per Kategori'];
        $rows[] = ['Kategori', 'Judul', 'Eksemplar', 'Tersedia'];
        foreach ($buku['per_category'] as $item) {
            $rows[] = [$item->name, (int) $item->books_count, (int) $item->total_stock, (int) $item->total_available];
        }
        $rows[] = [];
        $rows[] = ['Buku per Penerbit'];
        $rows[] = ['Penerbit', 'Judul', 'Eksemplar', 'Tersedia'];
        foreach ($buku['per_publisher'] as $item) {
            $rows[] = [$item->name, (int) $item->total_judul, (int) $item->total_eksemplar, (int) $item->total_tersedia];
        }
        $rows[] = [];
        $rows[] = ['Buku Paling Sering Dipinjam (semua waktu)'];
        $rows[] = ['Judul', 'Jumlah Peminjaman'];
        foreach ($buku['popular'] as $item) {
            $rows[] = [$item->title, (int) $item->loan_details_count];
        }
        $rows[] = [];

        $rows[] = ['ANGGOTA'];
        $rows[] = ['Total Anggota', $anggota['total'], 'Pernah Pinjam', $anggota['ever_borrowed']];
        $rows[] = [];
        $rows[] = ['Anggota per Sekolah'];
        $rows[] = ['Sekolah', 'Jumlah'];
        foreach ($anggota['per_school'] as $item) {
            $rows[] = [$item->school, (int) $item->total];
        }
        $rows[] = [];
        $rows[] = ['Anggota per Asal'];
        $rows[] = ['Asal', 'Jumlah'];
        foreach ($anggota['per_origin'] as $item) {
            $rows[] = [$item->origin, (int) $item->total];
        }
        $rows[] = [];
        $rows[] = ['Anggota Paling Sering Meminjam'];
        $rows[] = ['Kode', 'Nama', 'Jumlah Peminjaman'];
        foreach ($anggota['borrowers'] as $item) {
            $rows[] = [$item->member_code, $item->full_name, (int) $item->loans_count];
        }
        $rows[] = [];

        $rows[] = ['PEMINJAMAN'];
        $rows[] = ['Total Transaksi', $peminjaman['total']];
        $rows[] = [];
        $rows[] = ['Peminjaman per Hari'];
        $rows[] = ['Tanggal', 'Jumlah'];
        foreach ($peminjaman['per_hari'] as $item) {
            $rows[] = [$item->periode, (int) $item->total];
        }
        $rows[] = [];
        $rows[] = ['Peminjaman per Bulan'];
        $rows[] = ['Periode', 'Jumlah'];
        foreach ($peminjaman['per_bulan'] as $item) {
            $rows[] = [$item->periode, (int) $item->total];
        }
        $rows[] = [];
        $rows[] = ['Peminjaman per Tahun'];
        $rows[] = ['Tahun', 'Jumlah'];
        foreach ($peminjaman['per_tahun'] as $item) {
            $rows[] = [$item->periode, (int) $item->total];
        }
        $rows[] = [];
        $rows[] = ['Daftar Transaksi Peminjaman'];
        $rows[] = ['No. Transaksi', 'Anggota', 'Petugas', 'Tanggal Pinjam', 'Jatuh Tempo', 'Status'];
        foreach ($peminjaman['transactions'] as $item) {
            $rows[] = [
                $item->loan_number,
                $item->member?->full_name,
                $item->user?->name,
                tanggal($item->loan_date),
                tanggal($item->due_date),
                $item->status_label,
            ];
        }
        $rows[] = [];
        $rows[] = ['Buku Paling Sering Dipinjam (periode filter)'];
        $rows[] = ['Judul', 'Jumlah'];
        foreach ($peminjaman['top_books'] as $item) {
            $rows[] = [$item->title, (int) $item->total];
        }
        $rows[] = [];

        $rows[] = ['PENGEMBALIAN'];
        $rows[] = ['Total', $pengembalian['total'], 'Tepat Waktu', $pengembalian['on_time'], 'Terlambat', $pengembalian['late']];
        $rows[] = [];
        $rows[] = ['Riwayat Pengembalian'];
        $rows[] = ['No. Pengembalian', 'Anggota', 'Tanggal Kembali', 'Jatuh Tempo', 'Keterangan', 'Petugas', 'Denda'];
        foreach ($pengembalian['history'] as $item) {
            $keterangan = $item->timing === 'terlambat'
                ? 'Terlambat '.$item->late_days.' hari'
                : ($item->timing === 'tepat_waktu' ? 'Tepat waktu' : 'Tanpa data');
            $rows[] = [
                $item->return_number,
                $item->member?->full_name,
                tanggal($item->return_date, true),
                tanggal($item->loan?->due_date),
                $keterangan,
                $item->user?->name,
                rupiah($item->total_fine),
            ];
        }
        $rows[] = [];

        $rows[] = ['DENDA'];
        $rows[] = ['Total Denda', rupiah($denda['total_amount']), 'Terbayar', rupiah($denda['paid_amount']), 'Belum Dibayar', rupiah($denda['unpaid_amount'])];
        $rows[] = ['Jumlah Denda', $denda['total_count'], 'Lunas', $denda['paid_count'], 'Sebagian', $denda['partial_count'], 'Belum Dibayar', $denda['unpaid_count']];
        $rows[] = ['Total Pembayaran', rupiah($denda['payment_total'])];
        $rows[] = [];
        $rows[] = ['Daftar Denda'];
        $rows[] = ['No. Denda', 'Anggota', 'No. Pinjaman', 'Jumlah', 'Dibayar', 'Sisa', 'Status'];
        foreach ($denda['fines'] as $item) {
            $rows[] = [
                $item->fine_number,
                $item->member?->full_name,
                $item->loan?->loan_number,
                rupiah($item->amount),
                rupiah($item->paid_amount),
                rupiah($item->remaining),
                ucwords(str_replace('_', ' ', $item->status)),
            ];
        }
        $rows[] = [];
        $rows[] = ['Riwayat Pembayaran Denda'];
        $rows[] = ['No. Kwitansi', 'Anggota', 'Petugas', 'Tanggal', 'Jumlah', 'Metode'];
        foreach ($denda['payments'] as $item) {
            $rows[] = [
                $item->receipt_number,
                $item->fine?->member?->full_name,
                $item->user?->name,
                tanggal($item->payment_date, true),
                rupiah($item->amount),
                $item->method,
            ];
        }
        $rows[] = [];

        $rows[] = ['KINERJA PETUGAS'];
        $rows[] = ['Nama', 'Peminjaman', 'Pengembalian', 'Anggota Didaftarkan', 'Pembayaran', 'Total Dilayani'];
        foreach ($kinerja['petugas'] as $item) {
            $rows[] = [$item['name'], $item['loans'], $item['returns'], $item['registered'], $item['payments'], $item['total']];
        }
        $rows[] = ['TOTAL', $kinerja['total_loans'], $kinerja['total_returns'], $kinerja['total_registered'], $kinerja['total_payments'], $kinerja['total_served']];

        return $rows;
    }

    private function csvCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $text = (string) $value;

        // Avoid spreadsheet formula injection from user-entered data.
        if (preg_match('/^[=+\-@]/', $text)) {
            $text = "'".$text;
        }

        return '"'.str_replace('"', '""', $text).'"';
    }
}

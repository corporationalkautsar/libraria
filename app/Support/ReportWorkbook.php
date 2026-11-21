<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Drawing;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ReportWorkbook
{
    private const ESPRESSO = '4A2E1B';
    private const COFFEE = '6F4E37';
    private const CARAMEL = 'C68B59';
    private const LATTE = 'EEDFCC';
    private const CREAM = 'FBF5EE';
    private const PAPER = 'FFFFFF';
    private const INK = '2B1B12';
    private const MUTED = '7A6A5F';
    private const GREEN = '1F7A5C';
    private const RED = 'B63B35';
    private const AMBER = 'B77A18';
    private const BLUE = '356C93';

    private const FORMAT_INT = '#,##0';
    private const FORMAT_MONEY = '"Rp "#,##0';

    public static function build(array $data): string
    {
        $spreadsheet = new Spreadsheet();

        $spreadsheet->getProperties()
            ->setCreator('Libraria')
            ->setCompany($data['library']['library_name'] ?? 'Libraria')
            ->setTitle('Laporan Perpustakaan - '.($data['library']['library_name'] ?? 'Libraria'))
            ->setSubject('Laporan komprehensif kondisi perpustakaan')
            ->setDescription('Dokumen ini dihasilkan otomatis oleh Sistem Informasi Manajemen Perpustakaan Libraria.');

        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('Ringkasan');
        self::prepareSheet($summary, 8, self::ESPRESSO);
        self::writeSummary($summary, $data);

        $books = $spreadsheet->createSheet();
        $books->setTitle('Buku');
        self::prepareSheet($books, 6, self::CARAMEL);
        self::writeBooks($books, $data);

        $members = $spreadsheet->createSheet();
        $members->setTitle('Anggota');
        self::prepareSheet($members, 6, self::BLUE);
        self::writeMembers($members, $data);

        $loans = $spreadsheet->createSheet();
        $loans->setTitle('Peminjaman');
        self::prepareSheet($loans, 7, self::GREEN);
        self::writeLoans($loans, $data);

        $returns = $spreadsheet->createSheet();
        $returns->setTitle('Pengembalian');
        self::prepareSheet($returns, 8, self::AMBER);
        self::writeReturns($returns, $data);

        $fines = $spreadsheet->createSheet();
        $fines->setTitle('Denda');
        self::prepareSheet($fines, 8, self::RED);
        self::writeFines($fines, $data);

        $performance = $spreadsheet->createSheet();
        $performance->setTitle('Kinerja');
        self::prepareSheet($performance, 7, self::COFFEE);
        self::writePerformance($performance, $data);

        $charts = $spreadsheet->createSheet();
        $charts->setTitle('Grafik');
        self::prepareSheet($charts, 20, self::CARAMEL);
        self::writeCharts($charts, $data);

        $spreadsheet->setActiveSheetIndex(0);

        ob_start();
        $writer = new Xlsx($spreadsheet);
        $writer->setIncludeCharts(true);
        $writer->save('php://output');

        return (string) ob_get_clean();
    }

    private static function prepareSheet(Worksheet $sheet, int $lastCol, string $tabColor): void
    {
        $sheet->setShowGridlines(false);
        $sheet->getTabColor()->setRGB($tabColor);
        $sheet->getSheetView()->setZoomScale(90);

        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $sheet->getPageMargins()
            ->setLeft(0.25)
            ->setRight(0.25)
            ->setTop(0.4)
            ->setBottom(0.4)
            ->setHeader(0.15)
            ->setFooter(0.15);

        $sheet->getHeaderFooter()
            ->setOddHeader('&C&"Segoe UI,Bold"&10 LIBRARIA &8 - LAPORAN PERPUSTAKAAN')
            ->setOddFooter('&L'.$sheet->getTitle().'&CData bersumber dari database perpustakaan&RHalaman &P dari &N');

        foreach (range(1, $lastCol) as $column) {
            $sheet->getColumnDimension(self::col($column))->setWidth(14);
        }
    }

    private static function writeSummary(Worksheet $sheet, array $data): void
    {
        self::sheetHeader($sheet, $data, 8, 'RINGKASAN EKSEKUTIF', 'Ikhtisar menyeluruh koleksi, anggota, transaksi, dan keuangan', true);

        $row = 7;
        self::section($sheet, $row, '01  INDIKATOR UTAMA', 8);

        $cards = [
            [1, 'JUDUL BUKU', $data['stats']['total_titles'], self::FORMAT_INT, self::COFFEE],
            [3, 'EKSEMPLAR', $data['stats']['total_copies'], self::FORMAT_INT, self::BLUE],
            [5, 'TOTAL ANGGOTA', $data['stats']['total_members'], self::FORMAT_INT, self::GREEN],
            [7, 'PEMINJAMAN', $data['stats']['total_loans'], self::FORMAT_INT, self::CARAMEL],
        ];

        foreach ($cards as [$col, $label, $value, $format, $accent]) {
            self::kpiCard($sheet, $row, $col, $label, $value, $format, $accent);
        }

        $row += 3;
        $cards = [
            [1, 'TERSEDIA', $data['stats']['available_copies'], self::FORMAT_INT, self::GREEN],
            [3, 'SEDANG DIPINJAM', $data['stats']['borrowed_copies'], self::FORMAT_INT, self::AMBER],
            [5, 'KEMBALI TERLAMBAT', $data['stats']['late_returns'], self::FORMAT_INT, self::RED],
            [7, 'DENDA BELUM DIBAYAR', $data['stats']['unpaid_fine'], self::FORMAT_MONEY, self::RED],
        ];

        foreach ($cards as [$col, $label, $value, $format, $accent]) {
            self::kpiCard($sheet, $row, $col, $label, $value, $format, $accent);
        }

        $row += 3;
        self::section($sheet, $row, '02  KONTEKS LAPORAN', 8);

        self::infoRow($sheet, $row++, 'Periode', $data['filters']['label'], 8);
        self::infoRow($sheet, $row++, 'Dicetak', $data['generated_at'], 8);
        self::infoRow($sheet, $row++, 'Petugas Pelapor', self::operator(), 8);
        self::infoRow($sheet, $row++, 'Nama Perpustakaan', $data['library']['library_name'], 8);
        self::infoRow($sheet, $row++, 'Alamat', $data['library']['library_address'], 8);
        self::infoRow($sheet, $row++, 'Sumber Data', 'Database perpustakaan Libraria (lokal)', 8);

        $row++;
        self::section($sheet, $row, '03  SOROTAN OPERASIONAL', 8);

        self::table($sheet, $row, ['Aspek', 'Nilai', 'Aspek', 'Nilai'], [
            ['Total Pengembalian', $data['stats']['total_returns'], 'Total Pembayaran Denda', $data['denda']['payment_total']],
            ['Denda Terbayar', $data['denda']['paid_amount'], 'Denda Belum Dibayar', $data['denda']['unpaid_amount']],
            ['Katalog Tercatat', $data['buku']['per_category']->count(), 'Penerbit Tercatat', $data['buku']['per_publisher']->count()],
            ['Jumlah Sekolah Asal', $data['anggota']['per_school']->count(), 'Jumlah Asal Anggota', $data['anggota']['per_origin']->count()],
        ], [1 => self::FORMAT_INT, 3 => self::FORMAT_MONEY], [], null, [22, 18, 22, 18]);

        self::note($sheet, $row + 1, 'Tips: buka tab "Grafik" untuk melihat tren peminjaman dan komposisi katalog secara visual.', 8);

        self::widths($sheet, [22, 18, 5, 22, 18, 5, 22, 18]);
        $sheet->freezePane('A7');
    }

    private static function writeBooks(Worksheet $sheet, array $data): void
    {
        $buku = $data['buku'];
        self::sheetHeader($sheet, $data, 6, 'KOLEKSI & KATALOG', 'Komposisi koleksi, katalog, penerbit, dan buku terpopuler');

        $row = 6;
        self::section($sheet, $row, '01  RINGKASAN KOLEKSI', 6);
        self::table($sheet, $row, ['Total Judul', 'Eksemplar', 'Tersedia', 'Dipinjam', 'Stok Habis', 'Tidak Aktif'], [
            [$buku['titles'], $buku['copies'], $buku['available'], $buku['borrowed'], $buku['out_of_stock'], $buku['inactive']],
        ], [0 => self::FORMAT_INT, 1 => self::FORMAT_INT, 2 => self::FORMAT_INT, 3 => self::FORMAT_INT, 4 => self::FORMAT_INT, 5 => self::FORMAT_INT]);

        self::section($sheet, $row, '02  BUKU PER KATALOG', 6);
        self::table($sheet, $row, ['Katalog', 'Judul', 'Eksemplar', 'Tersedia', 'Stok Habis', 'Persentase'], self::mapCategoryRows($buku['per_category']), [
            1 => self::FORMAT_INT, 2 => self::FORMAT_INT, 3 => self::FORMAT_INT, 4 => self::FORMAT_INT, 5 => '0.0"%"',
        ]);

        self::section($sheet, $row, '03  BUKU PER PENERBIT', 6);
        self::table($sheet, $row, ['Penerbit', 'Judul', 'Eksemplar', 'Tersedia', 'Persentase', 'Keterangan'], self::mapPublisherRows($buku['per_publisher']), [
            1 => self::FORMAT_INT, 2 => self::FORMAT_INT, 3 => self::FORMAT_INT, 4 => '0.0"%"',
        ], [5]);

        self::section($sheet, $row, '04  BUKU PALING SERING DIPINJAM', 6);
        self::table($sheet, $row, ['Peringkat', 'Judul Buku', 'Jumlah Peminjaman', 'Kategori', 'Status', 'Catatan'], self::mapPopularRows($buku['popular']), [2 => self::FORMAT_INT]);

        self::widths($sheet, [34, 16, 16, 16, 16, 18]);
        $sheet->freezePane('A7');
    }

    private static function writeMembers(Worksheet $sheet, array $data): void
    {
        $anggota = $data['anggota'];
        self::sheetHeader($sheet, $data, 6, 'ANGGOTA', 'Profil keanggotaan, asal sekolah, dan aktivitas peminjaman');

        $row = 6;
        self::section($sheet, $row, '01  RINGKASAN ANGGOTA', 6);
        self::table($sheet, $row, ['Total Anggota', 'Pernah Meminjam', 'Belum Pernah Meminjam', 'Jumlah Sekolah', 'Jumlah Asal', 'Tingkat Aktivitas'], [
            [
                $anggota['total'],
                $anggota['ever_borrowed'],
                max(0, $anggota['total'] - $anggota['ever_borrowed']),
                $anggota['per_school']->count(),
                $anggota['per_origin']->count(),
                $anggota['total'] > 0 ? round($anggota['ever_borrowed'] / $anggota['total'] * 100, 1).'%' : '0%',
            ],
        ], [0 => self::FORMAT_INT, 1 => self::FORMAT_INT, 2 => self::FORMAT_INT, 3 => self::FORMAT_INT, 4 => self::FORMAT_INT]);

        self::section($sheet, $row, '02  ANGGOTA PER SEKOLAH', 6);
        self::table($sheet, $row, ['Peringkat', 'Sekolah', 'Jumlah Anggota', 'Persentase', 'Keterangan', 'Catatan'], self::mapCountRows($anggota['per_school'], 'school', $anggota['total']), [2 => self::FORMAT_INT, 3 => '0.0"%"']);

        self::section($sheet, $row, '03  ANGGOTA PER ASAL', 6);
        self::table($sheet, $row, ['Peringkat', 'Asal', 'Jumlah Anggota', 'Persentase', 'Keterangan', 'Catatan'], self::mapCountRows($anggota['per_origin'], 'origin', $anggota['total']), [2 => self::FORMAT_INT, 3 => '0.0"%"']);

        self::section($sheet, $row, '04  ANGGOTA PALING SERING MEMINJAM', 6);
        self::table($sheet, $row, ['Peringkat', 'Kode Anggota', 'Nama Anggota', 'Jumlah Peminjaman', 'Status', 'Catatan'], self::mapBorrowerRows($anggota['borrowers']), [3 => self::FORMAT_INT]);

        self::widths($sheet, [14, 34, 20, 16, 18, 18]);
        $sheet->freezePane('A7');
    }

    private static function writeLoans(Worksheet $sheet, array $data): void
    {
        $peminjaman = $data['peminjaman'];
        self::sheetHeader($sheet, $data, 7, 'PEMINJAMAN', 'Rekap transaksi peminjaman dan buku yang paling diminati');

        $row = 6;
        self::section($sheet, $row, '01  RINGKASAN PEMINJAMAN', 7);
        self::table($sheet, $row, ['Total Transaksi', 'Hari Aktif', 'Bulan Aktif', 'Tahun Aktif', 'Judul Terpopuler', 'Status', 'Catatan'], [
            [
                $peminjaman['total'],
                $peminjaman['per_hari']->count(),
                $peminjaman['per_bulan']->count(),
                $peminjaman['per_tahun']->count(),
                $peminjaman['top_books']->count(),
                $peminjaman['total'] > 0 ? 'Aktif' : 'Belum ada transaksi',
                'Sesuai filter yang diterapkan',
            ],
        ], [0 => self::FORMAT_INT, 1 => self::FORMAT_INT, 2 => self::FORMAT_INT, 3 => self::FORMAT_INT, 4 => self::FORMAT_INT], [5]);

        self::section($sheet, $row, '02  REKAP PER HARI', 7);
        self::table($sheet, $row, ['Tanggal', 'Jumlah Transaksi', 'Hari', 'Periode', 'Keterangan', 'Status', 'Catatan'], self::mapPeriodRows($peminjaman['per_hari'], 'periode'), [1 => self::FORMAT_INT]);

        self::section($sheet, $row, '03  REKAP PER BULAN', 7);
        self::table($sheet, $row, ['Periode', 'Jumlah Transaksi', 'Tahun', 'Keterangan', 'Status', 'Catatan', 'Urutan'], self::mapMonthRows($peminjaman['per_bulan']), [1 => self::FORMAT_INT]);

        self::section($sheet, $row, '04  REKAP PER TAHUN', 7);
        self::table($sheet, $row, ['Tahun', 'Jumlah Transaksi', 'Periode', 'Keterangan', 'Status', 'Catatan', 'Urutan'], self::mapYearRows($peminjaman['per_tahun']), [1 => self::FORMAT_INT]);

        self::section($sheet, $row, '05  DAFTAR TRANSAKSI PEMINJAMAN', 7);
        $transactions = self::mapLoanRows($peminjaman['transactions']);
        $mainTable = self::table($sheet, $row, ['No. Transaksi', 'Anggota', 'Petugas', 'Tanggal Pinjam', 'Jatuh Tempo', 'Status', 'Keterangan'], $transactions, [], [5]);

        self::section($sheet, $row, '06  BUKU PALING SERING DIPINJAM (PERIODE INI)', 7);
        self::table($sheet, $row, ['Peringkat', 'Judul Buku', 'Jumlah Peminjaman', 'Kategori', 'Status', 'Catatan', 'Urutan'], self::mapTopBookRows($peminjaman['top_books']), [2 => self::FORMAT_INT]);

        $sheet->setAutoFilter($mainTable['range']);
        self::widths($sheet, [20, 32, 22, 18, 18, 18, 22]);
        $sheet->freezePane('A7');
    }

    private static function writeReturns(Worksheet $sheet, array $data): void
    {
        $pengembalian = $data['pengembalian'];
        self::sheetHeader($sheet, $data, 8, 'PENGEMBALIAN', 'Riwayat pengembalian dan ketepatan waktu berdasarkan jatuh tempo');

        $row = 6;
        self::section($sheet, $row, '01  RINGKASAN PENGEMBALIAN', 8);
        self::table($sheet, $row, ['Total', 'Tepat Waktu', 'Terlambat', 'Tanpa Data', 'Persentase Tepat Waktu', 'Persentase Terlambat', 'Status', 'Catatan'], [
            [
                $pengembalian['total'],
                $pengembalian['on_time'],
                $pengembalian['late'],
                max(0, $pengembalian['total'] - $pengembalian['on_time'] - $pengembalian['late']),
                $pengembalian['total'] > 0 ? round($pengembalian['on_time'] / $pengembalian['total'] * 100, 1).'%' : '0%',
                $pengembalian['total'] > 0 ? round($pengembalian['late'] / $pengembalian['total'] * 100, 1).'%' : '0%',
                $pengembalian['late'] > 0 ? 'Perlu perhatian' : 'Baik',
                'Dibandingkan tanggal jatuh tempo',
            ],
        ], [0 => self::FORMAT_INT, 1 => self::FORMAT_INT, 2 => self::FORMAT_INT, 3 => self::FORMAT_INT], [6]);

        self::section($sheet, $row, '02  RIWAYAT PENGEMBALIAN', 8);
        $mainTable = self::table($sheet, $row, ['No. Pengembalian', 'Anggota', 'Tanggal Kembali', 'Jatuh Tempo', 'Keterangan', 'Petugas', 'Denda', 'Status'], self::mapReturnRows($pengembalian['history']), [6 => self::FORMAT_MONEY], [4, 7]);

        $sheet->setAutoFilter($mainTable['range']);
        self::widths($sheet, [22, 32, 20, 18, 22, 22, 16, 18]);
        $sheet->freezePane('A7');
    }

    private static function writeFines(Worksheet $sheet, array $data): void
    {
        $denda = $data['denda'];
        self::sheetHeader($sheet, $data, 8, 'DENDA & PEMBAYARAN', 'Rekap denda, sisa tagihan, dan riwayat pembayaran');

        $row = 6;
        self::section($sheet, $row, '01  RINGKASAN KEUANGAN', 8);
        self::table($sheet, $row, ['Total Denda', 'Sudah Dibayar', 'Belum Dibayar', 'Total Pembayaran', 'Jumlah Denda', 'Lunas', 'Sebagian', 'Belum Dibayar'], [
            [
                $denda['total_amount'],
                $denda['paid_amount'],
                $denda['unpaid_amount'],
                $denda['payment_total'],
                $denda['total_count'],
                $denda['paid_count'],
                $denda['partial_count'],
                $denda['unpaid_count'],
            ],
        ], [
            0 => self::FORMAT_MONEY, 1 => self::FORMAT_MONEY, 2 => self::FORMAT_MONEY, 3 => self::FORMAT_MONEY,
            4 => self::FORMAT_INT, 5 => self::FORMAT_INT, 6 => self::FORMAT_INT, 7 => self::FORMAT_INT,
        ]);

        self::section($sheet, $row, '02  DAFTAR DENDA', 8);
        $mainTable = self::table($sheet, $row, ['No. Denda', 'Anggota', 'No. Pinjaman', 'Jumlah', 'Dibayar', 'Sisa', 'Status', 'Keterangan'], self::mapFineRows($denda['fines']), [
            3 => self::FORMAT_MONEY, 4 => self::FORMAT_MONEY, 5 => self::FORMAT_MONEY,
        ], [6]);

        self::section($sheet, $row, '03  RIWAYAT PEMBAYARAN DENDA', 8);
        self::table($sheet, $row, ['No. Kwitansi', 'Anggota', 'Petugas', 'Tanggal', 'Jumlah', 'Metode', 'Status', 'Keterangan'], self::mapPaymentRows($denda['payments']), [4 => self::FORMAT_MONEY], [6]);

        $sheet->setAutoFilter($mainTable['range']);
        self::widths($sheet, [22, 30, 22, 16, 16, 16, 18, 24]);
        $sheet->freezePane('A7');
    }

    private static function writePerformance(Worksheet $sheet, array $data): void
    {
        $kinerja = $data['kinerja'];
        self::sheetHeader($sheet, $data, 7, 'KINERJA PETUGAS', 'Kontribusi petugas dalam pelayanan peminjaman, pengembalian, dan pendaftaran');

        $row = 6;
        self::section($sheet, $row, '01  REKAP KINERJA', 7);

        $rows = [];
        foreach ($kinerja['petugas'] as $item) {
            $rows[] = [
                $item['name'],
                $item['loans'],
                $item['returns'],
                $item['registered'],
                $item['payments'],
                $item['total'],
                $item['total'] > 0 ? 'Aktif' : 'Belum ada aktivitas',
            ];
        }

        $mainTable = self::table($sheet, $row, ['Nama Petugas', 'Peminjaman', 'Pengembalian', 'Anggota Didaftarkan', 'Pembayaran', 'Total Dilayani', 'Status'], $rows, [
            1 => self::FORMAT_INT, 2 => self::FORMAT_INT, 3 => self::FORMAT_INT, 4 => self::FORMAT_INT, 5 => self::FORMAT_INT,
        ], [6], [
            'TOTAL',
            $kinerja['total_loans'],
            $kinerja['total_returns'],
            $kinerja['total_registered'],
            $kinerja['total_payments'],
            $kinerja['total_served'],
            'Seluruh petugas',
        ]);

        $sheet->setAutoFilter($mainTable['range']);
        self::widths($sheet, [30, 16, 16, 22, 16, 20, 24]);
        $sheet->freezePane('A7');
    }

    private static function writeCharts(Worksheet $sheet, array $data): void
    {
        self::sheetHeader($sheet, $data, 20, 'VISUALISASI DATA', 'Grafik tren transaksi dan komposisi katalog berksi filter aktif');

        $labels = array_values($data['charts']['labels'] ?? []);
        $loans = array_values($data['charts']['loans'] ?? []);
        $returns = array_values($data['charts']['returns'] ?? []);
        $categoryLabels = array_values($data['charts']['categoryLabels'] ?? []);
        $categoryData = array_values($data['charts']['categoryData'] ?? []);

        $sheet->setCellValue('A6', 'SUMBER DATA GRAFIK TREN');
        self::styleRange($sheet, 'A6:C6', self::miniHeadingStyle());
        $sheet->fromArray(['Periode', 'Peminjaman', 'Pengembalian'], null, 'A7');
        foreach ($labels as $index => $label) {
            $r = 8 + $index;
            $sheet->setCellValue("A{$r}", (string) $label);
            self::setCell($sheet, "B{$r}", $loans[$index] ?? 0);
            self::setCell($sheet, "C{$r}", $returns[$index] ?? 0);
        }
        self::styleRange($sheet, 'A7:C7', self::tableHeaderStyle());
        self::styleRange($sheet, 'A8:C'.(7 + max(1, count($labels))), [
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::LATTE]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->setCellValue('E6', 'SUMBER DATA GRAFIK KATALOG');
        self::styleRange($sheet, 'E6:F6', self::miniHeadingStyle());
        $sheet->fromArray(['Katalog', 'Jumlah Judul'], null, 'E7');
        foreach ($categoryLabels as $index => $label) {
            $r = 8 + $index;
            $sheet->setCellValue("E{$r}", (string) $label);
            self::setCell($sheet, "F{$r}", $categoryData[$index] ?? 0);
        }
        self::styleRange($sheet, 'E7:F7', self::tableHeaderStyle());
        self::styleRange($sheet, 'E8:F'.(7 + max(1, count($categoryLabels))), [
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::LATTE]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        if ($labels !== []) {
            $last = 7 + count($labels);
            $categories = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Grafik'!\$A\$8:\$A\${$last}", null, count($labels), $labels);
            $loanValues = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Grafik'!\$B\$8:\$B\${$last}", self::FORMAT_INT, count($labels), $loans, null, self::CARAMEL);
            $returnValues = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Grafik'!\$C\$8:\$C\${$last}", self::FORMAT_INT, count($labels), $returns, null, self::BLUE);

            $loanLabel = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Grafik'!\$B\$7", null, 1, ['Peminjaman']);
            $returnLabel = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Grafik'!\$C\$7", null, 1, ['Pengembalian']);

            $series = [
                new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, [0], [$loanLabel], [$categories], [$loanValues], DataSeries::DIRECTION_COL),
                new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, [1], [$returnLabel], [$categories], [$returnValues], DataSeries::DIRECTION_COL),
            ];

            $chart = new Chart(
                'Tren Transaksi',
                new Title('Tren Peminjaman & Pengembalian'),
                new Legend(Legend::POSITION_BOTTOM),
                new PlotArea(null, $series),
                true,
                DataSeries::EMPTY_AS_GAP,
                new Title('Periode'),
                new Title('Jumlah Transaksi')
            );
            $chart->setDescription('Grafik batang perbandingan peminjaman dan pengembalian per bulan.')
                ->setTopLeftPosition('H6')
                ->setBottomRightPosition('T22');
            $sheet->addChart($chart);
        }

        if ($categoryLabels !== []) {
            $last = 7 + count($categoryLabels);
            $categories = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Grafik'!\$E\$8:\$E\${$last}", null, count($categoryLabels), $categoryLabels);
            $values = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "'Grafik'!\$F\$8:\$F\${$last}", self::FORMAT_INT, count($categoryLabels), $categoryData, null, self::COFFEE);
            $label = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "'Grafik'!\$F\$7", null, 1, ['Jumlah Judul']);

            $series = new DataSeries(DataSeries::TYPE_BARCHART, DataSeries::GROUPING_CLUSTERED, [0], [$label], [$categories], [$values], DataSeries::DIRECTION_BAR);

            $chart = new Chart(
                'Komposisi Katalog',
                new Title('Komposisi Koleksi per Katalog'),
                null,
                new PlotArea(null, [$series]),
                true,
                DataSeries::EMPTY_AS_GAP,
                new Title('Jumlah Judul'),
                new Title('Katalog')
            );
            $chart->setDescription('Grafik batang komposisi judul buku per katalog.')
                ->setTopLeftPosition('H24')
                ->setBottomRightPosition('T40');
            $sheet->addChart($chart);
        }

        self::note($sheet, 42, 'Data grafik mengikuti filter yang sama dengan seluruh laporan.', 20);
        self::widths($sheet, [20, 16, 16, 4, 24, 16, 4], 8);
        $sheet->freezePane('A7');
    }

    // ------------------------------------------------------------------
    // Sheet building helpers
    // ------------------------------------------------------------------

    private static function sheetHeader(Worksheet $sheet, array $data, int $lastCol, string $title, string $subtitle, bool $withLogo = false): void
    {
        $last = self::col($lastCol);
        $titleEnd = $withLogo ? self::col($lastCol - 1) : $last;

        $sheet->mergeCells("A1:{$titleEnd}1");
        $sheet->setCellValue('A1', $title);
        $sheet->getRowDimension(1)->setRowHeight($withLogo ? 46 : 30);

        if ($withLogo) {
            self::styleRange($sheet, "A1:{$last}1", [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ESPRESSO]],
                'font' => ['color' => ['rgb' => self::PAPER], 'bold' => true, 'size' => 15],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            ]);
            self::addLogo($sheet, "{$last}1");
        } else {
            self::styleRange($sheet, "A1:{$last}1", [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ESPRESSO]],
                'font' => ['color' => ['rgb' => self::PAPER], 'bold' => true, 'size' => 15],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
            ]);
        }

        $sheet->mergeCells("A2:{$last}2");
        $sheet->setCellValue('A2', strtoupper((string) ($data['library']['library_name'] ?? 'Perpustakaan')));
        $sheet->getRowDimension(2)->setRowHeight(20);
        self::styleRange($sheet, "A2:{$last}2", [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::LATTE]],
            'font' => ['color' => ['rgb' => self::ESPRESSO], 'bold' => true, 'size' => 10],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);

        $sheet->mergeCells("A3:{$last}3");
        $sheet->setCellValue('A3', $subtitle);
        $sheet->getRowDimension(3)->setRowHeight(17);
        self::styleRange($sheet, "A3:{$last}3", [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CREAM]],
            'font' => ['color' => ['rgb' => self::MUTED], 'italic' => true, 'size' => 9],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);

        $sheet->mergeCells("A4:{$last}4");
        $sheet->setCellValue('A4', 'Periode: '.$data['filters']['label'].'   |   Dicetak: '.$data['generated_at'].'   |   Oleh: '.self::operator());
        $sheet->getRowDimension(4)->setRowHeight(17);
        self::styleRange($sheet, "A4:{$last}4", [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PAPER]],
            'font' => ['color' => ['rgb' => self::COFFEE], 'size' => 9],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::CARAMEL]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);

        $sheet->getRowDimension(5)->setRowHeight(8);
    }

    private static function section(Worksheet $sheet, int &$row, string $title, int $lastCol): void
    {
        $last = self::col($lastCol);
        $sheet->mergeCells("A{$row}:{$last}{$row}");
        $sheet->setCellValue("A{$row}", $title);
        $sheet->getRowDimension($row)->setRowHeight(21);
        self::styleRange($sheet, "A{$row}:{$last}{$row}", [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CREAM]],
            'font' => ['color' => ['rgb' => self::ESPRESSO], 'bold' => true, 'size' => 10],
            'borders' => [
                'left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::CARAMEL]],
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::LATTE]],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        $row++;
    }

    /**
     * @param array<int, string|int|float|null> $headers
     * @param iterable<array<int, string|int|float|null>> $rows
     * @param array<int, string> $formats
     * @param array<int, int> $statusColumns
     * @param array<int, string|int|float|null>|null $totalValues
     * @return array{header: int, last: int, range: string}
     */
    private static function table(
        Worksheet $sheet,
        int &$row,
        array $headers,
        iterable $rows,
        array $formats = [],
        array $statusColumns = [],
        ?array $totalValues = null,
        ?array $widths = null
    ): array {
        $columnCount = count($headers);
        $lastCol = self::col($columnCount);
        $headerRow = $row;

        foreach ($headers as $index => $header) {
            self::setCell($sheet, self::col($index + 1).$headerRow, $header);
        }
        $sheet->getRowDimension($headerRow)->setRowHeight(19);
        self::styleRange($sheet, "A{$headerRow}:{$lastCol}{$headerRow}", self::tableHeaderStyle());
        $row++;

        $dataCount = 0;
        foreach ($rows as $values) {
            $values = array_values((array) $values);
            $r = $row;
            for ($c = 0; $c < $columnCount; $c++) {
                $value = $values[$c] ?? '';
                self::setCell($sheet, self::col($c + 1).$r, $value);

                if (isset($formats[$c]) && is_numeric($value)) {
                    $sheet->getStyle(self::col($c + 1).$r)->getNumberFormat()->setFormatCode($formats[$c]);
                }

                $style = [
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $dataCount % 2 === 0 ? self::PAPER : self::CREAM]],
                    'font' => ['color' => ['rgb' => self::INK], 'size' => 9],
                    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::LATTE]]],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'horizontal' => is_numeric($value) ? Alignment::HORIZONTAL_RIGHT : Alignment::HORIZONTAL_LEFT,
                        'wrapText' => true,
                    ],
                ];

                if (in_array($c, $statusColumns, true)) {
                    $color = self::statusColor((string) $value);
                    if ($color !== null) {
                        $style['font']['color'] = ['rgb' => $color];
                        $style['font']['bold'] = true;
                    }
                }

                self::styleRange($sheet, self::col($c + 1).$r, $style);
            }
            $sheet->getRowDimension($r)->setRowHeight(18);
            $row++;
            $dataCount++;
        }

        $lastDataRow = max($headerRow, $row - 1);

        if ($dataCount === 0) {
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->setCellValue("A{$row}", 'Tidak ada data pada filter ini.');
            self::styleRange($sheet, "A{$row}:{$lastCol}{$row}", [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CREAM]],
                'font' => ['color' => ['rgb' => self::MUTED], 'italic' => true, 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }

        if ($totalValues !== null) {
            $totalRow = $row;
            foreach (array_values($totalValues) as $index => $value) {
                self::setCell($sheet, self::col($index + 1).$totalRow, $value);
                if (isset($formats[$index]) && is_numeric($value)) {
                    $sheet->getStyle(self::col($index + 1).$totalRow)->getNumberFormat()->setFormatCode($formats[$index]);
                }
            }
            self::styleRange($sheet, "A{$totalRow}:{$lastCol}{$totalRow}", [
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::LATTE]],
                'font' => ['color' => ['rgb' => self::ESPRESSO], 'bold' => true, 'size' => 9],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::CARAMEL]]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($totalRow)->setRowHeight(19);
            $row++;
        }

        if ($widths !== null) {
            foreach ($widths as $index => $width) {
                $sheet->getColumnDimension(self::col($index + 1))->setWidth($width);
            }
        }

        $row++;

        return [
            'header' => $headerRow,
            'last' => $row - 2,
            'range' => "A{$headerRow}:{$lastCol}".($row - 2),
        ];
    }

    private static function kpiCard(Worksheet $sheet, int $row, int $startCol, string $label, mixed $value, string $format, string $accent): void
    {
        $first = self::col($startCol);
        $second = self::col($startCol + 1);

        $sheet->mergeCells("{$first}{$row}:{$second}{$row}");
        $sheet->mergeCells("{$first}".($row + 1).":{$second}".($row + 1));
        $sheet->setCellValue("{$first}{$row}", $label);
        self::setCell($sheet, "{$first}".($row + 1), $value);

        if (is_numeric($value)) {
            $sheet->getStyle("{$first}".($row + 1))->getNumberFormat()->setFormatCode($format);
        }

        $sheet->getRowDimension($row)->setRowHeight(16);
        $sheet->getRowDimension($row + 1)->setRowHeight(27);

        self::styleRange($sheet, "{$first}{$row}:{$second}{$row}", [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::LATTE]],
            'font' => ['color' => ['rgb' => self::COFFEE], 'bold' => true, 'size' => 8],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        self::styleRange($sheet, "{$first}".($row + 1).":{$second}".($row + 1), [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PAPER]],
            'font' => ['color' => ['rgb' => $accent], 'bold' => true, 'size' => 15],
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $accent]],
                'left' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::LATTE]],
                'right' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::LATTE]],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    private static function infoRow(Worksheet $sheet, int $row, string $label, mixed $value, int $lastCol): void
    {
        $last = self::col($lastCol);
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->mergeCells("C{$row}:{$last}{$row}");
        $sheet->setCellValue("A{$row}", strtoupper($label));
        self::setCell($sheet, "C{$row}", $value);
        $sheet->getRowDimension($row)->setRowHeight(18);

        self::styleRange($sheet, "A{$row}:B{$row}", [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::LATTE]],
            'font' => ['color' => ['rgb' => self::ESPRESSO], 'bold' => true, 'size' => 8],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
        self::styleRange($sheet, "C{$row}:{$last}{$row}", [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::PAPER]],
            'font' => ['color' => ['rgb' => self::INK], 'size' => 9],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::LATTE]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1, 'wrapText' => true],
        ]);
    }

    private static function note(Worksheet $sheet, int $row, string $text, int $lastCol): void
    {
        $last = self::col($lastCol);
        $sheet->mergeCells("A{$row}:{$last}{$row}");
        $sheet->setCellValue("A{$row}", $text);
        $sheet->getRowDimension($row)->setRowHeight(19);
        self::styleRange($sheet, "A{$row}:{$last}{$row}", [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CREAM]],
            'font' => ['color' => ['rgb' => self::COFFEE], 'italic' => true, 'size' => 9],
            'borders' => ['left' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::CARAMEL]]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ]);
    }

    // ------------------------------------------------------------------
    // Row mappers
    // ------------------------------------------------------------------

    private static function mapCategoryRows($items): array
    {
        $rows = [];
        $total = max(1, (int) $items->sum('books_count'));
        foreach ($items as $index => $item) {
            $titles = (int) $item->books_count;
            $stock = (int) $item->total_stock;
            $available = (int) $item->total_available;
            $rows[] = [
                $item->name,
                $titles,
                $stock,
                $available,
                max(0, $stock - $available),
                round($titles / $total * 100, 1),
            ];
        }

        return $rows;
    }

    private static function mapPublisherRows($items): array
    {
        $rows = [];
        $total = max(1, (int) $items->sum('total_judul'));
        foreach ($items as $item) {
            $titles = (int) $item->total_judul;
            $stock = (int) $item->total_eksemplar;
            $available = (int) $item->total_tersedia;
            $rows[] = [
                $item->name,
                $titles,
                $stock,
                $available,
                round($titles / $total * 100, 1),
                $available === 0 ? 'Stok habis' : ($stock > $available ? 'Sebagian dipinjam' : 'Semua tersedia'),
            ];
        }

        return $rows;
    }

    private static function mapPopularRows($items): array
    {
        $rows = [];
        foreach ($items as $index => $item) {
            $rows[] = [
                $index + 1,
                $item->title,
                (int) $item->loan_details_count,
                $item->category?->name ?? 'Tanpa katalog',
                (int) $item->loan_details_count > 0 ? 'Diminati' : 'Belum dipinjam',
                'Sepanjang waktu',
            ];
        }

        return $rows;
    }

    private static function mapCountRows($items, string $field, int $total): array
    {
        $rows = [];
        foreach ($items as $index => $item) {
            $count = (int) $item->total;
            $rows[] = [
                $index + 1,
                $item->{$field} ?: 'Tanpa data',
                $count,
                $total > 0 ? round($count / $total * 100, 1) : 0,
                $count > 0 ? 'Terdata' : 'Tidak ada',
                'Anggota terdaftar',
            ];
        }

        return $rows;
    }

    private static function mapBorrowerRows($items): array
    {
        $rows = [];
        foreach ($items as $index => $item) {
            $rows[] = [
                $index + 1,
                $item->member_code,
                $item->full_name,
                (int) $item->loans_count,
                (int) $item->loans_count > 0 ? 'Aktif meminjam' : 'Tidak aktif',
                'Sesuai filter transaksi',
            ];
        }

        return $rows;
    }

    private static function mapPeriodRows($items, string $field): array
    {
        $rows = [];
        foreach ($items as $index => $item) {
            $rows[] = [
                (string) $item->{$field},
                (int) $item->total,
                (string) $item->{$field},
                'Rekap harian',
                (int) $item->total > 0 ? 'Aktif' : 'Tidak ada',
                'Transaksi pinjam',
                $index + 1,
            ];
        }

        return $rows;
    }

    private static function mapMonthRows($items): array
    {
        $rows = [];
        foreach ($items as $index => $item) {
            $rows[] = [
                (string) $item->periode,
                (int) $item->total,
                substr((string) $item->periode, 0, 4),
                'Rekap bulanan',
                (int) $item->total > 0 ? 'Aktif' : 'Tidak ada',
                'Transaksi pinjam',
                $index + 1,
            ];
        }

        return $rows;
    }

    private static function mapYearRows($items): array
    {
        $rows = [];
        foreach ($items as $index => $item) {
            $rows[] = [
                (string) $item->periode,
                (int) $item->total,
                (string) $item->periode,
                'Rekap tahunan',
                (int) $item->total > 0 ? 'Aktif' : 'Tidak ada',
                'Transaksi pinjam',
                $index + 1,
            ];
        }

        return $rows;
    }

    private static function mapLoanRows($items): array
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                $item->loan_number,
                $item->member?->full_name ?? '-',
                $item->user?->name ?? '-',
                tanggal($item->loan_date),
                tanggal($item->due_date),
                $item->status_label,
                'Transaksi perpustakaan',
            ];
        }

        return $rows;
    }

    private static function mapTopBookRows($items): array
    {
        $rows = [];
        foreach ($items as $index => $item) {
            $rows[] = [
                $index + 1,
                $item->title,
                (int) $item->total,
                $item->category?->name ?? 'Tanpa katalog',
                (int) $item->total > 0 ? 'Diminati' : 'Belum dipinjam',
                'Periode terfilter',
                $index + 1,
            ];
        }

        return $rows;
    }

    private static function mapReturnRows($items): array
    {
        $rows = [];
        foreach ($items as $item) {
            $keterangan = $item->timing === 'terlambat'
                ? 'Terlambat '.(int) $item->late_days.' hari'
                : ($item->timing === 'tepat_waktu' ? 'Tepat waktu' : 'Tanpa data');

            $rows[] = [
                $item->return_number,
                $item->member?->full_name ?? '-',
                tanggal($item->return_date, true),
                tanggal($item->loan?->due_date),
                $keterangan,
                $item->user?->name ?? '-',
                (float) $item->total_fine,
                $item->timing === 'terlambat' ? 'Terlambat' : ($item->timing === 'tepat_waktu' ? 'Tepat waktu' : 'Tanpa data'),
            ];
        }

        return $rows;
    }

    private static function mapFineRows($items): array
    {
        $rows = [];
        foreach ($items as $item) {
            $status = ucwords(str_replace('_', ' ', $item->status));
            $rows[] = [
                $item->fine_number,
                $item->member?->full_name ?? '-',
                $item->loan?->loan_number ?? '-',
                (float) $item->amount,
                (float) $item->paid_amount,
                (float) $item->remaining,
                $status,
                $item->status === 'lunas' ? 'Selesai' : 'Masih berjalan',
            ];
        }

        return $rows;
    }

    private static function mapPaymentRows($items): array
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = [
                $item->receipt_number,
                $item->fine?->member?->full_name ?? '-',
                $item->user?->name ?? '-',
                tanggal($item->payment_date, true),
                (float) $item->amount,
                ucfirst((string) $item->method),
                'Lunas',
                'Pembayaran tercatat',
            ];
        }

        return $rows;
    }

    // ------------------------------------------------------------------
    // Low-level helpers
    // ------------------------------------------------------------------

    private static function setCell(Worksheet $sheet, string $coordinate, mixed $value): void
    {
        $value ??= '';

        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
            $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_STRING);

            return;
        }

        $sheet->setCellValue($coordinate, $value);
    }

    private static function styleRange(Worksheet $sheet, string $range, array $style): void
    {
        $sheet->getStyle($range)->applyFromArray($style);
    }

    private static function tableHeaderStyle(): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::ESPRESSO]],
            'font' => ['color' => ['rgb' => self::PAPER], 'bold' => true, 'size' => 8],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::CARAMEL]]],
        ];
    }

    private static function miniHeadingStyle(): array
    {
        return [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::CREAM]],
            'font' => ['color' => ['rgb' => self::ESPRESSO], 'bold' => true, 'size' => 9],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'indent' => 1],
        ];
    }

    private static function statusColor(string $value): ?string
    {
        $value = strtolower($value);

        if (str_contains($value, 'terlambat') || str_contains($value, 'belum dibayar') || str_contains($value, 'stok habis') || str_contains($value, 'tidak aktif')) {
            return self::RED;
        }

        if (str_contains($value, 'lunas') || str_contains($value, 'tepat waktu') || str_contains($value, 'selesai') || str_contains($value, 'tersedia') || str_contains($value, 'aktif') || str_contains($value, 'diminati') || str_contains($value, 'baik')) {
            return self::GREEN;
        }

        if (str_contains($value, 'sebagian') || str_contains($value, 'sebagaian') || str_contains($value, 'dipinjam') || str_contains($value, 'perlu perhatian') || str_contains($value, 'masih berjalan')) {
            return self::AMBER;
        }

        if (str_contains($value, 'dibatalkan') || str_contains($value, 'tanpa data')) {
            return self::MUTED;
        }

        return null;
    }

    private static function widths(Worksheet $sheet, array $widths, int $start = 1): void
    {
        foreach ($widths as $index => $width) {
            $sheet->getColumnDimension(self::col($start + $index))->setWidth($width);
        }
    }

    private static function col(int $index): string
    {
        return Coordinate::stringFromColumnIndex($index);
    }

    private static function operator(): string
    {
        return (string) (auth()->user()?->name ?? '-');
    }

    private static function addLogo(Worksheet $sheet, string $coordinate): void
    {
        $path = self::logoPath();
        if ($path === null) {
            return;
        }

        try {
            $drawing = new Drawing();
            $drawing->setName('Logo Perpustakaan');
            $drawing->setDescription('Logo '.(string) (setting('library_name', 'Perpustakaan') ?? 'Perpustakaan'));
            $drawing->setPath($path);
            $drawing->setCoordinates($coordinate);
            $drawing->setRowHeight(44);
            $drawing->setWidth(44);
            $drawing->setOffsetX(12);
            $drawing->setOffsetY(1);
            $sheet->addDrawing($drawing);
        } catch (\Throwable) {
            // The workbook stays valid even when the logo cannot be embedded.
        }
    }

    private static function logoPath(): ?string
    {
        try {
            $logo = (string) (setting('library_logo', '') ?? '');
            if ($logo === '') {
                return null;
            }
            $path = Storage::disk('public')->path($logo);

            return is_file($path) ? $path : null;
        } catch (\Throwable) {
            return null;
        }
    }
}

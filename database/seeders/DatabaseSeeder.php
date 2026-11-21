<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use App\Models\BookReturn;
use App\Models\Category;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\LoanDetail;
use App\Models\Member;
use App\Models\Publisher;
use App\Models\Setting;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Settings::DEFAULTS as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        User::firstOrCreate(
            ['email' => 'admin@libraria.local'],
            ['name' => 'Administrator', 'password' => Hash::make('password'), 'role' => 'admin', 'is_active' => true]
        );

        User::firstOrCreate(
            ['email' => 'petugas@libraria.local'],
            ['name' => 'Petugas Utama', 'password' => Hash::make('password'), 'role' => 'petugas', 'is_active' => true]
        );

        if (Category::count() > 0) {
            return;
        }

        $categories = collect(['Fiksi', 'Non-Fiksi', 'Pendidikan', 'Sains', 'Sejarah', 'Agama', 'Teknologi', 'Bahasa'])
            ->map(fn ($name) => Category::create(['name' => $name, 'description' => 'Katalog '.$name]));

        $authors = collect(['Ahmad Fauzi', 'Siti Aisyah', 'M. Khalil', 'Dewi Lestari', 'Rizky Pratama', 'Nur Hasanah', 'Andi Saputra', 'Fatmawati'])
            ->map(fn ($name) => Author::create(['name' => $name]));

        $publishers = collect(['Pustaka Jaya', 'Gramedia', 'Erlangga', 'Mizan', 'Bentang Pustaka', 'Balai Pustaka'])
            ->map(fn ($name) => Publisher::create(['name' => $name]));

        $titles = [
            'Laskar Pelangi', 'Negeri 5 Menara', 'Ayat-Ayat Cinta', 'Bumi Manusia',
            'Perahu Kertas', 'Habis Gelap Terbitlah Terang', 'Sang Pemimpi', 'Cookies',
            'Matematika untuk SMA', 'Biologi Dasar', 'Fisika Kelas XI', 'Sejarah Indonesia',
            'Al-Quran Terjemahan', 'Fikih Sederhana', 'Dasar Pemrograman Web', 'Jaringan Komputer',
            'Tata Bahasa Indonesia', 'Kamus Besar Bahasa Indonesia', 'Ekonomi Mikro', 'Geografi Umum',
        ];

        $books = collect($titles)->map(function (string $title, int $i) use ($categories, $authors, $publishers) {
            return Book::create([
                'isbn' => '978-602-'.str_pad((string) (10000 + $i), 6, '0', STR_PAD_LEFT).'-'.$i,
                'title' => $title,
                'author_id' => $authors[$i % $authors->count()]->id,
                'publisher_id' => $publishers[$i % $publishers->count()]->id,
                'category_id' => $categories[$i % $categories->count()]->id,
                'year' => 2015 + ($i % 10),
                'stock' => 4 + ($i % 3),
                'available' => 4 + ($i % 3),
                'description' => 'Deskripsi singkat buku '.$title.'.',
                'status' => 'tersedia',
            ]);
        });

        $members = collect([
            ['Ahmad Fauzi', 'SMP Nurul Jadid', 'Probolinggo'],
            ['Siti Aisyah', 'SMA Nurul Jadid', 'Situbondo'],
            ['Muhammad Halim', 'SMP Al-Falah', 'Jember'],
            ['Nur Aini', 'SMA Bahrul Ulum', 'Bondowoso'],
            ['Rizky Ramadhan', 'SMP Nurul Jadid', 'Surabaya'],
            ['Dewi Sartika', 'SMA Nurul Jadid', 'Malang'],
            ['Fajar Nugroho', 'SMK Al-Islah', 'Probolinggo'],
            ['Alya Putri', 'SMP Al-Falah', 'Sidoarjo'],
            ['Budi Santoso', 'SMA Bahrul Ulum', 'Jember'],
            ['Salsa Amelia', 'SMP Nurul Jadid', 'Situbondo'],
        ])->map(fn ($data) => Member::create([
            'full_name' => $data[0], 'school' => $data[1], 'origin' => $data[2],
        ]));

        $petugas = User::where('role', 'petugas')->first();

        // Riwayat selesai (dikembalikan tepat waktu)
        foreach ([0, 1, 2, 3] as $i) {
            $this->createLoan($members[$i], $petugas, $books[$i], now()->subDays(20 - $i), now()->subDays(13 - $i), now()->subDays(13 - $i), false);
        }

        // Riwayat terlambat dengan denda lunas / sebagian
        $this->createLoan($members[4], $petugas, $books[4], now()->subDays(20), now()->subDays(13), now()->subDays(9), true, 20000, 20000);
        $this->createLoan($members[5], $petugas, $books[5], now()->subDays(18), now()->subDays(11), now()->subDays(6), true, 10000, 6000);

        // Pinjaman aktif & terlambat
        $this->createLoan($members[6], $petugas, [$books[6], $books[7]], now()->subDays(3), now()->addDays(4), null, false);
        $this->createLoan($members[7], $petugas, [$books[8]], now()->subDays(12), now()->subDays(5), null, false);
        $this->createLoan($members[8], $petugas, [$books[9], $books[10]], now()->subDay(), now()->addDays(6), null, false);
        $this->createLoan($members[9], $petugas, [$books[11]], now()->subDays(2), now()->addDays(5), null, false);
    }

    private function createLoan(Member $member, User $petugas, mixed $books, $loanDate, $dueDate, $returnedAt, bool $withFine, float $fineAmount = 0, float $paid = 0): void
    {
        $books = is_array($books) ? $books : [$books];

        $loan = Loan::create([
            'member_id' => $member->id,
            'user_id' => $petugas->id,
            'loan_date' => $loanDate,
            'due_date' => $dueDate,
            'status' => $returnedAt ? 'selesai' : ($dueDate->isPast() ? 'terlambat' : 'dipinjam'),
            'total_books' => count($books),
        ]);

        $return = $returnedAt ? BookReturn::create([
            'loan_id' => $loan->id,
            'member_id' => $member->id,
            'user_id' => $petugas->id,
            'return_date' => $returnedAt,
            'total_books' => count($books),
            'total_fine' => $fineAmount,
        ]) : null;

        foreach ($books as $book) {
            $lateDays = $returnedAt ? max(0, (int) $returnedAt->diffInDays($dueDate)) : 0;
            $fine = $withFine ? $fineAmount : 0;

            $detail = LoanDetail::create([
                'loan_id' => $loan->id,
                'book_id' => $book->id,
                'returned_at' => $returnedAt,
                'status' => $returnedAt ? 'dikembalikan' : ($dueDate->isPast() ? 'terlambat' : 'dipinjam'),
                'fine_amount' => $fine,
            ]);

            if ($return) {
                $return->details()->create([
                    'loan_detail_id' => $detail->id,
                    'book_id' => $book->id,
                    'fine_amount' => $fine,
                    'late_days' => $lateDays,
                ]);
            }

            if (! $returnedAt) {
                $book->decrement('available');
            }
        }

        if ($withFine) {
            $fineRecord = Fine::create([
                'loan_id' => $loan->id,
                'member_id' => $member->id,
                'amount' => $fineAmount,
                'paid_amount' => $paid,
                'status' => $paid >= $fineAmount ? 'lunas' : ($paid > 0 ? 'dibayar_sebagian' : 'belum_dibayar'),
            ]);

            if ($paid > 0) {
                $fineRecord->payments()->create([
                    'user_id' => $petugas->id,
                    'amount' => $paid,
                    'payment_date' => $returnedAt,
                    'method' => 'tunai',
                ]);
            }
        }
    }
}

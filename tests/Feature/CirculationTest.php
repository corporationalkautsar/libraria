<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Member;
use App\Models\Publisher;
use App\Models\Shelf;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CirculationTest extends TestCase
{
    use RefreshDatabase;

    private function makeBook(string $title = 'Buku Uji'): Book
    {
        return Book::create([
            'isbn' => '978-'.$title,
            'title' => $title,
            'author_id' => Author::create(['name' => 'Penulis Uji'])->id,
            'publisher_id' => Publisher::create(['name' => 'Penerbit Uji'])->id,
            'category_id' => Category::create(['name' => 'Kategori Uji'])->id,
            'shelf_id' => Shelf::create(['code' => 'RK-UJI', 'name' => 'Rak Uji'])->id,
            'year' => 2024,
            'stock' => 3,
            'available' => 3,
            'status' => 'tersedia',
        ]);
    }

    public function test_member_has_only_three_input_fields_and_auto_code(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);

        $this->actingAs($petugas)->post('/members', [
            'full_name' => 'Ahmad Fauzi',
            'school' => 'SMP Nurul Jadid',
            'origin' => 'Probolinggo',
        ])->assertRedirect();

        $member = Member::first();
        $this->assertSame('AGT-0001', $member->member_code);
        $this->assertSame('Ahmad Fauzi', $member->full_name);
        $this->assertSame('SMP Nurul Jadid', $member->school);
        $this->assertSame('Probolinggo', $member->origin);
        $this->assertArrayNotHasKey('phone', $member->getAttributes());
        $this->assertArrayNotHasKey('email', $member->getAttributes());
    }

    public function test_loan_decrements_stock_and_return_calculates_fine(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $member = Member::create([
            'member_code' => 'AGT-0001',
            'full_name' => 'Siti Aisyah',
            'school' => 'SMA Nurul Jadid',
            'origin' => 'Situbondo',
        ]);
        $book = $this->makeBook();

        $this->actingAs($petugas)->post('/loans', [
            'member_id' => $member->id,
            'book_ids' => [$book->id],
        ])->assertRedirect();

        $loan = Loan::first();
        $this->assertNotNull($loan);
        $this->assertSame(2, $book->fresh()->available);
        $this->assertSame('dipinjam', $loan->status);

        // Simulasikan keterlambatan 2 hari
        $loan->update(['due_date' => today()->subDays(2)]);

        $this->post('/returns', [
            'loan_id' => $loan->id,
            'detail_ids' => [$loan->details->first()->id],
        ])->assertRedirect();

        $this->assertSame(3, $book->fresh()->available);
        $this->assertSame('selesai', $loan->fresh()->status);

        $fine = Fine::first();
        $this->assertNotNull($fine);
        $this->assertEquals(4000, $fine->amount); // 2 hari x Rp2.000
        $this->assertSame('belum_dibayar', $fine->status);
    }

    public function test_fine_payment_updates_status(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $member = Member::create([
            'member_code' => 'AGT-0001', 'full_name' => 'Uji', 'school' => 'SMP Uji', 'origin' => 'Jember',
        ]);
        $loan = Loan::create([
            'member_id' => $member->id, 'user_id' => $petugas->id,
            'loan_date' => today(), 'due_date' => today(), 'status' => 'selesai', 'total_books' => 1,
        ]);
        $fine = Fine::create([
            'loan_id' => $loan->id, 'member_id' => $member->id,
            'amount' => 10000, 'paid_amount' => 0, 'status' => 'belum_dibayar',
        ]);

        $this->actingAs($petugas)->post("/fines/{$fine->id}/pay", [
            'amount' => 4000, 'method' => 'tunai', 'payment_date' => today()->toDateString(),
        ])->assertRedirect();

        $this->assertEquals(4000, $fine->fresh()->paid_amount);
        $this->assertSame('dibayar_sebagian', $fine->fresh()->status);

        $this->post("/fines/{$fine->id}/pay", [
            'amount' => 6000, 'method' => 'tunai', 'payment_date' => today()->toDateString(),
        ])->assertRedirect();

        $this->assertSame('lunas', $fine->fresh()->status);
    }

    public function test_member_with_active_loan_cannot_be_deleted(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $member = Member::create([
            'member_code' => 'AGT-0001', 'full_name' => 'Uji', 'school' => 'SMP Uji', 'origin' => 'Jember',
        ]);
        $book = $this->makeBook();
        $this->actingAs($petugas)->post('/loans', [
            'member_id' => $member->id, 'book_ids' => [$book->id],
        ]);

        $this->delete("/members/{$member->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('members', ['id' => $member->id]);
    }
}

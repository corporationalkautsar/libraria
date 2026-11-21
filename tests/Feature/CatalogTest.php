<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_groups_books_by_catalog_and_has_no_shelf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Novel']);
        $author = Author::create(['name' => 'Penulis Uji']);
        $publisher = Publisher::create(['name' => 'Penerbit Uji']);

        Book::create([
            'title' => 'Buku Novel Uji',
            'author_id' => $author->id,
            'publisher_id' => $publisher->id,
            'category_id' => $category->id,
            'year' => 2026,
            'stock' => 1,
            'available' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('catalog.index'));

        $response->assertOk()
            ->assertSee('Jelajahi Katalog')
            ->assertSee('Katalog Novel')
            ->assertSee('Buku Novel Uji')
            ->assertDontSee('Rak Buku');
    }

    public function test_shelf_lookup_is_no_longer_available(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('lookups.index', 'shelves'))->assertNotFound();
    }
}

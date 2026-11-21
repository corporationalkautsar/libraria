<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    private const SORTABLE = ['title', 'year', 'stock', 'available', 'created_at'];

    public function index(Request $request)
    {
        $query = Book::with(['author', 'publisher', 'category']);

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('books.title', 'like', "%{$search}%")
                    ->orWhere('books.isbn', 'like', "%{$search}%")
                    ->orWhereHas('author', fn ($a) => $a->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('publisher', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('category', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        $query->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id));

        $query->when($request->filled('availability'), function ($q) use ($request) {
            match ($request->availability) {
                'tersedia' => $q->where('available', '>', 0),
                'habis' => $q->where('available', 0),
                default => null,
            };
        });

        $sort = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'title';
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';

        $books = $query->orderBy($sort, $direction)->paginate(15)->withQueryString();
        $catalogs = Category::withCount('books')->orderBy('name')->get();
        $totalBooks = Book::count();

        return view('catalog.index', compact('books', 'catalogs', 'totalBooks'));
    }

    public function show(Book $book)
    {
        $book->load(['author', 'publisher', 'category']);

        $related = Book::where('category_id', $book->category_id)
            ->where('id', '!=', $book->id)
            ->with('author')
            ->latest()
            ->take(4)
            ->get();

        return view('catalog.show', compact('book', 'related'));
    }
}

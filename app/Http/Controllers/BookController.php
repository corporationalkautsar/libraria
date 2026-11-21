<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Publisher;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    private const SORTABLE = ['title', 'year', 'stock', 'available', 'created_at'];

    private const STATUSES = ['tersedia', 'dipinjam', 'stok_habis', 'tidak_aktif'];

    /** Data master untuk dropdown filter dan form. */
    private function lookups(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
            'publishers' => Publisher::orderBy('name')->get(),
        ];
    }

    public function index(Request $request)
    {
        $query = Book::with(['author', 'publisher', 'category']);

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%")
                    ->orWhere('book_code', 'like', "%{$search}%");
            });
        }

        $query->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->category_id));
        $query->when($request->filled('author_id'), fn ($q) => $q->where('author_id', $request->author_id));
        $query->when($request->filled('publisher_id'), fn ($q) => $q->where('publisher_id', $request->publisher_id));
        $query->when($request->filled('year'), fn ($q) => $q->where('year', $request->year));
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->status));

        $query->when($request->filled('availability'), function ($q) use ($request) {
            match ($request->availability) {
                'tersedia' => $q->where('available', '>', 0),
                'sebagian' => $q->where('available', '>', 0)->whereColumn('available', '<', 'stock'),
                'habis' => $q->where('available', 0),
                default => null,
            };
        });

        $sort = in_array($request->get('sort'), self::SORTABLE, true) ? $request->get('sort') : 'created_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $books = $query->orderBy($sort, $direction)->paginate(15)->withQueryString();

        $stats = [
            'titles' => Book::count(),
            'copies' => (int) Book::sum('stock'),
            'available' => (int) Book::sum('available'),
            'empty' => Book::where('available', 0)->count(),
        ];

        $years = Book::query()->select('year')->distinct()->orderByDesc('year')->pluck('year')->all();

        return view('books.index', array_merge(
            compact('books', 'stats', 'years'),
            $this->lookups()
        ));
    }

    public function create()
    {
        return view('books.create', array_merge(
            ['book' => new Book()],
            $this->lookups()
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->messages());

        $data['isbn'] = ! empty($data['isbn']) ? $data['isbn'] : null;
        $data['available'] = (int) $data['stock'];

        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('covers', 'public');
        }

        $book = Book::create($data);
        $book->syncStatus();
        $book->save();

        ActivityLogger::log('Penambahan Buku', 'Buku '.$book->title.' ('.$book->book_code.') ditambahkan ke koleksi.');

        return redirect()->route('books.index')->with('success', 'Buku berhasil ditambahkan.');
    }

    public function show(Book $book)
    {
        $book->load(['author', 'publisher', 'category']);

        $loanHistory = $book->loanDetails()
            ->with('loan.member')
            ->latest()
            ->paginate(10);

        return view('books.show', compact('book', 'loanHistory'));
    }

    public function edit(Book $book)
    {
        return view('books.edit', array_merge(
            compact('book'),
            $this->lookups()
        ));
    }

    public function update(Request $request, Book $book)
    {
        $data = $request->validate($this->rules($book->id, true), $this->messages());

        $data['isbn'] = ! empty($data['isbn']) ? $data['isbn'] : null;

        $oldStock = (int) $book->stock;
        $oldAvailable = min((int) $book->available, $oldStock);
        $newStock = (int) $data['stock'];
        $delta = $newStock - $oldStock;

        // Stok bertambah -> jumlah tersedia ikut bertambah; stok berkurang -> dibatasi stok baru.
        $data['available'] = max(0, $delta > 0
            ? min($newStock, $oldAvailable + $delta)
            : min($oldAvailable, $newStock));

        if (! empty($data['remove_cover'])) {
            $this->deleteCover($book);
            $data['cover'] = null;
        } elseif ($request->hasFile('cover')) {
            $this->deleteCover($book);
            $data['cover'] = $request->file('cover')->store('covers', 'public');
        }

        unset($data['remove_cover']);

        $book->fill($data);
        $book->syncStatus();
        $book->save();

        ActivityLogger::log('Perubahan Buku', 'Data buku '.$book->title.' ('.$book->book_code.') diperbarui.');

        return redirect()->route('books.index')->with('success', 'Data buku berhasil diperbarui.');
    }

    public function destroy(Book $book)
    {
        if ($book->loanDetails()->exists()) {
            return back()->with('error', 'Buku tidak dapat dihapus karena memiliki riwayat peminjaman.');
        }

        $title = $book->title;
        $this->deleteCover($book);
        $book->delete();

        ActivityLogger::log('Hapus Buku', 'Buku '.$title.' dihapus dari koleksi.');

        return redirect()->route('books.index')->with('success', 'Buku berhasil dihapus.');
    }

    private function rules(?int $ignoreId = null, bool $withStatus = false): array
    {
        $isbnRule = ['nullable', 'string', 'max:32', 'unique:books,isbn'];
        if ($ignoreId) {
            $isbnRule[3] = 'unique:books,isbn,'.$ignoreId;
        }

        $rules = [
            'isbn' => $isbnRule,
            'title' => ['required', 'string', 'max:200'],
            'author_id' => ['required', 'exists:authors,id'],
            'publisher_id' => ['required', 'exists:publishers,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'stock' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'cover' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($withStatus) {
            $rules['status'] = ['required', 'in:'.implode(',', self::STATUSES)];
            $rules['remove_cover'] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    private function messages(): array
    {
        return [
            'title.required' => 'Judul buku wajib diisi.',
            'author_id.required' => 'Penulis wajib dipilih.',
            'publisher_id.required' => 'Penerbit wajib dipilih.',
            'category_id.required' => 'Katalog wajib dipilih.',
            'year.required' => 'Tahun terbit wajib diisi.',
            'year.min' => 'Tahun terbit minimal 1900.',
            'stock.required' => 'Jumlah stok wajib diisi.',
            'stock.min' => 'Jumlah stok minimal 1.',
            'isbn.unique' => 'ISBN sudah digunakan buku lain.',
            'cover.image' => 'File sampul harus berupa gambar.',
            'cover.max' => 'Ukuran sampul maksimal 2 MB.',
        ];
    }

    private function deleteCover(Book $book): void
    {
        if ($book->cover && Storage::disk('public')->exists($book->cover)) {
            Storage::disk('public')->delete($book->cover);
        }
    }
}

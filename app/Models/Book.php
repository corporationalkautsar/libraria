<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_code', 'isbn', 'title', 'author_id', 'publisher_id', 'category_id',
        'shelf_id', 'shelf_number', 'year', 'stock', 'available', 'description',
        'cover', 'status',
    ];

    protected function casts(): array
    {
        return ['year' => 'integer', 'stock' => 'integer', 'available' => 'integer'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(Publisher::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function shelf(): BelongsTo
    {
        return $this->belongsTo(Shelf::class);
    }

    public function loanDetails(): HasMany
    {
        return $this->hasMany(LoanDetail::class);
    }

    public function syncStatus(): void
    {
        if ($this->status === 'tidak_aktif') {
            return;
        }
        $this->status = match (true) {
            $this->available <= 0 => 'stok_habis',
            $this->available < $this->stock => 'dipinjam',
            default => 'tersedia',
        };
    }

    protected static function booted(): void
    {
        static::creating(function (Book $book) {
            if (empty($book->book_code)) {
                $last = static::max('id');
                $book->book_code = 'BUK-'.str_pad(((int) $last + 1), 4, '0', STR_PAD_LEFT);
            }
            $book->available ??= $book->stock;
        });
    }
}

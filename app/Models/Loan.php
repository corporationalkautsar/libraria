<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    protected $fillable = [
        'loan_number', 'member_id', 'user_id', 'loan_date', 'due_date',
        'status', 'total_books', 'notes',
    ];

    protected function casts(): array
    {
        return ['loan_date' => 'date', 'due_date' => 'date'];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(LoanDetail::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(BookReturn::class);
    }

    public function fine(): HasOne
    {
        return $this->hasOne(Fine::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'dipinjam' => 'Dipinjam',
            'terlambat' => 'Terlambat',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }

    protected static function booted(): void
    {
        static::creating(function (Loan $loan) {
            if (empty($loan->loan_number)) {
                $last = static::max('id');
                $loan->loan_number = 'PIN-'.date('ym').'-'.str_pad(((int) $last + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }
}

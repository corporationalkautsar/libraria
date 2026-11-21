<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookReturn extends Model
{
    protected $table = 'returns';

    protected $fillable = [
        'return_number', 'loan_id', 'member_id', 'user_id',
        'return_date', 'total_books', 'total_fine',
    ];

    protected function casts(): array
    {
        return ['return_date' => 'datetime', 'total_fine' => 'decimal:2'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
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
        return $this->hasMany(ReturnDetail::class, 'return_id');
    }

    protected static function booted(): void
    {
        static::creating(function (BookReturn $return) {
            if (empty($return->return_number)) {
                $last = static::max('id');
                $return->return_number = 'KMB-'.date('ym').'-'.str_pad(((int) $last + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }
}

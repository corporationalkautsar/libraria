<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinePayment extends Model
{
    protected $fillable = ['receipt_number', 'fine_id', 'user_id', 'amount', 'payment_date', 'method', 'note'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'payment_date' => 'datetime'];
    }

    public function fine(): BelongsTo
    {
        return $this->belongsTo(Fine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (FinePayment $payment) {
            if (empty($payment->receipt_number)) {
                $last = static::max('id');
                $payment->receipt_number = 'BYT-'.date('ym').'-'.str_pad(((int) $last + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fine extends Model
{
    protected $fillable = ['fine_number', 'loan_id', 'member_id', 'amount', 'paid_amount', 'status'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_amount' => 'decimal:2'];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinePayment::class);
    }

    public function getRemainingAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }

    public function refreshStatus(): void
    {
        $this->status = match (true) {
            $this->remaining <= 0 => 'lunas',
            $this->paid_amount > 0 => 'dibayar_sebagian',
            default => 'belum_dibayar',
        };
        $this->save();
    }

    protected static function booted(): void
    {
        static::creating(function (Fine $fine) {
            if (empty($fine->fine_number)) {
                $last = static::max('id');
                $fine->fine_number = 'DEN-'.date('ym').'-'.str_pad(((int) $last + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }
}

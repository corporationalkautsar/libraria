<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnDetail extends Model
{
    protected $fillable = ['return_id', 'loan_detail_id', 'book_id', 'fine_amount', 'late_days'];

    protected function casts(): array
    {
        return ['fine_amount' => 'decimal:2', 'late_days' => 'integer'];
    }

    public function bookReturn(): BelongsTo
    {
        return $this->belongsTo(BookReturn::class, 'return_id');
    }

    public function loanDetail(): BelongsTo
    {
        return $this->belongsTo(LoanDetail::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}

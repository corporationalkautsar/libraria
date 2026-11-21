<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = ['member_code', 'full_name', 'school', 'origin'];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class);
    }

    /**
     * Anggota diblokir otomatis bila masih memiliki pinjaman terlambat
     * atau denda belum lunas. Tidak menambah kolom pada tabel members.
     */
    public function getIsBlockedAttribute(): bool
    {
        if ($this->loans()->whereIn('status', ['dipinjam', 'terlambat'])
            ->where('due_date', '<', now()->toDateString())->exists()) {
            return true;
        }

        return $this->fines()->whereIn('status', ['belum_dibayar', 'dibayar_sebagian'])->exists();
    }

    public function getActiveLoansCountAttribute(): int
    {
        return $this->loans()->whereIn('status', ['dipinjam', 'terlambat'])->count();
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            if (empty($member->member_code)) {
                $last = static::max('id');
                $member->member_code = 'AGT-'.str_pad(((int) $last + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }
}

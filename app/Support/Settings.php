<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class Settings
{
    public const DEFAULTS = [
        'library_name' => 'Perpustakaan Libraria',
        'library_address' => 'Jl. Pendidikan No. 1, Indonesia',
        'library_logo' => '',
        'library_info' => 'Sistem Informasi Manajemen PerpustakaanOffline.',
        'max_books' => '3',
        'loan_days' => '7',
        'fine_per_day' => '2000',
    ];

    protected static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        try {
            if (! Schema::hasTable('settings')) {
                return $default ?? (self::DEFAULTS[$key] ?? null);
            }
            $value = Setting::where('key', $key)->value('value');
        } catch (\Throwable) {
            $value = null;
        }

        return self::$cache[$key] = $value ?? $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public static function all(): array
    {
        $values = [];
        foreach (self::DEFAULTS as $key => $default) {
            $values[$key] = self::get($key, $default);
        }

        return $values;
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => (string) $value]);
            self::$cache[$key] = (string) $value;
        }
    }

    public static function maxBooks(): int
    {
        return max(1, (int) self::get('max_books', 3));
    }

    public static function loanDays(): int
    {
        return max(1, (int) self::get('loan_days', 7));
    }

    public static function finePerDay(): float
    {
        return max(0, (float) self::get('fine_per_day', 2000));
    }

    public static function flush(): void
    {
        self::$cache = [];
    }
}

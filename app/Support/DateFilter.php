<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DateFilter
{
    public const OPTIONS = [
        '' => 'Semua Tanggal',
        'today' => 'Hari Ini',
        'yesterday' => 'Kemarin',
        '7days' => '7 Hari Terakhir',
        '30days' => '30 Hari Terakhir',
        'this_month' => 'Bulan Ini',
        'last_month' => 'Bulan Sebelumnya',
        'this_year' => 'Tahun Ini',
        'custom' => 'Rentang Tanggal Khusus',
    ];

    public static function apply(Builder $query, string $column, Request $request): Builder
    {
        $filter = $request->get('date_filter', '');
        $from = $request->get('date_from');
        $to = $request->get('date_to');

        return match ($filter) {
            'today' => $query->whereDate($column, now()->toDateString()),
            'yesterday' => $query->whereDate($column, now()->subDay()->toDateString()),
            '7days' => $query->whereBetween($column, [now()->subDays(6)->startOfDay(), now()->endOfDay()]),
            '30days' => $query->whereBetween($column, [now()->subDays(29)->startOfDay(), now()->endOfDay()]),
            'this_month' => $query->whereBetween($column, [now()->startOfMonth(), now()->endOfMonth()]),
            'last_month' => $query->whereBetween($column, [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()]),
            'this_year' => $query->whereBetween($column, [now()->startOfYear(), now()->endOfYear()]),
            'custom' => $query->when($from, fn ($q) => $q->whereDate($column, '>=', $from))
                ->when($to, fn ($q) => $q->whereDate($column, '<=', $to)),
            default => $query,
        };
    }
}

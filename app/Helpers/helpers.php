<?php

use App\Support\Settings;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}

if (! function_exists('rupiah')) {
    function rupiah(mixed $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }
}

if (! function_exists('tanggal')) {
    function tanggal(mixed $date, bool $withTime = false): string
    {
        if (! $date) {
            return '-';
        }
        return \Illuminate\Support\Carbon::parse($date)->translatedFormat($withTime ? 'd F Y, H:i' : 'd F Y');
    }
}

if (! function_exists('header_alerts')) {
    function header_alerts(): array
    {
        return [
            'overdue' => \App\Models\Loan::where('status', 'terlambat')->count(),
            'due_today' => \App\Models\Loan::whereIn('status', ['dipinjam', 'terlambat'])
                ->whereDate('due_date', today())->count(),
        ];
    }
}

if (! function_exists('icon')) {
    function icon(string $name, int $size = 18, string $class = ''): string
    {
        $name = preg_replace('/[^a-z0-9-]/', '', $name);
        $path = public_path("vendor/icons/{$name}.svg");
        if (! is_file($path)) {
            $path = public_path('vendor/icons/circle-help.svg');
        }
        $svg = file_get_contents($path);
        $svg = preg_replace('/<svg\s/', '<svg width="'.$size.'" height="'.$size.'" aria-hidden="true" ', $svg, 1);
        if ($class) {
            $svg = preg_replace('/class="lucide lucide-[^"]*"/', 'class="lucide '.$class.'"', $svg, 1);
        }

        return $svg;
    }
}

if (! function_exists('status_badge')) {
    function status_badge(string $status): string
    {
        $map = [
            'tersedia' => ['success', 'Tersedia'],
            'dipinjam' => ['warning', 'Dipinjam'],
            'stok_habis' => ['danger', 'Stok Habis'],
            'tidak_aktif' => ['secondary', 'Tidak Aktif'],
            'terlambat' => ['danger', 'Terlambat'],
            'selesai' => ['success', 'Selesai'],
            'dibatalkan' => ['secondary', 'Dibatalkan'],
            'belum_dibayar' => ['danger', 'Belum Dibayar'],
            'dibayar_sebagian' => ['warning', 'Dibayar Sebagian'],
            'lunas' => ['success', 'Lunas'],
            'dikembalikan' => ['success', 'Dikembalikan'],
            'aktif' => ['success', 'Aktif'],
            'nonaktif' => ['secondary', 'Nonaktif'],
            'admin' => ['primary', 'Admin'],
            'petugas' => ['info', 'Petugas'],
        ];
        [$tone, $label] = $map[$status] ?? ['secondary', ucfirst(str_replace('_', ' ', $status))];

        return '<span class="badge badge-'.$tone.'">'.$label.'</span>';
    }
}

if (! function_exists('sort_link')) {
    function sort_link(string $label, string $key): string
    {
        $current = request('sort');
        $direction = request('direction') === 'asc' ? 'asc' : 'desc';
        $newDirection = ($current === $key && $direction === 'desc') ? 'asc' : 'desc';
        $query = array_merge(request()->except('page'), ['sort' => $key, 'direction' => $newDirection]);
        $indicator = $current === $key ? ($direction === 'asc' ? ' ↑' : ' ↓') : '';

        return '<a class="sort-link" href="?'.http_build_query($query).'">'.$label.$indicator.'</a>';
    }
}

if (! function_exists('active_menu')) {
    function active_menu(string ...$patterns): string
    {
        foreach ($patterns as $pattern) {
            if (request()->is($pattern)) {
                return 'active';
            }
        }
        return '';
    }
}

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#5d361f">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ $library['library_name'] }}</title>
    <link rel="icon" href="{{ asset('vendor/icons/library-big.svg') }}">
    <link rel="stylesheet" href="{{ asset('vendor/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body class="admin-page">
@php($alerts = header_alerts())
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            @if(!empty($library['library_logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($library['library_logo']))
                <img src="{{ \Illuminate\Support\Facades\Storage::url($library['library_logo']) }}" alt="Logo">
            @else
                {!! icon('library-big', 22) !!}
            @endif
        </div>
        <div>
            <div class="brand-name">{{ Str::limit($library['library_name'], 22) }}</div>
            <div class="brand-sub">SISTEM PERPUSTAKAAN</div>
        </div>
    </div>

    <div class="sidebar-scroll">
        @if(auth()->user()->isAdmin())
            <div class="sidebar-label">Menu Utama</div>
            <ul class="nav-list">
                <li><a href="{{ route('dashboard') }}" class="{{ active_menu('dashboard') }}">{!! icon('layout-dashboard') !!} Dashboard</a></li>
                <li><a href="{{ route('books.index') }}" class="{{ active_menu('books', 'books/*') }}">{!! icon('book-open') !!} Data Buku</a></li>
                <li><a href="{{ route('catalog.index') }}" class="{{ active_menu('catalog', 'catalog/*') }}">{!! icon('library') !!} Katalog</a></li>
            </ul>
            <div class="sidebar-label">Master Data</div>
            <ul class="nav-list">
                <li><a href="{{ route('lookups.index', 'categories') }}" class="{{ request()->is('lookups/categories*') ? 'active' : '' }}">{!! icon('tags') !!} Kategori</a></li>
                <li><a href="{{ route('lookups.index', 'authors') }}" class="{{ request()->is('lookups/authors*') ? 'active' : '' }}">{!! icon('pen-line') !!} Penulis</a></li>
                <li><a href="{{ route('lookups.index', 'publishers') }}" class="{{ request()->is('lookups/publishers*') ? 'active' : '' }}">{!! icon('building-2') !!} Penerbit</a></li>
                <li><a href="{{ route('lookups.index', 'shelves') }}" class="{{ request()->is('lookups/shelves*') ? 'active' : '' }}">{!! icon('archive') !!} Rak Buku</a></li>
            </ul>
            <div class="sidebar-label">Monitoring & Laporan</div>
            <ul class="nav-list">
                <li><a href="{{ route('members.index') }}" class="{{ active_menu('members', 'members/*') }}">{!! icon('users') !!} Data Anggota</a></li>
                <li><a href="{{ route('monitoring.index') }}" class="{{ active_menu('monitoring', 'monitoring/*') }}">{!! icon('user-check') !!} Monitoring Petugas</a></li>
                <li><a href="{{ route('loans.index') }}" class="{{ request()->is('loans*') && !request()->is('loans/create') ? 'active' : '' }}">{!! icon('history') !!} Riwayat Peminjaman</a></li>
                <li><a href="{{ route('returns.index') }}" class="{{ active_menu('returns*') }}">{!! icon('rotate-ccw') !!} Riwayat Pengembalian</a></li>
                <li><a href="{{ route('fines.index') }}" class="{{ active_menu('fines*') }}">{!! icon('banknote') !!} Monitoring Denda</a></li>
                <li><a href="{{ route('logs.index') }}" class="{{ active_menu('logs') }}">{!! icon('scroll-text') !!} Log Aktivitas</a></li>
                <li><a href="{{ route('reports.index') }}" class="{{ active_menu('reports*') }}">{!! icon('chart-no-axes-combined') !!} Laporan</a></li>
            </ul>
            <div class="sidebar-label">Sistem</div>
            <ul class="nav-list">
                <li><a href="{{ route('accounts.index') }}" class="{{ active_menu('accounts*') }}">{!! icon('user-cog') !!} Manajemen Akun</a></li>
                <li><a href="{{ route('settings.index') }}" class="{{ active_menu('settings') }}">{!! icon('settings') !!} Pengaturan</a></li>
                <li><a href="{{ route('backup.index') }}" class="{{ active_menu('backup*') }}">{!! icon('database-backup') !!} Backup Database</a></li>
            </ul>
        @else
            <div class="sidebar-label">Menu Operasional</div>
            <ul class="nav-list">
                <li><a href="{{ route('dashboard') }}" class="{{ active_menu('dashboard') }}">{!! icon('layout-dashboard') !!} Dashboard</a></li>
                <li><a href="{{ route('members.index') }}" class="{{ active_menu('members', 'members/*') }}">{!! icon('users') !!} Data Anggota</a></li>
                <li><a href="{{ route('loans.create') }}" class="{{ request()->is('loans/create') ? 'active' : '' }}">{!! icon('book-marked') !!} Peminjaman</a></li>
                <li><a href="{{ route('returns.create') }}" class="{{ request()->is('returns/create') ? 'active' : '' }}">{!! icon('rotate-ccw') !!} Pengembalian</a></li>
                <li><a href="{{ route('fines.index') }}" class="{{ active_menu('fines*') }}">{!! icon('banknote') !!} Data Denda</a></li>
                <li><a href="{{ route('transactions.index') }}" class="{{ active_menu('transactions') }}">{!! icon('history') !!} Riwayat Transaksi</a></li>
            </ul>
            <div class="sidebar-label">Katalog</div>
            <ul class="nav-list">
                <li><a href="{{ route('catalog.index') }}" class="{{ active_menu('catalog', 'catalog/*') }}">{!! icon('library') !!} Katalog Buku</a></li>
                <li><a href="{{ route('profile.index') }}" class="{{ active_menu('profil') }}">{!! icon('circle-user') !!} Profil</a></li>
            </ul>
        @endif
    </div>

    <div class="sidebar-footer">
        <div class="d-flex align-items-center gap-2">
            <span class="live-dot"></span>
            <span>Sistem aktif · Mode Offline</span>
        </div>
        <div class="mt-1">{!! icon('shield-check', 12) !!} v1.0 · &copy; {{ date('Y') }} Libraria</div>
    </div>
</aside>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle" aria-label="Buka menu">{!! icon('menu', 22) !!}</button>
            <div>
                <h2 class="page-title">@yield('title', 'Dashboard')</h2>
                <p class="page-subtitle">@yield('subtitle', 'Sistem Informasi Manajemen Perpustakaan')</p>
            </div>
        </div>
        <div class="topbar-right">
            <form action="{{ route('catalog.index') }}" method="GET" class="topbar-search d-none d-lg-flex" role="search">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari buku, ISBN, penulis..." aria-label="Cari buku">
                <button type="submit" aria-label="Cari">{!! icon('search', 15) !!}</button>
            </form>
            <span class="topbar-chip d-none d-xl-inline-flex">{!! icon('calendar-days', 15) !!} {{ tanggal(now()) }}</span>
            <span class="clock-chip d-none d-lg-inline-flex" id="adminClock">{!! icon('clock', 15) !!} <span>--:--:--</span></span>

            <div class="theme-switch d-none d-md-inline-flex" role="group" aria-label="Tema tampilan">
                @foreach(['coffee' => 'Kopi', 'midnight' => 'Malam', 'emerald' => 'Hijau', 'violet' => 'Ungu'] as $key => $label)
                    <button type="button" class="theme-dot" data-theme="{{ $key }}" title="Tema {{ $label }}" aria-label="Tema {{ $label }}" aria-pressed="false"></button>
                @endforeach
            </div>

            <div class="dropdown notification-wrap">
                <a href="#" class="notif-trigger" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-label="Notifikasi">
                    {!! icon('bell', 17) !!}
                    @if($alerts['overdue'] + $alerts['due_today'] > 0)
                        <span class="notif-count">{{ $alerts['overdue'] + $alerts['due_today'] }}</span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end notif-menu">
                    <li class="notif-head"><strong>Notifikasi</strong><span class="cell-secondary">{{ $alerts['overdue'] + $alerts['due_today'] }} item</span></li>
                    <li>
                        <a class="notif-item tone-red" href="{{ route('loans.index') }}">
                            <span class="notif-ico">{!! icon('triangle-alert', 15) !!}</span>
                            <span><strong>{{ $alerts['overdue'] }} buku terlambat</strong><small>Perlu ditindaklanjuti segera</small></span>
                        </a>
                    </li>
                    <li>
                        <a class="notif-item tone-amber" href="{{ route('loans.index') }}">
                            <span class="notif-ico">{!! icon('clock', 15) !!}</span>
                            <span><strong>{{ $alerts['due_today'] }} jatuh tempo hari ini</strong><small>Pengecekan pengembalian</small></span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="dropdown">
                <a href="#" class="user-menu dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="true">
                    <span class="avatar">{{ strtoupper(Str::charAt(auth()->user()->name, 0)) }}</span>
                    <span>
                        <span class="user-name d-block">{{ auth()->user()->name }}</span>
                        <span class="user-role d-block">{{ ucfirst(auth()->user()->role) }}</span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('profile.index') }}">{!! icon('circle-user', 16) !!} Profil Saya</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit">{!! icon('log-out', 16) !!} Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show alert-soft mb-3" role="alert">
                {!! icon('circle-check', 16) !!} {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show alert-soft mb-3" role="alert">
                {!! icon('circle-alert', 16) !!} {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-soft mb-3">
                <strong>Terdapat kesalahan:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="{{ asset('vendor/js/bootstrap.bundle.min.js') }}"></script>
<script>
    (() => {
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (!sidebar || !backdrop) return;

        const close = () => {
            sidebar.classList.remove('open');
            backdrop.classList.remove('show');
        };
        const open = () => {
            sidebar.classList.add('open');
            backdrop.classList.add('show');
        };

        document.getElementById('menuToggle')?.addEventListener('click', () => {
            sidebar.classList.contains('open') ? close() : open();
        });
        backdrop.addEventListener('click', close);
        sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') close();
        });
    })();
</script>
<script src="{{ asset('js/admin-ui.js') }}"></script>
@stack('scripts')
</body>
</html>

@extends('layouts.auth')
@section('title', 'Masuk')
@section('body_class', 'auth-page login-room')

@section('body')
<div class="room is-off" id="room" data-start-on="{{ $errors->any() ? '1' : '0' }}">

    {{-- ================= Perpustakaan ================= --}}
    @php
        $shelfRows = [
            [[78, 13, '#9c5436'], [64, 11, '#d0a368'], [86, 15, '#5f6f63'], [72, 12, '#7a3f2f'], [80, 14, '#c08a58'], [68, 10, '#4b3b33'], [88, 16, '#a86a44'], [74, 12, '#d9c3a0'], [82, 13, '#6b6b4a']],
            [[70, 14, '#6f6f52'], [84, 12, '#b87a45'], [76, 16, '#8c4b32'], [90, 11, '#d8ad72'], [66, 13, '#5c6f68'], [79, 15, '#a45b3a'], [73, 10, '#4b3b33'], [87, 14, '#c49158'], [69, 12, '#7a3f2f']],
            [[88, 12, '#d9c3a0'], [74, 15, '#9c5436'], [68, 11, '#5c6f68'], [82, 14, '#b87a45'], [76, 13, '#6b6b4a'], [90, 16, '#8c4b32'], [71, 10, '#c49158'], [85, 12, '#5f6f63'], [78, 14, '#a86a44']],
            [[66, 13, '#7a3f2f'], [86, 16, '#d0a368'], [72, 11, '#4b3b33'], [80, 14, '#a45b3a'], [75, 12, '#d8ad72'], [89, 15, '#6b6b4a'], [69, 10, '#c08a58'], [83, 13, '#5c6f68'], [77, 14, '#9c5436']],
        ];
    @endphp
    <div class="room-scene">
        <div class="scene-dim" aria-hidden="true">
            <div class="scene-wall"></div>
            <div class="scene-floor"></div>
            <div class="scene-rug"></div>

            @foreach (['shelf-left', 'shelf-right'] as $sideIndex => $side)
                <div class="library-shelf {{ $side }}">
                    <div class="shelf-inner">
                        @foreach ($sideIndex === 0 ? $shelfRows : array_reverse($shelfRows) as $row)
                            <span class="shelf-row">
                                @foreach ($row as $book)
                                    <i style="--h: {{ $book[0] }}%; --w: {{ $book[1] }}px; --c: {{ $book[2] }};"></i>
                                @endforeach
                            </span>
                        @endforeach
                    </div>
                    @if ($sideIndex === 0)
                        <span class="shelf-ladder"></span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="scene-desk">
            <div class="desk-dim" aria-hidden="true">
                <span class="desk-top"></span>
                <span class="desk-apron"></span>
                <span class="desk-leg left"></span>
                <span class="desk-leg right"></span>
                <span class="desk-stack s1"></span>
                <span class="desk-stack s2"></span>
                <span class="desk-openbook"><i></i><i></i></span>
            </div>

            <div class="lamp" id="lamp">
                <span class="lamp-aura" aria-hidden="true"></span>
                <span class="lamp-cone" aria-hidden="true"></span>

                <svg class="lamp-art" viewBox="0 0 240 340" role="img" aria-hidden="true" focusable="false">
                    <defs>
                        <linearGradient id="lampShadeGrad" x1="0.1" y1="0" x2="0.85" y2="1">
                            <stop offset="0" class="shade-stop-a"/>
                            <stop offset="0.55" class="shade-stop-b"/>
                            <stop offset="1" class="shade-stop-c"/>
                        </linearGradient>
                        <linearGradient id="lampRimGrad" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" class="rim-stop-a"/>
                            <stop offset="1" class="rim-stop-b"/>
                        </linearGradient>
                        <radialGradient id="lampMouthGrad" cx="0.5" cy="0.2" r="0.85">
                            <stop offset="0" class="mouth-stop-a"/>
                            <stop offset="1" class="mouth-stop-b"/>
                        </radialGradient>
                        <linearGradient id="lampWoodGrad" x1="0" y1="0" x2="1" y2="0.2">
                            <stop offset="0" class="wood-stop-a"/>
                            <stop offset="0.5" class="wood-stop-b"/>
                            <stop offset="1" class="wood-stop-c"/>
                        </linearGradient>
                        <linearGradient id="lampBrassGrad" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0" class="brass-stop-a"/>
                            <stop offset="0.5" class="brass-stop-b"/>
                            <stop offset="1" class="brass-stop-c"/>
                        </linearGradient>
                        <radialGradient id="lampHaloGrad" cx="0.5" cy="0.5" r="0.5">
                            <stop offset="0" stop-color="rgba(255, 200, 124, .7)"/>
                            <stop offset="0.45" stop-color="rgba(255, 170, 86, .22)"/>
                            <stop offset="1" stop-color="rgba(255, 150, 60, 0)"/>
                        </radialGradient>
                    </defs>

                    <ellipse class="svg-halo" cx="120" cy="152" rx="132" ry="116" fill="url(#lampHaloGrad)"/>

                    <circle class="lamp-finial" cx="120" cy="64" r="4.5"/>
                    <path class="lamp-shade" d="M 84 82 C 97 66 143 66 156 82 L 202 190 C 162 203 78 203 38 190 Z" fill="url(#lampShadeGrad)"/>
                    <path class="shade-gloss" d="M 96 88 C 105 77 116 72 126 70 L 110 190 C 99 188 90 185 83 182 Z"/>
                    <ellipse class="shade-rim" cx="120" cy="190" rx="82" ry="13" fill="url(#lampRimGrad)"/>
                    <ellipse class="shade-mouth" cx="120" cy="187" rx="70" ry="9" fill="url(#lampMouthGrad)"/>

                    <rect class="lamp-neck" x="115" y="190" width="10" height="52" rx="4" fill="url(#lampBrassGrad)"/>
                    <ellipse class="lamp-collar" cx="120" cy="243" rx="21" ry="6.5" fill="url(#lampWoodGrad)"/>

                    <path class="lamp-base" d="M 102 246 C 101 262 92 266 92 284 C 92 304 105 313 120 313 C 135 313 148 304 148 284 C 148 266 139 262 138 246 Z" fill="url(#lampWoodGrad)"/>
                    <ellipse class="wood-ring" cx="120" cy="258" rx="16" ry="4.5"/>
                    <ellipse class="wood-ring" cx="120" cy="300" rx="27" ry="6"/>
                    <ellipse class="lamp-foot" cx="120" cy="315" rx="45" ry="10" fill="url(#lampWoodGrad)"/>
                    <rect class="lamp-plate" x="70" y="320" width="100" height="10" rx="4.5" fill="url(#lampWoodGrad)"/>
                </svg>

                <button class="lamp-cord" id="lampCord" type="button"
                        aria-pressed="false" aria-label="Tarik tali lampu ke bawah untuk menyalakan lampu"
                        title="Tarik tali lampu ke bawah">
                    <span class="cord-swing">
                        <span class="cord-string"></span>
                        <span class="cord-bead"></span>
                    </span>
                </button>

                <div class="cord-tip" id="cordTip" aria-hidden="true">
                    <span class="tip-arrow">&#8595;</span>
                    <span>Tarik ke bawah</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Cahaya & suasana ================= --}}
    <div class="room-light" aria-hidden="true"></div>
    <div class="room-vignette" aria-hidden="true"></div>
    <div class="room-dust" aria-hidden="true">
        <i style="--x:51%;--y:48%;--t:14s;--d:-2s"></i>
        <i style="--x:58%;--y:56%;--t:17s;--d:-7s"></i>
        <i style="--x:63%;--y:44%;--t:12s;--d:-4s"></i>
        <i style="--x:47%;--y:60%;--t:19s;--d:-11s"></i>
        <i style="--x:68%;--y:58%;--t:15s;--d:-9s"></i>
        <i style="--x:55%;--y:38%;--t:16s;--d:-5s"></i>
        <i style="--x:44%;--y:52%;--t:13s;--d:-1s"></i>
        <i style="--x:61%;--y:64%;--t:18s;--d:-13s"></i>
    </div>

    {{-- Petunjuk kini menempel langsung pada tali lampu --}}

    {{-- ================= Panel login ================= --}}
    <main class="room-panel" id="loginPanel" aria-labelledby="loginTitle">
        <div class="panel-top">
            <div class="panel-brand">
                <span class="panel-logo">
                    @if(!empty($library['library_logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($library['library_logo']))
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($library['library_logo']) }}" alt="Logo {{ $library['library_name'] }}">
                    @else
                        {!! icon('library-big', 22) !!}
                    @endif
                </span>
                <span class="panel-brand-text">
                    <strong>{{ $library['library_name'] }}</strong>
                    <span>Perpustakaan offline</span>
                </span>
            </div>
            <span class="panel-chip">{!! icon('lightbulb', 12) !!} Menyala</span>
        </div>

        <p class="panel-eyebrow">Selamat datang</p>
        <h1 id="loginTitle">Masuk ke perpustakaan</h1>
        <p class="panel-sub">Lampu sudah menyala. Masukkan kredensial Anda untuk melanjutkan.</p>

        @if(session('error'))
            <div class="alert alert-danger alert-soft d-flex align-items-center gap-2 mb-3">
                {!! icon('circle-alert', 15) !!} <span>{{ session('error') }}</span>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-soft d-flex align-items-center gap-2 mb-3">
                {!! icon('circle-check', 15) !!} <span>{{ session('success') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label" for="email">Alamat email</label>
                <div class="input-group">
                    <span class="input-group-text">{!! icon('mail', 17) !!}</span>
                    <input id="email" type="email" name="email" autocomplete="username"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" placeholder="nama@perpustakaan.local" required>
                </div>
                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">Kata sandi</label>
                <div class="input-group">
                    <span class="input-group-text">{!! icon('lock-keyhole', 17) !!}</span>
                    <input id="password" type="password" name="password" autocomplete="current-password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="Masukkan kata sandi" required>
                </div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex align-items-center mb-3">
                <label class="d-flex align-items-center gap-2 mb-0 small panel-remember">
                    <input type="checkbox" name="remember" value="1" class="form-check-input m-0">
                    Ingat saya di perangkat ini
                </label>
            </div>

            <button type="submit" class="btn btn-teal w-100">
                {!! icon('log-in', 17) !!} Masuk ke Sistem
            </button>
        </form>

        <p class="panel-foot">{!! icon('wifi-off', 12) !!} 100% offline &middot; {{ date('Y') }} {{ $library['library_name'] }}</p>
    </main>

    <p class="visually-hidden" id="roomStatus" role="status" aria-live="polite"></p>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/login-lamp.js') }}"></script>
@endpush

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Production System</title>

    @vite('resources/css/app.css')

    <script src="//unpkg.com/alpinejs" defer></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&family=Inter:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --red: #C0392B;
            --red-hover: #E74C3C;
            --red-light: #FDECEA;
            --red-border: rgba(192,57,43,0.2);
            --white: #FFFFFF;
            --off-white: #F8F7F5;
            --border: #E9E7E4;
            --t1: #111110;
            --t2: #5C5A58;
            --t3: #A09E9C;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--off-white);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ─── REVEAL ANIMATIONS ─── */
        .fade-up {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.7s cubic-bezier(.22,.61,.36,1), transform 0.7s cubic-bezier(.22,.61,.36,1);
        }
        .fade-left {
            opacity: 0;
            transform: translateX(-30px);
            transition: opacity 0.7s cubic-bezier(.22,.61,.36,1), transform 0.7s cubic-bezier(.22,.61,.36,1);
        }
        .fade-right {
            opacity: 0;
            transform: translateX(30px);
            transition: opacity 0.7s cubic-bezier(.22,.61,.36,1), transform 0.7s cubic-bezier(.22,.61,.36,1);
        }
        .fade-up.show, .fade-left.show, .fade-right.show {
            opacity: 1;
            transform: translate(0);
        }

        /* ─── NAVBAR ─── */
        header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 68px;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            transition: background 0.3s, backdrop-filter 0.3s;
        }
        header.scrolled {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nav-logo {
            width: 36px;
            height: 36px;
            object-fit: contain;
        }

        .nav-divider {
            width: 1px;
            height: 20px;
            background: var(--border);
        }

        .nav-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.05rem;
            font-weight: 500;
            color: var(--t1);
        }

        .nav-badge {
            font-size: 0.65rem;
            font-weight: 500;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--red);
            background: var(--red-light);
            border: 1px solid var(--red-border);
            padding: 3px 10px;
            border-radius: 20px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--t2);
            font-size: 0.82rem;
            font-weight: 400;
            text-decoration: none;
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .btn-back:hover {
            border-color: var(--red);
            color: var(--red);
            background: var(--red-light);
        }

        .btn-back svg { transition: transform 0.2s; }
        .btn-back:hover svg { transform: translateX(-2px); }

        /* ─── TOAST ─── */
        .toast-wrap {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 100;
            width: 90%;
            max-width: 400px;
        }

        .toast {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 8px;
            background: var(--white);
            border: 1px solid #F5C6C6;
            border-left: 3px solid var(--red);
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .toast-icon { color: var(--red); flex-shrink: 0; margin-top: 1px; }

        .toast-title {
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--t1);
            margin-bottom: 2px;
        }

        .toast-msg {
            font-size: 0.78rem;
            color: var(--t2);
        }

        .toast-close {
            margin-left: auto;
            background: none;
            border: none;
            color: var(--t3);
            cursor: pointer;
            font-size: 1rem;
            line-height: 1;
            padding: 0;
            transition: color 0.2s;
        }

        .toast-close:hover { color: var(--red); }

        /* ─── MAIN LAYOUT ─── */
        .main {
            flex: 1;
            display: flex;
            padding: 3rem 1.5rem;
            margin-top: 68px; /* ensures content clears the fixed header */
            min-height: calc(100vh - 68px - 60px);
        }

        .login-card {
            margin: auto; /* Centers safely without clipping top */
            width: 100%;
            max-width: 960px; /* Restored to original balanced size */
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            box-shadow: 0 8px 40px rgba(0,0,0,0.06);
            align-items: stretch; /* equal height for both panels */
            gap: 0;
        }

        /* ─── LEFT PANEL ─── */
        .panel-left {
            background: #1a1a2e;
            background-image: url('{{ asset('images/building.png') }}');
            background-size: cover;
            background-position: center;
            padding: 3.5rem 3rem; /* Original padding */
            display: flex;
            flex-direction: column;
            justify-content: center; /* Centers entire content block naturally */
            position: relative;
            overflow: hidden;
        }

        /* Dark gradient overlay — like Forgewell */
        .panel-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(
                160deg,
                rgba(15, 15, 25, 0.80) 0%,
                rgba(150, 35, 25, 0.55) 50%,
                rgba(15, 15, 25, 0.88) 100%
            );
        }

        /* Subtle noise texture */
        .panel-left::after {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.03'/%3E%3C/svg%3E");
            background-size: 200px;
            pointer-events: none;
        }

        .panel-top { position: relative; z-index: 1; }

        .panel-tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 0.75rem; /* Larger badge */
            font-weight: 600;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.9);
            background: rgba(192,57,43,0.4);
            border: 1px solid rgba(192,57,43,0.6);
            padding: 6px 16px;
            border-radius: 4px;
            margin-bottom: 1rem; /* Compact spacing */
        }

        .panel-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #FF6B6B;
            animation: pulse 2s ease-in-out infinite;
            box-shadow: 0 0 0 0 rgba(255,107,107,0.4);
        }

        @keyframes pulse {
            0%,100% { opacity:1; box-shadow: 0 0 0 0 rgba(255,107,107,0.4); }
            50% { opacity:0.7; box-shadow: 0 0 0 6px rgba(255,107,107,0); }
        }

        .panel-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.6rem; /* Much larger title */
            font-weight: 700;
            color: var(--white);
            line-height: 1.15;
            margin-bottom: 0.35rem; /* Compact spacing */
            letter-spacing: -0.01em;
            text-shadow: 0 2px 12px rgba(0,0,0,0.4);
        }

        .panel-sub {
            font-size: 1.05rem; /* Larger description */
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
            text-shadow: 0 1px 6px rgba(0,0,0,0.4);
            margin-bottom: 1.75rem; /* Compact spacing */
        }

        .panel-logo-wrap {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center; /* Center the logo */
            align-items: center;
            margin-bottom: 1.75rem; /* Compact spacing */
        }

        .panel-logo-bg {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .panel-logo-bg img {
            width: 100%;
            max-width: 180px; /* Larger logo */
            object-fit: contain;
            filter: brightness(0) invert(1);
            opacity: 0.95;
        }

        .panel-bottom {
            position: relative;
            z-index: 1;
        }

        /* ─── STATISTIC CARD ─── */
        .panel-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1px;
            background: rgba(255,255,255,0.15);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 1.25rem; /* Compact spacing */
        }

        .panel-stat {
            background: rgba(0,0,0,0.35);
            backdrop-filter: blur(8px);
            padding: 1rem 0.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .panel-stat-num {
            font-size: 1.45rem; /* Larger stat number */
            font-weight: 700;
            color: white;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .panel-stat-label {
            font-size: 0.65rem; /* Larger stat label */
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        .panel-features {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .panel-feature {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.9rem; /* Larger feature text */
            color: rgba(255,255,255,0.85);
        }

        .panel-feature-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(192,57,43,0.35);
            border: 1px solid rgba(192,57,43,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .panel-feature-icon svg {
            width: 12px;
            height: 12px;
            stroke: rgba(255,255,255,0.9);
        }

        /* ─── QA BUTTON ─── */
        .btn-qa {
            width: 100%;
            background: rgba(74,158,255,0.05);
            color: #4A9EFF;
            border: 1.5px solid rgba(74,158,255,0.3);
            border-radius: 8px;
            padding: 12px;
            font-size: 0.82rem;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            letter-spacing: 0.02em;
            text-decoration: none;
        }

        .btn-qa:hover {
            background: rgba(74,158,255,0.12);
            border-color: rgba(74,158,255,0.65);
            color: #72C0FF;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(74,158,255,0.15);
        }

        .btn-qa .qa-badge {
            font-size: 0.58rem;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            background: rgba(74,158,255,0.15);
            border: 1px solid rgba(74,158,255,0.28);
            padding: 2px 7px;
            border-radius: 20px;
            color: #7BC8FF;
        }

        /* ─── RIGHT PANEL ─── */
        .panel-right {
            padding: 3.5rem 3rem; /* Original right padding */
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-eyebrow {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.68rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--t3);
            margin-bottom: 0.6rem;
        }

        .form-eyebrow::before {
            content: '';
            width: 18px;
            height: 2px;
            background: var(--red);
            border-radius: 2px;
        }

        .form-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem; /* Original size */
            font-weight: 700;
            color: var(--t1);
            margin-bottom: 0.4rem;
            letter-spacing: -0.01em;
        }

        .form-desc {
            font-size: 0.85rem; /* Original size */
            color: var(--t2);
            margin-bottom: 2.25rem;
            line-height: 1.6;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 500;
            color: var(--t2);
            margin-bottom: 0.5rem;
            letter-spacing: 0.02em;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px; /* Original size */
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.88rem; /* Original size */
            font-family: 'Inter', sans-serif;
            color: var(--t1);
            background: var(--white);
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        .form-input::placeholder { color: var(--t3); }

        .form-input:focus {
            border-color: var(--red);
            box-shadow: 0 0 0 3px rgba(192,57,43,0.08);
        }

        .form-input:hover:not(:focus) {
            border-color: #D0CEC9;
        }

        .btn-submit {
            width: 100%;
            background: var(--red);
            color: var(--white);
            border: none;
            border-radius: 8px;
            padding: 14px; /* Original size */
            font-size: 0.88rem; /* Original size */
            font-weight: 500;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 0.5rem;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            letter-spacing: 0.02em;
        }

        .btn-submit:hover {
            background: var(--red-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(192,57,43,0.28);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit svg { transition: transform 0.2s; }
        .btn-submit:hover svg { transform: translateX(3px); }

        .form-divider {
            height: 1px;
            background: var(--border);
            margin: 1.75rem 0; /* Original size */
        }

        .form-footer {
            font-size: 0.75rem; /* Original size */
            color: var(--t3);
            text-align: center;
            line-height: 1.6;
        }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 768px) {
            header { padding: 0 1.25rem; }
            .nav-title { display: none; }
            .main { padding: 1.5rem 1rem; }
            .login-card { grid-template-columns: 1fr; }
            .panel-left { display: none; }
            .panel-right { padding: 2.5rem 1.75rem; }

            /* Mobile: show logo on right panel */
            .panel-right::before {
                content: '';
                display: block;
                width: 56px;
                height: 56px;
                background: var(--red);
                border-radius: 10px;
                margin: 0 auto 1.5rem;
            }
        }

        /* ─── FOOTER ─── */
        footer {
            background: var(--white);
            border-top: 1px solid var(--border);
            padding: 1.2rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-copy { font-size: 0.75rem; color: var(--t3); }
        .footer-dot { width: 4px; height: 4px; border-radius: 50%; background: var(--red); }

    </style>
</head>

<body>

<!-- NAVBAR -->
<header>
    <div class="nav-brand fade-up" style="transition-delay:0.05s">
        <img src="{{ asset('images/ippi.png') }}" class="nav-logo" alt="IPPI">
        <div class="nav-divider"></div>
        <span class="nav-title">Production System</span>
        <span class="nav-badge">Live</span>
    </div>

    <a href="{{ route('landing') }}" class="btn-back fade-up" style="transition-delay:0.1s">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M9 3L5 7l4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Kembali
    </a>
</header>

<!-- TOAST ERROR -->
@if(session('error'))
<div
    x-data="{ show: true }"
    x-show="show"
    x-init="setTimeout(() => show = false, 4500)"
    class="toast-wrap fade-up"
    x-data x-init="$nextTick(() => $el.classList.add('show'))"
>
    <div class="toast">
        <div class="toast-icon">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5"/>
                <path d="M8 5v4M8 11h.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            </svg>
        </div>
        <div>
            <div class="toast-title">Login Gagal</div>
            <div class="toast-msg">{{ session('error') }}</div>
        </div>
        <button @click="show = false" class="toast-close">✕</button>
    </div>
</div>
@endif

<!-- MAIN -->
<div class="main">

    <div class="login-card fade-up" x-data x-init="$nextTick(() => $el.classList.add('show'))">

        <!-- LEFT PANEL -->
        <div class="panel-left fade-left" x-data x-init="$nextTick(() => $el.classList.add('show'))">

            <div class="panel-top">
                <div class="panel-tag">
                    <div class="panel-dot"></div>
                    Smart Manufacturing Platform
                </div>
                <h1 class="panel-title">Industrial<br>Production System</h1>
                <p class="panel-sub">Platform monitoring produksi yang dirancang untuk performa dan efisiensi industri modern.</p>
            </div>

            <div class="panel-logo-wrap">
                <div class="panel-logo-bg">
                    <img src="{{ asset('images/logoippi.png') }}" alt="IPPI Logo">
                </div>
            </div>

            <div class="panel-bottom">
                <div class="panel-stats">
                    <div class="panel-stat">
                        <div class="panel-stat-num">24/7</div>
                        <div class="panel-stat-label">Live Monitor</div>
                    </div>
                    <div class="panel-stat">
                        <div class="panel-stat-num">Real</div>
                        <div class="panel-stat-label">Time Data</div>
                    </div>
                    <div class="panel-stat">
                        <div class="panel-stat-num">Auto</div>
                        <div class="panel-stat-label">Reporting</div>
                    </div>
                </div>
                <div class="panel-features">
                    <div class="panel-feature">
                        <div class="panel-feature-icon">
                            <svg viewBox="0 0 12 12" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                                <path d="M1 6.5l2.5 3L9 3"/>
                            </svg>
                        </div>
                        Production Monitoring Real-time
                    </div>
                    <div class="panel-feature">
                        <div class="panel-feature-icon">
                            <svg viewBox="0 0 12 12" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                                <path d="M1 6.5l2.5 3L9 3"/>
                            </svg>
                        </div>
                        Downtime & Quality Tracking
                    </div>
                    <div class="panel-feature">
                        <div class="panel-feature-icon">
                            <svg viewBox="0 0 12 12" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                                <path d="M1 6.5l2.5 3L9 3"/>
                            </svg>
                        </div>
                        Dashboard Terpadu & Akurat
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT PANEL -->
        <div class="panel-right fade-right" x-data x-init="$nextTick(() => $el.classList.add('show'))">

            <div class="form-eyebrow">Masuk ke Sistem</div>
            <h2 class="form-title">Selamat Datang</h2>
            <p class="form-desc">Masukkan NRP dan password Anda untuk mengakses dashboard produksi.</p>

            <form method="POST" action="{{ route('login.process') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="nrp">NRP</label>
                    <input
                        type="text"
                        id="nrp"
                        name="nrp"
                        required
                        autocomplete="username"
                        placeholder="Masukkan NRP Anda"
                        class="form-input"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Masukkan password Anda"
                        class="form-input"
                    >
                </div>

                <button type="submit" class="btn-submit">
                    Masuk ke Sistem
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M3 7h8M8 4l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <div style="text-align: center; margin: 1.25rem 0; color: var(--t3); font-size: 0.8rem; font-weight: 500; display: flex; align-items: center; gap: 10px;">
                    <div style="flex: 1; height: 1px; background: var(--border);"></div>
                    <span>atau</span>
                    <div style="flex: 1; height: 1px; background: var(--border);"></div>
                </div>

                <button type="button" onclick="openQrModal()" class="btn-submit" style="background: white; color: var(--t1); border: 1px solid var(--border); margin-top: 0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <rect x="7" y="7" width="3" height="3"></rect>
                        <rect x="14" y="7" width="3" height="3"></rect>
                        <rect x="7" y="14" width="3" height="3"></rect>
                        <rect x="14" y="14" width="3" height="3"></rect>
                    </svg>
                    Login dengan QR Code (Monitor)
                </button>

                <div style="text-align: center; margin: 1.25rem 0; color: var(--t3); font-size: 0.8rem; font-weight: 500; display: flex; align-items: center; gap: 10px;">
                    <div style="flex: 1; height: 1px; background: var(--border);"></div>
                    <span>akses sistem lain</span>
                    <div style="flex: 1; height: 1px; background: var(--border);"></div>
                </div>

                <a href="https://qa.tantechstev.com/login" class="btn-qa">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Login ke QA System
                    <span class="qa-badge">QA</span>
                </a>
            </form>

            <div class="form-divider"></div>

            <div class="form-footer">
                Hubungi administrator jika mengalami kendala login.
            </div>

        </div>

    </div>

</div>

<!-- FOOTER -->
<footer>
    <div style="display:flex;align-items:center;gap:8px;">
        <img src="{{ asset('images/ippi.png') }}" style="width:18px;height:18px;object-fit:contain;" alt="IPPI">
        <span class="footer-copy">© {{ date('Y') }} Production System — IPPI</span>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
        <div class="footer-dot"></div>
        <span class="footer-copy">Industrial Intelligence Platform</span>
    </div>
</footer>

<!-- QR Modal -->
<div id="qrModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
    <div style="background: white; padding: 2rem; border-radius: 12px; text-align: center; max-width: 400px; width: 90%; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
        <h3 style="font-family: 'Playfair Display', serif; font-size: 1.25rem; margin-bottom: 0.5rem;">Login via QR Code</h3>
        <p style="color: var(--t2); font-size: 0.85rem; margin-bottom: 1.5rem;">Buka web di HP Anda, pastikan sudah login, lalu arahkan kamera ke QR ini.</p>
        
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; display: inline-block; margin-bottom: 1rem;">
            <img id="qrImage" src="" alt="QR Code" style="width: 250px; height: 250px; object-fit: contain;">
        </div>
        
        <p id="qrStatus" style="font-weight: 500; font-size: 0.9rem; color: var(--red); margin-bottom: 1.5rem;">Membuat sesi...</p>
        
        <button type="button" onclick="closeQrModal()" style="padding: 10px 20px; border-radius: 6px; border: 1px solid var(--border); background: white; color: var(--t2); cursor: pointer; transition: all 0.2s;">
            Batalkan
        </button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const elements = document.querySelectorAll('.fade-up, .fade-left, .fade-right');
        setTimeout(() => {
            elements.forEach(el => el.classList.add('show'));
        }, 100);
    });

    // ─── QR LOGIN LOGIC ───
    let qrPollingInterval = null;
    let qrTokenHash = null;

    function openQrModal() {
        document.getElementById('qrModal').style.display = 'flex';
        generateQr();
    }

    function closeQrModal() {
        document.getElementById('qrModal').style.display = 'none';
        if (qrPollingInterval) {
            clearInterval(qrPollingInterval);
            qrPollingInterval = null;
        }
    }

    async function generateQr() {
        const qrImg = document.getElementById('qrImage');
        const statusText = document.getElementById('qrStatus');
        qrImg.src = '';
        statusText.innerText = 'Membuat sesi...';

        try {
            const res = await fetch('{{ route('device_link.create') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({})
            });
            
            const data = await res.json();
            
            if (data.success) {
                qrTokenHash = data.token_hash; 
                qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(data.scan_url)}`;
                statusText.innerText = 'Menunggu scan dari HP...';
                
                if (qrPollingInterval) clearInterval(qrPollingInterval);
                qrPollingInterval = setInterval(() => checkQrStatus(data.token_hash), 3000);
            } else {
                statusText.innerText = 'Gagal membuat QR.';
            }
        } catch (err) {
            console.error(err);
            statusText.innerText = 'Terjadi kesalahan sistem.';
        }
    }

    async function checkQrStatus(token) {
        try {
            const res = await fetch(`{{ url('/auth/device-link') }}/${token}/status`);
            const data = await res.json();
            
            if (data.success) {
                const statusText = document.getElementById('qrStatus');
                if (data.status === 'scanned') {
                    statusText.innerText = 'QR di-scan! Menunggu persetujuan...';
                } else if (data.status === 'approved') {
                    statusText.innerText = 'Disetujui! Mengalihkan...';
                    clearInterval(qrPollingInterval);
                    consumeQr(token);
                } else if (data.status === 'expired' || data.status === 'cancelled') {
                    statusText.innerText = 'Sesi kedaluwarsa atau dibatalkan.';
                    clearInterval(qrPollingInterval);
                }
            }
        } catch (err) {
            console.error(err);
        }
    }

    async function consumeQr(token) {
        try {
            const res = await fetch(`{{ url('/auth/device-link') }}/${token}/consume`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            
            const data = await res.json();
            if (data.success) {
                const statusText = document.getElementById('qrStatus');
                if (statusText) statusText.innerText = 'Login berhasil! Mengalihkan...';
                window.location.href = data.redirect_url || '/';
            } else {
                alert(data.message || 'Gagal login.');
            }
        } catch (err) {
            console.error(err);
        }
    }
</script>
</body>
</html>
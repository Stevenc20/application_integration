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
            align-items: center;
            justify-content: center;
            padding: 3rem 1.5rem;
        }

        .login-card {
            width: 100%;
            max-width: 960px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            box-shadow: 0 8px 40px rgba(0,0,0,0.06);
        }

        /* ─── LEFT PANEL ─── */
        .panel-left {
            background: var(--red);
            padding: 3.5rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        /* Subtle pattern overlay */
        .panel-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        .panel-left::after {
            content: '';
            position: absolute;
            bottom: -60px;
            right: -60px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }

        .panel-top { position: relative; z-index: 1; }

        .panel-tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 0.68rem;
            font-weight: 500;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.7);
            border: 1px solid rgba(255,255,255,0.25);
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 2rem;
        }

        .panel-dot {
            width: 5px;
            height: 5px;
            border-radius: 50%;
            background: rgba(255,255,255,0.8);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }

        .panel-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--white);
            line-height: 1.15;
            margin-bottom: 0.75rem;
            letter-spacing: -0.01em;
        }

        .panel-sub {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.65);
            line-height: 1.7;
        }

        .panel-logo-wrap {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            flex: 1;
            padding: 2rem 0;
        }

        .panel-logo-bg {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 16px;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .panel-logo-bg img {
            width: 100%;
            max-width: 180px;
            object-fit: contain;
            filter: brightness(0) invert(1);
            opacity: 0.9;
        }

        .panel-bottom {
            position: relative;
            z-index: 1;
        }

        .panel-features {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .panel-feature {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.78rem;
            color: rgba(255,255,255,0.75);
        }

        .panel-feature-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
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

        /* ─── RIGHT PANEL ─── */
        .panel-right {
            padding: 3.5rem 3rem;
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
            font-size: 2rem;
            font-weight: 700;
            color: var(--t1);
            margin-bottom: 0.4rem;
            letter-spacing: -0.01em;
        }

        .form-desc {
            font-size: 0.85rem;
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
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.88rem;
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
            padding: 14px;
            font-size: 0.88rem;
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
            margin: 1.75rem 0;
        }

        .form-footer {
            font-size: 0.75rem;
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
                    Smart Manufacturing
                </div>
                <h1 class="panel-title">Industrial Production System</h1>
                <p class="panel-sub">Platform monitoring produksi yang dirancang untuk performa dan efisiensi industri.</p>
            </div>

            <div class="panel-logo-wrap">
                <div class="panel-logo-bg">
                    <img src="{{ asset('images/logoippi.png') }}" alt="IPPI Logo">
                </div>
            </div>

            <div class="panel-bottom">
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
            <p class="form-desc">Masukkan NIP dan password Anda untuk mengakses dashboard produksi.</p>

            <form method="POST" action="{{ route('login.process') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="nip">NIP</label>
                    <input
                        type="text"
                        id="nip"
                        name="nip"
                        required
                        autocomplete="username"
                        placeholder="Masukkan NIP Anda"
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

<script>
    document.querySelectorAll('.fade-up, .fade-left, .fade-right').forEach((el, i) => {
        setTimeout(() => el.classList.add('show'), i * 80);
    });
</script>

</body>
</html>
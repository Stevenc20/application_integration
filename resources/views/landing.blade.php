<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production System</title>

    @vite('resources/css/app.css')

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
            color: var(--t1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ─── REVEAL ─── */
        .reveal, .reveal-left, .reveal-right {
            opacity: 0;
            transition: opacity 0.75s cubic-bezier(.22,.61,.36,1), transform 0.75s cubic-bezier(.22,.61,.36,1);
        }
        .reveal          { transform: translateY(28px); }
        .reveal-left     { transform: translateX(-40px); }
        .reveal-right    { transform: translateX(40px); }
        .reveal.active, .reveal-left.active, .reveal-right.active {
            opacity: 1; transform: translate(0);
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
            position: sticky;
            top: 0;
            z-index: 100;
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

        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--red);
            color: var(--white);
            padding: 10px 22px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 500;
            letter-spacing: 0.04em;
            text-decoration: none;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            background: var(--red-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(192,57,43,0.28);
        }

        .btn-login svg { transition: transform 0.2s; }
        .btn-login:hover svg { transform: translateX(3px); }

        /* ─── HERO ─── */
        .hero-wrap {
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }

        .hero {
            max-width: 1200px;
            margin: 0 auto;
            padding: 5rem 3rem 4.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5rem;
            align-items: center;
        }

        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--red);
            background: var(--red-light);
            border: 1px solid var(--red-border);
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 1.5rem;
        }

        .tag-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--red);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }

        .hero-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.4rem, 3.5vw, 3.6rem);
            font-weight: 700;
            line-height: 1.1;
            color: var(--t1);
            margin-bottom: 1.25rem;
            letter-spacing: -0.02em;
        }

        .hero-title .red {
            color: var(--red);
            position: relative;
            display: inline-block;
        }

        .hero-title .red::after {
            content: '';
            position: absolute;
            bottom: 3px;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--red);
            border-radius: 2px;
            opacity: 0.25;
        }

        .hero-desc {
            font-size: 0.95rem;
            line-height: 1.8;
            color: var(--t2);
            margin-bottom: 2.5rem;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--red);
            color: var(--white);
            padding: 13px 26px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
        }

        .btn-primary:hover {
            background: var(--red-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(192,57,43,0.28);
        }

        .btn-primary svg { transition: transform 0.2s; }
        .btn-primary:hover svg { transform: translateX(3px); }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--t2);
            font-size: 0.82rem;
            font-weight: 400;
            text-decoration: none;
            border: 1px solid var(--border);
            padding: 12px 22px;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .btn-outline:hover {
            border-color: var(--red);
            color: var(--red);
            background: var(--red-light);
        }

        /* ─── IMAGE CARD ─── */
        .hero-visual {
            background: var(--off-white);
            border: 1px solid var(--border);
            border-top: 3px solid var(--red);
            border-radius: 14px;
            padding: 3rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .hero-visual img {
            width: 100%;
            max-width: 320px;
            object-fit: contain;
        }

        .online-badge {
            position: absolute;
            bottom: 18px;
            right: 18px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 7px 13px;
            display: flex;
            align-items: center;
            gap: 7px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .online-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #27AE60;
            animation: pulse 1.8s ease-in-out infinite;
        }

        .online-text {
            font-size: 0.72rem;
            font-weight: 500;
            color: var(--t1);
        }

        /* ─── STATS ─── */
        .stats-bar {
            background: var(--red);
        }

        .stats-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 3rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .stat-item {
            padding: 1.75rem 0;
            text-align: center;
            border-right: 1px solid rgba(255,255,255,0.15);
        }

        .stat-item:last-child { border-right: none; }

        .stat-num {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--white);
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-num span { color: rgba(255,255,255,0.55); font-size: 1.4rem; font-weight: 400; }

        .stat-label {
            font-size: 0.7rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.6);
        }

        /* ─── FEATURES ─── */
        .features-wrap {
            background: var(--off-white);
        }

        .features {
            max-width: 1200px;
            margin: 0 auto;
            padding: 5rem 3rem;
        }

        .section-eyebrow {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.68rem;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--t3);
            margin-bottom: 0.6rem;
        }

        .section-eyebrow::before {
            content: '';
            width: 22px;
            height: 2px;
            background: var(--red);
            border-radius: 2px;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: clamp(1.7rem, 2.5vw, 2.3rem);
            font-weight: 700;
            color: var(--t1);
            margin-bottom: 3rem;
            max-width: 480px;
            line-height: 1.2;
            letter-spacing: -0.01em;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.25rem;
        }

        .feature-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem 1.75rem;
            position: relative;
            overflow: hidden;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            cursor: default;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--red);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s cubic-bezier(.22,.61,.36,1);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 40px rgba(0,0,0,0.08);
            border-color: var(--red-border);
        }

        .feature-card:hover::before {
            transform: scaleX(1);
        }

        .feature-num {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 700;
            color: rgba(192,57,43,0.1);
            line-height: 1;
            margin-bottom: 1rem;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: var(--red-light);
            border: 1px solid var(--red-border);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            transition: background 0.2s, border-color 0.2s;
        }

        .feature-card:hover .feature-icon {
            background: var(--red);
            border-color: var(--red);
        }

        .feature-icon svg {
            width: 18px;
            height: 18px;
            stroke: var(--red);
            transition: stroke 0.2s;
        }

        .feature-card:hover .feature-icon svg {
            stroke: var(--white);
        }

        .feature-title {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--t1);
            margin-bottom: 0.5rem;
        }

        .feature-desc {
            font-size: 0.82rem;
            line-height: 1.75;
            color: var(--t2);
        }

        /* ─── FOOTER ─── */
        footer {
            background: var(--white);
            border-top: 1px solid var(--border);
            padding: 1.5rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .footer-logo {
            width: 22px;
            height: 22px;
            object-fit: contain;
        }

        .footer-copy {
            font-size: 0.78rem;
            color: var(--t3);
        }

        .footer-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--red);
        }

        /* ─── RESPONSIVE ─── */
        @media (max-width: 900px) {
            header { padding: 0 1.5rem; }
            .nav-title { display: none; }
            .hero { grid-template-columns: 1fr; padding: 3rem 1.5rem; gap: 2.5rem; }
            .hero-desc { max-width: 100%; }
            .stats-inner { grid-template-columns: 1fr; padding: 0 1.5rem; }
            .stat-item { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.15); padding: 1.25rem 0; }
            .stat-item:last-child { border-bottom: none; }
            .features { padding: 3rem 1.5rem; }
            .features-grid { grid-template-columns: 1fr; }
            footer { flex-direction: column; gap: 0.75rem; padding: 1.25rem 1.5rem; }
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<header class="reveal">
    <div class="nav-brand">
        <img src="{{ asset('images/ippi.png') }}" class="nav-logo" alt="IPPI">
        <div class="nav-divider"></div>
        <span class="nav-title">Production System</span>
        <span class="nav-badge">Live</span>
    </div>

    <a href="{{ route('login') }}" class="btn-login">
        Login
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
            <path d="M3 7h8M8 4l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </a>
</header>

<!-- HERO -->
<section class="hero-wrap">
    <div class="hero">

        <div class="reveal-left">
            <div class="hero-tag">
                <div class="tag-dot"></div>
                Smart Manufacturing Platform
            </div>

            <h1 class="hero-title">
                Monitor. Track.<br>
                <span class="red">Control.</span>
            </h1>

            <p class="hero-desc">
                Pantau lini produksi, lacak downtime mesin, dan kendalikan kualitas produk secara real-time — dari satu dashboard yang dirancang untuk performa industri.
            </p>

            <div class="hero-actions">
                <a href="{{ route('login') }}" class="btn-primary">
                    Masuk ke Sistem
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
                        <path d="M3 7h8M8 4l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>
                <a href="#features" class="btn-outline">Lihat Fitur</a>
            </div>
        </div>

        <div class="hero-visual reveal-right">
            <img src="{{ asset('images/logoippi.png') }}" alt="IPPI Logo">
            <div class="online-badge">
                <div class="online-dot"></div>
                <span class="online-text">System Online</span>
            </div>
        </div>

    </div>
</section>

<!-- STATS -->
<div class="stats-bar reveal">
    <div class="stats-inner">
        <div class="stat-item">
            <div class="stat-num">99<span>%</span></div>
            <div class="stat-label">System Uptime</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">Real<span>-time</span></div>
            <div class="stat-label">Data Monitoring</div>
        </div>
        <div class="stat-item">
            <div class="stat-num">3<span>+</span></div>
            <div class="stat-label">Core Modules</div>
        </div>
    </div>
</div>

<!-- FEATURES -->
<section id="features" class="features-wrap">
    <div class="features">

        <div class="reveal">
            <div class="section-eyebrow">Fitur Utama</div>
            <h2 class="section-title">Semua yang dibutuhkan untuk produksi yang efisien</h2>
        </div>

        <div class="features-grid">

            <div class="feature-card reveal">
                <div class="feature-num">01</div>
                <div class="feature-icon">
                    <svg viewBox="0 0 18 18" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                        <rect x="1" y="4" width="16" height="11" rx="2"/>
                        <path d="M4 10.5l3-3.5 3 3 2.5-3L15 10.5"/>
                    </svg>
                </div>
                <div class="feature-title">Production Monitoring</div>
                <div class="feature-desc">
                    Pantau output, bandingkan target vs aktual, dan lihat performa setiap shift secara langsung.
                </div>
            </div>

            <div class="feature-card reveal">
                <div class="feature-num">02</div>
                <div class="feature-icon">
                    <svg viewBox="0 0 18 18" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                        <circle cx="9" cy="9" r="7"/>
                        <path d="M9 5.5V9.5l2.5 2.5"/>
                    </svg>
                </div>
                <div class="feature-title">Downtime Tracking</div>
                <div class="feature-desc">
                    Deteksi otomatis saat mesin berhenti, analisis penyebab, dan minimalisir keterlambatan produksi.
                </div>
            </div>

            <div class="feature-card reveal">
                <div class="feature-num">03</div>
                <div class="feature-icon">
                    <svg viewBox="0 0 18 18" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5">
                        <path d="M3.5 9l4 4 7-7"/>
                    </svg>
                </div>
                <div class="feature-title">Quality Control</div>
                <div class="feature-desc">
                    Monitor tingkat defect dan pastikan setiap produk memenuhi standar kualitas yang ditetapkan.
                </div>
            </div>

        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="reveal">
    <div class="footer-left">
        <img src="{{ asset('images/ippi.png') }}" class="footer-logo" alt="IPPI">
        <span class="footer-copy">© {{ date('Y') }} Production System — IPPI. All rights reserved.</span>
    </div>
    <div class="footer-right">
        <div class="footer-dot"></div>
        <span class="footer-copy">Industrial Intelligence Platform</span>
    </div>
</footer>

<script>
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('active');
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.reveal, .reveal-left, .reveal-right')
        .forEach(el => observer.observe(el));
</script>

</body>
</html>
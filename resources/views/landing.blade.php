<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT IPPI — Production System</title>
    <meta name="description" content="Sistem monitoring produksi real-time PT IPPI. Pantau lini stamping, downtime, dan kualitas produk dari satu platform terpadu.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    <style>
        :root {
            --red: #C0392B;
            --red-hover: #E74C3C;
            --red-light: #FDECEA;
            --red-border: rgba(192,57,43,0.18);
            --white: #FFFFFF;
            --bg: #F5F4F2;
            --border: #E2E0DD;
            --t1: #1A1918;
            --t2: #5C5A58;
            --t3: #9A9895;
            --mono: 'IBM Plex Mono', monospace;
            --sans: 'IBM Plex Sans', -apple-system, sans-serif;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--sans);
            background: var(--bg);
            color: var(--t1);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* ═══ TOPBAR ═══ */
        .topbar {
            background: var(--t1);
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .topbar-dot {
            width: 5px; height: 5px; border-radius: 50%;
            background: #27AE60;
            animation: blink 2.5s ease-in-out infinite;
        }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
        .topbar-text {
            font-family: var(--mono);
            font-size: .68rem;
            color: rgba(255,255,255,.55);
            letter-spacing: .06em;
        }
        .topbar-text strong { color: rgba(255,255,255,.85); font-weight: 500; }

        /* ═══ NAV ═══ */
        nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-left img { height: 28px; width: auto; }
        .nav-pipe { width: 1px; height: 18px; background: var(--border); }
        .nav-name {
            font-size: .8rem;
            font-weight: 600;
            color: var(--t1);
            letter-spacing: .01em;
        }
        .nav-right { display: flex; align-items: center; gap: 14px; }
        .nav-link {
            font-size: .75rem;
            font-weight: 500;
            color: var(--t2);
            text-decoration: none;
            transition: color .15s;
        }
        .nav-link:hover { color: var(--red); }
        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--red);
            color: var(--white);
            padding: 7px 18px;
            border-radius: 4px;
            font-size: .75rem;
            font-weight: 600;
            text-decoration: none;
            letter-spacing: .02em;
            transition: background .15s, box-shadow .15s;
        }
        .nav-btn:hover {
            background: var(--red-hover);
            box-shadow: 0 4px 14px rgba(192,57,43,.25);
        }

        /* ═══ HERO ═══ */
        .hero {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 3rem 2.5rem 0;
        }
        .hero-inner {
            max-width: 1120px;
            margin: 0 auto;
        }
        .hero-top {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 2rem;
            padding-bottom: 2.5rem;
        }
        .hero-text { max-width: 600px; }
        .hero-label {
            font-family: var(--mono);
            font-size: .65rem;
            font-weight: 500;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--red);
            margin-bottom: .6rem;
        }
        .hero h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            line-height: 1.08;
            letter-spacing: -.03em;
            color: var(--t1);
            margin-bottom: .8rem;
        }
        .hero h1 em {
            font-style: normal;
            color: var(--red);
            position: relative;
        }
        .hero h1 em::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: 2px;
            height: 2px;
            background: var(--red);
            opacity: .2;
        }
        .hero-sub {
            font-size: .88rem;
            line-height: 1.65;
            color: var(--t2);
            max-width: 460px;
        }
        .hero-cta {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin-top: 1.4rem;
        }
        .cta-main {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--red);
            color: var(--white);
            padding: 11px 24px;
            border-radius: 4px;
            font-size: .8rem;
            font-weight: 600;
            text-decoration: none;
            letter-spacing: .02em;
            transition: background .15s, transform .15s, box-shadow .15s;
        }
        .cta-main:hover {
            background: var(--red-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(192,57,43,.25);
        }
        .cta-ghost {
            font-size: .78rem;
            font-weight: 500;
            color: var(--t2);
            text-decoration: none;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1px;
            transition: color .15s, border-color .15s;
        }
        .cta-ghost:hover { color: var(--red); border-color: var(--red); }

        .hero-logo-area {
            flex-shrink: 0;
            width: 200px;
            height: 200px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .hero-logo-area img { width: 120px; height: auto; opacity: .85; }
        .hero-logo-area::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: var(--red);
            border-radius: 10px 10px 0 0;
        }

        /* ═══ STATS STRIP ═══ */
        .stats-strip {
            background: var(--red);
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            max-width: 1120px;
            margin: 0 auto;
            width: calc(100% - 5rem);
            border-radius: 0 0 6px 6px;
        }
        .stat {
            padding: 1rem 0;
            text-align: center;
            border-right: 1px solid rgba(255,255,255,.12);
        }
        .stat:last-child { border-right: none; }
        .stat-val {
            font-family: var(--mono);
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--white);
            line-height: 1;
        }
        .stat-val small {
            font-size: .85rem;
            font-weight: 400;
            opacity: .55;
        }
        .stat-lbl {
            font-size: .62rem;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: rgba(255,255,255,.5);
            margin-top: 4px;
        }

        /* ═══ MODULES ═══ */
        .modules {
            max-width: 1120px;
            margin: 0 auto;
            padding: 2.5rem 2.5rem 0;
        }
        .modules-head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .mod-label {
            font-family: var(--mono);
            font-size: .62rem;
            font-weight: 500;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: var(--t3);
        }
        .mod-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--t1);
            letter-spacing: -.02em;
        }
        .mod-count {
            font-family: var(--mono);
            font-size: .7rem;
            color: var(--t3);
        }

        .mod-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            border: 1px solid var(--border);
            border-radius: 6px;
            overflow: hidden;
            background: var(--white);
        }
        .mod-card {
            padding: 1.5rem 1.4rem;
            border-right: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            position: relative;
            transition: background .15s;
            cursor: default;
        }
        .mod-card:nth-child(3n) { border-right: none; }
        .mod-card:nth-last-child(-n+3) { border-bottom: none; }
        .mod-card:hover { background: var(--red-light); }
        .mod-card:hover .mod-card-num { color: var(--red); }
        .mod-card:hover .mod-card-ico { background: var(--red); border-color: var(--red); }
        .mod-card:hover .mod-card-ico svg { stroke: var(--white); }

        .mod-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: .8rem;
        }
        .mod-card-num {
            font-family: var(--mono);
            font-size: .7rem;
            font-weight: 500;
            color: var(--t3);
            transition: color .15s;
        }
        .mod-card-ico {
            width: 32px; height: 32px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background .15s, border-color .15s;
        }
        .mod-card-ico svg { width: 16px; height: 16px; stroke: var(--t2); stroke-width: 1.5; fill: none; transition: stroke .15s; }
        .mod-card h3 {
            font-size: .82rem;
            font-weight: 600;
            color: var(--t1);
            margin-bottom: .3rem;
            letter-spacing: -.01em;
        }
        .mod-card p {
            font-size: .74rem;
            line-height: 1.55;
            color: var(--t2);
        }

        /* ═══ BOTTOM BANNER ═══ */
        .bottom-banner {
            max-width: 1120px;
            margin: 0 auto;
            padding: 2rem 2.5rem;
        }
        .banner-inner {
            background: var(--t1);
            border-radius: 6px;
            padding: 1.8rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }
        .banner-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .banner-ico {
            width: 40px; height: 40px;
            background: rgba(192,57,43,.2);
            border: 1px solid rgba(192,57,43,.35);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .banner-ico svg { width: 20px; height: 20px; stroke: var(--red-hover); stroke-width: 1.5; fill: none; }
        .banner-text h4 {
            font-size: .85rem;
            font-weight: 600;
            color: var(--white);
            margin-bottom: 2px;
        }
        .banner-text p {
            font-size: .72rem;
            color: rgba(255,255,255,.45);
        }
        .banner-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--red);
            color: var(--white);
            padding: 9px 20px;
            border-radius: 4px;
            font-size: .75rem;
            font-weight: 600;
            text-decoration: none;
            flex-shrink: 0;
            transition: background .15s;
        }
        .banner-btn:hover { background: var(--red-hover); }

        /* ═══ FOOTER ═══ */
        footer {
            background: var(--white);
            border-top: 1px solid var(--border);
            margin-top: auto;
            padding: 1rem 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .foot-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .foot-left img { height: 18px; width: auto; opacity: .5; }
        .foot-copy {
            font-size: .68rem;
            color: var(--t3);
        }
        .foot-right {
            font-family: var(--mono);
            font-size: .62rem;
            color: var(--t3);
            letter-spacing: .06em;
        }

        /* ═══ RESPONSIVE ═══ */
        @media (max-width: 860px) {
            nav { padding: 0 1.2rem; }
            .nav-name { display: none; }
            .hero { padding: 2rem 1.2rem 0; }
            .hero-top { flex-direction: column; align-items: flex-start; gap: 1.5rem; padding-bottom: 1.8rem; }
            .hero-logo-area { width: 140px; height: 140px; }
            .hero-logo-area img { width: 80px; }
            .stats-strip { width: calc(100% - 2.4rem); grid-template-columns: repeat(2, 1fr); }
            .stat:nth-child(2) { border-right: none; }
            .stat:nth-child(1), .stat:nth-child(2) { border-bottom: 1px solid rgba(255,255,255,.12); }
            .modules { padding: 2rem 1.2rem 0; }
            .mod-grid { grid-template-columns: 1fr; }
            .mod-card { border-right: none !important; }
            .mod-card:last-child { border-bottom: none; }
            .bottom-banner { padding: 1.5rem 1.2rem; }
            .banner-inner { flex-direction: column; text-align: center; padding: 1.5rem; }
            .banner-left { flex-direction: column; }
            footer { flex-direction: column; gap: .5rem; padding: .8rem 1.2rem; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-dot"></div>
    <span class="topbar-text"><strong>SYS ONLINE</strong> — manufacturing.tantechstev.com</span>
</div>

<!-- NAV -->
<nav>
    <div class="nav-left">
        <img src="{{ asset('images/ippi.png') }}" alt="IPPI">
        <div class="nav-pipe"></div>
        <span class="nav-name">Production System</span>
    </div>
    <div class="nav-right">
        <a href="#modules" class="nav-link">Modules</a>
        <a href="{{ route('login') }}" class="nav-btn">
            Login
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2.5 6h7M7 3.5L9.5 6 7 8.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-top">
            <div class="hero-text">
                <div class="hero-label">// Manufacturing Execution System</div>
                <h1>Monitor. Track.<br><em>Control.</em></h1>
                <p class="hero-sub">Pantau lini produksi, lacak downtime mesin, dan kendalikan kualitas produk secara real-time dari satu platform terpadu.</p>
                <div class="hero-cta">
                    <a href="{{ route('login') }}" class="cta-main">
                        Masuk ke Sistem
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7h8M8 4l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                    <a href="#modules" class="cta-ghost">Lihat modul →</a>
                </div>
            </div>
            <div class="hero-logo-area">
                <img src="{{ asset('images/logoippi.png') }}" alt="PT IPPI">
            </div>
        </div>
    </div>
    <!-- STATS attached to hero -->
    <div class="stats-strip">
        <div class="stat">
            <div class="stat-val">99<small>%</small></div>
            <div class="stat-lbl">Uptime</div>
        </div>
        <div class="stat">
            <div class="stat-val">4</div>
            <div class="stat-lbl">Press Lines</div>
        </div>
        <div class="stat">
            <div class="stat-val">Real<small>-time</small></div>
            <div class="stat-lbl">Monitoring</div>
        </div>
        <div class="stat">
            <div class="stat-val">24<small>/7</small></div>
            <div class="stat-lbl">Operation</div>
        </div>
    </div>
</section>

<!-- MODULES -->
<section id="modules" class="modules">
    <div class="modules-head">
        <div>
            <div class="mod-label">// Core Modules</div>
            <div class="mod-title">Modul Sistem</div>
        </div>
        <div class="mod-count">6 modules</div>
    </div>
    <div class="mod-grid">
        <div class="mod-card">
            <div class="mod-card-top">
                <span class="mod-card-num">01</span>
                <div class="mod-card-ico">
                    <svg viewBox="0 0 18 18" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="16" height="11" rx="2"/><path d="M4 10.5l3-3.5 3 3 2.5-3L15 10.5"/></svg>
                </div>
            </div>
            <h3>Production Monitoring</h3>
            <p>Pantau output aktual vs target setiap shift dan lini secara langsung.</p>
        </div>
        <div class="mod-card">
            <div class="mod-card-top">
                <span class="mod-card-num">02</span>
                <div class="mod-card-ico">
                    <svg viewBox="0 0 18 18" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="9" r="7"/><path d="M9 5.5V9.5l2.5 2.5"/></svg>
                </div>
            </div>
            <h3>Downtime Tracking</h3>
            <p>Deteksi dan analisis penyebab berhentinya mesin untuk minimalisir delay.</p>
        </div>
        <div class="mod-card">
            <div class="mod-card-top">
                <span class="mod-card-num">03</span>
                <div class="mod-card-ico">
                    <svg viewBox="0 0 18 18" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 9l4 4 7-7"/></svg>
                </div>
            </div>
            <h3>Quality Control</h3>
            <p>Lembar inspeksi digital, monitoring defect, dan laporan QPR terpadu.</p>
        </div>
        <div class="mod-card">
            <div class="mod-card-top">
                <span class="mod-card-num">04</span>
                <div class="mod-card-ico">
                    <svg viewBox="0 0 18 18" stroke-linecap="round" stroke-linejoin="round"><path d="M2 4h14M2 9h14M2 14h8"/></svg>
                </div>
            </div>
            <h3>PPC & Planning</h3>
            <p>Manajemen jadwal produksi, BOM, dan rundown press stamping.</p>
        </div>
        <div class="mod-card">
            <div class="mod-card-top">
                <span class="mod-card-num">05</span>
                <div class="mod-card-ico">
                    <svg viewBox="0 0 18 18" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="5" height="5" rx="1"/><rect x="11" y="2" width="5" height="5" rx="1"/><rect x="2" y="11" width="5" height="5" rx="1"/><rect x="11" y="11" width="5" height="5" rx="1"/></svg>
                </div>
            </div>
            <h3>Role-Based Dashboard</h3>
            <p>Dashboard spesifik untuk Operator, Supervisor, Manager, hingga Direktur.</p>
        </div>
        <div class="mod-card">
            <div class="mod-card-top">
                <span class="mod-card-num">06</span>
                <div class="mod-card-ico">
                    <svg viewBox="0 0 18 18" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2v14M2 9h14"/><circle cx="9" cy="9" r="7"/></svg>
                </div>
            </div>
            <h3>Data Integration</h3>
            <p>Sinkronisasi data antar modul produksi, quality, dan logistik secara otomatis.</p>
        </div>
    </div>
</section>

<!-- BOTTOM BANNER -->
<div class="bottom-banner">
    <div class="banner-inner">
        <div class="banner-left">
            <div class="banner-ico">
                <svg viewBox="0 0 20 20" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zM10 6v4l3 2"/></svg>
            </div>
            <div class="banner-text">
                <h4>Sistem berjalan 24/7</h4>
                <p>Akses kapan saja dari perangkat manapun yang terhubung ke jaringan.</p>
            </div>
        </div>
        <a href="{{ route('login') }}" class="banner-btn">
            Login Sekarang
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2.5 6h7M7 3.5L9.5 6 7 8.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="foot-left">
        <img src="{{ asset('images/ippi.png') }}" alt="IPPI">
        <span class="foot-copy">© {{ date('Y') }} PT IPPI — Production System</span>
    </div>
    <span class="foot-right">manufacturing.tantechstev.com</span>
</footer>

</body>
</html>
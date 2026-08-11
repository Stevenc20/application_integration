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
            height: 34px;
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
            font-size: .65rem;
            color: rgba(255,255,255,.55);
            letter-spacing: .06em;
        }
        .topbar-text strong { color: rgba(255,255,255,.85); font-weight: 500; }

        /* ═══ NAV ═══ */
        nav {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-left { display: flex; align-items: center; gap: 10px; }
        .nav-left img { height: 26px; width: auto; }
        .nav-pipe { width: 1px; height: 16px; background: var(--border); }
        .nav-name { font-size: .78rem; font-weight: 600; color: var(--t1); }
        .nav-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--red); color: var(--white);
            padding: 6px 16px; border-radius: 4px;
            font-size: .73rem; font-weight: 600; text-decoration: none;
            transition: background .15s, box-shadow .15s;
        }
        .nav-btn:hover { background: var(--red-hover); box-shadow: 0 4px 14px rgba(192,57,43,.25); }

        /* ═══ HERO ═══ */
        .hero {
            background: var(--white);
            padding: 2.5rem 2.5rem 2rem;
        }
        .hero-inner {
            max-width: 1120px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }
        .hero-text { max-width: 560px; }
        .hero h1 {
            font-size: clamp(2rem, 4vw, 2.8rem);
            font-weight: 700;
            line-height: 1.05;
            letter-spacing: -.03em;
            color: var(--t1);
            margin-bottom: .6rem;
        }
        .hero h1 em { font-style: normal; color: var(--red); }
        .hero-sub {
            font-size: .85rem;
            line-height: 1.6;
            color: var(--t2);
            max-width: 420px;
            margin-bottom: 1.2rem;
        }
        .hero-cta { display: flex; align-items: center; gap: .75rem; }
        .cta-main {
            display: inline-flex; align-items: center; gap: 8px;
            background: var(--red); color: var(--white);
            padding: 10px 22px; border-radius: 4px;
            font-size: .78rem; font-weight: 600; text-decoration: none;
            transition: background .15s, transform .15s, box-shadow .15s;
        }
        .cta-main:hover { background: var(--red-hover); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(192,57,43,.25); }
        .cta-ghost {
            font-size: .76rem; font-weight: 500; color: var(--t2); text-decoration: none;
            border-bottom: 1px solid var(--border); padding-bottom: 1px;
            transition: color .15s, border-color .15s;
        }
        .cta-ghost:hover { color: var(--red); border-color: var(--red); }

        .hero-logo-area {
            flex-shrink: 0; width: 180px; height: 180px;
            background: var(--bg); border: 1px solid var(--border); border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .hero-logo-area img { width: 110px; height: auto; opacity: .85; }
        .hero-logo-area::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0;
            height: 3px; background: var(--red); border-radius: 8px 8px 0 0;
        }

        /* ═══ STATS ═══ */
        .stats {
            background: var(--red);
            display: grid;
            grid-template-columns: repeat(4, 1fr);
        }
        .stat {
            padding: .85rem 0;
            text-align: center;
            border-right: 1px solid rgba(255,255,255,.12);
        }
        .stat:last-child { border-right: none; }
        .stat-val {
            font-family: var(--mono);
            font-size: 1.35rem; font-weight: 700; color: var(--white); line-height: 1;
        }
        .stat-val small { font-size: .8rem; font-weight: 400; opacity: .55; }
        .stat-lbl { font-size: .58rem; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.5); margin-top: 3px; }

        /* ═══ FEATURES STRIP ═══ */
        .features-strip {
            background: var(--white);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        .features-inner {
            max-width: 1120px;
            margin: 0 auto;
            padding: 1.4rem 2.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0;
        }
        .feat-col {
            flex: 1;
            padding: 0 1.5rem;
            border-right: 1px solid var(--border);
        }
        .feat-col:first-child { padding-left: 0; }
        .feat-col:last-child { border-right: none; padding-right: 0; }
        .feat-col h3 {
            font-size: .78rem;
            font-weight: 600;
            color: var(--t1);
            margin-bottom: .25rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .feat-col h3 svg {
            width: 14px; height: 14px; stroke: var(--red); stroke-width: 1.5; fill: none; flex-shrink: 0;
        }
        .feat-col p {
            font-size: .7rem;
            line-height: 1.5;
            color: var(--t2);
            padding-left: 20px;
        }

        /* ═══ BOTTOM BANNER ═══ */
        .bottom-banner {
            background: var(--t1);
            padding: 1.1rem 2.5rem;
            display: flex; align-items: center; justify-content: center; gap: 1.5rem;
        }
        .banner-text { font-size: .78rem; font-weight: 500; color: rgba(255,255,255,.7); }
        .banner-text strong { color: var(--white); }
        .banner-btn {
            display: inline-flex; align-items: center; gap: 6px;
            background: var(--red); color: var(--white);
            padding: 7px 16px; border-radius: 4px;
            font-size: .72rem; font-weight: 600; text-decoration: none; flex-shrink: 0;
            transition: background .15s;
        }
        .banner-btn:hover { background: var(--red-hover); }

        /* ═══ FOOTER ═══ */
        footer {
            background: var(--white);
            border-top: 1px solid var(--border);
            margin-top: auto;
            padding: .7rem 2.5rem;
            display: flex; align-items: center; justify-content: space-between;
        }
        .foot-left { display: flex; align-items: center; gap: 8px; }
        .foot-left img { height: 16px; width: auto; opacity: .5; }
        .foot-copy { font-size: .65rem; color: var(--t3); }
        .foot-right { font-family: var(--mono); font-size: .6rem; color: var(--t3); letter-spacing: .06em; }

        /* ═══ RESPONSIVE ═══ */
        @media (max-width: 860px) {
            nav { padding: 0 1.2rem; }
            .nav-name { display: none; }
            .hero { padding: 1.5rem 1.2rem; }
            .hero-inner { flex-direction: column; align-items: flex-start; gap: 1.2rem; }
            .hero-logo-area { width: 120px; height: 120px; }
            .hero-logo-area img { width: 70px; }
            .stats { grid-template-columns: repeat(2, 1fr); }
            .stat:nth-child(2) { border-right: none; }
            .stat:nth-child(1), .stat:nth-child(2) { border-bottom: 1px solid rgba(255,255,255,.12); }
            .features-inner { flex-direction: column; padding: 1rem 1.2rem; gap: .8rem; }
            .feat-col { padding: 0; border-right: none; border-bottom: 1px solid var(--border); padding-bottom: .8rem; }
            .feat-col:last-child { border-bottom: none; padding-bottom: 0; }
            .bottom-banner { flex-direction: column; text-align: center; padding: 1rem 1.2rem; gap: .8rem; }
            footer { flex-direction: column; gap: .4rem; padding: .6rem 1.2rem; }
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
    <a href="{{ route('login') }}" class="nav-btn">
        Login
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2.5 6h7M7 3.5L9.5 6 7 8.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
</nav>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-text">
            <h1>Monitor. Track.<br><em>Control.</em></h1>
            <p class="hero-sub">Pantau lini produksi, lacak downtime mesin, dan kendalikan kualitas produk secara real-time dari satu platform terpadu.</p>
            <div class="hero-cta">
                <a href="{{ route('login') }}" class="cta-main">
                    Masuk ke Sistem
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7h8M8 4l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
                <a href="{{ route('login') }}" class="cta-ghost">Pelajari lebih lanjut →</a>
            </div>
        </div>
        <div class="hero-logo-area">
            <img src="{{ asset('images/logoippi.png') }}" alt="PT IPPI">
        </div>
    </div>
</section>

<!-- STATS -->
<div class="stats">
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

<!-- FEATURES - inline columns, no cards -->
<div class="features-strip">
    <div class="features-inner">
        <div class="feat-col">
            <h3>
                <svg viewBox="0 0 18 18" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="16" height="11" rx="2"/><path d="M4 10.5l3-3.5 3 3 2.5-3L15 10.5"/></svg>
                Production Monitoring
            </h3>
            <p>Output aktual vs target per shift dan lini produksi.</p>
        </div>
        <div class="feat-col">
            <h3>
                <svg viewBox="0 0 18 18" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="9" r="7"/><path d="M9 5.5V9.5l2.5 2.5"/></svg>
                Downtime Tracking
            </h3>
            <p>Deteksi dan analisis penyebab berhentinya mesin.</p>
        </div>
        <div class="feat-col">
            <h3>
                <svg viewBox="0 0 18 18" stroke-linecap="round" stroke-linejoin="round"><path d="M3.5 9l4 4 7-7"/></svg>
                Quality Control
            </h3>
            <p>Inspeksi digital, monitoring defect, dan laporan QPR.</p>
        </div>
    </div>
</div>

<!-- BOTTOM BANNER -->
<div class="bottom-banner">
    <span class="banner-text"><strong>Sistem berjalan 24/7</strong> — akses dari perangkat manapun</span>
    <a href="{{ route('login') }}" class="banner-btn">
        Login Sekarang
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M2.5 6h7M7 3.5L9.5 6 7 8.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </a>
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
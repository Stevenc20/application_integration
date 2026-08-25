<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT IPPI — Production System</title>
    <meta name="description" content="Sistem monitoring produksi real-time PT IPPI.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,700;1,700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --red:       #C0392B;
            --red-hover: #E74C3C;
            --red-glow:  rgba(192,57,43,0.28);
            --white:     #FFFFFF;
            --dark:      #0C1018;
            --dark-mid:  #101620;
            --dark-nav:  #0E1420;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            background: var(--dark);
            color: var(--white);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── REVEAL ── */
        .reveal {
            opacity: 0;
            transform: translate3d(0, 28px, 0);
            transition: opacity 0.9s cubic-bezier(0.22,0.61,0.36,1),
                        transform 0.9s cubic-bezier(0.22,0.61,0.36,1);
            will-change: opacity, transform;
        }
        .reveal.active { opacity: 1; transform: translate3d(0,0,0); }
        .d1 { transition-delay: 0.12s; }
        .d2 { transition-delay: 0.26s; }
        .d3 { transition-delay: 0.40s; }
        .d4 { transition-delay: 0.54s; }
        .d5 { transition-delay: 0.68s; }

        /* ════════════════════════════════════════
           NAVBAR — white, matches footer & login
        ════════════════════════════════════════ */
        header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 200;
            height: 66px;
            padding: 0 3rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #FFFFFF;
            border-bottom: 1px solid #E8E6E2;
            transition: box-shadow 0.3s ease;
        }
        header.scrolled {
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
        }

        .nav-brand { display: flex; align-items: center; gap: 11px; }
        .nav-logo  { width: 32px; height: 32px; object-fit: contain; }
        .nav-divider {
            width: 1px; height: 17px;
            background: #E2E0DC;
        }
        .nav-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.02rem;
            font-weight: 500;
            color: #111110;
        }
        .nav-badge {
            font-size: 0.59rem;
            font-weight: 600;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #C0392B;
            background: rgba(192,57,43,0.07);
            border: 1px solid rgba(192,57,43,0.22);
            padding: 3px 9px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .nav-badge::before {
            content: '';
            width: 5px; height: 5px;
            border-radius: 50%;
            background: #C0392B;
            animation: livepulse 2.4s ease-in-out infinite;
        }
        @keyframes livepulse {
            0%,100%{ opacity:1; } 50%{ opacity:0.3; }
        }

        /* ════════════════════════════════════════
           HERO — photo + multi-stop natural overlay
        ════════════════════════════════════════ */
        .hero {
            position: relative;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Layer 1: Photo */
        .hero-bg {
            position: absolute;
            inset: 0;
            background-image: url("{{ asset('images/building.png') }}");
            background-size: cover;
            background-position: center 28%;
            /* Photo feels warmer / slightly darker from the sky */
            filter: saturate(0.85) brightness(0.96);
            transform: scale(1.05);
            transition: transform 14s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        .hero-bg.ready { transform: scale(1); }

        /* Layer 2: Tonal gradient — top dark navy, middle breathes, bottom fades to white */
        .hero-overlay {
            position: absolute;
            inset: 0;
            background:
                /* Left vignette */
                linear-gradient(95deg,
                    rgba(9,13,21,0.45) 0%,
                    rgba(9,13,21,0.04) 55%,
                    transparent 100%
                ),
                /* Main vertical — top dark, middle thin, bottom to white */
                linear-gradient(180deg,
                    rgba(10,15,24,0.82)  0%,
                    rgba(12,18,28,0.46)  20%,
                    rgba(13,19,30,0.36)  38%,
                    rgba(13,19,30,0.42)  55%,
                    rgba(240,238,234,0.72) 80%,
                    rgba(245,243,240,0.96) 92%,
                    rgba(248,246,243,1.00) 100%
                );
        }

        /* Layer 3: Film grain — gives photo-overlay a unified texture */
        .hero-grain {
            position: absolute;
            inset: 0;
            opacity: 0.028;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cfilter id='g'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.75' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23g)'/%3E%3C/svg%3E");
            background-size: 256px;
            pointer-events: none;
        }

        /* ── Hero text area ── */
        .hero-content {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            /* Extra top padding so headline clears the white navbar */
            padding: 140px 2rem 0;
            text-align: center;
        }

        .hero-box { max-width: 680px; width: 100%; }

        /* Label */
        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            font-size: 0.67rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.48);
            margin-bottom: 1.5rem;
        }
        .hero-label .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: var(--red);
            box-shadow: 0 0 0 0 rgba(192,57,43,0.55);
            animation: dotglow 2.6s cubic-bezier(0.4,0,0.6,1) infinite;
        }
        @keyframes dotglow {
            0%  { box-shadow: 0 0 0 0   rgba(192,57,43,0.55); }
            60% { box-shadow: 0 0 0 7px rgba(192,57,43,0);    }
            100%{ box-shadow: 0 0 0 0   rgba(192,57,43,0);    }
        }

        /* Headline */
        .hero-h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(3rem, 5.8vw, 4.8rem);
            font-weight: 700;
            line-height: 1.07;
            letter-spacing: -0.03em;
            color: var(--white);
            margin-bottom: 1.25rem;
            text-shadow: 0 2px 28px rgba(0,0,0,0.30);
        }
        .hero-h1 em {
            font-style: italic;
            color: var(--red);
            /* warm glow so red feels embedded in the scene, not a foreign element */
            text-shadow:
                0 0 50px rgba(192,57,43,0.32),
                0 2px 24px rgba(0,0,0,0.35);
        }

        /* Desc */
        .hero-desc {
            font-size: 0.98rem;
            line-height: 1.78;
            color: rgba(255,255,255,0.56);
            max-width: 500px;
            margin: 0 auto 2.8rem;
            font-weight: 400;
        }

        /* CTA */
        .hero-cta {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--red);
            color: var(--white);
            padding: 15px 38px;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            letter-spacing: 0.025em;
            box-shadow:
                0 4px 24px rgba(192,57,43,0.28),
                inset 0 1px 0 rgba(255,255,255,0.10);
            position: relative;
            transition: background 0.2s, transform 0.25s cubic-bezier(0.22,0.61,0.36,1), box-shadow 0.25s;
        }
        .hero-cta::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            border: 1px solid rgba(255,255,255,0.09);
        }
        .hero-cta:hover {
            background: var(--red-hover);
            transform: translateY(-3px);
            box-shadow: 0 14px 36px rgba(192,57,43,0.36), inset 0 1px 0 rgba(255,255,255,0.12);
        }
        .hero-cta:active { transform: translateY(-1px); }
        .hero-cta svg { transition: transform 0.2s; flex-shrink: 0; }
        .hero-cta:hover svg { transform: translateX(4px); }

        /* ════════════════════════════════════════
           FEATURE ROW — lives inside the hero dark zone
        ════════════════════════════════════════ */
        .hero-features {
            position: relative;
            z-index: 10;
        }

        .feat-inner {
            max-width: 740px;
            margin: 0 auto;
            padding: 1.75rem 2rem 2.75rem;
            display: flex;
            justify-content: center;
            gap: 0;
            border-top: 1px solid rgba(0,0,0,0.07);
        }

        .feat-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 6px;
            padding: 0 2rem;
            text-align: left;
        }
        .feat-item + .feat-item {
            border-left: 1px solid rgba(0,0,0,0.06);
        }

        .feat-icon {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 3px;
        }
        .feat-icon svg {
            width: 15px; height: 15px;
            stroke: var(--red);
            stroke-width: 2;
            fill: none;
            flex-shrink: 0;
            opacity: 0.9;
        }
        .feat-name {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--red);
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .feat-desc {
            font-size: 0.76rem;
            color: #5A5856;
            line-height: 1.6;
        }

        /* ════════════════════════════════════════
           FOOTER — white, bridges to login page
        ════════════════════════════════════════ */
        footer {
            position: relative;
            z-index: 10;
            background: #FFFFFF;
            border-top: 1px solid #E8E6E2;
            padding: 1.35rem 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .foot-left { display: flex; align-items: center; gap: 9px; }
        .foot-left img { width: 19px; height: 19px; object-fit: contain; }
        .foot-copy { font-size: 0.73rem; color: #A09E9C; }
        .foot-right { font-size: 0.7rem; color: #C8C6C2; letter-spacing: 0.09em; text-transform: uppercase; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            header { padding: 0 1.25rem; }
            .hero-content { padding: 96px 1.5rem 0; }
            .hero-desc { font-size: 0.88rem; }
            .feat-inner { flex-direction: column; gap: 0; padding: 1.5rem 1.5rem 2.25rem; }
            .feat-item {
                padding: 1.25rem 0 0;
                border-left: none !important;
                border-top: 1px solid rgba(255,255,255,0.06);
            }
            .feat-item:first-child { border-top: none; padding-top: 0; }
            footer { padding: 1.2rem 1.5rem; flex-direction: column; gap: 0.5rem; text-align: center; }
            .foot-right { display: none; }
        }
    </style>
</head>
<body>

<!-- ═══ NAVBAR ═══ -->
<header id="main-header">
    <div class="nav-brand">
        <img src="{{ asset('images/ippi.png') }}" class="nav-logo" alt="IPPI">
        <div class="nav-divider"></div>
        <span class="nav-title">Production System</span>
        <span class="nav-badge">Live</span>
    </div>
</header>

<!-- ═══ HERO ═══ -->
<section class="hero">

    <div class="hero-bg" id="heroBg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-grain"></div>

    <!-- Headline + CTA -->
    <div class="hero-content">
        <div class="hero-box">

            <div class="hero-label reveal">
                <span class="dot"></span>
                Smart Manufacturing Platform
            </div>

            <h1 class="hero-h1 reveal d1">
                Monitor. Track.<br><em>Control.</em>
            </h1>

            <p class="hero-desc reveal d2">
                Pantau lini produksi, lacak downtime mesin, dan kendalikan kualitas produk
                secara real-time dari satu platform terpadu.
            </p>

            <a href="{{ route('login') }}" class="hero-cta reveal d3">
                Masuk ke Sistem
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                    <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>

        </div>
    </div>

    <!-- Feature row — lives inside hero's dark gradient base -->
    <div class="hero-features">
        <div class="feat-inner">

            <div class="feat-item reveal d3">
                <div class="feat-icon">
                    <svg viewBox="0 0 24 24"><path d="M3 3v18h18M18 9l-5 5-4-4-5 5"/></svg>
                    <span class="feat-name">Production</span>
                </div>
                <p class="feat-desc">Output aktual vs target per shift dan lini produksi.</p>
            </div>

            <div class="feat-item reveal d4">
                <div class="feat-icon">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    <span class="feat-name">Downtime</span>
                </div>
                <p class="feat-desc">Deteksi otomatis dan analisis penyebab berhentinya mesin.</p>
            </div>

            <div class="feat-item reveal d5">
                <div class="feat-icon">
                    <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14M22 4L12 14.01l-3-3"/></svg>
                    <span class="feat-name">Quality</span>
                </div>
                <p class="feat-desc">Inspeksi digital, monitoring defect, dan laporan terpadu.</p>
            </div>

        </div>
    </div>

</section>

<!-- ═══ FOOTER ═══ -->
<footer>
    <div class="foot-left">
        <img src="{{ asset('images/ippi.png') }}" alt="IPPI">
        <span class="foot-copy">© {{ date('Y') }} PT IPPI. Created by Steven Christian.</span>
    </div>
    <span class="foot-right">Industrial Intelligence Platform</span>
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function () {

        // Reveal on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('active'); });
        }, { threshold: 0.06 });
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));

        // Navbar scroll state
        const header = document.getElementById('main-header');
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 28);
        }, { passive: true });

        // Hero bg: load image then trigger scale-in
        const bg = document.getElementById('heroBg');
        const img = new Image();
        img.onload = () => bg.classList.add('ready');
        img.src = "{{ asset('images/building.png') }}";

    });
</script>

</body>
</html>
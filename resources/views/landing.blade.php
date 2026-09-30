<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT IPPI — Production System</title>
    <meta name="description" content="Sistem monitoring produksi real-time PT IPPI.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ asset('images/ippi.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --red:       #C0392B;
            --red-hover: #D44235;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            background: #fff;
            color: #111;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── REVEAL ── */
        .reveal {
            opacity: 0;
            transform: translate3d(0, 22px, 0);
            transition: opacity 0.85s cubic-bezier(0.22,0.61,0.36,1),
                        transform 0.85s cubic-bezier(0.22,0.61,0.36,1);
        }
        .reveal.active { opacity: 1; transform: translate3d(0,0,0); }
        .d1 { transition-delay: 0.10s; }
        .d2 { transition-delay: 0.22s; }
        .d3 { transition-delay: 0.36s; }
        .d4 { transition-delay: 0.50s; }
        .d5 { transition-delay: 0.64s; }

        /* ══════════════════════════════════════
           NAVBAR — white, stays white always
        ══════════════════════════════════════ */
        header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 200;
            height: 64px;
            padding: 0 3rem;
            display: flex;
            align-items: center;
            background: #ffffff;
            border-bottom: 1px solid rgba(0,0,0,0.07);
            transition: box-shadow 0.3s ease;
        }
        header.scrolled {
            box-shadow: 0 1px 12px rgba(0,0,0,0.08);
        }

        .nav-brand { display: flex; align-items: center; gap: 11px; }
        .nav-logo  { width: 30px; height: 30px; object-fit: contain; }
        .nav-divider { width: 1px; height: 16px; background: #e0deda; }
        .nav-title {
            font-family: 'Playfair Display', serif;
            font-size: 1rem;
            font-weight: 700;
            color: #111;
            letter-spacing: 0.005em;
        }
        .nav-badge {
            font-size: 0.57rem;
            font-weight: 600;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--red);
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
            background: var(--red);
            animation: livepulse 2.5s ease-in-out infinite;
        }
        @keyframes livepulse { 0%,100%{ opacity:1; } 50%{ opacity:0.28; } }

        /* ══════════════════════════════════════
           HERO — photo with cinematic overlay
        ══════════════════════════════════════ */
        .hero {
            position: relative;
            flex: 1;
            display: flex;
            flex-direction: column;
            /* hero starts right under the white navbar */
            margin-top: 64px;
            overflow: hidden;
        }

        /* Photo */
        .hero-bg {
            position: absolute;
            inset: 0;
            background-image: url("{{ asset('images/building.png') }}");
            background-size: cover;
            background-position: center 30%;
            /* cinematic: desaturate slightly, keep detail */
            filter: saturate(0.78) brightness(0.92);
            transform: scale(1.04);
            transition: transform 14s cubic-bezier(0.25,0.46,0.45,0.94);
        }
        .hero-bg.ready { transform: scale(1); }

        /*
         * Cinematic overlay — inspired by reference.
         * NOT flat black. Multi-stop so photo breathes in the middle.
         * Top heavier so text area is clear.
         * Bottom stays dark through feature row.
         * No white inside hero — white only happens in footer.
         */
        .hero-overlay {
            position: absolute;
            inset: 0;
            background:
                /* subtle left edge depth */
                linear-gradient(90deg,
                    rgba(0,0,0,0.38) 0%,
                    rgba(0,0,0,0.04) 55%,
                    transparent      100%
                ),
                /* main vertical cinematic ramp */
                linear-gradient(180deg,
                    rgba(0,0,0,0.52)  0%,
                    rgba(0,0,0,0.36) 18%,
                    rgba(0,0,0,0.28) 36%,
                    rgba(0,0,0,0.38) 58%,
                    rgba(0,0,0,0.60) 76%,
                    rgba(0,0,0,0.82) 100%
                );
        }

        /* Film grain for unified texture */
        .hero-grain {
            position: absolute;
            inset: 0;
            opacity: 0.022;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 512 512'%3E%3Cfilter id='g'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.72' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23g)'/%3E%3C/svg%3E");
            background-size: 256px;
            pointer-events: none;
        }

        /* ── Hero content: headline area ── */
        .hero-content {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 80px 2rem 0;
            text-align: center;
        }

        .hero-box { max-width: 680px; width: 100%; }

        /* Platform label */
        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.50);
            margin-bottom: 1.4rem;
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

        /* Headline — pure white, no opacity reduction */
        .hero-h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(3rem, 5.8vw, 4.8rem);
            font-weight: 700;
            line-height: 1.07;
            letter-spacing: -0.03em;
            color: #ffffff;
            margin-bottom: 1.2rem;
            /* subtle shadow for depth against photo */
            text-shadow: 0 2px 32px rgba(0,0,0,0.28);
        }
        .hero-h1 em {
            font-style: italic;
            color: var(--red);
            text-shadow: 0 0 48px rgba(192,57,43,0.30), 0 2px 24px rgba(0,0,0,0.35);
        }

        /* Description — white at ~82% so it reads as secondary to headline */
        .hero-desc {
            font-size: 0.97rem;
            line-height: 1.78;
            color: rgba(255,255,255,0.82);
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
            color: #fff;
            padding: 14px 36px;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            letter-spacing: 0.025em;
            box-shadow: 0 4px 20px rgba(192,57,43,0.30), inset 0 1px 0 rgba(255,255,255,0.10);
            position: relative;
            transition: background 0.2s, transform 0.25s cubic-bezier(0.22,0.61,0.36,1), box-shadow 0.25s;
        }
        .hero-cta::before {
            content: '';
            position: absolute; inset: 0;
            border-radius: inherit;
            border: 1px solid rgba(255,255,255,0.10);
        }
        .hero-cta:hover {
            background: var(--red-hover);
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(192,57,43,0.38), inset 0 1px 0 rgba(255,255,255,0.12);
        }
        .hero-cta:active { transform: translateY(-1px); }
        .hero-cta svg { transition: transform 0.2s; }
        .hero-cta:hover svg { transform: translateX(4px); }

        /* ══════════════════════════════════════
           FEATURE ROW — stays on dark overlay
        ══════════════════════════════════════ */
        .hero-features {
            position: relative;
            z-index: 10;
        }

        .feat-inner {
            max-width: 740px;
            margin: 0 auto;
            padding: 1.75rem 2rem 2.75rem;
            display: flex;
            gap: 0;
            /* hairline: white 10% — readable against dark, not harsh */
            border-top: 1px solid rgba(255,255,255,0.10);
        }

        .feat-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 0 1.75rem;
        }
        .feat-item:first-child { padding-left: 0; }
        .feat-item:last-child  { padding-right: 0; }
        .feat-item + .feat-item {
            border-left: 1px solid rgba(255,255,255,0.08);
        }

        .feat-icon {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 4px;
        }
        .feat-icon svg {
            width: 14px; height: 14px;
            stroke: var(--red);
            stroke-width: 2.2;
            fill: none;
        }
        .feat-name {
            font-size: 0.76rem;
            font-weight: 600;
            color: var(--red);
            letter-spacing: 0.07em;
            text-transform: uppercase;
        }
        /* Description: white at 60% — clearly secondary but still readable on dark */
        .feat-desc {
            font-size: 0.75rem;
            color: rgba(255,255,255,0.60);
            line-height: 1.62;
        }

        /* ══════════════════════════════════════
           SMOOTH BRIDGE — dark hero → white footer
           This div sits at the very bottom of .hero,
           AFTER all content, and fades to white.
        ══════════════════════════════════════ */
        .hero-fade {
            position: relative;
            z-index: 10;
            height: 64px;
            background: linear-gradient(180deg,
                transparent 0%,
                #ffffff      100%
            );
        }

        /* ══════════════════════════════════════
           FOOTER — white
        ══════════════════════════════════════ */
        footer {
            background: #ffffff;
            border-top: none; /* bridge handles the transition */
            padding: 1.2rem 3rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .foot-left { display: flex; align-items: center; gap: 9px; }
        .foot-left img { width: 18px; height: 18px; object-fit: contain; }
        .foot-copy { font-size: 0.73rem; color: #A09E9C; }
        .foot-right { font-size: 0.68rem; color: #C4C2BE; letter-spacing: 0.09em; text-transform: uppercase; }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            header { padding: 0 1.25rem; }
            .hero-content { padding: 60px 1.5rem 0; }
            .hero-h1 { letter-spacing: -0.02em; }
            .hero-desc { font-size: 0.88rem; }
            .feat-inner {
                flex-direction: column;
                padding: 1.5rem 1.5rem 2rem;
            }
            .feat-item {
                padding: 1.25rem 0 0;
                border-left: none !important;
                border-top: 1px solid rgba(255,255,255,0.08);
            }
            .feat-item:first-child { border-top: none; padding-top: 0; }
            .hero-fade { height: 48px; }
            footer { padding: 1.1rem 1.5rem 1.3rem; flex-direction: column; gap: 0.5rem; text-align: center; }
            .foot-right { display: none; }
        }
    </style>
</head>
<body>

<!-- ── NAVBAR: white, always white ── -->
<header id="main-header">
    <div class="nav-brand">
        <img src="{{ asset('images/ippi.png') }}" class="nav-logo" alt="IPPI">
        <div class="nav-divider"></div>
        <span class="nav-title">Production System</span>
        <span class="nav-badge">Live</span>
    </div>
</header>

<!-- ── HERO: photo + cinematic dark overlay ── -->
<section class="hero">

    <div class="hero-bg" id="heroBg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-grain"></div>

    <!-- Headline + description + CTA -->
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
                Pantau lini produksi, lacak downtime mesin, dan kendalikan kualitas
                produk secara real-time dari satu platform terpadu.
            </p>

            <a href="{{ route('login') }}" class="hero-cta reveal d3">
                Masuk ke Sistem
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                    <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2"
                          stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>

        </div>
    </div>

    <!-- Feature row — still inside dark cinematic area -->
    <div class="hero-features">
        <div class="feat-inner">

            <div class="feat-item reveal d3">
                <div class="feat-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 3v18h18M18 9l-5 5-4-4-5 5"/>
                    </svg>
                    <span class="feat-name">Production</span>
                </div>
                <p class="feat-desc">Output aktual vs target per shift dan lini produksi.</p>
            </div>

            <div class="feat-item reveal d4">
                <div class="feat-icon">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                    <span class="feat-name">Downtime</span>
                </div>
                <p class="feat-desc">Deteksi otomatis dan analisis penyebab berhentinya mesin.</p>
            </div>

            <div class="feat-item reveal d5">
                <div class="feat-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14M22 4L12 14.01l-3-3"/>
                    </svg>
                    <span class="feat-name">Quality</span>
                </div>
                <p class="feat-desc">Inspeksi digital, monitoring defect, dan laporan terpadu.</p>
            </div>

        </div>
    </div>

    <!-- Smooth bridge: dark → white footer -->
    <div class="hero-fade"></div>

</section>

<!-- ── FOOTER: white ── -->
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

        // Navbar: white but gets subtle shadow on scroll
        const header = document.getElementById('main-header');
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 16);
        }, { passive: true });

        // Hero bg: scale-in after image loads
        const bg = document.getElementById('heroBg');
        const img = new Image();
        img.onload = () => bg.classList.add('ready');
        img.src = "{{ asset('images/building.png') }}";

    });
</script>

</body>
</html>

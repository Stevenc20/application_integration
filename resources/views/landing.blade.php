<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT IPPI — Production System</title>
    <meta name="description" content="Sistem monitoring produksi real-time PT IPPI.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #C0392B;
            --red-hover: #E74C3C;
            --red-light: #FDECEA;
            --white: #FFFFFF;
            --off-white: #F8F7F5;
            --border: #E9E7E4;
            --t1: #111110;
            --t2: #5C5A58;
            --t3: #A09E9C;
        }

        *{box-sizing:border-box;margin:0;padding:0}
        body{
            font-family:'Inter',sans-serif;
            min-height:100vh;display:flex;flex-direction:column;
            -webkit-font-smoothing:antialiased;
            background:var(--off-white);
        }
        .page{flex:1;display:flex;flex-direction:column}

        /* ─── ANIMASI MASUK (REVEAL) ─── */
        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: all 0.8s cubic-bezier(0.22, 0.61, 0.36, 1);
        }
        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }
        .delay-100 { transition-delay: 0.1s; }
        .delay-200 { transition-delay: 0.2s; }
        .delay-300 { transition-delay: 0.3s; }

        /* ─── NAVBAR (Matching Login Page) ─── */
        header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            padding: 0 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 68px;
            position:relative;z-index:10;
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
            border: 1px solid rgba(192,57,43,0.2);
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* ─── CENTER HERO (Shadowy Building) ─── */
        .center{
            flex:1;display:flex;align-items:center;justify-content:center;
            padding:3rem 2rem;position:relative;
            background:url("{{ asset('images/building.png') }}") center/cover no-repeat;
        }
        .center::before{
            content:'';position:absolute;inset:0;
            background:linear-gradient(180deg,rgba(17,17,16,.75) 0%,rgba(17,17,16,.65) 50%,rgba(17,17,16,.85) 100%);
        }
        .center-box{position:relative;z-index:2;text-align:center;max-width:640px;width:100%}
        
        .hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.7rem;
            font-weight: 500;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            color: var(--red-light);
            margin-bottom: 1.5rem;
        }
        .hero-tag .dot {
            width: 6px; height: 6px; border-radius: 50%;
            background: var(--red);
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }

        .center-box h1{
            font-family: 'Playfair Display', serif;
            font-size: clamp(2.5rem, 4vw, 3.5rem);
            font-weight: 700;line-height:1.1;
            letter-spacing:-.02em;margin-bottom:.8rem;color:var(--white);
        }
        .center-box h1 em{font-style:normal;color:var(--red)}
        
        .center-box p{
            font-size:1rem;line-height:1.7;color:rgba(255,255,255,.8);
            margin-bottom:2.5rem;
        }
        
        .center-btn{
            display:inline-flex;align-items:center;gap:8px;
            background:var(--red);color:var(--white);
            padding:14px 34px;border-radius:6px;
            font-size:.9rem;font-weight:500;text-decoration:none;
            transition:background 0.2s,transform 0.2s,box-shadow 0.2s;
        }
        .center-btn:hover{
            background:var(--red-hover);
            transform:translateY(-2px);
            box-shadow:0 8px 24px rgba(192,57,43,.3);
        }

        /* ─── FEATURES (INLINE) ─── */
        .features{
            display:flex;justify-content:center;gap:2rem;margin-top:3.5rem;
            border-top:1px solid rgba(255,255,255,.1);padding-top:2.5rem;
        }
        .feat-item{
            flex:1;text-align:left;display:flex;flex-direction:column;gap:6px;
        }
        .feat-item h3{
            font-size:.85rem;font-weight:600;color:var(--red-hover);
            display:flex;align-items:center;gap:8px;
            letter-spacing: 0.02em;
        }
        .feat-item h3 svg{width:18px;height:18px;stroke:currentColor;stroke-width:2;fill:none}
        .feat-item p{font-size:.78rem;color:rgba(255,255,255,.65);line-height:1.55}

        /* ─── FOOTER (Matching Login Page) ─── */
        footer{
            background:var(--white);
            border-top:1px solid var(--border);
            padding:1.5rem 3rem;
            display:flex;justify-content:space-between;align-items:center;
            position:relative;z-index:10;
        }
        .foot-left { display: flex; align-items: center; gap: 10px; }
        .foot-left img { width: 22px; height: 22px; object-fit: contain; }
        .foot-copy { font-size: 0.78rem; color: var(--t3); }

        @media(max-width:768px){
            header{padding:0 1.5rem}
            .nav-badge{display:none}
            .center{padding:2rem 1.5rem}
            .features{flex-direction:column;gap:1.5rem}
            footer{flex-direction:column;gap:0.75rem;padding:1.25rem 1.5rem;text-align:center;}
        }
    </style>
</head>
<body>

<div class="page">
    <header class="reveal">
        <div class="nav-brand">
            <img src="{{ asset('images/ippi.png') }}" class="nav-logo" alt="IPPI">
            <div class="nav-divider"></div>
            <span class="nav-title">Production System</span>
            <span class="nav-badge">Live</span>
        </div>
        <!-- Button Login dihapus dari navbar, fokus ke tengah saja -->
    </header>

    <div class="center">
        <div class="center-box">
            <div class="hero-tag reveal">
                <div class="dot"></div>
                Smart Manufacturing Platform
            </div>
            <h1 class="reveal delay-100">Monitor. Track.<br><em>Control.</em></h1>
            <p class="reveal delay-200">Pantau lini produksi, lacak downtime mesin, dan kendalikan kualitas produk secara real-time dari satu platform terpadu.</p>
            
            <a href="{{ route('login') }}" class="center-btn reveal delay-300">
                Masuk ke Sistem
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            
            <div class="features">
                <div class="feat-item reveal delay-100">
                    <h3>
                        <svg viewBox="0 0 24 24"><path d="M3 3v18h18M18 9l-5 5-4-4-5 5"/></svg>
                        Production
                    </h3>
                    <p>Output aktual vs target per shift dan lini produksi.</p>
                </div>
                <div class="feat-item reveal delay-200">
                    <h3>
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Downtime
                    </h3>
                    <p>Deteksi otomatis dan analisis penyebab berhentinya mesin.</p>
                </div>
                <div class="feat-item reveal delay-300">
                    <h3>
                        <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14M22 4L12 14.01l-3-3"/></svg>
                        Quality
                    </h3>
                    <p>Inspeksi digital, monitoring defect, dan laporan terpadu.</p>
                </div>
            </div>
        </div>
    </div>

    <footer class="reveal delay-200">
        <div class="foot-left">
            <img src="{{ asset('images/ippi.png') }}" alt="IPPI">
            <span class="foot-copy">© {{ date('Y') }} PT IPPI. Created by Steven Christian.</span>
        </div>
    </footer>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    });
</script>

</body>
</html>
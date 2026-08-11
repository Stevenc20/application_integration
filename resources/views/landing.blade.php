<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PT IPPI — Production System</title>
    <meta name="description" content="Sistem monitoring produksi real-time PT IPPI.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --red: #C0392B;
            --red-hover: #E74C3C;
            --white: #FFFFFF;
            --bg: #F5F4F2;
            --border: #E2E0DD;
            --t1: #1A1918;
            --t2: #5C5A58;
            --t3: #9A9895;
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--t1);min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased}

        /* NAV */
        nav{background:var(--white);border-bottom:1px solid var(--border);height:50px;display:flex;align-items:center;justify-content:space-between;padding:0 2rem;position:sticky;top:0;z-index:100}
        .nav-left{display:flex;align-items:center;gap:8px}
        .nav-left img{height:24px;width:auto}
        .nav-left span{font-size:.8rem;font-weight:600;color:var(--t1)}
        .nav-btn{background:var(--red);color:var(--white);padding:6px 16px;border-radius:4px;font-size:.75rem;font-weight:600;text-decoration:none;transition:background .15s}
        .nav-btn:hover{background:var(--red-hover)}

        /* MAIN */
        .main{background:var(--white);flex:1;display:flex;align-items:center;justify-content:center;padding:2rem}
        .main-box{text-align:center;max-width:480px}
        .main-box img{width:100px;height:auto;margin-bottom:1.2rem;opacity:.9}
        .main-box h1{font-size:2.2rem;font-weight:700;line-height:1.1;letter-spacing:-.03em;margin-bottom:.5rem}
        .main-box h1 em{font-style:normal;color:var(--red)}
        .main-box p{font-size:.85rem;line-height:1.6;color:var(--t2);margin-bottom:1.5rem}
        .main-btn{display:inline-flex;align-items:center;gap:8px;background:var(--red);color:var(--white);padding:12px 28px;border-radius:5px;font-size:.85rem;font-weight:600;text-decoration:none;transition:background .15s,transform .15s,box-shadow .15s}
        .main-btn:hover{background:var(--red-hover);transform:translateY(-1px);box-shadow:0 6px 20px rgba(192,57,43,.25)}

        /* STATS BAR */
        .stats{background:var(--red);display:flex}
        .stat{flex:1;padding:.7rem 0;text-align:center;border-right:1px solid rgba(255,255,255,.12)}
        .stat:last-child{border-right:none}
        .stat-val{font-size:1.2rem;font-weight:700;color:var(--white);line-height:1}
        .stat-val small{font-size:.75rem;font-weight:400;opacity:.5}
        .stat-lbl{font-size:.58rem;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.45);margin-top:2px}

        /* FOOTER */
        footer{background:var(--t1);padding:.6rem 2rem;display:flex;align-items:center;justify-content:space-between}
        .foot-copy{font-size:.62rem;color:rgba(255,255,255,.35)}
        .foot-right{font-size:.62rem;color:rgba(255,255,255,.35)}

        @media(max-width:640px){
            nav{padding:0 1rem}
            .main{padding:1.5rem 1rem}
            .main-box h1{font-size:1.6rem}
            .stats{flex-wrap:wrap}
            .stat{flex:1 1 50%;border-bottom:1px solid rgba(255,255,255,.12)}
            .stat:nth-child(2){border-right:none}
            .stat:nth-child(3),.stat:nth-child(4){border-bottom:none}
            footer{flex-direction:column;gap:.3rem;padding:.5rem 1rem}
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-left">
        <img src="{{ asset('images/ippi.png') }}" alt="IPPI">
        <span>Production System</span>
    </div>
    <a href="{{ route('login') }}" class="nav-btn">Login</a>
</nav>

<div class="main">
    <div class="main-box">
        <img src="{{ asset('images/logoippi.png') }}" alt="PT IPPI">
        <h1>Monitor. Track. <em>Control.</em></h1>
        <p>Pantau lini produksi, lacak downtime mesin, dan kendalikan kualitas produk secara real-time dari satu platform terpadu.</p>
        <a href="{{ route('login') }}" class="main-btn">
            Masuk ke Sistem
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7h8M8 4l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
    </div>
</div>

<div class="stats">
    <div class="stat"><div class="stat-val">99<small>%</small></div><div class="stat-lbl">Uptime</div></div>
    <div class="stat"><div class="stat-val">4</div><div class="stat-lbl">Press Lines</div></div>
    <div class="stat"><div class="stat-val">Real<small>-time</small></div><div class="stat-lbl">Monitoring</div></div>
    <div class="stat"><div class="stat-val">24<small>/7</small></div><div class="stat-lbl">Operation</div></div>
</div>

<footer>
    <span class="foot-copy">© {{ date('Y') }} PT IPPI — Production System</span>
    <span class="foot-right">manufacturing.tantechstev.com</span>
</footer>

</body>
</html>
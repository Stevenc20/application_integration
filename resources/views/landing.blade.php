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
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'DM Sans',sans-serif;color:#fff;min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased}

        .page{flex:1;display:flex;flex-direction:column}

        /* NAV */
        nav{
            height:50px;display:flex;align-items:center;justify-content:space-between;
            padding:0 2rem;background:#1A1918;border-bottom:1px solid rgba(255,255,255,.06);
        }
        .nav-left{display:flex;align-items:center;gap:8px}
        .nav-left img{height:24px;width:auto;filter:brightness(0) invert(1);opacity:.85}
        .nav-left span{font-size:.8rem;font-weight:600;color:rgba(255,255,255,.8)}
        .nav-btn{
            background:#C0392B;color:#fff;padding:6px 16px;border-radius:4px;
            font-size:.75rem;font-weight:600;text-decoration:none;transition:background .15s;
        }
        .nav-btn:hover{background:#E74C3C}

        /* CENTER - photo bg here only */
        .center{
            flex:1;display:flex;align-items:center;justify-content:center;
            padding:2rem;position:relative;
            background:url("{{ asset('images/building.png') }}") center/cover no-repeat;
        }
        .center::before{
            content:'';position:absolute;inset:0;
            background:linear-gradient(180deg,rgba(20,18,16,.82) 0%,rgba(20,18,16,.68) 50%,rgba(20,18,16,.85) 100%);
        }
        .center-box{position:relative;z-index:2}
        .center-box{text-align:center;max-width:500px}
        .center-box img{width:160px;height:auto;margin-bottom:1rem}
        .center-box h1{
            font-size:2.4rem;font-weight:700;line-height:1.08;
            letter-spacing:-.03em;margin-bottom:.5rem;
        }
        .center-box h1 em{font-style:normal;color:#E74C3C}
        .center-box p{
            font-size:.85rem;line-height:1.6;color:rgba(255,255,255,.55);
            margin-bottom:1.5rem;
        }
        .center-btn{
            display:inline-flex;align-items:center;gap:8px;
            background:#C0392B;color:#fff;padding:12px 30px;border-radius:5px;
            font-size:.85rem;font-weight:600;text-decoration:none;
            transition:background .15s,transform .15s,box-shadow .15s;
        }
        .center-btn:hover{background:#E74C3C;transform:translateY(-1px);box-shadow:0 8px 24px rgba(192,57,43,.35)}

        /* FOOTER */
        .foot{
            padding:.5rem 2rem;text-align:center;
            background:#1A1918;border-top:1px solid rgba(255,255,255,.06);
        }
        .foot span{font-size:.6rem;color:rgba(255,255,255,.3)}

        @media(max-width:640px){
            nav{padding:0 1rem}
            .center{padding:1.5rem 1rem}
            .center-box img{width:120px}
            .center-box h1{font-size:1.8rem}
            .stats{flex-wrap:wrap}
            .stat{flex:1 1 50%}
            .stat:nth-child(2){border-right:none}
            .stat:nth-child(1),.stat:nth-child(2){border-bottom:1px solid rgba(255,255,255,.06)}
            .foot{padding:.4rem 1rem}
        }
    </style>
</head>
<body>

<div class="page">
    <nav>
        <div class="nav-left">
            <img src="{{ asset('images/ippi.png') }}" alt="IPPI">
            <span>Production System</span>
        </div>
        <a href="{{ route('login') }}" class="nav-btn">Login</a>
    </nav>

    <div class="center">
        <div class="center-box">
            <img src="{{ asset('images/logoippi.png') }}" alt="PT IPPI">
            <h1>Monitor. Track. <em>Control.</em></h1>
            <p>Pantau lini produksi, lacak downtime mesin, dan kendalikan kualitas produk secara real-time dari satu platform terpadu.</p>
            <a href="{{ route('login') }}" class="center-btn">
                Masuk ke Sistem
                <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3 7h8M8 4l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
    </div>

    <div class="foot">
        <span>Created by Steven Christian</span>
    </div>
</div>

</body>
</html>
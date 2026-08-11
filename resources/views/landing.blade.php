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
        body{font-family:'DM Sans',sans-serif;color:#1A1918;min-height:100vh;display:flex;flex-direction:column;-webkit-font-smoothing:antialiased}

        .page{
            flex:1;display:flex;flex-direction:column;
            background:url("{{ asset('images/building.png') }}") center/cover no-repeat fixed;
            position:relative;
        }
        /* Overlay: Putih tapi tidak menyilaukan (85%), gambar gedung terlihat sebagai shadow */
        .page::before{
            content:'';position:absolute;inset:0;
            background:rgba(245, 244, 242, 0.85); /* Off-white overlay */
            z-index:1;
        }
        .page>*{position:relative;z-index:2}

        /* NAV - PUTIH SOLID */
        nav{
            height:55px;display:flex;align-items:center;justify-content:space-between;
            padding:0 2rem;background:#fff;border-bottom:1px solid #E2E0DD;
        }
        .nav-left{display:flex;align-items:center;gap:10px}
        .nav-left img{height:28px;width:auto}
        .nav-left span{font-size:.85rem;font-weight:600;color:#1A1918}
        .nav-btn{
            background:#C0392B;color:#fff;padding:8px 20px;border-radius:4px;
            font-size:.75rem;font-weight:600;text-decoration:none;transition:background .15s;
        }
        .nav-btn:hover{background:#E74C3C}

        /* CENTER */
        .center{
            flex:1;display:flex;align-items:center;justify-content:center;
            padding:2rem;
        }
        .center-box{text-align:center;max-width:600px;width:100%}
        .center-box img{width:150px;height:auto;margin-bottom:1.5rem}
        .center-box h1{
            font-size:2.8rem;font-weight:700;line-height:1.1;
            letter-spacing:-.02em;margin-bottom:.8rem;color:#1A1918;
        }
        .center-box h1 em{font-style:normal;color:#C0392B}
        .center-box p{
            font-size:.95rem;line-height:1.6;color:#5C5A58;
            margin-bottom:2rem;
        }
        .center-btn{
            display:inline-flex;align-items:center;gap:8px;
            background:#C0392B;color:#fff;padding:12px 30px;border-radius:5px;
            font-size:.9rem;font-weight:600;text-decoration:none;
            transition:background .15s,transform .15s,box-shadow .15s;
        }
        .center-btn:hover{background:#E74C3C;transform:translateY(-2px);box-shadow:0 8px 24px rgba(192,57,43,.25)}

        /* FEATURES (INLINE, NO CARDS, DARK TEXT) */
        .features{
            display:flex;justify-content:center;gap:2rem;margin-top:3rem;
            border-top:1px solid rgba(0,0,0,.1);padding-top:2rem;
        }
        .feat-item{
            flex:1;text-align:left;display:flex;flex-direction:column;gap:5px;
        }
        .feat-item h3{
            font-size:.85rem;font-weight:600;color:#C0392B;
            display:flex;align-items:center;gap:6px;
        }
        .feat-item h3 svg{width:16px;height:16px;stroke:currentColor;stroke-width:2;fill:none}
        .feat-item p{font-size:.75rem;color:#5C5A58;line-height:1.5}

        /* FOOTER - PUTIH SOLID */
        .foot{
            padding:1rem 2rem;text-align:center;
            background:#fff;border-top:1px solid #E2E0DD;
        }
        .foot span{font-size:.65rem;color:#9A9895}

        @media(max-width:768px){
            nav{padding:0 1rem}
            .center{padding:1.5rem 1rem}
            .center-box img{width:120px}
            .center-box h1{font-size:2rem}
            .features{flex-direction:column;gap:1.5rem}
            .foot{padding:.8rem 1rem}
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
            
            <div class="features">
                <div class="feat-item">
                    <h3>
                        <svg viewBox="0 0 24 24"><path d="M3 3v18h18M18 9l-5 5-4-4-5 5"/></svg>
                        Production Monitoring
                    </h3>
                    <p>Output aktual vs target per shift dan lini produksi.</p>
                </div>
                <div class="feat-item">
                    <h3>
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                        Downtime Tracking
                    </h3>
                    <p>Deteksi dan analisis penyebab berhentinya mesin.</p>
                </div>
                <div class="feat-item">
                    <h3>
                        <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 11-5.93-9.14M22 4L12 14.01l-3-3"/></svg>
                        Quality Control
                    </h3>
                    <p>Inspeksi digital, defect, dan laporan QPR.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="foot">
        <span>Created by Steven Christian</span>
    </div>
</div>

</body>
</html>
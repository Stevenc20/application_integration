<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>@yield('title', 'Selamat Datang')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        :root{--navy-dark:#0d1b2a;--navy-mid:#162032;--navy-light:#1e2d42;--red-main:#C0001C;--red-dark:#8f000d;--accent:#e53935;--sidebar-w:180px;--header-h:52px;}
        body{font-family:'Inter',sans-serif;background:#f4f5f7;color:#1a1a1a;min-height:100vh;}
        .top-navbar{position:fixed;top:0;left:0;right:0;height:var(--header-h);background:#111827;display:flex;align-items:center;justify-content:space-between;padding:0 20px 0 0;z-index:10000 !important;box-shadow:0 2px 12px rgba(0,0,0,.4);}
        .navbar-left{display:flex;align-items:center;height:100%;}
        .navbar-brand{background:#0d1b2a;height:var(--header-h);display:flex;flex-direction:column;justify-content:center;padding:0 18px;border-right:1px solid rgba(255,255,255,.15);}
        .navbar-brand h1{font-size:13px;font-weight:900;color:white;letter-spacing:1px;text-transform:uppercase;}
        .navbar-brand small{font-size:8px;color:rgba(255,255,255,.55);font-weight:600;letter-spacing:1.5px;text-transform:uppercase;margin-top:1px;}
        .navbar-title{padding:0 20px;font-size:15px;font-weight:900;color:white;text-transform:uppercase;}
        .navbar-menus{display:flex;align-items:center;height:var(--header-h);border-left:1px solid rgba(255,255,255,.15);overflow-x:auto;}
        .nav-menu-item{display:flex;align-items:center;height:100%;padding:0 14px;font-size:10px;font-weight:700;color:rgba(255,255,255,.7);text-decoration:none;text-transform:uppercase;letter-spacing:.5px;border-bottom:3px solid transparent;transition:all .15s;white-space:nowrap;}
        .nav-menu-item:hover{color:white;background:rgba(255,255,255,.08);}
        .nav-menu-item.active{color:white;border-bottom-color:white;}
        .navbar-right{display:flex;align-items:center;gap:12px;}
        .navbar-datetime .ndt-date{font-size:12px;font-weight:700;color:white;}
        .navbar-datetime .ndt-time{font-size:11px;color:rgba(255,255,255,.7);}
        .hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px;margin-left:6px;}
        .hamburger span{display:block;width:22px;height:2px;background:white;border-radius:2px;}
        
        aside{position:fixed;left:0;top:var(--header-h);bottom:0;width:var(--sidebar-w);background:#ffffff;border-right:1px solid #e2e2e7;display:flex;flex-direction:column;z-index:11000 !important;transition:transform .3s ease;}
        .sidebar-header{padding:14px 16px 10px;border-bottom:1px solid #f0f0f0;}
        .sec-label{font-size:11px;font-weight:800;color:var(--red-main);letter-spacing:1.5px;text-transform:uppercase;}
        .sec-sub{font-size:8px;color:#999;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;margin-top:3px;}
        .sidebar-nav{flex:1;padding:12px 0;overflow-y:auto;}
        .sidebar-item{display:flex;align-items:center;gap:10px;padding:10px 14px;margin:2px 10px;border-radius:8px;font-size:12px;font-weight:600;color:#555;text-decoration:none;transition:all .15s;}
        .sidebar-item:hover{color:var(--red-main);background:#f9f9f9;}
        .sidebar-item.active{color:white;background:var(--red-main);}
        .sidebar-item .material-icons{font-size:18px;}
        .sidebar-bottom{padding:14px;border-top:1px solid #f0f0f0;}
        .btn-back-sidebar{display:block;width:100%;background:#f5f5f5;color:#333;border:1px solid #ddd;border-radius:6px;padding:9px;font-size:10px;font-weight:700;text-align:center;text-decoration:none;text-transform:uppercase;letter-spacing:.8px;}
        .btn-back-sidebar:hover{background:#e0e0e0;color:#000;}
        
        main{margin-left:var(--sidebar-w);padding-top:var(--header-h);min-height:100vh;position:relative;z-index:1;}
        
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:10500 !important;}
        .sidebar-overlay.active{display:block;}
        aside.mobile-open{transform:translateX(0) !important;}
        
        @media(max-width:768px){
            .hamburger{display:flex;}
            .navbar-menus{display:none;}
            .navbar-title{display:none;}
            aside{transform:translateX(-100%);z-index:11000 !important;}
            main{margin-left:0;}
        }
        
        ::-webkit-scrollbar{width:5px;height:5px;}
        ::-webkit-scrollbar-thumb{background:#ddd;border-radius:3px;}

        /* Sidebar Dropdown */
        .sidebar-dropdown { position: relative; margin: 2px 10px; }
        .sidebar-dropdown-btn { 
            display: flex; align-items: center; justify-content: space-between; 
            width: 100%; padding: 10px 14px; border-radius: 8px; 
            font-size: 12px; font-weight: 600; color: #555; cursor: pointer; transition: all .15s; 
        }
        .sidebar-dropdown-btn:hover { color: var(--red-main); background: #f9f9f9; }
        .sidebar-dropdown-btn .material-icons:first-child { font-size: 18px; margin-right: 10px; }
        .sidebar-dropdown-btn .arrow { font-size: 18px; transition: transform .3s; }
        .sidebar-dropdown.active .sidebar-dropdown-btn { color: var(--red-main); font-weight: 700; }
        .sidebar-dropdown.active .arrow { transform: rotate(180deg); }
        
        .sidebar-dropdown-content { display: none; padding-left: 10px; margin-top: 2px; }
        .sidebar-dropdown.active .sidebar-dropdown-content { display: block; }
        .sidebar-dropdown-item { 
            display: flex; align-items: center; gap: 10px; padding: 8px 14px; 
            margin: 2px 0; border-radius: 6px; font-size: 11px; font-weight: 600; 
            color: #666; text-decoration: none; transition: all .15s; 
        }
        .sidebar-dropdown-item:hover { color: var(--red-main); background: #fdfdfd; }
        .sidebar-dropdown-item.active { color: var(--red-main); background: #fef2f2; }
    </style>
    @stack('styles')
</head>
<body>

<nav class="top-navbar">
    <div class="navbar-left">
        <div class="navbar-brand"><h1>PPLC</h1><small>Section</small></div>
        <div class="hamburger" onclick="toggleSidebar()"><span></span><span></span><span></span></div>
        <span class="navbar-title">Dashboard Production Planning Logistic Control</span>

    </div>

</nav>

<aside id="mainSidebar">
    <div class="sidebar-header"><div class="sec-label">PPLC Section</div><div class="sec-sub">Sidebar</div></div>
    <nav class="sidebar-nav">
        <a href="{{ route('stock.index') }}" class="sidebar-item {{ request()->routeIs('stock.index') ? 'active' : '' }}"><span class="material-icons">dashboard</span> Dashboard</a>
        
        {{-- Logistic Dropdown --}}
        <div class="sidebar-dropdown {{ request()->routeIs('rundown.index') || request()->routeIs('single_part.index') ? 'active' : '' }}">
            <div class="sidebar-dropdown-btn" onclick="toggleSidebarDropdown(this)">
                <div style="display:flex;align-items:center;"><span class="material-icons">local_shipping</span> LOGISTIC</div>
                <span class="material-icons arrow">expand_more</span>
            </div>
            <div class="sidebar-dropdown-content">
                <a href="{{ route('rundown.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('rundown.index') ? 'active' : '' }}">Rundown Stock</a>
                <a href="{{ route('single_part.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('single_part.index') ? 'active' : '' }}">Rundown Incoming</a>
            </div>
        </div>

        {{-- PPC Dropdown --}}
        <div class="sidebar-dropdown {{ request()->routeIs('rundown_press.index') || request()->routeIs('schedule_stamping.*') ? 'active' : '' }}">
            <div class="sidebar-dropdown-btn" onclick="toggleSidebarDropdown(this)">
                <div style="display:flex;align-items:center;"><span class="material-icons">settings</span> PPC</div>
                <span class="material-icons arrow">expand_more</span>
            </div>
            <div class="sidebar-dropdown-content">
                <a href="{{ route('rundown_press.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('rundown_press.index') ? 'active' : '' }}">Rundown Press</a>
                <a href="{{ route('schedule_stamping.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('schedule_stamping.*') ? 'active' : '' }}">Schedule Stamping</a>
            </div>
        </div>

        <a href="#" class="sidebar-item"><span class="material-icons">assessment</span> PPC 4</a>
    </nav>
    <div class="sidebar-bottom"><a href="{{ route('stock.index') }}" class="btn-back-sidebar">Back</a></div>
</aside>

<main>
    @yield('content')
</main>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<script>

function toggleSidebar(){
    document.getElementById('mainSidebar').classList.toggle('mobile-open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}

function toggleSidebarDropdown(btn){
    const parent = btn.parentElement;
    parent.classList.toggle('active');
}
</script>

@stack('scripts')

</body>
</html>
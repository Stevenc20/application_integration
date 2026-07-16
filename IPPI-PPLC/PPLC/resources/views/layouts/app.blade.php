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
        .navbar-brand{background:#0d1b2a;height:var(--header-h);display:flex;flex-direction:column;justify-content:center;align-items:center;text-align:center;padding:0 10px;width:var(--sidebar-w);border-right:1px solid rgba(255,255,255,.15);transition:width .3s ease;}
        .navbar-brand h1{font-size:13px;font-weight:900;color:white;letter-spacing:1px;text-transform:uppercase;transition:font-size .3s ease;}
        .navbar-brand small{font-size:8px;color:rgba(255,255,255,.55);font-weight:600;letter-spacing:1.5px;text-transform:uppercase;margin-top:1px;transition:display .3s ease;}
        .navbar-title{padding:0 20px;font-size:15px;font-weight:900;color:white;text-transform:uppercase;}
        .navbar-menus{display:flex;align-items:center;height:var(--header-h);border-left:1px solid rgba(255,255,255,.15);overflow-x:auto;}
        .nav-menu-item{display:flex;align-items:center;height:100%;padding:0 14px;font-size:10px;font-weight:700;color:rgba(255,255,255,.7);text-decoration:none;text-transform:uppercase;letter-spacing:.5px;border-bottom:3px solid transparent;transition:all .15s;white-space:nowrap;}
        .nav-menu-item:hover{color:white;background:rgba(255,255,255,.08);}
        .nav-menu-item.active{color:white;border-bottom-color:white;}
        .navbar-right{display:flex;align-items:center;padding-right:20px;height:100%;}
        .navbar-datetime{display:flex;flex-direction:column;align-items:flex-end;justify-content:center;height:100%;}
        .navbar-datetime .ndt-date{font-size:11px;font-weight:700;color:white;}
        .navbar-datetime .ndt-time{font-size:10px;color:rgba(255,255,255,.7);font-weight:500;margin-top:1px;}
        .hamburger{display:none;flex-direction:column;gap:5px;cursor:pointer;padding:8px;margin-left:6px;}
        .hamburger span{display:block;width:22px;height:2px;background:white;border-radius:2px;}
        
        aside{position:fixed;left:0;top:var(--header-h);bottom:0;width:var(--sidebar-w);background:#ffffff;border-right:1px solid #e2e2e7;display:flex;flex-direction:column;z-index:11000 !important;transition:transform .3s ease, width .3s ease, background-color .3s ease !important;}
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
        
        main{margin-left:var(--sidebar-w);padding-top:var(--header-h);min-height:100vh;position:relative;z-index:1;transition:margin-left .3s ease !important;}
        
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:10500 !important;}
        .sidebar-overlay.active{display:block;}
        aside.mobile-open{transform:translateX(0) !important;}

        /* Collapsed Sidebar State styling */
        body.sidebar-collapsed {
            --sidebar-w: 60px !important;
        }
        body.sidebar-collapsed .navbar-brand {
            width: 60px !important;
        }
        body.sidebar-collapsed .navbar-brand h1 {
            font-size: 11px !important;
            letter-spacing: 0.5px !important;
        }
        body.sidebar-collapsed .navbar-brand small {
            display: none !important;
        }
        body.sidebar-collapsed .sidebar-header {
            padding: 10px 0 !important;
            text-align: center;
            justify-content: center !important;
        }
        body.sidebar-collapsed .sec-label,
        body.sidebar-collapsed .sec-sub {
            display: none !important;
        }
        body.sidebar-collapsed .sidebar-toggle-btn {
            margin: 0 auto !important;
        }
        body.sidebar-collapsed .sidebar-item {
            font-size: 0 !important;
            justify-content: center !important;
            padding: 10px 0 !important;
            margin: 4px 8px !important;
        }
        body.sidebar-collapsed .sidebar-item .material-icons {
            margin-right: 0 !important;
            font-size: 20px !important;
        }
        body.sidebar-collapsed .sidebar-dropdown-btn {
            font-size: 0 !important;
            justify-content: center !important;
            padding: 10px 0 !important;
            margin: 4px 8px !important;
            width: auto !important;
        }
        body.sidebar-collapsed .sidebar-dropdown-btn div {
            font-size: 0 !important;
            display: flex !important;
            justify-content: center !important;
            width: 100% !important;
        }
        body.sidebar-collapsed .sidebar-dropdown-btn .material-icons:first-child {
            margin-right: 0 !important;
            font-size: 20px !important;
        }
        body.sidebar-collapsed .sidebar-dropdown-btn .arrow {
            display: none !important;
        }
        body.sidebar-collapsed .sidebar-dropdown-content {
            display: none !important;
        }
        body.sidebar-collapsed .btn-back-sidebar {
            font-size: 0 !important;
            padding: 0 !important;
            border-radius: 50% !important;
            width: 38px !important;
            height: 38px !important;
            margin: 0 auto !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        body.sidebar-collapsed .btn-back-sidebar::before {
            content: 'arrow_back';
            font-family: 'Material Icons';
            font-size: 18px;
            color: #555;
            display: inline-block;
        }
        
        @media(max-width:768px){
            .hamburger{display:flex;}
            .navbar-menus{display:none;}
            .navbar-title{display:none;}
            aside{transform:translateX(-100%) !important;z-index:11000 !important;width:180px !important;}
            main{margin-left:0 !important;}
            body.sidebar-collapsed {
                --sidebar-w: 180px !important;
            }
            body.sidebar-collapsed aside {
                width: 180px !important;
            }
            .sidebar-toggle-btn {
                display: none !important;
            }
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

        /* ===== CUSTOM DROPDOWN SELECT ===== */
        .custom-select-container {
            position: relative;
            display: inline-block;
            width: auto;
            min-width: 140px;
            z-index: 150;
            vertical-align: middle;
        }
        .custom-select-container.w-full {
            width: 100% !important;
            display: block;
        }
        .custom-select-btn {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            color: #333;
            transition: all .2s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            box-sizing: border-box;
            height: 36px;
        }
        .custom-select-btn:hover {
            border-color: var(--red-main);
            background: #fffdfd;
        }
        .custom-select-btn .material-icons {
            font-size: 16px;
            color: var(--red-main);
        }
        .custom-select-btn .arrow {
            color: #999;
            transition: transform .2s;
            font-size: 18px;
            margin-left: 8px;
        }
        .custom-select-container.active .arrow {
            transform: rotate(180deg);
        }
        .custom-select-container.active .custom-select-btn {
            border-color: var(--red-main);
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }
        div.custom-select-container.filter-select {
            background: transparent !important;
            border: none !important;
            height: auto !important;
            padding: 0 !important;
        }
        .custom-select-content {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--red-main);
            border-top: none;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            max-height: 280px;
            overflow-y: auto;
            z-index: 99999 !important;
            display: none;
            box-sizing: border-box;
            scrollbar-width: thin;
            scrollbar-color: var(--red-main) #f1f1f1;
        }
        .custom-select-content::-webkit-scrollbar {
            width: 6px;
        }
        .custom-select-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-select-content::-webkit-scrollbar-thumb {
            background: var(--red-main);
            border-radius: 10px;
        }
        .custom-select-container.active .custom-select-content {
            display: block;
        }
        .custom-select-content a {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 9px 12px;
            text-decoration: none;
            color: #444;
            font-size: 11px;
            font-weight: 500;
            border-bottom: 1px solid #f8f9fa;
            transition: all .15s;
            cursor: pointer;
            box-sizing: border-box;
        }
        .custom-select-content a:last-child {
            border-bottom: none;
        }
        .custom-select-content a:hover {
            background: #fef2f2;
            color: var(--red-main);
            padding-left: 16px;
        }
        .custom-select-content a.selected {
            background: var(--red-main);
            color: white;
            font-weight: 700;
        }
        .custom-select-content a.selected:hover {
            background: var(--red-dark);
            color: white;
        }

        /* ===== LARAVEL PAGINATION CUSTOM STYLE ===== */
        nav[role="navigation"] {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            font-size: 13px;
            color: #475569;
        }
        nav[role="navigation"] svg {
            width: 16px !important;
            height: 16px !important;
            display: inline-block !important;
            vertical-align: middle;
        }
        nav[role="navigation"] .flex-1 {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        /* Desktop: Show full pagination, hide simple */
        nav[role="navigation"] > div:first-child {
            display: none !important;
        }
        nav[role="navigation"] > div:last-child {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Mobile: Show simple pagination, hide full */
        @media (max-width: 768px) {
            nav[role="navigation"] > div:first-child {
                display: flex !important;
                justify-content: space-between;
                align-items: center;
                width: 100%;
                gap: 8px;
            }
            nav[role="navigation"] > div:last-child {
                display: none !important;
            }
        }

        nav[role="navigation"] > div:first-child a,
        nav[role="navigation"] > div:first-child span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 36px;
            padding: 0 16px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-decoration: none;
            transition: all 0.15s ease;
            box-sizing: border-box;
        }
        nav[role="navigation"] > div:first-child a:hover {
            background-color: #f8fafc;
            color: var(--red-main);
        }
        nav[role="navigation"] > div:first-child span {
            color: #cbd5e1;
            background-color: #f8fafc;
            cursor: not-allowed;
        }

        nav[role="navigation"] p.text-sm {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }
        nav[role="navigation"] p.text-sm span {
            font-weight: 700;
            color: #1e293b;
        }
        nav[role="navigation"] span.relative.z-0 {
            display: inline-flex;
            border-radius: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            background-color: #fff;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        nav[role="navigation"] span.relative.z-0 a,
        nav[role="navigation"] span.relative.z-0 span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            text-decoration: none;
            border-right: 1px solid #e2e8f0;
            transition: all 0.15s ease;
            background: #fff;
            box-sizing: border-box;
        }
        nav[role="navigation"] span.relative.z-0 a:last-child,
        nav[role="navigation"] span.relative.z-0 span:last-child {
            border-right: none;
        }
        nav[role="navigation"] span.relative.z-0 a:hover {
            background-color: #f8fafc;
            color: var(--red-main);
        }
        nav[role="navigation"] span.relative.z-0 span[aria-current="page"] {
            background-color: var(--red-main) !important;
            color: #fff !important;
            border-color: var(--red-main);
        }
        nav[role="navigation"] span.relative.z-0 span[aria-disabled="true"] {
            color: #cbd5e1;
            background-color: #f8fafc;
            cursor: not-allowed;
        }

        /* ==========================================================================
           UNIFIED SEARCH BUTTON STYLING (Aesthetics, Size, Color, Symbol)
           ========================================================================== */
        .btn-search-go, .btn-search, .btn-filter, .btn-search-unified {
            background: var(--navy-dark) !important;
            color: white !important;
            padding: 8px 16px !important;
            font-weight: 700 !important;
            font-size: 12px !important;
            border-radius: 8px !important;
            border: none !important;
            cursor: pointer !important;
            height: 38px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 6px !important;
            transition: all 0.2s ease !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            white-space: nowrap !important;
            text-decoration: none !important;
        }
        .btn-search-go:hover, .btn-search:hover, .btn-filter:hover, .btn-search-unified:hover {
            background: var(--navy-light) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 6px rgba(0,0,0,0.15) !important;
            color: white !important;
        }
        .btn-search-go:active, .btn-search:active, .btn-filter:active, .btn-search-unified:active {
            transform: translateY(0) !important;
        }
        .btn-search-go .material-icons, .btn-search .material-icons, .btn-filter .material-icons, .btn-search-unified .material-icons {
            font-size: 16px !important;
            color: white !important;
            display: inline-block !important;
            margin: 0 !important;
            padding: 0 !important;
        }
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
    <div class="navbar-right">
        <div class="navbar-datetime">
            <span class="ndt-date" id="headerDate"></span>
            <span class="ndt-time" id="headerTime"></span>
        </div>
    </div>
</nav>

<aside id="mainSidebar">
    <div class="sidebar-header" style="display:flex; align-items:center; justify-content:space-between;">
        <div class="sidebar-logo-text" style="display:flex; flex-direction:column;">
            <div class="sec-label">PPLC Section</div>
            <div class="sec-sub">Sidebar</div>
        </div>
        <button type="button" class="sidebar-toggle-btn" onclick="toggleSidebarCollapse()" style="background:none; border:none; color:#555; cursor:pointer; display:flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; transition:background 0.2s;" onmouseover="this.style.background='#f0f0f0'" onmouseout="this.style.background='transparent'">
            <span class="material-icons" id="toggleIcon" style="font-size: 20px;">menu_open</span>
        </button>
    </div>
    <nav class="sidebar-nav">
        <a href="{{ route('stock.index') }}" class="sidebar-item {{ request()->routeIs('stock.index') ? 'active' : '' }}"><span class="material-icons">dashboard</span> Dashboard</a>
        <a href="{{ route('rundown.index') }}" class="sidebar-item {{ request()->routeIs('rundown.index') ? 'active' : '' }}"><span class="material-icons">analytics</span> Rundown Stock</a>

        {{-- Master Data Dropdown --}}
        <div class="sidebar-dropdown {{ request()->routeIs('boms.*') || request()->routeIs('materials.*') || request()->routeIs('vendors.*') || request()->routeIs('customers.*') || request()->routeIs('storage_locations.*') ? 'active' : '' }}">
            <div class="sidebar-dropdown-btn" onclick="toggleSidebarDropdown(this)">
                <div style="display:flex;align-items:center;"><span class="material-icons">storage</span> Master Data</div>
                <span class="material-icons arrow">expand_more</span>
            </div>
            <div class="sidebar-dropdown-content">
                <a href="{{ route('boms.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('boms.*') ? 'active' : '' }}">Bill of Materials</a>
                <a href="{{ route('materials.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('materials.*') ? 'active' : '' }}">Master Material</a>
                <a href="{{ route('vendors.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('vendors.*') ? 'active' : '' }}">Vendor</a>
                <a href="{{ route('customers.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('customers.*') ? 'active' : '' }}">Customer</a>
                <a href="{{ route('storage_locations.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('storage_locations.*') ? 'active' : '' }}">Storage Location</a>
            </div>
        </div>

        {{-- PPC Dropdown --}}
        <div class="sidebar-dropdown {{ request()->routeIs('rundown_press.index') || request()->routeIs('schedule_stamping.*') || request()->routeIs('master_stamping.*') || request()->routeIs('production_orders.*') || request()->routeIs('mrp.*') ? 'active' : '' }}">
            <div class="sidebar-dropdown-btn" onclick="toggleSidebarDropdown(this)">
                <div style="display:flex;align-items:center;"><span class="material-icons">assignment</span> PPC</div>
                <span class="material-icons arrow">expand_more</span>
            </div>
            <div class="sidebar-dropdown-content">
                <a href="{{ route('rundown_press.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('rundown_press.index') ? 'active' : '' }}">Simulasi Press</a>
                <a href="{{ route('schedule_stamping.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('schedule_stamping.*') ? 'active' : '' }}">Schedule Stamping</a>
                <a href="{{ route('master_stamping.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('master_stamping.*') ? 'active' : '' }}">Master Data Stamping</a>
                <a href="{{ route('production_orders.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('production_orders.*') ? 'active' : '' }}">Production Order</a>
                <a href="{{ route('mrp.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('mrp.*') ? 'active' : '' }}">MRP</a>
            </div>
        </div>

        {{-- IRM Dropdown (Goods Receipt & Goods Issue dipindah ke Transaksi) --}}
        <div class="sidebar-dropdown {{ (request()->routeIs('purchase_orders.*') || request()->routeIs('stock_overviews.*') || request()->routeIs('summary_kanban.*') || request()->routeIs('business_logs.*')) ? 'active' : '' }}">
            <div class="sidebar-dropdown-btn" onclick="toggleSidebarDropdown(this)">
                <div style="display:flex;align-items:center;"><span class="material-icons">assessment</span> IRM</div>
                <span class="material-icons arrow">expand_more</span>
            </div>
            <div class="sidebar-dropdown-content">
                <a href="{{ route('purchase_orders.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('purchase_orders.*') ? 'active' : '' }}">Purchase Order</a>
                <a href="{{ route('stock_overviews.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('stock_overviews.*') ? 'active' : '' }}">Stock Overview</a>
                <a href="{{ route('summary_kanban.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('summary_kanban.*') ? 'active' : '' }}">Summary Kanban</a>
                <a href="{{ route('business_logs.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('business_logs.*') ? 'active' : '' }}">Business Logs</a>
            </div>
        </div>

        {{-- Logistic Dropdown --}}
        <div class="sidebar-dropdown {{ request()->routeIs('rundown_incoming.index') || request()->routeIs('pallet_mutation.index') || request()->routeIs('smr_vendor.index') || request()->routeIs('smr_customer.index') || request()->routeIs('data_gr.index') || request()->routeIs('data_scrap.index') ? 'active' : '' }}">
            <div class="sidebar-dropdown-btn" onclick="toggleSidebarDropdown(this)">
                <div style="display:flex;align-items:center;"><span class="material-icons">local_shipping</span> LOGISTIC</div>
                <span class="material-icons arrow">expand_more</span>
            </div>
            <div class="sidebar-dropdown-content">
                <a href="{{ route('rundown_incoming.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('rundown_incoming.index') ? 'active' : '' }}">Rundown Incoming</a>
                <a href="{{ route('pallet_mutation.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('pallet_mutation.index') ? 'active' : '' }}">Mutasi Pallet</a>
                <a href="{{ route('smr_vendor.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('smr_vendor.index') ? 'active' : '' }}">SMR Vendor</a>
                <a href="{{ route('smr_customer.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('smr_customer.index') ? 'active' : '' }}">SMR Customer</a>
                <a href="{{ route('data_gr.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('data_gr.index') ? 'active' : '' }}">Data GR</a>
                <a href="{{ route('data_scrap.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('data_scrap.index') ? 'active' : '' }}">Data Scrap</a>
            </div>
        </div>

        {{-- Delivery Dropdown --}}
        <div class="sidebar-dropdown">
            <div class="sidebar-dropdown-btn" onclick="toggleSidebarDropdown(this)">
                <div style="display:flex;align-items:center;"><span class="material-icons">delivery_dining</span> DELIVERY</div>
                <span class="material-icons arrow">expand_more</span>
            </div>
            <div class="sidebar-dropdown-content">
                {{-- Menu delivery akan ditambahkan di sini --}}
            </div>
        </div>

        {{-- Transaksi Dropdown --}}
        <div class="sidebar-dropdown {{ request()->routeIs('goods_receipts.*') || request()->routeIs('goods_issues.*') ? 'active' : '' }}">
            <div class="sidebar-dropdown-btn" onclick="toggleSidebarDropdown(this)">
                <div style="display:flex;align-items:center;"><span class="material-icons">swap_horiz</span> TRANSAKSI</div>
                <span class="material-icons arrow">expand_more</span>
            </div>
            <div class="sidebar-dropdown-content">
                <a href="{{ route('goods_receipts.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('goods_receipts.*') ? 'active' : '' }}">Goods Receipt</a>
                <a href="{{ route('goods_issues.index') }}" class="sidebar-dropdown-item {{ request()->routeIs('goods_issues.*') ? 'active' : '' }}">Goods Issue</a>
            </div>
        </div>

    </nav>
    <div class="sidebar-bottom"><a href="{{ route('stock.index') }}" class="btn-back-sidebar">Kembali</a></div>
</aside>

<main>
    @yield('content')
</main>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<script>
// Prevent layout flashing by applying collapsed state immediately
(function() {
    if (localStorage.getItem('sidebar_collapsed') === 'true') {
        document.body.classList.add('sidebar-collapsed');
    }
})();

function toggleSidebarCollapse() {
    document.body.classList.toggle('sidebar-collapsed');
    const isCollapsed = document.body.classList.contains('sidebar-collapsed');
    localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
    
    // Update the toggle icon
    const icon = document.getElementById('toggleIcon');
    if (icon) {
        icon.textContent = isCollapsed ? 'menu' : 'menu_open';
    }
}

function toggleSidebar(){
    document.getElementById('mainSidebar').classList.toggle('mobile-open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}

function toggleSidebarDropdown(btn){
    // If sidebar is collapsed, expand it first
    if (document.body.classList.contains('sidebar-collapsed')) {
        toggleSidebarCollapse();
    }
    const parent = btn.parentElement;
    parent.classList.toggle('active');
}

// Transform all select.filter-select elements into custom styled premium dropdowns
document.addEventListener('DOMContentLoaded', function() {
    // Set initial toggle icon state
    const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
    const icon = document.getElementById('toggleIcon');
    if (icon) {
        icon.textContent = isCollapsed ? 'menu' : 'menu_open';
    }
    
    // Dynamic tooltips for collapsed state
    document.querySelectorAll('.sidebar-item, .sidebar-dropdown-btn').forEach(el => {
        let text = el.textContent.replace(/expand_more|expand_less/g, '').trim();
        el.setAttribute('title', text);
    });

    const selects = document.querySelectorAll('select.filter-select');
    selects.forEach(select => {
        // Skip if already hidden or multiple
        if (select.style.display === 'none' || select.hasAttribute('multiple')) return;

        const name = (select.getAttribute('name') || '').toLowerCase();
        const id = (select.getAttribute('id') || '').toLowerCase();
        
        // Determine main icon
        let mainIcon = 'apps';
        if (name.includes('vendor') || id.includes('vendor')) mainIcon = 'factory';
        else if (name.includes('customer') || id.includes('customer')) mainIcon = 'person';
        else if (name.includes('location') || name.includes('storage') || id.includes('location') || id.includes('storage')) mainIcon = 'location_on';
        else if (name.includes('status') || id.includes('status')) mainIcon = 'info';
        else if (name.includes('month') || id.includes('month')) mainIcon = 'calendar_month';
        else if (name.includes('quarter') || id.includes('quarter')) mainIcon = 'date_range';
        else if (name.includes('type') || name.includes('tipe') || name.includes('category') || name.includes('kategori')) mainIcon = 'category';
        else if (name.includes('part') || id.includes('part')) mainIcon = 'build';
        else if (name.includes('process') || name.includes('proses') || id.includes('process') || id.includes('proses')) mainIcon = 'settings';
        else if (name.includes('movement') || id.includes('movement')) mainIcon = 'swap_horiz';
        else if (name.includes('remarks') || id.includes('remarks')) mainIcon = 'comment';
        else mainIcon = 'list';

        // Create container
        const container = document.createElement('div');
        container.className = 'custom-select-container ' + select.className;
        if (select.classList.contains('w-full') || select.style.width === '100%') {
            container.classList.add('w-full');
        }

        // Create button
        const btn = document.createElement('div');
        btn.className = 'custom-select-btn';
        
        const btnLeft = document.createElement('div');
        btnLeft.style.display = 'flex';
        btnLeft.style.alignItems = 'center';
        btnLeft.style.gap = '10px';
        
        const iconSpan = document.createElement('span');
        iconSpan.className = 'material-icons';
        iconSpan.textContent = mainIcon;
        btnLeft.appendChild(iconSpan);
        
        const textSpan = document.createElement('span');
        textSpan.textContent = select.options[select.selectedIndex]?.text || '';
        btnLeft.appendChild(textSpan);
        
        btn.appendChild(btnLeft);
        
        const arrowSpan = document.createElement('span');
        arrowSpan.className = 'material-icons arrow';
        arrowSpan.textContent = 'expand_more';
        btn.appendChild(arrowSpan);
        
        container.appendChild(btn);

        // Create dropdown content
        const content = document.createElement('div');
        content.className = 'custom-select-content';
        
        // Populate options
        Array.from(select.options).forEach((opt, idx) => {
            const item = document.createElement('a');
            item.href = '#';
            item.dataset.value = opt.value;
            item.dataset.index = idx;
            if (idx === select.selectedIndex) {
                item.className = 'selected';
            }
            
            // Determine option icon
            let optIcon = 'label';
            const textLower = opt.text.toLowerCase();
            if (opt.value === '' || textLower.includes('semua') || textLower.includes('all')) {
                optIcon = 'apps';
            } else if (name.includes('vendor') || id.includes('vendor')) {
                optIcon = 'business';
            } else if (name.includes('customer') || id.includes('customer')) {
                optIcon = 'person_outline';
            } else if (name.includes('location') || name.includes('storage') || id.includes('location') || id.includes('storage')) {
                optIcon = 'store';
            } else if (name.includes('status') || id.includes('status')) {
                optIcon = 'check_circle_outline';
            } else if (name.includes('month') || id.includes('month') || name.includes('year') || id.includes('year')) {
                optIcon = 'event';
            } else {
                optIcon = mainIcon;
            }
            
            const optIconSpan = document.createElement('span');
            optIconSpan.className = 'material-icons';
            optIconSpan.style.fontSize = '16px';
            optIconSpan.textContent = optIcon;
            item.appendChild(optIconSpan);
            
            const optTextSpan = document.createElement('span');
            optTextSpan.textContent = opt.text;
            item.appendChild(optTextSpan);
            
            item.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Select in original dropdown
                select.selectedIndex = idx;
                
                // Update selected styles
                content.querySelectorAll('a').forEach(a => a.className = '');
                item.className = 'selected';
                
                // Update button text
                textSpan.textContent = opt.text;
                
                // Trigger change event
                const event = new Event('change', { bubbles: true });
                select.dispatchEvent(event);
                
                // Explicitly invoke inline onchange handler if present
                if (typeof select.onchange === 'function') {
                    try {
                        select.onchange();
                    } catch(err) {
                        console.error(err);
                    }
                }
                
                // Close dropdown
                container.classList.remove('active');
            });
            
            content.appendChild(item);
        });
        
        container.appendChild(content);
        
        // Hide original select
        select.style.display = 'none';
        
        // Insert custom container in place of select
        select.parentNode.insertBefore(container, select);

        // Sync programmatic changes to custom UI
        select.addEventListener('change', function() {
            const currentIdx = select.selectedIndex;
            const currentOpt = select.options[currentIdx];
            textSpan.textContent = currentOpt ? currentOpt.text : '';
            content.querySelectorAll('a').forEach((a, aIdx) => {
                a.className = aIdx === currentIdx ? 'selected' : '';
            });
        });
        
        // Toggle dropdown on button click
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Close other custom dropdowns
            document.querySelectorAll('.custom-select-container, .vendor-dropdown-container, .date-dropdown-container, .customer-dropdown-container, .category-dropdown-container').forEach(c => {
                if (c !== container) c.classList.remove('active');
            });
            
            container.classList.toggle('active');
        });
    });
    
    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        document.querySelectorAll('.custom-select-container').forEach(c => {
            if (!c.contains(e.target)) c.classList.remove('active');
        });
    });

    // Real-time clock updating logic
    function updateHeaderClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const dateStr = now.toLocaleDateString('id-ID', options);
        const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        
        const dateEl = document.getElementById('headerDate');
        const timeEl = document.getElementById('headerTime');
        if (dateEl) dateEl.textContent = dateStr;
        if (timeEl) timeEl.textContent = timeStr;
    }
    updateHeaderClock();
    setInterval(updateHeaderClock, 1000);
});
</script>

@stack('scripts')

</body>
</html>
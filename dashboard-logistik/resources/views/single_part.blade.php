@extends('layouts.app')

@push('styles')
<style>

        /* ===== HERO ===== */
        .hero {
            background: var(--red-main);
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }
        .hero-title-block h2 {
            font-size: 28px;
            font-weight: 900;
            color: white;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .hero-title-block h2 .material-icons { font-size: 32px; opacity: 0.8; }
        .hero-meta {
            color: rgba(255,255,255,0.75);
            font-size: 12px;
            font-weight: 500;
            margin-top: 6px;
        }

        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
                align-items: flex-start;
                padding: 20px;
            }
            .hero-actions {
                width: 100%;
                justify-content: flex-start !important;
                overflow-x: auto;
                padding-bottom: 5px;
            }
            .hero-actions > div {
                min-width: 80px !important;
                padding: 6px 10px !important;
            }
            .hero-actions > div div:first-child { font-size: 8px !important; }
            .hero-actions > div div:last-child { font-size: 18px !important; }
        }

        /* ===== CONTENT BODY ===== */
        .content-body { padding: 20px 28px; }

        .card { background: white; border-radius: 12px; border: 1px solid #eee; overflow: visible !important; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .card-header { padding: 14px 20px; border-bottom: 1px solid #f5f5f5; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; overflow: visible !important; }
        
        .toolbar-group { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        
        /* Date Picker Styling */
        .date-picker-wrapper { position: relative; display: flex; align-items: center; background: white; border: 1px solid #ddd; border-radius: 8px; padding: 6px 12px; cursor: pointer; }
        .date-picker-wrapper .material-icons { font-size: 18px; color: var(--red-main); margin-right: 8px; }
        .date-picker-wrapper input { border: none; outline: none; font-size: 13px; font-weight: 600; font-family: 'Inter', sans-serif; color: #333; width: 140px; background: transparent; cursor: pointer; }
        
        .search-box { display: flex; align-items: center; gap: 8px; background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 6px 12px; }
        .search-box .material-icons { font-size: 16px; color: #999; }
        .search-box input { border: none; background: transparent; outline: none; font-size: 12px; font-family: inherit; width: 180px; }

        .vendor-dropdown-container { position: relative; width: 220px; z-index: 150; }
        .vendor-dropdown-btn { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; font-size: 13px; font-weight: 600; color: #333; transition: all .2s; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .vendor-dropdown-btn:hover { border-color: var(--red-main); background: #fffdfd; }
        .vendor-dropdown-btn .material-icons { font-size: 18px; color: var(--red-main); }
        .vendor-dropdown-btn .arrow { color: #999; transition: transform .2s; font-size: 20px; }
        .vendor-dropdown-container.active .arrow { transform: rotate(180deg); }
        .vendor-dropdown-container.active .vendor-dropdown-btn { border-color: var(--red-main); border-bottom-left-radius: 0; border-bottom-right-radius: 0; }

        .vendor-dropdown-content { 
            position: absolute; top: 100%; left: 0; right: 0; 
            background: white; border: 1px solid var(--red-main); border-top: none;
            border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
            max-height: 280px; overflow-y: auto; z-index: 151; 
            display: none; 
            scrollbar-width: thin;
            scrollbar-color: var(--red-main) #f1f1f1;
        }
        .vendor-dropdown-content::-webkit-scrollbar { width: 6px; }
        .vendor-dropdown-content::-webkit-scrollbar-track { background: #f1f1f1; }
        .vendor-dropdown-content::-webkit-scrollbar-thumb { background: var(--red-main); border-radius: 10px; }

        .vendor-dropdown-container.active .vendor-dropdown-content { display: block; }
        .vendor-dropdown-content a { display: flex; align-items: center; gap: 10px; padding: 11px 16px; text-decoration: none; color: #444; font-size: 12px; font-weight: 500; border-bottom: 1px solid #f8f9fa; transition: all .15s; }
        .vendor-dropdown-content a:last-child { border-bottom: none; }
        .vendor-dropdown-content a:hover { background: #fef2f2; color: var(--red-main); padding-left: 20px; }
        .vendor-dropdown-content a.selected { background: var(--red-main); color: white; font-weight: 700; }
        .vendor-dropdown-content a.selected:hover { background: var(--red-dark); color: white; }

        .btn-add { background: var(--navy-dark); color: white; border: none; border-radius: 8px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background .15s; }
        .btn-add:hover { background: var(--navy-light); }
        .btn-add .material-icons { font-size: 16px; }

        .btn-upload { background: var(--red-main); color: white; border: none; border-radius: 8px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: background .15s; text-decoration: none; }
        .btn-upload:hover { background: var(--red-dark); }
        .btn-upload .material-icons { font-size: 16px; }

        .top-scrollbar { width: 100%; overflow-x: auto; overflow-y: hidden; height: 14px; margin-bottom: 4px; }
        .top-scrollbar-dummy { height: 1px; }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; min-width: 1500px; }
        thead tr { background: #f8f9fa; border-bottom: 2px solid #eaeaea; }
        thead th { padding: 10px 12px; text-align: left; font-weight: 800; color: #555; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
        thead th a { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        thead th .material-icons { font-size: 14px; color: #999; }
        tbody tr { border-bottom: 1px solid #f0f0f0; transition: background .1s; }
        tbody tr:hover { background: #fdfdfd; }
        tbody td { padding: 8px 12px; color: #333; white-space: nowrap; vertical-align: middle; }

        .td-jobno { font-weight: 800; color: var(--navy-dark); font-size: 12px; }
        .td-vendor { font-weight: 700; color: #666; }
        .td-num { text-align: right; font-weight: 600; font-size: 12px; }
        
        .inline-input { width: 80px; border: 1.5px solid #ddd; border-radius: 6px; padding: 5px 8px; font-size: 11px; font-weight: 600; font-family: inherit; text-align: right; outline: none; transition: all .15s; }
        .inline-input:focus { border-color: var(--red-main); box-shadow: 0 0 0 3px rgba(192,0,28,0.1); }
        .inline-input.saving { background: #fffbeb; border-color: #f59e0b; }
        .inline-input.saved { border-color: #22c55e; background: #f0fdf4; }

        .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; color: white; display: inline-block; min-width: 70px; text-align: center; }
        .badge-status.over { background: #3b82f6; }
        .badge-status.standar { background: #16a34a; }
        .badge-status.critical { background: #dc2626; }

        .badge-cat { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; display: inline-block; min-width: 90px; text-align: center; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; text-align: center; }
        .empty-state .material-icons { font-size: 48px; color: #ccc; margin-bottom: 16px; }
        .empty-state h3 { font-size: 16px; font-weight: 700; color: #555; margin-bottom: 8px; }

        /* Alerts */
        .alert { margin: 20px 28px 0; padding: 12px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .alert.success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .alert.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Actions Bar Custom Styles */
        .action-btn { 
            height: 38px;
            padding: 0 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-red { background: #dc2626; color: white; }
        .btn-red:hover { background: #b91c1c; transform: translateY(-1px); }
        .btn-navy { background: var(--navy-mid); color: white; }
        .btn-navy:hover { background: var(--navy-light); transform: translateY(-1px); }
        .btn-green { background: #10b981; color: white; }
        .btn-green:hover { background: #059669; transform: translateY(-1px); }
        .btn-outline { background: white; border: 1px solid #ddd; color: #555; }
        .btn-outline:hover { border-color: var(--red-main); color: var(--red-main); }

        /* Sidebar overlay */
        .sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:40;}
        .sidebar-overlay.active{display:block;}
        aside.mobile-open{transform:translateX(0) !important;}
        @media(max-width:768px){
            .hamburger{display:flex;} .header-search{display:none;}
            aside{transform:translateX(-100%);transition:transform .3s ease;z-index:60;}
            main{margin-left:0;}
            .card-header { padding: 15px !important; }
            .card-header > form > div { flex-direction: column !important; align-items: stretch !important; gap: 15px !important; }
            .toolbar-group { flex-direction: column; align-items: stretch; width: 100%; }
            .date-picker-wrapper, .search-box, .vendor-dropdown-container { width: 100% !important; max-width: 100% !important; }
            .search-box input { width: 100%; }
            .table-wrap { padding-bottom: 20px; }
            
            /* Action Buttons Stacking */
            .card-header div[style*="justify-content:flex-end"] { 
                justify-content: flex-start !important; 
                width: 100%; 
                border-top: 1px solid #eee;
                padding-top: 15px;
            }
            .card-header div[style*="border-right"] { 
                border-right: none !important; 
                padding-right: 0 !important; 
                flex-wrap: wrap;
                width: 100%;
            }
            .action-btn { flex: 1; justify-content: center; min-width: 120px; }
            
            /* Modal Stacking */
            .form-row { grid-template-columns: 1fr; }
            .modal { width: 95%; margin: 10px; padding: 20px; }
        }

        /* Modal */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9000; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        .modal { background: white; border-radius: 12px; padding: 24px; width: 100%; max-width: 500px; box-shadow: 0 10px 30px rgba(0,0,0,.2); }
        .modal h3 { font-size: 16px; font-weight: 800; color: var(--navy-dark); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .modal h3 .material-icons { font-size: 20px; color: var(--red-main); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group label { font-size: 11px; font-weight: 700; color: #555; text-transform: uppercase; }
        .form-group input { border: 1px solid #ddd; border-radius: 6px; padding: 8px 10px; font-size: 12px; font-family: inherit; outline: none; }
        .form-group input:focus { border-color: var(--red-main); }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
        .btn-cancel { background: #eee; color: #555; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; }
        .btn-save { background: var(--red-main); color: white; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; }

        .btn-save { background: var(--red-main); color: white; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 700; cursor: pointer; }
</style>
@endpush

@section('content')
    @if(session('sp_success'))
    <div class="alert success"><span class="material-icons">check_circle</span> {{ session('sp_success') }}</div>
    @endif
    @if(session('sp_error'))
    <div class="alert error"><span class="material-icons">error</span> {{ session('sp_error') }}</div>
    @endif

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">view_list</span> Rundown Incoming </h2>
            <div class="hero-meta">Rundown incoming stock harian per vendor</div>
        </div>
        <div class="hero-actions" style="display: flex; gap: 10px; align-items: center; justify-content: flex-end;">
            <!-- Status Summary Cards Navbar -->
            <div style="background: #3b82f6; color: white; border-radius: 8px; padding: 8px 16px; text-align: center; min-width: 100px; box-shadow: 0 4px 6px rgba(0,0,0,0.15);">
                <div style="font-size: 10px; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 3px; text-transform: uppercase; color: rgba(255,255,255,0.9);">OVER STOCK</div>
                <div style="font-size: 24px; font-weight: 900; line-height: 1;">{{ $countOver ?? 0 }}</div>
            </div>
            
            <div style="background: #16a34a; color: white; border-radius: 8px; padding: 8px 16px; text-align: center; min-width: 100px; box-shadow: 0 4px 6px rgba(0,0,0,0.15);">
                <div style="font-size: 10px; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 3px; text-transform: uppercase; color: rgba(255,255,255,0.9);">STANDAR</div>
                <div style="font-size: 24px; font-weight: 900; line-height: 1;">{{ $countStandar ?? 0 }}</div>
            </div>

            <div style="background: #dc2626; color: white; border-radius: 8px; padding: 8px 16px; text-align: center; min-width: 100px; box-shadow: 0 4px 6px rgba(0,0,0,0.15); border: 1px solid rgba(255,255,255,0.1);">
                <div style="font-size: 10px; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 3px; text-transform: uppercase; color: rgba(255,255,255,0.9);">CRITICAL STOCK</div>
                <div style="font-size: 24px; font-weight: 900; line-height: 1;">{{ $countMinim ?? 0 }}</div>
            </div>
        </div>
    </div>

    @if(!$hasData)
    <div class="content-body"> 
        <div class="card">
            <div class="empty-state">
                <span class="material-icons">upload_file</span>
                <h3>Belum ada data Rundown Incoming</h3>
                <p>Silakan upload file Excel Rundown Incoming Vendor di sudut kanan atas.</p>
            </div>
        </div>
    </div>
    @else
    <div class="content-body">
        <div class="card">
            <div class="card-header" style="padding: 20px;">
                <form action="{{ route('single_part.index') }}" method="GET" id="toolbarForm" style="width:100%;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:20px;">
                        
                        <!-- GROUP 1: Filters (Left) -->
                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                            <!-- Date Selector -->
                            <div class="date-picker-wrapper" style="width: 180px;">
                                <span class="material-icons">calendar_month</span>
                                <input type="date" id="calendarInput" onchange="convertAndSubmitDate(this.value)" style="border:none;outline:none;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;color:#333;cursor:pointer;width:100%;background:transparent;">
                                <input type="hidden" name="sheet" id="sheetHidden" value="{{ $selectedSheet }}">
                            </div>

                            <!-- Vendor Selector -->
                            <div class="vendor-dropdown-container" id="vendorDropdownContainer">
                                <div class="vendor-dropdown-btn" onclick="toggleVendorDropdown(event)">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span class="material-icons">factory</span>
                                        <span>{{ $filterVendor ?: 'Semua Vendor' }}</span>
                                    </div>
                                    <span class="material-icons arrow">expand_more</span>
                                </div>
                                <div class="vendor-dropdown-content">
                                    <a href="{{ request()->fullUrlWithQuery(['vendor'=>null, 'page'=>null]) }}" class="{{ $filterVendor==='' ? 'selected' : '' }}">
                                        <span class="material-icons" style="font-size:16px;">apps</span> Semua Vendor
                                    </a>
                                    @foreach($allVendors as $v)
                                    <a href="{{ request()->fullUrlWithQuery(['vendor'=>$v, 'page'=>null]) }}" class="{{ $filterVendor===$v ? 'selected' : '' }}">
                                        <span class="material-icons" style="font-size:16px;">business</span> {{ $v }}
                                    </a>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Customer Selector -->
                            <div class="vendor-dropdown-container" id="customerDropdownContainer">
                                <div class="vendor-dropdown-btn" onclick="toggleCustomerDropdown(event)">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span class="material-icons">person</span>
                                        <span>{{ $filterCustomer ?: 'Semua Customer' }}</span>
                                    </div>
                                    <span class="material-icons arrow">expand_more</span>
                                </div>
                                <div class="vendor-dropdown-content">
                                    <a href="{{ request()->fullUrlWithQuery(['customer'=>null, 'page'=>null]) }}" class="{{ $filterCustomer==='' ? 'selected' : '' }}">
                                        <span class="material-icons" style="font-size:16px;">apps</span> Semua Customer
                                    </a>
                                    @foreach($allCustomers as $c)
                                    <a href="{{ request()->fullUrlWithQuery(['customer'=>$c, 'page'=>null]) }}" class="{{ $filterCustomer===$c ? 'selected' : '' }}">
                                        <span class="material-icons" style="font-size:16px;">person_outline</span> {{ $c }}
                                    </a>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Kategori Selector -->
                            <div class="vendor-dropdown-container" id="categoryDropdownContainer">
                                <div class="vendor-dropdown-btn" onclick="toggleCategoryDropdown(event)">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <span class="material-icons">category</span>
                                        @if($filterCategory === 'ALL')
                                            <span style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;">ALL CATEGORIES</span>
                                        @elseif($filterCategory === 'FINISH PART')
                                            <span style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;">FINISH PART</span>
                                        @else
                                            <span style="background:#e3f2fd;color:#1565c0;border:1px solid #90caf9;border-radius:4px;padding:2px 8px;font-size:11px;font-weight:700;">SINGLE PART</span>
                                        @endif
                                    </div>
                                    <span class="material-icons arrow">expand_more</span>
                                </div>
                                <div class="vendor-dropdown-content" style="z-index: 10000 !important; max-height: none !important; overflow: visible !important;">
                                    {{-- Opsi ALL CATEGORIES --}}
                                    <a href="{{ request()->fullUrlWithQuery(['category'=>'ALL', 'customer'=>null, 'page'=>null]) }}" class="{{ $filterCategory==='ALL' ? 'selected' : '' }}">
                                        <span class="material-icons" style="font-size:16px;color:#475569;">apps</span>
                                        <span>ALL CATEGORIES</span>
                                    </a>
                                    {{-- Opsi SINGLE PART --}}
                                    <a href="{{ request()->fullUrlWithQuery(['category'=>'SINGLE PART', 'customer'=>null, 'page'=>null]) }}" class="{{ $filterCategory==='SINGLE PART' ? 'selected' : '' }}">
                                        <span class="material-icons" style="font-size:16px;color:#1565c0;">label</span>
                                        <span>SINGLE PART</span>
                                        <span style="margin-left:auto;font-size:10px;color:#999;">assy input</span>
                                    </a>
                                    {{-- Opsi FINISH PART --}}
                                    <a href="{{ request()->fullUrlWithQuery(['category'=>'FINISH PART', 'customer'=>null, 'page'=>null]) }}" class="{{ $filterCategory==='FINISH PART' ? 'selected' : '' }}">
                                        <span class="material-icons" style="font-size:16px;color:#2e7d32;">label</span>
                                        <span>FINISH PART</span>
                                        <span style="margin-left:auto;font-size:10px;color:#999;">order input</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Search Box -->
                            <div class="search-box" style="width: 220px;">
                                <button type="submit" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;padding:0;"><span class="material-icons" style="color:#999;">search</span></button>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Cari Job No, Kategori...">
                            </div>

                            <input type="hidden" name="vendor" value="{{ $filterVendor }}">
                            <input type="hidden" name="customer" value="{{ $filterCustomer }}">
                            <input type="hidden" name="category" value="{{ $filterCategory }}">
                            <input type="hidden" name="sort" value="{{ $sortBy }}">
                            <input type="hidden" name="dir" value="{{ $sortDir }}">

                        </div>

                        <!-- GROUP 2: Actions (Right) -->
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            <!-- Data Management -->
                            <div style="display:flex; border-right: 1px solid #eee; padding-right: 10px; gap: 8px;">
                                <button type="button" class="action-btn btn-red" onclick="openDeleteModal()"><span class="material-icons">delete</span> Delete</button>
                                <button type="button" class="action-btn btn-navy" style="background:#1e3a5f" onclick="openIncomingModal()"><span class="material-icons">add_shopping_cart</span> Add Incoming</button>
                                <button type="button" class="action-btn btn-navy" onclick="openModal()"><span class="material-icons">add</span> Add Job No</button>
                            </div>
                            
                            <!-- Excel Operations -->
                            <div style="display:flex; gap: 8px;">
                                <button type="button" class="action-btn btn-green" onclick="openExportModal()"><span class="material-icons">file_download</span> Export</button>
                                <label for="sp_excel_input" class="action-btn btn-navy" style="margin:0; cursor:pointer;">
                                    <span class="material-icons">upload_file</span> Upload
                                </label>
                            </div>
                        </div>
                    </div>
                </form>

                <div style="display:none;">
                    <form action="{{ route('single_part.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                        @csrf
                        <input type="file" name="excel_file" id="sp_excel_input" accept=".xlsx,.xls,.xlsm" onchange="this.form.submit()">
                    </form>
                </div>
            </div>

            {{-- Pagination Simple --}}
            @if(isset($items) && $items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->lastPage() > 1)
            <div style="padding:14px 20px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #f5f5f5; font-size:11px; color:#666; background:#fafafa;">
                <div>Menampilkan {{ $items->firstItem() }} - {{ $items->lastItem() }} dari {{ $items->total() }} item</div>
                <div style="display:flex;gap:4px;">
                    <a href="{{ $items->previousPageUrl() }}" style="padding:6px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:white; {{ $items->onFirstPage() ? 'opacity:0.5;pointer-events:none;' : '' }}">Prev</a>
                    <a href="{{ $items->nextPageUrl() }}" style="padding:6px 12px; border:1px solid #ddd; border-radius:4px; text-decoration:none; color:#333; background:white; {{ !$items->hasMorePages() ? 'opacity:0.5;pointer-events:none;' : '' }}">Next</a>
                </div>
            </div>
            @endif

            {{-- Legend --}}
            <div style="padding: 8px 20px; background: #fafafa; border-bottom: 1px solid #f0f0f0; display:flex; align-items:center; gap:16px; flex-wrap:wrap; font-size:11px; color:#555;">
                <span style="font-weight:700; color:#888;">KETERANGAN INPUT:</span>
                <span style="display:flex;align-items:center;gap:6px;">
                    <span style="background:#e3f2fd;color:#1565c0;border:1px solid #90caf9;border-radius:4px;padding:2px 7px;font-weight:700;">SINGLE PART</span>
                    → Input <strong>ASSY</strong> yang aktif
                </span>
                <span style="display:flex;align-items:center;gap:6px;">
                    <span style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;border-radius:4px;padding:2px 7px;font-weight:700;">FINISH PART</span>
                    → Input customer order (IAMI/GKD/SAP/KAP/GMO) aktif sesuai <strong>Customer</strong>
                </span>
                <span style="display:flex;align-items:center;gap:5px;color:#aaa;">
                    <span style="display:inline-block;width:24px;height:14px;background:#f8f8f8;border:1px dashed #ddd;border-radius:3px;"></span> = Read-only (tidak aktif)
                </span>
                <span style="display:flex;align-items:center;gap:5px;">
                    <span style="display:inline-block;width:24px;height:14px;background:white;border:1.5px solid #2e7d32;border-radius:3px;"></span> = <span style="color:#2e7d32;font-weight:700;">Input aktif Finish Part</span>
                </span>
            <div class="top-scrollbar" id="topScrollbar">
                <div class="top-scrollbar-dummy" id="topScrollbarDummy"></div>
            </div>

            <div class="table-wrap" id="tableWrap">
                @if($items->isEmpty())
                <div class="empty-state">
                    <span class="material-icons" style="font-size:40px;color:#eee;">search_off</span>
                    <p>Tidak ada data yang cocok</p>
                </div>
                @else
                <table>
                    <thead>
                        <tr>
                            <th style="width:36px">#</th>
                            <th><a href="{{ request()->fullUrlWithQuery(['sort'=>'job_no','dir'=>$sortBy==='job_no'&&$sortDir==='asc'?'desc':'asc']) }}">JOB NO @if($sortBy==='job_no')<span class="material-icons">{{$sortDir==='asc'?'arrow_upward':'arrow_downward'}}</span>@endif</a></th>
                            <th>JOB NO FINISH</th>
                            <th>TYPE PALLET</th>
                            <th>KATEGORI</th>
                            <th>CUSTOMER</th>
                            <th style="text-align:right">PRICE/PC</th>
                            <th><a href="{{ request()->fullUrlWithQuery(['sort'=>'vendor','dir'=>$sortBy==='vendor'&&$sortDir==='asc'?'desc':'asc']) }}">VENDOR @if($sortBy==='vendor')<span class="material-icons">{{$sortDir==='asc'?'arrow_upward':'arrow_downward'}}</span>@endif</a></th>
                            <th style="text-align:center">STATUS</th>
                            <th style="text-align:center">MOVEMENT</th>
                            <th style="text-align:center">CYCLE ISSUE</th>
                            <th style="text-align:right">STOCK AWAL ✏️</th>
                            <th style="text-align:right">INCOMING ✏️</th>
                            <th style="text-align:right" title="Aktif untuk Single Part">ASSY ✏️</th>
                            <th style="text-align:right" title="Aktif untuk Finish Part: customer IAMI">IAMI ✏️</th>
                            <th style="text-align:right" title="Aktif untuk Finish Part: customer GKD">GKD ✏️</th>
                            <th style="text-align:right" title="Aktif untuk Finish Part: customer SAP">SAP ✏️</th>
                            <th style="text-align:right" title="Aktif untuk Finish Part: customer KAP">KAP ✏️</th>
                            <th style="text-align:right" title="Aktif untuk Finish Part: customer GMO/TMMIN/FTI">IKAR/TMMIN/FTI ✏️</th>
                            <th style="text-align:right">STOK AKHIR</th>
                            <th style="text-align:right">ALL PRICE</th>
                            <th style="text-align:right">PCS/DAY ✏️</th>
                            <th style="text-align:right">STRENGTH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                        @php
                            $isSinglePart = strtoupper(trim($item->category ?? '')) !== 'FINISH PART';
                            $isFinishPart = !$isSinglePart;
                            // Tentukan field customer order aktif untuk finish part
                            // ADM KAP → kap, ADM SAP → sap, IAMI → iami, GKD → gkd, FTI/GMO/TMMIN → gmo
                            $customerUpper = strtoupper(trim($item->customer ?? ''));
                            if (str_contains($customerUpper, 'KAP'))        $activeCustomerField = 'kap';
                            elseif (str_contains($customerUpper, 'SAP'))    $activeCustomerField = 'sap';
                            elseif (str_contains($customerUpper, 'IAMI'))   $activeCustomerField = 'iami';
                            elseif (str_contains($customerUpper, 'GKD'))    $activeCustomerField = 'gkd';
                            elseif (str_contains($customerUpper, 'GMO') || str_contains($customerUpper, 'TMMIN') || str_contains($customerUpper, 'FTI')) $activeCustomerField = 'gmo';
                            else $activeCustomerField = 'iami';
                        @endphp
                        <tr id="row-{{ $item->id }}">
                            <td style="color:#999">{{ ($items->currentPage()-1)*$perPage + $i + 1 }}</td>
                            <td class="td-jobno">{{ $item->job_no }}</td>
                            <td style="color:#666;font-size:11px;">{{ $item->job_no_finish }}</td>
                            <td style="color:#666;font-size:11px;">{{ $item->type_pallet }}</td>
                            <td style="color:#555">
                                @if($isFinishPart)
                                    <span style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;border-radius:6px;padding:3px 8px;font-size:10px;font-weight:700;">FINISH PART</span>
                                @else
                                    <span style="background:#e3f2fd;color:#1565c0;border:1px solid #90caf9;border-radius:6px;padding:3px 8px;font-size:10px;font-weight:700;">SINGLE PART</span>
                                @endif
                            </td>
                            <td>{{ $item->customer }}</td>
                            <td style="text-align:right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="price_pc" value="{{ number_format($item->price_pc, 0, '.', '') }}" onchange="saveInline(this)" step="1">
                            </td>
                            <td class="td-vendor">{{ $item->vendor }}</td>
                            
                            <td style="text-align:center" id="status-{{ $item->id }}">
                                @php $stClass = strtolower($item->status ?: 'standar'); @endphp
                                <span class="badge-status {{ $stClass }}">{{ $item->status }}</span>
                            </td>
                            
                            <td style="text-align:center">
                                <span class="badge-cat">{{ $item->movement }}</span>
                            </td>
                            
                            <td style="text-align:center">
                                <input type="number" class="inline-input" style="width:50px;text-align:center;font-weight:700;" data-id="{{ $item->id }}" data-field="cycle_issue" value="{{ $item->cycle_issue }}" onchange="saveInline(this)">
                            </td>
                            
                            <!-- Editables -->
                            <td style="text-align:right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="stock_awal" value="{{ number_format($item->stock_awal, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>

                            <td style="text-align:right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="incoming" value="{{ number_format($item->incoming, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>

                            {{-- ASSY: aktif untuk Single Part, read-only untuk Finish Part --}}
                            <td style="text-align:right">
                                @if($isSinglePart)
                                    <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="assy" value="{{ number_format($item->assy, 0, '.', '') }}" onchange="saveInline(this)">
                                @else
                                    <span style="display:inline-block;width:80px;text-align:right;font-size:11px;font-weight:600;color:#aaa;background:#f8f8f8;border:1px dashed #ddd;border-radius:6px;padding:5px 8px;">{{ number_format($item->assy, 0, ',', '.') }}</span>
                                @endif
                            </td>

                            {{-- Customer Order Columns: aktif sesuai customer untuk Finish Part --}}
                            @foreach(['iami','gkd','sap','kap','gmo'] as $coField)
                            <td style="text-align:right">
                                @if($isFinishPart && $activeCustomerField === $coField)
                                    {{-- Aktif untuk Finish Part sesuai customer-nya --}}
                                    <input type="number" class="inline-input" style="border-color:#2e7d32;" data-id="{{ $item->id }}" data-field="{{ $coField }}" value="{{ number_format($item->$coField, 0, '.', '') }}" onchange="saveInline(this)">
                                @elseif($isSinglePart)
                                    {{-- Single Part: semua customer order read-only --}}
                                    <span style="display:inline-block;width:80px;text-align:right;font-size:11px;font-weight:600;color:#aaa;background:#f8f8f8;border:1px dashed #ddd;border-radius:6px;padding:5px 8px;">{{ number_format($item->$coField, 0, ',', '.') }}</span>
                                @else
                                    {{-- Finish Part tapi bukan field customer ini --}}
                                    <span style="display:inline-block;width:80px;text-align:right;font-size:11px;font-weight:600;color:#bbb;background:#f8f8f8;border:1px dashed #eee;border-radius:6px;padding:5px 8px;">{{ number_format($item->$coField, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            @endforeach

                            
                            <td class="td-num" id="stok-{{ $item->id }}" style="color:{{$item->stok_akhir<0?'#ef4444':'inherit'}}">{{ number_format($item->stok_akhir, 0, ',', '.') }}</td>
                            <td class="td-num" id="allprice-{{ $item->id }}">Rp {{ number_format($item->all_price, 0, ',', '.') }}</td>
                            
                            <td style="text-align:right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="pcs_day" value="{{ number_format($item->pcs_day, 0, '.', '') }}" step="0.01" onchange="saveInline(this)">
                            </td>
                            
                            <td class="td-num" id="str-{{ $item->id }}" style="color:{{$item->strength < 2 ? '#dc2626' : ($item->strength >= 5 ? '#3b82f6' : '#16a34a')}}">{{ number_format($item->strength, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>

    @endif

    {{-- Scripts remain unchanged --}}
    {{-- ... --}}

    <!-- MODAL ADD JOB -->
    <div class="modal-overlay" id="modalOverlay" onclick="closeModalOutside(event)">
        <div class="modal">
            <h3><span class="material-icons">add_circle</span> Add New Job No</h3>
            <form action="{{ route('single_part.add') }}" method="POST">
                @csrf
                <input type="hidden" name="sheet_date" value="{{ $selectedSheet }}">
                <div class="form-row">
                    <div class="form-group">
                        <label>Job No</label>
                        <input type="text" name="job_no" required placeholder="e.g. JOB001">
                    </div>
                    <div class="form-group">
                        <label>Vendor</label>
                        <input type="text" name="vendor" required placeholder="e.g. VENDOR A">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Kategori</label>
                        <select name="category" style="border:1px solid #ddd; border-radius:6px; padding:8px 10px; font-size:12px; font-family:inherit; outline:none;">
                            <option value="SINGLE PART">SINGLE PART</option>
                            <option value="FINISH PART">FINISH PART</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Customer</label>
                        <input type="text" name="customer" placeholder="e.g. IAMI">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Stock Awal</label>
                        <input type="number" name="stock_awal" value="0">
                    </div>
                    <div class="form-group">
                        <label>Pcs/Day</label>
                        <input type="number" name="pcs_day" step="0.01" required value="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                    <button type="submit" class="btn-save">Simpan Job</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL ADD INCOMING -->
    <div class="modal-overlay" id="incomingModalOverlay" onclick="closeIncomingModalOutside(event)">
        <div class="modal" style="max-width: 400px;">
            <h3><span class="material-icons">add_shopping_cart</span> Add Incoming Stock</h3>
            <form action="{{ route('single_part.add_incoming') }}" method="POST">
                @csrf
                <input type="hidden" name="sheet_date" value="{{ $selectedSheet }}">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Job Number</label>
                    <input type="text" name="job_no" id="jobNoIncoming" required placeholder="Masukkan Job Number" style="width: 100%; border: 1px solid #ddd; border-radius: 6px; padding: 10px; font-size: 13px; font-family: inherit; outline: none;">
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Incoming Qty</label>
                    <input type="number" name="incoming" required value="0" style="width: 100%; border: 1px solid #ddd; border-radius: 6px; padding: 10px; font-size: 13px; font-family: inherit; outline: none;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeIncomingModal()">Batal</button>
                    <button type="submit" class="btn-save" style="background: #1e3a5f;">Simpan Incoming</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EXPORT EXCEL -->
    <div class="modal-overlay" id="exportModalOverlay" onclick="closeExportModalOutside(event)">
        <div class="modal" style="max-width: 400px;">
            <h3><span class="material-icons">file_download</span> Export Data Excel</h3>
            <p style="font-size: 12px; color: #666; margin-bottom: 20px;">Pilih bulan dan tahun data yang ingin Anda export ke format Excel.</p>
            <form action="{{ route('single_part.export') }}" method="GET">
                <div class="form-group" style="margin-bottom: 15px;">
                    <label>Pilih Bulan</label>
                    <select name="month" required style="width: 100%; border: 1px solid #ddd; border-radius: 6px; padding: 10px; font-size: 13px; font-family: inherit; outline: none;">
                        @php
                            $months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
                            $mIdx = (int)now()->format('m') - 1;
                        @endphp
                        @foreach($months as $idx => $m)
                            <option value="{{ $m }}" {{ $idx === $mIdx ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Pilih Tahun</label>
                    <select name="year" required style="width: 100%; border: 1px solid #ddd; border-radius: 6px; padding: 10px; font-size: 13px; font-family: inherit; outline: none;">
                        @for($y = date('Y'); $y >= 2024; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeExportModal()">Batal</button>
                    <button type="submit" class="btn-save" style="background: #10b981;">Download Excel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL DELETE JOB -->
    <div class="modal-overlay" id="deleteModalOverlay" onclick="closeDeleteModalOutside(event)">
        <div class="modal" style="max-width: 400px;">
            <h3><span class="material-icons">delete_forever</span> Hapus Job No</h3>
            <p style="font-size: 12px; color: #666; margin-bottom: 20px;">Anda akan menghapus Job No dari tanggal <strong>{{ $selectedSheet }}</strong>. Tindakan ini tidak dapat dibatalkan.</p>
            <form action="{{ route('single_part.delete') }}" method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" name="sheet_date" value="{{ $selectedSheet }}">
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Masukkan Job No</label>
                    <input type="text" name="job_no" required placeholder="e.g. JOB001" style="width: 100%; border: 1px solid #ddd; border-radius: 6px; padding: 8px 10px; font-size: 12px; font-family: inherit; outline: none;">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Batal</button>
                    <button type="submit" class="btn-save" style="background: #dc2626;">Hapus Sekarang</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';

function openModal() { document.getElementById('modalOverlay').classList.add('open'); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
function closeModalOutside(e) { if(e.target === document.getElementById('modalOverlay')) closeModal(); }

function openIncomingModal() { 
    document.getElementById('incomingModalOverlay').classList.add('open'); 
    setTimeout(() => {
        document.getElementById('jobNoIncoming').focus();
    }, 100);
}
function closeIncomingModal() { document.getElementById('incomingModalOverlay').classList.remove('open'); }
function closeIncomingModalOutside(e) { if(e.target === document.getElementById('incomingModalOverlay')) closeIncomingModal(); }

function openExportModal() { document.getElementById('exportModalOverlay').classList.add('open'); }
function closeExportModal() { document.getElementById('exportModalOverlay').classList.remove('open'); }
function closeExportModalOutside(e) { if(e.target === document.getElementById('exportModalOverlay')) closeExportModal(); }

function openDeleteModal() { document.getElementById('deleteModalOverlay').classList.add('open'); }
function closeDeleteModal() { document.getElementById('deleteModalOverlay').classList.remove('open'); }
function closeDeleteModalOutside(e) { if(e.target === document.getElementById('deleteModalOverlay')) closeDeleteModal(); }

// Date Picker Script
document.addEventListener('DOMContentLoaded', function() {
    const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
    const selected = "{{ $selectedSheet }}";
    if(selected) {
        const parts = selected.split(' ');
        if(parts.length >= 2) {
            const d = parseInt(parts[0]);
            const mStr = parts[1].toUpperCase();
            const mIndex = months.indexOf(mStr);
            if(mIndex >= 0) {
                const y = new Date().getFullYear();
                const m = (mIndex + 1).toString().padStart(2, '0');
                const day = d.toString().padStart(2, '0');
                document.getElementById('calendarInput').value = `${y}-${m}-${day}`;
            }
        }
    }

    // Sync Top Scrollbar
    const tableWrap = document.getElementById('tableWrap');
    const topScrollbar = document.getElementById('topScrollbar');
    const topScrollbarDummy = document.getElementById('topScrollbarDummy');
    if(tableWrap && topScrollbar && topScrollbarDummy) {
        const table = tableWrap.querySelector('table');
        if(table) {
            // function to update dummy width based on table
            const updateDummyWidth = () => {
                topScrollbarDummy.style.width = table.offsetWidth + 'px';
            };
            updateDummyWidth();
            
            topScrollbar.addEventListener('scroll', function() {
                tableWrap.scrollLeft = topScrollbar.scrollLeft;
            });
            tableWrap.addEventListener('scroll', function() {
                topScrollbar.scrollLeft = tableWrap.scrollLeft;
            });
            window.addEventListener('resize', updateDummyWidth);
        } else {
            topScrollbar.style.display = 'none';
        }
    }
});

function convertAndSubmitDate(val) {
    if(!val) return;
    const d = new Date(val);
    const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
    let day = d.getDate().toString().padStart(2, '0');
    const sheetName = day + ' ' + months[d.getMonth()];
    document.getElementById('sheetHidden').value = sheetName;
    document.getElementById('toolbarForm').submit();
}

function formatRp(num) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(num));
}
function formatNum(num) {
    return new Intl.NumberFormat('id-ID').format(Math.round(num));
}

// Vendor Dropdown Logic
function toggleVendorDropdown(e) {
    if(e) e.stopPropagation();
    document.getElementById('vendorDropdownContainer').classList.toggle('active');
    document.getElementById('customerDropdownContainer').classList.remove('active');
    document.getElementById('categoryDropdownContainer').classList.remove('active');
}

function toggleCustomerDropdown(e) {
    if(e) e.stopPropagation();
    document.getElementById('customerDropdownContainer').classList.toggle('active');
    document.getElementById('vendorDropdownContainer').classList.remove('active');
    document.getElementById('categoryDropdownContainer').classList.remove('active');
}

function toggleCategoryDropdown(e) {
    if(e) e.stopPropagation();
    document.getElementById('categoryDropdownContainer').classList.toggle('active');
    document.getElementById('vendorDropdownContainer').classList.remove('active');
    document.getElementById('customerDropdownContainer').classList.remove('active');
}

document.addEventListener('click', function(e) {
    const vContainer = document.getElementById('vendorDropdownContainer');
    const custContainer = document.getElementById('customerDropdownContainer');
    const cContainer = document.getElementById('categoryDropdownContainer');
    if (vContainer && !vContainer.contains(e.target)) {
        vContainer.classList.remove('active');
    }
    if (custContainer && !custContainer.contains(e.target)) {
        custContainer.classList.remove('active');
    }
    if (cContainer && !cContainer.contains(e.target)) {
        cContainer.classList.remove('active');
    }
});

function saveInline(input) {
    var id    = input.dataset.id;
    var field = input.dataset.field;
    var val   = parseFloat(input.value) || 0;
    
    input.classList.add('saving');

    fetch('{{ route("single_part.inline") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ id: id, field: field, value: val })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            input.classList.remove('saving');
            input.classList.add('saved');
            setTimeout(() => input.classList.remove('saved'), 1500);

            // Update UI
            document.getElementById('stok-'+id).textContent = formatNum(data.stok_akhir);
            document.getElementById('stok-'+id).style.color = data.stok_akhir < 0 ? '#ef4444' : 'inherit';
            
            document.getElementById('allprice-'+id).textContent = formatRp(data.all_price);
            
            let strCell = document.getElementById('str-'+id);
            strCell.textContent = new Intl.NumberFormat('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(data.strength);
            strCell.style.color = data.strength < 2 ? '#dc2626' : (data.strength >= 5 ? '#3b82f6' : '#16a34a');
            
            let statusBadge = document.getElementById('status-'+id).querySelector('.badge-status');
            statusBadge.textContent = data.status;
            statusBadge.className = 'badge-status ' + data.status.toLowerCase();

            // Jika price_pc yang diubah, update value input agar konsisten
            if (field === 'price_pc' && data.price_pc !== undefined) {
                input.value = data.price_pc;
            }
        }
    })
    .catch(err => {
        input.classList.remove('saving');
        alert('Gagal menyimpan!');
    });
}


</script>
@endpush
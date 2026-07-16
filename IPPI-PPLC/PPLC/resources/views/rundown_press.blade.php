@extends('layouts.app')

@push('styles')
<style>
    .hero { background: var(--red-main); padding: 24px 32px; display: flex; align-items: center; justify-content: space-between; gap: 24px; flex-wrap: wrap; }
    .hero-title-block h2 { font-size: 28px; font-weight: 900; color: white; display: flex; align-items: center; gap: 12px; }
    .hero-meta { color: rgba(255,255,255,0.75); font-size: 12px; margin-top: 6px; }
    .content-body { padding: 20px 28px; }
    .card { background: white; border-radius: 12px; border: 1px solid #eee; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
    .card-header { padding: 14px 20px; border-bottom: 1px solid #f5f5f5; }

    @media (max-width: 768px) {
        .hero { flex-direction: column; align-items: flex-start; padding: 20px; }
        .hero-title-block { margin-bottom: 15px; }
        .hero-actions { width: 100%; justify-content: flex-start !important; overflow-x: auto; padding-bottom: 5px; }
        .hero-actions > div { min-width: 85px !important; padding: 6px 12px !important; }
        .hero-actions .stat-card-value { font-size: 18px !important; }
        
        .content-body { padding: 15px; }
        .card-header { padding: 12px !important; }
        .card-header form > div { flex-direction: column !important; align-items: stretch !important; gap: 15px !important; }
        .card-header form div[style*="align-items:center"] { flex-direction: column !important; align-items: stretch !important; }
        .date-dropdown-container, .vendor-dropdown-container, .search-box, .action-btn { width: 100% !important; }
        .date-dropdown-btn, .vendor-dropdown-btn { width: 100% !important; }
        .date-dropdown-content, .vendor-dropdown-content { width: 100% !important; right: 0; }
        
        .pagination-wrap { flex-direction: column; gap: 10px; align-items: center; text-align: center; }
    }

    /* Date Picker */
    .date-picker-wrapper { position: relative; display: flex; align-items: center; background: white; border: 1px solid #ddd; border-radius: 8px; padding: 6px 12px; cursor: pointer; }
    .date-picker-wrapper .material-icons { font-size: 18px; color: var(--red-main); margin-right: 8px; }
    .date-picker-wrapper input[type=date] { border: none; outline: none; font-size: 13px; font-weight: 600; font-family: 'Inter', sans-serif; color: #333; width: 140px; background: transparent; cursor: pointer; }

    /* Date Dropdown */
    .date-dropdown-container { position: relative; z-index: 160; display: inline-block; min-width: 160px; }
    .date-dropdown-btn { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 7px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 12px; font-weight: 700; color: #333; white-space: nowrap; width: 100%; box-sizing: border-box; }
    .date-dropdown-btn .material-icons { font-size: 17px; color: var(--red-main); }
    .date-dropdown-btn .arrow { color: #999; transition: transform .2s; font-size: 18px; margin-left: auto; }
    .date-dropdown-container.active .arrow { transform: rotate(180deg); }
    .date-dropdown-container.active .date-dropdown-btn { border-color: var(--red-main); border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
    .date-dropdown-content { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--red-main); border-top: none; border-radius: 0 0 8px 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.12); max-height: 320px; overflow-y: auto; z-index: 161; display: none; box-sizing: border-box; }
    .date-dropdown-container.active .date-dropdown-content { display: block; }
    .date-dropdown-content a { display: flex; align-items: center; gap: 8px; padding: 9px 14px; text-decoration: none; color: #444; font-size: 12px; font-weight: 600; border-bottom: 1px solid #f8f9fa; transition: all .15s; }
    .date-dropdown-content a:hover { background: #fef2f2; color: var(--red-main); }
    .date-dropdown-content a.selected { background: var(--red-main); color: white; font-weight: 700; }

    /* Vendor Dropdown */
    .vendor-dropdown-container { position: relative; z-index: 150; display: inline-block; min-width: 160px; }
    .vendor-dropdown-btn { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 7px 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 12px; font-weight: 600; color: #333; white-space: nowrap; width: 100%; box-sizing: border-box; }
    .vendor-dropdown-btn .material-icons { font-size: 17px; color: var(--red-main); }
    .vendor-dropdown-btn .arrow { color: #999; transition: transform .2s; font-size: 18px; margin-left: auto; }
    .vendor-dropdown-container.active .arrow { transform: rotate(180deg); }
    .vendor-dropdown-container.active .vendor-dropdown-btn { border-color: var(--red-main); border-bottom-left-radius: 0; border-bottom-right-radius: 0; }
    .vendor-dropdown-content { position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid var(--red-main); border-top: none; border-radius: 0 0 8px 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 280px; overflow-y: auto; z-index: 151; display: none; box-sizing: border-box; }
    .vendor-dropdown-container.active .vendor-dropdown-content { display: block; }
    .vendor-dropdown-content a { display: flex; align-items: center; gap: 8px; padding: 9px 14px; text-decoration: none; color: #444; font-size: 12px; font-weight: 500; border-bottom: 1px solid #f8f9fa; transition: all .15s; }
    .vendor-dropdown-content a:hover { background: #fef2f2; color: var(--red-main); }
    .vendor-dropdown-content a.selected { background: var(--red-main); color: white; font-weight: 700; }

    .search-box { display: flex; align-items: center; gap: 8px; background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 6px 12px; }
    .search-box input { border: none; background: transparent; outline: none; font-size: 12px; font-family: inherit; width: 180px; }

    .action-btn { height: 36px; padding: 0 14px; border-radius: 8px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px; cursor: pointer; border: none; transition: all 0.2s; white-space: nowrap; text-decoration: none; }
    .btn-navy { background: var(--navy-mid); color: white; }
    .btn-navy:hover { background: var(--navy-light); }

    .top-scrollbar { width: 100%; overflow-x: auto; overflow-y: hidden; height: 18px; margin-bottom: 4px; }
    .top-scrollbar-dummy { height: 1px; }
    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 11px; min-width: 1800px; }
    thead tr { background: #f8f9fa; border-bottom: 2px solid #eaeaea; }
    thead th { padding: 10px 12px; text-align: left; font-weight: 800; color: #555; text-transform: uppercase; white-space: nowrap; }
    tbody tr { border-bottom: 1px solid #f0f0f0; }
    tbody tr:hover { background: #fdfdfd; }
    tbody td { padding: 7px 12px; color: #333; white-space: nowrap; }

    .inline-input { width: 80px; border: 1.5px solid #ddd; border-radius: 6px; padding: 4px 7px; font-size: 11px; font-weight: 600; font-family: inherit; text-align: right; outline: none; transition: all .15s; }
    .inline-input:focus { border-color: var(--red-main); box-shadow: 0 0 0 3px rgba(192,0,28,0.1); }
    .inline-input.saving { background: #fffbeb; border-color: #f59e0b; }
    .inline-input.saved { border-color: #22c55e; background: #f0fdf4; }

    .badge-status { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; color: white; display: inline-block; min-width: 70px; text-align: center; }
    .badge-status.over { background: #3b82f6; }
    .badge-status.standar { background: #16a34a; }
    .badge-status.limited { background: #dc2626; }
    .badge-status.minim { background: #dc2626; }
    .badge-status.critical { background: #dc2626; }

    .alert { margin: 20px 28px 0; padding: 12px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .alert.success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .alert.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; text-align: center; }
    .empty-state .material-icons { font-size: 48px; color: #ccc; margin-bottom: 16px; }
    .empty-state h3 { font-size: 16px; font-weight: 700; color: #555; margin-bottom: 8px; }

    .pagination-info { font-size: 12px; color: #737780; }
    .pagination-info strong { color: #333; }
    .pagination { display: flex; gap: 4px; align-items: center; }
    .page-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e5e5e5; background: white; font-size: 12px; font-weight: 600; color: #555; display: flex; align-items: center; justify-content: center; text-decoration: none; }
    .page-btn:hover { border-color: var(--red-main); color: var(--red-main); }
    .page-btn.active { background: var(--red-main); color: white; border-color: var(--red-main); }
    .page-btn.disabled { opacity: .35; pointer-events: none; }
    .page-btn .material-icons { font-size: 15px; }

    .pagination-wrap { padding: 16px 24px 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }

    /* Custom Scrollbar for Tables */
    .top-scrollbar::-webkit-scrollbar { height: 16px; }
    .top-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .top-scrollbar::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
    .top-scrollbar::-webkit-scrollbar-thumb:hover { background: #333; }

    .table-wrap::-webkit-scrollbar { height: 16px; }
    .table-wrap::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .table-wrap::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
    .table-wrap::-webkit-scrollbar-thumb:hover { background: #333; }

    @media (max-width: 768px) {
        .top-scrollbar::-webkit-scrollbar { height: 20px; }
        .table-wrap::-webkit-scrollbar { height: 20px; }
        .top-scrollbar { height: 22px; }
    }
</style>
@endpush

@section('content')
    @if(session('success'))
    <div class="alert success"><span class="material-icons">check_circle</span> {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert error"><span class="material-icons">error</span> {{ session('error') }}</div>
    @endif

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">settings_input_component</span> Simulasi Press</h2>
            <div class="hero-meta">Inventory simulasi press harian per vendor — {{ $selectedSheet }}</div>
        </div>
        <div class="hero-actions" style="display: flex; gap: 10px; align-items: center;">
            <div style="background: #3b82f6; color: white; border-radius: 8px; padding: 8px 16px; text-align: center; min-width: 100px; box-shadow: 0 4px 6px rgba(0,0,0,0.15);">
                <div style="font-size: 10px; font-weight: 800; margin-bottom: 3px;">OVER STOCK</div>
                <div style="font-size: 24px; font-weight: 900; line-height: 1;">{{ $countOver }}</div>
            </div>
            <div style="background: #16a34a; color: white; border-radius: 8px; padding: 8px 16px; text-align: center; min-width: 100px; box-shadow: 0 4px 6px rgba(0,0,0,0.15);">
                <div style="font-size: 10px; font-weight: 800; margin-bottom: 3px;">STANDAR</div>
                <div style="font-size: 24px; font-weight: 900; line-height: 1;">{{ $countStandar }}</div>
            </div>
            <div style="background: #dc2626; color: white; border-radius: 8px; padding: 8px 16px; text-align: center; min-width: 100px; box-shadow: 0 4px 6px rgba(0,0,0,0.15);">
                <div style="font-size: 10px; font-weight: 800; margin-bottom: 3px;">CRITICAL</div>
                <div style="font-size: 24px; font-weight: 900; line-height: 1;">{{ $countMinim }}</div>
            </div>
        </div>
    </div>

    {{-- ══ TOOLBAR: selalu tampil ══ --}}

    <div class="content-body">
        <div class="card">
            {{-- Card Header: date picker + vendor + search + upload --}}
            <div class="card-header">
                <form action="{{ route('rundown_press.index') }}" method="GET" id="toolbarForm" style="width:100%;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                            {{-- Search --}}
                            <div class="search-box" style="padding-right:4px; height: 38px; box-sizing: border-box;">
                                <input type="text" name="search" value="{{ $search }}" placeholder="Cari Job No, Vendor...">
                            </div>
                            <button type="submit" class="btn-search-go">
                                <span class="material-icons">search</span> Cari
                            </button>
                            <a href="{{ route('rundown_press.index', ['sheet' => $selectedSheet]) }}" class="action-btn" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; height: 32px;">Kembali</a>

                            {{-- Date Dropdown --}}
                            <input type="hidden" name="sheet" id="sheetHidden" value="{{ $selectedSheet }}">
                            <div class="date-dropdown-container" id="dateDropdownContainer">
                                <div class="date-dropdown-btn" onclick="toggleDateDropdown(event)">
                                    <span class="material-icons">calendar_month</span>
                                    <span id="dateDropdownLabel">{{ $selectedSheet ?: 'Pilih Tanggal' }}</span>
                                    <span class="material-icons arrow">expand_more</span>
                                </div>
                                <div class="date-dropdown-content" style="min-width: 200px;">
                                    <div style="padding: 10px;">
                                        <div style="font-size: 10px; font-weight: 700; color: #999; margin-bottom: 5px; text-transform: uppercase;">Pilih dari Kalender:</div>
                                        <input type="date" id="calendarInput" onclick="event.stopPropagation()" onmousedown="event.stopPropagation()" ontouchstart="event.stopPropagation()" onchange="submitCalendarDate(this.value)" style="width: 100%; border: 1px solid #ddd; border-radius: 4px; padding: 5px; font-size: 12px; font-family: inherit; cursor: pointer;">
                                    </div>
                                    @if(isset($availableSheets) && count($availableSheets) > 0)
                                        <div style="border-top: 1px solid #eee; max-height: 200px; overflow-y: auto;">
                                            @foreach($availableSheets as $sh)
                                                <a href="{{ request()->fullUrlWithQuery(['sheet' => $sh, 'page' => null]) }}" class="{{ $selectedSheet === $sh ? 'selected' : '' }}">
                                                    <span class="material-icons" style="font-size:15px;">calendar_today</span> {{ $sh }}
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Vendor Dropdown --}}
                            <div class="vendor-dropdown-container" id="vendorDropdownContainer">
                                <div class="vendor-dropdown-btn" onclick="toggleVendorDropdown(event)">
                                    <span class="material-icons">factory</span>
                                    <span>{{ $filterVendor ?: 'Semua Vendor' }}</span>
                                    <span class="material-icons arrow">expand_more</span>
                                </div>
                                <div class="vendor-dropdown-content">
                                    <a href="{{ request()->fullUrlWithQuery(['vendor' => null, 'page' => null]) }}" class="{{ $filterVendor==='' ? 'selected' : '' }}">
                                        <span class="material-icons" style="font-size:15px;">apps</span> Semua Vendor
                                    </a>
                                    @foreach($allVendors as $v)
                                    <a href="{{ request()->fullUrlWithQuery(['vendor' => $v, 'page' => null]) }}" class="{{ $filterVendor===$v ? 'selected' : '' }}">
                                        <span class="material-icons" style="font-size:15px;">business</span> {{ $v }}
                                    </a>
                                    @endforeach
                                </div>
                            </div>

                            <input type="hidden" name="vendor" value="{{ $filterVendor }}">
                            <input type="hidden" name="sort" value="{{ $sortBy }}">
                            <input type="hidden" name="dir" value="{{ $sortDir }}">
                        </div>

                        {{-- Upload Excel + Sync ke Stamping --}}
                        <div style="display:flex; gap:8px; align-items:center;">
                            <form action="{{ route('rundown_press.upload') }}" method="POST" enctype="multipart/form-data" id="uploadPressForm">
                                @csrf
                                <input type="file" name="excel_file" id="press_excel_input" style="display:none" accept=".xlsx,.xls,.xlsm" onchange="this.form.submit()">
                                <label for="press_excel_input" class="action-btn btn-navy" style="cursor:pointer;">
                                    <span class="material-icons">upload_file</span> Upload Excel
                                </label>
                            </form>
                            @if($hasData && $selectedSheet)
                            <button type="button" class="action-btn" id="syncStampingBtn"
                                style="background:#7c3aed; color:white; cursor:pointer;"
                                onclick="syncStokToStamping('{{ $selectedSheet }}')">
                                <span class="material-icons" style="font-size:16px;">sync</span> Sync ke Stamping
                            </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

    @if(!$hasData)

            {{-- Empty state ketika belum ada data --}}

            <div class="empty-state">
                <span class="material-icons">upload_file</span>
                <h3>Belum ada data Simulasi Press</h3>
                <p style="color:#aaa; font-size:13px;">Upload file Excel yang berisi sheet per tanggal<br>(contoh nama sheet: "01 MEI", "02 MEI", dst.)</p>
            </div>

            @else

            {{-- Pagination Info --}}
            @if(isset($items) && $items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->lastPage() > 1)
            <div class="pagination-wrap">
                <div class="pagination-info">Menampilkan <strong>{{ $items->firstItem() }}-{{ $items->lastItem() }}</strong> dari <strong>{{ $items->total() }}</strong></div>
                <div class="pagination">
                    @if($items->onFirstPage())
                    <span class="page-btn disabled"><span class="material-icons">chevron_left</span></span>
                    @else
                    <a class="page-btn" href="{{ $items->previousPageUrl() }}"><span class="material-icons">chevron_left</span></a>
                    @endif

                    @php
                    $pStart = max(1, $items->currentPage() - 2);
                    $pEnd   = min($items->lastPage(), $items->currentPage() + 2);
                    @endphp

                    @if($pStart > 1)
                    <a class="page-btn" href="{{ $items->url(1) }}">1</a>
                    @if($pStart > 2)
                    <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                    @endif
                    @endif

                    @for($p = $pStart; $p <= $pEnd; $p++)
                    @if($p === $items->currentPage())
                    <span class="page-btn active">{{ $p }}</span>
                    @else
                    <a class="page-btn" href="{{ $items->url($p) }}">{{ $p }}</a>
                    @endif
                    @endfor

                    @if($pEnd < $items->lastPage())
                    @if($pEnd < $items->lastPage() - 1)
                    <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                    @endif
                    <a class="page-btn" href="{{ $items->url($items->lastPage()) }}">{{ $items->lastPage() }}</a>
                    @endif

                    @if($items->hasMorePages())
                    <a class="page-btn" href="{{ $items->nextPageUrl() }}"><span class="material-icons">chevron_right</span></a>
                    @else
                    <span class="page-btn disabled"><span class="material-icons">chevron_right</span></span>
                    @endif
                </div>
            </div>
            @endif

            <div class="top-scrollbar" id="topScrollbar">
                <div class="top-scrollbar-dummy" id="topScrollbarDummy"></div>
            </div>

            <div class="table-wrap" id="tableWrap">
                @if($items->isEmpty())
                <div class="empty-state">
                    <span class="material-icons" style="font-size:40px; color:#eee;">search_off</span>
                    <p style="color:#999;">Tidak ada data untuk tanggal <strong>{{ $selectedSheet }}</strong></p>
                </div>
                @else
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>JOB NO (SCHEDULE)</th>
                            <th>JOB NO (STAMPING)</th>
                            <th>MAKER / VENDOR</th>
                            <th style="text-align:center">STATUS</th>
                            <th style="text-align:right">S. AWAL ✏️</th>
                            <th style="text-align:right">QTY/KBN</th>
                            <th style="text-align:right">MDFO (INC) ✏️</th>
                            <th style="text-align:right">ORDER ✏️</th>
                            <th style="text-align:right">PLAN DAY ✏️</th>
                            <th style="text-align:right">PLAN NIGHT ✏️</th>
                            <th style="text-align:right">ACT PROD ✏️</th>
                            <th style="text-align:right">S. AKHIR</th>
                            <th style="text-align:right">PCS/DAY ✏️</th>
                            <th style="text-align:right">STRENGTH</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $i => $item)
                        @php
                            $st = (float)$item->strength;
                            $stColor = $st < 2 ? '#dc2626' : ($st >= 5 ? '#3b82f6' : '#16a34a');
                        @endphp
                        <tr id="row-{{ $item->id }}">
                            <td style="color:#999;">{{ ($items->currentPage()-1)*$perPage + $i + 1 }}</td>
                            <td style="font-weight:800; color:var(--navy-dark);">{{ $item->job_no }}</td>
                            <td style="color:#666;">{{ $item->tipe }}</td>
                            <td style="font-weight:700; color:#555;">{{ $item->vendor }}</td>
                            <td style="text-align:center;" id="status-{{ $item->id }}">
                                @php $stClass = strtolower($item->status ?: 'standar'); @endphp
                                <span class="badge-status {{ $stClass }}">{{ $item->status }}</span>
                            </td>
                            <td style="text-align:right;">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="stock_awal" value="{{ number_format($item->stock_awal, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>
                            <td style="text-align:right;">{{ number_format($item->price, 0, ',', '.') }}</td>
                            <td style="text-align:right;">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="incoming" value="{{ number_format($item->incoming, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>
                            <td style="text-align:right;">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="spare_part" value="{{ number_format($item->spare_part, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>
                            <td style="text-align:right;">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="plan_day" value="{{ number_format($item->plan_day, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>
                            <td style="text-align:right;">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="plan_night" value="{{ number_format($item->plan_night, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>
                            <td style="text-align:right;">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="actual_prod" value="{{ $item->actual_prod !== null ? number_format($item->actual_prod, 0, '.', '') : '' }}" onchange="saveInline(this)">
                            </td>
                            <td style="text-align:right; font-weight:700;" id="stok-{{ $item->id }}" style="color:{{ $item->stok_akhir < 0 ? '#ef4444' : 'inherit' }}">
                                {{ number_format($item->stok_akhir, 0, ',', '.') }}
                            </td>
                            <td style="text-align:right;">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="pcs_day" value="{{ number_format($item->pcs_day, 2, '.', '') }}" step="0.01" onchange="saveInline(this)">
                            </td>
                            <td style="text-align:right; font-weight:800; color:{{ $stColor }};" id="str-{{ $item->id }}">
                                {{ number_format($item->strength, 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

            @if(isset($items) && $items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->lastPage() > 1)
            <div class="pagination-wrap" style="border-top:1px solid #eee; border-bottom:none;">
                <div class="pagination-info">Menampilkan <strong>{{ $items->firstItem() }}-{{ $items->lastItem() }}</strong> dari <strong>{{ $items->total() }}</strong></div>
                <div class="pagination">
                    @if($items->onFirstPage())
                    <span class="page-btn disabled"><span class="material-icons">chevron_left</span></span>
                    @else
                    <a class="page-btn" href="{{ $items->previousPageUrl() }}"><span class="material-icons">chevron_left</span></a>
                    @endif

                    @php
                    $pStart = max(1, $items->currentPage() - 2);
                    $pEnd   = min($items->lastPage(), $items->currentPage() + 2);
                    @endphp

                    @if($pStart > 1)
                    <a class="page-btn" href="{{ $items->url(1) }}">1</a>
                    @if($pStart > 2)
                    <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                    @endif
                    @endif

                    @for($p = $pStart; $p <= $pEnd; $p++)
                    @if($p === $items->currentPage())
                    <span class="page-btn active">{{ $p }}</span>
                    @else
                    <a class="page-btn" href="{{ $items->url($p) }}">{{ $p }}</a>
                    @endif
                    @endfor

                    @if($pEnd < $items->lastPage())
                    @if($pEnd < $items->lastPage() - 1)
                    <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
                    @endif
                    <a class="page-btn" href="{{ $items->url($items->lastPage()) }}">{{ $items->lastPage() }}</a>
                    @endif

                    @if($items->hasMorePages())
                    <a class="page-btn" href="{{ $items->nextPageUrl() }}"><span class="material-icons">chevron_right</span></a>
                    @else
                    <span class="page-btn disabled"><span class="material-icons">chevron_right</span></span>
                    @endif
                </div>
            </div>
            @endif

            @endif {{-- end @if(!$hasData) / @else --}}

        </div>
    </div>
@endsection

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';

// ── Date Picker Setup ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Sync Top Scrollbar
    const tableWrap = document.getElementById('tableWrap');
    const topScrollbar = document.getElementById('topScrollbar');
    const topScrollbarDummy = document.getElementById('topScrollbarDummy');
    if(tableWrap && topScrollbar && topScrollbarDummy) {
        const table = tableWrap.querySelector('table');
        if(table) {
            const updateWidth = () => { topScrollbarDummy.style.width = table.offsetWidth + 'px'; };
            updateWidth();
            topScrollbar.addEventListener('scroll', () => { tableWrap.scrollLeft = topScrollbar.scrollLeft; });
            tableWrap.addEventListener('scroll', () => { topScrollbar.scrollLeft = tableWrap.scrollLeft; });
            window.addEventListener('resize', updateWidth);
        } else {
            topScrollbar.style.display = 'none';
        }
    }

    // Calendar default value
    const selected = "{{ $selectedSheet }}";
    if(selected) {
        const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
        const parts = selected.split(' ');
        if(parts.length >= 2) {
            const mIndex = months.indexOf(parts[1].toUpperCase());
            if(mIndex >= 0) {
                const m = (mIndex + 1).toString().padStart(2, '0');
                const day = parseInt(parts[0]).toString().padStart(2, '0');
                const year = parts[2] ? parts[2] : new Date().getFullYear();
                const calInput = document.getElementById('calendarInput');
                if(calInput) calInput.value = `${year}-${m}-${day}`;
            }
        }
    }
});

function submitCalendarDate(val) {
    if(!val) return;
    const parts = val.split('-');
    if (parts.length < 3) return;
    const monthIndex = parseInt(parts[1], 10) - 1;
    const day = parts[2].padStart(2, '0');
    
    const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
    const sheetName = day + ' ' + months[monthIndex];
    
    const url = new URL(window.location.href);
    url.searchParams.set('sheet', sheetName);
    url.searchParams.set('page', '');
    window.location.href = url.toString();
}

// ── Date Dropdown ────────────────────────────────────────────────────────────
function toggleDateDropdown(e) {
    if(e) e.stopPropagation();
    document.getElementById('dateDropdownContainer').classList.toggle('active');
    // close vendor if open
    const vc = document.getElementById('vendorDropdownContainer');
    if(vc) vc.classList.remove('active');
}
document.addEventListener('click', function(e) {
    const dc = document.getElementById('dateDropdownContainer');
    const cal = document.getElementById('calendarInput');
    if (dc) {
        if (!dc.contains(e.target) && e.target !== cal && document.activeElement !== cal) {
            dc.classList.remove('active');
        }
    }
});

// ── Vendor Dropdown ───────────────────────────────────────────────────────────
function toggleVendorDropdown(e) {
    if(e) e.stopPropagation();
    document.getElementById('vendorDropdownContainer').classList.toggle('active');
    // close date if open
    const dc = document.getElementById('dateDropdownContainer');
    if(dc) dc.classList.remove('active');
}
document.addEventListener('click', function(e) {
    const vc = document.getElementById('vendorDropdownContainer');
    if(vc && !vc.contains(e.target)) vc.classList.remove('active');
});

// ── Inline Save ───────────────────────────────────────────────────────────────
function formatNum(num) {
    return new Intl.NumberFormat('id-ID').format(Math.round(num));
}

function saveInline(input) {
    const id    = input.dataset.id;
    const field = input.dataset.field;
    let val     = input.value;
    
    // If empty string and field is actual_prod, send null to trigger fallback
    if (val === '' && field === 'actual_prod') {
        val = null;
    } else {
        val = parseFloat(val) || 0;
    }

    input.classList.add('saving');

    fetch('{{ route("rundown_press.inline") }}', {
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

            const stokEl = document.getElementById('stok-' + id);
            if(stokEl) {
                stokEl.textContent = formatNum(data.stok_akhir);
                stokEl.style.color = data.stok_akhir < 0 ? '#ef4444' : 'inherit';
            }

            const strEl = document.getElementById('str-' + id);
            if(strEl) {
                strEl.textContent = new Intl.NumberFormat('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}).format(data.strength);
                const st = data.strength;
                strEl.style.color = st <= 0 ? '#dc2626' : (st < 2 ? '#d97706' : (st < 5 ? '#16a34a' : '#2563eb'));
            }

            const statusEl = document.getElementById('status-' + id);
            if(statusEl) {
                const badge = statusEl.querySelector('.badge-status');
                if(badge) {
                    badge.textContent = data.status;
                    badge.className = 'badge-status ' + data.status.toLowerCase();
                }
            }

            if (data.pcs_day !== undefined) {
                const pcsDayInput = document.querySelector(`input[data-id="${id}"][data-field="pcs_day"]`);
                if (pcsDayInput) {
                    pcsDayInput.value = parseFloat(data.pcs_day).toFixed(2);
                }
            }

            if (data.actual_prod !== undefined) {
                const actProdInput = document.querySelector(`input[data-id="${id}"][data-field="actual_prod"]`);
                if (actProdInput) {
                    actProdInput.value = data.actual_prod !== null ? parseFloat(data.actual_prod).toFixed(0) : '';
                }
            }
        } else {
            input.classList.remove('saving');
            alert('Gagal menyimpan: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(() => {
        input.classList.remove('saving');
        alert('Gagal menyimpan!');
    });
}
// ── Sync Stok Akhir -> Plan Schedule Stamping ────────────────────────────────
function syncStokToStamping(sheetDate) {
    const btn = document.getElementById('syncStampingBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="material-icons" style="font-size:16px;animation:spin 1s linear infinite;">sync</span> Menyinkronkan...';
    }

    fetch('/rundown-press/sync-to-stamping?sheet=' + encodeURIComponent(sheetDate), {
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-icons" style="font-size:16px;">sync</span> Sync ke Stamping';
        }
        if (data.success) {
            showToast('✅ ' + data.message, '#16a34a');
        } else {
            showToast('❌ ' + (data.error || 'Gagal sync'), '#dc2626');
        }
    })
    .catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<span class="material-icons" style="font-size:16px;">sync</span> Sync ke Stamping';
        }
        showToast('❌ Gagal menghubungi server', '#dc2626');
    });
}

function showToast(msg, color) {
    const el = document.createElement('div');
    el.style.cssText = `position:fixed;bottom:24px;right:24px;background:${color};color:white;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:700;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,0.25);transition:opacity .4s`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 3000);
}

// CSS for spin animation
const styleEl = document.createElement('style');
styleEl.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
document.head.appendChild(styleEl);
</script>
@endpush
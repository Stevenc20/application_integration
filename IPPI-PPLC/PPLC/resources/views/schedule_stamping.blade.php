@extends('layouts.app')

@push('styles')
<style>
    .hero { background: var(--red-main); padding: 22px 32px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
    .hero-title-block h2 { font-size: 26px; font-weight: 900; color: white; display: flex; align-items: center; gap: 12px; }
    .hero-meta { color: rgba(255,255,255,0.7); font-size: 12px; margin-top: 5px; }

    .stat-box { background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); border-radius: 8px; padding: 8px 18px; text-align: center; min-width: 90px; color: white; }
    .stat-box .s-label { font-size: 9px; font-weight: 800; margin-bottom: 3px; text-transform: uppercase; letter-spacing: .8px; color: rgba(255,255,255,0.7); }
    .stat-box .s-val { font-size: 22px; font-weight: 900; line-height: 1; color: white; }

    .content-body { padding: 18px 26px; }
    .card { background: white; border-radius: 10px; border: 1px solid #eee; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.04); }
    .card-header { padding: 12px 18px; border-bottom: 1px solid #f0f0f0; background: #fafafa; }

    @media (max-width: 768px) {
        .hero { flex-direction: column; align-items: flex-start; padding: 20px; }
        .hero-title-block { margin-bottom: 15px; }
        .hero div[style*="display:flex"] { width: 100%; overflow-x: auto; padding-bottom: 5px; }
        .stat-box { min-width: 80px !important; padding: 6px 12px !important; }
        .stat-box .s-val { font-size: 18px !important; }
        
        .content-body { padding: 15px; }
        .card-header { flex-direction: column !important; align-items: stretch !important; padding: 15px !important; gap: 16px !important; }
        .card-header form { flex-direction: column !important; align-items: stretch !important; gap: 12px !important; width: 100% !important; border-top: 1px solid #eee; padding-top: 15px; }
        .dd-wrap, .dd-btn, .search-box, .action-btn { width: 100% !important; }
        .search-box input { width: 100%; }
        
        .action-buttons-group { 
            flex-direction: column !important; 
            width: 100% !important; 
            align-items: stretch !important;
            gap: 8px !important;
        }
        
        .meta-bar { padding: 10px 15px; gap: 10px; }
    }

    /* Dropdowns */
    .dd-wrap { position: relative; z-index: 200; }
    .dd-wrap.open { z-index: 300; }
    .dd-btn { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 7px 13px; display: inline-flex; align-items: center; gap: 7px; cursor: pointer; font-size: 12px; font-weight: 700; color: #333; white-space: nowrap; min-width: 150px; user-select: none; height: 38px; box-sizing: border-box; }
    .dd-btn .material-icons { font-size: 16px; color: var(--navy-dark); }
    .dd-btn .arr { margin-left: auto; color: #aaa; transition: transform .2s; font-size: 18px; }
    .dd-wrap.open .arr { transform: rotate(180deg); }
    .dd-menu { display: none; position: absolute; top: calc(100% + 2px); left: 0; min-width: 190px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.1); max-height: 300px; overflow-y: auto; z-index: 201; }
    .dd-wrap.open .dd-menu { display: block; }
    .dd-menu a { display: flex; align-items: center; gap: 8px; padding: 9px 14px; text-decoration: none; color: #444; font-size: 12px; font-weight: 500; border-bottom: 1px solid #f5f5f5; transition: background .1s; }
    .dd-menu a:last-child { border-bottom: none; }
    .dd-menu a:hover { background: #f0f4ff; color: var(--navy-dark); }
    .dd-menu a.sel { background: var(--navy-dark); color: white; font-weight: 700; }
 
    .search-box { display: flex; align-items: center; gap: 8px; background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 6px 12px; height: 38px; box-sizing: border-box; }
    .search-box input { border: none; background: transparent; outline: none; font-size: 12px; font-family: inherit; width: 180px; }
    .action-btn { height: 38px; padding: 0 14px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; border: none; transition: all 0.2s; white-space: nowrap; text-decoration: none; box-sizing: border-box; }
    .btn-navy { background: var(--navy-mid); color: white; }
    .btn-navy:hover { background: var(--navy-light); }

    /* Meta bar */
    .meta-bar { padding: 8px 18px; background: #f8f9fb; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 20px; font-size: 11px; color: #666; flex-wrap: wrap; }
    .meta-bar strong { color: #333; }
    .meta-chip { background: white; border: 1px solid #e0e0e0; border-radius: 20px; padding: 3px 10px; font-size: 10px; font-weight: 700; color: #555; }
    .meta-chip.revisi { background: #fef3c7; border-color: #f59e0b; color: #92400e; }

    /* Table */
    .top-scrollbar { overflow-x: auto; overflow-y: hidden; height: 18px; margin-bottom: 2px; }
    .top-scrollbar-dummy { height: 1px; }

    /* Meta bar */
    .meta-bar { padding: 8px 18px; background: #f8f9fb; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 20px; font-size: 11px; color: #666; flex-wrap: wrap; }
    .meta-bar strong { color: #333; }
    .meta-chip { background: white; border: 1px solid #e0e0e0; border-radius: 20px; padding: 3px 10px; font-size: 10px; font-weight: 700; color: #555; }
    .meta-chip.revisi { background: #fef3c7; border-color: #f59e0b; color: #92400e; }

    @media (max-width: 768px) {
        .break-banner { gap: 12px; padding: 10px 15px; }
        .b-item { font-size: 11px; }
    }

    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 10.5px; min-width: 1650px; table-layout: fixed; }
    thead tr { background: var(--navy-dark); }
    thead th { padding: 8px 4px; text-align: left; font-weight: 700; color: rgba(255,255,255,0.85); font-size: 9.5px; text-transform: uppercase; white-space: nowrap; position: sticky; top: 0; z-index: 10; border-right: 1px solid rgba(255,255,255,0.1); }
    thead th.center { text-align: center; }
    tbody tr { border-bottom: 1px solid #f0f0f0; }
    tbody tr:hover { background: #f9fbff; }
    tbody td { padding: 4px 5px; color: #333; white-space: nowrap; font-size: 10.5px; border-right: 1px solid #f0f0f0; overflow: hidden; text-overflow: ellipsis; }
    tbody td.center { text-align: center; }
    tbody td.right { text-align: right; }

    /* Row types */
    tr.row-break { background: #f1f5f9 !important; }
    tr.row-break td { color: #475569; font-weight: 700; font-style: italic; border-right-color: #cbd5e1; border-bottom: 2px solid #e2e8f0; }
    tr.row-break .break-label { color: #0f172a; font-style: normal; text-align: left; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    tr.row-break .bg-grey { background: #d1d5db !important; }

    .badge-type { padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; text-transform: uppercase; }
    .badge-c { background: #3b82f6; color: white; }
    .badge-r { background: #10b981; color: white; }
    .badge-e { background: #f59e0b; color: white; }
    .badge-d { background: #8b5cf6; color: white; }
    .badge-besi { background: #64748b; color: white; }
    .badge-khs { background: #ec4899; color: white; }
    .badge-c2 { background: #06b6d4; color: white; }

    .time-chip { background: #e8f4fd; color: #1565c0; padding: 2px 7px; border-radius: 4px; font-size: 10px; font-weight: 700; font-family: monospace; }
    .keterangan-chip { background: #fef3c7; color: #78350f; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; }

    .a-box { width: 100%; height: 22px; border-radius: 4px; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; color: #333; background: #f8f8f8; }
    .a-box.filled { background: var(--navy-dark); color: white; border-color: var(--navy-dark); }

    /* Inline Inputs */
    .inline-input { width: 100%; border: 1px solid transparent; background: transparent; padding: 2px 4px; font-size: inherit; font-family: inherit; font-weight: inherit; color: inherit; text-align: center; border-radius: 4px; transition: all .1s; outline: none; }
    .inline-input:hover { background: rgba(0,0,0,0.03); border-color: #ddd; }
    .inline-input:focus { background: white; border-color: var(--navy-dark); box-shadow: 0 0 0 2px rgba(13,27,42,0.1); z-index: 5; }
    .inline-input.saving { background: #fff7ed; color: #9a3412; }
    .inline-input.saved { background: #f0fdf4; border-color: #22c55e; color: #166534; }
    
    /* Hide number spin buttons */
    input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    input[type=number] { -moz-appearance: textfield; }

    .alert { margin: 16px 26px 0; padding: 11px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .alert.success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
    .alert.error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    .empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; text-align: center; }
    .empty-state .material-icons { font-size: 52px; color: #ccc; margin-bottom: 16px; }
    .empty-state h3 { font-size: 16px; font-weight: 700; color: #666; margin-bottom: 8px; }

    /* Custom Scrollbar for Tables */
    .top-scrollbar::-webkit-scrollbar { height: 16px; }
    .top-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .top-scrollbar::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
    .top-scrollbar::-webkit-scrollbar-thumb:hover { background: #333; }

    .table-wrap::-webkit-scrollbar { height: 16px; }
    .table-wrap::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .table-wrap::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
    .table-wrap::-webkit-scrollbar-thumb:hover { background: #333; }

    /* For Mobile: Ensure scrollbar is always visible and thick */
    @media (max-width: 768px) {
        .top-scrollbar::-webkit-scrollbar { height: 20px; }
        .table-wrap::-webkit-scrollbar { height: 20px; }
    }

    /* Drag & Drop */
    .drag-handle { cursor: grab; color: #bbb; font-size: 16px; display: inline-flex; align-items: center; justify-content: center; padding: 2px 3px; border-radius: 4px; transition: color .15s, background .15s; vertical-align: middle; user-select: none; }
    .drag-handle:hover { color: #1e3a5f; background: #e8f0fe; }
    .drag-handle:active { cursor: grabbing; color: #1e3a5f; }
    tr.sortable-ghost { opacity: 0.35; background: #bfdbfe !important; outline: 2px dashed #3b82f6; }
    tr.sortable-chosen { background: #eff6ff !important; }
    tr.sortable-drag td { background: white; }
    .sortable-fallback { opacity: 0.85 !important; background: white; box-shadow: 0 8px 32px rgba(0,0,0,0.18); border: 2px solid #3b82f6; border-radius: 6px; }
    #reorderToast { position:fixed; bottom:24px; right:24px; background:#1e3a5f; color:white; padding:11px 18px; border-radius:9px; font-size:12px; font-weight:700; z-index:9999; box-shadow:0 4px 16px rgba(0,0,0,0.25); display:none; align-items:center; gap:8px; transition: all .3s; }
</style>
@endpush

@section('content')
    @if(session('success'))
    <div class="alert success"><span class="material-icons">check_circle</span> {{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert error"><span class="material-icons">error</span> {{ session('error') }}</div>
    @endif
    @if($errors->has('excel_file'))
    <div class="alert error"><span class="material-icons">error</span> {{ $errors->first('excel_file') }}</div>
    @endif

    {{-- HERO --}}
    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">event_note</span> Schedule Stamping</h2>
            <div class="hero-meta">
                Jadwal produksi harian per mesin &amp; shift
                @if($selectedDate) â€” {{ $selectedDate }} @endif
            </div>
        </div>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <div class="stat-box">
                <div class="s-label">Total Plan</div>
                <div class="s-val">{{ number_format($totalPlan, 0, ',', '.') }}</div>
            </div>
            <div class="stat-box">
                <div class="s-label">Total Stroke</div>
                <div class="s-val">{{ $statStroke }}</div>
            </div>
            <div class="stat-box">
                <div class="s-label">Total TPT</div>
                <div class="s-val">{{ $statTpt }}</div>
            </div>
            <div class="stat-box">
                <div class="s-label">Target GSPH</div>
                <div class="s-val">{{ $statTargetGsph }}</div>
            </div>
            <div class="stat-box">
                <div class="s-label">GSPH</div>
                <div class="s-val">{{ $statGsph }}</div>
            </div>
            <div class="stat-box">
                <div class="s-label">Total Finish</div>
                <div class="s-val">{{ $statTotalFinish }}</div>
            </div>
        </div>
    </div>

    <div class="content-body">
        @if(session('success'))
        <div class="alert success" style="background:#dcfce7; color:#166534; padding:12px 20px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-weight:600; font-size:13px; border:1px solid #bbf7d0;">
            <span class="material-icons">check_circle</span> {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="alert error" style="background:#fee2e2; color:#991b1b; padding:12px 20px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; gap:10px; font-weight:600; font-size:13px; border:1px solid #fecaca;">
            <span class="material-icons">error</span> {{ session('error') }}
        </div>
        @endif

        <div class="card">
            {{-- TOOLBAR --}}
            <div class="card-header" style="display:flex; flex-direction:column; align-items:stretch; gap:14px; padding: 20px;">

                {{-- Row 1: Title & Actions --}}
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
                    <div style="font-size: 15px; font-weight: 800; color: var(--navy-dark);">Schedule Stamping</div>
                    
                    {{-- Actions Group --}}
                    <div class="action-buttons-group" style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <button type="button" class="action-btn" style="background:#16a34a; color:white; cursor:pointer;" onclick="openExportModal()">
                            <span class="material-icons">download</span> Export Excel
                        </button>

                        {{-- Add Job Button --}}
                        <button type="button" class="action-btn" style="background:#0d6efd; color:white;" onclick="openAddJobModal()">
                            <span class="material-icons">add_circle</span> Tambah Job
                        </button>

                        {{-- Recalibrate Current Section --}}
                        @if($selectedDate && $selectedShift && $selectedPress)
                        <form action="{{ route('schedule_stamping.recalibrate_section') }}" method="POST" style="display:inline;"
                              onsubmit="return confirm('Recalibrate waktu start/finish untuk {{ $selectedPress }} {{ $selectedShift }} {{ $selectedDate }}?\n\nIni akan memperbaiki semua waktu berdasarkan jam istirahat yang berlaku.')">
                            @csrf
                            <input type="hidden" name="date"  value="{{ $selectedDate }}">
                            <input type="hidden" name="shift" value="{{ $selectedShift }}">
                            <input type="hidden" name="press" value="{{ $selectedPress }}">
                            <button type="submit" class="action-btn" style="background:#f59e0b; color:white;" title="Recalibrate waktu istirahat untuk section ini">
                                <span class="material-icons">schedule</span> Recalibrate
                            </button>
                        </form>
                        @endif

                        {{-- Upload Button (triggers modal) --}}
                        <button type="button" class="action-btn btn-navy" onclick="openUploadModal()">
                            <span class="material-icons">upload_file</span> Upload Excel
                        </button>
                    </div>
                </div>

                {{-- Row 2: Filter Form --}}
                <form action="{{ route('schedule_stamping.index') }}" method="GET" id="stampingForm" style="display:flex; align-items:center; gap:10px; row-gap:12px; flex-wrap:wrap;">

                    {{-- Date Dropdown --}}
                    <input type="hidden" name="date" id="dateVal" value="{{ $selectedDate }}">
                    <div class="dd-wrap" id="ddDate">
                        <div class="dd-btn" onclick="toggleDD('ddDate')">
                            <span class="material-icons">calendar_today</span>
                            <span id="dateLbl">{{ $selectedDate ?: 'Pilih Tanggal' }}</span>
                            <span class="material-icons arr">expand_more</span>
                        </div>
                        <div class="dd-menu">
                            <div style="padding: 10px;">
                                <div style="font-size: 10px; font-weight: 700; color: #999; margin-bottom: 5px; text-transform: uppercase;">Pilih dari Kalender:</div>
                                <input type="date" id="calendarInput" onchange="submitCalendarDate(this.value)" style="width: 100%; border: 1px solid #ddd; border-radius: 4px; padding: 5px; font-size: 12px; font-family: inherit; cursor: pointer;">
                            </div>
                        </div>

                    </div>

                    {{-- Shift Dropdown --}}
                    <input type="hidden" name="shift" id="shiftVal" value="{{ $selectedShift }}">
                    <div class="dd-wrap" id="ddShift">
                        <div class="dd-btn" onclick="toggleDD('ddShift')">
                            <span class="material-icons">schedule</span>
                            <span id="shiftLbl">{{ $selectedShift ?: 'Pilih Shift' }}</span>
                            <span class="material-icons arr">expand_more</span>
                        </div>
                        <div class="dd-menu">
                            @foreach($allShifts as $sh)
                            <a href="{{ request()->fullUrlWithQuery(['shift' => $sh, 'page' => null]) }}"
                               class="{{ $selectedShift === $sh ? 'sel' : '' }}">
                                <span class="material-icons" style="font-size:14px;">nights_stay</span> {{ $sh }}
                            </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Press Dropdown --}}
                    <input type="hidden" name="press" id="pressVal" value="{{ $selectedPress }}">
                    <div class="dd-wrap" id="ddPress">
                        <div class="dd-btn" onclick="toggleDD('ddPress')">
                            <span class="material-icons">precision_manufacturing</span>
                            <span id="pressLbl">{{ $selectedPress ?: 'Pilih Press' }}</span>
                            <span class="material-icons arr">expand_more</span>
                        </div>
                        <div class="dd-menu">
                            @foreach($allPress as $pr)
                            <a href="{{ request()->fullUrlWithQuery(['press' => $pr, 'page' => null]) }}"
                               class="{{ $selectedPress === $pr ? 'sel' : '' }}">
                                <span class="material-icons" style="font-size:14px;">construction</span> {{ $pr }}
                            </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Search --}}
                    <div class="search-box">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari Job Master, Job No...">
                    </div>
                    <button type="submit" class="btn-search-go">
                        <span class="material-icons">search</span> Cari
                    </button>
                    <a href="{{ route('schedule_stamping.index', ['date' => $selectedDate, 'shift' => $selectedShift, 'press' => $selectedPress]) }}" class="action-btn" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; height: 38px;">Kembali</a>

                </form>

            </div>

            {{-- UPLOAD MODAL --}}
            <div id="uploadModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div style="background:white; border-radius:12px; width:400px; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h3 style="margin:0; font-size:18px; font-weight:700; color:#1e293b;">Upload Schedule</h3>
                        <button onclick="closeUploadModal()" style="background:none; border:none; cursor:pointer; color:#94a3b8;"><span class="material-icons">close</span></button>
                    </div>
                    
                    <form action="{{ route('schedule_stamping.upload') }}" method="POST" enctype="multipart/form-data" id="stampingUploadForm">
                        @csrf
                        
                        <div style="margin-bottom:20px;">
                            <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:8px;">Pilih File Excel:</label>
                            <input type="file" name="excel_file" id="stampingFileInput" accept=".xlsx,.xls,.xlsm" required 
                                   style="width:100%; padding:8px; border:1px solid #e2e8f0; border-radius:6px; font-size:13px;">
                        </div>

                        <div style="margin-bottom:24px;">
                            <label style="display:block; font-size:13px; font-weight:600; color:#475569; margin-bottom:12px;">Pilih Shift:</label>
                            <div style="display:flex; gap:16px;">
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="radio" name="target_shift" value="Shift Pagi" checked style="width:16px; height:16px;">
                                    <span style="font-size:13px; font-weight:500;">Shift Pagi</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="radio" name="target_shift" value="Shift Malam" style="width:16px; height:16px;">
                                    <span style="font-size:13px; font-weight:500;">Shift Malam</span>
                                </label>
                                <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                    <input type="radio" name="target_shift" value="AUTO" style="width:16px; height:16px;">
                                    <span style="font-size:13px; font-weight:500;">Otomatis (Semua)</span>
                                </label>
                            </div>
                            <p style="margin-top:8px; font-size:11px; color:#64748b;">Pilihan ini akan menentukan sheet mana yang akan dibaca dari file Excel.</p>
                        </div>

                        <div style="display:flex; gap:12px;">
                            <button type="button" onclick="closeUploadModal()" style="flex:1; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-weight:600; color:#64748b; background:white; cursor:pointer;">Batal</button>
                            <button type="submit" style="flex:1; padding:10px; border:none; border-radius:8px; font-weight:600; color:white; background:#1e3a5f; cursor:pointer;">Upload Sekarang</button>
                        </div>
                    </form>
                </div>
            </div>


            @if(!$hasData)
            {{-- Empty state with add-job prompt --}}
            <div class="empty-state">
                <span class="material-icons" style="color:#3b82f6;">event_available</span>
                <h3>Belum Ada Job untuk {{ $selectedShift }} — {{ $selectedPress }}</h3>
                <p style="color:#aaa;font-size:13px;">Klik <strong>Tambah Job</strong> untuk menambahkan job pertama.<br>
                    Jadwal akan otomatis dihitung mulai <strong>{{ str_contains(strtoupper($selectedShift), 'MALAM') ? '21:00' : '07:30' }}</strong> dengan mengikuti jam istirahat.</p>
                <button type="button" class="action-btn" style="background:#0d6efd; color:white; margin-top:16px; height:40px; padding:0 20px; font-size:13px;" onclick="openAddJobModal()">
                    <span class="material-icons">add_circle</span> Tambah Job Sekarang
                </button>
            </div>

            @else

            {{-- Meta bar --}}
            @if($metaInfo)
            <div class="meta-bar">
                <span><strong>Press:</strong> {{ $metaInfo->press_name }}</span>
                <span><strong>Hari:</strong> {{ $metaInfo->hari }}</span>
                <span><strong>Tanggal:</strong> {{ $metaInfo->tgl }}</span>
                @php
                    $displayJam = '';
                    if (str_contains(strtoupper($selectedShift), 'PAGI')) {
                        $displayJam = '07:30 - 21:00 WIB';
                    } elseif (str_contains(strtoupper($selectedShift), 'MALAM')) {
                        $displayJam = '21:00 - 07:20 WIB';
                    } else {
                        $displayJam = $metaInfo->jam;
                    }
                @endphp
                <span><strong>Jam:</strong> {{ $displayJam }}</span>
                @if($metaInfo->revisi)
                <span class="meta-chip revisi">{{ $metaInfo->revisi }}</span>
                @endif
            </div>
            @endif

            {{-- Table --}}
            <div class="top-scrollbar" id="topScrollbar"><div class="top-scrollbar-dummy" id="topScrollbarDummy"></div></div>
            <div class="table-wrap" id="tableWrap">
                @if($items->isEmpty())
                <div class="empty-state">
                    <span class="material-icons" style="font-size:40px;color:#eee;">search_off</span>
                    <p style="color:#999;">Tidak ada data untuk filter yang dipilih.</p>
                </div>
                @else
                <table>
                    <thead>
                        <tr>
                            <th style="width: 44px;">No #</th>
                            <th style="width: 120px;">JOB MASTER</th>
                            <th class="center" style="width: 50px;">TYPE</th>
                            <th class="center" style="width: 50px;">QTY/PLT</th>
                            <th class="center" style="width: 55px;">KEB.MTL</th>
                            <th class="center" style="width: 50px;">TOT.PLT</th>
                            <th style="width: 110px;">JOB NO.</th>
                            <th class="center" style="width: 55px;">PLAN</th>
                            <th class="center" style="width: 45px;">OK</th>
                            <th class="center" style="width: 45px;">REPAIR</th>
                            <th class="center" style="width: 45px;">REJECT</th>
                            <th class="center" style="width: 40px;">MESIN</th>
                            <th class="center" style="width: 40px;">CT(")</th>
                            <th class="center" style="width: 60px;">PROC.TIME</th>
                            <th class="center" style="width: 60px;">REG.ACT</th>
                            <th class="center" style="width: 35px;">DCT</th>
                            <th class="center" style="width: 35px;">MCT</th>
                            <th class="center" style="width: 60px;">PLAN DCT</th>
                            <th class="center" style="width: 45px; background: #fbbf24; color: #000;">TPT</th>
                            <th class="center" style="width: 55px;">GSPH/ ITEM</th>
                            <th class="center" style="width: 55px;">START</th>
                            <th class="center" style="width: 55px;">FINISH</th>
                            <th class="center" style="width: 55px;">ACT START</th>
                            <th class="center" style="width: 55px;">ACT FINISH</th>
                            <th style="width: 130px;">KETERANGAN</th>
                            @php
                                $pressLetter = 'A';
                                if($selectedPress) {
                                    $lastChar = strtoupper(substr(trim($selectedPress), -1));
                                    if(in_array($lastChar, ['A', 'B', 'C', 'D'])) {
                                        $pressLetter = $lastChar;
                                    }
                                }
                            @endphp
                            <th class="center" style="width: 35px;">{{ $pressLetter }}-1</th>
                            <th class="center" style="width: 35px;">{{ $pressLetter }}-2</th>
                            <th class="center" style="width: 35px;">{{ $pressLetter }}-3</th>
                            <th class="center" style="width: 35px;">{{ $pressLetter }}-4</th>
                            <th class="center" style="width: 55px;">DT (MENIT)</th>
                            <th class="center" style="width: 38px;">HAPUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        @if($item->row_type === 'summary')
                            @continue
                        @endif
                        @if($item->row_type === 'break')
                        <tr class="row-break" data-id="{{ $item->id }}" data-type="break">
                            <td></td> {{-- # --}}
                            <td></td> {{-- JOB MASTER --}}
                            <td class="center"></td> {{-- TYPE --}}
                            <td class="center"></td> {{-- QTY/PLT --}}
                            <td class="center"></td> {{-- KEB.MTL --}}
                            <td class="center"></td> {{-- TOT.PLT --}}
                            <td class="break-label">{{ $item->job_no }}</td> {{-- JOB NO (Label) --}}
                            <td class="center"></td> {{-- PLAN --}}
                            <td class="center"></td> {{-- OK --}}
                            <td class="center"></td> {{-- REPAIR --}}
                            <td class="center"></td> {{-- REJECT --}}
                            <td class="center"></td> {{-- MESIN --}}
                            <td class="center"></td> {{-- CT(") --}}
                            <td class="center"></td> {{-- PROC.TIME --}}
                            <td class="center"></td> {{-- REG.ACT --}}
                            <td class="center">{{ $item->dct }}</td> {{-- DCT --}}
                            <td class="center">{{ $item->mct }}</td> {{-- MCT --}}
                            <td class="center">{{ $item->plan_dct }}</td> {{-- PLAN DCT --}}
                            <td class="center" style="background:#fbbf24; color:#000;">{{ $item->tpt }}</td> {{-- TPT --}}
                            <td class="center"></td> {{-- GSPH --}}
                            <td class="center">{{ $item->start_time }}</td> {{-- START --}}
                            <td class="center">{{ $item->finish_time }}</td> {{-- FINISH --}}
                            <td class="center">{{ $item->act_start }}</td> {{-- ACT START --}}
                            <td class="center">{{ $item->act_finish }}</td> {{-- ACT FINISH --}}
                            <td></td> {{-- KETERANGAN --}}
                            <td class="center bg-grey">{{ $item->a1 }}</td> {{-- A-1 --}}
                            <td class="center bg-grey">{{ $item->a2 }}</td> {{-- A-2 --}}
                            <td class="center bg-grey">{{ $item->a3 }}</td> {{-- A-3 --}}
                            <td class="center bg-grey">{{ $item->a4 }}</td> {{-- A-4 --}}
                            <td class="center"></td> {{-- DT (MENIT) --}}
                            <td class="center"></td> {{-- HAPUS (break row - empty) --}}
                        </tr>
                        @else
                        @php
                            $typeClass = match(strtoupper($item->type_plt ?? '')) {
                                'C'    => 'badge-c',
                                'R'    => 'badge-r',
                                'E'    => 'badge-e',
                                'D'    => 'badge-d',
                                'BESI' => 'badge-besi',
                                'KHS'  => 'badge-khs',
                                'C2'   => 'badge-c2',
                                default => 'badge-c',
                            };
                        @endphp
                        <tr data-id="{{ $item->id }}" data-type="job" @if(isset($item->is_split) && $item->split_part === 2) style="background: #fafafa; color: #666;" @endif>
                            <td style="color:#999; font-size:10px; white-space:nowrap;">
                                @if(isset($item->is_split) && $item->split_part === 2)
                                    <span style="display:inline-block; width:22px;"></span>{{ $item->row_no }} (Cont.)
                                @else
                                    <span class="drag-handle material-icons" title="Geser untuk ubah urutan">drag_indicator</span>{{ $item->row_no }}
                                @endif
                            </td>
                            <td>
                                <input type="text" class="inline-input" style="text-align:left; font-weight:800; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; cursor:not-allowed; @endif" 
                                    data-id="{{ $item->id }}" data-field="job_master" value="{{ $item->job_master }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly @endif>
                            </td>
                            <td class="center" style="font-weight: 700; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; @endif">
                                {{ $item->type_plt }}
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="qty_plt" value="{{ $item->qty_plt }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly style="opacity:0.75; cursor:not-allowed;" @endif>
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="keb_mtl" value="{{ $item->keb_mtl }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly style="opacity:0.75; cursor:not-allowed;" @endif>
                            </td>
                            <td class="center" style="color:#666; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; @endif">{{ $item->total_plt ? number_format($item->total_plt, 1) : '-' }}</td>
                            <td>
                                <input type="text" class="inline-input" style="text-align:left; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; cursor:not-allowed; @endif" 
                                    data-id="{{ $item->id }}" data-field="job_no" value="{{ $item->job_no }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly @endif>
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" style="font-weight:700; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; cursor:not-allowed; @endif" 
                                    data-id="{{ $item->id }}" data-field="plan" value="{{ $item->plan }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly @endif>
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" style="color:#16a34a; font-weight:700; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; cursor:not-allowed; @endif" 
                                    data-id="{{ $item->id }}" data-field="ok" value="{{ $item->ok }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly @endif>
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" style="color:#d97706; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; cursor:not-allowed; @endif" 
                                    data-id="{{ $item->id }}" data-field="repair" value="{{ $item->repair }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly @endif>
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" style="color:#dc2626; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; cursor:not-allowed; @endif" 
                                    data-id="{{ $item->id }}" data-field="reject" value="{{ $item->reject }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly @endif>
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="total_mesin" value="{{ $item->total_mesin }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly style="opacity:0.75; cursor:not-allowed;" @endif>
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" step="0.1"
                                    data-id="{{ $item->id }}" data-field="ct_detik" value="{{ $item->ct_detik }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly style="opacity:0.75; cursor:not-allowed;" @endif>
                            </td>
                            <td class="center" style="color:#666; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; @endif">{{ $item->process_time !== null ? number_format($item->process_time, 1) : '-' }}</td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="reg_active" value="{{ $item->reg_active }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly style="opacity:0.75; cursor:not-allowed;" @endif>
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="dct" value="{{ $item->dct }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly style="opacity:0.75; cursor:not-allowed;" @endif>
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="mct" value="{{ $item->mct }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly style="opacity:0.75; cursor:not-allowed;" @endif>
                            </td>
                            <td class="center" style="color:#666; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; @endif">{{ $item->plan_dct ?: '-' }}</td>
                            <td class="center" style="font-weight:700; background: #fef3c7; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; @endif">{{ $item->tpt !== null ? number_format($item->tpt, 0) : '-' }}</td>
                            <td class="center" style="@if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; @endif">{{ $item->gsph_item !== null ? number_format($item->gsph_item, 0) : '-' }}</td>
                            <td class="center">
                                @if(isset($item->is_split) && $item->split_part === 2)
                                    <span class="time-chip" style="opacity:0.75;">{{ $item->start_time }}</span>
                                @else
                                    <input type="text" class="inline-input" style="font-family:monospace; font-size:10px;" 
                                        data-id="{{ $item->id }}" data-field="start_time" value="{{ $item->start_time }}" onchange="saveInline(this)">
                                @endif
                            </td>
                            <td class="center">
                                <span class="time-chip" style="@if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; @endif">{{ $item->finish_time ?: '-' }}</span>
                            </td>
                            <td class="center">
                                <input type="text" class="inline-input" style="font-family:monospace; font-size:10px; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; cursor:not-allowed; @endif" 
                                    data-id="{{ $item->id }}" data-field="act_start" value="{{ $item->act_start }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly @endif>
                            </td>
                            <td class="center">
                                <input type="text" class="inline-input" style="font-family:monospace; font-size:10px; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; cursor:not-allowed; @endif" 
                                    data-id="{{ $item->id }}" data-field="act_finish" value="{{ $item->act_finish }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly @endif>
                            </td>
                            <td>
                                <input type="text" class="inline-input" style="text-align:left; @if(isset($item->is_split) && $item->split_part === 2) opacity:0.75; cursor:not-allowed; @endif" 
                                    data-id="{{ $item->id }}" data-field="keterangan" value="{{ $item->keterangan }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly @endif>
                            </td>
                            <td class="center">
                                @if(isset($item->is_split))
                                    <div class="a-box {{ $item->a1 ? 'filled' : '' }}" style="opacity: 0.75; display: flex; align-items: center; justify-content: center; height: 22px; width: 100%; border-radius: 4px; font-weight: 800; font-size: 10px;">{{ $item->a1 ?: '' }}</div>
                                @else
                                    <input type="number" class="inline-input a-box {{ $item->a1 ? 'filled' : '' }}" 
                                        data-id="{{ $item->id }}" data-field="a1" value="{{ $item->a1 }}" onchange="saveInline(this)">
                                @endif
                            </td>
                            <td class="center">
                                @if(isset($item->is_split))
                                    <div class="a-box {{ $item->a2 ? 'filled' : '' }}" style="opacity: 0.75; display: flex; align-items: center; justify-content: center; height: 22px; width: 100%; border-radius: 4px; font-weight: 800; font-size: 10px;">{{ $item->a2 ?: '' }}</div>
                                @else
                                    <input type="number" class="inline-input a-box {{ $item->a2 ? 'filled' : '' }}" 
                                        data-id="{{ $item->id }}" data-field="a2" value="{{ $item->a2 }}" onchange="saveInline(this)">
                                @endif
                            </td>
                            <td class="center">
                                @if(isset($item->is_split))
                                    <div class="a-box {{ $item->a3 ? 'filled' : '' }}" style="opacity: 0.75; display: flex; align-items: center; justify-content: center; height: 22px; width: 100%; border-radius: 4px; font-weight: 800; font-size: 10px;">{{ $item->a3 ?: '' }}</div>
                                @else
                                    <input type="number" class="inline-input a-box {{ $item->a3 ? 'filled' : '' }}" 
                                        data-id="{{ $item->id }}" data-field="a3" value="{{ $item->a3 }}" onchange="saveInline(this)">
                                @endif
                            </td>
                            <td class="center">
                                @if(isset($item->is_split))
                                    <div class="a-box {{ $item->a4 ? 'filled' : '' }}" style="opacity: 0.75; display: flex; align-items: center; justify-content: center; height: 22px; width: 100%; border-radius: 4px; font-weight: 800; font-size: 10px;">{{ $item->a4 ?: '' }}</div>
                                @else
                                    <input type="number" class="inline-input a-box {{ $item->a4 ? 'filled' : '' }}" 
                                        data-id="{{ $item->id }}" data-field="a4" value="{{ $item->a4 }}" onchange="saveInline(this)">
                                @endif
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="dt_menit" value="{{ $item->dt_menit }}" onchange="saveInline(this)" @if(isset($item->is_split) && $item->split_part === 2) readonly style="opacity:0.75; cursor:not-allowed;" @endif>
                            </td>
                            <td class="center">
                                @if(!isset($item->is_split) || $item->split_part === 1)
                                    <button onclick="deleteJob({{ $item->id }})" title="Hapus job ini"
                                        style="background:none; border:none; cursor:pointer; color:#dc2626; padding:2px; border-radius:4px; display:inline-flex; align-items:center; transition:background .15s;"
                                        onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='none'">
                                        <span class="material-icons" style="font-size:16px;">delete</span>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8fafc; font-weight: 800; border-top: 2px solid #cbd5e1;">
                            <td colspan="7" class="right">Total Finish</td>
                            <td class="center">{{ number_format($items->sum('plan'), 0, ',', '.') }}</td>
                            <td colspan="5"></td>
                            <td class="center">{{ number_format($items->where('row_type', 'job')->sum('process_time'), 1, ',', '.') }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="center">{{ number_format($items->sum('plan_dct'), 1, ',', '.') }}</td>
                            <td class="center" style="background: #fef3c7;">{{ number_format($items->where('row_type', 'job')->sum('tpt'), 0, ',', '.') }}</td>
                            <td class="center">{{ number_format($items->where('row_type', 'job')->sum('gsph_item'), 0, ',', '.') }}</td>
                            <td colspan="10"></td>
                        </tr>
                    </tfoot>
                </table>
                @endif
            </div>

            {{-- SIGNATURE SECTION --}}
            @if($selectedShift && $hasData)
            <div class="signature-section" style="margin-top: 30px; padding: 20px; background: #ffffff; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03); margin-bottom: 20px;">
                <h4 style="margin: 0 0 15px 0; font-size: 13px; font-weight: 800; color: #1e3a5f; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #1e3a5f; padding-bottom: 6px; display: inline-block;">Lembar Pengesahan (Signatures)</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; text-align: center; font-size: 11px;">
                    
                    {{-- Prepared By --}}
                    <div style="border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; display: flex; flex-direction: column; overflow: hidden;">
                        <div style="font-weight: 800; padding: 8px; background: #1e3a5f; color: white; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">PREPARED BY</div>
                        <div style="font-weight: 700; color: #475569; padding: 6px; background: #f1f5f9; font-size: 9.5px; border-bottom: 1px solid #cbd5e1; text-transform: uppercase;">Team Member PPC</div>
                        <div style="flex-grow: 1; min-height: 60px;"></div>
                        <div style="font-weight: 800; color: #0f172a; padding: 8px; border-top: 1px solid #cbd5e1; background: white; font-size: 12px; text-decoration: underline;">Dandi R.</div>
                    </div>

                    {{-- Checked By --}}
                    <div style="border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; display: flex; flex-direction: column; overflow: hidden;">
                        <div style="font-weight: 800; padding: 8px; background: #1e3a5f; color: white; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">CHECKED BY</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-size: 9.5px; font-weight: 700; color: #475569; text-transform: uppercase;">
                            <div style="padding: 6px; border-right: 1px solid #cbd5e1;">Leader PPC</div>
                            <div style="padding: 6px;">Foreman PPC</div>
                        </div>
                        <div style="flex-grow: 1; min-height: 60px; display: grid; grid-template-columns: 1fr 1fr;">
                            <div style="border-right: 1px solid #e2e8f0;"></div>
                            <div></div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; font-weight: 800; color: #0f172a; border-top: 1px solid #cbd5e1; background: white; font-size: 12px;">
                            <div style="padding: 8px; border-right: 1px solid #cbd5e1; text-decoration: underline;">Cece S.</div>
                            <div style="padding: 8px; text-decoration: underline;">{{ str_contains(strtoupper($selectedShift), 'MALAM') ? 'Syaiful D.' : 'M. Abdullah' }}</div>
                        </div>
                    </div>

                    {{-- Approved By --}}
                    <div style="border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; display: flex; flex-direction: column; overflow: hidden;">
                        <div style="font-weight: 800; padding: 8px; background: #1e3a5f; color: white; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">APPROVED BY</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-size: 9.5px; font-weight: 700; color: #475569; text-transform: uppercase;">
                            <div style="padding: 6px; border-right: 1px solid #cbd5e1;">Sect. Head PPC</div>
                            <div style="padding: 6px;">Sect. Head Prod.</div>
                        </div>
                        <div style="flex-grow: 1; min-height: 60px; display: grid; grid-template-columns: 1fr 1fr;">
                            <div style="border-right: 1px solid #e2e8f0;"></div>
                            <div></div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; font-weight: 800; color: #0f172a; border-top: 1px solid #cbd5e1; background: white; font-size: 12px;">
                            <div style="padding: 8px; border-right: 1px solid #cbd5e1; text-decoration: underline;">{{ str_contains(strtoupper($selectedShift), 'MALAM') ? 'Alvyn' : 'Alberta P. S.' }}</div>
                            <div style="padding: 8px; text-decoration: underline;">Sapriadi</div>
                        </div>
                    </div>

                    {{-- Knowed By --}}
                    <div style="border: 1px solid #cbd5e1; border-radius: 8px; background: #f8fafc; display: flex; flex-direction: column; overflow: hidden;">
                        <div style="font-weight: 800; padding: 8px; background: #1e3a5f; color: white; text-transform: uppercase; font-size: 10px; letter-spacing: 0.5px;">KNOWED BY</div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; background: #f1f5f9; border-bottom: 1px solid #cbd5e1; font-size: 9.5px; font-weight: 700; color: #475569; text-transform: uppercase;">
                            <div style="padding: 6px; border-right: 1px solid #cbd5e1;">Dept. Head PPLC</div>
                            <div style="padding: 6px;">Dept. Head Prod.</div>
                        </div>
                        <div style="flex-grow: 1; min-height: 60px; display: grid; grid-template-columns: 1fr 1fr;">
                            <div style="border-right: 1px solid #e2e8f0;"></div>
                            <div></div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; font-weight: 800; color: #0f172a; border-top: 1px solid #cbd5e1; background: white; font-size: 12px;">
                            <div style="padding: 8px; border-right: 1px solid #cbd5e1; text-decoration: underline;">{{ str_contains(strtoupper($selectedShift), 'MALAM') ? 'Bayu Prakosa' : 'P. Aditya' }}</div>
                            <div style="padding: 8px; text-decoration: underline;">{{ str_contains(strtoupper($selectedShift), 'MALAM') ? 'M. Rajief A.' : 'Heri P.' }}</div>
                        </div>
                    </div>

                </div>
            </div>
            @endif

            @endif
    </div>
</div>

{{-- ADD JOB MODAL --}}
<div id="addJobModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; width:480px; max-height:90vh; overflow-y:auto; padding:28px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
            <div>
                <h3 style="margin:0; font-size:18px; font-weight:700; color:#1e293b;">Tambah Job ke Antrian</h3>
                <p style="margin:4px 0 0; font-size:12px; color:#64748b;" id="addJobSubtitle">{{ $selectedShift }} &mdash; {{ $selectedPress }}</p>
            </div>
            <button onclick="closeAddJobModal()" style="background:none; border:none; cursor:pointer; color:#94a3b8; flex-shrink:0;"><span class="material-icons">close</span></button>
        </div>

        <form action="{{ route('schedule_stamping.add_job') }}" method="POST" id="addJobForm">
            @csrf
            <input type="hidden" name="date"  id="addJobDate"  value="{{ $selectedDate }}">
            <input type="hidden" name="shift" id="addJobShift" value="{{ $selectedShift }}">
            <input type="hidden" name="press" id="addJobPress" value="{{ $selectedPress }}">

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:18px;">
                <div style="grid-column:1/-1; position:relative;">
                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px;">Job No. <span style="color:#dc2626">*</span></label>
                    <input type="text" name="job_no" id="addJobNo" required autocomplete="off" placeholder="Ketik atau cari Job No dari master..."
                        style="width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:13px; font-weight:700; font-family:inherit; box-sizing:border-box;">
                    <div id="addJobNoSuggest" style="display:none; position:absolute; top:100%; left:0; width:100%; background:white; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); max-height:200px; overflow-y:auto; z-index:999;"></div>
                    <div id="addJobMasterPreview" style="display:none; margin-top:8px; padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; font-size:11px; color:#475569; line-height:1.5;"></div>
                    <input type="hidden" name="job_master" id="addJobMasterVal">
                    <input type="hidden" name="type_plt" id="addJobTypePltVal">
                    <input type="hidden" name="qty_plt" id="addJobQtyPltVal">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px;">Plan (pcs)</label>
                    <input type="number" name="plan" placeholder="0" min="0"
                        style="width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:13px; font-family:inherit; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px;">CT (detik)</label>
                    <input type="number" name="ct_detik" placeholder="0" min="0" step="0.1"
                        style="width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:13px; font-family:inherit; box-sizing:border-box;">
                </div>
                <div style="grid-column: 1 / -1; margin-top: 4px;">
                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:8px; text-transform:uppercase; letter-spacing:.5px;">Pilihan Mesin yang Digunakan <span style="color:#dc2626">*</span></label>
                    <div style="display:flex; gap:20px; align-items:center;">
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#334155; cursor:pointer;">
                            <input type="checkbox" name="machines[]" value="1" checked style="width:17px; height:17px; cursor:pointer;" class="machine-checkbox">
                            <span class="machine-label">1</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#334155; cursor:pointer;">
                            <input type="checkbox" name="machines[]" value="2" checked style="width:17px; height:17px; cursor:pointer;" class="machine-checkbox">
                            <span class="machine-label">2</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#334155; cursor:pointer;">
                            <input type="checkbox" name="machines[]" value="3" checked style="width:17px; height:17px; cursor:pointer;" class="machine-checkbox">
                            <span class="machine-label">3</span>
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#334155; cursor:pointer;">
                            <input type="checkbox" name="machines[]" value="4" checked style="width:17px; height:17px; cursor:pointer;" class="machine-checkbox">
                            <span class="machine-label">4</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px;">DCT (menit)</label>
                    <input type="number" name="dct" placeholder="0" min="0"
                        style="width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:13px; font-family:inherit; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px;">Reg. Active (menit)</label>
                    <input type="number" name="reg_active" placeholder="0" min="0"
                        style="width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:13px; font-family:inherit; box-sizing:border-box;">
                </div>
                <div style="grid-column:1/-1;">
                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:6px; text-transform:uppercase; letter-spacing:.5px;">Keterangan</label>
                    <input type="text" name="keterangan" placeholder="Opsional..."
                        style="width:100%; padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:13px; font-family:inherit; box-sizing:border-box;">
                </div>
            </div>

            <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:7px; padding:10px 12px; margin-bottom:18px; font-size:12px; color:#0369a1;">
                <span class="material-icons" style="font-size:14px; vertical-align:middle;">info</span>
                Job dijadwalkan otomatis setelah job terakhir, melewati jam istirahat yang berlaku.
            </div>

            <div style="display:flex; gap:12px;">
                <button type="button" onclick="closeAddJobModal()" style="flex:1; padding:11px; border:1px solid #e2e8f0; border-radius:8px; font-weight:600; color:#64748b; background:white; cursor:pointer; font-family:inherit;">Batal</button>
                <button type="submit" style="flex:2; padding:11px; border:none; border-radius:8px; font-weight:700; color:white; background:#0d6efd; cursor:pointer; font-family:inherit; font-size:14px;">
                    Tambah Job ke Antrian
                </button>
            </div>
        </form>
    </div>
</div>

{{-- EXPORT MODAL --}}
<div id="exportModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:white; border-radius:12px; width:420px; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); font-family:inherit;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h3 style="margin:0; font-size:18px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px;">
                <span class="material-icons" style="color:#16a34a;">download</span> Export Excel
            </h3>
            <button onclick="closeExportModal()" style="background:none; border:none; cursor:pointer; color:#94a3b8;"><span class="material-icons">close</span></button>
        </div>
        
        <div style="margin-bottom:20px;">
            <p style="font-size:13px; color:#475569; margin:0 0 16px 0; line-height:1.5;">
                Pilih tanda tangan untuk kolom <strong>Sect. Head PPC</strong> yang akan diterapkan pada seluruh press di file Excel hasil export.
            </p>
            
            <label style="display:block; font-size:13px; font-weight:600; color:#1e293b; margin-bottom:10px;">Pilih Tanda Tangan:</label>
            
            <div style="display:flex; flex-direction:column; gap:12px;">
                <label style="display:flex; align-items:center; gap:12px; padding:12px; border:2px solid #e2e8f0; border-radius:8px; cursor:pointer; transition:all 0.2s;" class="export-opt-label" id="optLabelAlberta">
                    <input type="radio" name="export_sect_head" value="Alberta P. S." checked style="width:18px; height:18px; cursor:pointer;" onclick="selectExportOpt('Alberta P. S.')">
                    <div>
                        <div style="font-size:14px; font-weight:700; color:#1e293b;">Alberta P. S.</div>
                    </div>
                </label>
                
                <label style="display:flex; align-items:center; gap:12px; padding:12px; border:2px solid #e2e8f0; border-radius:8px; cursor:pointer; transition:all 0.2s;" class="export-opt-label" id="optLabelAlvyn">
                    <input type="radio" name="export_sect_head" value="Alvyn" style="width:18px; height:18px; cursor:pointer;" onclick="selectExportOpt('Alvyn')">
                    <div>
                        <div style="font-size:14px; font-weight:700; color:#1e293b;">Alvyn</div>
                    </div>
                </label>
            </div>
        </div>

        <div style="display:flex; gap:12px; margin-top:24px;">
            <button type="button" onclick="closeExportModal()" style="flex:1; padding:10px; border:1px solid #e2e8f0; border-radius:8px; font-weight:600; color:#64748b; background:white; cursor:pointer; font-family:inherit; font-size:13px;">Batal</button>
            <button type="button" onclick="confirmExport()" style="flex:1; padding:10px; border:none; border-radius:8px; font-weight:600; color:white; background:#16a34a; cursor:pointer; font-family:inherit; font-size:13px; display:flex; align-items:center; justify-content:center; gap:6px;">
                <span class="material-icons" style="font-size:18px;">download</span> Export
            </button>
        </div>
    </div>
</div>

@endsection

{{-- Toast notification --}}
<div id="reorderToast">
    <span class="material-icons" style="font-size:18px;">check_circle</span>
    <span id="reorderToastMsg">Urutan berhasil diperbarui!</span>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
// â”€â”€ Dropdown toggle â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function toggleDD(id) {
    document.querySelectorAll('.dd-wrap.open').forEach(el => { if(el.id !== id) el.classList.remove('open'); });
    document.getElementById(id).classList.toggle('open');
}

function submitCalendarDate(val) {
    if(!val) return;
    const d = new Date(val);
    const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
    let day = d.getDate().toString().padStart(2, '0');
    const sheetName = day + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    const url = new URL(window.location.href);
    url.searchParams.set('date', sheetName);
    url.searchParams.set('page', '');
    window.location.href = url.toString();
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.dd-wrap')) {
        document.querySelectorAll('.dd-wrap.open').forEach(el => el.classList.remove('open'));
    }
});

// â”€â”€ DOMContentLoaded: init calendar + drag & drop â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
document.addEventListener('DOMContentLoaded', function() {

    // Calendar default value
    const selected = "{{ $selectedDate }}";
    if(selected) {
        const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
        const parts = selected.split(' ');
        if(parts.length >= 3) {
            const mIndex = months.indexOf(parts[1].toUpperCase());
            if(mIndex >= 0) {
                const m = (mIndex + 1).toString().padStart(2, '0');
                const day = parseInt(parts[0]).toString().padStart(2, '0');
                const calInput = document.getElementById('calendarInput');
                if(calInput) calInput.value = `${parts[2]}-${m}-${day}`;
            }
        }
    }

    // ──────────────────────── Drag & Drop (SortableJS) ──────────────────────────────────────────────
    const tbody = document.querySelector('#tableWrap table tbody');
    if (tbody && typeof Sortable !== 'undefined') {
        Sortable.create(tbody, {
            filter: '[data-type="break"]',     // break rows tidak bisa di-drag
            preventOnFilter: false,
            handle: '.drag-handle',             // harus pegang handle icon
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            // forceFallback: wajib agar drag vertikal bekerja dalam overflow-x container
            forceFallback: true,
            fallbackClass: 'sortable-fallback',
            fallbackTolerance: 5,              // toleransi piksel sebelum dianggap drag (bukan klik)
            scroll: true,                      // auto scroll saat drag ke tepi
            scrollSensitivity: 60,
            scrollSpeed: 10,
            onStart: function() {
                // Nonaktifkan inline input saat drag untuk menghindari konflik
                document.querySelectorAll('.inline-input').forEach(el => el.setAttribute('tabindex', '-1'));
            },
            onEnd: function(evt) {
                // Aktifkan kembali inline input
                document.querySelectorAll('.inline-input').forEach(el => el.removeAttribute('tabindex'));

                if (evt.oldIndex === evt.newIndex) return;

                // Kumpulkan semua ID job rows dalam urutan DOM yang baru
                const allRows = tbody.querySelectorAll('tr[data-type="job"]');
                const orderedIds = Array.from(allRows)
                    .map(r => parseInt(r.dataset.id))
                    .filter((id, idx, self) => self.indexOf(id) === idx);
                if (!orderedIds.length) return;

                showToast('⏳ Menyimpan urutan...', '#7c3aed');

                fetch('{{ route("schedule_stamping.reorder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ordered_ids: orderedIds })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showToast('✅ Urutan disimpan! Klik Recalibrate untuk menghitung waktu.', '#f59e0b');
                        // Tampilkan banner hint recalibrate
                        showRecalibrateHint();
                    } else {
                        showToast('❌ Gagal: ' + (data.message || 'Error'), '#dc2626');
                    }
                })
                .catch(() => showToast('âŒ Gagal menghubungi server', '#dc2626'));
            }
        });
    }

    // Sync top scrollbar
    const tw = document.getElementById('tableWrap');
    const ts = document.getElementById('topScrollbar');
    const td = document.getElementById('topScrollbarDummy');
    if(tw && ts && td) {
        const t = tw.querySelector('table');
        if(t) {
            td.style.width = t.offsetWidth + 'px';
            ts.addEventListener('scroll', () => tw.scrollLeft = ts.scrollLeft);
            tw.addEventListener('scroll', () => ts.scrollLeft = tw.scrollLeft);
            window.addEventListener('resize', () => { td.style.width = t.offsetWidth + 'px'; });
        } else { ts.style.display = 'none'; }
    }
});

// â”€â”€ Inline Save â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function saveInline(input) {
    const id    = input.dataset.id;
    const field = input.dataset.field;
    const val   = input.value;

    input.classList.add('saving');

    fetch('{{ route("schedule_stamping.inline") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ id: id, field: field, value: val })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            input.classList.remove('saving');
            input.classList.add('saved');
            setTimeout(() => { window.location.reload(); }, 800);
        } else {
            input.classList.remove('saving');
            alert('Gagal menyimpan: ' + (data.message || 'Error'));
        }
    })
    .catch(err => {
        input.classList.remove('saving');
        console.error(err);
        alert('Gagal menyimpan!');
    });
}

// â”€â”€ Modal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function openUploadModal() { document.getElementById('uploadModal').style.display = 'flex'; }
function closeUploadModal() { document.getElementById('uploadModal').style.display = 'none'; }

// â”€â”€ Toast â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function showToast(msg, color) {
    const toast = document.getElementById('reorderToast');
    const msgEl = document.getElementById('reorderToastMsg');
    if (!toast || !msgEl) return;
    msgEl.textContent = msg;
    toast.style.background = color || '#1e3a5f';
    toast.style.display = 'flex';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => { toast.style.display = 'none'; }, 4000);
}

function showRecalibrateHint() {
    // Tampilkan banner di bawah toolbar yang mengingatkan user untuk klik Recalibrate
    let banner = document.getElementById('recalibrateHint');
    if (!banner) {
        banner = document.createElement('div');
        banner.id = 'recalibrateHint';
        banner.style.cssText = 'background:#fef3c7;border:2px solid #f59e0b;color:#92400e;padding:10px 18px;display:flex;align-items:center;gap:10px;font-size:12px;font-weight:700;';
        banner.innerHTML = '<span class="material-icons" style="font-size:18px;">schedule</span>' +
            '<span>Urutan sudah disimpan. Klik tombol <strong style="color:#b45309;">Recalibrate</strong> untuk menghitung ulang waktu start/finish sesuai jam istirahat.</span>' +
            '<button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#92400e;font-size:18px;line-height:1;">✕</button>';
        // Sisipkan setelah card-header
        const cardHeader = document.querySelector('.card-header');
        if (cardHeader && cardHeader.nextSibling) {
            cardHeader.parentNode.insertBefore(banner, cardHeader.nextSibling);
        }
    }
    // Buat tombol Recalibrate berkedip/pulse untuk menarik perhatian
    const recalBtn = document.querySelector('button[type="submit"][title*="Recalibrate"], form[action*="recalibrate"] button[type="submit"]');
    if (recalBtn) {
        recalBtn.style.animation = 'recalPulse 0.8s ease-in-out infinite alternate';
        // Tambah keyframe jika belum ada
        if (!document.getElementById('recalPulseStyle')) {
            const s = document.createElement('style');
            s.id = 'recalPulseStyle';
            s.textContent = '@keyframes recalPulse { from {box-shadow:0 0 0 0 rgba(245,158,11,0.7);} to {box-shadow:0 0 0 8px rgba(245,158,11,0);} }';
            document.head.appendChild(s);
        }
    }
}

// ── Add Job Modal ────────────────────────────────────────────────────────────────────
function openAddJobModal() {
    const dateVal  = document.getElementById('dateVal')  ? document.getElementById('dateVal').value  : '';
    const shiftVal = document.getElementById('shiftVal') ? document.getElementById('shiftVal').value : '';
    const pressVal = document.getElementById('pressVal') ? document.getElementById('pressVal').value : '';
    if (dateVal)  document.getElementById('addJobDate').value  = dateVal;
    if (shiftVal) document.getElementById('addJobShift').value = shiftVal;
    if (pressVal) document.getElementById('addJobPress').value = pressVal;

    // Update machine labels dynamically based on press (e.g. PRESS B -> B-1, B-2)
    const pressLetter = (pressVal || '').replace('PRESS ', '').trim();
    const labels = document.querySelectorAll('#addJobModal .machine-label');
    labels.forEach((lbl, idx) => {
        lbl.textContent = (pressLetter ? pressLetter : 'M') + '-' + (idx + 1);
    });

    const subtitle = (document.getElementById('addJobShift').value || '') + ' — ' + (document.getElementById('addJobPress').value || '') + ' — ' + (document.getElementById('addJobDate').value || '');
    document.getElementById('addJobSubtitle').textContent = subtitle;
    const modal = document.getElementById('addJobModal');
    if (modal) { modal.style.display = 'flex'; }
    setTimeout(() => { const n = document.getElementById('addJobNo'); if (n) n.focus(); }, 100);
}
function closeAddJobModal() {
    const modal = document.getElementById('addJobModal');
    if (modal) modal.style.display = 'none';
    
    // Clear autocomplete state
    const addJobNo = document.getElementById('addJobNo');
    if (addJobNo) addJobNo.value = '';
    const suggestDiv = document.getElementById('addJobNoSuggest');
    if (suggestDiv) suggestDiv.style.display = 'none';
    const previewDiv = document.getElementById('addJobMasterPreview');
    if (previewDiv) {
        previewDiv.style.display = 'none';
        previewDiv.innerHTML = '';
    }
    const masterVal = document.getElementById('addJobMasterVal');
    if (masterVal) masterVal.value = '';
    const typePltVal = document.getElementById('addJobTypePltVal');
    if (typePltVal) typePltVal.value = '';
    const qtyPltVal = document.getElementById('addJobQtyPltVal');
    if (qtyPltVal) qtyPltVal.value = '';
    
    // Reset standard form inputs
    const form = document.getElementById('addJobForm');
    if (form) {
        form.querySelector('input[name="plan"]').value = '';
        form.querySelector('input[name="ct_detik"]').value = '';
        form.querySelector('input[name="dct"]').value = '';
        form.querySelector('input[name="reg_active"]').value = '';
        form.querySelector('input[name="keterangan"]').value = '';
        form.querySelector('input[name="total_mesin"]').value = '4';
    }
}

// ── Delete Job ─────────────────────────────────────────────────────────────────────────
function deleteJob(id) {
    if (!confirm('Hapus job ini dari antrian? Waktu akan dikalkulasi ulang.')) return;
    fetch('{{ url("/schedule-stamping/delete-job") }}/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast('✅ ' + (data.message || 'Job berhasil dihapus.'), '#16a34a');
            setTimeout(() => window.location.reload(), 800);
        } else {
            showToast('❌ ' + (data.message || 'Gagal menghapus job.'), '#dc2626');
        }
    })
    .catch(() => showToast('❌ Gagal menghubungi server.', '#dc2626'));
}

// ── Job No Autocomplete from Master ──
document.addEventListener('DOMContentLoaded', function() {
    const addJobNo = document.getElementById('addJobNo');
    const suggestDiv = document.getElementById('addJobNoSuggest');
    const previewDiv = document.getElementById('addJobMasterPreview');

    const masterVal = document.getElementById('addJobMasterVal');
    const typePltVal = document.getElementById('addJobTypePltVal');
    const qtyPltVal = document.getElementById('addJobQtyPltVal');

    const form = document.getElementById('addJobForm');
    const ctInput = form ? form.querySelector('input[name="ct_detik"]') : null;
    const dctInput = form ? form.querySelector('input[name="dct"]') : null;
    const regActiveInput = form ? form.querySelector('input[name="reg_active"]') : null;
    const totalMesinInput = form ? form.querySelector('input[name="total_mesin"]') : null;

    let debounceTimeout = null;

    if (addJobNo && suggestDiv) {
        addJobNo.addEventListener('input', function() {
            clearTimeout(debounceTimeout);
            const q = this.value.trim();
            if (q.length < 2) {
                suggestDiv.style.display = 'none';
                return;
            }
            
            const shiftVal = document.getElementById('addJobShift') ? document.getElementById('addJobShift').value : '';
            const shiftParam = shiftVal.toLowerCase().includes('malam') ? 'malam' : (shiftVal.toLowerCase().includes('pagi') || shiftVal.toLowerCase().includes('siang') ? 'pagi' : '');
            
            debounceTimeout = setTimeout(() => {
                fetch(`/master-stamping/search-ajax?q=${encodeURIComponent(q)}&shift=${shiftParam}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.length === 0) {
                            suggestDiv.innerHTML = '<div style="padding:10px; color:#94a3b8; font-size:12px;">Tidak ditemukan data master</div>';
                        } else {
                            let html = '';
                            data.forEach(item => {
                                html += `
                                    <div class="suggest-item" style="padding:10px 12px; cursor:pointer; border-bottom:1px solid #f1f5f9; font-size:12.5px; transition:background 0.15s;"
                                         data-job_no="${item.job_no}"
                                         data-job_master="${item.job_master || ''}"
                                         data-type_pallet="${item.type_pallet || ''}"
                                         data-qty_pallet="${item.qty_pallet || 0}"
                                         data-ct_detik="${item.ct_detik || 0}"
                                         data-dct="${item.dct || 0}"
                                         data-reg_active="${item.reg_active || 0}"
                                         data-mach="${item.mach || ''}"
                                         data-part_name="${item.part_name || ''}">
                                        <strong style="color:#0f172a;">${item.job_no}</strong> 
                                        <span style="color:#64748b; font-size:11px;">(${item.job_master || ''}) - ${item.part_name || ''}</span>
                                    </div>
                                `;
                            });
                            suggestDiv.innerHTML = html;
                        }
                        suggestDiv.style.display = 'block';
                    });
            }, 200);
        });

        suggestDiv.addEventListener('click', function(e) {
            const item = e.target.closest('.suggest-item');
            if (!item) return;
            
            const jNo = item.getAttribute('data-job_no');
            const jMaster = item.getAttribute('data-job_master');
            const typePallet = item.getAttribute('data-type_pallet');
            const qtyPallet = item.getAttribute('data-qty_pallet');
            const ct = item.getAttribute('data-ct_detik');
            const dct = item.getAttribute('data-dct');
            const regActive = item.getAttribute('data-reg_active');
            const partName = item.getAttribute('data-part_name');
            
            addJobNo.value = jNo;
            if (masterVal) masterVal.value = jMaster;
            if (typePltVal) typePltVal.value = typePallet;
            if (qtyPltVal) qtyPltVal.value = qtyPallet;
            
            if (ctInput) ctInput.value = ct;
            if (dctInput) dctInput.value = dct;
            if (regActiveInput) regActiveInput.value = regActive;
            
            if (previewDiv) {
                previewDiv.style.display = 'block';
                previewDiv.innerHTML = `
                    <div style="display:flex; align-items:center; gap:6px; font-weight:700; color:#15803d; margin-bottom:4px;">
                        <span class="material-icons" style="font-size:14px; color:#16a34a;">check_circle</span> Data Master Ditemukan
                    </div>
                    <strong>Master:</strong> ${jMaster}<br>
                    <strong>Part Name:</strong> ${partName}<br>
                    <strong>Type Pallet:</strong> ${typePallet || '-'} (${qtyPallet} pcs/plt)
                `;
            }
            
            suggestDiv.style.display = 'none';
        });

        // Close suggestions on click outside
        document.addEventListener('click', function(e) {
            if (!addJobNo.contains(e.target) && !suggestDiv.contains(e.target)) {
                suggestDiv.style.display = 'none';
            }
        });
    }
});

function openExportModal() {
    document.getElementById('exportModal').style.display = 'flex';
    const selectedShift = "{{ $selectedShift }}";
    if (selectedShift.toUpperCase().includes('MALAM')) {
        document.querySelector('input[name="export_sect_head"][value="Alvyn"]').checked = true;
        selectExportOpt('Alvyn');
    } else {
        document.querySelector('input[name="export_sect_head"][value="Alberta P. S."]').checked = true;
        selectExportOpt('Alberta P. S.');
    }
}

function closeExportModal() {
    document.getElementById('exportModal').style.display = 'none';
}

function selectExportOpt(val) {
    const labelAlberta = document.getElementById('optLabelAlberta');
    const labelAlvyn = document.getElementById('optLabelAlvyn');
    
    if (val === 'Alberta P. S.') {
        labelAlberta.style.borderColor = '#16a34a';
        labelAlberta.style.background = '#f0fdf4';
        labelAlvyn.style.borderColor = '#e2e8f0';
        labelAlvyn.style.background = 'white';
    } else {
        labelAlvyn.style.borderColor = '#16a34a';
        labelAlvyn.style.background = '#f0fdf4';
        labelAlberta.style.borderColor = '#e2e8f0';
        labelAlberta.style.background = 'white';
    }
}

function confirmExport() {
    const selectedOpt = document.querySelector('input[name="export_sect_head"]:checked').value;
    const baseUrl = "{{ route('schedule_stamping.export') }}";
    
    const urlParams = new URLSearchParams();
    urlParams.set('date', "{{ $selectedDate }}");
    @if($selectedPress)
        urlParams.set('press', "{{ $selectedPress }}");
    @endif
    @if($search)
        urlParams.set('search', "{{ $search }}");
    @endif
    urlParams.set('sect_head_ppc', selectedOpt);
    
    window.location.href = baseUrl + '?' + urlParams.toString();
    closeExportModal();
}
</script>
@endpush

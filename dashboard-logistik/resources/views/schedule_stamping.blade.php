@extends('layouts.app')

@push('styles')
<style>
    .hero { background: var(--navy-dark); padding: 22px 32px; display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
    .hero-title-block h2 { font-size: 26px; font-weight: 900; color: white; display: flex; align-items: center; gap: 12px; }
    .hero-meta { color: rgba(255,255,255,0.6); font-size: 12px; margin-top: 5px; }

    .stat-box { border-radius: 8px; padding: 8px 18px; text-align: center; min-width: 90px; box-shadow: 0 4px 8px rgba(0,0,0,.2); }
    .stat-box .s-label { font-size: 9px; font-weight: 800; margin-bottom: 3px; text-transform: uppercase; letter-spacing: .8px; }
    .stat-box .s-val { font-size: 22px; font-weight: 900; line-height: 1; }

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
        .card-header { flex-direction: column !important; align-items: stretch !important; padding: 15px !important; }
        .card-header form { flex-direction: column !important; align-items: stretch !important; gap: 12px !important; }
        .dd-wrap, .dd-btn, .search-box, .action-btn { width: 100% !important; }
        .search-box input { width: 100%; }
        
        .card-header div[style*="display:flex"]:last-child { 
            flex-direction: column; 
            width: 100%; 
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
        
        .meta-bar { padding: 10px 15px; gap: 10px; }
    }

    /* Dropdowns */
    .dd-wrap { position: relative; z-index: 200; }
    .dd-btn { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 7px 13px; display: flex; align-items: center; gap: 7px; cursor: pointer; font-size: 12px; font-weight: 700; color: #333; white-space: nowrap; min-width: 150px; user-select: none; }
    .dd-btn .material-icons { font-size: 16px; color: var(--navy-dark); }
    .dd-btn .arr { margin-left: auto; color: #aaa; transition: transform .2s; font-size: 18px; }
    .dd-wrap.open .arr { transform: rotate(180deg); }
    .dd-menu { display: none; position: absolute; top: calc(100% + 2px); left: 0; min-width: 190px; background: white; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.1); max-height: 300px; overflow-y: auto; z-index: 201; }
    .dd-wrap.open .dd-menu { display: block; }
    .dd-menu a { display: flex; align-items: center; gap: 8px; padding: 9px 14px; text-decoration: none; color: #444; font-size: 12px; font-weight: 500; border-bottom: 1px solid #f5f5f5; transition: background .1s; }
    .dd-menu a:last-child { border-bottom: none; }
    .dd-menu a:hover { background: #f0f4ff; color: var(--navy-dark); }
    .dd-menu a.sel { background: var(--navy-dark); color: white; font-weight: 700; }

    .search-box { display: flex; align-items: center; gap: 8px; background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; padding: 6px 12px; }
    .search-box input { border: none; background: transparent; outline: none; font-size: 12px; font-family: inherit; width: 180px; }
    .action-btn { height: 36px; padding: 0 14px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; cursor: pointer; border: none; transition: all 0.2s; white-space: nowrap; text-decoration: none; }
    .btn-navy { background: var(--navy-mid); color: white; }
    .btn-navy:hover { background: var(--navy-light); }

    /* Meta bar */
    .meta-bar { padding: 8px 18px; background: #f8f9fb; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 20px; font-size: 11px; color: #666; flex-wrap: wrap; }
    .meta-bar strong { color: #333; }
    .meta-chip { background: white; border: 1px solid #e0e0e0; border-radius: 20px; padding: 3px 10px; font-size: 10px; font-weight: 700; color: #555; }
    .meta-chip.revisi { background: #fef3c7; border-color: #f59e0b; color: #92400e; }

    /* Table */
    .top-scrollbar { overflow-x: auto; overflow-y: hidden; height: 14px; margin-bottom: 2px; }
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
                @if($selectedDate) — {{ $selectedDate }} @endif
            </div>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <div class="stat-box" style="background:#1e3a5f; color:white;">
                <div class="s-label">Total Job</div>
                <div class="s-val">{{ $totalJobs }}</div>
            </div>
            <div class="stat-box" style="background:#1d4ed8; color:white;">
                <div class="s-label">Total Plan</div>
                <div class="s-val">{{ number_format($totalPlan, 0, ',', '.') }}</div>
            </div>
            <div class="stat-box" style="background:#0369a1; color:white;">
                <div class="s-label">Total Pcs</div>
                <div class="s-val">{{ number_format($totalPcs, 0, ',', '.') }}</div>
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
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">

                {{-- Filter Form --}}
                <form action="{{ route('schedule_stamping.index') }}" method="GET" id="stampingForm" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; flex:1;">

                    {{-- Date Dropdown --}}
                    <input type="hidden" name="date" id="dateVal" value="{{ $selectedDate }}">
                    <div class="dd-wrap" id="ddDate">
                        <div class="dd-btn" onclick="toggleDD('ddDate')">
                            <span class="material-icons">calendar_today</span>
                            <span id="dateLbl">{{ $selectedDate ?: 'Pilih Tanggal' }}</span>
                            <span class="material-icons arr">expand_more</span>
                        </div>
                        <div class="dd-menu">
                            <div style="padding: 10px; border-bottom: 1px solid #eee;">
                                <div style="font-size: 10px; font-weight: 700; color: #999; margin-bottom: 5px; text-transform: uppercase;">Pilih dari Kalender:</div>
                                <input type="date" id="calendarInput" onchange="submitCalendarDate(this.value)" style="width: 100%; border: 1px solid #ddd; border-radius: 4px; padding: 5px; font-size: 12px; font-family: inherit; cursor: pointer;">
                            </div>
                            @forelse($allDates as $d)
                            <a href="{{ request()->fullUrlWithQuery(['date' => $d, 'page' => null]) }}"
                               class="{{ $selectedDate === $d ? 'sel' : '' }}">
                                <span class="material-icons" style="font-size:14px;">today</span> {{ $d }}
                            </a>
                            @empty
                            <div style="padding:12px 14px; color:#999; font-size:12px;">Belum ada data</div>
                            @endforelse
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
                        <button type="submit" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;padding:0;">
                            <span class="material-icons" style="color:#999;font-size:16px;">search</span>
                        </button>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari Job Master, Job No...">
                    </div>

                </form>

                {{-- Actions --}}
                <div style="display:flex; align-items:center; gap:8px;">

                    <a href="{{ route('schedule_stamping.export', request()->all()) }}" class="action-btn" style="background:#16a34a; color:white;">
                        <span class="material-icons">download</span> Export Excel
                    </a>

                    {{-- Upload Form --}}
                    <form action="{{ route('schedule_stamping.upload') }}" method="POST" enctype="multipart/form-data" id="stampingUploadForm">
                        @csrf
                        <input type="file" name="excel_file" id="stampingFileInput" style="display:none"
                               accept=".xlsx,.xls,.xlsm" onchange="document.getElementById('stampingUploadForm').submit()">
                        <label for="stampingFileInput" class="action-btn btn-navy" style="cursor:pointer;">
                            <span class="material-icons">upload_file</span> Upload Excel
                        </label>
                    </form>
                </div>

            </div>

            @if(!$hasData)
            {{-- Empty state --}}
            <div class="empty-state">
                <span class="material-icons">upload_file</span>
                <h3>Belum ada data Schedule Stamping</h3>
                <p style="color:#aaa;font-size:13px;">Upload file Excel Schedule Stamping<br>(contoh: 04__Schedule_Stamping_05_Mei_2026.xlsx)</p>
            </div>

            @else

            {{-- Meta bar --}}
            @if($metaInfo)
            <div class="meta-bar">
                <span><strong>Press:</strong> {{ $metaInfo->press_name }}</span>
                <span><strong>Hari:</strong> {{ $metaInfo->hari }}</span>
                <span><strong>Tanggal:</strong> {{ $metaInfo->tgl }}</span>
                <span><strong>Jam:</strong> {{ $metaInfo->jam }}</span>
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
                            <th style="width: 30px;">#</th>
                            <th style="width: 120px;">JOB MASTER</th>
                            <th class="center" style="width: 35px;">TYPE</th>
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
                            <th class="center" style="width: 35px;">A-1</th>
                            <th class="center" style="width: 35px;">A-2</th>
                            <th class="center" style="width: 35px;">A-3</th>
                            <th class="center" style="width: 35px;">A-4</th>
                            <th class="center" style="width: 55px;">DT (MENIT)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        @if($item->row_type === 'break')
                        <tr class="row-break">
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
                        <tr>
                            <td style="color:#999; font-size:10px;">{{ $item->row_no }}</td>
                            <td>
                                <input type="text" class="inline-input" style="text-align:left; font-weight:800;" 
                                    data-id="{{ $item->id }}" data-field="job_master" value="{{ $item->job_master }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                @if($item->type_plt)
                                <span class="badge-type {{ $typeClass }}">{{ $item->type_plt }}</span>
                                @endif
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="qty_plt" value="{{ $item->qty_plt }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="keb_mtl" value="{{ $item->keb_mtl }}" onchange="saveInline(this)">
                            </td>
                            <td class="center" style="color:#666;">{{ $item->total_plt ? number_format($item->total_plt, 1) : '-' }}</td>
                            <td>
                                <input type="text" class="inline-input" style="text-align:left;" 
                                    data-id="{{ $item->id }}" data-field="job_no" value="{{ $item->job_no }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" style="font-weight:700;" 
                                    data-id="{{ $item->id }}" data-field="plan" value="{{ $item->plan }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" style="color:#16a34a; font-weight:700;" 
                                    data-id="{{ $item->id }}" data-field="ok" value="{{ $item->ok }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" style="color:#d97706;" 
                                    data-id="{{ $item->id }}" data-field="repair" value="{{ $item->repair }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" style="color:#dc2626;" 
                                    data-id="{{ $item->id }}" data-field="reject" value="{{ $item->reject }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="total_mesin" value="{{ $item->total_mesin }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" step="0.1"
                                    data-id="{{ $item->id }}" data-field="ct_detik" value="{{ $item->ct_detik }}" onchange="saveInline(this)">
                            </td>
                            <td class="center" style="color:#666;">{{ $item->process_time !== null ? number_format($item->process_time, 1) : '-' }}</td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="reg_active" value="{{ $item->reg_active }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="dct" value="{{ $item->dct }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="mct" value="{{ $item->mct }}" onchange="saveInline(this)">
                            </td>
                            <td class="center" style="color:#666;">{{ $item->plan_dct ?: '-' }}</td>
                            <td class="center" style="font-weight:700; background: #fef3c7;">{{ $item->tpt !== null ? number_format($item->tpt, 0) : '-' }}</td>
                            <td class="center">{{ $item->gsph_item !== null ? number_format($item->gsph_item, 0) : '-' }}</td>
                            <td class="center">
                                <input type="text" class="inline-input" style="font-family:monospace; font-size:10px;" 
                                    data-id="{{ $item->id }}" data-field="start_time" value="{{ $item->start_time }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <span class="time-chip">{{ $item->finish_time ?: '-' }}</span>
                            </td>
                            <td class="center">
                                <input type="text" class="inline-input" style="font-family:monospace; font-size:10px;" 
                                    data-id="{{ $item->id }}" data-field="act_start" value="{{ $item->act_start }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="text" class="inline-input" style="font-family:monospace; font-size:10px;" 
                                    data-id="{{ $item->id }}" data-field="act_finish" value="{{ $item->act_finish }}" onchange="saveInline(this)">
                            </td>
                            <td>
                                <input type="text" class="inline-input" style="text-align:left;" 
                                    data-id="{{ $item->id }}" data-field="keterangan" value="{{ $item->keterangan }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input a-box {{ $item->a1 ? 'filled' : '' }}" 
                                    data-id="{{ $item->id }}" data-field="a1" value="{{ $item->a1 }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input a-box {{ $item->a2 ? 'filled' : '' }}" 
                                    data-id="{{ $item->id }}" data-field="a2" value="{{ $item->a2 }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input a-box {{ $item->a3 ? 'filled' : '' }}" 
                                    data-id="{{ $item->id }}" data-field="a3" value="{{ $item->a3 }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input a-box {{ $item->a4 ? 'filled' : '' }}" 
                                    data-id="{{ $item->id }}" data-field="a4" value="{{ $item->a4 }}" onchange="saveInline(this)">
                            </td>
                            <td class="center">
                                <input type="number" class="inline-input" 
                                    data-id="{{ $item->id }}" data-field="dt_menit" value="{{ $item->dt_menit }}" onchange="saveInline(this)">
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

            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Dropdown toggle
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
    
    // Redirect with the new date string
    const url = new URL(window.location.href);
    url.searchParams.set('date', sheetName);
    url.searchParams.set('page', '');
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar input value if possible
    const selected = "{{ $selectedDate }}";
    if(selected) {
        const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
        const parts = selected.split(' ');
        if(parts.length >= 3) {
            const d = parseInt(parts[0]);
            const mStr = parts[1].toUpperCase();
            const y = parts[2];
            const mIndex = months.indexOf(mStr);
            if(mIndex >= 0) {
                const m = (mIndex + 1).toString().padStart(2, '0');
                const day = d.toString().padStart(2, '0');
                const calInput = document.getElementById('calendarInput');
                if(calInput) calInput.value = `${y}-${m}-${day}`;
            }
        }
    }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.dd-wrap')) {
        document.querySelectorAll('.dd-wrap.open').forEach(el => el.classList.remove('open'));
    }
});

// Inline Save logic
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
            
            // For time-cascading and formula updates, we reload the page 
            // after a short delay to see all calculated values.
            // In a more advanced version, we could update the DOM elements manually.
            setTimeout(() => {
                window.location.reload();
            }, 800);
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

// Sync top scrollbar
document.addEventListener('DOMContentLoaded', function() {
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
</script>
@endpush

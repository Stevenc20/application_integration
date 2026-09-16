@extends('layouts.supervisor')

@section('title', 'Dashboard Hambatan Jalur')

@section('content')
<style>
    .hj-hero-gradient {
        background: linear-gradient(135deg, #3b0a0a 0%, #5c1010 50%, #7f1d1d 100%);
        position: relative;
        overflow: hidden;
    }
    .hj-hero-gradient::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -30%;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .hj-hero-gradient::after {
        content: '';
        position: absolute;
        bottom: -40%;
        left: -20%;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(220,80,80,0.08) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .hj-stat-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .hj-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .hj-item-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
        position: relative;
        overflow: hidden;
    }
    .hj-item-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        border-radius: 16px 0 0 16px;
        transition: width 0.3s ease;
    }
    .hj-item-card.status-open::before { background: linear-gradient(180deg, #ef4444, #dc2626); }
    .hj-item-card.status-pic_signed::before { background: linear-gradient(180deg, #f59e0b, #d97706); }
    .hj-item-card.status-signed::before { background: linear-gradient(180deg, #22c55e, #16a34a); }
    .hj-item-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 40px rgba(0,0,0,0.08);
        border-color: #cbd5e1;
    }
    .hj-item-card:hover::before { width: 6px; }
    .hj-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }
    .hj-filter-tab {
        padding: 8px 18px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.25s ease;
        border: 1.5px solid transparent;
        color: #64748b;
        background: white;
    }
    .hj-filter-tab:hover { background: #f1f5f9; color: #334155; }
    .hj-filter-tab.active {
        background: #1e293b;
        color: white;
        border-color: #1e293b;
        box-shadow: 0 4px 12px rgba(30,41,59,0.25);
    }
    .hj-pulse-dot {
        width: 8px; height: 8px;
        border-radius: 50%;
        display: inline-block;
        position: relative;
    }
    .hj-pulse-dot.red { background: #ef4444; }
    .hj-pulse-dot.green { background: #22c55e; }
    .hj-pulse-dot.amber { background: #f59e0b; }
    .hj-pulse-dot.red::after {
        content: '';
        position: absolute;
        inset: -3px;
        border-radius: 50%;
        background: rgba(239,68,68,0.3);
        animation: hjPulse 2s ease-in-out infinite;
    }
    @keyframes hjPulse {
        0%,100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.5); opacity: 0; }
    }
    @keyframes hjSlideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .hj-animate-in {
        animation: hjSlideUp 0.5s cubic-bezier(0.4,0,0.2,1) both;
    }
    .hj-info-grid span.label { color: #94a3b8; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
    .hj-info-grid span.value { color: #1e293b; font-size: 13px; font-weight: 700; }
    .hj-detail-block {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 10px;
        padding: 12px 14px;
        border-left: 3px solid #cbd5e1;
    }
    .hj-empty-state {
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
        border: 2px dashed #bbf7d0;
        border-radius: 20px;
        padding: 60px 40px;
        text-align: center;
    }

    @media (max-width: 639px) {
        .hj-hero-gradient { border-radius: 12px !important; padding: 14px !important; }
        .hj-hero-gradient .grid.gap-3 { gap: 6px; }
        .hj-stat-card { padding: 10px; }
        .hj-stat-card p.text-2xl { font-size: 18px; }
        .hj-stat-card .text-\[10px\] { font-size: 8px; }
        .hj-stat-card .w-7 { width: 20px; height: 20px; }
        .hj-item-card .p-5 { padding: 12px; }
        .hj-item-card .p-6 { padding: 12px; }
        .hj-item-card .flex-col.md\:flex-row { flex-direction: column; }
        .hj-item-card .flex-shrink-0 { width: 100%; }
        .hj-item-card .flex-shrink-0 a { width: 100%; justify-content: center; }
        .hj-info-grid { gap: 2px !important; }
        .hj-info-grid span.value { font-size: 11px; }
        .hj-info-grid span.label { font-size: 9px; }
        .hj-detail-block { padding: 8px 10px; }
        .hj-detail-block p.text-xs { font-size: 10px; }
        .hj-filter-tab { padding: 6px 10px; font-size: 11px; }
        .hj-empty-state { padding: 30px 16px; }
        .hj-empty-state .w-20 { width: 48px; height: 48px; }
        .hj-empty-state h3 { font-size: 14px; }
        .hj-empty-state p { font-size: 11px; }
        .flex-col.md\:flex-row.md\:items-start { flex-direction: column; }
        .flex-shrink-0.flex-col.items-end { width: 100%; align-items: stretch; }
        .hj-item-card .hj-badge { font-size: 8px; padding: 2px 6px; }
        .hj-item-card .flex.items-center.gap-2.flex-wrap.mb-3 { gap: 4px; }
        .hj-item-card .ml-auto { margin-left: 0; }
    }
</style>

<div class="space-y-6">
    {{-- HERO SECTION --}}
    <div class="hj-hero-gradient rounded-2xl p-6 md:p-8 relative z-10">
        <div class="relative z-10">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center shadow-lg shadow-red-900/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-xl md:text-2xl font-black text-white tracking-tight">Dashboard Hambatan Jalur</h1>
                            <p class="text-slate-400 text-xs font-medium mt-0.5">
                                Monitoring & penanganan hambatan produksi
                                @if($jenis) <span class="text-red-400 font-bold">• {{ $jenis }}</span> @endif
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span class="font-semibold">{{ now()->translatedFormat('l, d F Y') }}</span>
                </div>
            </div>

            {{-- STATS GRID --}}
            @php
                $totalOpen = $counts['open'] ?? $items->where('status','open')->count();
                $totalPicSigned = $counts['pic_signed'] ?? $items->where('status','pic_signed')->count();
                $totalSigned = $counts['signed'] ?? $items->where('status','signed')->count();
                $totalAll = $items->total();
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div class="hj-stat-card">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Total</span>
                    </div>
                    <p class="text-2xl font-black text-slate-900">{{ $totalAll }}</p>
                    <p class="text-[10px] text-slate-400 mt-1">Laporan hambatan</p>
                </div>
                <div class="hj-stat-card">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-7 h-7 rounded-lg bg-red-50 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                        <span class="text-[10px] font-bold text-red-500 uppercase tracking-widest">Open</span>
                    </div>
                    <p class="text-2xl font-black text-red-600">{{ $totalOpen }}</p>
                    <p class="text-[10px] text-slate-400 mt-1">Menunggu tanda tangan</p>
                </div>
                <div class="hj-stat-card">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-7 h-7 rounded-lg bg-amber-50 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                        <span class="text-[10px] font-bold text-amber-500 uppercase tracking-widest">Menunggu</span>
                    </div>
                    <p class="text-2xl font-black text-amber-600">{{ $totalPicSigned }}</p>
                    <p class="text-[10px] text-slate-400 mt-1">Menunggu leader</p>
                </div>
                <div class="hj-stat-card">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                        <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest">Signed</span>
                    </div>
                    <p class="text-2xl font-black text-emerald-600">{{ $totalSigned }}</p>
                    <p class="text-[10px] text-slate-400 mt-1">Sudah ditangani</p>
                </div>
                <div class="hj-stat-card">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg></div>
                        <span class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">Rasio</span>
                    </div>
                    <p class="text-2xl font-black text-blue-600">{{ $totalAll > 0 ? round(($totalSigned / $totalAll) * 100) : 0 }}%</p>
                    <p class="text-[10px] text-slate-400 mt-1">Tingkat penyelesaian</p>
                </div>
            </div>
        </div>
    </div>

    {{-- PERSISTENT NOTIFICATION --}}
    @if($totalOpen > 0 || $totalPicSigned > 0)
    <div class="flex items-start gap-4 px-5 py-4 rounded-xl bg-red-50 border-2 border-red-200 hj-animate-in shadow-sm">
        <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
            <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-black text-red-700 uppercase tracking-wider">⚠️ {{ $totalOpen + $totalPicSigned }} Laporan Belum Selesai</p>
            <p class="text-xs text-red-500 font-medium mt-1">
                @if($totalOpen > 0){{ $totalOpen }} laporan menunggu tanda tangan PIC. @endif
                @if($totalPicSigned > 0){{ $totalPicSigned }} laporan menunggu tanda tangan leader. @endif
                Segera selesaikan agar proses produksi dapat berjalan lancar.
            </p>
        </div>
    </div>
    @endif

    {{-- ALERTS --}}
    @if(session('success'))
        <div class="flex items-center gap-3 px-5 py-4 rounded-xl bg-emerald-50 border border-emerald-200 hj-animate-in">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div>
            <p class="text-sm font-semibold text-emerald-700">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="flex items-center gap-3 px-5 py-4 rounded-xl bg-red-50 border border-red-200 hj-animate-in">
            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></div>
            <p class="text-sm font-semibold text-red-700">{{ session('error') }}</p>
        </div>
    @endif
    @if(session('warning'))
        <div class="flex items-center gap-3 px-5 py-4 rounded-xl bg-amber-50 border border-amber-200 hj-animate-in">
            <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg></div>
            <p class="text-sm font-semibold text-amber-700">{{ session('warning') }}</p>
        </div>
    @endif

    {{-- FILTER TABS --}}
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest mr-1">Filter:</span>
        <a href="{{ route('hambatan-jalur.index', request()->except('status')) }}" class="hj-filter-tab {{ $currentTab === 'all' ? 'active' : '' }}">Semua <span class="text-[10px] opacity-60">({{ $totalAll }})</span></a>
        <a href="{{ route('hambatan-jalur.index', array_merge(request()->except('status'), ['status' => 'open'])) }}" class="hj-filter-tab {{ $currentTab === 'open' ? 'active' : '' }}">
            <span class="hj-pulse-dot red mr-1"></span> Open <span class="text-[10px] opacity-60">({{ $counts['open'] }})</span>
        </a>
        <a href="{{ route('hambatan-jalur.index', array_merge(request()->except('status'), ['status' => 'pic_signed'])) }}" class="hj-filter-tab {{ $currentTab === 'pic_signed' ? 'active' : '' }}">
            <span class="hj-pulse-dot amber mr-1"></span> Menunggu Leader <span class="text-[10px] opacity-60">({{ $counts['pic_signed'] }})</span>
        </a>
        <a href="{{ route('hambatan-jalur.index', array_merge(request()->except('status'), ['status' => 'signed'])) }}" class="hj-filter-tab {{ $currentTab === 'signed' ? 'active' : '' }}">
            <span class="hj-pulse-dot green mr-1"></span> Signed <span class="text-[10px] opacity-60">({{ $counts['signed'] }})</span>
        </a>
    </div>

    {{-- ITEMS LIST --}}
    @forelse($items as $idx => $item)
        <div class="hj-item-card status-{{ $item->status }} hj-animate-in" style="animation-delay: {{ $idx * 0.06 }}s" data-status="{{ $item->status }}">
            <div class="p-5 md:p-6">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        {{-- TOP BADGES --}}
                        <div class="flex items-center gap-2 flex-wrap mb-3">
                            @php
                                $typeColors = [
                                    'DT' => 'bg-orange-100 text-orange-700 border-orange-200',
                                    'MT' => 'bg-red-100 text-red-700 border-red-200',
                                    'MST' => 'bg-blue-100 text-blue-700 border-blue-200',
                                    'LOGT' => 'bg-amber-100 text-amber-700 border-amber-200',
                                    'Prot' => 'bg-purple-100 text-purple-700 border-purple-200',
                                    'SMT' => 'bg-cyan-100 text-cyan-700 border-cyan-200',
                                    'QT' => 'bg-pink-100 text-pink-700 border-pink-200',
                                ];
                                $badgeClass = $typeColors[$item->jenis_hambatan] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            @endphp
                            <span class="hj-badge border {{ $badgeClass }}">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                {{ $item->jenis_hambatan }}
                            </span>
                            <span class="hj-badge {{ $item->status === 'open' ? 'bg-red-50 text-red-600 border border-red-200' : ($item->status === 'pic_signed' ? 'bg-amber-50 text-amber-600 border border-amber-200' : 'bg-emerald-50 text-emerald-600 border border-emerald-200') }}">
                                <span class="hj-pulse-dot {{ $item->status === 'open' ? 'red' : ($item->status === 'pic_signed' ? 'amber' : 'green') }}"></span>
                                {{ $item->status === 'pic_signed' ? 'MENUNGGU LEADER' : strtoupper($item->status) }}
                            </span>
                            @if($item->sub_jenis)
                                <span class="hj-badge bg-slate-100 text-slate-600 border border-slate-200">{{ $item->sub_jenis }}</span>
                            @endif
                            <span class="text-[11px] text-slate-400 font-medium ml-auto">{{ $item->created_at->diffForHumans() }}</span>
                        </div>

                        {{-- INFO GRID --}}
                        <div class="hj-info-grid grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-2.5">
                            <div class="flex flex-col gap-0.5">
                                <span class="label">Line</span>
                                <span class="value">{{ $item->line_name ?? '-' }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="label">Mesin</span>
                                <span class="value">{{ $item->mesin ?? '-' }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="label">Job No</span>
                                <span class="value font-mono">{{ $item->job_no ?? '-' }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="label">Nama Part</span>
                                <span class="value">{{ $item->nama_part ?? '-' }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="label">PIC</span>
                                <span class="value">{{ $item->signer?->name ?? $item->pic_hambatan ?? '-' }}</span>
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span class="label">Waktu</span>
                                <span class="value">{{ $item->waktu ? \Carbon\Carbon::parse($item->waktu)->format('d M Y H:i') : '-' }}</span>
                            </div>
                        </div>

                        {{-- DETAIL BLOCKS --}}
                        @if($item->problem)
                        <div class="hj-detail-block mt-4" style="border-left-color:#ef4444">
                            <span class="text-[10px] font-black text-red-500 uppercase tracking-widest">Problem</span>
                            <p class="text-xs text-slate-700 mt-1 leading-relaxed">{{ Str::limit($item->problem, 150) }}</p>
                        </div>
                        @endif
                        @if($item->penyebab)
                        <div class="hj-detail-block mt-2" style="border-left-color:#f59e0b">
                            <span class="text-[10px] font-black text-amber-600 uppercase tracking-widest">Penyebab</span>
                            <p class="text-xs text-slate-700 mt-1 leading-relaxed">{{ Str::limit($item->penyebab, 150) }}</p>
                        </div>
                        @endif
                        @if($item->penanggulangan)
                        <div class="hj-detail-block mt-2" style="border-left-color:#22c55e">
                            <span class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Penanggulangan</span>
                            <p class="text-xs text-slate-700 mt-1 leading-relaxed">{{ Str::limit($item->penanggulangan, 150) }}</p>
                        </div>
                        @endif
                    </div>

                    {{-- ACTION BUTTON --}}
                    <div class="flex-shrink-0 flex flex-col items-end gap-2">
                        @php
                            $isOpen = $item->status === 'open';
                            $isPicSigned = $item->status === 'pic_signed';
                            $isComplete = $item->status === 'signed';
                            $needsAction = $isOpen || $isPicSigned;
                        @endphp
                        <a href="{{ route('hambatan-jalur.show', $item->id) }}"
                           class="group inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all duration-300
                                  {{ $needsAction
                                     ? 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg shadow-red-500/25 hover:shadow-red-500/40 hover:scale-105'
                                     : 'bg-slate-100 text-slate-500 hover:bg-slate-200' }}">
                            @if($isOpen)
                                <svg class="w-3.5 h-3.5 group-hover:animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                TANDA TANGANI
                            @elseif($isPicSigned)
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                LEADER SIGN
                            @else
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                LIHAT DETAIL
                            @endif
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="hj-empty-state hj-animate-in">
            <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-black text-emerald-800 mb-1">Tidak Ada Hambatan</h3>
            <p class="text-emerald-600 text-sm font-medium">Semua hambatan{{ $jenis ? ' (' . $jenis . ')' : '' }} sudah ditangani dengan baik.</p>
        </div>
    @endforelse

    {{-- PAGINATION --}}
    @if($items->hasPages())
    <div class="flex justify-center pt-2">
        <div class="bg-white rounded-xl border border-slate-200 px-4 py-3 shadow-sm">
            {{ $items->links() }}
        </div>
    </div>
    @endif
</div>


@endsection

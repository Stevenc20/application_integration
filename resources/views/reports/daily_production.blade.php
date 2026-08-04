@extends('layouts.supervisor')

@section('title', 'Laporan Kerja Harian (LKH)')

@section('head')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap"></noscript>
<link rel="stylesheet" href="{{ asset('css/lkh-table.css') }}">
<style>
    main { overflow-x: auto !important; }
    .page-wrapper, .lkh-ppc-table { font-family: 'Inter', system-ui, sans-serif; }
    .no-print-filter { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    @media screen {
        .overflow-x-auto { max-width: 100%; overflow-x: auto; scrollbar-width: thin; scrollbar-color: rgba(148,163,184,0.5) rgba(241,245,249,0.3); -webkit-overflow-scrolling: touch; }
        .overflow-x-auto::-webkit-scrollbar { height: 8px; width: 8px; }
        .overflow-x-auto::-webkit-scrollbar-track { background: rgba(241,245,249,0.3); border-radius: 10px; }
        .overflow-x-auto::-webkit-scrollbar-thumb { background-color: rgba(148,163,184,0.5); border-radius: 10px; border: 2px solid transparent; background-clip: padding-box; }
        .overflow-x-auto::-webkit-scrollbar-thumb:hover { background-color: rgba(100,116,139,0.8); }
        body.signature-modal-open .page-wrapper { pointer-events: none; filter: blur(1.5px); user-select: none; transition: filter 0.2s ease-in-out; }
    }
    @media print {
        .no-print { display: none !important; }
        body { background-color: white !important; color: black !important; padding: 0 !important; }
        .print-card { border: none !important; box-shadow: none !important; background: transparent !important; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        .overflow-x-auto { overflow: visible !important; }
    }
    .section-table { min-width: 1800px; width: 100%; border-collapse: collapse; font-size: 11px; font-family: 'Inter', system-ui, sans-serif; }
    .section-table th { background-color: #991B1B !important; color: #fff !important; font-weight: 700; text-transform: uppercase; padding: 6px 8px; border: 1px solid #7a1414; white-space: nowrap; }
    .section-table td { padding: 5px 8px; border: 1px solid #E5CACA; vertical-align: middle; white-space: nowrap; }
    .section-table tbody tr:hover { background-color: #FEE2E2 !important; }
    .section-table tbody tr:nth-child(even) { background-color: #FFF7F7; }
    .section-table .break-row { background-color: #FFFBEB !important; }
    .section-table .break-row td { border-color: #FDE68A; }
    .time-box { display: inline-flex; align-items: center; gap: 6px; background: linear-gradient(135deg, #FEF3C7, #FDE68A); border: 1.5px solid #F59E0B; border-radius: 8px; padding: 2px 10px; box-shadow: 0 1px 4px rgba(245,158,11,0.15); }
    .section-table tfoot td { background-color: #FFE4E6 !important; font-weight: 800; border-top: 2px solid #991B1B; }
    .section-table thead .group-border { border-right: 2px solid #7a1414 !important; }
    .summ-table { min-width: 800px; width: 100%; border-collapse: collapse; font-size: 12px; }
    .summ-table th { background-color: #991B1B !important; color: #fff; font-weight: 800; padding: 10px 14px; border: 1px solid #7a1414; text-transform: uppercase; font-size: 11px; }
    .summ-table td { padding: 8px 14px; border: 1px solid #D1D5DB; }
    .summ-table tbody tr:hover { background-color: #FEE2E2 !important; }
    .summ-table tbody tr:nth-child(even) { background-color: #F9FAFB; }
    .val-plan { color: #1F2937; font-weight: 700; }
    .val-actual { color: #991B1B; font-weight: 800; }
    .val-pct { font-weight: 700; }
    .val-pct.good { color: #059669; }
    .val-pct.warn { color: #D97706; }
    .val-pct.bad { color: #DC2626; }
    .badge-ok { display: inline-flex; background: #D1FAE5; color: #065F46; padding: 1px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; border: 1px solid #A7F3D0; }
    .badge-gap { display: inline-flex; background: #FEF3C7; color: #92400E; padding: 1px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; border: 1px solid #FDE68A; }
    .badge-alert { display: inline-flex; background: #FEE2E2; color: #991B1B; padding: 1px 10px; border-radius: 999px; font-size: 10px; font-weight: 800; border: 1px solid #FECACA; }
    .section-header { display: flex; align-items: center; gap: 12px; padding: 12px 0 8px 0; }
    .section-header .bar { width: 4px; height: 24px; border-radius: 2px; }
    .section-header h2 { font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; color: #1F2937; }
    .section-header .sub { font-size: 10px; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: 0.5px; }
    .cell-time { font-family: 'Courier New', monospace; font-weight: 700; font-size: 11px; }
    .cell-qty { font-variant-numeric: tabular-nums; text-align: center; }
    .lkh-editable-cell { position: relative; cursor: pointer; }
    .lkh-editable-cell .lkh-edit-icon { display: none; position: absolute; top: 1px; right: 2px; font-size: 9px; color: #DC2626; background: #FEE2E2; border-radius: 4px; padding: 0 3px; line-height: 1.3; z-index: 2; }
    .lkh-editable-cell:hover .lkh-edit-icon { display: block; }
    body.lkh-edit-mode .lkh-editable-cell { outline: 1px dashed #F59E0B; outline-offset: -1px; background: #FFFBEB; }
    body.lkh-edit-mode .lkh-editable-cell:hover { background: #FEF3C7; }
    .lkh-editable-cell.lkh-cell-edited { background: #FDE68A !important; }
    .lkh-editable-cell.lkh-cell-edited .lkh-edit-display { color: #92400E; }
    .lkh-cell-input { width: 100%; min-width: 45px; padding: 2px 4px; border: 1.5px solid #F59E0B; border-radius: 6px; background: #fff; font-size: 11px; font-weight: 700; text-align: center; color: #1F2937; }
    .lkh-cell-input:focus { outline: none; border-color: #DC2626; box-shadow: 0 0 0 2px rgba(220,38,38,0.15); }
</style>
@endsection

@section('content')

<div class="page-wrapper max-w-full mx-auto space-y-6 px-4">

    {{-- FILTER PANEL --}}
    <div class="no-print bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden no-print-filter hover:shadow-md">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary-red text-white flex items-center justify-center shadow-md shadow-red-500/10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                    </svg>
                </div>
                <span class="font-bold text-gray-800 tracking-wide text-sm uppercase">Filter Laporan Kerja Harian (LKH)</span>
            </div>
            <div class="flex items-center gap-2">
                @php $canEdit = $canEdit ?? false; $ttdLocked = $ttdLocked ?? false; @endphp
                @if ($canEdit)
                    @if ($ttdLocked)
                        <span class="inline-flex items-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl bg-gray-100 text-gray-400 border border-gray-200 cursor-not-allowed" title="Edit terkunci karena TTD sudah diisi">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <span>Edit Terkunci (TTD)</span>
                        </span>
                    @else
                        <button type="button" id="btn-edit-lkh" onclick="toggleLkhEdit(true)"
                                class="flex items-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl transition-all shadow-sm border border-amber-300 bg-amber-50 text-amber-800 hover:bg-amber-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit Actual</span>
                        </button>
                        <button type="button" id="btn-save-lkh" onclick="saveLkhEdits()" style="display:none;background-color:#DC2626 !important;"
                                class="flex items-center gap-2 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-all shadow-sm border border-red-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Simpan</span>
                        </button>
                        <button type="button" id="btn-cancel-lkh" onclick="toggleLkhEdit(false)" style="display:none"
                                class="flex items-center gap-2 text-xs font-bold px-4 py-2.5 rounded-xl transition-all border border-gray-300 bg-white text-gray-500 hover:bg-gray-50">
                            <span>Batal</span>
                        </button>
                    @endif
                @endif
                <a href="{{ route('supervisor.reports.daily_production', ['line' => $selectedLineName, 'shift' => $selectedShift, 'date' => $date, 'format' => 'excel']) }}"
                   class="flex items-center gap-2 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-all shadow-sm no-underline"
                   style="background-color:#15803d !important;"
                   onmouseover="this.style.backgroundColor='#166534'" onmouseout="this.style.backgroundColor='#15803d'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Export Excel</span>
                </a>
            </div>
        </div>
        @if ($canEdit && !$ttdLocked)
        <div id="lkh-edit-banner" class="no-print px-6 py-2.5 bg-amber-50 border-b border-amber-200 text-amber-800 text-xs font-semibold flex items-center gap-2" style="display:none">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span>Mode edit aktif — klik sel <b>Good / Repair / Reject / Act Start / Act Fin</b> untuk mengubah data actual, lalu tekan <b>Simpan</b>.</span>
        </div>
        @endif
        <div class="p-6">
            <form id="filter-form" method="GET" action="{{ route('supervisor.reports.daily_production') }}" class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                <input type="hidden" id="active-line" name="line" value="{{ $selectedLineName }}">
                <input type="hidden" id="active-shift" name="shift" value="{{ $selectedShift }}">
                <div class="flex flex-wrap items-center gap-6">
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Production Line</label>
                        <div class="flex items-center gap-2 bg-gray-50 p-1.5 rounded-2xl border border-gray-100">
                            @foreach ($lineNamesUnique as $name)
                                @php $cleanName = trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $name)); $isActive = strtolower($name) === strtolower($selectedLineName); @endphp
                                <button type="button" onclick="document.getElementById('active-line').value='{{ $name }}';this.form.submit();"
                                        class="px-5 py-2.5 rounded-xl border {{ $isActive ? 'bg-primary-red border-primary-red text-white shadow-md shadow-red-500/20 font-black' : 'bg-white border-gray-200 text-gray-500 hover:border-red-300 hover:text-primary-red font-semibold' }} transition-all flex items-center justify-center min-w-[60px] text-xs uppercase">
                                    {{ $cleanName }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Shift Kerja</label>
                        <div class="flex items-center gap-2 bg-gray-50 p-1.5 rounded-2xl border border-gray-100">
                            @php $isPagiActive = str_contains(strtolower($selectedShift), 'pagi') || str_contains(strtolower($selectedShift), '1'); $isMalamActive = str_contains(strtolower($selectedShift), 'malam') || str_contains(strtolower($selectedShift), '2'); @endphp
                            <button type="button" onclick="document.getElementById('active-shift').value='Shift Pagi';this.form.submit();"
                                    class="px-6 py-2.5 rounded-xl text-xs transition-all border {{ $isPagiActive ? 'bg-primary-red border-primary-red text-white shadow-md shadow-red-500/20 font-black' : 'bg-white border-gray-200 text-gray-400 hover:bg-red-50 hover:text-primary-red font-semibold' }}">SHIFT PAGI</button>
                            <button type="button" onclick="document.getElementById('active-shift').value='Shift Malam';this.form.submit();"
                                    class="px-6 py-2.5 rounded-xl text-xs transition-all border {{ $isMalamActive ? 'bg-primary-red border-primary-red text-white shadow-md shadow-red-500/20 font-black' : 'bg-white border-gray-200 text-gray-400 hover:bg-red-50 hover:text-primary-red font-semibold' }}">SHIFT MALAM</button>
                        </div>
                    </div>
                </div>
                <div class="flex items-end gap-4 w-full lg:w-auto">
                    <div class="flex-1 lg:flex-none min-w-[200px]">
                        <label for="date" class="block text-xs font-black text-gray-500 uppercase tracking-widest mb-2">Tanggal</label>
                        <input type="date" name="date" id="date" onchange="this.form.submit();" class="w-full rounded-xl border-gray-200 text-sm font-semibold text-gray-700 focus:border-primary-red focus:ring focus:ring-red-200 focus:ring-opacity-50" value="{{ $date }}">
                    </div>
                    <button type="submit" class="bg-gradient-to-r from-red-600 to-red-800 hover:from-red-700 hover:to-red-900 text-white font-bold text-sm px-6 py-2.5 rounded-xl shadow-md shadow-red-500/10 transition-all border border-red-600/20 flex items-center justify-center gap-2 h-[42px]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>Tampilkan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- LAPORAN BODY --}}
    <div class="bg-white rounded-3xl border border-gray-200 shadow-xl print-card">
        <div class="p-8 space-y-8">

            {{-- HEADER BRANDING --}}
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 pb-6 border-b-2 border-gray-900">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-white border-2 border-gray-800 rounded-2xl flex items-center justify-center p-1 shadow-sm">
                        <span class="text-gray-900 font-black tracking-tighter text-2xl">IPPI</span>
                    </div>
                    <div>
                        <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest leading-none">PRODUCTION SECTION</h4>
                        <h3 class="text-lg font-black text-gray-800 tracking-wide mt-1">INTI PANTJA PRESS INDUSTRI</h3>
                    </div>
                </div>
                <div class="text-center md:text-right">
                    <h1 class="text-2xl font-black text-gray-900 uppercase tracking-tight">LAPORAN KERJA HARIAN STAMPING</h1>
                    <p class="text-xs font-semibold text-gray-500 mt-1 uppercase tracking-widest">Sistem Pendataan Terintegrasi &amp; real-time</p>
                </div>
            </div>

            {{-- SIGNATURES & METADATA --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                <div class="lg:col-span-4 bg-gradient-to-br from-slate-50 to-slate-100 rounded-3xl border border-gray-200 p-6 grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-1 gap-6 items-center">
                    <div class="lg:border-b lg:border-gray-200/60 lg:pb-3.5 last:border-b-0 last:pb-0 w-full">
                        <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">SHOP LINE</span>
                        <span class="text-sm font-black text-gray-800">{{ $selectedLineName }}</span>
                    </div>
                    <div class="lg:border-b lg:border-gray-200/60 lg:pb-3.5 last:border-b-0 last:pb-0 w-full">
                        <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">TANGGAL KERJA</span>
                        <span class="text-sm font-black text-gray-800">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
                    </div>
                    <div class="last:border-b-0 last:pb-0 w-full">
                        <span class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">SHIFT OPERASIONAL</span>
                        <div class="space-y-1">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 shadow-sm border border-amber-200/50">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>{{ $latestShiftName }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-8 bg-white rounded-3xl border border-gray-200 p-4 shadow-sm">
                    <div class="grid grid-cols-1 sm:grid-cols-3 print:grid-cols-3 gap-4 text-center text-xs lg:text-[13px] uppercase font-bold">
                        @foreach (['Supervisor', 'Foreman', 'Team Leader'] as $role)
                        @php
                            $roleKey = strtolower(str_replace(' ', '', $role));
                            $sigState = $signatureStatus[$roleKey] ?? ['signed' => false, 'available' => false];
                            $isLocked = !$sigState['signed'] && !$sigState['available'];
                            $isSigned = $sigState['signed'];
                        @endphp
                        <div id="card_ttd_{{ $roleKey }}" class="flex flex-col items-center justify-between p-3 rounded-2xl bg-slate-50/50 border border-slate-200/60 relative group shadow-sm transition-all hover:shadow-md hover:border-slate-300{{ $isLocked ? ' opacity-50' : '' }}">
                            <div id="lock_ttd_{{ $roleKey }}" class="absolute inset-0 bg-white/70 backdrop-blur-[1px] rounded-2xl flex flex-col items-center justify-center z-10 cursor-not-allowed{{ $isLocked ? '' : ' hidden' }}">
                                <svg class="w-5 h-5 text-slate-400 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-wider">LOCKED</span>
                            </div>
                            <span class="block text-xs tracking-wider font-black text-gray-500 mb-2">{{ $role }}</span>
                            <div id="badge_ttd_{{ $roleKey }}" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[9px] font-black transition-all select-none mb-2{{ $isSigned ? ' bg-emerald-50 text-emerald-700 border-emerald-200' : ($isLocked ? ' bg-gray-100 text-gray-400 border-gray-200' : ' bg-gray-100 text-gray-400 border-gray-200') }}">
                                @if ($isSigned)
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span class="text-emerald-700">SIGNED</span>
                                @elseif ($isLocked)
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span><span>LOCKED</span>
                                @else
                                <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span><span>UNSIGNED</span>
                                @endif
                            </div>
                            <div id="clickarea_ttd_{{ $roleKey }}" class="h-20 w-full flex items-center justify-center border border-dashed border-gray-300 rounded-xl transition-all relative overflow-hidden group mb-2{{ $isLocked ? ' opacity-50 cursor-not-allowed' : ' cursor-pointer hover:border-emerald-500 hover:bg-emerald-50/20' }}"
                                 @if(!$isLocked) onclick="openSignatureModal('{{ $roleKey }}')" title="Klik untuk menggambar tanda tangan {{ $role }}" @endif>
                                <img id="img_ttd_{{ $roleKey }}" class="max-h-18 max-w-[95%] object-contain{{ $isSigned ? '' : ' hidden' }} transition-all duration-300" />
                                <div id="placeholder_ttd_{{ $roleKey }}" class="text-[10px] font-black text-gray-400 uppercase tracking-wider flex flex-col items-center gap-1.5{{ $isSigned ? ' hidden' : '' }} group-hover:text-emerald-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    <span>@if($isLocked) LOCKED @else Klik TTD @endif</span>
                                </div>
                                <button type="button" id="btn_delete_ttd_{{ $roleKey }}" class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-red-100 hover:bg-red-200 text-red-600 flex items-center justify-center{{ $isSigned ? '' : ' hidden' }} transition-all no-print hover:scale-110 shadow-sm"
                                        onclick="event.stopPropagation();deleteSignature('{{ $roleKey }}')" title="Hapus tanda tangan">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                            <div class="w-full px-1">
                                <input type="text" id="ttd_{{ $roleKey }}" name="ttd_{{ $roleKey }}"
                                       placeholder="NAMA {{ strtoupper($role) }}"
                                       value="{{ $sigState['name'] ?? '' }}"
                                       class="border-0 border-b border-dashed border-transparent hover:border-gray-300 focus:border-red-500 focus:ring-0 text-center bg-transparent w-full font-black text-gray-800 py-0.5 text-xs lg:text-[13px] uppercase tracking-wide transition-all focus:outline-none placeholder-gray-400" />
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- OEE MONITORING CARDS --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-6 bg-red-600 rounded-full animate-pulse"></div>
                    <h2 class="text-md font-black text-gray-800 uppercase tracking-wide flex items-center gap-2">
                        <span>Real-Time Factory Performance Monitoring</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 animate-pulse"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1"></span>LIVE</span>
                    </h2>
                </div>
                @include('reports.partials.lkh_oee_cards')
            </div>

            {{-- HELPER --}}
            @php $timeCell = fn($dt) => $dt ? (is_string($dt) ? $dt : $dt->format('H:i')) : '-'; @endphp

            {{-- ===== SECTION 1: PRODUCTION SCHEDULE ===== --}}
            <div class="space-y-3">
                <div class="section-header">
                    <div class="bar" style="background:#991B1B"></div>
                    <h2>1. Production Schedule</h2>
                    <span class="sub">PPC Master Timeline</span>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-300 shadow-sm">
                    <table class="section-table">
                        <thead>
                            <tr>
                                <th colspan="7" class="group-border">Schedule</th>
                                <th colspan="3" class="group-border">Uchi Dandori</th>
                                <th>Total Uchi</th>
                                <th>TPT</th>
                                <th>Break</th>
                                <th>Work Time</th>
                                <th>GSPH</th>
                            </tr>
                            <tr>
                                <th style="width:40px">No</th>
                                <th style="width:180px">Job No</th>
                                <th style="width:70px">Plan Qty</th>
                                <th style="width:60px">Machine</th>
                                <th style="width:65px">CT (sec)</th>
                                <th style="width:70px">Start</th>
                                <th style="width:70px" class="group-border">Finish</th>
                                <th style="width:90px">Dies & Variant</th>
                                <th style="width:60px">1st-Q Check</th>
                                <th style="width:60px" class="group-border">Total Dan (min)</th>
                                <th style="width:65px">Uchi (min)</th>
                                <th style="width:60px">TPT (min)</th>
                                <th style="width:60px">Break (min)</th>
                                <th style="width:65px">Work (min)</th>
                                <th style="width:65px">GSPH</th>
                            </tr>
                        @php
                            $lastJobFinish = null;
                            foreach ($jobsData as $j) {
                                if (($j['row_type'] ?? 'job') === 'job' && ($j['schedule_finish'] ?? null)) {
                                    $lastJobFinish = $j['schedule_finish'];
                                }
                            }
                        @endphp
                        <tbody>
                            @forelse ($jobsData as $job)
                                @if (($job['row_type'] ?? 'job') === 'break')
                                <tr class="break-row">
                                    <td class="text-center font-bold text-amber-600">—</td>
                                    <td colspan="4" class="text-center font-black text-amber-900 uppercase tracking-widest">
                                        <span class="inline-flex items-center gap-2">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            {{ $job['break_label'] ?? $job['job_master'] ?? 'ISTIRAHAT' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($job['schedule_start'])
                                        <span class="time-box">
                                            <span class="text-[11px] font-bold text-amber-800">{{ $job['schedule_start']->format('H:i') }}</span>
                                        </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($job['schedule_finish'])
                                        <span class="time-box">
                                            <span class="text-[11px] font-bold text-amber-800">{{ $job['schedule_finish']->format('H:i') }}</span>
                                        </span>
                                        @endif
                                    </td>
                                    <td colspan="8" class="font-bold text-amber-700 text-center">
                                        @if ($job['schedule_start'] && $job['schedule_finish'])
                                        <span class="px-2 py-0.5 rounded-full bg-white border border-amber-200 text-[10px] font-bold text-amber-700">
                                            {{ abs($job['schedule_finish']->diffInMinutes($job['schedule_start'])) }} MINS
                                        </span>
                                        @endif
                                    </td>
                                </tr>
                                @else
                                @php
                                    $sTime = $job['schedule_start'] ?? null;
                                    $fTime = $job['schedule_finish'] ?? null;
                                    $planQty = $job['plan_qty'] ?? 0;
                                    $ctPlan = $job['plan_ct'] ?? 0;
                                    $procTime = $job['process_time'] ?? 0;
                                    $tptPlan = $job['tpt_plan'] ?? 0;
                                    $breakTime = $job['break_time_duration'] ?? 0;
                                    $workTime = max(0, $tptPlan + $breakTime);
                                    $gsphVal = $job['plan_gsph'] ?? 0;
                                    $isLastJob = $fTime && $lastJobFinish && $fTime->eq($lastJobFinish);
                                    if ($isLastJob) {
                                        $fTime = $shiftDisplayEnd;
                                    }
                                @endphp
                                <tr>
                                    <td class="text-center font-bold text-gray-500">{{ $job['display_no'] ?? $loop->iteration }}</td>
                                    <td class="text-center font-semibold text-gray-800" title="{{ $job['job_no'] ?? '' }}">{{ $job['job_master'] ?? '-' }}</td>
                                    <td class="cell-qty">{{ number_format($planQty) }}</td>
                                    <td class="text-center">{{ $job['total_mesin'] ?? 1 }}</td>
                                    <td class="text-center font-semibold">{{ number_format($ctPlan,1) }}</td>
                                    <td class="text-center cell-time">{{ $timeCell($sTime) }}</td>
                                    <td class="text-center cell-time">{{ $timeCell($fTime) }}</td>
                                    <td class="text-center font-semibold">@fmtMin($job['dies_variant_time'] ?? 0)</td>
                                    <td class="text-center">@fmtMin($job['qcheck_time'] ?? 0)</td>
                                    <td class="cell-qty">@fmtMin($job['dandori_time'] ?? 0)</td>
                                    <td class="cell-qty">{{ (int)ceil($procTime) }}</td>
                                    <td class="cell-qty font-bold">@fmtMin($tptPlan)</td>
                                    <td class="cell-qty">@fmtMin($breakTime)</td>
                                    <td class="cell-qty font-bold">@fmtMin($workTime)</td>
                                    <td class="cell-qty font-bold text-red-900">{{ number_format($gsphVal,0) }}</td>
                                </tr>
                                @endif
                            @empty
                                <tr><td colspan="15" class="text-center py-8 text-gray-500 font-bold">Tidak ada jadwal produksi</td></tr>
                            @endforelse
                        </tbody>
                        @php
                            $schedRows = collect($jobsData)->where('row_type','job');
                            $totPlanQty = $schedRows->sum('plan_qty');
                            $totProcTime = $schedRows->sum('process_time');
                            $totDandori = $schedRows->sum('dandori_time');
                            $totTptPlan = $schedRows->sum('tpt_plan');
                            $totBreak = $schedRows->sum('break_time_duration');
                            $totWork = $totTptPlan + $totBreak;
                            $totGsph = $totTptPlan > 0 ? round($totPlanQty / ($totTptPlan / 60)) : 0;
                        @endphp
                        <tfoot>
                            <tr>
                                <td></td>
                                <td class="font-bold">TOTAL SHIFT</td>
                                <td class="cell-qty font-bold">{{ number_format($totPlanQty) }}</td>
                                <td></td><td></td><td></td><td></td>
                                <td class="cell-qty font-bold">@fmtMin($schedRows->sum('dies_variant_time'))</td><td class="cell-qty font-bold">@fmtMin($schedRows->sum('qcheck_time'))</td>
                                <td class="cell-qty font-bold">@fmtMin($totDandori)</td>
                                <td class="cell-qty font-bold">{{ (int)ceil($totProcTime) }}</td>
                                <td class="cell-qty font-bold">@fmtMin($totTptPlan)</td>
                                <td class="cell-qty font-bold">@fmtMin($totBreak)</td>
                                <td class="cell-qty font-bold">@fmtMin($totWork)</td>
                                <td class="cell-qty font-bold text-red-900">{{ number_format($totGsph,0) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                </div>
            </div>

            {{-- ===== SECTION 2: ACTUAL LAPANGAN ===== --}}
            <div class="space-y-3">
                <div class="section-header">
                    <div class="bar" style="background:#DC2626"></div>
                    <h2>2. Actual Lapangan</h2>
                    <span class="sub">Realtime Execution</span>
                </div>
                <div class="overflow-x-auto rounded-xl border border-gray-300 shadow-sm">
                    <table class="section-table">
                        <thead>
                            <tr>
                                <th colspan="12" class="group-border">Schedule</th>
                                <th colspan="2" class="group-border">CT Actual</th>
                                <th class="group-border">Press Time</th>
                                <th colspan="3" class="group-border">Uchi Dandori</th>
                                <th colspan="6" class="group-border">Down Time</th>
                                <th colspan="2" class="group-border">TPT</th>
                                <th colspan="2" class="group-border">Break</th>
                                <th class="group-border">Work Time</th>
                                <th colspan="3" class="group-border">Quality Rate</th>
                                <th>OEE</th>
                                <th>GSPH</th>
                            </tr>
                            <tr>
                                <th style="width:34px">No</th>
                                <th style="width:150px">Job No</th>
                                <th style="width:55px">Plan Qty</th>
                                <th style="width:55px">Act Qty</th>
                                <th style="width:50px">Good</th>
                                <th style="width:50px">Repair</th>
                                <th style="width:50px">Reject</th>
                                <th style="width:50px">Stroke</th>
                                <th style="width:60px" class="group-border">PL Start</th>
                                <th style="width:60px" class="group-border">PL Fin</th>
                                <th style="width:60px" class="group-border">Act Start</th>
                                <th style="width:60px" class="group-border">Act Fin</th>
                                <th style="width:60px">CT Record</th>
                                <th style="width:60px" class="group-border">CT LKH</th>
                                <th style="width:65px" class="group-border">Press Time (Menit)</th>
                                <th style="width:90px">Dies & Variant</th>
                                <th style="width:60px">1st-Q Check</th>
                                <th style="width:60px" class="group-border">Total Dan (min)</th>
                                <th style="width:55px">Dies</th>
                                <th style="width:55px">Machine</th>
                                <th style="width:55px">Material</th>
                                <th style="width:55px">Log</th>
                                <th style="width:60px">Production</th>
                                <th style="width:55px" class="group-border">Total</th>
                                <th style="width:50px">Plan</th>
                                <th style="width:50px" class="group-border">Actual</th>
                                <th style="width:40px">Type</th>
                                <th style="width:45px" class="group-border">Time</th>
                                <th style="width:60px" class="group-border">Work Time (Menit)</th>
                                <th style="width:50px">Pass%</th>
                                <th style="width:50px">Rep%</th>
                                <th style="width:50px" class="group-border">Rej%</th>
                                <th style="width:55px">OEE (%)</th>
                                <th style="width:55px">GSPH (Pcs/Hour)</th>
                            </tr>
                        </thead>
                        @include('reports.partials.lkh_actual_rows')
                    </table>
                </div>
            </div>

            {{-- ===== SECTION 3: SUMMARY ACHIEVEMENT ===== --}}
            <div class="space-y-3">
                <div class="section-header">
                    <div class="bar" style="background:#991B1B"></div>
                    <h2>3. Summary Achievement</h2>
                    <span class="sub">Shift Performance Overview</span>
                </div>
                <div class="overflow-x-auto rounded-xl border border-gray-300 shadow-sm bg-white">
                    <table class="summ-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:40%">KPI PARAMETER</th>
                                <th style="width:20%">PLAN</th>
                                <th style="width:20%">ACTUAL</th>
                                <th style="width:20%">%</th>
                            </tr>
                        </thead>
                        @include('reports.partials.lkh_summary_rows')
                    </table>
                </div>
            </div>

        </div>{{-- /p-8 --}}
    </div>{{-- /print-card --}}

</div>{{-- /page-wrapper --}}

{{-- CONFIRM MODAL --}}
<div id="confirm-modal" class="no-print fixed inset-0 z-[1000] hidden items-center justify-center p-4" style="background:rgba(15,23,42,0.82);backdrop-filter:blur(8px);" onclick="closeConfirmModal(false)">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-2xl w-full max-w-sm overflow-hidden p-6 text-center" onclick="event.stopPropagation()">
        <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center">
            <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        </div>
        <p class="text-sm font-bold text-gray-800 mb-1">Hapus Tanda Tangan</p>
        <p class="text-xs text-gray-500 mb-6" id="confirm-message">Apakah anda yakin?</p>
        <div class="flex items-center justify-center gap-3">
            <button type="button" onclick="closeConfirmModal(false)" class="px-5 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-bold text-xs uppercase tracking-wider transition-all">Batal</button>
            <button type="button" onclick="closeConfirmModal(true)" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs uppercase tracking-wider shadow-md transition-all">Ya, Hapus</button>
        </div>
    </div>
</div>

{{-- SIGNATURE MODAL --}}
<div id="signature-modal" class="no-print fixed inset-0 z-[999] hidden items-center justify-center p-4" style="background:rgba(15,23,42,0.82);backdrop-filter:blur(8px);" onclick="handleModalBackdropClick(event)">
    <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl w-full max-w-lg overflow-hidden" onclick="event.stopPropagation()">
        <div class="px-6 py-4 bg-gradient-to-r from-slate-50 to-white border-b border-slate-100 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-emerald-500 flex items-center justify-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                </div>
                <div><p class="text-xs font-black text-slate-800 uppercase tracking-widest">Tanda Tangan Digital</p><p class="text-[10px] text-slate-400 font-semibold mt-0.5">Verifikasi Elektronik LKH IPPI</p></div>
            </div>
            <button type="button" onclick="closeSignatureModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <p class="text-sm font-bold text-gray-700 text-center" id="signature-role-label">Tanda Tangan</p>
            <div class="bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl p-4 flex items-center justify-center" style="min-height:200px">
                <canvas id="signature-canvas" width="500" height="200" class="w-full cursor-crosshair rounded-xl" style="touch-action:none;background:#fff"></canvas>
            </div>
            <div class="flex items-center justify-between gap-3">
                <button type="button" onclick="clearSignature()" class="px-5 py-2.5 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 font-bold text-xs uppercase tracking-wider transition-all">Hapus</button>
                <button type="button" onclick="saveSignature()" class="px-8 py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-bold text-xs uppercase tracking-wider shadow-md transition-all">Simpan Tanda Tangan</button>
            </div>
        </div>
    </div>
</div>

@php
    $sigSaveUrl = '';
    $sigDeleteUrl = '';
    $sigGetUrl = '';
    $sigStatusUrl = '';
    try { $sigSaveUrl = route('signature.save'); } catch (\Exception $e) { $sigSaveUrl = url('/signature/save'); }
    try { $sigDeleteUrl = route('signature.delete'); } catch (\Exception $e) { $sigDeleteUrl = url('/signature/delete'); }
    try { $sigGetUrl = route('signature.get'); } catch (\Exception $e) { $sigGetUrl = url('/signature/get'); }
    try { $sigStatusUrl = route('signature.status'); } catch (\Exception $e) { $sigStatusUrl = url('/signature/status'); }
@endphp

@push('scripts')
<script>
let activeSignatureRole = null;
const sigWorkDate = '{{ $date }}';

function openSignatureModal(role) {
    activeSignatureRole = role;
    document.getElementById('signature-role-label').textContent = 'Tanda Tangan: ' + role.charAt(0).toUpperCase() + role.slice(1);
    const m = document.getElementById('signature-modal');
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.body.classList.add('signature-modal-open');
    if (!canvas) initCanvas();
    clearSignature();
}

function closeSignatureModal() {
    const m = document.getElementById('signature-modal');
    m.classList.add('hidden');
    m.classList.remove('flex');
    document.body.classList.remove('signature-modal-open');
}

function handleModalBackdropClick(e) {
    if (e.target === document.getElementById('signature-modal')) closeSignatureModal();
}

let canvas, ctx, isDrawing = false, drawPending = false, lastPos = null;

function initCanvas() {
    canvas = document.getElementById('signature-canvas');
    ctx = canvas.getContext('2d');
    ctx.strokeStyle = '#1F2937';
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    canvas.addEventListener('mousedown', startDraw);
    canvas.addEventListener('mousemove', onMouseMove);
    canvas.addEventListener('mouseup', stopDraw);
    canvas.addEventListener('mouseleave', stopDraw);
    canvas.addEventListener('touchstart', onTouchStart, { passive: true });
    canvas.addEventListener('touchmove', onTouchMove, { passive: true });
    canvas.addEventListener('touchend', stopDraw, { passive: true });
}

function getCanvasPos(clientX, clientY) {
    const rect = canvas.getBoundingClientRect();
    return { x: (clientX - rect.left) * (canvas.width / rect.width), y: (clientY - rect.top) * (canvas.height / rect.height) };
}

function onMouseMove(e) { if (!isDrawing) return; lastPos = { clientX: e.clientX, clientY: e.clientY }; scheduleDraw(); }
function onTouchStart(e) { const t = e.touches[0]; lastPos = { clientX: t.clientX, clientY: t.clientY }; startDrawFromTouch(); }
function onTouchMove(e) { if (!isDrawing) return; const t = e.touches[0]; lastPos = { clientX: t.clientX, clientY: t.clientY }; scheduleDraw(); }

function startDrawFromTouch() {
    isDrawing = true;
    const p = getCanvasPos(lastPos.clientX, lastPos.clientY);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
}

function startDraw(e) {
    isDrawing = true;
    const p = getCanvasPos(e.clientX, e.clientY);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
}

function scheduleDraw() {
    if (drawPending) return;
    drawPending = true;
    requestAnimationFrame(() => {
        drawPending = false;
        if (!isDrawing || !lastPos) return;
        const p = getCanvasPos(lastPos.clientX, lastPos.clientY);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    });
}

function stopDraw() { isDrawing = false; drawPending = false; }

function clearSignature() { ctx.clearRect(0, 0, canvas.width, canvas.height); }

function saveSignature() {
    const dataUrl = canvas.toDataURL('image/png');
    fetch('{{ $sigSaveUrl }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ role: activeSignatureRole, signature: dataUrl, work_date: sigWorkDate })
    })
    .then(r => {
        if (!r.ok) return r.json().then(d => { throw new Error(d.error || 'Gagal menyimpan'); });
        return r.json();
    })
    .then(d => {
        const img = document.getElementById('img_ttd_' + activeSignatureRole);
        const placeholder = document.getElementById('placeholder_ttd_' + activeSignatureRole);
        const badge = document.getElementById('badge_ttd_' + activeSignatureRole);
        const delBtn = document.getElementById('btn_delete_ttd_' + activeSignatureRole);
        img.src = dataUrl;
        img.classList.remove('hidden');
        placeholder.classList.add('hidden');
        badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span class="text-emerald-700">SIGNED</span>';
        badge.classList.remove('bg-gray-100','text-gray-400','border-gray-200');
        badge.classList.add('bg-emerald-50','text-emerald-700','border-emerald-200');
        delBtn.classList.remove('hidden');
        closeSignatureModal();
        refreshSignatureStatus();
    })
    .catch(err => {
        alert(err.message);
    });
}

let confirmResolve = null;

function showConfirm(message) {
    document.getElementById('confirm-message').textContent = message;
    document.getElementById('confirm-modal').classList.remove('hidden');
    document.getElementById('confirm-modal').classList.add('flex');
    return new Promise(resolve => { confirmResolve = resolve; });
}

function closeConfirmModal(result) {
    document.getElementById('confirm-modal').classList.add('hidden');
    document.getElementById('confirm-modal').classList.remove('flex');
    if (confirmResolve) { confirmResolve(result); confirmResolve = null; }
}

function deleteSignature(role) {
    showConfirm('Hapus tanda tangan ' + role + '?').then(confirmed => {
        if (!confirmed) return;
        const img = document.getElementById('img_ttd_' + role);
        const placeholder = document.getElementById('placeholder_ttd_' + role);
        const badge = document.getElementById('badge_ttd_' + role);
        const delBtn = document.getElementById('btn_delete_ttd_' + role);
        img.classList.add('hidden'); img.src = '';
        placeholder.classList.remove('hidden');
        badge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span><span>UNSIGNED</span>';
        badge.classList.remove('bg-emerald-50','text-emerald-700','border-emerald-200');
        badge.classList.add('bg-gray-100','text-gray-400','border-gray-200');
        delBtn.classList.add('hidden');
        fetch('{{ $sigDeleteUrl }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ role: role, work_date: sigWorkDate })
        })
            .then(r => r.json())
            .then(d => { refreshSignatureStatus(); })
            .catch(() => {});
    });
}

function refreshSignatureStatus() {
    fetch('{{ $sigStatusUrl }}?work_date=' + encodeURIComponent(sigWorkDate))
        .then(r => r.json())
        .then(status => {
            const chain = ['teamleader', 'foreman', 'supervisor'];
            chain.forEach(role => {
                const s = status[role];
                const lock = document.getElementById('lock_ttd_' + role);
                const clickArea = document.getElementById('clickarea_ttd_' + role);
                const card = document.getElementById('card_ttd_' + role);
                if (!s) return;
                if (s.signed) {
                    if (lock) { lock.classList.add('hidden'); lock.style.display = ''; }
                    if (clickArea) {
                        clickArea.onclick = function() { openSignatureModal(role); };
                        clickArea.classList.remove('opacity-50');
                        clickArea.classList.add('cursor-pointer');
                    }
                    if (card) card.classList.remove('opacity-50');
                } else if (s.available) {
                    if (lock) { lock.classList.add('hidden'); lock.style.display = ''; }
                    if (clickArea) {
                        clickArea.onclick = function() { openSignatureModal(role); };
                        clickArea.classList.remove('opacity-50');
                        clickArea.classList.add('cursor-pointer');
                    }
                    if (card) card.classList.remove('opacity-50');
                } else {
                    if (lock) lock.classList.remove('hidden');
                    if (clickArea) {
                        clickArea.onclick = null;
                        clickArea.classList.add('opacity-50');
                        clickArea.classList.remove('cursor-pointer');
                    }
                    if (card) card.classList.add('opacity-50');
                }
            });
        })
        .catch(() => {});
}

document.addEventListener('DOMContentLoaded', function() {
    @foreach (['supervisor','foreman','teamleader'] as $role)
    fetch('{{ $sigGetUrl }}?role={{ $role }}&work_date=' + encodeURIComponent(sigWorkDate))
        .then(r => r.json())
        .then(d => {
            if (d.signature) {
                document.getElementById('img_ttd_{{ $role }}').src = d.signature;
                document.getElementById('img_ttd_{{ $role }}').classList.remove('hidden');
                document.getElementById('placeholder_ttd_{{ $role }}').classList.add('hidden');
                document.getElementById('badge_ttd_{{ $role }}').innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span class="text-emerald-700">SIGNED</span>';
                document.getElementById('badge_ttd_{{ $role }}').classList.remove('bg-gray-100','text-gray-400','border-gray-200');
                document.getElementById('badge_ttd_{{ $role }}').classList.add('bg-emerald-50','text-emerald-700','border-emerald-200');
                document.getElementById('btn_delete_ttd_{{ $role }}').classList.remove('hidden');
            }
        })
        .catch(() => {});
    @endforeach
    refreshSignatureStatus();
});

// ── LKH Edit Actual mode ─────────────────────────────────────────────
const LKH_UPDATE_URL = '{{ route('supervisor.reports.daily_production.update_cells') }}';
const LKH_LINE = @json($selectedLineName);
const LKH_DATE = @json($date);
const LKH_SHIFT = @json($selectedShift);
const LKH_CSRF = '{{ csrf_token() }}';

let lkhEditMode = false;
let lkhUpdates = {};

function lkhSetEditMode(on) {
    lkhEditMode = on;
    document.body.classList.toggle('lkh-edit-mode', on);
    const btnEdit = document.getElementById('btn-edit-lkh');
    const btnSave = document.getElementById('btn-save-lkh');
    const btnCancel = document.getElementById('btn-cancel-lkh');
    const banner = document.getElementById('lkh-edit-banner');
    if (btnEdit) btnEdit.style.display = on ? 'none' : '';
    if (btnSave) btnSave.style.display = on ? '' : 'none';
    if (btnCancel) btnCancel.style.display = on ? '' : 'none';
    if (banner) banner.style.display = on ? '' : 'none';
}

function toggleLkhEdit(on) {
    if (!on && lkhEditMode) {
        // revert unsaved edits
        Object.values(lkhUpdates).forEach(u => {
            const cell = document.querySelector('#lkh-actual-tbody .lkh-editable-cell[data-plan-id="' + u.plan_id + '"][data-field="' + u.field + '"]');
            if (cell && u.orig !== undefined) {
                cell.dataset.value = u.orig;
                cell.classList.remove('lkh-cell-edited');
                lkhRestoreCellDisplay(cell);
            }
        });
        lkhUpdates = {};
    }
    lkhSetEditMode(on);
}

function lkhRestoreCellDisplay(cell) {
    cell.dataset.editing = '';
    cell.textContent = '';
    const span = document.createElement('span');
    span.className = 'lkh-edit-display';
    span.textContent = cell.dataset.value;
    cell.appendChild(span);
    const icon = document.createElement('span');
    icon.className = 'lkh-edit-icon';
    icon.textContent = '✎';
    cell.appendChild(icon);
}

function lkhCellToInput(cell) {
    if (cell.dataset.editing === '1') return;
    cell.dataset.editing = '1';
    const type = cell.dataset.type === 'time' ? 'time' : 'number';
    const input = document.createElement('input');
    input.type = type;
    if (type === 'number') { input.step = '1'; input.min = '0'; }
    input.className = 'lkh-cell-input';
    input.value = cell.dataset.value;
    cell.textContent = '';
    cell.appendChild(input);
    input.focus();
    input.select();

    const commit = () => {
        const val = input.value.trim();
        if (val === '') { lkhRestoreCellDisplay(cell); return; }
        if (val === cell.dataset.value) { lkhRestoreCellDisplay(cell); return; }
        const key = cell.dataset.planId + ':' + cell.dataset.field;
        if (!lkhUpdates[key]) lkhUpdates[key] = { plan_id: cell.dataset.planId, field: cell.dataset.field, value: val, orig: cell.dataset.value };
        else lkhUpdates[key].value = val;
        cell.dataset.value = val;
        cell.classList.add('lkh-cell-edited');
        lkhRestoreCellDisplay(cell);
    };
    const cancel = () => { lkhRestoreCellDisplay(cell); };
    input.addEventListener('change', commit);
    input.addEventListener('blur', commit);
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); commit(); input.blur(); }
        if (e.key === 'Escape') { e.preventDefault(); cancel(); }
    });
}

document.addEventListener('click', function(e) {
    if (!lkhEditMode) return;
    const cell = e.target.closest('#lkh-actual-tbody .lkh-editable-cell');
    if (!cell) return;
    if (e.target.tagName === 'INPUT') return;
    lkhCellToInput(cell);
});

function lkhShowToast(msg, isError) {
    let toast = document.getElementById('lkh-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'lkh-toast';
        toast.style.cssText = 'position:fixed;top:16px;right:16px;z-index:9999;padding:12px 18px;border-radius:12px;color:#fff;font-size:13px;font-weight:700;box-shadow:0 6px 20px rgba(0,0,0,0.2);transition:opacity .3s;';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.background = isError ? '#DC2626' : '#15803D';
    toast.style.opacity = '1';
    clearTimeout(lkhShowToast._t);
    lkhShowToast._t = setTimeout(() => { toast.style.opacity = '0'; }, 4000);
}

async function saveLkhEdits() {
    const updates = Object.values(lkhUpdates);
    if (!updates.length) { toggleLkhEdit(false); return; }
    const ok = await showConfirm('Apakah Anda yakin ingin mengedit data actual?');
    if (!ok) return;
    try {
        const res = await fetch(LKH_UPDATE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': LKH_CSRF },
            body: JSON.stringify({ line: LKH_LINE, date: LKH_DATE, shift: LKH_SHIFT, updates })
        });
        const data = await res.json();
        if (!res.ok) { lkhShowToast(data.error || 'Gagal menyimpan data', true); return; }
        lkhApplyFragments(data.html);
        lkhUpdates = {};
        lkhSetEditMode(false);
        lkhShowToast(data.message || 'Data actual berhasil diperbarui');
    } catch (err) {
        lkhShowToast('Terjadi kesalahan jaringan', true);
    }
}

function lkhApplyFragments(html) {
    const tpl = document.createElement('template');
    tpl.innerHTML = html;
    const frag = tpl.content;
    const tbody = document.getElementById('lkh-actual-tbody');
    const tfoot = document.getElementById('lkh-actual-tfoot');
    const summary = document.getElementById('lkh-summary-tbody');
    const cards = document.getElementById('lkh-oee-cards');
    if (tbody) tbody.innerHTML = frag.getElementById('lkh-actual-tbody').innerHTML;
    if (tfoot) tfoot.innerHTML = frag.getElementById('lkh-actual-tfoot').innerHTML;
    if (summary) summary.innerHTML = frag.getElementById('lkh-summary-tbody').innerHTML;
    if (cards) cards.innerHTML = frag.getElementById('lkh-oee-cards').innerHTML;
}
</script>
@endpush
@endsection

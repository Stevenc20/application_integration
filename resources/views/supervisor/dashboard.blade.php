@extends('layouts.supervisor')

@section('title', 'Production Achievement Dashboard')

@section('content')
<div class="space-y-5 min-w-0 space-wrapper">

    <!-- ===== HEADER ===== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-gray-200 rounded-2xl px-5 py-4 sm:px-8 sm:py-5 shadow-sm border-l-4 border-l-red-400">
        <div>
            <h1 class="dash-h1 text-base sm:text-xl lg:text-2xl 2xl:text-3xl font-black text-gray-800 uppercase tracking-wide leading-tight">Production Achievement Dashboard</h1>
            <p class="dash-subtitle text-xs sm:text-sm text-gray-500 mt-0.5 font-medium">Stamping Department &mdash; {{ $selectedLine ?? 'All Lines' }}</p>
        </div>
        <div class="flex flex-row sm:flex-col items-center sm:items-end gap-4 sm:gap-1 shrink-0">
            <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Tgl: <strong id="hdrDate" class="text-gray-700 font-black ml-1"></strong></div>
            <div class="text-2xl sm:text-3xl 2xl:text-5xl font-black text-red-500 tracking-widest tabular-nums" id="liveClock">--:--:--</div>
        </div>
    </div>

    <!-- ===== FILTER BAR ===== -->
    <div class="flex flex-wrap items-center gap-3 bg-white border border-gray-200 rounded-2xl px-5 py-3 shadow-sm">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <input type="date" id="dateInput" onchange="onDateChange()" class="filter-input border border-gray-200 rounded-xl px-3 py-2 text-sm font-semibold text-gray-700 focus:ring-2 focus:ring-red-300 focus:border-red-500 outline-none transition bg-gray-50">
        </div>
        <div class="flex bg-gray-100 rounded-xl p-1 border border-gray-200 gap-1" role="group">
            <button id="s1btn" onclick="setShift(1)" class="filter-btn px-4 py-1.5 text-xs sm:text-sm font-bold rounded-lg transition-all text-white bg-red-500 shadow-sm">Shift 1</button>
            <button id="s2btn" onclick="setShift(2)" class="filter-btn px-4 py-1.5 text-xs sm:text-sm font-bold rounded-lg transition-all text-gray-500 hover:text-gray-700">Shift 2</button>
        </div>
        <div class="flex items-center gap-2 ml-auto">
            <a href="{{ route('supervisor.monitor') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-bold hover:bg-indigo-100 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Monitor
            </a>
            <span class="live-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse inline-block"></span>LIVE
            </span>
        </div>
    </div>

    <!-- ===== LINE CARDS ===== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 min-h-[300px]" id="linesGrid"></div>

    <!-- ===== DAY RANGE ===== -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-2 border-t border-gray-200">
    <p class="day-label text-xs font-bold text-gray-400 uppercase tracking-widest">Grafik Analisis</p>
        <div class="flex bg-gray-100 rounded-xl p-1 border border-gray-200 gap-1" role="group">
            <button id="d1btn" onclick="setDays(1)" class="filter-btn px-5 py-2 text-xs sm:text-sm font-bold rounded-lg transition-all text-white bg-red-500 shadow-sm">Hari Ini</button>
            <button id="d7btn" onclick="setDays(7)" class="filter-btn px-5 py-2 text-xs sm:text-sm font-bold rounded-lg transition-all text-gray-500 hover:text-gray-700">7 Hari</button>
            <button id="d30btn" onclick="setDays(30)" class="filter-btn px-5 py-2 text-xs sm:text-sm font-bold rounded-lg transition-all text-gray-500 hover:text-gray-700">30 Hari</button>
        </div>
    </div>

    <!-- ===== CHARTS TODAY ===== -->
    <div class="flex items-center gap-2 mb-2">
        <span class="text-xs text-gray-400 font-medium">🔄 Drag = select zoom, Shift+Drag = pan</span>
    </div>
    <div id="todayCharts" class="chart-grid grid grid-cols-1 gap-4 2xl:gap-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>Pencapaian Produksi (Pcs)
                <button onclick="resetChart('cQty')" class="ml-auto text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50" title="Reset zoom">↺ Reset</button>
            </div>
            <div class="p-4 flex-1 chart-min-h min-h-[350px] 2xl:min-h-[500px]"><canvas id="cQty"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-full bg-red-500 shrink-0"></span>Total Downtime (Menit)
                <button onclick="resetChart('cDt')" class="ml-auto text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50" title="Reset zoom">↺ Reset</button>
            </div>
            <div class="p-4 flex-1 min-h-[350px] 2xl:min-h-[500px]"><canvas id="cDt"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>Repair &amp; Reject (Pcs)
                <button onclick="resetChart('cRr')" class="ml-auto text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50" title="Reset zoom">↺ Reset</button>
            </div>
            <div class="p-4 flex-1 min-h-[350px] 2xl:min-h-[500px]"><canvas id="cRr"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span>Pencapaian GSPH
                <button onclick="fitGsphChart('cGsph')" class="text-xs text-gray-400 hover:text-blue-500 transition-colors px-2 py-1 rounded-lg hover:bg-blue-50" title="Sesuaikan skala ke data">↺ Fit</button>
                <button onclick="resetChart('cGsph')" class="text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50" title="Reset zoom">↺ Reset</button>
            </div>
            <div class="p-4 flex-1 min-h-[350px] 2xl:min-h-[500px]"><canvas id="cGsph"></canvas></div>
        </div>
    </div>

    <!-- ===== CHARTS TREND ===== -->
    <div id="trendCharts" class="hidden space-y-4">
        <div class="text-center font-black text-gray-600 text-sm sm:text-lg uppercase tracking-widest" id="trendLabel"></div>
        <div class="grid grid-cols-1 gap-4 2xl:gap-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>Tren Pencapaian Produksi (Pcs)
                    <button onclick="resetChart('cTQty')" class="ml-auto text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50" title="Reset zoom">↺ Reset</button>
                </div>
                <div class="p-4 flex-1 min-h-[350px] 2xl:min-h-[500px]"><canvas id="cTQty"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 rounded-full bg-red-500 shrink-0"></span>Tren Total Downtime (Menit)
                    <button onclick="resetChart('cTDt')" class="ml-auto text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50" title="Reset zoom">↺ Reset</button>
                </div>
                <div class="p-4 flex-1 min-h-[350px] 2xl:min-h-[500px]"><canvas id="cTDt"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>Tren Repair &amp; Reject (Pcs)
                    <button onclick="resetChart('cTRr')" class="ml-auto text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50" title="Reset zoom">↺ Reset</button>
                </div>
                <div class="p-4 flex-1 min-h-[350px] 2xl:min-h-[500px]"><canvas id="cTRr"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span>Tren Pencapaian GSPH
                    <button onclick="fitGsphChart('cTGsph')" class="text-xs text-gray-400 hover:text-blue-500 transition-colors px-2 py-1 rounded-lg hover:bg-blue-50" title="Sesuaikan skala ke data">↺ Fit</button>
                    <button onclick="resetChart('cTGsph')" class="text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50" title="Reset zoom">↺ Reset</button>
                </div>
                <div class="p-4 flex-1 min-h-[350px] 2xl:min-h-[500px]"><canvas id="cTGsph"></canvas></div>
            </div>
        </div>
    </div>

</div>

<!-- ===== MODAL ===== -->
<div id="modalBackdrop" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4" onclick="if(event.target===this) closeKpiDetailModal()">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl border border-gray-100 flex flex-col max-h-[90vh] transform scale-95 opacity-0 transition-all duration-200" id="modalDialog">
        <div class="px-5 py-4 border-b border-red-100 flex justify-between items-center bg-red-50 rounded-t-2xl">
            <h3 class="font-black text-red-700 text-base sm:text-lg" id="modalTitle">Detail Data</h3>
            <button onclick="closeKpiDetailModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5 overflow-y-auto flex-1 text-sm" id="modalBody">
            <div class="flex flex-col items-center justify-center py-10 gap-3 text-gray-400">
                <svg class="animate-spin h-8 w-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span class="text-sm font-semibold">Mengambil data...</span>
            </div>
        </div>
        <div class="px-5 py-3 border-t border-gray-100 text-right bg-gray-50 rounded-b-2xl">
            <button onclick="closeKpiDetailModal()" class="flex-1 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all">Tutup</button>
        </div>
    </div>
</div>


{{-- ===== LARGE SCREEN SCALING (1920px / 2560px / 3840px) ===== --}}
<style>
/* ── 1080p / Large Monitor ≥1920px ─────────────────────────── */
@media (min-width: 1920px) {
  .chart-min-h             { min-height: 380px !important; }
  .filter-input            { font-size: 0.95rem !important; padding: 10px 16px !important; }
  .filter-btn              { font-size: 0.9rem !important; padding: 10px 20px !important; }
  #liveClock               { font-size: 3.5rem !important; }
  .dash-h1                 { font-size: 2rem !important; }
  .space-wrapper           { gap: 1.5rem !important; }
  .press-card-header       { font-size: 0.9rem !important; padding: 12px 14px !important; }
  .kpi-row                 { padding: 9px 14px !important; min-height: 34px !important; }
  .kpi-row .kpi-label      { font-size: 0.78rem !important; }
  .kpi-row .kpi-value      { font-size: 0.82rem !important; }
  .detail-toggle           { font-size: 0.78rem !important; padding: 10px 14px !important; }
}

/* ── QHD / 2K ≥2560px ──────────────────────────────────────── */
@media (min-width: 2560px) {
  .chart-min-h             { min-height: 500px !important; }
  .filter-input            { font-size: 1.1rem !important; padding: 12px 20px !important; }
  .filter-btn              { font-size: 1.05rem !important; padding: 12px 28px !important; }
  #liveClock               { font-size: 5rem !important; }
  .dash-h1                 { font-size: 2.6rem !important; }
  .space-wrapper           { gap: 2rem !important; }
  .chart-grid              { gap: 1.75rem !important; }
  .modal-dialog-inner      { max-width: 900px !important; font-size: 1.1rem !important; }
  .press-card-header       { font-size: 1rem !important; padding: 14px 18px !important; }
  .kpi-row                 { padding: 10px 16px !important; min-height: 38px !important; }
  .kpi-row .kpi-label      { font-size: 0.85rem !important; }
  .kpi-row .kpi-value      { font-size: 0.9rem !important; }
  .kpi-row .kpi-pct        { font-size: 0.75rem !important; }
  .detail-toggle           { font-size: 0.85rem !important; padding: 12px 16px !important; }
}

/* ── 4K ≥3840px ─────────────────────────────────────────────── */
@media (min-width: 3840px) {
  .chart-min-h             { min-height: 720px !important; }
  .filter-input            { font-size: 1.5rem !important; padding: 18px 28px !important; border-radius: 1rem !important; }
  .filter-btn              { font-size: 1.45rem !important; padding: 18px 40px !important; border-radius: 1rem !important; }
  #liveClock               { font-size: 7.5rem !important; }
  .dash-h1                 { font-size: 3.75rem !important; }
  .dash-subtitle           { font-size: 1.4rem !important; }
  .space-wrapper           { gap: 3rem !important; }
  .chart-grid              { gap: 2.5rem !important; }
  .section-card            { border-radius: 1.5rem !important; padding: 2rem !important; }
  .modal-dialog-inner      { max-width: 1400px !important; font-size: 1.6rem !important; }
  .live-badge              { font-size: 1.2rem !important; padding: 10px 20px !important; }
  .day-label               { font-size: 1.2rem !important; }
  #linesGrid               { grid-template-columns: repeat(4, 1fr) !important; gap: 2.5rem !important; }
  .press-card-header       { font-size: 1.4rem !important; padding: 18px 24px !important; letter-spacing: 0.25em !important; }
  .kpi-row                 { padding: 14px 22px !important; min-height: 48px !important; }
  .kpi-row .kpi-label      { font-size: 1.1rem !important; }
  .kpi-row .kpi-value      { font-size: 1.2rem !important; }
  .kpi-row .kpi-pct        { font-size: 0.95rem !important; }
  .detail-toggle           { font-size: 1.1rem !important; padding: 16px 22px !important; }
}

/* ── DETAIL PRODUKSI TABLE ───────────────────────────────────── */
.det-scroll {
  overflow-x: auto;
  overflow-y: auto;
  max-height: 320px;
  scrollbar-width: thin;
  scrollbar-color: #e5e7eb #f9fafb;
}
.det-scroll::-webkit-scrollbar        { height: 5px; width: 5px; }
.det-scroll::-webkit-scrollbar-track  { background: #f9fafb; }
.det-scroll::-webkit-scrollbar-thumb  { background: #d1d5db; border-radius: 99px; }
.det-scroll::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

.det-table {
  border-collapse: collapse;
  width: 100%;
  min-width: 900px;
  font-size: 11px;
}
.det-table thead tr {
  position: sticky;
  top: 0;
  z-index: 2;
  background: #f8fafc;
}
.det-table thead th {
  padding: 7px 8px;
  font-size: 9px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #6b7280;
  white-space: nowrap;
  border-bottom: 2px solid #e5e7eb;
  background: #f8fafc;
}
.det-table thead th:first-child  { border-left: 3px solid #e5e7eb; }
.det-table thead th:last-child    { border-right: 3px solid #e5e7eb; }
.det-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.12s;
}
.det-table tbody tr:nth-child(even) { background: #f9fafb; }
.det-table tbody tr:hover           { background: #eff6ff !important; }
.det-table tbody tr:last-child      { border-bottom: none; }
.det-table td {
  padding: 6px 8px;
  font-size: 10px;
  white-space: nowrap;
  vertical-align: middle;
  color: #6b7280;
}

.det-section-label {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 14px;
  background: linear-gradient(90deg, #f1f5f9 0%, #f8fafc 100%);
  border-top: 2px solid #e5e7eb;
  border-bottom: 1px solid #e9ecef;
}
.det-section-label span.label-text {
  font-size: 10px;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: 0.12em;
  color: #64748b;
}
.det-section-label span.label-badge {
  display: inline-flex;
  align-items: center;
  padding: 1px 7px;
  background: #dbeafe;
  color: #1d4ed8;
  border-radius: 99px;
  font-size: 9px;
  font-weight: 800;
  letter-spacing: 0.04em;
}
.det-section-label span.label-badge.zero {
  background: #f3f4f6;
  color: #9ca3af;
}
.det-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 28px 16px;
  background: #fafafa;
  color: #c4c8d0;
}
.det-empty svg  { width: 32px; height: 32px; opacity: 0.5; }
.det-empty span { font-size: 11px; font-weight: 700; letter-spacing: 0.04em; color: #b0b7c3; }

/* ── PRESS CARD NEW LAYOUT ──────────────────────────────────── */
.press-card {
  display: flex;
  flex-direction: column;
  background: #fff;
  border-radius: 1rem;
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  overflow: hidden;
  transition: border-color 0.2s;
}
.press-card:hover { border-color: #fca5a5; }

.press-card-header {
  background: linear-gradient(135deg, #fef2f2 0%, #fff1f2 100%);
  border-bottom: 2px solid #fecaca;
  padding: 10px 12px;
  text-align: center;
  font-size: 0.85rem;
  font-weight: 900;
  color: #dc2626;
  letter-spacing: 0.22em;
  text-transform: uppercase;
}

.press-card-body {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.kpi-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 7px 12px;
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.15s;
  min-height: 32px;
}
.kpi-row:last-child { border-bottom: none; }
.kpi-row:hover { background: #f9fafb; }

.kpi-row .kpi-label {
  font-size: 0.7rem;
  font-weight: 800;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  flex-shrink: 0;
}
.kpi-row .kpi-value {
  font-size: 0.75rem;
  font-weight: 800;
  color: #1f2937;
  text-align: right;
  word-break: break-word;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 1px;
}
.kpi-row .kpi-pct {
  font-size: 0.6rem;
  font-weight: 600;
  color: #9ca3af;
  margin-left: 0;
}

/* Clickable rows — red value + underline + pct below */
.kpi-row-clickable { cursor: pointer; }
.kpi-row-clickable .kpi-val-main {
  color: #dc2626;
  font-weight: 900;
  text-decoration: underline;
  text-decoration-style: dotted;
  text-decoration-color: #fca5a5;
  text-underline-offset: 3px;
}
.kpi-row-clickable .kpi-label {
  color: #dc2626;
  font-weight: 700;
}
.kpi-row-clickable .kpi-pct {
  color: #9ca3af;
  font-weight: 600;
}
.kpi-row-clickable:hover { background: #fef2f2; }

/* GSPH row highlight */
.kpi-row[data-desc="GSPH"] {
  background: #f0f9ff;
  border-bottom: 1px solid #bae6fd;
}
.kpi-row[data-desc="GSPH"] .kpi-label { color: #0369a1; }

/* JOB row highlight */
.kpi-row[data-desc="JOB"] {
  background: #eff6ff;
  border-bottom: 1px solid #bfdbfe;
}
.kpi-row[data-desc="JOB"] .kpi-label { color: #1d4ed8; }

/* STROKE row highlight */
.kpi-row[data-desc="STROKE"] {
  background: #f5f3ff;
  border-bottom: 1px solid #ddd6fe;
}
.kpi-row[data-desc="STROKE"] .kpi-label { color: #7c3aed; }

/* Danger row (high repair/reject/downtime) */
.kpi-row-danger {
  background: #fef2f2 !important;
  border-left: 3px solid #ef4444;
}
.kpi-row-danger .kpi-label { color: #dc2626 !important; font-weight: 900 !important; }
.kpi-row-danger .kpi-value { color: #dc2626 !important; font-weight: 900 !important; }

/* Detail toggle button */
.detail-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 12px;
  background: #f8fafc;
  border-top: 1px solid #e5e7eb;
  border: none;
  cursor: pointer;
  font-size: 0.7rem;
  font-weight: 800;
  color: #64748b;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  transition: all 0.15s;
  width: 100%;
}
.detail-toggle:hover { background: #eff6ff; color: #3b82f6; }
.detail-toggle .detail-arrow {
  display: inline-block;
  transition: transform 0.25s;
  font-size: 0.6rem;
}
.detail-toggle.open .detail-arrow { transform: rotate(180deg); }

/* Detail panel dropdown */
.detail-panel {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.35s ease-in-out;
  background: #fafbfc;
  border-top: 1px solid #e5e7eb;
}
.detail-panel.open {
  max-height: 600px;
  overflow-y: auto;
}
.detail-panel-inner {
  padding: 8px;
}
.detail-panel .det-empty { padding: 20px 12px; }

/* ── BLINK ANIMATIONS ──────────────────────────────────────── */
@keyframes blink-red {
  0%, 100% { background: #ef4444 !important; color: #fff !important; }
  50% { background: #fff !important; color: #ef4444 !important; }
}
@keyframes blink-yellow {
  0%, 100% { background: #eab308 !important; color: #000 !important; }
  50% { background: #fff !important; color: #eab308 !important; }
}
@keyframes blink-green {
  0%, 100% { background: #22c55e !important; color: #fff !important; }
  50% { background: #fff !important; color: #22c55e !important; }
}

.kpi-row.blink-red {
  animation: blink-red 0.8s ease-in-out infinite;
  border-radius: 4px;
  margin: 0 4px;
  padding: 7px 8px;
}
.kpi-row.blink-yellow {
  animation: blink-yellow 1.2s ease-in-out infinite;
  border-radius: 4px;
  margin: 0 4px;
  padding: 7px 8px;
}
.kpi-row.blink-green {
  animation: blink-green 0.6s ease-in-out 3;
  border-radius: 4px;
  margin: 0 4px;
  padding: 7px 8px;
}
</style>

@endsection

@section('scripts')
{{-- PART A: fetch data ASAP (starts before Chart.js loads) --}}
<script>
const LINES = @json($lines);
const SELECTED_LINE = @json($selectedLine);

let selectedShift = 1;
let selectedDays  = 1;
let charts = {};
let LINE_KPI = {};
let LINE_META = {};
let DETAIL_DATA = {};
let LAST_KPI_HASH = '';
let LAST_DETAIL_HASH = '';

// Incremental render cache
let CARDS_CACHED = false;
let CELL_CACHE = {};
let LAST_DETAIL_RENDER_HASH = '';

function setText(el, v) {
  if (el && el.textContent !== v) el.textContent = v;
}

/**
 * FUNGSI UTAMA PENARIKAN DATA (REAL-TIME API)
 * Fungsi ini memanggil endpoint backend untuk mendapatkan data Qty, Downtime, 
 * dan rincian kualitas secara asli dari database.
 */
let LINE_DETAIL = {};

async function fetchDetailData() {
    const date = document.getElementById('dateInput').value;
    const shift = selectedShift;
    try {
        let url = `{{ route('supervisor.dashboard.detail') }}?date=${date}&shift=${shift}`;
        if(SELECTED_LINE) url += `&line=${SELECTED_LINE}`;
        const response = await fetch(url);
        const data = await response.json();
        const newDetailHash = JSON.stringify(data.detail);
        if (newDetailHash === LAST_DETAIL_HASH) return;
        LAST_DETAIL_HASH = newDetailHash;
        LINE_DETAIL = data.detail || {};
        renderLineCards();
    } catch (error) {
        console.error("Error fetching detail data:", error);
    }
}

async function fetchDashboardData() {
    const date = document.getElementById('dateInput').value;
    const shift = selectedShift;
    
    try {
        let url = `{{ route('supervisor.dashboard.api') }}?date=${date}&shift=${shift}`;
        if(SELECTED_LINE) url += `&line=${SELECTED_LINE}`;

        const response = await fetch(url);
        const data = await response.json();
        
        const newHash = JSON.stringify(data.line_kpi);
        const detailChanged = newHash !== LAST_KPI_HASH;
        LAST_KPI_HASH = newHash;
        
        LINE_KPI = data.line_kpi;
        LINE_META = data.line_meta || {};
        DETAIL_DATA = data.detail_data;
        
        if (detailChanged) {
            renderLineCards();
            if(selectedDays === 1 && typeof renderTodayCharts === 'function') renderTodayCharts();
        }
        fetchDetailData();
    } catch (error) {
        console.error("Error fetching dashboard data:", error);
    }
}



// Menjalankan penarikan data pertama kali saat halaman dibuka
fetchDashboardData();
setTimeout(fetchDetailData, 100);

// Real-time via BroadcastChannel (instant from Input Harian saves)
try {
    const statusChan = new BroadcastChannel('line_status');
    statusChan.onmessage = (e) => {
        if (e.data.type === 'status-changed') fetchDashboardData();
    };
} catch (e) { /* fallback to polling */ }

// AUTO-REFRESH: Mengupdate data dashboard setiap 5 detik + BroadcastChannel dari Input Harian
setInterval(fetchDashboardData, 5000);

function pad(n){ return String(n).padStart(2,'0'); }

function updateClock(){
  const now = new Date();
  document.getElementById('liveClock').textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
  const opts = {day:'2-digit', month:'2-digit', year:'numeric'};
  document.getElementById('hdrDate').textContent = now.toLocaleDateString('id-ID', opts);
}
setInterval(updateClock, 1000);
updateClock();

(function(){
  const now = new Date();
  const today = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
  let saved;
  try { saved = localStorage.getItem('dash_filter_date'); } catch(e) {}
  document.getElementById('dateInput').value = saved || today;
})();

function setShift(s){
  selectedShift = s;
  
  const s1 = document.getElementById('s1btn');
  const s2 = document.getElementById('s2btn');
  
  if(s === 1){
      s1.className = "px-4 py-1.5 text-xs sm:text-sm font-bold rounded-lg transition-all text-white bg-red-500 shadow-sm";
      s2.className = "px-4 py-1.5 text-xs sm:text-sm font-bold rounded-lg transition-all text-gray-500 hover:text-gray-700";
  } else {
      s2.className = "px-4 py-1.5 text-xs sm:text-sm font-bold rounded-lg transition-all text-white bg-red-500 shadow-sm";
      s1.className = "px-4 py-1.5 text-xs sm:text-sm font-bold rounded-lg transition-all text-gray-500 hover:text-gray-700";
  }
  
  flushCardCache();
  fetchDashboardData();
}

function onDateChange(){
  const val = document.getElementById('dateInput').value;
  try { localStorage.setItem('dash_filter_date', val); } catch(e) {}
  flushCardCache();
  fetchDashboardData();
}

function setDays(d){
  selectedDays = d;
  
  [1,7,30].forEach(x => {
    const btn = document.getElementById('d'+x+'btn');
    if(x === d){
        btn.className = "px-6 py-2 text-sm font-bold rounded-lg transition-all text-white bg-red-500 shadow-sm";
    } else {
        btn.className = "px-6 py-2 text-sm font-bold rounded-lg transition-all text-gray-500 hover:text-gray-700";
    }
  });
  
  if(d === 1){
      document.getElementById('todayCharts').classList.remove('hidden');
      document.getElementById('todayCharts').classList.add('grid');
      document.getElementById('trendCharts').classList.add('hidden');
      if(typeof renderTodayCharts === 'function') renderTodayCharts();
  } else {
      document.getElementById('todayCharts').classList.add('hidden');
      document.getElementById('todayCharts').classList.remove('grid');
      document.getElementById('trendCharts').classList.remove('hidden');
      document.getElementById('trendLabel').textContent = `Grafik Tren ${d} Hari — Semua Line`;
      if(typeof renderTrendCharts === 'function') renderTrendCharts(d);
  }
}

function dCell(v, cls){ return `<td class="px-2 py-1.5 text-center text-[10px] sm:text-xs ${cls || 'text-gray-500'}">${v}</td>`; }

function detailCells(r){
  const chk = c => c ? '<span class="inline-flex items-center justify-center w-4 h-4 sm:w-5 sm:h-5 rounded border-2 border-green-500 bg-green-50 text-green-600 text-[10px] sm:text-xs font-black leading-none">&#10003;</span>' : '<span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded bg-gray-100 text-gray-400 text-[10px] sm:text-xs font-semibold">-</span>';
  const pt = r.press_time > 0 ? r.press_time + ' m' : '-';
  const dn = r.dandori > 0 ? r.dandori + ' m' : '-';
  const iq = r.iq_check > 0 ? r.iq_check + ' m' : '-';
  const dt = r.downtime > 0 ? r.downtime + ' m' : '-';
  const tp = r.tpt > 0 ? r.tpt + ' m' : '-';
  return dCell(r.no) +
    dCell(r.job_number, 'text-left font-semibold text-gray-800 whitespace-nowrap') +
    dCell(chk(r.p1)) + dCell(chk(r.p2)) + dCell(chk(r.p3)) + dCell(chk(r.p4)) +
    dCell(r.plan_qty, 'text-gray-700') +
    dCell(r.good, 'text-green-600 font-semibold') +
    dCell(r.repair, 'text-amber-600 font-semibold') +
    dCell(r.reject, 'text-red-600 font-semibold') +
    dCell(pt) + dCell(dn) + dCell(iq) + dCell(dt) +
    dCell(tp, 'text-blue-600 font-bold') +
    dCell(r.plan_finish) + dCell(r.actual_finish);
}

function cellClass(desc, actual, actPct){
    if(desc==='GSPH'){const p=parseFloat(actPct||actual);return p>=100?'blink-green':p>=80?'blink-yellow':'blink-red';}
    if(desc==='REPAIR'||desc==='REJECT'){const p=parseFloat(actPct||actual);return p>5?'blink-red':p>2?'blink-yellow':'';}
    if(['DT','TOTAL_DT','MACH_T','MAT_T','LOG_T','DIES_T'].includes(desc)){const v=parseFloat(actual);return v>30?'blink-red':v>15?'blink-yellow':'';}
    return '';
}

var prevBlinkClass = {};

function toggleDetail(safeLine){
  const panel = document.getElementById('detail-panel-' + safeLine);
  const btn = document.getElementById('detail-toggle-' + safeLine);
  if (!panel || !btn) return;
  const isOpen = panel.classList.contains('open');
  if (isOpen) {
    panel.classList.remove('open');
    btn.classList.remove('open');
    btn.innerHTML = '<span class="detail-arrow">&#9660;</span> Detail';
  } else {
    panel.classList.add('open');
    btn.classList.add('open');
    btn.innerHTML = '<span class="detail-arrow">&#9650;</span> Detail';
  }
}

const MAIN_KPIS = ['QTY','GSPH','REPAIR','REJECT','DT','TOTAL_DT'];
const EXTRA_KPIS = ['PROD_T','MACH_T','DIES_T','MAT_T','LOG_T','OVERTIME'];

function buildLineCard(line){
  const rows = LINE_KPI[line] || [];
  const meta = LINE_META[line] || {};
  const jobLabel = meta.job || '-';
  const jobActual = meta.jobActual || '0/0';
  const strokeVal = meta.stroke || '0';
  const currStrokeVal = meta.currStroke || '-';
  const detailRows = LINE_DETAIL[line] || [];
  const safeLine = line.replace(/[^a-zA-Z0-9]/g,'_');

  let kpiHtml = '';

  kpiHtml += `<div class="kpi-row" data-line="${line}" data-desc="JOB">
    <span class="kpi-label">JOB</span>
    <span class="kpi-value">${jobActual} <span class="kpi-pct">${jobLabel !== '-' ? jobLabel : ''}</span></span>
  </div>`;

  const mainRows = rows.filter(k => MAIN_KPIS.includes(k.desc));
  mainRows.forEach((kpi) => {
    const isClickable = kpi.popup || kpi.actualLink;
    let valueHtml = '';
    if (isClickable && (kpi.desc === 'REPAIR' || kpi.desc === 'REJECT')) {
      valueHtml = `<span class="kpi-val-main">${kpi.actual}</span><span class="kpi-pct">(${kpi.actualPct || ''})</span>`;
    } else if (isClickable) {
      valueHtml = `<span class="kpi-val-main">${kpi.actual}</span><span class="kpi-pct">${kpi.currentPct ? '(' + kpi.currentPct + ')' : ''}</span>`;
    } else if(kpi.desc === 'GSPH'){
      valueHtml = `<span>${kpi.actual}</span><span class="kpi-pct">(${kpi.actualPct || ''})</span>`;
    } else if(kpi.desc === 'REPAIR' || kpi.desc === 'REJECT'){
      valueHtml = `<span>${kpi.actual}</span><span class="kpi-pct">(${kpi.actualPct || ''})</span>`;
    } else if(kpi.desc === 'DT' || kpi.desc === 'TOTAL_DT'){
      valueHtml = `<span>${kpi.actual}m</span>`;
    } else {
      valueHtml = `<span>${kpi.actual}</span><span class="kpi-pct">${kpi.currentPct ? '(' + kpi.currentPct + ')' : ''}</span>`;
    }
    const dangerCls = kpi.danger ? ' kpi-row-danger' : '';
    const clickCls = isClickable ? ' kpi-row-clickable' : '';
    const clickAttr = isClickable ? ` onclick="openKpiDetailModal('${kpi.desc}','${line}')"` : '';
    const label = kpi.desc === 'DT' ? 'DIES TROUBLE' : kpi.desc;
    kpiHtml += `<div class="kpi-row${dangerCls}${clickCls}" data-line="${line}" data-desc="${kpi.desc}" id="kpi-${kpi.desc}-${safeLine}"${clickAttr}>
      <span class="kpi-label">${label}</span>
      <span class="kpi-value">${valueHtml}</span>
    </div>`;
  });

  const strokeDisplay = currStrokeVal === '-' ? '-' : Number(currStrokeVal || 0).toLocaleString('id-ID') + ' / ' + Number(strokeVal).toLocaleString('id-ID');
  kpiHtml += `<div class="kpi-row" data-line="${line}" data-desc="STROKE" id="kpi-STROKE-${safeLine}">
    <span class="kpi-label">STROKE</span>
    <span class="kpi-value">${strokeDisplay}</span>
  </div>`;

  const hasDetail = detailRows.length > 0;
  const rowCount = detailRows.length;
  let detRows = '';
  detailRows.forEach((r) => {
    detRows += `<tr>${detailCells(r)}</tr>`;
  });

  let extraKpiHtml = '';
  const extraRows = rows.filter(k => EXTRA_KPIS.includes(k.desc));
  if (extraRows.length > 0) {
    extraRows.forEach((kpi) => {
      const isClickable = kpi.popup || kpi.actualLink;
      let valueHtml = '';
      if (isClickable) {
        valueHtml = `<span class="kpi-val-main">${kpi.actual}</span><span class="kpi-pct">${kpi.currentPct ? '(' + kpi.currentPct + ')' : ''}</span>`;
      } else {
        valueHtml = `<span>${kpi.actual}</span>`;
      }
      const dangerCls = kpi.danger ? ' kpi-row-danger' : '';
      const clickCls = isClickable ? ' kpi-row-clickable' : '';
      const clickAttr = isClickable ? ` onclick="openKpiDetailModal('${kpi.desc}','${line}')"` : '';
      extraKpiHtml += `<div class="kpi-row${dangerCls}${clickCls}" data-line="${line}" data-desc="${kpi.desc}"${clickAttr}>
        <span class="kpi-label">${kpi.desc}</span>
        <span class="kpi-value">${valueHtml}</span>
      </div>`;
    });
  }

  const detailTableHtml = hasDetail ? `
    <div class="det-scroll">
      <table class="det-table">
        <thead>
          <tr>
            <th style="text-align:center">No</th>
            <th style="text-align:left">Job No</th>
            <th style="text-align:center">P1</th>
            <th style="text-align:center">P2</th>
            <th style="text-align:center">P3</th>
            <th style="text-align:center">P4</th>
            <th style="text-align:center">Plan Qty</th>
            <th style="text-align:center;color:#16a34a">Good</th>
            <th style="text-align:center;color:#d97706">Rep</th>
            <th style="text-align:center;color:#dc2626">Rej</th>
            <th style="text-align:center">Press Time</th>
            <th style="text-align:center">Dandori</th>
            <th style="text-align:center">IQ Check</th>
            <th style="text-align:center">Downtime</th>
            <th style="text-align:center;color:#2563eb">TPT</th>
            <th style="text-align:center">Plan Finish</th>
            <th style="text-align:center">Act Finish</th>
          </tr>
        </thead>
        <tbody>${detRows}</tbody>
      </table>
    </div>` : `
    <div class="det-empty">
      <svg fill="none" viewBox="0 0 24 24" stroke="#d1d5db" style="width:28px;height:28px;opacity:0.5">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
      </svg>
      <span>Belum ada data produksi</span>
    </div>`;

  return `<div class="press-card" id="card-${safeLine}">
    <div class="press-card-header">${line}</div>
    <div class="press-card-body">${kpiHtml}</div>
    <button class="detail-toggle" id="detail-toggle-${safeLine}" onclick="toggleDetail('${safeLine}')">
      <span class="detail-arrow">&#9660;</span> Detail
    </button>
    <div class="detail-panel" id="detail-panel-${safeLine}">
      <div class="detail-panel-inner">
        ${extraKpiHtml ? `
        <div class="det-section-label">
          <svg width="13" height="13" viewBox="0 0 20 20" fill="none" style="flex-shrink:0">
            <circle cx="10" cy="10" r="8" stroke="#94a3b8" stroke-width="2"/>
            <path d="M10 6v4l3 2" stroke="#94a3b8" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
          <span class="label-text">Downtime Detail</span>
        </div>
        ${extraKpiHtml}` : ''}
        <div class="det-section-label">
          <svg width="13" height="13" viewBox="0 0 20 20" fill="none" style="flex-shrink:0">
            <rect x="2" y="4" width="16" height="12" rx="2" stroke="#94a3b8" stroke-width="2"/>
            <path d="M2 8h16" stroke="#94a3b8" stroke-width="1.5"/>
            <path d="M7 4v12M13 4v12" stroke="#94a3b8" stroke-width="1" stroke-dasharray="2 2"/>
          </svg>
          <span class="label-text">Detail Produksi</span>
          <span class="label-badge ${hasDetail ? '' : 'zero'}">${hasDetail ? rowCount + ' Job' : 'Belum Ada Data'}</span>
        </div>
        ${detailTableHtml}
      </div>
    </div>
  </div>`;
}

function renderLineCards(forceDetail){
  const grid = document.getElementById('linesGrid');
  if (!CARDS_CACHED) {
    grid.innerHTML = LINES.map(buildLineCard).join('');
    CARDS_CACHED = true;
    cacheCards();
    return;
  }
  updateCards(forceDetail);
}

function cacheCards() {
  CELL_CACHE = {};
  LINES.forEach(line => {
    const safeLine = line.replace(/[^a-zA-Z0-9]/g,'_');
    const card = document.getElementById('card-' + safeLine);
    if (!card) return;
    CELL_CACHE[line] = { el: card };

    card.querySelectorAll('.kpi-row').forEach(row => {
      const desc = row.getAttribute('data-desc');
      if (!desc) return;
      CELL_CACHE[`${line}-${desc}-row`] = row;
      CELL_CACHE[`${line}-${desc}-value`] = row.querySelector('.kpi-value');
    });
  });
}

function updateCards(forceDetail) {
  if (forceDetail || LAST_DETAIL_HASH !== LAST_DETAIL_RENDER_HASH) {
    LAST_DETAIL_RENDER_HASH = LAST_DETAIL_HASH;
    flushCardCache();
    document.getElementById('linesGrid').innerHTML = LINES.map(buildLineCard).join('');
    CARDS_CACHED = true;
    cacheCards();
    return;
  }

  LINES.forEach(line => {
    const rows = LINE_KPI[line] || [];
    const meta = LINE_META[line] || {};
    const safeLine = line.replace(/[^a-zA-Z0-9]/g,'_');

    const jobValEl = CELL_CACHE[`${line}-JOB-value`];
    if (jobValEl) {
      const h = `${meta.jobActual || '0/0'} <span class="kpi-pct">${meta.job && meta.job !== '-' ? meta.job : ''}</span>`;
      if (jobValEl.innerHTML !== h) jobValEl.innerHTML = h;
    }

    const strokeValEl = CELL_CACHE[`${line}-STROKE-value`];
    if (strokeValEl) {
      const st = meta.stroke || '0';
      const cs = meta.currStroke || '-';
      const h = cs === '-' ? '-' : Number(cs || 0).toLocaleString('id-ID') + ' / ' + Number(st).toLocaleString('id-ID');
      if (strokeValEl.textContent !== h) strokeValEl.textContent = h;
    }

    rows.forEach(kpi => {
      const rowEl = CELL_CACHE[`${line}-${kpi.desc}-row`];
      const valEl = CELL_CACHE[`${line}-${kpi.desc}-value`];
      if (!rowEl || !valEl) return;

      const isClickable = kpi.popup || kpi.actualLink;
      let valueHtml = '';
      if (isClickable && (kpi.desc === 'REPAIR' || kpi.desc === 'REJECT')) {
        valueHtml = `<span class="kpi-val-main">${kpi.actual}</span><span class="kpi-pct">(${kpi.actualPct || ''})</span>`;
      } else if (isClickable) {
        valueHtml = `<span class="kpi-val-main">${kpi.actual}</span><span class="kpi-pct">${kpi.currentPct ? '(' + kpi.currentPct + ')' : ''}</span>`;
      } else if(kpi.desc === 'GSPH'){
        valueHtml = `<span>${kpi.actual}</span><span class="kpi-pct">(${kpi.actualPct || ''})</span>`;
      } else if(kpi.desc === 'DT' || kpi.desc === 'TOTAL_DT'){
        valueHtml = `<span>${kpi.actual}m</span>`;
      } else {
        valueHtml = `<span>${kpi.actual}</span><span class="kpi-pct">${kpi.currentPct ? '(' + kpi.currentPct + ')' : ''}</span>`;
      }
      if (valEl.innerHTML !== valueHtml) valEl.innerHTML = valueHtml;

      if (isClickable) {
        rowEl.classList.add('kpi-row-clickable');
        if (!rowEl.getAttribute('onclick')) {
          rowEl.setAttribute('onclick', `openKpiDetailModal('${kpi.desc}','${line}')`);
        }
      }

      if (kpi.danger) {
        rowEl.classList.add('kpi-row-danger');
      } else {
        rowEl.classList.remove('kpi-row-danger');
      }

      const newBlink = cellClass(kpi.desc, kpi.actual, kpi.actualPct);
      const prevBlink = prevBlinkClass[`${line}-${kpi.desc}`] || '';

      if (prevBlink && prevBlink.includes('blink') && !newBlink.includes('blink')) {
        rowEl.classList.remove('blink-red', 'blink-yellow', 'blink-green');
        rowEl.classList.add('blink-green');
        setTimeout(() => {
          rowEl.classList.remove('blink-green');
        }, 1800);
      } else {
        rowEl.classList.remove('blink-red', 'blink-yellow', 'blink-green');
        if (newBlink) rowEl.classList.add(newBlink);
      }
      prevBlinkClass[`${line}-${kpi.desc}`] = newBlink;
    });
  });
}

function flushCardCache() {
  CARDS_CACHED = false;
  CELL_CACHE = {};
  LAST_DETAIL_RENDER_HASH = '';
  prevBlinkClass = {};
}

function openKpiDetailModal(type, line){
  console.log(`Attempting to open modal: Type=${type}, Line=${line}`);
  const backdrop = document.getElementById('modalBackdrop');
  const dialog = document.getElementById('modalDialog');
  const body = document.getElementById('modalBody');
  
  document.getElementById('modalTitle').textContent = `Rincian ${type} — ${line}`;
  body.innerHTML = `
    <div class="flex flex-col items-center justify-center py-10 gap-3 text-gray-500">
        <svg class="animate-spin h-8 w-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span class="text-sm font-semibold">Memuat rincian...</span>
    </div>`;
  
  backdrop.classList.remove('hidden');
  backdrop.classList.add('flex');
  setTimeout(() => { dialog.classList.add('scale-100', 'opacity-100'); dialog.classList.remove('scale-95', 'opacity-0'); }, 10);

  try {
    const typeData = DETAIL_DATA[type];
    if(!typeData) { 
        console.warn(`No data found for type: ${type}`);
        showKpiModalEmpty(); return; 
    }

    let html = '';

    if(typeData.type === 'production'){
      const lineData = typeData[line];
      if(!lineData || !lineData.rows || lineData.rows.length === 0) { showKpiModalEmpty(); return; }

      html = `<div class="overflow-x-auto rounded-xl border border-gray-200"><table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600"><tr>
          <th class="px-4 py-3 text-center border-b border-gray-200 w-12">No</th>
          <th class="px-4 py-3 text-left border-b border-gray-200">Item</th>
          <th class="px-4 py-3 text-center border-b border-gray-200 w-24">OK Qty</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
        ${lineData.rows.map((r, i) => `<tr class="hover:bg-gray-50">
          <td class="px-4 py-3 text-center text-gray-500">${i + 1}</td>
          <td class="px-4 py-3 font-semibold text-gray-800">${r.item}</td>
          <td class="px-4 py-3 text-center text-green-600 font-black">${r.ok}</td>
        </tr>`).join('')}
        <tr class="bg-gray-50">
          <td colspan="2" class="px-4 py-3 text-right font-bold text-gray-700">TOTAL</td>
          <td class="px-4 py-3 text-center font-black text-gray-900">${lineData.total}</td>
        </tr>
        </tbody></table></div>`;

    } else {
      const lineData = typeData[line];
      if(!lineData || !lineData.rows || (lineData.rows.length === 0 && type !== 'PROD_T')) { 
          console.warn(`No rows found for line: ${line} in type: ${type}`);
          showKpiModalEmpty(); return; 
      }

      if(typeData.type === 'quality'){
        html = `<div class="overflow-x-auto rounded-xl border border-gray-200"><table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-600"><tr>
            <th class="px-4 py-3 text-center border-b border-gray-200 w-12">No</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Item</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Problem</th>
            <th class="px-4 py-3 text-center border-b border-gray-200 w-20">Qty</th>
          </tr></thead>
          <tbody class="divide-y divide-gray-100">
          ${lineData.rows.map(r=>`<tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-center text-gray-500">${r.no}</td>
            <td class="px-4 py-3 font-semibold text-gray-800">${r.item}</td>
            <td class="px-4 py-3 text-gray-600">${r.problem}</td>
            <td class="px-4 py-3 text-center text-red-600 font-black">${r.qty}</td>
          </tr>`).join('')}
          <tr class="bg-gray-50">
            <td colspan="3" class="px-4 py-3 text-right font-bold text-gray-700">TOTAL</td>
            <td class="px-4 py-3 text-center font-black text-gray-900">${lineData.total}</td>
          </tr>
          </tbody></table></div>`;

      } else if(typeData.type === 'dt_summary'){
        html = `<div class="overflow-x-auto rounded-xl border border-gray-200"><table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-600"><tr>
            <th class="px-4 py-3 text-center border-b border-gray-200">No</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Jenis</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Job</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Problem (Alasan)</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Penyebab</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Action</th>
            <th class="px-4 py-3 text-right border-b border-gray-200">Durasi</th>
          </tr></thead>
          <tbody class="divide-y divide-gray-100">
          ${lineData.rows.map(r=>`<tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-center text-gray-500">${r.no}</td>
            <td class="px-4 py-3 font-semibold text-gray-800">${r.jenis}</td>
            <td class="px-4 py-3 font-semibold text-blue-700">${r.job || '-'}</td>
            <td class="px-4 py-3 text-gray-600">${r.problem}</td>
            <td class="px-4 py-3 text-gray-600">${r.penyebab}</td>
            <td class="px-4 py-3 text-gray-600">${r.action || '-'}</td>
            <td class="px-4 py-3 text-right font-bold text-gray-700">${r.durasi} m</td>
          </tr>`).join('')}
          <tr class="bg-gray-50 font-black">
            <td colspan="6" class="px-4 py-3 text-right text-gray-700">TOTAL DOWNTIME</td>
            <td class="px-4 py-3 text-right text-red-600">${lineData.total} m</td>
          </tr>
          </tbody></table></div>`;

      } else if(typeData.type === 'runtime'){
        html = `<div class="overflow-x-auto rounded-xl border border-gray-200"><table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-600"><tr>
            <th class="px-4 py-3 text-center border-b border-gray-200 w-12">No</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Job</th>
            <th class="px-4 py-3 text-center border-b border-gray-200 w-24">Runtime</th>
          </tr></thead>
          <tbody class="divide-y divide-gray-100">
          ${lineData.rows.map(r=>`<tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-center text-gray-500">${r.no}</td>
            <td class="px-4 py-3 font-semibold text-gray-800">${r.item}</td>
            <td class="px-4 py-3 text-center text-blue-600 font-black">${r.durasi}</td>
          </tr>`).join('')}
          <tr class="bg-gray-50">
            <td colspan="2" class="px-4 py-3 text-right font-bold text-gray-700">TOTAL</td>
            <td class="px-4 py-3 text-center font-black text-gray-900">${lineData.total}</td>
          </tr>
          </tbody></table></div>`;

      } else if(typeData.type === 'idle_detail'){
        html = `<div class="overflow-x-auto rounded-xl border border-gray-200"><table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-600"><tr>
            <th class="px-4 py-3 text-center border-b border-gray-200 w-12">No</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Item / Job</th>
            <th class="px-4 py-3 text-center border-b border-gray-200">Idle Start</th>
            <th class="px-4 py-3 text-center border-b border-gray-200">Idle End</th>
            <th class="px-4 py-3 text-right border-b border-gray-200">Durasi</th>
          </tr></thead>
          <tbody class="divide-y divide-gray-100">
          ${lineData.rows.map(r=>`<tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-center text-gray-500">${r.no}</td>
            <td class="px-4 py-3 font-semibold text-gray-800">${r.item}</td>
            <td class="px-4 py-3 text-center text-gray-600">${r.start}</td>
            <td class="px-4 py-3 text-center text-gray-600">${r.end}</td>
            <td class="px-4 py-3 text-right font-bold text-gray-700">${r.durasi} m</td>
          </tr>`).join('')}
          <tr class="bg-gray-50">
            <td colspan="4" class="px-4 py-3 text-right font-bold text-gray-700">TOTAL IDLE</td>
            <td class="px-4 py-3 text-right font-black text-red-600">${lineData.total} m</td>
          </tr>
          </tbody></table></div>`;

      } else {
        // dt_detail
        html = `<div class="overflow-x-auto rounded-xl border border-gray-200"><table class="w-full text-sm">
          <thead class="bg-gray-50 text-gray-600"><tr>
            <th class="px-4 py-3 text-center border-b border-gray-200">No</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Item</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Problem</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Penyebab</th>
            <th class="px-4 py-3 text-left border-b border-gray-200">Action</th>
            <th class="px-4 py-3 text-right border-b border-gray-200">Durasi</th>
          </tr></thead>
          <tbody class="divide-y divide-gray-100">
          ${lineData.rows.map(r=>`<tr class="hover:bg-gray-50">
            <td class="px-4 py-3 text-center text-gray-500">${r.no}</td>
            <td class="px-4 py-3 font-semibold text-gray-800">${r.item}</td>
            <td class="px-4 py-3 text-gray-600">${r.problem}</td>
            <td class="px-4 py-3 text-gray-600">${r.penyebab}</td>
            <td class="px-4 py-3 text-gray-600">${r.action}</td>
            <td class="px-4 py-3 text-right font-bold text-gray-700">${r.durasi} m</td>
          </tr>`).join('')}
          <tr class="bg-gray-50">
            <td colspan="5" class="px-4 py-3 text-right font-bold text-gray-700">TOTAL</td>
            <td class="px-4 py-3 text-right font-black text-red-600">${lineData.total} m</td>
          </tr>
          </tbody></table></div>`;
      }
    }

    body.innerHTML = html;
    } catch (e) {
    console.error("Error in openModal:", e);
    body.innerHTML = `<div class="text-center py-10 text-red-500 font-medium">Error: ${e.message}</div>`;
  }
}

function showKpiModalEmpty(){
  document.getElementById('modalBody').innerHTML =
    '<div class="text-center py-10 text-gray-400 font-medium flex flex-col items-center gap-2">' +
    '<svg class="w-12 h-12 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' +
    '<span>Tidak ada rincian data untuk saat ini.</span>' +
    '</div>';
}

function closeKpiDetailModal(){
  const backdrop = document.getElementById('modalBackdrop');
  const dialog = document.getElementById('modalDialog');
  
  dialog.classList.remove('scale-100', 'opacity-100');
  dialog.classList.add('scale-95', 'opacity-0');
  
  setTimeout(() => {
      backdrop.classList.add('hidden');
      backdrop.classList.remove('flex');
  }, 200);
}
function onBackdropClick(e){
  if(e.target === document.getElementById('modalBackdrop')) closeModal();
}
</script>

{{-- PART B: Chart.js (loads after Part A, so API call starts first) --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
Chart.register(ChartZoom);
if(typeof ChartDataLabels !== 'undefined'){ Chart.register(ChartDataLabels); }

function resetChart(id){
  if(!charts[id]) return;
  charts[id].resetZoom();
  if(id === 'cGsph' || id === 'cTGsph'){
    charts[id].options.scales.x.beginAtZero = true;
    charts[id].options.scales.x.min = undefined;
    charts[id].options.scales.x.max = undefined;
    charts[id].update();
  }
}

function renderGsphChart(id, labels, plan, actual){
  const posActual = actual.filter(v => v > 0);
  const minVal = posActual.length > 0 ? Math.min(...posActual) : 0;
  const maxPlan = Math.max(...plan);
  const isSmall = minVal > 0 && maxPlan > 0 && (maxPlan / minVal) > 5;

  const opts = baseOpts('GSPH');
  opts.indexAxis = 'y';
  opts.scales.y.beginAtZero = true;
  opts.scales.y.grid.display = false;
  opts.scales.x.beginAtZero = !isSmall;
  if(isSmall){
    opts.scales.x.min = Math.max(0, minVal * 0.8);
    opts.scales.x.max = maxPlan * 1.1;
  }
  opts.scales.x.title = { display: true, text: 'GSPH', color: '#9ca3af', font:{size: 13, weight: 'bold'} };
  opts.plugins.tooltip = {
    callbacks: {
      label: function(ctx){
        if(ctx.datasetIndex === 0) return 'Plan: ' + ctx.parsed.x.toLocaleString();
        var planVal = ctx.chart.data.datasets[0].data[ctx.dataIndex];
        var pct = planVal > 0 ? ((ctx.parsed.x / planVal) * 100).toFixed(2) + '%' : '-';
        return 'Actual: ' + ctx.parsed.x.toLocaleString() + ' (' + pct + ')';
      }
    }
  };
  opts.plugins.datalabels = {
    display: function(ctx){ return ctx.datasetIndex === 1; },
    anchor: 'end',
    align: 'end',
    color: '#374151',
    font: { weight: 'bold', size: 11 },
    formatter: function(val){
      if(val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
      if(val >= 1000) return (val / 1000).toFixed(1) + 'K';
      return val.toLocaleString();
    }
  };

  const chart = new Chart(document.getElementById(id), {
    type:'bar',
    data:{ labels, datasets:[
      { label:'Plan',   data: plan,   backgroundColor:'#e5e7eb', borderRadius: 4, minBarLength: 10 },
      { label:'Actual', data: actual, backgroundColor:'#3b82f6', borderRadius: 4, minBarLength: 10 }
    ]},
    options: opts
  });
  chart.__gsphData = { plan, actual };
  return chart;
}

function fitGsphChart(id){
  const chart = charts[id];
  if(!chart || !chart.__gsphData) return;
  const { plan, actual } = chart.__gsphData;
  const posActual = actual.filter(v => v > 0);
  if(posActual.length === 0) return;
  const minVal = Math.min(...posActual);
  const maxVal = Math.max(...plan);
  if(maxVal <= 0) return;
  chart.options.scales.x.beginAtZero = false;
  chart.options.scales.x.min = Math.max(0, minVal * 0.8);
  chart.options.scales.x.max = maxVal * 1.1;
  chart.resetZoom();
  chart.update();
}

function destroyChart(id){
  if(charts[id]){ charts[id].destroy(); delete charts[id]; }
}

const CHART_TEXT  = '#6b7280';
const CHART_GRID  = '#f3f4f6';
const FONT_SZ     = 13;

function baseOpts(yLabel){
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { labels: { color: CHART_TEXT, font:{size: FONT_SZ, family: "'Inter', sans-serif"} } },
        zoom: {
          pan: { enabled: true, mode: 'xy', modifierKey: 'shift' },
          zoom: { wheel: { enabled: false }, pinch: { enabled: true }, drag: { enabled: true, backgroundColor: 'rgba(59,130,246,0.08)', borderColor: '#3b82f6', borderWidth: 1 } },
          limits: { x: { minRange: 0.5 }, y: { minRange: 0.5 } }
        }
    },
    scales: {
      x: {
        ticks: { color: CHART_TEXT, font:{size: FONT_SZ} },
        grid:  { color: CHART_GRID }
      },
      y: {
        beginAtZero: true,
        ticks: { color: CHART_TEXT, font:{size: FONT_SZ} },
        grid:  { color: CHART_GRID },
        title: { display: true, text: yLabel, color: '#9ca3af', font:{size: FONT_SZ, weight: 'bold'} }
      }
    }
  };
}

function updChart(id, datasets) {
  const c = charts[id];
  if (!c) return;
  datasets.forEach((ds, i) => {
    if (c.data.datasets[i]) c.data.datasets[i].data = ds.data;
  });
  c.update('none');
}

function renderTodayCharts(){
  const labels_src = Object.keys(LINE_KPI);
  if(labels_src.length === 0) return;
  const labels     = labels_src;
  const qty_plan   = labels_src.map(l => parseInt((LINE_KPI[l]?.find(r=>r.desc==='QTY')||{}).plan||'0'));
  const qty_actual = labels_src.map(l => parseInt((LINE_KPI[l]?.find(r=>r.desc==='QTY')||{}).actual||'0'));
  const downtime   = labels_src.map(l => parseFloat((LINE_KPI[l]?.find(r=>r.desc==='TOTAL_DT')||{}).actual||'0'));
  const repair     = labels_src.map(l => parseInt((LINE_KPI[l]?.find(r=>r.desc==='REPAIR')||{}).actual||'0'));
  const reject     = labels_src.map(l => parseInt((LINE_KPI[l]?.find(r=>r.desc==='REJECT')||{}).actual||'0'));
  const gsph_plan  = labels_src.map(l => parseFloat((LINE_KPI[l]?.find(r=>r.desc==='GSPH')||{}).plan||'0'));
  const gsph_act   = labels_src.map(l => parseFloat((LINE_KPI[l]?.find(r=>r.desc==='GSPH')||{}).actual||'0'));

  if (charts['cQty']) {
    updChart('cQty', [
      { data: qty_plan },
      { data: qty_actual }
    ]);
    updChart('cDt', [
      { data: downtime }
    ]);
    updChart('cRr', [
      { data: repair },
      { data: reject }
    ]);
    if (charts['cGsph']) {
      charts['cGsph'].data.datasets[0].data = gsph_plan;
      charts['cGsph'].data.datasets[1].data = gsph_act;
      charts['cGsph'].update('none');
    }
    return;
  }

  // First-time creation
  charts['cQty'] = new Chart(document.getElementById('cQty'), {
    type:'bar',
    data:{ labels, datasets:[
      { label:'Plan',   data: qty_plan,   backgroundColor:'#e5e7eb', borderRadius: 4 },
      { label:'Actual', data: qty_actual, backgroundColor:'#10b981', borderRadius: 4 }
    ]},
    options: baseOpts('Pcs')
  });

  charts['cDt'] = new Chart(document.getElementById('cDt'), {
    type:'bar',
    data:{ labels, datasets:[
      { label:'Total DT', data: downtime, backgroundColor:'#ef4444', borderRadius: 4 }
    ]},
    options: baseOpts('Menit')
  });

  charts['cRr'] = new Chart(document.getElementById('cRr'), {
    type:'bar',
    data:{ labels, datasets:[
      { label:'Repair', data: repair, backgroundColor:'#f59e0b', borderRadius: 4 },
      { label:'Reject', data: reject, backgroundColor:'#ef4444', borderRadius: 4 }
    ]},
    options: baseOpts('Pcs')
  });

  charts['cGsph'] = renderGsphChart('cGsph', labels, gsph_plan, gsph_act);
}

function renderTrendCharts(d){
  const labels = Array.from({length:d}, (_,i)=>{
    const dt = new Date();
    dt.setDate(dt.getDate() - (d - 1 - i));
    return `${pad(dt.getDate())}/${pad(dt.getMonth()+1)}`;
  });

  const seed = (i, base, amp, freq) => Math.max(0, Math.round(base + Math.sin(i * freq) * amp + (Math.random()-0.5)*amp*0.4));

  const qty  = labels.map((_,i) => seed(i, 1650, 120, 0.5));
  const dt   = labels.map((_,i) => seed(i, 60, 20, 0.7));
  const rp   = labels.map((_,i) => seed(i, 26, 10, 0.4));
  const rj   = labels.map((_,i) => seed(i, 10, 5, 0.6));
  const gs   = labels.map((_,i) => parseFloat((82 + Math.sin(i*0.3)*5).toFixed(1)));

  ['cTQty','cTDt','cTRr','cTGsph'].forEach(id => destroyChart(id));

  charts['cTQty'] = new Chart(document.getElementById('cTQty'), {
    type:'bar',
    data:{ labels, datasets:[
      { label:'Good',   data:qty, backgroundColor:'#10b981', borderRadius: 2 },
      { label:'Repair', data:rp,  backgroundColor:'#f59e0b', borderRadius: 2 },
      { label:'Reject', data:rj,  backgroundColor:'#ef4444', borderRadius: 2 }
    ]},
    options: { ...baseOpts('Pcs'), scales: { x: { stacked: true }, y: { stacked: true } } }
  });

  charts['cTDt'] = new Chart(document.getElementById('cTDt'), {
    type:'line',
    data:{ labels, datasets:[{
      label:'Downtime', data:dt, borderColor:'#ef4444', backgroundColor:'rgba(239, 68, 68, 0.1)', fill:true, tension:0.4, borderWidth: 2
    }]},
    options: baseOpts('Menit')
  });

  charts['cTRr'] = new Chart(document.getElementById('cTRr'), {
    type:'line',
    data:{ labels, datasets:[
      { label:'Repair', data:rp, borderColor:'#f59e0b', fill:false, tension:0.4, borderWidth: 2 },
      { label:'Reject', data:rj, borderColor:'#ef4444', fill:false, tension:0.4, borderWidth: 2 }
    ]},
    options: baseOpts('Pcs')
  });

  destroyChart('cTGsph');
  {
    const trendGsphOpts = baseOpts('GSPH');
    const trendPos = gs.filter(v => v > 0);
    const trendMin = trendPos.length > 0 ? Math.min(...trendPos) : 0;
    const trendMax = Math.max(...gs);
    const trendSmall = trendMin > 0 && trendMax > 0 && (trendMax / trendMin) > 5;
    trendGsphOpts.scales.y.beginAtZero = !trendSmall;
    if(trendSmall){
      trendGsphOpts.scales.y.min = Math.max(0, trendMin * 0.8);
      trendGsphOpts.scales.y.max = trendMax * 1.1;
    }
    charts['cTGsph'] = new Chart(document.getElementById('cTGsph'), {
      type:'line',
      data:{ labels, datasets:[{
        label:'GSPH', data:gs, borderColor:'#3b82f6', backgroundColor:'rgba(59, 130, 246, 0.1)', fill:true, tension:0.4, borderWidth: 2
      }]},
      options: trendGsphOpts
    });
    charts['cTGsph'].__gsphData = { plan: gs, actual: gs };
  }
}

document.addEventListener('DOMContentLoaded', ()=>{
  renderLineCards();
  renderTodayCharts();
});
</script>
@endsection

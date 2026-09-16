@extends('layouts.supervisor')

@section('title', 'Downtime Achievement Dashboard')
@section('header_title', 'Downtime Achievement Dashboard')

@section('content')
<div class="space-y-5 min-w-0 space-wrapper">

    <!-- ===== HEADER ===== -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-gray-200 rounded-2xl px-5 py-4 sm:px-8 sm:py-5 shadow-sm border-l-4 border-l-red-400">
        <div>
            <h1 class="dash-h1 text-base sm:text-xl lg:text-2xl 2xl:text-3xl font-black text-gray-800 uppercase tracking-wide leading-tight">Downtime Achievement Dashboard</h1>
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
        <div class="ml-auto">
            <span class="live-badge inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse inline-block"></span>LIVE
            </span>
        </div>
    </div>

    <!-- ===== LINE CARDS ===== -->
    <div class="grid grid-cols-1 {{ count($lines) > 1 ? 'lg:grid-cols-2' : '' }} gap-6" id="linesGrid"></div>

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
    <div id="todayCharts" class="chart-grid grid grid-cols-1 sm:grid-cols-2 gap-4 2xl:gap-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>Pencapaian Produksi (Pcs)
            </div>
            <div class="p-4 flex-1 chart-min-h min-h-[220px] 2xl:min-h-[320px]"><canvas id="cQty"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-full bg-red-500 shrink-0"></span>Total Downtime (Menit)
            </div>
            <div class="p-4 flex-1 min-h-[220px] 2xl:min-h-[320px]"><canvas id="cDt"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>Repair &amp; Reject (Pcs)
            </div>
            <div class="p-4 flex-1 min-h-[220px] 2xl:min-h-[320px]"><canvas id="cRr"></canvas></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                <span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span>Pencapaian GSPH
            </div>
            <div class="p-4 flex-1 min-h-[220px] 2xl:min-h-[320px]"><canvas id="cGsph"></canvas></div>
        </div>
    </div>

    <!-- ===== CHARTS TREND ===== -->
    <div id="trendCharts" class="hidden space-y-4">
        <div class="text-center font-black text-gray-600 text-sm sm:text-lg uppercase tracking-widest" id="trendLabel"></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 2xl:gap-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>Tren Pencapaian Produksi (Pcs)
                </div>
                <div class="p-4 flex-1 min-h-[220px] 2xl:min-h-[320px]"><canvas id="cTQty"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 rounded-full bg-red-500 shrink-0"></span>Tren Total Downtime (Menit)
                </div>
                <div class="p-4 flex-1 min-h-[220px] 2xl:min-h-[320px]"><canvas id="cTDt"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>Tren Repair &amp; Reject (Pcs)
                </div>
                <div class="p-4 flex-1 min-h-[220px] 2xl:min-h-[320px]"><canvas id="cTRr"></canvas></div>
            </div>
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
                <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
                    <span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span>Tren Pencapaian GSPH
                </div>
                <div class="p-4 flex-1 min-h-[220px] 2xl:min-h-[320px]"><canvas id="cTGsph"></canvas></div>
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
  .kpi-table-cell          { font-size: 0.95rem !important; padding: 10px 14px !important; }
  .kpi-table-header        { font-size: 0.75rem !important; padding: 9px 14px !important; }
  .card-line-title         { font-size: 1.05rem !important; padding: 12px 16px !important; }
  .chart-min-h             { min-height: 380px !important; }
  .filter-input            { font-size: 0.95rem !important; padding: 10px 16px !important; }
  .filter-btn              { font-size: 0.9rem !important; padding: 10px 20px !important; }
  #liveClock               { font-size: 3.5rem !important; }
  .dash-h1                 { font-size: 2rem !important; }
  .linesGrid               { gap: 1.25rem !important; }
  .space-wrapper           { gap: 1.5rem !important; }
}

/* ── QHD / 2K ≥2560px ──────────────────────────────────────── */
@media (min-width: 2560px) {
  .kpi-table-cell          { font-size: 1.15rem !important; padding: 13px 18px !important; }
  .kpi-table-header        { font-size: 0.9rem !important; padding: 12px 18px !important; }
  .card-line-title         { font-size: 1.3rem !important; padding: 15px 20px !important; letter-spacing: 0.15em !important; }
  .chart-min-h             { min-height: 500px !important; }
  .filter-input            { font-size: 1.1rem !important; padding: 12px 20px !important; }
  .filter-btn              { font-size: 1.05rem !important; padding: 12px 28px !important; }
  #liveClock               { font-size: 5rem !important; }
  .dash-h1                 { font-size: 2.6rem !important; }
  .linesGrid               { gap: 1.75rem !important; grid-template-columns: repeat(4, 1fr) !important; }
  .space-wrapper           { gap: 2rem !important; }
  .chart-grid              { gap: 1.75rem !important; }
  .modal-dialog-inner      { max-width: 900px !important; font-size: 1.1rem !important; }
}

/* ── 4K ≥3840px ─────────────────────────────────────────────── */
@media (min-width: 3840px) {
  .kpi-table-cell          { font-size: 1.65rem !important; padding: 20px 28px !important; }
  .kpi-table-header        { font-size: 1.25rem !important; padding: 18px 28px !important; }
  .card-line-title         { font-size: 1.9rem !important; padding: 22px 28px !important; letter-spacing: 0.2em !important; }
  .chart-min-h             { min-height: 720px !important; }
  .filter-input            { font-size: 1.5rem !important; padding: 18px 28px !important; border-radius: 1rem !important; }
  .filter-btn              { font-size: 1.45rem !important; padding: 18px 40px !important; border-radius: 1rem !important; }
  #liveClock               { font-size: 7.5rem !important; }
  .dash-h1                 { font-size: 3.75rem !important; }
  .dash-subtitle           { font-size: 1.4rem !important; }
  .linesGrid               { gap: 2.5rem !important; grid-template-columns: repeat(4, 1fr) !important; }
  .space-wrapper           { gap: 3rem !important; }
  .chart-grid              { gap: 2.5rem !important; }
  .section-card            { border-radius: 1.5rem !important; padding: 2rem !important; }
  .modal-dialog-inner      { max-width: 1400px !important; font-size: 1.6rem !important; }
  .live-badge              { font-size: 1.2rem !important; padding: 10px 20px !important; }
  .day-label               { font-size: 1.2rem !important; }
  #linesGrid               { grid-template-columns: repeat(2, 1fr) !important; gap: 2.5rem !important; }
}
</style>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const LINES = @json($lines);
const SELECTED_LINE = @json($selectedLine);

let selectedShift = 1;
let selectedDays  = 1;
let charts = {};
let LINE_KPI = {};
let DETAIL_DATA = {};

/**
 * FUNGSI UTAMA PENARIKAN DATA (REAL-TIME API)
 * Fungsi ini memanggil endpoint backend untuk mendapatkan data Qty, Downtime, 
 * dan rincian kualitas secara asli dari database.
 */
async function fetchDashboardData() {
    const date = document.getElementById('dateInput').value;
    const shift = selectedShift;
    
    try {
        let url = `{{ route('supervisor.dashboard.api') }}?date=${date}&shift=${shift}`;
        if(SELECTED_LINE) url += `&line=${SELECTED_LINE}`;

        // Mengirim request ke Laravel Controller (SupervisorDashboardController@getApiData)
        const response = await fetch(url);
        const data = await response.json();
        
        // Memasukkan hasil hitungan database ke dalam variabel dashboard
        LINE_KPI = data.line_kpi;
        DETAIL_DATA = data.detail_data;
        
        // Memperbarui tampilan kartu per Line dan grafik secara otomatis
        renderLineCards();
        if(selectedDays === 1) renderTodayCharts();
    } catch (error) {
        console.error("Error fetching dashboard data:", error);
    }
}

// Menjalankan penarikan data pertama kali saat halaman dibuka
fetchDashboardData();

// AUTO-REFRESH: Mengupdate data dashboard setiap 30 detik secara otomatis
setInterval(fetchDashboardData, 30000);

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
  document.getElementById('dateInput').value = `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}`;
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
  
  fetchDashboardData();
}

function onDateChange(){
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
      renderTodayCharts();
  } else {
      document.getElementById('todayCharts').classList.add('hidden');
      document.getElementById('todayCharts').classList.remove('grid');
      document.getElementById('trendCharts').classList.remove('hidden');
      document.getElementById('trendLabel').textContent = `Grafik Tren ${d} Hari — Semua Line`;
      renderTrendCharts(d);
  }
}

function buildLineCard(line){
  const rows = LINE_KPI[line] || [];
  const trs = rows.map(kpi => {
    const dangerRowCls = kpi.danger ? 'bg-red-50' : 'hover:bg-gray-50/70';
    const textDescCls  = kpi.danger ? 'text-red-700 font-extrabold' : 'text-gray-700 font-bold';
    const borderCls    = kpi.danger ? 'border-l-[4px] border-red-500' : 'border-l-[4px] border-transparent';

    let actualCell = '';
    let clickAttr = kpi.popup || kpi.actualLink ? `onclick="openKpiDetailModal('${kpi.desc}','${line}')"` : '';
    let cursorCls = kpi.popup || kpi.actualLink ? 'cursor-pointer hover:bg-red-50 transition-colors' : '';
    let underlineCls = kpi.popup || kpi.actualLink ? 'underline decoration-dotted decoration-red-300 underline-offset-4' : '';

    if(kpi.popup && (kpi.desc==='REPAIR' || kpi.desc==='REJECT')){
      actualCell = `<span class="text-red-600 font-extrabold ${underlineCls}">${kpi.actual}</span><span class="text-gray-400 text-[10px] ml-1">| ${kpi.actualPct}</span>`;
    } else {
      actualCell = `<span class="text-red-600 font-extrabold ${underlineCls}">${kpi.actual}</span>`;
    }

    return `<tr class="border-b border-gray-100 transition-colors ${dangerRowCls}">
      <td class="kpi-table-cell px-4 py-3 text-left ${textDescCls} ${borderCls} text-xs sm:text-sm lg:text-base">${kpi.desc}</td>
      <td class="kpi-table-cell px-4 py-3 text-center text-gray-500 text-xs sm:text-sm lg:text-base">${kpi.plan}</td>
      <td class="kpi-table-cell px-4 py-3 text-center text-xs sm:text-sm lg:text-base ${cursorCls}" ${clickAttr}>${actualCell}</td>
      <td class="kpi-table-cell px-4 py-3 text-center text-gray-600 font-semibold text-xs sm:text-sm lg:text-base">${kpi.current}</td>
    </tr>`;
  }).join('');

  return `<div class="bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden flex flex-col group hover:border-red-300 transition-all">
    <div class="card-line-title bg-red-50 border-b border-red-100 px-5 py-4 text-center text-red-700 font-black tracking-[0.2em] text-sm sm:text-base lg:text-lg uppercase">
      ${line}
    </div>
    <div class="overflow-x-auto flex-1">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-gray-100 border-b-2 border-gray-200 text-gray-500">
            <th class="kpi-table-header px-4 py-3 text-left text-[10px] sm:text-xs lg:text-sm font-black uppercase tracking-widest w-[35%]">DESC</th>
            <th class="kpi-table-header px-4 py-3 text-center text-[10px] sm:text-xs lg:text-sm font-black uppercase tracking-widest">PLAN</th>
            <th class="kpi-table-header px-4 py-3 text-center text-[10px] sm:text-xs lg:text-sm font-black uppercase tracking-widest">ACTUAL</th>
            <th class="kpi-table-header px-4 py-3 text-center text-[10px] sm:text-xs lg:text-sm font-black uppercase tracking-widest">CURR</th>
          </tr>
        </thead>
        <tbody>${trs}</tbody>
      </table>
    </div>
  </div>`;
}

function renderLineCards(){
  document.getElementById('linesGrid').innerHTML = LINES.map(buildLineCard).join('');
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
    const lineData = typeData[line];
    if(!lineData || !lineData.rows || (lineData.rows.length === 0 && type !== 'PROD_T')) { 
        console.warn(`No rows found for line: ${line} in type: ${type}`);
        showKpiModalEmpty(); return; 
    }

    let html = '';
    if(typeData.type === 'quality'){
      html = `<div class="overflow-x-auto rounded-xl border border-gray-200"><table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-600"><tr>
          <th class="px-4 py-3 text-center border-b border-gray-200">No</th>
          <th class="px-4 py-3 text-left border-b border-gray-200">Item</th>
          <th class="px-4 py-3 text-left border-b border-gray-200">Problem</th>
          <th class="px-4 py-3 text-left border-b border-gray-200">Penyebab</th>
          <th class="px-4 py-3 text-left border-b border-gray-200">Countermeasure</th>
          <th class="px-4 py-3 text-center border-b border-gray-200">Qty</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-100">
        ${lineData.rows.map(r=>`<tr class="hover:bg-gray-50">
          <td class="px-4 py-3 text-center text-gray-500">${r.no}</td>
          <td class="px-4 py-3 font-semibold text-gray-800">${r.item}</td>
          <td class="px-4 py-3 text-gray-600">${r.problem}</td>
          <td class="px-4 py-3 text-center text-red-600 font-black">${r.qty}</td>
        </tr>`).join('')}
        <tr class="bg-gray-50">
          <td colspan="5" class="px-4 py-3 text-right font-bold text-gray-700">TOTAL</td>
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

function destroyChart(id){
  if(charts[id]){ charts[id].destroy(); delete charts[id]; }
}

const CHART_TEXT  = '#6b7280';
const CHART_GRID  = '#f3f4f6';
const FONT_SZ     = 11;

function baseOpts(yLabel){
  return {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { labels: { color: CHART_TEXT, font:{size: FONT_SZ, family: "'Inter', sans-serif"} } }
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

  destroyChart('cQty');
  charts['cQty'] = new Chart(document.getElementById('cQty'), {
    type:'bar',
    data:{ labels, datasets:[
      { label:'Plan',   data: qty_plan,   backgroundColor:'#e5e7eb', borderRadius: 4 },
      { label:'Actual', data: qty_actual, backgroundColor:'#10b981', borderRadius: 4 }
    ]},
    options: baseOpts('Pcs')
  });

  destroyChart('cDt');
  charts['cDt'] = new Chart(document.getElementById('cDt'), {
    type:'bar',
    data:{ labels, datasets:[
      { label:'Total DT', data: downtime, backgroundColor:'#ef4444', borderRadius: 4 }
    ]},
    options: baseOpts('Menit')
  });

  destroyChart('cRr');
  charts['cRr'] = new Chart(document.getElementById('cRr'), {
    type:'bar',
    data:{ labels, datasets:[
      { label:'Repair', data: repair, backgroundColor:'#f59e0b', borderRadius: 4 },
      { label:'Reject', data: reject, backgroundColor:'#ef4444', borderRadius: 4 }
    ]},
    options: baseOpts('Pcs')
  });

  destroyChart('cGsph');
  charts['cGsph'] = new Chart(document.getElementById('cGsph'), {
    type:'bar',
    data:{ labels, datasets:[
      { label:'Plan',   data: gsph_plan, backgroundColor:'#e5e7eb', borderRadius: 4 },
      { label:'Actual', data: gsph_act,  backgroundColor:'#3b82f6', borderRadius: 4 }
    ]},
    options: baseOpts('GSPH')
  });
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

  charts['cTGsph'] = new Chart(document.getElementById('cTGsph'), {
    type:'line',
    data:{ labels, datasets:[{
      label:'GSPH', data:gs, borderColor:'#3b82f6', backgroundColor:'rgba(59, 130, 246, 0.1)', fill:true, tension:0.4, borderWidth: 2
    }]},
    options: baseOpts('GSPH')
  });
}

document.addEventListener('DOMContentLoaded', ()=>{
  renderLineCards();
  renderTodayCharts();
});
</script>
@endsection

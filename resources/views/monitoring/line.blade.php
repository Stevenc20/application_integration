@extends('layouts.supervisor')

@section('content')
<style>
/* ── LINE MONITORING PREMIUM STYLES ── */
.lm-card {
  background:#fff;
  border-radius:1rem;
  border:1px solid #e5e7eb;
  box-shadow:0 2px 12px rgba(0,0,0,0.07);
  overflow:hidden;
  display:flex;
  flex-direction:column;
  transition:box-shadow .2s,border-color .2s;
  max-height:560px;
}
.lm-card:hover { box-shadow:0 6px 24px rgba(0,0,0,0.12); border-color:#fca5a5; }

.lm-card-header {
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:12px 14px 10px;
  border-bottom:1px solid #f3f4f6;
  background:linear-gradient(135deg,#fff7f7 0%,#fff 100%);
  flex-shrink:0;
}
.lm-line-name {
  font-size:1.05rem;
  font-weight:900;
  color:#1e293b;
  letter-spacing:0.04em;
}
.lm-status-badge {
  font-size:9px;
  font-weight:800;
  text-transform:uppercase;
  letter-spacing:0.08em;
  padding:3px 9px;
  border-radius:99px;
  display:inline-flex;
  align-items:center;
  gap:5px;
}
.lm-status-badge .dot {
  width:6px; height:6px; border-radius:50%;
  display:inline-block;
}
.lm-body { padding:12px 14px; flex:1; display:flex; flex-direction:column; gap:10px; overflow-y:auto; }

/* Progress bar */
.lm-prog-label { font-size:9px; font-weight:800; color:#94a3b8; text-transform:uppercase; letter-spacing:0.07em; }
.lm-prog-track {
  width:100%; height:10px; background:#f1f5f9;
  border-radius:99px; overflow:hidden; display:flex;
  border:1px solid #e2e8f0;
  box-shadow:inset 0 1px 3px rgba(0,0,0,0.07);
}
.lm-prog-bar { height:100%; transition:width .4s ease; }
.lm-prog-foot { font-size:10px; color:#94a3b8; font-weight:700; display:flex; justify-content:space-between; margin-top:3px; }

/* KPI grid */
.lm-kpi-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px; }
.lm-kpi-cell {
  border-radius:8px;
  padding:7px 8px;
  display:flex;
  flex-direction:column;
  gap:2px;
}
.lm-kpi-cell .kpi-label { font-size:9px; font-weight:700; opacity:.7; text-transform:uppercase; letter-spacing:0.05em; }
.lm-kpi-cell .kpi-value { font-size:15px; font-weight:900; line-height:1.1; }
.lm-kpi-cell .kpi-sub   { font-size:9px; font-weight:600; opacity:.6; }

/* Quality row */
.lm-quality-row { display:grid; grid-template-columns:1fr 1fr 1fr; gap:5px; }
.lm-q-cell {
  border-radius:8px;
  padding:6px 8px;
  text-align:center;
}
.lm-q-cell .q-label { font-size:9px; font-weight:800; text-transform:uppercase; opacity:.75; }
.lm-q-cell .q-value { font-size:14px; font-weight:900; line-height:1.2; }

/* Job items */
.lm-job-item {
  border-top:1px solid #f1f5f9;
  padding-top:10px;
  margin-top:2px;
}
.lm-job-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:6px; }
.lm-job-name { font-size:11px; font-weight:900; color:#1e293b; }
.lm-job-part { font-size:9px; color:#94a3b8; font-weight:700; margin-top:1px; }
.lm-job-badge {
  font-size:8px; font-weight:800; text-transform:uppercase;
  padding:2px 7px; border-radius:99px; flex-shrink:0;
}

/* Downtime */
.lm-dt-row { display:flex; align-items:center; gap:6px; font-size:11px; padding:2px 0; }
.lm-dt-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

/* Empty / no-data state */
.lm-no-data {
  display:flex; flex-direction:column; align-items:center;
  justify-content:center; gap:6px; padding:28px 12px;
  color:#cbd5e1;
}
.lm-no-data span { font-size:11px; font-weight:700; letter-spacing:0.04em; }

/* Stat cards */
.lm-stat-card {
  border-radius:1rem;
  padding:16px 18px;
  border:1px solid;
  display:flex; flex-direction:column; gap:4px;
}
</style>

<div class="p-4 md:p-6 space-y-5">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-gray-200 rounded-2xl px-5 py-4 shadow-sm border-l-4 border-l-red-400">
        <div>
            <h1 class="text-xl md:text-2xl font-black text-gray-800 uppercase tracking-wide">Line Monitoring</h1>
            <p class="text-gray-400 text-xs font-semibold mt-0.5" id="hdrDate">{{ \Carbon\Carbon::parse($selectedDate)->format('d F Y') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <input type="date" id="dateInput" value="{{ $selectedDate }}" onchange="onFilterChange()" class="border border-gray-200 rounded-xl px-3 py-2 text-sm font-semibold text-gray-700 focus:ring-2 focus:ring-red-300 focus:border-red-500 outline-none transition bg-gray-50">
            </div>
            <div class="flex bg-gray-100 rounded-xl p-1 border border-gray-200 gap-1">
                <button id="s1btn" onclick="setShift(1)" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $selectedShift === 1 ? 'text-white bg-red-500 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Shift Pagi</button>
                <button id="s2btn" onclick="setShift(2)" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $selectedShift === 2 ? 'text-white bg-red-500 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}">Shift Malam</button>
            </div>
            <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-green-50 border border-green-200 text-green-700 text-xs font-bold">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse inline-block"></span>LIVE
            </span>
            <div class="text-right hidden sm:block">
                <p class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider">Live Time</p>
                <p id="liveClock" class="text-base font-black text-blue-600 tabular-nums"></p>
            </div>
        </div>
    </div>

    {{-- FACTORY STATUS --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="factoryStats">
        <div class="lm-stat-card bg-blue-50 border-blue-100">
            <p class="text-[10px] font-bold text-blue-400 uppercase tracking-wider">Total Production</p>
            <p class="text-2xl font-black text-blue-700" id="statTotalProd">-</p>
        </div>
        <div class="lm-stat-card bg-emerald-50 border-emerald-100">
            <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">Average Speed</p>
            <p class="text-2xl font-black text-emerald-700" id="statAvgSpeed">-</p>
        </div>
        <div class="lm-stat-card bg-gray-50 border-gray-200">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Active Lines</p>
            <p class="text-2xl font-black text-gray-800" id="statActiveLines">-</p>
        </div>
    </div>

    {{-- LINE MONITORING GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4" id="linesGrid">
        <div class="col-span-full text-center py-8 text-gray-400 text-sm">Memuat data...</div>
    </div>

    {{-- LINE COMPARISON CHART --}}
    <div id="comparisonSection" class="bg-white shadow-sm rounded-2xl border border-gray-200 p-4 md:p-6" style="display:none">
        <h2 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">Perbandingan Plan vs Actual per Line</h2>
        <canvas id="comparisonChart" height="120"></canvas>
    </div>

    {{-- PRODUCTION TREND --}}
    <div class="bg-white shadow-sm rounded-2xl border border-gray-200 p-4 md:p-6">
        <div class="flex flex-wrap gap-2 mb-4">
            <button class="line-filter px-3 py-1.5 rounded-lg bg-red-500 text-white text-xs font-bold" data-line="all">All Lines</button>
            @foreach($lines as $line)
            <button class="line-filter px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-600" data-line="{{ $line }}">Line {{ explode(' ', $line)[1] ?? $line }}</button>
            @endforeach
        </div>
        <h2 class="font-bold text-gray-700 mb-4 text-sm uppercase tracking-wider">Hourly Production Trend</h2>
        <canvas id="lineChart" height="90"></canvas>
    </div>

</div>

@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
<script>
(function() {
    'use strict';

    const LINES = @json($lines);
    const API_URL = '{{ route("monitoring.line.api") }}';
    const INIT_DATA = @json($lineKpi);
    let selectedShift = {{ $selectedShift }};
    let LINE_KPI = INIT_DATA || {};
    let chart = null;
    let comparisonChart = null;

    function shortName(name) {
        var p = name.split(' ');
        return p.length > 1 ? p[1] : name;
    }

    function pad(n) { return String(n).padStart(2, '0'); }

    function updateClock() {
        const now = new Date();
        const el = document.getElementById('liveClock');
        if (el) el.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
    }
    setInterval(updateClock, 1000);
    updateClock();

    window.setShift = function(s) {
        selectedShift = s;
        document.getElementById('s1btn').className = 'px-3 py-1.5 text-xs font-bold rounded-lg transition-all ' + (s === 1 ? 'text-white bg-red-500 shadow-sm' : 'text-gray-500 hover:text-gray-700');
        document.getElementById('s2btn').className = 'px-3 py-1.5 text-xs font-bold rounded-lg transition-all ' + (s === 2 ? 'text-white bg-red-500 shadow-sm' : 'text-gray-500 hover:text-gray-700');
        fetchLineData();
    };

    window.onFilterChange = function() {
        const d = document.getElementById('dateInput').value;
        document.getElementById('hdrDate').textContent = new Date(d + 'T00:00:00').toLocaleDateString('id-ID', {day:'2-digit',month:'long',year:'numeric'});
        fetchLineData();
    };

    function parseNum(v) {
        if (v === null || v === undefined || v === '' || v === '-') return 0;
        var s = String(v).replace(/[^0-9.\-]/g, '');
        return parseFloat(s) || 0;
    }

    function findKpi(rows, key) {
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].desc === key) return rows[i];
        }
        return null;
    }

    async function fetchLineData() {
        var date = document.getElementById('dateInput').value;
        var url = API_URL + '?date=' + date + '&shift=' + selectedShift;
        try {
            var res = await fetch(url, { credentials: 'same-origin' });
            if (!res.ok) {
                console.error('HTTP ' + res.status + ': ' + url);
                return;
            }
            var data = await res.json();
            if (data.line_kpi) {
                LINE_KPI = data.line_kpi;
            } else {
                LINE_KPI = {};
            }
            renderFactoryStats();
            renderLineCards();
            renderComparisonChart();
        } catch (e) {
            console.error('Fetch error:', url, e);
        }
    }

    function renderFactoryStats() {
        var totalProd = 0;
        var totalGsph = 0;
        var gsphCount = 0;
        var activeCount = 0;

        LINES.forEach(function(line) {
            var kpi = LINE_KPI[line];
            if (!kpi) return;
            var kpis = kpi.rows || [];

            var qtyRow = findKpi(kpis, 'QTY');
            var gsphRow = findKpi(kpis, 'GSPH');

            var qty = qtyRow ? parseNum(qtyRow.actual) : 0;
            var gsph = gsphRow ? parseNum(gsphRow.actual) : 0;

            if (kpi.running) {
                activeCount++;
            }
            if (qty > 0) {
                totalProd += qty;
            }
            if (gsph > 0) {
                totalGsph += gsph;
                gsphCount++;
            }
        });

        var avgSpeed = gsphCount > 0 ? Math.round(totalGsph / gsphCount) : 0;

        document.getElementById('statTotalProd').textContent = totalProd > 0 ? totalProd.toLocaleString('id-ID') + ' pcs' : '0 pcs';
        document.getElementById('statAvgSpeed').textContent = avgSpeed > 0 ? avgSpeed.toLocaleString('id-ID') + ' pcs/h' : '0 pcs/h';
        document.getElementById('statActiveLines').textContent = activeCount + ' / ' + LINES.length;
    }

    // Zoom and Tooltip functions for segmented timeline
    window.zoomLevels = window.zoomLevels || {};
    window.zoomTimeline = function(id, delta) {
        if (!window.zoomLevels[id]) window.zoomLevels[id] = 1.0;
        let newZoom = window.zoomLevels[id] + delta;
        if (newZoom < 1.0) newZoom = 1.0;
        if (newZoom > 10.0) newZoom = 10.0;
        window.zoomLevels[id] = newZoom;
        
        const container = document.getElementById('aseg-container-' + id);
        const labelVal = document.getElementById('zoom-val-' + id);
        if (container) {
            container.style.width = (newZoom * 100) + '%';
            container.style.minWidth = (newZoom * 100) + '%';
        }
        if (labelVal) {
            labelVal.innerText = newZoom.toFixed(1) + 'x';
        }
    };
    window.resetZoomTimeline = function(id) {
        window.zoomLevels[id] = 1.0;
        const container = document.getElementById('aseg-container-' + id);
        const labelVal = document.getElementById('zoom-val-' + id);
        if (container) {
            container.style.width = '100%';
            container.style.minWidth = '100%';
        }
        if (labelVal) {
            labelVal.innerText = '1.0x';
        }
    };

    window.showTimelineTooltip = function(e, label, from, to, duration, detail) {
        let el = document.getElementById('timeline-tooltip');
        if (!el) {
            el = document.createElement('div');
            el.id = 'timeline-tooltip';
            el.style.cssText = 'position:fixed;z-index:9999;pointer-events:none;background:#1e293b;color:white;border-radius:10px;padding:8px 12px;font-size:11px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,0.3);border:1px solid #334155;max-width:260px;';
            document.body.appendChild(el);
        }
        el.innerHTML = '<div style="font-weight:800;font-size:10px;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;color:' + (label.toLowerCase().includes('dandori') ? '#fbbf24' : label.toLowerCase().includes('production') ? '#60a5fa' : '#f87171') + ';">' + label + '</div>'
            + '<div style="display:flex;gap:12px;font-size:10px;color:#94a3b8;">'
            + '<span>' + from + ' → ' + to + '</span>'
            + '<span style="color:white;font-weight:700;">' + duration + '</span>'
            + '</div>'
            + (detail ? '<div style="font-size:9px;color:#64748b;margin-top:3px;border-top:1px solid #334155;padding-top:3px;">' + detail + '</div>' : '');
        el.style.display = 'block';
        let x = e.clientX + 12;
        let y = e.clientY - 10;
        if (x + 270 > window.innerWidth) x = e.clientX - 270;
        if (y < 0) y = e.clientY + 20;
        el.style.left = x + 'px';
        el.style.top = y + 'px';
    };
    window.hideTimelineTooltip = function() {
        let el = document.getElementById('timeline-tooltip');
        if (el) el.style.display = 'none';
    };

    function buildJobTimelineHtml(job, dateStr) {
        if (!job || !job.segments || job.segments.length === 0) {
            return '';
        }
        
        var earliest = null;
        var latest = null;
        
        job.segments.forEach(function(seg) {
            var s = new Date(seg.start.replace(' ', 'T')).getTime();
            var e = new Date(seg.end.replace(' ', 'T')).getTime();
            if (earliest === null || s < earliest) earliest = s;
            if (latest === null || e > latest) latest = e;
        });
        
        if (earliest === null) {
            earliest = new Date(dateStr + 'T07:00:00').getTime();
            latest = earliest + 3600000;
        }
        if (latest === null) {
            latest = earliest + 3600000;
        }
        
        var totalDur = Math.max(1, latest - earliest);
        
        var segmentsHtml = '';
        job.segments.forEach(function(seg) {
            var s = new Date(seg.start.replace(' ', 'T')).getTime();
            var e = new Date(seg.end.replace(' ', 'T')).getTime();
            var left = Math.max(0, ((s - earliest) / totalDur) * 100);
            var width = Math.max(0.1, ((e - s) / totalDur) * 100);
            
            var fromTime = new Date(s).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
            var toTime = new Date(e).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit',second:'2-digit'});
            var durMin = Math.round((e - s) / 60000);
            var durStr = durMin + ' m';
            if (durMin === 0) {
                durStr = Math.round((e - s) / 1000) + ' s';
            }
            
            segmentsHtml += '<div class="absolute h-full ' + seg.color + ' cursor-pointer border-r border-white/20 hover:brightness-110 transition-all"'
                + ' style="left: ' + left + '%; width: ' + width + '%;"'
                + ' onmouseover="showTimelineTooltip(event, \'' + seg.label + '\', \'' + fromTime + '\', \'' + toTime + '\', \'' + durStr + '\', \'' + (seg.detail || '').replace(/'/g, "\\'") + '\')"'
                + ' onmouseout="hideTimelineTooltip()"'
                + ' title="' + seg.label + ': ' + fromTime + ' - ' + toTime + ' (' + durStr + ')">'
                + '</div>';
        });
        
        var ticksHtml = '';
        var durationHours = (latest - earliest) / 3600000;
        var intervalMs = 3600000;
        if (durationHours <= 2) {
            intervalMs = 900000;
        } else if (durationHours <= 6) {
            intervalMs = 1800000;
        } else if (durationHours <= 12) {
            intervalMs = 3600000;
        } else {
            intervalMs = 7200000;
        }
        
        var startHour = new Date(earliest);
        startHour.setMinutes(0, 0, 0);
        if (startHour.getTime() < earliest) {
            startHour.setTime(startHour.getTime() + 3600000);
        }
        
        var curr = startHour.getTime();
        while (curr <= latest) {
            var leftTick = ((curr - earliest) / totalDur) * 100;
            if (leftTick >= 0 && leftTick <= 100) {
                var timeStr = new Date(curr).toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
                ticksHtml += '<div class="absolute flex flex-col items-center -translate-x-1/2" style="left: ' + leftTick + '%">'
                    + '<div class="w-[1px] h-1 bg-gray-300"></div>'
                    + '<span class="text-[8px] font-black text-gray-400 mt-0.5 font-mono leading-none">' + timeStr + '</span>'
                    + '</div>';
            }
            curr += intervalMs;
        }
        
        var html = '<div class="space-y-1.5 mt-2">'
            + '<div class="flex items-center justify-between text-[8px] font-black text-slate-400">'
            + '<span>ACTUAL SEGMENTED EXECUTION</span>'
            + '<div class="flex items-center gap-1 bg-gray-100 rounded-lg p-0.5 border border-gray-200 scale-90 origin-right select-none">'
            + '<button type="button" onclick="zoomTimeline(\'' + job.id + '\', -0.5)" class="w-4 h-4 flex items-center justify-center rounded bg-white border border-gray-200 shadow-sm text-gray-500 hover:bg-gray-50 text-[8px] font-black">&minus;</button>'
            + '<span id="zoom-val-' + job.id + '" class="text-[7px] font-bold text-gray-600 px-0.5 font-mono">1.0x</span>'
            + '<button type="button" onclick="zoomTimeline(\'' + job.id + '\', 0.5)" class="w-4 h-4 flex items-center justify-center rounded bg-white border border-gray-200 shadow-sm text-gray-500 hover:bg-gray-50 text-[8px] font-black">+</button>'
            + '<button type="button" onclick="resetZoomTimeline(\'' + job.id + '\')" class="px-1 py-0.5 rounded text-[7px] font-bold text-gray-400 hover:text-gray-600 uppercase">Reset</button>'
            + '</div>'
            + '</div>'
            
            + '<div class="relative w-full overflow-x-auto select-none py-1 scrollbar-thin scrollbar-thumb-slate-300" id="scroll-container-' + job.id + '">'
            + '<div class="relative flex flex-col gap-1 transition-all duration-200" style="width: 100%; min-width: 100%;" id="aseg-container-' + job.id + '">'
            + '<div class="relative h-8 bg-slate-900 rounded-lg border border-slate-800 shadow-inner overflow-hidden">'
            + '<div class="absolute inset-0 rounded-lg">' + segmentsHtml + '</div>'
            + '</div>'
            + '<div class="relative h-4 w-full">' + ticksHtml + '</div>'
            + '</div>'
            + '</div>'
            + '</div>';
            
        return html;
    }

    function renderLineCards() {
        var container = document.getElementById('linesGrid');
        if (LINES.length === 0) {
            container.innerHTML = '<div class="col-span-full text-center py-8 text-gray-400 text-sm">Tidak ada line aktif</div>';
            return;
        }

        var html = '';
        LINES.forEach(function(line) {
            var kpi = LINE_KPI[line];
            var kpis = kpi ? (kpi.rows || []) : [];
            var qtyRow    = findKpi(kpis, 'QTY');
            var gsphRow   = findKpi(kpis, 'GSPH');
            var dtRow     = findKpi(kpis, 'TOTAL_DT');
            var repairRow = findKpi(kpis, 'REPAIR');
            var rejectRow = findKpi(kpis, 'REJECT');

            var plan       = qtyRow    ? parseNum(qtyRow.plan)        : 0;
            var actual     = qtyRow    ? parseNum(qtyRow.actual)      : 0;
            var gsphActual = gsphRow   ? parseNum(gsphRow.actual)     : 0;
            var gsphPlan   = gsphRow   ? parseNum(gsphRow.plan)       : 0;
            var dtLabel    = dtRow     ? (dtRow.actual   || '0 m')    : '0 m';
            var repair     = repairRow ? parseNum(repairRow.actual)   : 0;
            var reject     = rejectRow ? parseNum(rejectRow.actual)   : 0;
            var ok         = Math.max(0, actual - repair - reject);

            var totalActual = ok + repair + reject;
            var denom    = Math.max(plan, totalActual, 1);
            var pct      = Math.round((actual / denom) * 100);
            var okPct    = (ok     / denom) * 100;
            var repairPct= (repair / denom) * 100;
            var rejectPct= (reject / denom) * 100;

            // Status badge
            var st = kpi && kpi.status ? kpi.status : {label:'NOT RUNNING', color:'gray', pulse:false};
            var dotColor, badgeBg, badgeText;
            if (st.color === 'green' || st.color === 'emerald') {
                dotColor = '#10b981'; badgeBg = '#d1fae5'; badgeText = '#065f46';
            } else if (st.color === 'amber' || st.color === 'yellow') {
                dotColor = '#f59e0b'; badgeBg = '#fef3c7'; badgeText = '#92400e';
            } else if (st.color === 'red' || st.color === 'rose') {
                dotColor = '#ef4444'; badgeBg = '#fee2e2'; badgeText = '#991b1b';
            } else if (st.color === 'blue') {
                dotColor = '#3b82f6'; badgeBg = '#dbeafe'; badgeText = '#1e40af';
            } else if (st.color === 'purple' || st.color === 'violet') {
                dotColor = '#9333ea'; badgeBg = '#f3e8ff'; badgeText = '#581c87';
            } else {
                dotColor = '#9ca3af'; badgeBg = '#f3f4f6'; badgeText = '#4b5563';
            }
            var statusBadgeHtml = '<span class="lm-status-badge" style="background:' + badgeBg + ';color:' + badgeText + '">'
                + '<span class="dot' + (st.pulse ? ' animate-pulse' : '') + '" style="background:' + dotColor + '"></span>'
                + st.label + '</span>';

            // Progress bar (show empty track when no data)
            var progBarInner = '';
            if (okPct > 0)     progBarInner += '<div class="lm-prog-bar bg-emerald-500" style="width:' + okPct + '%"></div>';
            if (repairPct > 0) progBarInner += '<div class="lm-prog-bar bg-amber-500"   style="width:' + repairPct + '%"></div>';
            if (rejectPct > 0) progBarInner += '<div class="lm-prog-bar bg-rose-500"    style="width:' + rejectPct + '%"></div>';

            // KPI 2x2 grid
            var kpiGrid = '<div class="lm-kpi-grid">'
                + '<div class="lm-kpi-cell" style="background:#eff6ff">'
                    + '<span class="kpi-label" style="color:#3b82f6">Target</span>'
                    + '<span class="kpi-value" style="color:#1d4ed8">' + plan.toLocaleString('id-ID') + '</span>'
                    + '<span class="kpi-sub">pcs</span>'
                + '</div>'
                + '<div class="lm-kpi-cell" style="background:#f0fdf4">'
                    + '<span class="kpi-label" style="color:#16a34a">Actual</span>'
                    + '<span class="kpi-value" style="color:#15803d">' + actual.toLocaleString('id-ID') + '</span>'
                    + '<span class="kpi-sub">pcs</span>'
                + '</div>'
                + '<div class="lm-kpi-cell" style="background:#fff7ed">'
                    + '<span class="kpi-label" style="color:#ea580c">Speed</span>'
                    + '<span class="kpi-value" style="color:#c2410c">' + (gsphActual || '—') + '</span>'
                    + '<span class="kpi-sub">/h actual</span>'
                + '</div>'
                + '<div class="lm-kpi-cell" style="background:#fef2f2">'
                    + '<span class="kpi-label" style="color:#dc2626">Required</span>'
                    + '<span class="kpi-value" style="color:#b91c1c">' + (gsphPlan || '—') + '</span>'
                    + '<span class="kpi-sub">/h plan</span>'
                + '</div>'
                + '</div>';

            // Quality row
            var qualityRow = '<div class="lm-quality-row">'
                + '<div class="lm-q-cell" style="background:#f0fdf4">'
                    + '<div class="q-label" style="color:#16a34a">OK</div>'
                    + '<div class="q-value" style="color:#15803d">' + ok.toLocaleString('id-ID') + '</div>'
                + '</div>'
                + '<div class="lm-q-cell" style="background:#fffbeb">'
                    + '<div class="q-label" style="color:#d97706">Repair</div>'
                    + '<div class="q-value" style="color:#b45309">' + repair.toLocaleString('id-ID') + '</div>'
                + '</div>'
                + '<div class="lm-q-cell" style="background:#fff1f2">'
                    + '<div class="q-label" style="color:#e11d48">Reject</div>'
                    + '<div class="q-value" style="color:#be123c">' + reject.toLocaleString('id-ID') + '</div>'
                + '</div>'
                + '</div>';

            // Jobs
            var jobsHtml = '';
            if (kpi && kpi.jobs && kpi.jobs.length > 0) {
                var dateStr = document.getElementById('dateInput').value;
                kpi.jobs.forEach(function(job) {
                    var jOk     = job.actual_ok     || 0;
                    var jRepair = job.actual_repair  || 0;
                    var jReject = job.actual_reject  || 0;
                    var jPlan   = job.target_qty     || 0;
                    var jActual = job.actual_qty     || 0;
                    var jTotal  = jOk + jRepair + jReject;
                    var jDenom  = Math.max(jPlan, jTotal, 1);
                    var jPct    = Math.round((jActual / jDenom) * 100);
                    var jOkPct     = (jOk     / jDenom) * 100;
                    var jRepairPct = (jRepair  / jDenom) * 100;
                    var jRejectPct = (jReject  / jDenom) * 100;

                    var jStatus = job.status || 'done';
                    var jBadgeBg, jBadgeColor;
                    if (jStatus === 'running')       { jBadgeBg = '#d1fae5'; jBadgeColor = '#065f46'; }
                    else                             { jBadgeBg = '#f1f5f9'; jBadgeColor = '#64748b'; }

                    var jProgInner = '';
                    if (jOkPct     > 0) jProgInner += '<div class="lm-prog-bar bg-emerald-500" style="width:' + jOkPct     + '%"></div>';
                    if (jRepairPct > 0) jProgInner += '<div class="lm-prog-bar bg-amber-500"   style="width:' + jRepairPct + '%"></div>';
                    if (jRejectPct > 0) jProgInner += '<div class="lm-prog-bar bg-rose-500"    style="width:' + jRejectPct + '%"></div>';

                    jobsHtml += '<div class="lm-job-item">'
                        + '<div class="lm-job-header">'
                            + '<div>'
                                + '<div class="lm-job-name">' + job.job_number + '</div>'
                                + '<div class="lm-job-part">' + job.job_name + '</div>'
                            + '</div>'
                            + '<span class="lm-job-badge" style="background:' + jBadgeBg + ';color:' + jBadgeColor + '">' + jStatus + '</span>'
                        + '</div>'
                        + '<div class="lm-prog-label" style="margin-bottom:4px">Progress &nbsp;<span style="color:#3b82f6;font-weight:900">' + jPct + '%</span></div>'
                        + '<div class="lm-prog-track">' + jProgInner + '</div>'
                        + '<div class="lm-prog-foot">'
                            + '<span>OK: <b style="color:#16a34a">' + jOk.toLocaleString('id-ID') + '</b> &middot; Rep: <b style="color:#d97706">' + jRepair.toLocaleString('id-ID') + '</b> &middot; Rej: <b style="color:#e11d48">' + jReject.toLocaleString('id-ID') + '</b></span>'
                            + '<span>Target: ' + jPlan.toLocaleString('id-ID') + '</span>'
                        + '</div>'
                        + buildJobTimelineHtml(job, dateStr)
                    + '</div>';
                });
            }

            html += '<div class="lm-card">'
                // Header
                + '<div class="lm-card-header">'
                    + '<span class="lm-line-name">Line ' + shortName(line) + '</span>'
                    + statusBadgeHtml
                + '</div>'
                // Body
                + '<div class="lm-body">'
                    // Progress
                    + '<div>'
                        + '<div class="lm-prog-label" style="margin-bottom:5px">Production Progress &nbsp;<span style="color:#3b82f6;font-weight:900">' + pct + '%</span></div>'
                        + '<div class="lm-prog-track">' + progBarInner + '</div>'
                        + '<div class="lm-prog-foot">'
                            + '<span>' + actual.toLocaleString('id-ID') + ' pcs actual</span>'
                            + '<span>Plan: ' + plan.toLocaleString('id-ID') + '</span>'
                        + '</div>'
                    + '</div>'
                    // KPI grid
                    + kpiGrid
                    // Quality
                    + qualityRow
                    // Downtime
                    + buildDowntimeSection(kpi, dtLabel)
                    // Jobs
                    + (jobsHtml ? jobsHtml : '')
                + '</div>'
            + '</div>';
        });

        container.innerHTML = html;
    }

    function buildDowntimeSection(kpi, dtLabel) {
        var totalMin = parseNum(dtLabel);
        if (totalMin === 0) return '';
        var types = kpi ? kpi.downtimeByType : null;
        var colorMap = {'produksi':'#f97316','Mesin':'#ef4444','dies':'#a855f7','Logistic':'#3b82f6','Material':'#f59e0b'};
        var labels   = {'produksi':'Produksi','Mesin':'Mesin','dies':'Dies','Logistic':'Logistic','Material':'Material'};

        var rows = '';
        if (types && Object.keys(types).length > 0) {
            for (var jenis in types) {
                var mins = Math.round(types[jenis] / 60);
                if (mins === 0) continue;
                rows += '<div class="lm-dt-row">'
                    + '<span class="lm-dt-dot" style="background:' + (colorMap[jenis] || '#9ca3af') + '"></span>'
                    + '<span style="color:#475569;flex:1">' + (labels[jenis] || jenis) + '</span>'
                    + '<span style="font-weight:700;color:#1e293b">' + mins.toLocaleString('id-ID') + ' m</span>'
                + '</div>';
            }
        } else {
            rows = '<div style="font-weight:800;color:#ef4444;font-size:13px">' + dtLabel + '</div>';
        }

        return '<div style="border-top:1px solid #f1f5f9;padding-top:8px;margin-top:2px">'
            + '<div style="font-size:9px;font-weight:800;color:#94a3b8;text-transform:uppercase;letter-spacing:0.07em;margin-bottom:5px">Downtime (' + dtLabel + ')</div>'
            + rows
        + '</div>';
    }

    function initComparisonChart() {
        var ctx = document.getElementById('comparisonChart');
        if (!ctx) return;
        comparisonChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [
                    { label: 'Plan', data: [], backgroundColor: '#3b82f6', borderRadius: 4 },
                    { label: 'Actual', data: [], backgroundColor: '#22c55e', borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Qty (pcs)' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function renderComparisonChart() {
        if (!comparisonChart) return;
        var labels = [];
        var planData = [];
        var actualData = [];
        var hasData = false;
        LINES.forEach(function(line) {
            var kpi = LINE_KPI[line];
            var kpis = kpi ? (kpi.rows || []) : [];
            var qtyRow = findKpi(kpis, 'QTY');
            var plan = qtyRow ? parseNum(qtyRow.plan) : 0;
            var actual = qtyRow ? parseNum(qtyRow.actual) : 0;
            if (plan > 0 || actual > 0) hasData = true;
            planData.push(plan);
            actualData.push(actual);
            labels.push(shortName(line));
        });
        var section = document.getElementById('comparisonSection');
        if (!hasData) {
            section.style.display = 'none';
            return;
        }
        section.style.display = '';
        comparisonChart.data.labels = labels;
        comparisonChart.data.datasets[0].data = planData;
        comparisonChart.data.datasets[1].data = actualData;
        comparisonChart.update();
    }

    function initChart() {
        var ctx = document.getElementById('lineChart');
        if (!ctx) return;
        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Actual Production', data: [], borderWidth: 2, tension: 0.3, borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,0.1)', fill: true },
                    { label: 'Target', data: [], borderDash: [5,5], borderWidth: 2, borderColor: '#ef4444' }
                ]
            },
            options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } }
        });
    }

    function renderChart() {
        if (!chart) return;
        var selectedLine = document.querySelector('.line-filter.bg-red-600')?.dataset.line || 'all';
        var hours = ['07','08','09','10','11','12','13','14','15','16','17','18','19','20'];
        var allData = [];
        var planData = [];

        hours.forEach(function(h, i) {
            var sum = 0;
            var planSum = 0;
            LINES.forEach(function(line) {
                var kpi = LINE_KPI[line];
                var kpis = kpi ? (kpi.rows || []) : [];
                var qtyRow = findKpi(kpis, 'QTY');
                if (qtyRow) {
                    sum += parseNum(qtyRow.actual) / hours.length;
                    planSum += parseNum(qtyRow.plan) / hours.length;
                }
            });
            allData.push(Math.round(sum));
            planData.push(Math.round(planSum));
        });

        chart.data.labels = hours;
        chart.data.datasets[0].data = allData;
        chart.data.datasets[1].data = planData;
        chart.update();
    }

    document.querySelectorAll('.line-filter').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.line-filter').forEach(function(b) {
                b.classList.remove('bg-red-600', 'text-white');
                b.classList.add('bg-gray-200');
            });
            this.classList.remove('bg-gray-200');
            this.classList.add('bg-red-600', 'text-white');
            renderChart();
        });
    });

    renderFactoryStats();
    renderLineCards();
    fetchLineData();

    // Real-time via BroadcastChannel (instant from Input Harian saves)
    try {
        const statusChan = new BroadcastChannel('line_status');
        statusChan.onmessage = (e) => {
            if (e.data.type === 'status-changed') fetchLineData();
        };
    } catch (e) { /* fallback to polling */ }

    setInterval(fetchLineData, 10000);

    if (typeof Chart !== 'undefined') {
        initChart();
        renderChart();
        initComparisonChart();
        renderComparisonChart();
    } else {
        document.querySelector('script[src*="chart.js"]')?.addEventListener('load', function() {
            initChart();
            renderChart();
            initComparisonChart();
            renderComparisonChart();
        });
    }
})();
</script>
@endsection

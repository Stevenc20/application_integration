@extends('layouts.supervisor')

@section('title', 'Production Analytics')

@section('content')
<style>
    /* ═══════════════════════════════════════════
       Production Analytics Design System
       Consistent premium palette:
       - Primary:   slate-800/900 (dark text/bg)
       - Accent:    #2563eb (blue-600)  
       - OK:        #059669 (emerald-600)
       - Repair:    #d97706 (amber-600)
       - Reject:    #dc2626 (red-600)
       - Overtime:  #dc2626 (red-600) w/ ring
       - Dandori:   #f59e0b (amber-500)

       - Production:#2563eb (blue-600)
    ═══════════════════════════════════════════ */
    .pa-page { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    
    .pa-tab {
        padding: 10px 22px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 0.02em;
        cursor: pointer;
        transition: all 0.25s cubic-bezier(.4,0,.2,1);
        border: 1.5px solid transparent;
        color: #64748b;
        background: white;
        text-transform: uppercase;
    }
    .pa-tab:hover { background: #f8fafc; color: #334155; border-color: #e2e8f0; }
    .pa-tab.active {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: white;
        border-color: transparent;
        box-shadow: 0 4px 14px rgba(30,41,59,0.25);
    }
    
    .pa-stat-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .pa-stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        border-radius: 14px 14px 0 0;
    }
    .pa-stat-card:hover { 
        box-shadow: 0 4px 16px rgba(0,0,0,0.08); 
        transform: translateY(-1px);
    }
    .pa-stat-card.stat-total::before { background: linear-gradient(90deg, #1e293b, #475569); }
    .pa-stat-card.stat-ok::before { background: linear-gradient(90deg, #059669, #10b981); }
    .pa-stat-card.stat-repair::before { background: linear-gradient(90deg, #d97706, #f59e0b); }
    .pa-stat-card.stat-reject::before { background: linear-gradient(90deg, #dc2626, #f87171); }
    .pa-stat-card.stat-achievement::before { background: linear-gradient(90deg, #2563eb, #60a5fa); }

    .job-card {
        transition: all 0.2s cubic-bezier(.4,0,.2,1);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .job-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        border-color: #cbd5e1;
    }

    .timeline-bar {
        height: 8px;
        border-radius: 999px;
        overflow: hidden;
        display: flex;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }
    .timeline-bar > div {
        height: 100%;
        transition: width 0.5s cubic-bezier(.4,0,.2,1);
    }

    .pa-filter-input {
        padding: 8px 12px;
        border-radius: 10px;
        border: 1.5px solid #e2e8f0;
        font-size: 13px;
        font-weight: 500;
        color: #334155;
        background: white;
        transition: all 0.2s ease;
        outline: none;
    }
    .pa-filter-input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
    }

    .pa-shift-btn {
        padding: 8px 20px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.05em;
        transition: all 0.25s cubic-bezier(.4,0,.2,1);
        text-transform: uppercase;
    }
    .pa-shift-btn.active {
        background: linear-gradient(135deg, #1e293b, #334155);
        color: white;
        box-shadow: 0 4px 12px rgba(30,41,59,0.25);
    }
    .pa-shift-btn:not(.active) {
        background: white;
        color: #94a3b8;
    }
    .pa-shift-btn:not(.active):hover {
        background: #f8fafc;
        color: #475569;
    }

    .pa-donut-ring {
        position: relative;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .pa-donut-ring .hole {
        position: absolute;
        inset: 3px;
        border-radius: 50%;
        background: white;
    }
    .pa-hist-btn {
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 8px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        transition: all 0.15s ease;
        white-space: nowrap;
    }
</style>

<div class="pa-page space-y-6">
    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight">Production Analytics</h1>
            <p class="text-slate-400 text-xs font-medium mt-0.5">Daily Input Dashboard — per-item detail & drill-down</p>
        </div>
    </div>

    {{-- FILTERS --}}
    <form method="GET" action="{{ route('analytics.production') }}" id="filterForm" class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Dari</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="pa-filter-input w-36" onchange="autoSubmit()">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Sampai</label>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="pa-filter-input w-36" onchange="autoSubmit()">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Line</label>
                <select name="line" class="pa-filter-input w-36" onchange="autoSubmit()">
                    <option value="">Semua Line</option>
                    @foreach($lines as $l)
                        <option value="{{ $l }}" {{ $line == $l ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Shift</label>
                <select name="shift" class="pa-filter-input w-28" onchange="autoSubmit()">
                    <option value="">Semua</option>
                    <option value="Shift Pagi" {{ $shift == 'Shift Pagi' ? 'selected' : '' }}>Pagi</option>
                    <option value="Shift Malam" {{ $shift == 'Shift Malam' ? 'selected' : '' }}>Malam</option>
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Status</label>
                <select name="status" class="pa-filter-input w-32" onchange="autoSubmit()">
                    <option value="">Semua Status</option>
                    <option value="running" {{ ($status ?? '') == 'running' ? 'selected' : '' }}>Running</option>
                    <option value="pending" {{ ($status ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="complete" {{ ($status ?? '') == 'complete' ? 'selected' : '' }}>Complete</option>
                </select>
            </div>
            <a href="{{ route('analytics.production', ['tab' => $tab, 'reset' => '1']) }}" class="px-4 py-2 rounded-lg border border-slate-200 text-slate-400 text-xs font-bold hover:bg-slate-50 hover:text-slate-600 hover:border-slate-300 transition-all">
                Reset
            </a>
        </div>
    </form>

    {{-- SHIFT TOGGLE --}}
    @if($tab === 'overview')
    <div class="bg-white rounded-xl border border-slate-200 p-1 shadow-sm flex items-center gap-1 w-fit">
        <a href="{{ route('analytics.production', array_merge(request()->query(), ['shift' => 'Shift Pagi'])) }}" 
           class="pa-shift-btn {{ ($shift ?? '') === 'Shift Pagi' ? 'active' : '' }}">
            Shift Pagi
        </a>
        <a href="{{ route('analytics.production', array_merge(request()->query(), ['shift' => 'Shift Malam'])) }}" 
           class="pa-shift-btn {{ ($shift ?? '') === 'Shift Malam' ? 'active' : '' }}">
            Shift Malam
        </a>
    </div>
    @endif

    {{-- TABS --}}
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('analytics.production', ['tab' => 'overview'] + request()->except('tab')) }}" class="pa-tab {{ $tab === 'overview' ? 'active' : '' }}">Overview</a>
        <a href="{{ route('analytics.production', ['tab' => 'more_detail'] + request()->except('tab')) }}" class="pa-tab {{ $tab === 'more_detail' ? 'active' : '' }}">More Detail</a>
        <a href="{{ route('analytics.production', ['tab' => 'timeline'] + request()->except('tab')) }}" class="pa-tab {{ $tab === 'timeline' ? 'active' : '' }}">Timeline Explorer</a>
        <a href="{{ route('analytics.production', ['tab' => 'history'] + request()->except('tab')) }}" class="pa-tab {{ $tab === 'history' ? 'active' : '' }}">History</a>
    </div>

    {{-- TAB CONTENT --}}
    @if($tab === 'overview')
        @include('analytics._overview')
    @elseif($tab === 'more_detail')
        @include('analytics._more_detail')
    @elseif($tab === 'timeline')
        @include('analytics._timeline')
    @elseif($tab === 'history')
        @include('analytics._history')
    @endif
</div>

<script>
    let submitTimer;
    function autoSubmit() {
        clearTimeout(submitTimer);
        submitTimer = setTimeout(function() {
            document.getElementById('filterForm').submit();
        }, 400);
    }

    // Timeline zoom functions
    window.zoomLevels = window.zoomLevels || {};
    function zoomTimeline(id, delta) {
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
    }
    function resetZoomTimeline(id) {
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
    }

    // Timeline tooltip
    function showTimelineTooltip(e, label, from, to, duration, detail) {
        try {
            let el = document.getElementById('timeline-tooltip');
            if (!el) {
                el = document.createElement('div');
                el.id = 'timeline-tooltip';
                el.style.cssText = 'position:fixed;z-index:9999;pointer-events:none;background:#1e293b;color:white;border-radius:12px;padding:10px 14px;font-size:11px;font-weight:600;box-shadow:0 12px 32px rgba(0,0,0,0.3);border:1px solid #334155;max-width:280px;backdrop-filter:blur(8px);';
                document.body.appendChild(el);
            }
            const lc = (label||'').toLowerCase();
            const lColor = lc.includes('dandori') ? '#fbbf24' : lc.includes('1st') ? '#a855f7' : lc.includes('production') ? '#60a5fa' : lc.includes('overtime') ? '#f87171' : lc.includes('out') ? '#f97316' : lc.includes('break') ? '#6366f1' : '#f87171';
            el.innerHTML = '<div style="font-weight:800;font-size:10px;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:5px;color:' + lColor + ';">' + label + '</div>'
                + '<div style="display:flex;gap:12px;font-size:10px;color:#94a3b8;">'
                + '<span>' + from + ' → ' + to + '</span>'
                + '<span style="color:white;font-weight:700;">' + duration + '</span>'
                + '</div>'
                + (detail ? '<div style="font-size:9px;color:#64748b;margin-top:4px;border-top:1px solid #334155;padding-top:4px;">' + detail + '</div>' : '');
            el.style.display = 'block';
            let x = e.clientX + 14;
            let y = e.clientY - 12;
            if (x + 290 > window.innerWidth) x = e.clientX - 290;
            if (y < 0) y = e.clientY + 20;
            el.style.left = x + 'px';
            el.style.top = y + 'px';
        } catch(err) {
            console.warn('Tooltip error:', err);
        }
    }
    function hideTimelineTooltip() {
        let el = document.getElementById('timeline-tooltip');
        if (el) el.style.display = 'none';
    }
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
Chart.register(ChartDataLabels);
</script>
@endsection

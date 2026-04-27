@extends('layouts.layouts')

@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

* { box-sizing: border-box; }

.db {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: #f5f6fa;
    min-height: 100vh;
    padding: 28px 24px;
    color: #1a1d23;
}

.card {
    background: #fff;
    border-radius: 14px;
    padding: 22px 24px;
    border: 1px solid #eaecf0;
    margin-bottom: 20px;
}

.sec-title {
    font-size: 12px;
    font-weight: 600;
    color: #9298a4;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 16px;
}

.kpi { background: #f9fafb; border: 1px solid #eaecf0; border-radius: 12px; padding: 18px; }
.kpi-label { font-size: 12px; color: #9298a4; font-weight: 500; margin-bottom: 8px; }
.kpi-value { font-size: 26px; font-weight: 700; line-height: 1; }
.kpi-sub   { font-size: 11px; color: #b0b5c0; margin-top: 5px; }

.c-blue   { color: #3b7bff; }
.c-green  { color: #22c55e; }
.c-red    { color: #f04438; }
.c-amber  { color: #f79009; }
.c-purple { color: #7c3aed; }
.c-gray   { color: #6b7280; }

.track { background: #f0f1f5; border-radius: 99px; height: 5px; margin-top: 10px; overflow: hidden; }
.track-fill { height: 100%; border-radius: 99px; }

.pill { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; }
.pill-green  { background: #dcfce7; color: #16a34a; }
.pill-red    { background: #fee2e2; color: #dc2626; }
.pill-amber  { background: #fef3c7; color: #d97706; }
.pill-blue   { background: #dbeafe; color: #2563eb; }
.pill-gray   { background: #f3f4f6; color: #6b7280; }

.alert { display: flex; align-items: center; gap: 10px; padding: 11px 14px; border-radius: 10px; font-size: 13px; font-weight: 500; margin-bottom: 8px; }
.alert-warn { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
.alert-ok   { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }

.line-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; }
@media(max-width:640px){ .line-grid { grid-template-columns: repeat(2,1fr); } }

.line-card { border-radius: 12px; padding: 18px 14px; text-align: center; border: 1px solid #eaecf0; }
.lc-green { background: #f0fdf4; border-color: #bbf7d0; }
.lc-red   { background: #fff5f5; border-color: #fecaca; }
.lc-amber { background: #fffbeb; border-color: #fde68a; }

.dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; margin-right: 4px; vertical-align: middle; }
.dot-green { background: #22c55e; }
.dot-red   { background: #f04438; }
.dot-amber { background: #f79009; }

.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 14px; align-items: end; }
.f-label { font-size: 12px; font-weight: 500; color: #6b7280; margin-bottom: 5px; display: block; }
.f-input { width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 9px 12px; font-size: 13px; font-family: 'Plus Jakarta Sans', sans-serif; background: #fff; color: #1a1d23; transition: border-color .2s; }
.f-input:focus { outline: none; border-color: #3b7bff; box-shadow: 0 0 0 3px rgba(59,123,255,.1); }

.btn-apply { background: #1a1d23; color: #fff; border: none; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: background .2s; width: 100%; }
.btn-apply:hover { background: #374151; }
.btn-reset { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; border-radius: 8px; padding: 9px 18px; font-size: 13px; font-weight: 600; font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; text-align: center; display: block; text-decoration: none; transition: background .2s; width: 100%; }
.btn-reset:hover { background: #e5e7eb; }

.col2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
@media(max-width:640px){ .col2 { grid-template-columns: 1fr; } }

.g3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; }
.g4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; }
@media(max-width:700px){ .g3 { grid-template-columns: repeat(2,1fr); } .g4 { grid-template-columns: repeat(2,1fr); } }

.big-track { background: #f0f1f5; border-radius: 99px; height: 20px; overflow: hidden; }
.big-fill { height: 100%; border-radius: 99px; background: linear-gradient(90deg, #3b7bff, #22c55e); display: flex; align-items: center; padding-left: 10px; transition: width 1s ease; }

.tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.tbl th { text-align: left; padding: 10px 14px; font-size: 11px; font-weight: 600; color: #9298a4; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid #eaecf0; white-space: nowrap; background: #fafafa; }
.tbl td { padding: 11px 14px; border-bottom: 1px solid #f3f4f6; color: #374151; white-space: nowrap; }
.tbl tr:last-child td { border-bottom: none; }
.tbl tr:hover td { background: #fafafa; }

.modal-bg { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.25); backdrop-filter: blur(4px); align-items: center; justify-content: center; z-index: 50; }
.modal-box { background: #fff; border-radius: 18px; padding: 28px; width: 480px; max-width: 95vw; box-shadow: 0 20px 60px rgba(0,0,0,.1); }

.btn-detail { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px 14px; font-size: 12px; font-weight: 600; font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: all .2s; }
.btn-detail:hover { background: #e5e7eb; }
</style>

<div class="db">

    {{-- HEADER --}}
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:24px;">
        <div>
            <h1 style="font-size:22px; font-weight:700; margin:0 0 4px;">Production Overview</h1>
            <p style="font-size:13px; color:#9298a4; margin:0;">{{ now()->format('l, d F Y') }}</p>
        </div>
        <div style="text-align:right;">
            <p style="font-size:11px; color:#9298a4; margin:0 0 2px;">Live Time</p>
            <p id="liveClock" style="font-size:22px; font-weight:700; color:#3b7bff; margin:0;"></p>
        </div>
    </div>

    {{-- SHIFT + ALERTS --}}
    <div class="col2">
        <div class="card" style="margin-bottom:0;">
            <p class="sec-title">Shift Status</p>
            @php $nowTime = now()->format('H:i'); @endphp
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <p style="font-size:12px; color:#9298a4; margin:0 0 6px;">Summary</p>
                    @if($isOvertime && $shift == 1)
                        <span class="pill pill-red">Shift 1 Selesai</span>
                    @elseif($shift == 2 && $nowTime < $shiftStart)
                        <span class="pill pill-red">Shift 1 Selesai</span>
                    @else
                        <span class="pill pill-green">⚙ Shift {{ $shift }} Berjalan</span>
                    @endif
                </div>
                <div style="text-align:right;">
                    <p style="font-size:12px; color:#9298a4; margin:0 0 6px;">Realtime</p>
                    @if($isBreak)
                        <span class="pill pill-blue">☕ Break {{ $breakStart }}–{{ $breakEnd }}</span>
                    @elseif($isOvertime)
                        <span class="pill pill-amber">⚠ Overtime</span>
                    @elseif($nowTime >= $shiftStart && $nowTime <= $shiftEnd)
                        <span class="pill pill-green">Normal Operation</span>
                    @else
                        <span class="pill pill-gray">Waiting Shift</span>
                    @endif
                    <p id="shiftRealtimeStatus" style="font-size:12px; font-weight:600; color:#3b7bff; margin:8px 0 0;"></p>
                    <p id="remainingTime" style="font-size:12px; color:#9298a4; margin:2px 0 0;"></p>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:0;">
            <p class="sec-title">Production Alerts</p>
            @if($achievementPercent < 80)
                <div class="alert alert-warn">⚠ Production behind target ({{ $achievementPercent }}%)</div>
            @endif
            @if($rejectRate > 5)
                <div class="alert alert-warn">⚠ Reject rate tinggi — {{ $rejectRate }}%</div>
            @endif
            @if($activeDowntime > 0)
                <div class="alert alert-warn">⚠ Machine downtime aktif</div>
            @endif
            @if($openAbnormality > 0)
                <div class="alert alert-warn">⚠ {{ $openAbnormality }} abnormality belum ditutup</div>
            @endif
            @if($achievementPercent >= 80 && $rejectRate <= 5 && $activeDowntime == 0 && $openAbnormality == 0)
                <div class="alert alert-ok">✔ Semua sistem berjalan normal</div>
            @endif
        </div>
    </div>

    {{-- LINE MONITORING --}}
    <div class="card">
        <p class="sec-title">Line Monitoring</p>
        <div class="line-grid">
            <div class="line-card lc-green">
                <p style="font-size:11px; font-weight:600; color:#9298a4; margin:0 0 6px;">Line A</p>
                <p style="font-size:16px; font-weight:700;" class="c-green"><span class="dot dot-green"></span>Running</p>
            </div>
            <div class="line-card lc-red">
                <p style="font-size:11px; font-weight:600; color:#9298a4; margin:0 0 6px;">Line B</p>
                <p style="font-size:16px; font-weight:700;" class="c-red"><span class="dot dot-red"></span>Stop</p>
            </div>
            <div class="line-card lc-amber">
                <p style="font-size:11px; font-weight:600; color:#9298a4; margin:0 0 6px;">Line C</p>
                <p style="font-size:16px; font-weight:700;" class="c-amber"><span class="dot dot-amber"></span>Setup</p>
            </div>
            <div class="line-card lc-green">
                <p style="font-size:11px; font-weight:600; color:#9298a4; margin:0 0 6px;">Line D</p>
                <p style="font-size:16px; font-weight:700;" class="c-green"><span class="dot dot-green"></span>Running</p>
            </div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card">
        <p class="sec-title">Filter</p>
        <form method="GET" action="{{ route('supervisor.dashboard') }}">
            <div class="filter-grid">
                <div>
                    <label class="f-label">Date From</label>
                    <input type="date" name="date_from" class="f-input" value="{{ request('date_from', now()->toDateString()) }}">
                </div>
                <div>
                    <label class="f-label">Date To</label>
                    <input type="date" name="date_to" class="f-input" value="{{ request('date_to', now()->toDateString()) }}">
                </div>
                <div>
                    <label class="f-label">Process</label>
                    <select name="process_type" class="f-input">
                        <option value="">All</option>
                        <option value="Stamping"    {{ request('process_type')=='Stamping'?'selected':'' }}>Stamping</option>
                        <option value="Sub Assy"    {{ request('process_type')=='Sub Assy'?'selected':'' }}>Sub Assy</option>
                        <option value="Shearing"    {{ request('process_type')=='Shearing'?'selected':'' }}>Shearing</option>
                        <option value="Metal Finish" {{ request('process_type')=='Metal Finish'?'selected':'' }}>Metal Finish</option>
                    </select>
                </div>
                <div>
                    <label class="f-label">Shift</label>
                    <select name="shift" class="f-input">
                        <option value="">All</option>
                        <option value="Shift 1" {{ request('shift')=='Shift 1'?'selected':'' }}>Shift 1</option>
                        <option value="Shift 2" {{ request('shift')=='Shift 2'?'selected':'' }}>Shift 2</option>
                        <option value="Shift 3" {{ request('shift')=='Shift 3'?'selected':'' }}>Shift 3</option>
                    </select>
                </div>
                <div>
                    <label class="f-label">Order</label>
                    <input type="text" name="order" class="f-input" value="{{ request('order') }}" placeholder="Search order...">
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit" class="btn-apply" style="flex:1;">Apply</button>
                    <a href="{{ route('supervisor.dashboard') }}" class="btn-reset" style="flex:1;">Reset</a>
                </div>
            </div>
        </form>
    </div>

    {{-- PERFORMANCE + OUTPUT --}}
    <div class="col2">
        <div class="card" style="margin-bottom:0;">
            <p class="sec-title">Production Performance</p>
            <div class="g3">
                <div class="kpi">
                    <p class="kpi-label">Target</p>
                    <p class="kpi-value c-blue">{{ number_format($targetQty ?? 0) }}</p>
                    <p class="kpi-sub">pcs</p>
                </div>
                <div class="kpi">
                    <p class="kpi-label">Achievement</p>
                    <p class="kpi-value {{ ($achievementPercent??0) >= 80 ? 'c-green' : 'c-red' }}">{{ $achievementPercent ?? 0 }}%</p>
                    <p class="kpi-sub">rate</p>
                </div>
                <div class="kpi">
                    <p class="kpi-label">Gap</p>
                    <p class="kpi-value c-amber">{{ number_format($gap ?? 0) }}</p>
                    <p class="kpi-sub">pcs</p>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:0;">
            <p class="sec-title">Production Output</p>
            <div class="g4">
                <div class="kpi">
                    <p class="kpi-label">OK</p>
                    <p class="kpi-value c-green">{{ number_format($totalOk) }}</p>
                    <p class="kpi-sub">{{ $okPercent }}%</p>
                    <div class="track"><div class="track-fill" style="width:{{ $okPercent }}%;background:#22c55e;"></div></div>
                </div>
                <div class="kpi">
                    <p class="kpi-label">Repair</p>
                    <p class="kpi-value c-amber">{{ number_format($totalRepair) }}</p>
                    <p class="kpi-sub">{{ $repairPercent }}%</p>
                    <div class="track"><div class="track-fill" style="width:{{ $repairPercent }}%;background:#f79009;"></div></div>
                </div>
                <div class="kpi">
                    <p class="kpi-label">Reject</p>
                    <p class="kpi-value c-red">{{ number_format($totalReject) }}</p>
                    <p class="kpi-sub">{{ $rejectPercent }}%</p>
                    <div class="track"><div class="track-fill" style="width:{{ $rejectPercent }}%;background:#f04438;"></div></div>
                </div>
                <div class="kpi">
                    <p class="kpi-label">Reject Rate</p>
                    <p class="kpi-value c-purple">{{ $rejectRate ?? 0 }}%</p>
                    <p class="kpi-sub">KPI</p>
                    <div class="track"><div class="track-fill" style="width:{{ $rejectRate ?? 0 }}%;background:#7c3aed;"></div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- CONTROL + MONITORING --}}
    <div class="col2">
        <div class="card" style="margin-bottom:0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <p class="sec-title" style="margin-bottom:0;">Production Control</p>
                <button onclick="openControlModal()" class="btn-detail">Detail</button>
            </div>
            <div class="g3">
                <div class="kpi">
                    <p class="kpi-label">Remaining</p>
                    <p class="kpi-value c-blue">{{ $remainingHours ?? 0 }}h</p>
                    <p class="kpi-sub">eff. time</p>
                </div>
                <div class="kpi">
                    <p class="kpi-label">Current</p>
                    <p class="kpi-value c-green">{{ $currentSpeed ?? 0 }}/h</p>
                    <p class="kpi-sub">speed</p>
                </div>
                <div class="kpi">
                    <p class="kpi-label">Required</p>
                    <p class="kpi-value c-red">{{ $requiredSpeed ?? 0 }}/h</p>
                    <p class="kpi-sub">min speed</p>
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom:0;">
            <p class="sec-title">Monitoring</p>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div class="kpi">
                    <p class="kpi-label">Abnormality</p>
                    <p class="kpi-value c-purple">{{ $openAbnormality ?? 0 }}</p>
                    <p class="kpi-sub">{{ ($openAbnormality??0) > 0 ? 'Open' : 'Clear' }}</p>
                </div>
                <div class="kpi">
                    <p class="kpi-label">Downtime</p>
                    <p class="kpi-value c-red">{{ $activeDowntime ?? 0 }}</p>
                    <p class="kpi-sub">{{ ($activeDowntime??0) > 0 ? 'Active' : 'None' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- PROGRESS --}}
    @php
        $progress = $targetQty > 0 ? ($totalOk / $targetQty) * 100 : 0;
        $progText = match(true) {
            $progress >= 100 => "Target tercapai",
            $progress >= 80  => "Produksi on track",
            $progress >= 50  => "Produksi berjalan",
            default          => "Di bawah target",
        };
    @endphp

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <p class="sec-title" style="margin-bottom:0;">Production Progress</p>
            <span style="font-size:15px; font-weight:700;">{{ round($progress,1) }}%</span>
        </div>
        <div class="big-track">
            <div class="big-fill" style="width:{{ min($progress,100) }}%;">
                @if($progress > 10)
                    <span style="font-size:11px; font-weight:700; color:#fff;">{{ round($progress,1) }}%</span>
                @endif
            </div>
        </div>
        <div style="display:flex; justify-content:space-between; margin-top:10px; font-size:12px; color:#9298a4;">
            <span>{{ $progText }}</span>
            <span>Target: {{ number_format($targetQty??0) }} &nbsp;|&nbsp; Actual: {{ number_format($totalOk??0) }}</span>
        </div>
    </div>

    {{-- CHART --}}
    <div class="card">
        <p class="sec-title">Production Trend</p>
        <div style="height:280px; position:relative;">
            <canvas id="productionChart"></canvas>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="card">
        <p class="sec-title">Recent Production</p>
        <div style="overflow-x:auto;">
            <table class="tbl">
                <thead>
                    <tr>
                        <th>Time</th><th>Pro Number</th><th>Job Number</th><th>Part Name</th>
                        <th>Process</th><th>Shift</th><th>OK</th><th>Repair</th><th>Reject</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($latestProductions as $p)
                    <tr>
                        <td style="color:#9298a4;">{{ $p->created_at->format('H:i') }}</td>
                        <td>{{ $p->production_order_number }}</td>
                        <td style="color:#9298a4;">{{ $p->job->job_number ?? '-' }}</td>
                        <td style="font-weight:600;">{{ $p->job->job_name ?? '-' }}</td>
                        <td>{{ $p->process_type }}</td>
                        <td style="color:#9298a4;">{{ $p->shift }}</td>
                        <td style="font-weight:600;" class="c-green">{{ $p->qty_ok }}</td>
                        <td style="font-weight:600;" class="c-amber">{{ $p->qty_repair }}</td>
                        <td style="font-weight:600;" class="c-red">{{ $p->qty_reject }}</td>
                        <td>
                            @if($p->status == 'approved')
                                <span class="pill pill-green">Approved</span>
                            @elseif($p->status == 'rejected')
                                <span class="pill pill-red">Rejected</span>
                            @else
                                <span class="pill pill-amber">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="text-align:center; padding:32px; color:#9298a4;">Tidak ada data produksi</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($latestProductions->hasPages())
            <div style="margin-top:16px; display:flex; justify-content:center;">{{ $latestProductions->links() }}</div>
        @endif
    </div>

    {{-- MODAL --}}
    <div id="controlModal" class="modal-bg">
        <div class="modal-box">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="font-size:17px; font-weight:700; margin:0;">Production Control Detail</h3>
                <button onclick="closeControlModal()" style="background:none; border:none; font-size:20px; color:#9298a4; cursor:pointer; line-height:1;">✕</button>
            </div>

            <div style="background:#f9fafb; border:1px solid #eaecf0; border-radius:10px; padding:16px; margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div>
                        <p style="font-size:12px; color:#9298a4; margin:0 0 6px;">Status</p>
                        @if($status == 'on_track')
                            <span class="pill pill-green">● On Track</span>
                        @elseif($status == 'behind')
                            <span class="pill pill-red">● Behind Speed</span>
                        @else
                            <span class="pill pill-gray">● Waiting Data</span>
                        @endif
                    </div>
                    <div style="text-align:right;">
                        <p style="font-size:12px; color:#9298a4; margin:0 0 4px;">Current Speed</p>
                        <p style="font-size:22px; font-weight:700; margin:0;">{{ $currentSpeed ?? '-' }}/h</p>
                    </div>
                </div>
            </div>

            <div class="g3" style="margin-bottom:14px;">
                <div class="kpi" style="text-align:center;">
                    <p class="kpi-label">Target</p>
                    <p class="kpi-value c-blue">{{ number_format($targetQty ?? 0) }}</p>
                </div>
                <div class="kpi" style="text-align:center;">
                    <p class="kpi-label">Actual</p>
                    <p class="kpi-value c-green">{{ number_format($totalOk ?? 0) }}</p>
                </div>
                <div class="kpi" style="text-align:center;">
                    <p class="kpi-label">Remaining</p>
                    <p class="kpi-value c-amber">{{ max(($targetQty??0)-($totalOk??0),0) }}</p>
                </div>
            </div>

            <div class="g3">
                <div class="kpi" style="text-align:center;">
                    <p class="kpi-label">Time Left</p>
                    <p class="kpi-value c-blue">{{ $remainingHours ?? 0 }}h</p>
                </div>
                <div class="kpi" style="text-align:center;">
                    <p class="kpi-label">Current</p>
                    <p class="kpi-value c-green">{{ $currentSpeed ?? 0 }}/h</p>
                </div>
                <div class="kpi" style="text-align:center;">
                    <p class="kpi-label">Required</p>
                    <p class="kpi-value c-red">{{ $requiredSpeed ?? 0 }}/h</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    function tick() {
        document.getElementById("liveClock").innerText =
            new Date().toLocaleTimeString('id-ID', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
    }
    setInterval(tick, 1000); tick();

    const ctx = document.getElementById('productionChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Expected',
                        data: @json($expectedProduction),
                        borderColor: '#3b7bff',
                        backgroundColor: 'rgba(59,123,255,0.07)',
                        borderWidth: 2, tension: 0.4, pointRadius: 3,
                        pointBackgroundColor: '#3b7bff', fill: true,
                    },
                    {
                        label: 'Actual',
                        data: @json($actualProduction),
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34,197,94,0.07)',
                        borderWidth: 2, tension: 0.4, pointRadius: 3,
                        pointBackgroundColor: '#22c55e', fill: true,
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color:'#9298a4', font:{ size:12 }, boxWidth:10 } },
                    tooltip: {
                        backgroundColor:'#fff', borderColor:'#eaecf0', borderWidth:1,
                        titleColor:'#1a1d23', bodyColor:'#6b7280', padding:10,
                        displayColors: false,
                    }
                },
                scales: {
                    x: { ticks:{ color:'#b0b5c0', font:{size:11} }, grid:{ color:'#f3f4f6' } },
                    y: { ticks:{ color:'#b0b5c0', font:{size:11} }, grid:{ color:'#f3f4f6' } }
                }
            }
        });
    }
});

function openControlModal() {
    document.getElementById('controlModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeControlModal() {
    document.getElementById('controlModal').style.display = 'none';
    document.body.style.overflow = '';
}

const bellSound = new Audio("https://www.soundjay.com/buttons/sounds/beep-07.mp3");
let lastState = "";

function parseDate(str) {
    if (!str) return new Date();
    return new Date(str.replace(' ', 'T'));
}
function fmt(ms) {
    if (ms <= 0) return "0h 0m 0s";
    const s = Math.floor(ms/1000);
    return `${Math.floor(s/3600)}h ${Math.floor((s%3600)/60)}m ${s%60}s`;
}
function updateRealtime() {
    const now = new Date();
    let s = parseDate("{{ $shiftStartFull ?? now() }}");
    let e = parseDate("{{ $shiftEndFull ?? now() }}");
    if (e < s) e.setDate(e.getDate() + 1);

    let status = "", remaining = 0;
    if (now < s) { status = "⏳ Menunggu shift"; remaining = s - now; }
    else if (now <= e) {
        @if($isOvertime)
            status = "⚠ Overtime Shift {{ $shift }}";
        @else
            status = "⚙ Shift {{ $shift }} berjalan";
        @endif
        remaining = e - now;
    } else {
        @if($shift == 2)
            status = "⚙ Shift 2 berjalan"; remaining = e - now;
        @else
            status = "⏱ Shift selesai";
        @endif
    }

    const el = document.getElementById("shiftRealtimeStatus");
    const rm = document.getElementById("remainingTime");
    if (el) el.innerText = status;
    if (rm) rm.innerText = "Sisa: " + fmt(remaining);
    if (status !== lastState) { bellSound.play().catch(()=>{}); lastState = status; }
}
setInterval(updateRealtime, 1000);
updateRealtime();
</script>
@endsection
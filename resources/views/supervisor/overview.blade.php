@extends('layouts.supervisor')

@section('title', 'Production Overview')

@section('content')
<div class="space-y-6">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-gray-200 rounded-2xl px-6 py-5 shadow-sm border-l-4 border-l-red-500">
        <div>
            <h1 class="text-2xl font-black text-gray-800 uppercase tracking-tight leading-tight">Production Overview</h1>
            <p class="text-sm text-gray-500 mt-1 font-medium">{{ now()->format('l, d F Y') }}</p>
        </div>
        <div class="flex flex-row sm:flex-col items-center sm:items-end gap-4 sm:gap-1 shrink-0">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">Live System Time</div>
            <div id="liveClock" class="text-3xl font-black text-red-500 tracking-widest tabular-nums">--:--:--</div>
        </div>
    </div>

    {{-- SHIFT + ALERTS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 relative overflow-hidden">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-500 flex items-center justify-center">
                    <i class="bx bx-time-five text-lg"></i>
                </div>
                <h2 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Shift Operational Status</h2>
            </div>
            
            <div class="flex justify-between items-end">
                <div>
                    @php $nowTime = now()->format('H:i'); @endphp
                    @if($isOvertime && $shift == 1)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-100 text-red-600 text-xs font-bold ring-4 ring-red-50">Shift 1 Selesai</span>
                    @elseif($shift == 2 && $nowTime < $shiftStart)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-100 text-red-600 text-xs font-bold ring-4 ring-red-50">Shift 1 Selesai</span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-green-100 text-green-600 text-xs font-bold ring-4 ring-green-50">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> Shift {{ $shift }} Berjalan
                        </span>
                    @endif
                    <div id="shiftRealtimeStatus" class="mt-3 text-lg font-bold text-gray-800">Menarik data...</div>
                </div>
                <div class="text-right">
                    <div id="remainingTime" class="text-2xl font-black text-gray-700 tabular-nums">00:00:00</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Sisa Waktu Operasional</div>
                </div>
            </div>

            @if($isBreak)
            <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-xl flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center text-blue-500 shadow-sm">☕</div>
                <div>
                    <div class="text-xs font-bold text-blue-800">Waktu Istirahat (Break)</div>
                    <div class="text-[10px] text-blue-600 font-medium">{{ $breakStart }} – {{ $breakEnd }}</div>
                </div>
            </div>
            @endif
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-500 flex items-center justify-center">
                    <i class="bx bx-bell text-lg"></i>
                </div>
                <h2 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Production Alerts</h2>
            </div>
            
            <div class="space-y-2">
                @if($achievementPercent < 80)
                    <div class="flex items-center gap-3 p-3 bg-red-50 border border-red-100 rounded-xl text-red-700 text-xs font-bold">
                        <i class="bx bx-trending-down text-lg"></i> Production behind target ({{ $achievementPercent }}%)
                    </div>
                @endif
                @if($rejectRate > 5)
                    <div class="flex items-center gap-3 p-3 bg-red-50 border border-red-100 rounded-xl text-red-700 text-xs font-bold">
                        <i class="bx bx-error text-lg"></i> High reject rate detected: {{ $rejectRate }}%
                    </div>
                @endif
                @if($activeDowntime > 0)
                    <div class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-100 rounded-xl text-amber-700 text-xs font-bold">
                        <i class="bx bx-stop-circle text-lg"></i> Machine downtime active on some lines
                    </div>
                @endif
                @if($openAbnormality > 0)
                    <div class="flex items-center gap-3 p-3 bg-amber-50 border border-amber-100 rounded-xl text-amber-700 text-xs font-bold">
                        <i class="bx bx-info-circle text-lg"></i> {{ $openAbnormality }} open abnormalities pending
                    </div>
                @endif
                @if($achievementPercent >= 80 && $rejectRate <= 5 && $activeDowntime == 0 && $openAbnormality == 0)
                    <div class="flex items-center gap-3 p-3 bg-green-50 border border-green-100 rounded-xl text-green-700 text-xs font-bold">
                        <i class="bx bx-check-double text-lg"></i> All systems operating within normal parameters
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- LINE MONITORING --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-500 flex items-center justify-center">
                <i class="bx bx-desktop text-lg"></i>
            </div>
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Real-time Line Status</h2>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-green-50 border border-green-100 text-center">
                <div class="text-[10px] font-bold text-green-600 uppercase tracking-widest mb-2">Line A</div>
                <div class="flex items-center justify-center gap-2 text-green-700 font-black">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> RUNNING
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-red-50 border border-red-100 text-center">
                <div class="text-[10px] font-bold text-red-600 uppercase tracking-widest mb-2">Line B</div>
                <div class="flex items-center justify-center gap-2 text-red-700 font-black">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span> STOPPED
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-100 text-center">
                <div class="text-[10px] font-bold text-amber-600 uppercase tracking-widest mb-2">Line C</div>
                <div class="flex items-center justify-center gap-2 text-amber-700 font-black">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span> SETUP
                </div>
            </div>
            <div class="p-4 rounded-2xl bg-green-50 border border-green-100 text-center">
                <div class="text-[10px] font-bold text-green-600 uppercase tracking-widest mb-2">Line D</div>
                <div class="flex items-center justify-center gap-2 text-green-700 font-black">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span> RUNNING
                </div>
            </div>
        </div>
    </div>

    {{-- KPI GRID --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- PERFORMANCE --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Production Performance</h2>
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Target</div>
                    <div class="text-xl font-black {{ $targetQty > 0 ? 'text-blue-600' : 'text-gray-400' }}">{{ $targetQty > 0 ? number_format($targetQty) : 'Not Set' }}</div>
                    <div class="text-[10px] text-gray-400">pcs</div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Achieved</div>
                    <div class="text-xl font-black {{ ($achievementPercent??0) >= 80 ? 'text-green-600' : 'text-red-600' }}">{{ $achievementPercent ?? 0 }}%</div>
                    <div class="text-[10px] text-gray-400">rate</div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Gap</div>
                    <div class="text-xl font-black text-amber-600">{{ number_format($gap ?? 0) }}</div>
                    <div class="text-[10px] text-gray-400">pcs</div>
                </div>
            </div>
        </div>

        {{-- OUTPUT --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Quality Output</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 relative overflow-hidden">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">OK Qty</span>
                        <span class="text-xs font-black text-green-600">{{ $okPercent }}%</span>
                    </div>
                    <div class="text-xl font-black text-gray-800">{{ number_format($totalOk) }}</div>
                    <div class="w-full bg-gray-200 h-1 rounded-full mt-2">
                        <div class="bg-green-500 h-1 rounded-full" style="width: {{ $okPercent }}%"></div>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 relative overflow-hidden">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Reject Rate</span>
                        <span class="text-xs font-black text-red-600">{{ $rejectRate ?? 0 }}%</span>
                    </div>
                    <div class="text-xl font-black text-gray-800">{{ number_format($totalReject) }}</div>
                    <div class="w-full bg-gray-200 h-1 rounded-full mt-2">
                        <div class="bg-red-500 h-1 rounded-full" style="width: {{ $rejectRate ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PROGRESS BAR --}}
    @php
        $progress = $targetQty > 0 ? ($totalOk / $targetQty) * 100 : 0;
        $progColor = $progress >= 80 ? 'bg-green-500' : ($progress >= 50 ? 'bg-amber-500' : 'bg-red-500');
    @endphp
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Overall Production Progress</h2>
            <span class="text-lg font-black text-gray-800">{{ round($progress, 1) }}%</span>
        </div>
        <div class="w-full bg-gray-100 h-4 rounded-full p-1 border border-gray-200">
            <div class="h-2 rounded-full {{ $targetQty > 0 ? $progColor : 'bg-gray-300' }} transition-all duration-1000" style="width: {{ $targetQty > 0 ? min($progress, 100) : 0 }}%"></div>
        </div>
        <div class="flex justify-between mt-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
            <span>Target: {{ $targetQty > 0 ? number_format($targetQty) : '-' }}</span>
            <span>Actual: {{ number_format($totalOk??0) }}</span>
        </div>
        @if($targetQty <= 0)
            <div class="mt-4 p-3 bg-amber-50 border border-amber-100 rounded-xl flex items-center gap-2 text-[10px] font-bold text-amber-700">
                <i class="bx bx-info-circle text-sm"></i>
                Belum ada target yang diatur untuk hari ini. Silakan atur di menu Production Plan.
            </div>
        @endif
    </div>

    {{-- CHART CARD --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-500 flex items-center justify-center">
                    <i class="bx bx-stats text-lg"></i>
                </div>
                <h2 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Production Trend Analysis</h2>
            </div>
            <div class="flex items-center gap-4">
                <div id="legendExpected" onclick="toggleDataset(0)" class="flex items-center gap-1.5 cursor-pointer hover:opacity-70 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    <span class="text-[10px] font-bold text-gray-500 lg:text-gray-700">Expected</span>
                </div>
                <div id="legendActual" onclick="toggleDataset(1)" class="flex items-center gap-1.5 cursor-pointer hover:opacity-70 transition-opacity">
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                    <span class="text-[10px] font-bold text-gray-500 lg:text-gray-700">Actual</span>
                </div>
            </div>
        </div>
        <div class="h-[300px] w-full">
            <canvas id="productionChart"></canvas>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Recent Production Logs</h2>
            <button class="px-3 py-1 bg-white border border-gray-200 rounded-lg text-[10px] font-bold text-gray-600 hover:bg-gray-50">View All</button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/30">
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Time</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Job Info</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100">Process</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Shift</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center text-green-600">OK</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center text-red-600">NG</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-100 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($latestProductions as $p)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="text-xs font-bold text-gray-400">{{ $p->created_at->format('H:i') }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-black text-gray-800">{{ $p->job->job_name ?? '-' }}</div>
                            <div class="text-[10px] font-bold text-gray-400">{{ $p->job->job_number ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[10px] font-bold uppercase tracking-widest">{{ $p->process_type }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-xs font-bold text-gray-600">{{ $p->shift }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-black text-green-600">{{ number_format($p->qty_ok) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-black text-red-600">{{ number_format($p->qty_reject) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($p->status == 'approved')
                                <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-600 text-[9px] font-black uppercase tracking-widest">Approved</span>
                            @elseif($p->status == 'rejected')
                                <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-[9px] font-black uppercase tracking-widest">Rejected</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-600 text-[9px] font-black uppercase tracking-widest">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm font-medium italic">No production logs recorded for this period</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let myChart; // Global ref

function toggleDataset(index) {
    if(!myChart) return;
    const meta = myChart.getDatasetMeta(index);
    meta.hidden = (meta.hidden === null) ? !myChart.data.datasets[index].hidden : null;
    
    // Update legend style
    const el = index === 0 ? document.getElementById('legendExpected') : document.getElementById('legendActual');
    if(meta.hidden) {
        el.classList.add('opacity-40', 'line-through');
    } else {
        el.classList.remove('opacity-40', 'line-through');
    }
    
    myChart.update();
}

document.addEventListener("DOMContentLoaded", function () {
    // 24H Clock Logic
    function pad(n) { return String(n).padStart(2, '0'); }
    function tick() {
        const now = new Date();
        const el = document.getElementById("liveClock");
        if(el) el.innerText = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
    }
    setInterval(tick, 1000); tick();

    const ctx = document.getElementById('productionChart');
    if (ctx) {
        myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($chartLabels),
                datasets: [
                    {
                        label: 'Expected',
                        data: @json($expectedProduction),
                        borderColor: '#3b7bff',
                        backgroundColor: 'rgba(59,123,255,0.05)',
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#3b7bff',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        fill: true,
                    },
                    {
                        label: 'Actual',
                        data: @json($actualProduction),
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34,197,94,0.05)',
                        borderWidth: 3,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#22c55e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        fill: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1f2937',
                        titleFont: { size: 12, weight: 'bold' },
                        bodyFont: { size: 11 },
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    x: { 
                        ticks: { color: '#9ca3af', font: { size: 10, weight: 'bold' } },
                        grid: { display: false }
                    },
                    y: { 
                        ticks: { color: '#9ca3af', font: { size: 10, weight: 'bold' } },
                        grid: { color: '#f3f4f6' }
                    }
                }
            }
        });
    }

    // Realtime Shift Logic
    const bellSound = new Audio("https://www.soundjay.com/buttons/sounds/beep-07.mp3");
    let lastState = "";

    function parseDate(str) {
        if (!str) return new Date();
        return new Date(str.replace(' ', 'T'));
    }

    function fmt(ms) {
        if (ms <= 0) return "00:00:00";
        const s = Math.floor(ms/1000);
        const h = String(Math.floor(s/3600)).padStart(2,'0');
        const m = String(Math.floor((s%3600)/60)).padStart(2,'0');
        const sec = String(s%60).padStart(2,'0');
        return `${h}:${sec}:${sec}`; // Wait, h:m:sec
    }
    
    // Correction for fmt
    function formatTime(ms) {
        if (ms <= 0) return "00:00:00";
        const s = Math.floor(ms/1000);
        const hh = String(Math.floor(s/3600)).padStart(2,'0');
        const mm = String(Math.floor((s%3600)/60)).padStart(2,'0');
        const ss = String(s%60).padStart(2,'0');
        return `${hh}:${mm}:${ss}`;
    }

    function updateRealtime() {
        const now = new Date();
        let s = parseDate("{{ $shiftStartFull ?? now() }}");
        let e = parseDate("{{ $shiftEndFull ?? now() }}");
        if (e < s) e.setDate(e.getDate() + 1);

        let status = "", remaining = 0;
        if (now < s) { 
            status = "⏳ Menunggu Shift"; 
            remaining = s - now; 
        } else if (now <= e) {
            @if($isOvertime)
                status = "⚠ Overtime Shift {{ $shift }}";
            @else
                status = "⚙ Shift {{ $shift }} Berjalan";
            @endif
            remaining = e - now;
        } else {
            @if($shift == 2)
                status = "⚙ Shift 2 Berjalan"; remaining = e - now;
            @else
                status = "⏱ Shift Selesai";
            @endif
        }

        const el = document.getElementById("shiftRealtimeStatus");
        const rm = document.getElementById("remainingTime");
        if (el) el.innerText = status;
        if (rm) rm.innerText = formatTime(remaining);
        
        if (status !== lastState && lastState !== "") { 
            bellSound.play().catch(()=>{}); 
        }
        lastState = status;
    }
    
    setInterval(updateRealtime, 1000);
    updateRealtime();
});
</script>
@endsection

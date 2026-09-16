@extends('layouts.supervisor')

@section('title', 'Production Overview')

@section('content')
<div class="space-y-6">
    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-gray-200 rounded-2xl px-6 py-5 shadow-sm border-l-4 border-l-red-500">
        <div>
            <h1 class="text-2xl font-black text-gray-800 uppercase tracking-tight leading-tight">Production Overview</h1>
            <div class="flex items-center gap-2 mt-1">
                <form method="GET" class="flex items-center gap-2" id="dateFilterForm">
                    <input type="date" name="date_from" value="{{ $dateFrom }}"
                        class="text-xs border border-gray-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-red-300"
                        onchange="applyFilters()">
                    <input type="hidden" name="line" value="{{ $selectedLine }}">
                    <input type="hidden" name="shift" value="{{ $selectedShift }}">
                    <button type="button" onclick="applyFilters()"
                        class="px-3 py-1 bg-red-500 text-white text-[10px] font-bold rounded-lg hover:bg-red-600 transition-colors">
                        Tampilkan
                    </button>
                </form>
                <span class="text-[10px] text-gray-400 font-medium">Data: {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}</span>
            </div>
        </div>
        <div class="flex flex-row sm:flex-col items-center sm:items-end gap-4 sm:gap-1 shrink-0">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">Live System Time</div>
            <div id="liveClock" class="text-3xl font-black text-red-500 tracking-widest tabular-nums">--:--:--</div>
        </div>
    </div>

{{-- NOTIFICATION BANNER --}}
<div id="overviewNotifBanner" class="hidden"></div>

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
                    <span id="shiftBadge" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold ring-4 ring-gray-50 bg-gray-100 text-gray-600">
                        <span id="shiftBadgeDot" class="w-2 h-2 rounded-full bg-gray-400"></span>
                        <span id="shiftBadgeText">Memuat...</span>
                    </span>
                    <div id="shiftRealtimeStatus" class="mt-3 text-lg font-bold text-gray-800">Menarik data...</div>
                </div>
                <div class="text-right">
                    <div id="remainingTime" class="text-2xl font-black text-gray-700 tabular-nums">00:00:00</div>
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Sisa Waktu Operasional</div>
                </div>
            </div>

            <div id="breakBanner" class="mt-4 p-3 border rounded-xl flex items-center gap-3" style="display: none;">
                <div class="w-10 h-10 rounded-lg bg-white flex items-center justify-center shadow-sm">
                    <span id="breakBannerIcon">☕</span>
                </div>
                <div>
                    <div id="breakBannerTitle" class="text-xs font-bold text-blue-800"></div>
                    <div id="breakBannerTime" class="text-[10px] text-blue-600 font-medium"></div>
                </div>
            </div>
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

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4" id="lineStatusGrid">
            @foreach($lineStatuses as $line => $s)
            <div data-line="{{ $line }}" class="p-4 rounded-2xl bg-{{ $s['color'] }}-50 border border-{{ $s['color'] }}-100 text-center">
                <div class="text-[10px] font-bold text-{{ $s['color'] }}-600 uppercase tracking-widest mb-2">Line {{ $line }}</div>
                <div class="flex items-center justify-center gap-2 text-{{ $s['color'] }}-700 font-black">
                    @if($s['pulse'])
                    <span class="w-2 h-2 rounded-full bg-{{ $s['color'] }}-500 animate-pulse"></span>
                    @else
                    <span class="w-2 h-2 rounded-full bg-{{ $s['color'] }}-500"></span>
                    @endif
                    {{ $s['label'] }}
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- FILTER TOOLBAR --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Line:</span>
                @php
                    $lineBtns = ['all' => 'All', 'A' => 'A', 'B' => 'B', 'C' => 'C', 'D' => 'D'];
                @endphp
                @foreach($lineBtns as $val => $label)
                    <button onclick="applyFilters('{{ $val }}')"
                        class="filter-line-btn px-3 py-1.5 rounded-lg text-[11px] font-bold transition-colors
                        {{ $selectedLine === $val ? 'bg-red-500 hover:bg-red-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        data-line="{{ $val }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
            <div class="w-px h-6 bg-gray-200 hidden sm:block"></div>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Shift:</span>
                @php
                    $shiftBtns = ['1' => 'Pagi', '2' => 'Malam'];
                @endphp
                @foreach($shiftBtns as $val => $label)
                    <button onclick="applyFilters(null, '{{ $val }}')"
                        class="filter-shift-btn px-3 py-1.5 rounded-lg text-[11px] font-bold transition-colors
                        {{ $shift === (int)$val ? 'bg-red-500 hover:bg-red-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        data-shift="{{ $val }}">
                        {{ $label }}
                    </button>
                @endforeach
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
                    <div id="kpi-target" class="text-xl font-black {{ $targetQty > 0 ? 'text-blue-600' : 'text-gray-400' }}">{{ $targetQty > 0 ? number_format($targetQty) : '0' }}</div>
                    <div class="text-[10px] text-gray-400">pcs</div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Achieved</div>
                    <div id="kpi-achievement" class="text-xl font-black {{ ($achievementPercent??0) >= 80 ? 'text-green-600' : 'text-red-600' }}">{{ $achievementPercent ?? 0 }}%</div>
                    <div class="text-[10px] text-gray-400">rate</div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100">
                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Gap</div>
                    <div id="kpi-gap" class="text-xl font-black text-amber-600">{{ number_format($gap ?? 0) }}</div>
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
                        <span id="kpi-ok-pct" class="text-xs font-black text-green-600">{{ $okPercent }}%</span>
                    </div>
                    <div id="kpi-ok-qty" class="text-xl font-black text-gray-800">{{ number_format($totalOk) }}</div>
                    <div class="w-full bg-gray-200 h-1 rounded-full mt-2">
                        <div id="kpi-ok-bar" class="bg-green-500 h-1 rounded-full" style="width: {{ $okPercent }}%"></div>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 relative overflow-hidden">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Reject Rate</span>
                        <span id="kpi-reject-pct" class="text-xs font-black text-red-600">{{ $rejectRate ?? 0 }}%</span>
                    </div>
                    <div id="kpi-reject-qty" class="text-xl font-black text-gray-800">{{ number_format($totalReject) }}</div>
                    <div class="w-full bg-gray-200 h-1 rounded-full mt-2">
                        <div id="kpi-reject-bar" class="bg-red-500 h-1 rounded-full" style="width: {{ $rejectRate ?? 0 }}%"></div>
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
            <span id="kpi-progress-pct" class="text-lg font-black text-gray-800">{{ round($progress, 1) }}%</span>
        </div>
        <div class="w-full bg-gray-100 h-4 rounded-full p-1 border border-gray-200">
            <div id="kpi-progress-bar" class="h-2 rounded-full {{ $targetQty > 0 ? $progColor : 'bg-gray-300' }} transition-all duration-1000" style="width: {{ $targetQty > 0 ? min($progress, 100) : 0 }}%"></div>
        </div>
        <div class="flex justify-between mt-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
            <span id="kpi-target-label">Target: {{ $targetQty > 0 ? number_format($targetQty) : '0' }}</span>
            <span id="kpi-actual-label">Actual: {{ number_format($totalOk??0) }}</span>
        </div>
        <div id="no-data-msg" class="mt-4 p-3 bg-gray-50 border border-gray-200 rounded-xl flex items-center gap-2 text-[10px] font-bold text-gray-500" style="{{ $totalOk <= 0 && $targetQty <= 0 ? '' : 'display:none' }}">
            <i class="bx bx-info-circle text-sm"></i>
            Tidak ada data produksi untuk tanggal ini.
        </div>
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
    <div id="recent-logs" class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h2 class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Recent Production Logs</h2>
            <button onclick="openAllLogsModal()" class="px-3 py-1 bg-white border border-gray-200 rounded-lg text-[10px] font-bold text-gray-600 hover:bg-gray-50">View All</button>
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
                            <div class="text-sm font-black text-gray-800">{{ $p->jobMaster->job_name ?? '-' }}</div>
                            <div class="text-[10px] font-bold text-gray-400">{{ $p->jobMaster->job_number ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[10px] font-bold uppercase tracking-widest">{{ $p->line ?? $p->jobMaster->line ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-xs font-bold text-gray-600">{{ $p->shift ?: ($shift === 1 ? 'Shift Pagi' : 'Shift Malam') }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-black text-green-600">{{ number_format($p->actual_ok) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-black text-red-600">{{ number_format($p->actual_reject) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $ok = $p->actual_ok ?? 0;
                                $repair = $p->actual_repair ?? 0;
                                $reject = $p->actual_reject ?? 0;
                                $total = $ok + $repair + $reject;
                            @endphp
                            @if($total > 0 && $reject > 0 && $ok == 0)
                                <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-[9px] font-black uppercase tracking-widest">REJECT</span>
                            @elseif($total > 0 && $repair > 0 && $ok == 0)
                                <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-600 text-[9px] font-black uppercase tracking-widest">REPAIR</span>
                            @elseif($ok > 0)
                                <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-600 text-[9px] font-black uppercase tracking-widest">OK</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-400 text-[9px] font-black uppercase tracking-widest">PENDING</span>
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
        @if($latestProductions->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50 logs-pagination">
            {{ $latestProductions->links() }}
        </div>
        @endif
    </div>
    {{-- ALL LOGS MODAL --}}
    <div id="allLogsModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeAllLogsModal()"></div>
        <div class="absolute inset-4 sm:inset-8 lg:inset-16 bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-black text-gray-800 uppercase tracking-widest">All Production Logs</h3>
                <button onclick="closeAllLogsModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500 font-bold">&times;</button>
            </div>
            <div id="allLogsContent" class="flex-1 overflow-auto p-6">
                <div class="text-center py-10 text-gray-400">Memuat data...</div>
            </div>
            <div id="allLogsPagination" class="px-6 py-3 border-t border-gray-100 bg-gray-50/50 flex justify-center gap-2"></div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Notification banner
function checkNotifBanner() {
    fetch('/notifications/unread')
    .then(r => r.json())
    .then(data => {
        const banner = document.getElementById('overviewNotifBanner');
        if (data.count > 0) {
            banner.className = 'flex items-center gap-3 px-5 py-3 rounded-xl bg-red-50 border-2 border-red-200 shadow-sm hj-animate-in';
            banner.innerHTML = '<div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg></div><p class="text-sm font-bold text-red-700 flex-1">' + data.count + ' notifikasi belum dibaca</p><button onclick="document.getElementById(\'notificationBellBtn\').click();this.closest(\'#overviewNotifBanner\').classList.add(\'hidden\')" class="text-xs font-bold text-red-600 bg-white px-3 py-1.5 rounded-lg hover:bg-red-50 border border-red-200 transition-colors">Lihat</button>';
        } else {
            banner.className = 'hidden';
        }
    });
}
checkNotifBanner();
setInterval(checkNotifBanner, 5000);
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const breakSchedule = @json($breakSchedule);
let selectedLine = '{{ $selectedLine }}';
let selectedShift = '{{ $selectedShift ?? '1' }}';
let myChart;

function toggleDataset(index) {
    if(!myChart) return;
    const meta = myChart.getDatasetMeta(index);
    meta.hidden = (meta.hidden === null) ? !myChart.data.datasets[index].hidden : null;
    
    const el = index === 0 ? document.getElementById('legendExpected') : document.getElementById('legendActual');
    if(meta.hidden) {
        el.classList.add('opacity-40', 'line-through');
    } else {
        el.classList.remove('opacity-40', 'line-through');
    }
    
    myChart.update();
}

function applyFilters(line, shift) {
    if (line) selectedLine = line;
    if (shift) selectedShift = shift;

    const dateFrom = document.querySelector('input[name="date_from"]')?.value || '{{ $dateFrom }}';
    const params = new URLSearchParams({
        date_from: dateFrom,
        line: selectedLine,
        shift: selectedShift,
    });

    // Update button styles
    document.querySelectorAll('.filter-line-btn').forEach(function(btn) {
        var isActive = btn.dataset.line === selectedLine;
        btn.className = 'filter-line-btn px-3 py-1.5 rounded-lg text-[11px] font-bold transition-colors ' +
            (isActive ? 'bg-red-500 hover:bg-red-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
    });
    document.querySelectorAll('.filter-shift-btn').forEach(function(btn) {
        var isActive = btn.dataset.shift === selectedShift;
        btn.className = 'filter-shift-btn px-3 py-1.5 rounded-lg text-[11px] font-bold transition-colors ' +
            (isActive ? 'bg-red-500 hover:bg-red-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200');
    });

    // Update hidden inputs
    document.querySelector('input[name="line"]').value = selectedLine;
    document.querySelector('input[name="shift"]').value = selectedShift;

    // Loading indicator
    document.getElementById('kpi-target').innerHTML = 'Memuat...';
    document.getElementById('kpi-achievement').innerHTML = 'Memuat...';
    document.getElementById('kpi-gap').innerHTML = 'Memuat...';
    document.getElementById('kpi-ok-qty').innerHTML = 'Memuat...';
    document.getElementById('kpi-reject-qty').innerHTML = 'Memuat...';

    fetch('{{ route("supervisor.overview.data") }}?' + params.toString())
        .then(function(r) { return r.json(); })
        .then(function(data) {
            // Update KPI
            var kpi = data.kpi;
            function el(id) { return document.getElementById(id); }

            if (kpi.no_data) {
                if (el('kpi-target')) {
                    el('kpi-target').textContent = 'Tidak ada data';
                    el('kpi-target').className = 'text-sm font-bold text-gray-400';
                }
                if (el('kpi-achievement')) {
                    el('kpi-achievement').textContent = '-';
                    el('kpi-achievement').className = 'text-xl font-black text-gray-400';
                }
                if (el('kpi-gap')) el('kpi-gap').textContent = '-';
                if (el('kpi-ok-qty')) el('kpi-ok-qty').textContent = '-';
                if (el('kpi-ok-pct')) el('kpi-ok-pct').textContent = '-';
                if (el('kpi-ok-bar')) el('kpi-ok-bar').style.width = '0%';
                if (el('kpi-reject-qty')) el('kpi-reject-qty').textContent = '-';
                if (el('kpi-reject-pct')) el('kpi-reject-pct').textContent = '-';
                if (el('kpi-reject-bar')) el('kpi-reject-bar').style.width = '0%';
                if (el('kpi-progress-pct')) el('kpi-progress-pct').textContent = '-';
                if (el('kpi-progress-bar')) {
                    el('kpi-progress-bar').style.width = '0%';
                    el('kpi-progress-bar').className = 'h-2 rounded-full bg-gray-300';
                }
                if (el('kpi-target-label')) el('kpi-target-label').textContent = 'Target: -';
                if (el('kpi-actual-label')) el('kpi-actual-label').textContent = 'Actual: -';
                if (el('no-data-msg')) el('no-data-msg').style.display = '';
            } else {
                if (el('kpi-target')) {
                    el('kpi-target').textContent = Number(kpi.target).toLocaleString('id-ID');
                    el('kpi-target').className = 'text-xl font-black ' + (kpi.target > 0 ? 'text-blue-600' : 'text-gray-400');
                }
                if (el('kpi-achievement')) {
                    el('kpi-achievement').textContent = kpi.achievement + '%';
                    el('kpi-achievement').className = 'text-xl font-black ' + kpi.achievement_color;
                }
                if (el('kpi-gap')) el('kpi-gap').textContent = Number(kpi.gap).toLocaleString('id-ID');
                if (el('kpi-ok-qty')) el('kpi-ok-qty').textContent = Number(kpi.ok_qty).toLocaleString('id-ID');
                if (el('kpi-ok-pct')) el('kpi-ok-pct').textContent = kpi.ok_pct + '%';
                if (el('kpi-ok-bar')) el('kpi-ok-bar').style.width = kpi.ok_pct + '%';
                if (el('kpi-reject-qty')) el('kpi-reject-qty').textContent = Number(kpi.reject_qty).toLocaleString('id-ID');
                if (el('kpi-reject-pct')) el('kpi-reject-pct').textContent = kpi.reject_rate + '%';
                if (el('kpi-reject-bar')) el('kpi-reject-bar').style.width = Math.min(Number(kpi.reject_rate), 100) + '%';
                if (el('kpi-progress-pct')) el('kpi-progress-pct').textContent = kpi.progress + '%';
                if (el('kpi-progress-bar')) {
                    el('kpi-progress-bar').style.width = kpi.progress + '%';
                    el('kpi-progress-bar').className = 'h-2 rounded-full transition-all duration-1000 ' + kpi.prog_color;
                }
                if (el('kpi-target-label')) el('kpi-target-label').textContent = kpi.target_label;
                if (el('kpi-actual-label')) el('kpi-actual-label').textContent = kpi.actual_label;
                if (el('no-data-msg')) el('no-data-msg').style.display = 'none';
            }

            // Update chart
            if (myChart) myChart.destroy();
            if (data.chart.labels.length > 0) {
                var ctx = document.getElementById('productionChart');
                if (ctx) {
                    myChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.chart.labels,
                            datasets: [
                                {
                                    label: 'Expected',
                                    data: data.chart.expected,
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
                                    data: data.chart.actual,
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
            }

            // Update logs table
            if (el('recent-logs')) {
                var logsContainer = el('recent-logs');
                var tableBody = logsContainer.querySelector('tbody');
                if (tableBody) {
                    tableBody.innerHTML = data.logs_html;
                }
                // Update pagination
                var paginationDiv = logsContainer.querySelector('.logs-pagination');
                if (paginationDiv) {
                    paginationDiv.innerHTML = data.logs_pagination;
                }
            }

            // Reconnect SSE with new filters
            reconnectSSE();
        })
        .catch(function(err) {
            console.error('Filter error:', err);
        });
}

function reconnectSSE() {
    if (window._sseSource) {
        window._sseSource.close();
        window._sseSource = null;
    }
    connectSSE();
}

function formatTime(ms) {
    if (ms <= 0) return "00:00:00";
    const s = Math.floor(ms / 1000);
    const hh = String(Math.floor(s / 3600)).padStart(2, '0');
    const mm = String(Math.floor((s % 3600) / 60)).padStart(2, '0');
    const ss = String(s % 60).padStart(2, '0');
    return `${hh}:${mm}:${ss}`;
}

function getShiftInfo(now) {
    const t = now.getHours() * 60 + now.getMinutes();
    const today0 = new Date(now);
    today0.setHours(0, 0, 0, 0);
    const tomorrow0 = new Date(today0);
    tomorrow0.setDate(tomorrow0.getDate() + 1);
    const yesterday0 = new Date(today0);
    yesterday0.setDate(yesterday0.getDate() - 1);

    if (t >= 450 && t < 1260) {
        const s = new Date(today0); s.setHours(7, 30, 0, 0);
        const e = new Date(today0); e.setHours(21, 0, 0, 0);
        return { shift: 1, shiftStart: s, shiftEnd: e, isOvertime: t >= 975 };
    }

    const s2s = t >= 1260 ? new Date(today0) : new Date(yesterday0);
    s2s.setHours(21, 0, 0, 0);
    const s2e = t >= 1260 ? new Date(tomorrow0) : new Date(today0);
    s2e.setHours(7, 30, 0, 0);
    return { shift: 2, shiftStart: s2s, shiftEnd: s2e, isOvertime: t >= 270 && t < 450 };
}

function getCurrentBreak(now) {
    const t = now.getHours() * 60 + now.getMinutes();
    for (const b of breakSchedule) {
        if (t >= b.startMin && t < b.endMin) return b;
    }
    return null;
}

function updateUI() {
    const now = new Date();
    const info = getShiftInfo(now);
    const b = getCurrentBreak(now);

    // Live clock
    const clk = document.getElementById("liveClock");
    if (clk) clk.innerText = now.toTimeString().slice(0, 8);

    // Badge
    const badge = document.getElementById("shiftBadge");
    const dot = document.getElementById("shiftBadgeDot");
    const badgeText = document.getElementById("shiftBadgeText");
    const statusEl = document.getElementById("shiftRealtimeStatus");
    const rm = document.getElementById("remainingTime");
    const breakBanner = document.getElementById("breakBanner");

    let status = "", remaining = 0;
    let badgeCls = "inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold ring-4";
    let dotCls = "w-2 h-2 rounded-full";

    if (now < info.shiftStart) {
        status = "⏳ Menunggu Shift";
        remaining = 0;
        badgeCls += " bg-gray-100 text-gray-600 ring-gray-50";
        dotCls += " bg-gray-400";
        badgeText.innerText = "Menunggu";
        if (breakBanner) breakBanner.style.display = "none";
    } else if (now <= info.shiftEnd) {
        if (b) {
            status = "☕ " + b.label + " (Shift " + info.shift + ")";
            remaining = info.shiftEnd - now;
            badgeCls += " bg-blue-100 text-blue-600 ring-blue-50";
            dotCls += " bg-blue-500 animate-pulse";
            badgeText.innerText = "Istirahat";

            if (breakBanner) {
                breakBanner.style.display = "flex";
                breakBanner.className = "mt-4 p-3 rounded-xl flex items-center gap-3 bg-blue-50 border border-blue-100";
                document.getElementById("breakBannerIcon").innerText = b.type === 'cinkorak' ? '🍪' : '☕';
                document.getElementById("breakBannerTitle").innerText = b.label;
                document.getElementById("breakBannerTitle").className = "text-xs font-bold text-blue-800";
                document.getElementById("breakBannerTime").innerText = b.start + " – " + b.end;
                document.getElementById("breakBannerTime").className = "text-[10px] text-blue-600 font-medium";
            }
        } else {
            remaining = info.shiftEnd - now;
            if (breakBanner) breakBanner.style.display = "none";

            if (info.isOvertime) {
                status = "⚠ Overtime Shift " + info.shift;
                badgeCls += " bg-amber-100 text-amber-600 ring-amber-50";
                dotCls += " bg-amber-500 animate-pulse";
                badgeText.innerText = "Overtime";
            } else {
                status = "⚙ Shift " + info.shift + " Berjalan";
                badgeCls += " bg-green-100 text-green-600 ring-green-50";
                dotCls += " bg-green-500 animate-pulse";
                badgeText.innerText = "Shift " + info.shift;
            }
        }
    } else {
        status = "⏱ Shift Selesai";
        remaining = 0;
        badgeCls += " bg-red-100 text-red-600 ring-red-50";
        dotCls += " bg-red-500";
        badgeText.innerText = "Selesai";
        if (breakBanner) breakBanner.style.display = "none";
    }

    if (badge) badge.className = badgeCls;
    if (dot) dot.className = dotCls;
    if (statusEl) statusEl.innerText = status;
    if (rm) rm.innerText = formatTime(remaining);
}

document.addEventListener("DOMContentLoaded", function () {
    // Chart
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

    // Realtime UI loop
    updateUI();
    setInterval(updateUI, 1000);

    // Line status polling
    const grid = document.getElementById('lineStatusGrid');

    function getBgClass(color) { return 'bg-' + color + '-50'; }
    function getBorderClass(color) { return 'border-' + color + '-100'; }
    function getTextClass(color) { return 'text-' + color + '-600'; }
    function getText700Class(color) { return 'text-' + color + '-700'; }
    function getDotClass(color) { return 'bg-' + color + '-500'; }

    function renderLineCard(line, s) {
        const dot = s.pulse
            ? '<span class="w-2 h-2 rounded-full ' + getDotClass(s.color) + ' animate-pulse"></span>'
            : '<span class="w-2 h-2 rounded-full ' + getDotClass(s.color) + '"></span>';
        return '<div data-line="' + line + '" class="p-4 rounded-2xl ' + getBgClass(s.color) + ' border ' + getBorderClass(s.color) + ' text-center">'
            + '<div class="text-[10px] font-bold ' + getTextClass(s.color) + ' uppercase tracking-widest mb-2">Line ' + line + '</div>'
            + '<div class="flex items-center justify-center gap-2 ' + getText700Class(s.color) + ' font-black">' + dot + ' ' + s.label + '</div>'
            + '</div>';
    }

    async function fetchLineStatuses() {
        try {
            const dateVal = document.querySelector('input[name="date_from"]')?.value || '{{ $dateFrom }}';
            const res = await fetch('{{ route("supervisor.overview.lineStatus") }}?date_from=' + dateVal, { credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.line_statuses) return;
            let html = '';
            for (const [line, s] of Object.entries(data.line_statuses)) {
                html += renderLineCard(line, s);
            }
            grid.innerHTML = html;
        } catch (e) {
            console.error('Line status poll error:', e);
        }
    }

    async function fetchSingleLineStatus(line) {
        try {
            const res = await fetch('{{ route("supervisor.overview.lineStatusSingle", ["line" => "__LINE__"]) }}'.replace('__LINE__', encodeURIComponent(line)), { credentials: 'same-origin' });
            if (!res.ok) return;
            const data = await res.json();
            if (!data.status) return;
            updateLineCard(line, data.status);
        } catch (e) {
            // fallback to full fetch
            fetchLineStatuses();
        }
    }

    function updateLineCard(line, status) {
        if (!grid) return;
        const existingCard = grid.querySelector('[data-line="' + line + '"]');
        if (existingCard) {
            existingCard.outerHTML = renderLineCard(line, status);
        } else {
            fetchLineStatuses();
        }
    }

    // ── KPI update from SSE data ──
    function parseNum(v) {
        if (v === null || v === undefined || v === '' || v === '-') return 0;
        var s = String(v).replace(/[^0-9.\-]/g, '');
        return parseFloat(s) || 0;
    }

    function findKpi(rows, key) {
        if (!rows) return null;
        for (var i = 0; i < rows.length; i++) {
            if (rows[i].desc === key) return rows[i];
        }
        return null;
    }

    function updateOverviewKpi(lineKpi) {
        if (!lineKpi) return;
        var totalTarget = 0, totalActual = 0, totalReject = 0;
        for (var line in lineKpi) {
            if (selectedLine !== 'all' && line.indexOf(selectedLine) === -1) continue;
            var rows = lineKpi[line];
            var qtyRow = findKpi(rows, 'QTY');
            var rejectRow = findKpi(rows, 'REJECT');
            if (qtyRow) {
                totalTarget += parseNum(qtyRow.plan);
                totalActual += parseNum(qtyRow.actual);
            }
            if (rejectRow) {
                totalReject += parseNum(rejectRow.actual);
            }
        }

        var achievement = totalTarget > 0 ? Math.round((totalActual / totalTarget) * 100) : 0;
        var gap = totalTarget - totalActual;
        var rejectRate = totalActual > 0 ? ((totalReject / totalActual) * 100).toFixed(1) : '0.0';
        var progress = totalTarget > 0 ? Math.min((totalActual / totalTarget) * 100, 100) : 0;
        var okPct = totalActual > 0 ? '100' : '0';

        var el = function(id) { return document.getElementById(id); };

        if (el('kpi-target')) {
            el('kpi-target').textContent = totalTarget.toLocaleString('id-ID');
            el('kpi-target').className = 'text-xl font-black ' + (totalTarget > 0 ? 'text-blue-600' : 'text-gray-400');
        }
        if (el('kpi-achievement')) {
            el('kpi-achievement').textContent = achievement + '%';
            el('kpi-achievement').className = 'text-xl font-black ' + (achievement >= 80 ? 'text-green-600' : 'text-red-600');
        }
        if (el('kpi-gap')) el('kpi-gap').textContent = Math.max(0, gap).toLocaleString('id-ID');
        if (el('kpi-ok-qty')) el('kpi-ok-qty').textContent = totalActual.toLocaleString('id-ID');
        if (el('kpi-ok-pct')) el('kpi-ok-pct').textContent = okPct + '%';
        if (el('kpi-ok-bar')) el('kpi-ok-bar').style.width = okPct + '%';
        if (el('kpi-reject-qty')) el('kpi-reject-qty').textContent = totalReject.toLocaleString('id-ID');
        if (el('kpi-reject-pct')) el('kpi-reject-pct').textContent = rejectRate + '%';
        if (el('kpi-reject-bar')) el('kpi-reject-bar').style.width = Math.min(parseFloat(rejectRate), 100) + '%';
        if (el('kpi-progress-pct')) el('kpi-progress-pct').textContent = progress.toFixed(1) + '%';
        if (el('kpi-progress-bar')) {
            el('kpi-progress-bar').style.width = progress + '%';
            el('kpi-progress-bar').className = 'h-2 rounded-full transition-all duration-1000 ' + (progress >= 80 ? 'bg-green-500' : progress >= 50 ? 'bg-amber-500' : 'bg-red-500');
        }
        if (el('kpi-target-label')) el('kpi-target-label').textContent = 'Target: ' + (totalTarget > 0 ? totalTarget.toLocaleString('id-ID') : '0');
        if (el('kpi-actual-label')) el('kpi-actual-label').textContent = 'Actual: ' + (totalActual > 0 ? totalActual.toLocaleString('id-ID') : '0');
        if (el('no-data-msg')) el('no-data-msg').style.display = (totalTarget > 0 || totalActual > 0) ? 'none' : '';
    }

    // ── SSE stream for real-time cross-browser updates ──
    function connectSSE() {
        var dateVal = document.querySelector('input[name="date_from"]')?.value || '{{ $dateFrom }}';
        var shiftVal = parseInt(selectedShift);
        var lineVal = selectedLine !== 'all' ? 'PRESS ' + selectedLine : '';
        var sseUrl = '{{ route("supervisor.dashboard.stream") }}?date=' + dateVal + '&shift=' + shiftVal + (lineVal ? '&line=' + encodeURIComponent(lineVal) : '');
        window._sseSource = new EventSource(sseUrl);

        window._sseSource.onmessage = function(e) {
            if (e.data === 'keepalive') return;
            try {
                var data = JSON.parse(e.data);
                console.log('[SSE] Received:', Object.keys(data).join(', '), 'lines:', Object.keys(data.line_kpi || {}).join(', '));
                if (data.line_kpi) {
                    updateOverviewKpi(data.line_kpi);
                }
                fetchLineStatuses();
            } catch (err) {
                console.warn('[SSE] Parse error:', err);
            }
        };

        window._sseSource.onerror = function() {
            if (window._sseSource) window._sseSource.close();
            window._sseSource = null;
            setTimeout(connectSSE, 3000);
        };
    }

    // ── Real-time via BroadcastChannel (instant from Input Harian saves) ──
    try {
        const statusChan = new BroadcastChannel('line_status');
        statusChan.onmessage = (e) => {
            if (e.data.type !== 'status-changed') return;
            if (e.data.line) {
                fetchSingleLineStatus(e.data.line);
            } else {
                fetchLineStatuses();
            }
        };
    } catch (e) { /* fallback to polling */ }

    // ── Polling fallback (reduced to 60s, SSE is primary) ──
    setInterval(fetchLineStatuses, 60000);

    // ── Connect SSE ──
    connectSSE();
});
</script>
<script>
function openAllLogsModal(page = 1) {
    const modal = document.getElementById('allLogsModal');
    const content = document.getElementById('allLogsContent');
    const pagination = document.getElementById('allLogsPagination');
    modal.classList.remove('hidden');
    content.innerHTML = '<div class="text-center py-10 text-gray-400">Memuat data...</div>';
    pagination.innerHTML = '';

    const dateFrom = document.querySelector('input[name="date_from"]')?.value || '{{ $dateFrom }}';
    const lineFilter = selectedLine !== 'all' ? '&line=' + selectedLine : '';
    const shiftFilter = '&shift=' + selectedShift;
    fetch(`{{ route('supervisor.overview.allLogs') }}?date_from=${dateFrom}${lineFilter}${shiftFilter}&page=${page}`)
        .then(r => r.json())
        .then(data => {
            if (data.data.length === 0) {
                content.innerHTML = '<div class="text-center py-10 text-gray-400">Tidak ada data</div>';
                return;
            }
            let html = '<table class="w-full text-sm"><thead><tr class="bg-gray-50">';
            html += '<th class="px-4 py-2 text-left text-[10px] font-black text-gray-400 uppercase">Time</th>';
            html += '<th class="px-4 py-2 text-left text-[10px] font-black text-gray-400 uppercase">Job</th>';
            html += '<th class="px-4 py-2 text-left text-[10px] font-black text-gray-400 uppercase">Press</th>';
            html += '<th class="px-4 py-2 text-center text-[10px] font-black text-gray-400 uppercase">Shift</th>';
            html += '<th class="px-4 py-2 text-center text-[10px] font-black text-gray-400 uppercase">OK</th>';
            html += '<th class="px-4 py-2 text-center text-[10px] font-black text-gray-400 uppercase">NG</th>';
            html += '<th class="px-4 py-2 text-center text-[10px] font-black text-gray-400 uppercase">Status</th>';
            html += '</tr></thead><tbody class="divide-y divide-gray-50">';
            data.data.forEach(p => {
                const ok = p.actual_ok || 0;
                const repair = p.actual_repair || 0;
                const reject = p.actual_reject || 0;
                const total = ok + repair + reject;
                let status = '<span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-400 text-[9px] font-black">PENDING</span>';
                if (total > 0 && reject > 0 && ok == 0) status = '<span class="px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-[9px] font-black">REJECT</span>';
                else if (total > 0 && repair > 0 && ok == 0) status = '<span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-600 text-[9px] font-black">REPAIR</span>';
                else if (ok > 0) status = '<span class="px-2 py-0.5 rounded-full bg-green-100 text-green-600 text-[9px] font-black">OK</span>';
                const shift = p.shift || 'Shift Pagi';
                const press = p.line || (p.job_master ? p.job_master.line : '-') || '-';
                html += `<tr class="hover:bg-gray-50/50">`;
                html += `<td class="px-4 py-3 text-xs font-bold text-gray-400">${new Date(p.created_at).toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'})}</td>`;
                html += `<td class="px-4 py-3"><div class="text-sm font-black text-gray-800">${p.job_master?.job_name || '-'}</div><div class="text-[10px] font-bold text-gray-400">${p.job_master?.job_number || '-'}</div></td>`;
                html += `<td class="px-4 py-3"><span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[10px] font-bold uppercase">${press}</span></td>`;
                html += `<td class="px-4 py-3 text-center text-xs font-bold text-gray-600">${shift}</td>`;
                html += `<td class="px-4 py-3 text-center text-sm font-black text-green-600">${ok.toLocaleString('id-ID')}</td>`;
                html += `<td class="px-4 py-3 text-center text-sm font-black text-red-600">${reject.toLocaleString('id-ID')}</td>`;
                html += `<td class="px-4 py-3 text-center">${status}</td>`;
                html += `</tr>`;
            });
            html += '</tbody></table>';
            content.innerHTML = html;

            let paginationHtml = '';
            if (data.last_page > 1) {
                if (data.current_page > 1) paginationHtml += `<button onclick="openAllLogsModal(${data.current_page - 1})" class="px-3 py-1 text-xs font-bold rounded-lg border border-gray-200 hover:bg-gray-50">&laquo; Prev</button>`;
                for (let i = 1; i <= data.last_page; i++) {
                    paginationHtml += `<button onclick="openAllLogsModal(${i})" class="px-3 py-1 text-xs font-bold rounded-lg border ${i === data.current_page ? 'bg-red-500 text-white border-red-500' : 'border-gray-200 hover:bg-gray-50'}">${i}</button>`;
                }
                if (data.current_page < data.last_page) paginationHtml += `<button onclick="openAllLogsModal(${data.current_page + 1})" class="px-3 py-1 text-xs font-bold rounded-lg border border-gray-200 hover:bg-gray-50">Next &raquo;</button>`;
            }
            pagination.innerHTML = paginationHtml;
        });
}
function closeAllLogsModal() {
    document.getElementById('allLogsModal').classList.add('hidden');
}
</script>
@endsection

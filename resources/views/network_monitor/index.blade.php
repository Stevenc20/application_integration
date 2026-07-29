@extends('layouts.supervisor')

@section('title', 'Network Monitor')

@section('content')
<style>
    .nm-page { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .nm-tab {
        padding: 10px 22px; border-radius: 10px; font-size: 12px; font-weight: 700;
        letter-spacing: 0.02em; cursor: pointer; transition: all 0.25s cubic-bezier(.4,0,.2,1);
        border: 1.5px solid transparent; color: #64748b; background: white;
        text-transform: uppercase;
    }
    .nm-tab:hover { background: #f8fafc; color: #334155; border-color: #e2e8f0; }
    .nm-tab.active {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: white; border-color: transparent;
        box-shadow: 0 4px 14px rgba(30,41,59,0.25);
    }
    .nm-card {
        background: white; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.2s ease; position: relative; overflow: hidden;
    }
    .nm-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-1px); }
    .nm-badge {
        display: inline-block; padding: 3px 10px; border-radius: 20px;
        font-size: 10px; font-weight: 700; letter-spacing: 0.02em;
    }
    .nm-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
    .nm-row { transition: all 0.2s ease; }
    .nm-row:hover { background: #f8fafc; }
</style>

<div class="nm-page">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Network Monitor</h1>
            <p class="text-sm text-slate-400 mt-1">Status kontainer, akses jaringan, dan latensi real-time</p>
        </div>
        <div class="flex gap-2 items-center">
            <span class="nm-badge bg-emerald-100 text-emerald-700" id="upCount">{{ $stats['up_containers'] }}/{{ $stats['total_containers'] }} running</span>
            <span class="nm-badge bg-blue-100 text-blue-700">{{ $stats['total_requests_24h'] }} requests (24h)</span>
        </div>
    </div>

    <div class="flex gap-2 mb-6 border-b border-slate-200 pb-2">
        <button class="nm-tab active" data-tab="containers">Containers</button>
        <button class="nm-tab" data-tab="logs">Access Logs</button>
        <button class="nm-tab" data-tab="latency">Latency</button>
    </div>

    <div id="tab-containers" class="tab-content">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($containers as $c)
            <div class="nm-card">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-slate-800 text-sm">{{ $c->container_name }}</h3>
                    <span class="nm-status-dot {{ $c->status === 'running' ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                </div>
                <div class="text-xs text-slate-400 space-y-1">
                    <div class="flex justify-between"><span>Image</span><span class="text-slate-600 font-medium">{{ $c->image }}</span></div>
                    <div class="flex justify-between"><span>Status</span><span class="{{ $c->status === 'running' ? 'text-emerald-600' : 'text-red-600' }} font-medium">{{ $c->status }}</span></div>
                    <div class="flex justify-between"><span>Ports</span><span class="text-slate-600 font-medium">{{ $c->ports ?? '-' }}</span></div>
                    @if($c->uptime_seconds)
                    <div class="flex justify-between"><span>Uptime</span><span class="text-slate-600 font-medium">{{ $c->uptime_hours }}h</span></div>
                    @endif
                </div>
                <div class="mt-2 text-xs text-slate-400">Last checked: {{ $c->last_checked_at ? $c->last_checked_at->diffForHumans() : '-' }}</div>
            </div>
            @empty
            <div class="nm-card col-span-full text-center py-8 text-slate-400">Belum ada data kontainer</div>
            @endforelse
        </div>
    </div>

    <div id="tab-logs" class="tab-content hidden">
        <div class="flex gap-2 mb-4">
            <select id="logPeriod" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 bg-white">
                <option value="1">1 Jam</option>
                <option value="6">6 Jam</option>
                <option value="24" selected>24 Jam</option>
                <option value="168">7 Hari</option>
            </select>
            <button id="refreshLogs" class="px-4 py-2 bg-slate-800 text-white text-sm font-bold rounded-lg hover:bg-slate-700 transition">Refresh</button>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-5">
            <div class="nm-card"><span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Requests</span><p class="text-2xl font-black text-slate-800 mt-1" id="logTotal">-</p></div>
            <div class="nm-card"><span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Avg Response</span><p class="text-2xl font-black text-slate-800 mt-1" id="logAvgResponse">- ms</p></div>
            <div class="nm-card"><span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Top IP</span><p class="text-lg font-black text-slate-800 mt-1 truncate" id="logTopIp">-</p></div>
            <div class="nm-card"><span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Top Endpoint</span><p class="text-lg font-black text-slate-800 mt-1 truncate" id="logTopEndpoint">-</p></div>
        </div>
        <div class="nm-card mb-5"><canvas id="logChart" height="80"></canvas></div>
        <div class="nm-card">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Recent Requests</h3>
                <span class="nm-badge bg-slate-100 text-slate-600" id="logCount">0</span>
            </div>
            <div class="overflow-x-auto max-h-96 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs text-slate-400 uppercase tracking-widest border-b border-slate-200">
                        <th class="py-2 pr-3">Time</th><th class="py-2 pr-3">User</th><th class="py-2 pr-3">Method</th>
                        <th class="py-2 pr-3">Endpoint</th><th class="py-2 pr-3">Status</th><th class="py-2 pr-3">Duration</th><th class="py-2 pr-3">IP</th>
                    </tr></thead>
                    <tbody id="logTableBody"><tr><td colspan="7" class="py-8 text-center text-slate-400">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-latency" class="tab-content hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-5" id="latencyCards">
            <div class="nm-card text-center py-6 text-slate-400">Memuat data latensi...</div>
        </div>
        <div class="nm-card"><canvas id="latencyChart" height="120"></canvas></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
let logChartInstance = null;
let latencyChartInstance = null;

document.querySelectorAll('.nm-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.nm-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.remove('hidden');
        if (this.dataset.tab === 'logs') loadLogs();
        if (this.dataset.tab === 'latency') loadLatency();
    });
});

document.getElementById('logPeriod').addEventListener('change', loadLogs);
document.getElementById('refreshLogs').addEventListener('click', loadLogs);

async function loadLogs() {
    const period = document.getElementById('logPeriod').value;
    const resp = await fetch('{{ route('network_monitor.logs') }}?period=' + period);
    if (!resp.ok) { document.getElementById('logTableBody').innerHTML = '<tr><td colspan="7" class="py-8 text-center text-red-400">Gagal memuat data</td></tr>'; return; }
    const data = await resp.json();

    document.getElementById('logTotal').textContent = data.total;
    document.getElementById('logAvgResponse').textContent = data.avgResponse + ' ms';
    document.getElementById('logTopIp').textContent = data.topIps?.[0]?.ip_address || '-';
    document.getElementById('logTopEndpoint').textContent = data.topEndpoints?.[0]?.endpoint || '-';
    document.getElementById('logCount').textContent = data.recent.length + ' requests';

    let html = '';
    data.recent.forEach(r => {
        const statusColor = r.response_status >= 500 ? 'text-red-600' : r.response_status >= 400 ? 'text-orange-600' : 'text-emerald-600';
            const time = r.created_at ? r.created_at.substr(11, 8) : '-';
            html += `<tr class="nm-row border-b border-slate-100">
            <td class="py-2 pr-3 text-xs text-slate-500 whitespace-nowrap">${time}</td>
            <td class="py-2 pr-3 text-slate-700">${r.user?.name || '-'}</td>
            <td class="py-2 pr-3"><span class="nm-badge bg-slate-100 text-slate-600">${r.method}</span></td>
            <td class="py-2 pr-3 text-slate-600 max-w-[200px] truncate" title="${r.endpoint}">${r.endpoint}</td>
            <td class="py-2 pr-3 font-bold ${statusColor}">${r.response_status}</td>
            <td class="py-2 pr-3 text-slate-600">${r.response_time_ms ?? '-'} ms</td>
            <td class="py-2 pr-3 text-xs text-slate-400 font-mono">${r.ip_address}</td>
        </tr>`;
    });
    if (data.recent.length === 0) html = '<tr><td colspan="7" class="py-8 text-center text-slate-400">Belum ada data</td></tr>';
    document.getElementById('logTableBody').innerHTML = html;

    if (logChartInstance) logChartInstance.destroy();
    const ctx = document.getElementById('logChart').getContext('2d');
    logChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.perHour.map(h => h.hour.substr(11, 5)),
            datasets: [{
                label: 'Requests/hour',
                data: data.perHour.map(h => h.count),
                backgroundColor: 'rgba(37,99,235,0.6)',
                borderColor: '#2563eb',
                borderWidth: 1,
                borderRadius: 3,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } }, x: { grid: { display: false } } }
        }
    });
}

async function loadLatency() {
    const resp = await fetch('{{ route('network_monitor.latency') }}');
    if (!resp.ok) { document.getElementById('latencyCards').innerHTML = '<div class="nm-card text-center py-6 text-red-400">Gagal memuat data</div>'; return; }
    const data = await resp.json();

    let cardsHtml = '';
    data.targets.forEach(t => {
        const statusColor = t.current_status === 'up' ? 'text-emerald-600' : t.current_status === 'degraded' ? 'text-orange-600' : 'text-red-600';
        cardsHtml += `<div class="nm-card">
            <h3 class="font-bold text-sm text-slate-800 mb-1 truncate">${t.target}</h3>
            <div class="space-y-1 text-xs text-slate-400">
                <div class="flex justify-between"><span>Avg Latency</span><span class="font-bold text-slate-700">${t.avg_latency} ms</span></div>
                <div class="flex justify-between"><span>Min/Max</span><span class="text-slate-600">${t.min_latency} / ${t.max_latency} ms</span></div>
                <div class="flex justify-between"><span>Status</span><span class="font-bold ${statusColor}">${t.current_status}</span></div>
                <div class="flex justify-between"><span>Measurements</span><span class="text-slate-600">${t.measurements}</span></div>
            </div>
        </div>`;
    });
    document.getElementById('latencyCards').innerHTML = cardsHtml;

    if (latencyChartInstance) latencyChartInstance.destroy();
    const ctx = document.getElementById('latencyChart').getContext('2d');
    const allTimes = [...new Set(data.history.flatMap(h => h.data.map(d => d.time)))].sort();
    const datasets = data.history.map(h => ({
        label: h.target,
        data: allTimes.map(t => { const found = h.data.find(d => d.time === t); return found ? found.latency_ms : null; }),
        borderWidth: 2,
        pointRadius: 2,
        tension: 0.3,
        fill: false,
        spanGaps: true,
    }));
    latencyChartInstance = new Chart(ctx, {
        type: 'line',
        data: { labels: allTimes, datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { maxTicksLimit: 24, maxRotation: 45 } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' }, title: { display: true, text: 'Latency (ms)' } }
            }
        }
    });
}

@if($latencies->count() > 0)
loadLatency();
@endif
</script>
@endsection

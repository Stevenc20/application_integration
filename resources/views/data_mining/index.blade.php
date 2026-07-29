@extends('layouts.supervisor')

@section('title', 'Data Mining')

@section('content')
<style>
    .dm-page { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    .dm-tab {
        padding: 10px 22px; border-radius: 10px; font-size: 12px; font-weight: 700;
        letter-spacing: 0.02em; cursor: pointer; transition: all 0.25s cubic-bezier(.4,0,.2,1);
        border: 1.5px solid transparent; color: #64748b; background: white;
        text-transform: uppercase;
    }
    .dm-tab:hover { background: #f8fafc; color: #334155; border-color: #e2e8f0; }
    .dm-tab.active {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: white; border-color: transparent;
        box-shadow: 0 4px 14px rgba(30,41,59,0.25);
    }
    .dm-card {
        background: white; border: 1px solid #e2e8f0; border-radius: 10px;
        padding: 12px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: all 0.2s ease; position: relative;
    }
    .dm-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-1px); }
    .dm-badge {
        display: inline-block; padding: 3px 10px; border-radius: 20px;
        font-size: 10px; font-weight: 700; letter-spacing: 0.02em;
    }
    .anomaly-row:hover { background: #fef2f2; }
</style>

<div class="dm-page">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tight">Data Mining</h1>
            <p class="text-sm text-slate-400 mt-1">Analisis pola, deteksi anomali, dan insight produksi berbasis data</p>
        </div>
        <div class="flex gap-2" id="summaryBanner"></div>
    </div>

    <div class="flex gap-2 mb-3 border-b border-slate-200 pb-2">
        <button class="dm-tab active" data-tab="trend">Trend</button>
        <button class="dm-tab" data-tab="anomaly">Anomaly</button>
        <button class="dm-tab" data-tab="pareto">Pareto</button>
    </div>

    <div id="tab-trend" class="tab-content">
        <div class="flex gap-2 mb-2">
            <select id="trendMetric" class="px-2 py-1 border border-slate-200 rounded-lg text-xs font-medium text-slate-600 bg-white">
                <option value="efficiency">Efficiency</option>
                <option value="reject">Reject</option>
                <option value="downtime">Downtime</option>
            </select>
            <select id="trendDays" class="px-2 py-1 border border-slate-200 rounded-lg text-xs font-medium text-slate-600 bg-white">
                <option value="7" selected>7H</option>
                <option value="14">14H</option>
                <option value="30">30H</option>
            </select>
        </div>
        <div class="grid grid-cols-4 gap-2 mb-3">
            <div class="dm-card"><span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Current</span><p class="text-base font-black text-slate-800 mt-0.5" id="trendCurrent">-</p></div>
            <div class="dm-card"><span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Average</span><p class="text-base font-black text-slate-800 mt-0.5" id="trendAvg">-</p></div>
            <div class="dm-card"><span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Trend</span><p class="text-base font-black mt-0.5" id="trendDirection">-</p></div>
            <div class="dm-card"><span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Points</span><p class="text-base font-black text-slate-800 mt-0.5" id="trendPoints">-</p></div>
        </div>
         <div class="dm-card"><div class="overflow-x-auto"><canvas id="trendChart" style="min-width:300px;height:200px"></canvas></div></div>
    </div>

    <div id="tab-anomaly" class="tab-content hidden">
        <div class="flex gap-2 mb-2">
            <select id="anomalyMetric" class="px-2 py-1 border border-slate-200 rounded-lg text-xs font-medium text-slate-600 bg-white">
                <option value="all">All</option>
                <option value="efficiency">Efficiency</option>
                <option value="reject">Reject</option>
                <option value="downtime">Downtime</option>
            </select>
            <select id="anomalyDays" class="px-2 py-1 border border-slate-200 rounded-lg text-xs font-medium text-slate-600 bg-white">
                <option value="7">7H</option>
                <option value="30" selected>30H</option>
                <option value="90">90H</option>
            </select>
        </div>
        <div class="dm-card">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Anomaly Detection</h3>
                <span class="dm-badge bg-red-100 text-red-700" id="anomalyCount">0 anomalies</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-left text-xs text-slate-400 uppercase tracking-widest border-b border-slate-200">
                        <th class="py-2 pr-3">Date</th><th class="py-2 pr-3">Line</th><th class="py-2 pr-3">Shift</th>
                        <th class="py-2 pr-3">Metric</th><th class="py-2 pr-3">Value</th><th class="py-2 pr-3">Z-Score</th><th class="py-2 pr-3">Detail</th>
                    </tr></thead>
                    <tbody id="anomalyTableBody"><tr><td colspan="7" class="py-8 text-center text-slate-400">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tab-pareto" class="tab-content hidden">
        <div class="flex gap-2 mb-3">
            <select id="paretoType" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 bg-white">
                <option value="downtime">Downtime</option>
                <option value="defect">Defect</option>
            </select>
            <select id="paretoDays" class="px-3 py-2 border border-slate-200 rounded-lg text-sm font-medium text-slate-600 bg-white">
                <option value="7">7 Hari</option>
                <option value="30" selected>30 Hari</option>
                <option value="90">90 Hari</option>
            </select>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="dm-card"><h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Pareto Chart</h3><div class="overflow-x-auto"><canvas id="paretoChart" style="min-width:400px;height:200px"></canvas></div></div>
            <div class="dm-card">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Breakdown</h3>
                    <span class="dm-badge bg-blue-100 text-blue-700" id="paretoTopCount">-</span>
                </div>
                <div id="paretoList"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
let trendChartInstance = null;
let paretoChartInstance = null;

document.querySelectorAll('.dm-tab').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.dm-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.remove('hidden');
        if (this.dataset.tab === 'trend') loadTrend();
        if (this.dataset.tab === 'anomaly') loadAnomaly();
        if (this.dataset.tab === 'pareto') loadPareto();
    });
});

document.getElementById('trendMetric').addEventListener('change', loadTrend);
document.getElementById('trendDays').addEventListener('change', loadTrend);
document.getElementById('anomalyMetric').addEventListener('change', loadAnomaly);
document.getElementById('anomalyDays').addEventListener('change', loadAnomaly);
document.getElementById('paretoType').addEventListener('change', loadPareto);
document.getElementById('paretoDays').addEventListener('change', loadPareto);

async function loadSummary() {
    const resp = await fetch('{{ route('data_mining.summary') }}?days=30&threshold=2');
    if (!resp.ok) return;
    const data = await resp.json();
    document.getElementById('summaryBanner').innerHTML = `
        <span class="dm-badge ${data.total_anomalies > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'}">
            ${data.total_anomalies > 0 ? data.total_anomalies + ' anomalies' : 'No anomalies'}
        </span>`;
}

async function loadTrend() {
    const metric = document.getElementById('trendMetric').value;
    const days = document.getElementById('trendDays').value;
    const resp = await fetch(`{{ url('data-mining/trend') }}/${metric}?days=${days}`);
    if (!resp.ok) { showError('trend'); return; }
    const data = await resp.json();
    if (!data.summary) return;

    document.getElementById('trendCurrent').textContent = data.summary.current ?? '-';
    document.getElementById('trendAvg').textContent = data.summary.average ?? '-';
    const dir = data.summary.trend_direction;
    const change = data.summary.trend_change_pct;
    const el = document.getElementById('trendDirection');
    if (dir === 'up') { el.textContent = `↑ ${change}%`; el.className = 'text-base font-black mt-0.5 text-emerald-600'; }
    else if (dir === 'down') { el.textContent = `↓ ${Math.abs(change)}%`; el.className = 'text-base font-black mt-0.5 text-red-600'; }
    else { el.textContent = '→ Stable'; el.className = 'text-base font-black mt-0.5 text-slate-500'; }
    document.getElementById('trendPoints').textContent = data.summary.data_points ?? '-';

    if (trendChartInstance) trendChartInstance.destroy();
    const ctx = document.getElementById('trendChart').getContext('2d');
    const mainValue = data.daily[0]?.efficiency !== undefined ? 'efficiency' :
                      data.daily[0]?.reject_rate !== undefined ? 'reject_rate' :
                      data.daily[0]?.total_minutes !== undefined ? 'total_minutes' : null;
    const mainLabel = metric === 'efficiency' ? 'Efficiency %' : metric === 'reject' ? 'Reject Rate %' : 'Downtime (min)';

    const chartData = data.daily;
    const maxPoints = 14;
    const sampled = chartData.length > maxPoints
        ? chartData.filter((_, i) => i % Math.ceil(chartData.length / maxPoints) === 0 || i === chartData.length - 1)
        : chartData;

    const datasets = [{
        label: mainLabel, data: sampled.map(d => d[mainValue]), borderColor: '#2563eb',
        backgroundColor: 'rgba(37,99,235,0.1)', fill: true, tension: 0.3, pointRadius: 3,
    }];
    if (sampled[0]?.sma_7) {
        datasets.push({
            label: 'SMA-7', data: sampled.map(d => d.sma_7), borderColor: '#059669',
            borderDash: [6, 3], pointRadius: 0, tension: 0.3,
        });
    }

    trendChartInstance = new Chart(ctx, {
        type: 'line', data: { labels: sampled.map(d => d.date), datasets },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
            scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } } }
        }
    });
}

async function loadAnomaly() {
    const metric = document.getElementById('anomalyMetric').value;
    const days = document.getElementById('anomalyDays').value;
    let url = `{{ route('data_mining.anomaly') }}?days=${days}&threshold=2`;
    const resp = await fetch(url);
    if (!resp.ok) { document.getElementById('anomalyTableBody').innerHTML = '<tr><td colspan="7" class="py-8 text-center text-red-400">Gagal memuat data</td></tr>'; return; }
    const data = await resp.json();
    let all = [];
    if (metric === 'all' || metric === 'efficiency') all = all.concat(data.anomalies?.efficiency || []);
    if (metric === 'all' || metric === 'reject') all = all.concat(data.anomalies?.reject || []);
    if (metric === 'all' || metric === 'downtime') all = all.concat(data.anomalies?.downtime || []);

    document.getElementById('anomalyCount').textContent = all.length + ' anomalies';
    if (all.length === 0) {
        document.getElementById('anomalyTableBody').innerHTML = '<tr><td colspan="7" class="py-8 text-center text-emerald-500 font-semibold">Tidak ada anomali terdeteksi ✓</td></tr>';
        return;
    }
    let html = '';
    all.forEach(a => {
        const cls = a.status === 'anomali_negatif' ? 'bg-red-50' : 'bg-orange-50';
        html += `<tr class="anomaly-row ${cls} border-b border-slate-100">
            <td class="py-2 pr-3 font-medium text-slate-700">${a.date}</td>
            <td class="py-2 pr-3 text-slate-600">${a.line || '-'}</td>
            <td class="py-2 pr-3 text-slate-600">${a.shift || '-'}</td>
            <td class="py-2 pr-3"><span class="dm-badge ${a.metric === 'efficiency' ? 'bg-blue-100 text-blue-700' : a.metric === 'reject' ? 'bg-red-100 text-red-700' : 'bg-purple-100 text-purple-700'}">${a.metric}</span></td>
            <td class="py-2 pr-3 font-bold text-slate-800">${a.value}</td>
            <td class="py-2 pr-3 font-mono ${Math.abs(a.z_score) >= 3 ? 'text-red-600 font-bold' : 'text-orange-600'}">${a.z_score}</td>
            <td class="py-2 pr-3 text-slate-500 text-xs">${a.detail}</td>
        </tr>`;
    });
    document.getElementById('anomalyTableBody').innerHTML = html;
}

async function loadPareto() {
    const type = document.getElementById('paretoType').value;
    const days = document.getElementById('paretoDays').value;
    const resp = await fetch(`{{ url('data-mining/pareto') }}/${type}?days=${days}`);
    if (!resp.ok) { document.getElementById('paretoList').innerHTML = '<p class="text-red-400">Gagal memuat data</p>'; return; }
    const data = await resp.json();
    const items = (data[type] || data.categories || []).slice(0, 5);
    const labelKey = type === 'defect' ? 'defect_name' : 'jenis_downtime';
    const valueKey = type === 'defect' ? 'total_qty' : 'total_minutes';
    const valueLabel = type === 'defect' ? 'Qty' : 'Minutes';

    document.getElementById('paretoTopCount').textContent = items.length + ' top categories';

    let listHtml = `<table class="w-full text-xs"><thead><tr class="text-left text-slate-400 border-b border-slate-200">
        <th class="py-0.5 pr-1">#</th><th class="py-0.5 pr-1">Item</th><th class="py-0.5 pr-1 text-right">${valueLabel}</th><th class="py-0.5 pr-1 text-right">%</th><th class="py-0.5 pr-1 text-right">Cum</th>
    </tr></thead><tbody>`;
    items.forEach((item, i) => {
        listHtml += `<tr class="border-b border-slate-100">
            <td class="py-0.5 pr-1 font-bold text-slate-400">${i + 1}</td>
            <td class="py-0.5 pr-1 font-medium text-slate-700">${item[labelKey]}</td>
            <td class="py-0.5 pr-1 font-bold text-slate-800 text-right">${item[valueKey]}</td>
            <td class="py-0.5 pr-1 text-slate-600 text-right">${item.pct}%</td>
            <td class="py-0.5 pr-1 text-slate-400 text-right">${item.cumulative}%</td>
        </tr>`;
    });
    listHtml += '</tbody></table>';
    document.getElementById('paretoList').innerHTML = listHtml;

    if (paretoChartInstance) paretoChartInstance.destroy();
    const ctx = document.getElementById('paretoChart').getContext('2d');
    paretoChartInstance = new Chart(ctx, {
        type: 'bar', data: {
            labels: items.map(i => i[labelKey]),
            datasets: [
                { label: valueLabel, data: items.map(i => i[valueKey]), backgroundColor: items.map(i => i.pct >= 20 ? '#dc2626' : i.pct >= 10 ? '#f59e0b' : '#2563eb'), borderRadius: 4 },
                { label: 'Cumulative %', data: items.map(i => i.cumulative), type: 'line', borderColor: '#1e293b', borderWidth: 2, pointRadius: 4, pointBackgroundColor: '#1e293b', yAxisID: 'y1' },
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
            scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' }, title: { display: true, text: valueLabel } }, y1: { position: 'right', beginAtZero: true, max: 100, grid: { display: false }, title: { display: true, text: '%' } }, x: { grid: { display: false } } }
        }
    });
}

function showError(tab) {
    if (tab === 'trend') document.getElementById('trendDirection').textContent = 'Service unavailable';
}

loadSummary();
loadTrend();
</script>
@endsection
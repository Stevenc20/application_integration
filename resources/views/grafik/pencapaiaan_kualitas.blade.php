@extends('layouts.supervisor')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="p-6">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Pencapaian Kualitas</h1>
            <p class="text-gray-500 text-sm">
                {{ now()->format('d F Y') }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <select id="lineFilter" class="form-select rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-sm">
                <option value="">Semua Line</option>
            </select>
            <input type="date" id="dateFrom" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-sm" value="{{ now()->subDays(6)->format('Y-m-d') }}">
            <input type="date" id="dateTo" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 text-sm" value="{{ now()->format('Y-m-d') }}">
            <button onclick="loadData()" class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors text-sm">Update</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6" id="summaryCards">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 text-center">
            <p class="text-sm text-gray-500 font-medium">Total Produksi</p>
            <p class="text-3xl font-bold text-gray-800 mt-1" id="totalProduction">0</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 text-center">
            <p class="text-sm text-gray-500 font-medium">Total OK</p>
            <p class="text-3xl font-bold text-green-600 mt-1" id="totalOk">0</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 text-center">
            <p class="text-sm text-gray-500 font-medium">Total Repair</p>
            <p class="text-3xl font-bold text-yellow-500 mt-1" id="totalRepair">0</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 text-center">
            <p class="text-sm text-gray-500 font-medium">Total Reject</p>
            <p class="text-3xl font-bold text-red-600 mt-1" id="totalReject">0</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
            <h5 class="text-xl font-bold text-gray-800">Tren Kualitas Harian</h5>
        </div>
        <div class="p-5">
            <div class="h-80 relative">
                <canvas id="qualityChart"></canvas>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
            <h5 class="text-xl font-bold text-gray-800">Rekap per Line</h5>
        </div>
        <div class="p-5 overflow-x-auto">
            <table class="w-full text-sm" id="lineTable">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600">
                        <th class="text-left py-2 px-3">Line</th>
                        <th class="text-right py-2 px-3">Total</th>
                        <th class="text-right py-2 px-3">OK</th>
                        <th class="text-right py-2 px-3">Repair</th>
                        <th class="text-right py-2 px-3">Reject</th>
                        <th class="text-right py-2 px-3">OK Rate</th>
                        <th class="text-right py-2 px-3">Reject Rate</th>
                    </tr>
                </thead>
                <tbody id="lineTableBody"></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let qualityChart = null;

async function loadData() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const line = document.getElementById('lineFilter').value;
    const params = new URLSearchParams({ date_from: dateFrom, date_to: dateTo });
    if (line) params.set('line', line);

    const res = await fetch("{{ route('supervisor.api.grafik.quality') }}?" + params);
    const data = await res.json();

    document.getElementById('totalProduction').textContent = data.totals.total.toLocaleString();
    document.getElementById('totalOk').textContent = data.totals.ok.toLocaleString();
    document.getElementById('totalRepair').textContent = data.totals.repair.toLocaleString();
    document.getElementById('totalReject').textContent = data.totals.reject.toLocaleString();

    if (qualityChart) qualityChart.destroy();

    const ctx = document.getElementById('qualityChart').getContext('2d');
    qualityChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: data.datasets.map(ds => ({
                ...ds,
                fill: false,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6,
                borderWidth: 2,
            })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100, title: { display: true, text: 'Persentase (%)' } },
            },
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y + '%' } },
            },
        },
    });

    const tbody = document.getElementById('lineTableBody');
    tbody.innerHTML = '';
    data.lineSummary.forEach(r => {
        tbody.innerHTML += `
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-2 px-3 font-medium">${r.line}</td>
                <td class="py-2 px-3 text-right">${r.total.toLocaleString()}</td>
                <td class="py-2 px-3 text-right text-green-600">${r.ok.toLocaleString()}</td>
                <td class="py-2 px-3 text-right text-yellow-500">${r.repair.toLocaleString()}</td>
                <td class="py-2 px-3 text-right text-red-600">${r.reject.toLocaleString()}</td>
                <td class="py-2 px-3 text-right font-medium">${r.ok_rate}%</td>
                <td class="py-2 px-3 text-right font-medium text-red-600">${r.reject_rate}%</td>
            </tr>`;
    });
}

async function loadLines() {
    const res = await fetch("{{ route('supervisor.api.grafik.quality') }}?date_from={{ now()->subDays(6)->format('Y-m-d') }}&date_to={{ now()->format('Y-m-d') }}");
    const data = await res.json();
    const lines = [...new Set(data.lineSummary.map(r => r.line))];
    const sel = document.getElementById('lineFilter');
    lines.forEach(l => { sel.innerHTML += `<option value="${l}">${l}</option>`; });
}

document.addEventListener('DOMContentLoaded', () => { loadLines(); loadData(); });
</script>
@endsection

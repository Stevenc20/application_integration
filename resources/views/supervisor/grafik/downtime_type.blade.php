@extends('layouts.supervisor')

@section('title', 'Grafik Downtime per Tipe')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Grafik Downtime per Tipe/Kategori</h5>
    </div>
    
    <div class="p-5">
        <form class="flex items-center w-full md:w-auto mb-6" onsubmit="event.preventDefault(); loadData();">
            <input type="month" id="monthFilter" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Update Grafik</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm text-center">
                <p class="text-sm text-gray-500">Total Downtime</p>
                <p class="text-2xl font-bold text-gray-800 mt-1" id="totalMinutes">0</p>
                <p class="text-xs text-gray-400">menit</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm text-center">
                <p class="text-sm text-gray-500">Total Kejadian</p>
                <p class="text-2xl font-bold text-gray-800 mt-1" id="totalEvents">0</p>
                <p class="text-xs text-gray-400">kali</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm text-center">
                <p class="text-sm text-gray-500">Kategori Terbanyak</p>
                <p class="text-2xl font-bold text-gray-800 mt-1" id="topCategory">-</p>
                <p class="text-xs text-gray-400">&nbsp;</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm flex flex-col justify-center">
                <h6 class="font-bold text-gray-700 mb-4 text-center">Distribusi Kategori Downtime</h6>
                <div class="h-64 relative" id="chartWrapper">
                    <canvas id="downtimeTypeChart"></canvas>
                    <div id="noDataMessage" class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm font-medium hidden">Tidak ada data</div>
                </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm">
                <h6 class="font-bold text-gray-700 mb-4">Detail Persentase</h6>
                <div class="space-y-4" id="percentageBars"></div>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600">
                        <th class="text-left py-2 px-3">Kategori</th>
                        <th class="text-right py-2 px-3">Total (Menit)</th>
                        <th class="text-right py-2 px-3">Persentase</th>
                        <th class="text-right py-2 px-3">Jumlah Kejadian</th>
                    </tr>
                </thead>
                <tbody id="detailTableBody"></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let chart = null;
const colors = ['#3b82f6', '#ef4444', '#eab308', '#22c55e', '#a855f7', '#f97316', '#06b6d4'];

async function loadData() {
    const month = document.getElementById('monthFilter').value;
    const params = new URLSearchParams({ month });

    const res = await fetch("{{ route('supervisor.api.grafik.downtime_type') }}?" + params);
    const data = await res.json();

    const hasData = data.labels && data.labels.length > 0;

    document.getElementById('totalMinutes').textContent = hasData ? data.totalMinutes.toFixed(0) : '0';
    const totalCnt = hasData ? data.counts.reduce((a, b) => a + b, 0) : 0;
    document.getElementById('totalEvents').textContent = totalCnt;
    document.getElementById('topCategory').textContent = hasData ? data.labels[0] : '-';

    if (chart) chart.destroy();

    const canvas = document.getElementById('downtimeTypeChart');
    const noDataMsg = document.getElementById('noDataMessage');
    const bars = document.getElementById('percentageBars');
    const tbody = document.getElementById('detailTableBody');

    if (!hasData) {
        canvas.classList.add('hidden');
        noDataMsg.classList.remove('hidden');
        bars.innerHTML = '<div class="flex items-center justify-center h-32 text-gray-400 text-sm font-medium">Tidak ada data</div>';
        tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-gray-400 text-sm">Tidak ada data</td></tr>';
        return;
    }

    canvas.classList.remove('hidden');
    noDataMsg.classList.add('hidden');
    const ctx = canvas.getContext('2d');
    chart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: data.labels,
            datasets: data.datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const val = data.datasets[0].data[ctx.dataIndex];
                            const pct = data.percentages[ctx.dataIndex];
                            return ctx.label + ': ' + val.toFixed(0) + ' menit (' + pct + '%)';
                        },
                    },
                },
            },
        },
    });

    bars.innerHTML = '';
    data.labels.forEach((label, i) => {
        const pct = data.percentages[i];
        const minutes = data.datasets[0].data[i];
        const color = colors[i % colors.length];
        bars.innerHTML += `
            <div>
                <div class="flex justify-between text-sm mb-1">
                    <span class="font-medium text-gray-700">${label}</span>
                    <span class="font-bold text-gray-900">${pct}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full" style="width:${pct}%;background-color:${color}"></div>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">${minutes.toFixed(0)} menit</p>
            </div>`;
    });

    tbody.innerHTML = '';
    data.labels.forEach((label, i) => {
        const minutes = data.datasets[0].data[i];
        const pct = data.percentages[i];
        const count = data.counts[i];
        tbody.innerHTML += `
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-2 px-3 font-medium">${label}</td>
                <td class="py-2 px-3 text-right">${minutes.toFixed(0)}</td>
                <td class="py-2 px-3 text-right">${pct}%</td>
                <td class="py-2 px-3 text-right">${count}</td>
            </tr>`;
    });
}

document.addEventListener('DOMContentLoaded', loadData);
</script>
@endsection

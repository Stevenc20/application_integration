@extends('layouts.supervisor')

@section('title', 'Grafik Downtime Mesin')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Grafik Downtime per Mesin</h5>
    </div>
    
    <div class="p-5">
        <form class="flex items-center w-full md:w-auto mb-6" onsubmit="event.preventDefault(); loadData();">
            <input type="month" id="monthFilter" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Update Grafik</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
                <p class="text-sm text-gray-500">Mesin Terbanyak</p>
                <p class="text-2xl font-bold text-gray-800 mt-1" id="topMachine">-</p>
                <p class="text-xs text-gray-400">&nbsp;</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm text-center">
                <p class="text-sm text-gray-500">Rata-rata per Kejadian</p>
                <p class="text-2xl font-bold text-gray-800 mt-1" id="avgMinutes">0</p>
                <p class="text-xs text-gray-400">menit</p>
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm">
            <h6 class="font-bold text-gray-700 mb-4 text-center">Top Mesin dengan Downtime Tertinggi (Menit)</h6>
            <div class="h-80 relative">
                <canvas id="downtimeMachineChart"></canvas>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600">
                        <th class="text-left py-2 px-3">Mesin</th>
                        <th class="text-left py-2 px-3">Line</th>
                        <th class="text-right py-2 px-3">Total (Menit)</th>
                        <th class="text-right py-2 px-3">Jumlah Kejadian</th>
                        <th class="text-right py-2 px-3">Rata-rata (Menit)</th>
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

async function loadData() {
    const month = document.getElementById('monthFilter').value;
    const params = new URLSearchParams({ month, limit: 10 });

    const res = await fetch("{{ route('supervisor.api.grafik.downtime_machine') }}?" + params);
    const data = await res.json();

    const totalMin = data.datasets[0].data.reduce((a, b) => a + b, 0);
    const totalCnt = data.counts.reduce((a, b) => a + b, 0);
    document.getElementById('totalMinutes').textContent = totalMin.toFixed(0);
    document.getElementById('totalEvents').textContent = totalCnt;
    document.getElementById('topMachine').textContent = data.labels.length > 0 ? data.labels[0] : '-';
    document.getElementById('avgMinutes').textContent = totalCnt > 0 ? (totalMin / totalCnt).toFixed(1) : '0';

    if (chart) chart.destroy();

    const ctx = document.getElementById('downtimeMachineChart').getContext('2d');
    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels,
            datasets: data.datasets.map(ds => ({ ...ds, borderRadius: 4 })),
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Menit' } },
            },
            plugins: {
                legend: { display: false },
            },
        },
    });

    const tbody = document.getElementById('detailTableBody');
    tbody.innerHTML = '';
    data.labels.forEach((label, i) => {
        const minutes = data.datasets[0].data[i];
        const count = data.counts[i];
        const avg = count > 0 ? (minutes / count).toFixed(1) : '0';
        const line = data.lines[i] || '-';
        tbody.innerHTML += `
            <tr class="border-b border-gray-100 hover:bg-gray-50">
                <td class="py-2 px-3 font-medium">${label}</td>
                <td class="py-2 px-3">${line}</td>
                <td class="py-2 px-3 text-right">${minutes.toFixed(0)}</td>
                <td class="py-2 px-3 text-right">${count}</td>
                <td class="py-2 px-3 text-right">${avg}</td>
            </tr>`;
    });
}

document.addEventListener('DOMContentLoaded', loadData);
</script>
@endsection

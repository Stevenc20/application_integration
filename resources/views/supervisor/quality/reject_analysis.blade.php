@extends('layouts.supervisor')

@section('title', 'Reject Analysis')

@section('head')
<!-- Include Chart.js for Graphic Analysis -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h4 class="text-xl font-bold text-gray-800">Reject Analysis</h4>
        <form class="flex items-center w-full md:w-auto">
            <input type="month" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Update</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
            <h6 class="font-bold text-gray-700 mb-4 text-center">Top 5 Jenis Reject</h6>
            <div class="h-64 relative">
                <canvas id="rejectTypeChart"></canvas>
            </div>
        </div>
        <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm">
            <h6 class="font-bold text-gray-700 mb-4 text-center">Tren Reject per Minggu</h6>
            <div class="h-64 relative">
                <canvas id="rejectTrendChart"></canvas>
            </div>
        </div>
    </div>
    
    <h5 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Tindakan Korektif (CAPA)</h5>
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Masalah</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Root Cause</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Countermeasure</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-sm text-gray-700">01-Oct-2023</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Baret / Scratch tinggi di M12</td>
                    <td class="px-4 py-3 text-sm text-gray-700">Guide pin aus</td>
                    <td class="px-4 py-3 text-sm text-gray-700">Penggantian guide pin & set jadwal PM</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Closed</span>
                    </td>
                </tr>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-sm text-gray-700">05-Oct-2023</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Dimensi Out PN-67890</td>
                    <td class="px-4 py-3 text-sm text-gray-700">Settingan stopper geser</td>
                    <td class="px-4 py-3 text-sm text-gray-700">Perkuat baut stopper & sosialisasi operator</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Monitoring</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Mock data for charts
        const ctxType = document.getElementById('rejectTypeChart').getContext('2d');
        new Chart(ctxType, {
            type: 'pie',
            data: {
                labels: ['Baret', 'Penyok', 'Dimensi Out', 'Retak', 'Lainnya'],
                datasets: [{
                    data: [45, 25, 15, 10, 5],
                    backgroundColor: [
                        '#be123c', // rose-700
                        '#fbbf24', // amber-400
                        '#3b82f6', // blue-500
                        '#10b981', // emerald-500
                        '#9ca3af'  // gray-400
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });

        const ctxTrend = document.getElementById('rejectTrendChart').getContext('2d');
        new Chart(ctxTrend, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [{
                    label: 'Total Reject',
                    data: [120, 95, 110, 80],
                    backgroundColor: '#881337', // rose-900
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
</script>
@endsection

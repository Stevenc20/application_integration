@extends('layouts.supervisor')

@section('title', 'Tren Downtime')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Tren Downtime Mingguan</h5>
    </div>
    
    <div class="p-5">
        <form class="flex items-center w-full md:w-auto mb-6">
            <input type="month" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Update Grafik</button>
        </form>

        <div class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm">
            <h6 class="font-bold text-gray-700 mb-4 text-center">Total Durasi Downtime per Minggu (Menit)</h6>
            <div class="h-80 relative">
                <canvas id="downtimeTrendChart"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('downtimeTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [
                    {
                        label: 'Total Downtime',
                        data: [1200, 1500, 900, 1100],
                        borderColor: '#881337', // rose-900
                        backgroundColor: 'rgba(136, 19, 55, 0.1)',
                        tension: 0.3,
                        fill: true
                    }
                ]
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

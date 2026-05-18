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
        <form class="flex items-center w-full md:w-auto mb-6">
            <input type="month" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Update Grafik</button>
        </form>

        <div class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm">
            <h6 class="font-bold text-gray-700 mb-4 text-center">Top 5 Mesin dengan Downtime Tertinggi (Menit)</h6>
            <div class="h-80 relative">
                <canvas id="downtimeMachineChart"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('downtimeMachineChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['M12', 'M05', 'M08', 'M21', 'M03'],
                datasets: [
                    {
                        label: 'Total Durasi (Menit)',
                        data: [350, 280, 200, 120, 80],
                        backgroundColor: '#be123c', // rose-700
                        borderRadius: 4
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

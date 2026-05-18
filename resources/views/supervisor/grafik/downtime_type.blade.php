@extends('layouts.supervisor')

@section('title', 'Grafik Downtime Tipe')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Grafik Downtime per Tipe/Kategori</h5>
    </div>
    
    <div class="p-5">
        <form class="flex items-center w-full md:w-auto mb-6">
            <input type="month" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Update Grafik</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm flex flex-col justify-center">
                <h6 class="font-bold text-gray-700 mb-4 text-center">Distribusi Kategori Downtime</h6>
                <div class="h-64 relative">
                    <canvas id="downtimeTypeChart"></canvas>
                </div>
            </div>
            
            <div class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm">
                <h6 class="font-bold text-gray-700 mb-4">Detail Persentase</h6>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">Planned Downtime (Dandori, Setup)</span>
                            <span class="font-bold text-gray-900">45%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 45%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">Unplanned (Mesin Error, Trouble)</span>
                            <span class="font-bold text-gray-900">35%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-red-500 h-2 rounded-full" style="width: 35%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">Waiting (Material, Manpower)</span>
                            <span class="font-bold text-gray-900">20%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-yellow-500 h-2 rounded-full" style="width: 20%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('downtimeTypeChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['Planned Downtime', 'Unplanned Downtime', 'Waiting'],
                datasets: [{
                    data: [45, 35, 20],
                    backgroundColor: [
                        '#3b82f6', // blue-500
                        '#ef4444', // red-500
                        '#eab308'  // yellow-500
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    });
</script>
@endsection

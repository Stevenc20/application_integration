@extends('layouts.supervisor')

@section('title', 'Performance Report')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Performance Report (OEE)</h5>
    </div>
    
    <div class="p-5">
        <form class="flex items-center w-full md:w-auto mb-6">
            <input type="month" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Filter</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="border border-gray-200 rounded-lg p-5 bg-white shadow-sm text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-blue-500"></div>
                <h6 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Availability</h6>
                <div class="flex justify-center items-end gap-2">
                    <h2 class="text-4xl font-bold text-gray-800">85<span class="text-2xl text-gray-500">%</span></h2>
                </div>
            </div>
            <div class="border border-gray-200 rounded-lg p-5 bg-white shadow-sm text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-yellow-500"></div>
                <h6 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Performance</h6>
                <div class="flex justify-center items-end gap-2">
                    <h2 class="text-4xl font-bold text-gray-800">92<span class="text-2xl text-gray-500">%</span></h2>
                </div>
            </div>
            <div class="border border-gray-200 rounded-lg p-5 bg-white shadow-sm text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-green-500"></div>
                <h6 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Quality</h6>
                <div class="flex justify-center items-end gap-2">
                    <h2 class="text-4xl font-bold text-gray-800">98<span class="text-2xl text-gray-500">%</span></h2>
                </div>
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm mb-6 text-center">
            <h6 class="text-lg font-bold text-gray-800 mb-2">Overall Equipment Effectiveness (OEE)</h6>
            <h1 class="text-6xl font-black text-primary-red">76.6<span class="text-3xl">%</span></h1>
            <p class="text-sm text-gray-500 mt-2">Target OEE: 80%</p>
        </div>

        <div class="h-64 relative border border-gray-200 rounded-lg p-4">
            <canvas id="oeeChart"></canvas>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('oeeChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                datasets: [
                    {
                        label: 'OEE (%)',
                        data: [72, 75, 78, 76.6],
                        borderColor: '#881337', // rose-900
                        backgroundColor: 'rgba(136, 19, 55, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Target (80%)',
                        data: [80, 80, 80, 80],
                        borderColor: '#10b981', // emerald-500
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        min: 60,
                        max: 100 
                    }
                }
            }
        });
    });
</script>
@endsection

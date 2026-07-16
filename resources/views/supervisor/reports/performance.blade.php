@extends('layouts.supervisor')

@section('title', 'Performance Report (OEE)')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Performance Report (OEE)</h5>
        <p class="text-sm text-gray-500 mt-1">Overall Equipment Effectiveness — Six Big Losses</p>
    </div>
    
    <div class="p-5">
        <form class="flex items-center w-full md:w-auto mb-6" method="GET" action="{{ route('supervisor.reports.performance') }}">
            <input type="month" name="month" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ $month ?? date('Y-m') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Filter</button>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="border border-gray-200 rounded-lg p-5 bg-white shadow-sm text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-blue-500"></div>
                <h6 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Availability</h6>
                <div class="flex justify-center items-end gap-2">
                    <h2 class="text-4xl font-bold text-gray-800">{{ number_format($avgAvailability, 1) }}<span class="text-2xl text-gray-500">%</span></h2>
                </div>
                <p class="text-xs text-gray-400 mt-1">(operating / planned production)</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-5 bg-white shadow-sm text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-yellow-500"></div>
                <h6 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Performance</h6>
                <div class="flex justify-center items-end gap-2">
                    <h2 class="text-4xl font-bold text-gray-800">{{ number_format($avgPerformance, 1) }}<span class="text-2xl text-gray-500">%</span></h2>
                </div>
                <p class="text-xs text-gray-400 mt-1">(ideal press / operating)</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-5 bg-white shadow-sm text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-green-500"></div>
                <h6 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-2">Quality</h6>
                <div class="flex justify-center items-end gap-2">
                    <h2 class="text-4xl font-bold text-gray-800">{{ number_format($avgQuality, 1) }}<span class="text-2xl text-gray-500">%</span></h2>
                </div>
                <p class="text-xs text-gray-400 mt-1">(good / total stroke)</p>
            </div>
        </div>

        <div class="border border-gray-200 rounded-lg p-6 bg-white shadow-sm mb-6 text-center">
            <h6 class="text-lg font-bold text-gray-800 mb-2">Overall Equipment Effectiveness (OEE)</h6>
            @php
                $oeeClass = 'text-red-600';
                if ($avgOee >= 85) $oeeClass = 'text-emerald-600';
                elseif ($avgOee >= 65) $oeeClass = 'text-amber-600';
            @endphp
            <h1 class="text-6xl font-black {{ $oeeClass }}">{{ number_format($avgOee, 1) }}<span class="text-3xl">%</span></h1>
            <p class="text-sm text-gray-500 mt-2">Target OEE: <span class="font-bold text-emerald-600">80%</span></p>
        </div>

        <div class="h-72 relative border border-gray-200 rounded-lg p-4 mb-8">
            <canvas id="oeeChart"></canvas>
        </div>

        @if (count($dailyRecords) > 0)
        <div class="overflow-x-auto rounded-xl border border-gray-300 shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase">Shift</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-blue-600 uppercase">Availability</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-yellow-600 uppercase">Performance</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-green-600 uppercase">Quality</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-800 uppercase">OEE</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Good Pcs</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase">Total Stroke</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach ($dailyRecords as $rec)
                    @php
                        $oeePct = $rec['oee'];
                        $rowClass = $oeePct >= 85 ? 'text-emerald-700' : ($oeePct >= 65 ? 'text-amber-700' : 'text-red-600');
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($rec['date'])->format('d M Y') }}</td>
                        <td class="px-4 py-2 text-sm text-gray-600">{{ $rec['shift'] }}</td>
                        <td class="px-4 py-2 text-sm text-right font-semibold text-blue-700">{{ number_format($rec['availability'], 1) }}%</td>
                        <td class="px-4 py-2 text-sm text-right font-semibold text-yellow-700">{{ number_format($rec['performance'], 1) }}%</td>
                        <td class="px-4 py-2 text-sm text-right font-semibold text-green-700">{{ number_format($rec['quality'], 1) }}%</td>
                        <td class="px-4 py-2 text-sm text-right font-bold {{ $rowClass }}">{{ number_format($oeePct, 1) }}%</td>
                        <td class="px-4 py-2 text-sm text-right text-gray-700">{{ number_format($rec['good']) }}</td>
                        <td class="px-4 py-2 text-sm text-right text-gray-700">{{ number_format($rec['stroke']) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const labels = {!! json_encode(array_keys($weeklyOee)) !!};
        const oeeData = {!! json_encode(array_values($weeklyOee)) !!};
        const target = 80;

        const ctx = document.getElementById('oeeChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'OEE (%)',
                        data: oeeData,
                        borderColor: '#881337',
                        backgroundColor: 'rgba(136, 19, 55, 0.1)',
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#881337',
                        pointRadius: 4,
                    },
                    {
                        label: 'Target (80%)',
                        data: Array(labels.length).fill(target),
                        borderColor: '#10b981',
                        borderDash: [5, 5],
                        fill: false,
                        pointRadius: 0,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Monthly OEE Trend ({{ $month ?? date("Y-m") }})',
                        font: { weight: 'bold', size: 14 }
                    },
                    legend: {
                        position: 'bottom',
                    }
                },
                scales: {
                    y: { 
                        min: 0,
                        max: 100,
                        ticks: { callback: v => v + '%' }
                    }
                }
            }
        });
    });
</script>
@endsection

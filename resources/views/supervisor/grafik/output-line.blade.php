@extends('layouts.supervisor')

@section('title', 'Grafik Pencapaian Output')
@section('header_title', 'Grafik Output')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<style>
    .chart-container {
        position: relative;
        height: 380px;
        width: 100%;
    }
</style>
@endsection

@section('content')
<div class="space-y-6">

    <!-- ========== HEADER ========== -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Pencapaian Output Harian</h2>
            <p class="text-sm text-gray-500 mt-1">Line: <span class="font-medium text-primary-red">{{ $selected_line_name ?? 'Semua Line' }}</span></p>
        </div>
        <div class="mt-3 md:mt-0 text-left md:text-right">
            <h5 class="text-sm text-gray-600 font-medium">Periode: {{ isset($first_day) ? \Carbon\Carbon::parse($first_day)->format('d-m-Y') : date('01-m-Y') }} s/d {{ isset($effective_end_date) ? \Carbon\Carbon::parse($effective_end_date)->format('d-m-Y') : date('t-m-Y') }}</h5>
            <h5 class="text-sm text-gray-600 font-medium mt-1">Waktu: <span id="liveClock" class="text-blue-600 font-bold"></span></h5>
        </div>
    </div>

    <!-- ============ FILTER ============ -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tahun</label>
                <select name="year" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                    @foreach($year_options ?? [2023, 2024, 2025, 2026] as $y)
                        <option value="{{ $y }}" {{ isset($selected_year) && $y == $selected_year ? 'selected' : (date('Y') == $y ? 'selected' : '') }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Bulan</label>
                <select name="month" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                    @foreach($month_options ?? range(1, 12) as $m)
                        <option value="{{ $m }}" {{ isset($selected_month) && $m == $selected_month ? 'selected' : (date('n') == $m ? 'selected' : '') }}>
                            {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Filter Line (Opsional)</label>
                <select name="line" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                    <option value="">Semua Line</option>
                    @foreach($all_lines ?? ['Line A', 'Line B'] as $nama)
                        <option value="{{ $nama }}" {{ isset($selected_line_value) && $nama == $selected_line_value ? 'selected' : '' }}>
                            {{ $nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors border border-transparent h-[42px]">
                    Tampilkan
                </button>
            </div>
        </form>
    </div>

    <!-- ============ CHART ============ -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">Grafik Aktual vs Target Output – {{ $selected_month_label ?? date('F Y') }}</h3>
            <span class="text-xs bg-gray-200 text-gray-700 font-medium px-2 py-1 rounded">Qty (Pcs) / GSPH Target</span>
        </div>
        <div class="p-4">
            <div class="chart-container">
                <canvas id="outputChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ============ TABLE ============ -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-gray-800">Detail Output Harian – {{ $selected_month_label ?? date('F Y') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-200 text-gray-700">
                        <th class="py-3 px-4 text-center font-semibold">Tanggal</th>
                        <th class="py-3 px-4 text-center font-semibold">GSPH Plan</th>
                        <th class="py-3 px-4 text-center font-semibold">Actual Qty</th>
                        <th class="py-3 px-4 text-center font-semibold">Selisih</th>
                        <th class="py-3 px-4 text-center font-semibold">Status (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($table_rows ?? [] as $row)
                        @php
                            $isShortfall = $row['diff'] < 0;
                            $diffClass = $isShortfall ? 'text-red-600' : 'text-green-600';
                            $statusClass = $row['percent'] >= 100 ? 'bg-green-100 text-green-800' : ($row['percent'] >= 80 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-center text-gray-600 font-medium">{{ \Carbon\Carbon::parse($row['date'])->format('d-m-Y') }}</td>
                            <td class="py-3 px-4 text-center text-gray-600">{{ number_format($row['gsph_plan']) }}</td>
                            <td class="py-3 px-4 text-center font-bold text-gray-900">{{ number_format($row['actual']) }}</td>
                            <td class="py-3 px-4 text-center font-semibold {{ $diffClass }}">{{ $row['diff'] > 0 ? '+' : '' }}{{ number_format($row['diff']) }}</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-1 rounded text-xs font-bold {{ $statusClass }}">{{ number_format($row['percent'], 1) }}%</span>
                            </td>
                        </tr>
                    @empty
                        <!-- Template Fallback -->
                        @if(empty($table_rows))
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-center text-gray-600 font-medium">01-{{ str_pad($selected_month ?? date('m'), 2, '0', STR_PAD_LEFT) }}-{{ $selected_year ?? date('Y') }}</td>
                            <td class="py-3 px-4 text-center text-gray-600">5000</td>
                            <td class="py-3 px-4 text-center font-bold text-gray-900">4500</td>
                            <td class="py-3 px-4 text-center font-semibold text-red-600">-500</td>
                            <td class="py-3 px-4 text-center">
                                <span class="px-2 py-1 rounded text-xs font-bold bg-yellow-100 text-yellow-800">90.0%</span>
                            </td>
                        </tr>
                        @endif
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">TOTAL BULAN INI</th>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">{{ number_format($total_plan ?? 5000) }}</th>
                        <th class="py-3 px-4 text-center font-bold text-gray-900">{{ number_format($total_actual ?? 4500) }}</th>
                        <th class="py-3 px-4 text-center font-bold {{ ($total_diff ?? -500) < 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ ($total_diff ?? -500) > 0 ? '+' : '' }}{{ number_format($total_diff ?? -500) }}
                        </th>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">
                            {{ number_format($avg_percent ?? 90.0, 1) }}%
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function updateClock() {
        const el = document.getElementById('liveClock');
        if (!el) return;
        const now = new Date();
        el.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
    }
    setInterval(updateClock, 1000);
    updateClock();

    Chart.register(ChartDataLabels);

    const labels = {!! isset($labels_json) ? $labels_json : '["01", "02", "03", "04"]' !!};
    const dataPlan = {!! isset($data_plan_json) ? $data_plan_json : '[5000, 5000, 5000, 5000]' !!};
    const dataActual = {!! isset($data_actual_json) ? $data_actual_json : '[4500, 5100, 4800, 5200]' !!};
    const dataPercent = {!! isset($data_percent_json) ? $data_percent_json : '[90.0, 102.0, 96.0, 104.0]' !!};

    const textColor = '#374151'; // text-gray-700
    const gridColor = '#e5e7eb'; // border-gray-200
    const isMobile = window.innerWidth < 768;
    const fontCfg = { size: isMobile ? 10 : 12, family: "'Inter', sans-serif" };

    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('outputChart');
        if (ctx && labels.length > 0) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Actual Qty',
                            data: dataActual,
                            backgroundColor: 'rgba(37, 99, 235, 0.7)', // Blue
                            borderColor: '#2563eb',
                            borderWidth: 1,
                            borderRadius: 4,
                            yAxisID: 'y',
                            datalabels: {
                                display: !isMobile,
                                anchor: 'end',
                                align: 'top',
                                color: '#2563eb',
                                font: { weight: 'bold', size: 10 },
                                formatter: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        },
                        {
                            type: 'line',
                            label: 'GSPH Plan Target',
                            data: dataPlan,
                            borderColor: '#991b1b', // Red
                            backgroundColor: '#991b1b',
                            borderWidth: 2,
                            borderDash: [5, 5], // Dashed line for target
                            fill: false,
                            tension: 0,
                            pointRadius: 0, // Hide points
                            yAxisID: 'y',
                            datalabels: { display: false } // No labels for plan line
                        },
                        {
                            type: 'line',
                            label: 'Pencapaian (%)',
                            data: dataPercent,
                            borderColor: '#16a34a', // Green
                            backgroundColor: '#16a34a',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3,
                            yAxisID: 'y1',
                            datalabels: {
                                display: !isMobile,
                                anchor: 'end',
                                align: 'bottom',
                                color: '#16a34a',
                                font: { weight: 'bold', size: 10 },
                                formatter: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            position: 'left',
                            beginAtZero: true,
                            title: { display: !isMobile, text: 'Quantity (Pcs)', color: textColor, font: fontCfg },
                            ticks: { color: textColor, font: fontCfg },
                            grid: { color: gridColor }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            grid: { drawOnChartArea: false },
                            title: { display: !isMobile, text: 'Pencapaian (%)', color: textColor, font: fontCfg },
                            ticks: { color: textColor, font: fontCfg }
                        },
                        x: {
                            ticks: { color: textColor, font: fontCfg },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { labels: { color: textColor, font: fontCfg, usePointStyle: true } },
                        tooltip: { mode: 'index', intersect: false }
                    }
                }
            });
        }
    });
</script>
@endsection

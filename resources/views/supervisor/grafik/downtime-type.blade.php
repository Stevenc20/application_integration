@extends('layouts.supervisor')

@section('title', 'Grafik Downtime per Jenis')
@section('header_title', 'Grafik Downtime')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    .chart-container {
        position: relative;
        height: 340px;
        width: 100%;
    }
</style>
@endsection

@section('content')
<div class="space-y-6">

    <!-- ========== HEADER ========== -->
    <div class="flex flex-col md:flex-row md:justify-between md:items-center bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div>
            <h2 class="text-xl font-bold text-gray-800">Grafik Downtime per Jenis (Bulanan)</h2>
            <p class="text-sm text-gray-500 mt-1">Line: <span class="font-medium text-primary-red">{{ $header_line_name ?? 'Semua Line' }}</span></p>
        </div>
        <div class="mt-3 md:mt-0 text-left md:text-right">
            <h5 class="text-sm text-gray-600 font-medium">Periode: {{ isset($first_day) ? \Carbon\Carbon::parse($first_day)->format('d-m-Y') : date('01-m-Y') }} s/d {{ isset($effective_end_date) ? \Carbon\Carbon::parse($effective_end_date)->format('d-m-Y') : date('t-m-Y') }}</h5>
            <h5 class="text-sm text-gray-600 font-medium mt-1">Waktu: <span id="liveClock" class="text-blue-600 font-bold"></span></h5>
        </div>
    </div>

    <!-- ========== FILTER ========== -->
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

    <!-- ========== ROW 1: CHART PER JENIS & HARIAN ========== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="font-bold text-gray-800">Total Downtime per Jenis (Menit) – {{ $selected_month_label ?? date('F Y') }}</h3>
            </div>
            <div class="p-4 flex-1">
                <div class="chart-container">
                    <canvas id="chartByType"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="font-bold text-gray-800">Total Downtime Harian (Menit) – {{ $selected_month_label ?? date('F Y') }}</h3>
            </div>
            <div class="p-4 flex-1">
                <div class="chart-container">
                    <canvas id="chartDailyTotal"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== ROW 2: TABEL HARIAN ========== -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-gray-800">Detail Downtime Harian per Jenis – {{ $selected_month_label ?? date('F Y') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-200 text-gray-700">
                        <th class="py-3 px-4 text-center font-semibold">Tanggal</th>
                        <th class="py-3 px-4 text-center font-semibold">Prod (menit)</th>
                        <th class="py-3 px-4 text-center font-semibold">Mat (menit)</th>
                        <th class="py-3 px-4 text-center font-semibold">Dies (menit)</th>
                        <th class="py-3 px-4 text-center font-semibold">Mach (menit)</th>
                        <th class="py-3 px-4 text-center font-semibold">Log (menit)</th>
                        <th class="py-3 px-4 text-center font-semibold text-primary-red">Total (menit)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($table_by_date ?? [] as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 text-center text-gray-600 font-medium">{{ \Carbon\Carbon::parse($row['date'])->format('d-m') }}</td>
                            <td class="py-2 px-4 text-center text-gray-600">{{ number_format($row['prod_t'], 1) }}</td>
                            <td class="py-2 px-4 text-center text-gray-600">{{ number_format($row['mat_t'], 1) }}</td>
                            <td class="py-2 px-4 text-center text-gray-600">{{ number_format($row['dies_t'], 1) }}</td>
                            <td class="py-2 px-4 text-center text-gray-600">{{ number_format($row['mach_t'], 1) }}</td>
                            <td class="py-2 px-4 text-center text-gray-600">{{ number_format($row['log_t'], 1) }}</td>
                            <td class="py-2 px-4 text-center font-bold text-primary-red">{{ number_format($row['total'], 1) }}</td>
                        </tr>
                    @empty
                        <!-- Template Fallback -->
                        @if(empty($table_by_date))
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 text-center text-gray-600 font-medium">01-{{ str_pad($selected_month ?? date('m'), 2, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-2 px-4 text-center text-gray-600">10.0</td>
                            <td class="py-2 px-4 text-center text-gray-600">5.0</td>
                            <td class="py-2 px-4 text-center text-gray-600">20.0</td>
                            <td class="py-2 px-4 text-center text-gray-600">15.0</td>
                            <td class="py-2 px-4 text-center text-gray-600">0.0</td>
                            <td class="py-2 px-4 text-center font-bold text-primary-red">50.0</td>
                        </tr>
                        @endif
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">TOTAL</th>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">{{ number_format($total_prod ?? 10.0, 1) }}</th>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">{{ number_format($total_mat ?? 5.0, 1) }}</th>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">{{ number_format($total_dies ?? 20.0, 1) }}</th>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">{{ number_format($total_mach ?? 15.0, 1) }}</th>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">{{ number_format($total_log ?? 0.0, 1) }}</th>
                        <th class="py-3 px-4 text-center font-bold text-primary-red">{{ number_format($total_all ?? 50.0, 1) }}</th>
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

    const labelsByType = {!! isset($labels_json) ? $labels_json : '["Produksi", "Material", "Dies", "Mesin", "Logistik"]' !!};
    const dataByType = {!! isset($data_minutes_json) ? $data_minutes_json : '[10.0, 5.0, 20.0, 15.0, 0.0]' !!};

    const dailyLabels = {!! isset($daily_labels_json) ? $daily_labels_json : '["01", "02", "03", "04"]' !!};
    const dailyTotals = {!! isset($daily_totals_json) ? $daily_totals_json : '[50.0, 20.0, 10.0, 30.0]' !!};

    const textColor = '#374151'; // text-gray-700
    const gridColor = '#e5e7eb'; // border-gray-200
    const isMobile = window.innerWidth < 768;
    const fontCfg = { size: isMobile ? 10 : 12, family: "'Inter', sans-serif" };

    function commonOptions(yTitle) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: !isMobile, text: yTitle, color: textColor, font: fontCfg },
                    ticks: { color: textColor, font: fontCfg },
                    grid: { color: gridColor }
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
        };
    }

    document.addEventListener("DOMContentLoaded", function () {
        const ctxType = document.getElementById('chartByType');
        if (ctxType && labelsByType.length > 0) {
            new Chart(ctxType, {
                type: 'bar',
                data: {
                    labels: labelsByType,
                    datasets: [{
                        label: 'Total Downtime (menit)',
                        data: dataByType,
                        backgroundColor: [
                            'rgba(153, 27, 27, 0.7)', // red
                            'rgba(37, 99, 235, 0.7)', // blue
                            'rgba(22, 163, 74, 0.7)', // green
                            'rgba(217, 119, 6, 0.7)', // amber
                            'rgba(147, 51, 234, 0.7)'  // purple
                        ],
                        borderColor: [
                            '#991b1b', '#2563eb', '#16a34a', '#d97706', '#9333ea'
                        ],
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: commonOptions('Menit')
            });
        }

        const ctxDaily = document.getElementById('chartDailyTotal');
        if (ctxDaily && dailyLabels.length > 0) {
            new Chart(ctxDaily, {
                type: 'line',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Total Downtime (menit)',
                        data: dailyTotals,
                        borderColor: '#991b1b', // Primary red
                        backgroundColor: 'rgba(153, 27, 27, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: '#991b1b'
                    }]
                },
                options: commonOptions('Menit')
            });
        }
    });
</script>
@endsection

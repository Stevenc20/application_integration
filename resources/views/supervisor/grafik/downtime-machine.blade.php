@extends('layouts.supervisor')

@section('title', 'Grafik Downtime Mesin')
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
            <h2 class="text-xl font-bold text-gray-800">Grafik Downtime Mesin Bulanan</h2>
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

    <!-- ============ ROW 1: CHARTS ============ -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="font-bold text-gray-800">Total Downtime per Mesin (Menit) – {{ $selected_month_label ?? date('F Y') }}</h3>
            </div>
            <div class="p-4 flex-1">
                <div class="chart-container">
                    <canvas id="downtimeMinutesChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="font-bold text-gray-800">Frekuensi Downtime per Mesin – {{ $selected_month_label ?? date('F Y') }}</h3>
            </div>
            <div class="p-4 flex-1">
                <div class="chart-container">
                    <canvas id="downtimeFreqChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ ROW 2: TABLE ============ -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-gray-800">Detail Downtime Mesin dalam Bulan {{ $selected_month_label ?? date('F Y') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-200 text-gray-700">
                        <th class="py-3 px-4 text-center font-semibold w-16">No</th>
                        <th class="py-3 px-4 font-semibold">Mesin</th>
                        <th class="py-3 px-4 text-center font-semibold w-64">Total Downtime (menit)</th>
                        <th class="py-3 px-4 text-center font-semibold w-64">Frekuensi Kejadian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($table_rows ?? [] as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-center text-gray-500">{{ $row['no'] }}</td>
                            <td class="py-3 px-4 text-gray-900 font-medium">{{ $row['machine_code'] }}</td>
                            <td class="py-3 px-4 text-center font-bold text-primary-red">
                                {{ number_format($row['total_minutes'], 1) }}
                            </td>
                            <td class="py-3 px-4 text-center text-gray-600">
                                {{ $row['freq'] }}
                            </td>
                        </tr>
                    @empty
                        <!-- Template Fallback -->
                        @if(empty($table_rows))
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-center text-gray-500">1</td>
                            <td class="py-3 px-4 text-gray-900 font-medium">M-01</td>
                            <td class="py-3 px-4 text-center font-bold text-primary-red">45.5</td>
                            <td class="py-3 px-4 text-center text-gray-600">3</td>
                        </tr>
                        @endif
                    @endforelse
                </tbody>
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

    const labels = {!! isset($labels_json) ? $labels_json : '["M-01", "M-02", "M-03"]' !!};
    const dataMinutes = {!! isset($data_minutes_json) ? $data_minutes_json : '[45.5, 30.0, 10.0]' !!};
    const dataFreq = {!! isset($data_freq_json) ? $data_freq_json : '[3, 2, 1]' !!};

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
        const ctxMinutes = document.getElementById('downtimeMinutesChart');
        if (ctxMinutes && labels.length > 0) {
            new Chart(ctxMinutes, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Total Downtime (menit)',
                        data: dataMinutes,
                        backgroundColor: 'rgba(153, 27, 27, 0.7)',
                        borderColor: '#991b1b',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: commonOptions('Menit')
            });
        }

        const ctxFreq = document.getElementById('downtimeFreqChart');
        if (ctxFreq && labels.length > 0) {
            new Chart(ctxFreq, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Frekuensi Downtime',
                        data: dataFreq,
                        backgroundColor: 'rgba(37, 99, 235, 0.7)',
                        borderColor: '#2563eb',
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: Object.assign({}, commonOptions('Frekuensi (x)'), {
                    scales: {
                        y: {
                            ticks: { stepSize: 1, color: textColor, font: fontCfg },
                            title: { display: !isMobile, text: 'Frekuensi (x)', color: textColor, font: fontCfg },
                            grid: { color: gridColor }
                        }
                    }
                })
            });
        }
    });
</script>
@endsection

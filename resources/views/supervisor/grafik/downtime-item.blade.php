@extends('layouts.supervisor')

@section('title', 'Grafik Downtime per Item')
@section('header_title', 'Grafik Downtime')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
    /* Custom styles specific for charts page if needed */
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
            <h2 class="text-xl font-bold text-gray-800">Grafik Downtime per Item (Bulanan)</h2>
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

    <!-- ========== ROW 1: PARETO & MONITORING ========== -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="font-bold text-gray-800">Pareto Downtime per Item – {{ $selected_month_label ?? date('F Y') }}</h3>
            </div>
            <div class="p-4 flex-1">
                <div class="chart-container">
                    <canvas id="paretoItemChart"></canvas>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden flex flex-col h-full">
            <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
                <h3 class="font-bold text-gray-800">Monitoring Downtime Harian per Item (Top 5) – {{ $selected_month_label ?? date('F Y') }}</h3>
            </div>
            <div class="p-4 flex-1">
                <div class="chart-container">
                    <canvas id="monitorItemChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== ROW 2: TABEL PARETO ========== -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50">
            <h3 class="font-bold text-gray-800">Rekap Downtime per Item – {{ $selected_month_label ?? date('F Y') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-200 text-gray-700">
                        <th class="py-3 px-4 text-center font-semibold text-sm w-16">No</th>
                        <th class="py-3 px-4 font-semibold text-sm">Item (Job Number)</th>
                        <th class="py-3 px-4 text-center font-semibold text-sm w-48">Total Downtime (menit)</th>
                        <th class="py-3 px-4 text-center font-semibold text-sm w-32">Persentase (%)</th>
                        <th class="py-3 px-4 text-center font-semibold text-sm w-32">Kumulatif (%)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($table_rows ?? [] as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 text-center text-sm text-gray-600">{{ $row['no'] }}</td>
                            <td class="py-2 px-4 text-sm font-medium text-gray-900">{{ $row['item_name'] }}</td>
                            <td class="py-2 px-4 text-center text-sm font-bold text-primary-red">{{ number_format($row['minutes'], 1) }}</td>
                            <td class="py-2 px-4 text-center text-sm text-gray-600">{{ number_format($row['percent'], 1) }}</td>
                            <td class="py-2 px-4 text-center text-sm text-gray-600">{{ number_format($row['cum_percent'], 1) }}</td>
                        </tr>
                    @empty
                        <!-- Template Fallback -->
                        @if(empty($table_rows))
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 text-center text-sm text-gray-600">1</td>
                            <td class="py-2 px-4 text-sm font-medium text-gray-900">PART-A</td>
                            <td class="py-2 px-4 text-center text-sm font-bold text-primary-red">120.5</td>
                            <td class="py-2 px-4 text-center text-sm text-gray-600">60.0</td>
                            <td class="py-2 px-4 text-center text-sm text-gray-600">60.0</td>
                        </tr>
                        @endif
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <th colspan="2" class="py-3 px-4 text-center font-bold text-gray-800">TOTAL</th>
                        <th class="py-3 px-4 text-center font-bold text-primary-red">{{ number_format($total_all_minutes ?? 120.5, 1) }}</th>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">100.0</th>
                        <th class="py-3 px-4 text-center font-bold text-gray-800">100.0</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ========== ROW 3: HISTORY PROBLEM PER ITEM ========== -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800">History Downtime per Item (Detail Record)</h3>
            <span class="text-xs text-gray-500 font-medium bg-gray-200 px-2 py-1 rounded">Periode: {{ $selected_month_label ?? date('F Y') }}</span>
        </div>

        <div class="p-4 border-b border-gray-200 bg-white">
            <form method="get" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <input type="hidden" name="year" value="{{ $selected_year ?? date('Y') }}">
                <input type="hidden" name="month" value="{{ $selected_month ?? date('n') }}">
                @if(isset($selected_line_value) && $selected_line_value)
                <input type="hidden" name="line" value="{{ $selected_line_value }}">
                @endif

                <div class="md:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Filter Item (Job Number)</label>
                    <select name="item" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                        <option value="">Semua Item</option>
                        @foreach($item_options ?? ['PART-A', 'PART-B'] as $it)
                            <option value="{{ $it }}" {{ isset($selected_item) && $it == $selected_item ? 'selected' : '' }}>{{ $it }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Search (Problem / Cause / Action / Mesin / Job / Part)</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="cth: scratch, bearing, OP10, MT01">
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="w-full px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors border border-transparent h-[42px]">Cari</button>
                </div>

                <div class="md:col-span-1 flex justify-end pb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ count($history_rows ?? [1]) }} record
                    </span>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-100 border-b border-gray-200 text-gray-700 text-xs uppercase tracking-wider">
                        <th class="py-2 px-3 text-center font-semibold">No</th>
                        <th class="py-2 px-3 text-center font-semibold whitespace-nowrap">Tanggal</th>
                        <th class="py-2 px-3 text-center font-semibold">Line</th>
                        <th class="py-2 px-3 text-center font-semibold">Job No</th>
                        <th class="py-2 px-3 text-center font-semibold">Part No</th>
                        <th class="py-2 px-3 text-center font-semibold">Mesin</th>
                        <th class="py-2 px-3 text-center font-semibold">Jenis DT</th>
                        <th class="py-2 px-3 font-semibold">Problem</th>
                        <th class="py-2 px-3 font-semibold">Cause</th>
                        <th class="py-2 px-3 font-semibold">Action</th>
                        <th class="py-2 px-3 text-right font-semibold">Stroke</th>
                        <th class="py-2 px-3 text-right font-semibold whitespace-nowrap">Durasi (m)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($history_rows ?? [] as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 text-center text-gray-500">{{ $row['no'] }}</td>
                        <td class="py-2 px-3 text-center text-gray-600 whitespace-nowrap">{{ \Carbon\Carbon::parse($row['tanggal'])->format('d-m-Y') }}</td>
                        <td class="py-2 px-3 text-center text-gray-800 font-medium">{{ $row['line_name'] }}</td>
                        <td class="py-2 px-3 text-center text-gray-600">{{ $row['job_number'] }}</td>
                        <td class="py-2 px-3 text-center text-gray-600">{{ $row['part_number'] }}</td>
                        <td class="py-2 px-3 text-center text-gray-600">{{ $row['machine_code'] }}</td>
                        <td class="py-2 px-3 text-center text-gray-600">
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full border border-gray-200">{{ $row['jenis_dt'] }}</span>
                        </td>
                        <td class="py-2 px-3 text-gray-800">{{ $row['problem'] }}</td>
                        <td class="py-2 px-3 text-gray-600">{{ $row['cause'] }}</td>
                        <td class="py-2 px-3 text-gray-600">{{ $row['action'] }}</td>
                        <td class="py-2 px-3 text-right text-gray-600">{{ number_format($row['stroke']) }}</td>
                        <td class="py-2 px-3 text-right font-bold text-primary-red">{{ number_format($row['duration_minutes'], 1) }}</td>
                    </tr>
                    @empty
                    <!-- Template Fallback -->
                    @if(empty($history_rows))
                    <tr class="hover:bg-gray-50">
                        <td class="py-2 px-3 text-center text-gray-500">1</td>
                        <td class="py-2 px-3 text-center text-gray-600 whitespace-nowrap">01-10-2023</td>
                        <td class="py-2 px-3 text-center text-gray-800 font-medium">Line A</td>
                        <td class="py-2 px-3 text-center text-gray-600">JOB-123</td>
                        <td class="py-2 px-3 text-center text-gray-600">PART-A</td>
                        <td class="py-2 px-3 text-center text-gray-600">M-01</td>
                        <td class="py-2 px-3 text-center text-gray-600">
                            <span class="px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-full border border-gray-200">Mesin</span>
                        </td>
                        <td class="py-2 px-3 text-gray-800">Rusak</td>
                        <td class="py-2 px-3 text-gray-600">Aus</td>
                        <td class="py-2 px-3 text-gray-600">Ganti part</td>
                        <td class="py-2 px-3 text-right text-gray-600">100</td>
                        <td class="py-2 px-3 text-right font-bold text-primary-red">120.5</td>
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

    // Data from context or dummy for template
    const paretoLabels = {!! isset($pareto_labels_json) ? $pareto_labels_json : '["PART-A", "PART-B", "PART-C"]' !!};
    const paretoMinutes = {!! isset($pareto_minutes_json) ? $pareto_minutes_json : '[120.5, 80.2, 45.0]' !!};
    const paretoCumPercent = {!! isset($pareto_cum_percent_json) ? $pareto_cum_percent_json : '[49, 81.6, 100]' !!};

    const monitorLabels = {!! isset($monitor_labels_json) ? $monitor_labels_json : '["01", "02", "03", "04", "05"]' !!};
    const monitorDatasetsPy = {!! isset($monitor_datasets_json) ? $monitor_datasets_json : '[{"label": "PART-A", "data": [10, 20, 0, 50, 40]}]' !!};

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
                legend: { labels: { color: textColor, font: fontCfg } }
            }
        };
    }

    document.addEventListener("DOMContentLoaded", function () {
        // PARETO CHART
        const ctxPareto = document.getElementById('paretoItemChart');
        if (ctxPareto && paretoLabels.length > 0) {
            new Chart(ctxPareto, {
                type: 'bar',
                data: {
                    labels: paretoLabels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Total Downtime (menit)',
                            data: paretoMinutes,
                            yAxisID: 'y',
                            backgroundColor: 'rgba(153, 27, 27, 0.7)', // Primary red with opacity
                            borderColor: '#991b1b',
                            borderWidth: 1,
                            borderRadius: 4
                        },
                        {
                            type: 'line',
                            label: 'Kumulatif (%)',
                            data: paretoCumPercent,
                            yAxisID: 'y1',
                            borderColor: '#2563eb', // Blue-600
                            backgroundColor: '#2563eb',
                            borderWidth: 2,
                            fill: false,
                            tension: 0.3,
                            pointBackgroundColor: '#2563eb'
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
                            title: { display: !isMobile, text: 'Menit', color: textColor, font: fontCfg },
                            ticks: { color: textColor, font: fontCfg },
                            grid: { color: gridColor }
                        },
                        y1: {
                            position: 'right',
                            beginAtZero: true,
                            max: 100,
                            grid: { drawOnChartArea: false },
                            title: { display: !isMobile, text: 'Kumulatif (%)', color: textColor, font: fontCfg },
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

        // MONITORING CHART
        const ctxMonitor = document.getElementById('monitorItemChart');
        if (ctxMonitor && monitorLabels.length > 0 && monitorDatasetsPy.length > 0) {
            const colors = ['#991b1b', '#2563eb', '#16a34a', '#d97706', '#9333ea'];
            const jsDatasets = monitorDatasetsPy.map((ds, idx) => ({
                label: ds.label,
                data: ds.data,
                borderColor: colors[idx % colors.length],
                backgroundColor: colors[idx % colors.length],
                borderWidth: 2,
                fill: false,
                tension: 0.3
            }));

            new Chart(ctxMonitor, {
                type: 'line',
                data: {
                    labels: monitorLabels,
                    datasets: jsDatasets
                },
                options: commonOptions('Menit')
            });
        }
    });
</script>
@endsection

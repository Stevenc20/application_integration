@extends('layouts.app')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@push('styles')
<style>
.sum-card { background:white; border-radius:12px; padding:20px; border:1px solid #eee; display:flex; align-items:center; gap:16px; }
        .sum-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; }
        .sum-icon .material-icons { font-size:24px; color:white; }
        .sum-icon.blue   { background:#3b82f6; }
        .sum-icon.green  { background:#22c55e; }
        .sum-icon.yellow { background:#f59e0b; }
        .sum-icon.red    { background:#ef4444; }
        .sum-val { font-size:28px; font-weight:900; line-height:1; }
        .sum-lbl { font-size:11px; color:#888; font-weight:500; margin-top:3px; }

        /* Charts grid */
        .charts-grid { padding:20px 28px 28px; display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .chart-full { grid-column: 1 / -1; }
        .chart-card { background:white; border-radius:12px; border:1px solid #eee; padding:20px; }
        .chart-card h3 { font-size:13px; font-weight:800; color:#1a1a1a; margin-bottom:4px; }
        .chart-card p { font-size:11px; color:#aaa; margin-bottom:16px; }
        .chart-container { position:relative; }

        .empty-state { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:80px 20px; text-align:center; }
        .empty-state .material-icons { font-size:64px; color:#ddd; margin-bottom:16px; }
        .empty-state h3 { font-size:18px; font-weight:700; color:#aaa; margin-bottom:8px; }
        .empty-state p { font-size:13px; color:#ccc; max-width:320px; line-height:1.6; }
        .btn-go { margin-top:20px; background:var(--red-main); color:white; border:none; border-radius:10px; padding:12px 24px; font-size:13px; font-weight:700; display:inline-flex; align-items:center; gap:8px; text-decoration:none; }

        /* Legend */
        .legend { display:flex; gap:16px; flex-wrap:wrap; margin-top:12px; }
        .legend-item { display:flex; align-items:center; gap:6px; font-size:11px; color:#555; font-weight:500; }
        .legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
        
        .page-hero { padding:16px 24px; display:flex; align-items:center; gap:12px; background:var(--red-main); color:white; }
        .page-hero-left { display:flex; align-items:center; gap:12px; }
        .page-hero-left .material-icons { font-size:26px; opacity:0.8; }
        .page-hero h2 { font-size:20px; font-weight:900; }
        .page-hero p { font-size:11px; opacity:0.8; margin-top:2px; }
        
        .summary-row { padding:20px 28px 0; display:grid; grid-template-columns:repeat(4,1fr); gap:20px; }

        @media (max-width: 768px) {
            .page-hero { padding:14px 16px; }
            .page-hero h2 { font-size:16px; }
            .summary-row { grid-template-columns:repeat(2,1fr); padding:14px 16px 0; gap:10px; }
            .charts-grid { grid-template-columns:1fr; padding:14px 16px 20px; gap:14px; }
            .chart-full { grid-column:1; }
            .sum-val { font-size:22px; }
            .sum-icon { width:40px; height:40px; }
            .sum-card { padding:14px; gap:12px; }
        }

        @media (max-width: 480px) {
            .summary-row { grid-template-columns:repeat(2,1fr); gap:8px; }
            .sum-val { font-size:18px; }
        }
</style>
@endpush

@section('content')
    <div class="page-hero">
        <div class="page-hero-left">
            <span class="material-icons">pie_chart</span>
            <div>
                <h2>Data Finish Chart</h2>
                <p>Grafik analisis inventory finish part</p>
            </div>
        </div>
    </div>

    @if(!$hasData)
    <div class="empty-state">
        <span class="material-icons">upload_file</span>
        <h3>Belum ada data</h3>
        <p>Upload file Excel XLSM dari halaman Dashboard terlebih dahulu.</p>
        <a href="{{ route('stock.index') }}" class="btn-go">
            <span class="material-icons">arrow_back</span> Ke Dashboard
        </a>
    </div>
    @else

    {{-- Summary Cards --}}
    <div class="summary-row">
        <div class="sum-card">
            <div class="sum-icon blue"><span class="material-icons">inventory_2</span></div>
            <div>
                <div class="sum-val">{{ $totalAll }}</div>
                <div class="sum-lbl">Total Item</div>
            </div>
        </div>
        <div class="sum-card">
            <div class="sum-icon green"><span class="material-icons">trending_up</span></div>
            <div>
                <div class="sum-val" style="color:#16a34a">{{ $totalOver }}</div>
                <div class="sum-lbl">Over Stock</div>
            </div>
        </div>
        <div class="sum-card">
            <div class="sum-icon yellow"><span class="material-icons">warning</span></div>
            <div>
                <div class="sum-val" style="color:#d97706">{{ $totalLimited }}</div>
                <div class="sum-lbl">Limited Stock</div>
            </div>
        </div>
        <div class="sum-card">
            <div class="sum-icon red"><span class="material-icons">remove_circle</span></div>
            <div>
                <div class="sum-val" style="color:#dc2626">{{ $totalZero }}</div>
                <div class="sum-lbl">Zero / Kosong</div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="charts-grid">

        {{-- Chart 1: Stacked Bar per Customer (full width) --}}
        <div class="chart-card chart-full">
            <h3>Stock Level per Customer</h3>
            <p>Jumlah item Over Stock, Limited, dan Zero per customer</p>
            <div class="chart-container" style="height:300px">
                <canvas id="chartCustomer"></canvas>
            </div>
            <div class="legend">
                <div class="legend-item"><div class="legend-dot" style="background:#22c55e"></div> Over Stock</div>
                <div class="legend-item"><div class="legend-dot" style="background:#f59e0b"></div> Limited</div>
                <div class="legend-item"><div class="legend-dot" style="background:#ef4444"></div> Zero / Kosong</div>
            </div>
        </div>

        {{-- Chart 2: Donut Remarks --}}
        <div class="chart-card">
            <h3>Distribusi Status (Remarks)</h3>
            <p>Perbandingan status item berdasarkan remarks</p>
            <div class="chart-container" style="height:280px">
                <canvas id="chartRemarks"></canvas>
            </div>
        </div>

        {{-- Chart 3: Bar Proses --}}
        <div class="chart-card">
            <h3>Distribusi Proses</h3>
            <p>Jumlah item per jenis proses produksi</p>
            <div class="chart-container" style="height:280px">
                <canvas id="chartProses"></canvas>
            </div>
        </div>

        {{-- Chart 4: Bar Avg Strength per Customer (full width) --}}
        <div class="chart-card chart-full">
            <h3>Rata-rata Strength (Day) per Customer</h3>
            <p>Berapa hari rata-rata stok finish part bertahan per customer</p>
            <div class="chart-container" style="height:260px">
                <canvas id="chartStrength"></canvas>
            </div>
        </div>

    </div>

    @endif

@if($hasData)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Data dari Laravel
    const customerLabels = @json($perCustomer->pluck('customer'));
    const overData       = @json($perCustomer->pluck('over_stock')->map(fn($v) => (int)$v));
    const limitedData    = @json($perCustomer->pluck('limited')->map(fn($v) => (int)$v));
    const zeroData       = @json($perCustomer->pluck('zero_stock')->map(fn($v) => (int)$v));

    const remarksLabels  = @json($remarksData->pluck('remarks'));
    const remarksCounts  = @json($remarksData->pluck('total')->map(fn($v) => (int)$v));

    const prosesLabels   = @json($prosesData->pluck('proses'));
    const prosesCounts   = @json($prosesData->pluck('total')->map(fn($v) => (int)$v));

    const strengthLabels = @json($strengthAvg->pluck('customer'));
    const strengthValues = @json($strengthAvg->pluck('avg_strength')->map(fn($v) => round((float)$v, 2)));

    // Warna remarks
    const remarksColors = [
        '#f59e0b','#22c55e','#3b82f6','#ef4444',
        '#9333ea','#64748b','#059669','#92400e'
    ];

    // Chart 1: Stacked Bar per Customer
    new Chart(document.getElementById('chartCustomer'), {
        type: 'bar',
        data: {
            labels: customerLabels,
            datasets: [
                { label: 'Over Stock', data: overData,    backgroundColor: '#22c55e' },
                { label: 'Limited',    data: limitedData, backgroundColor: '#f59e0b' },
                { label: 'Zero/Kosong',data: zeroData,    backgroundColor: '#ef4444' },
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { stacked: true, grid: { display: false } },
                y: { stacked: true, grid: { color: '#f0f0f0' } }
            }
        }
    });

    // Chart 2: Donut Remarks
    new Chart(document.getElementById('chartRemarks'), {
        type: 'doughnut',
        data: {
            labels: remarksLabels,
            datasets: [{
                data: remarksCounts,
                backgroundColor: remarksColors,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { font: { size: 11 }, padding: 12 }
                }
            },
            cutout: '60%'
        }
    });

    // Chart 3: Bar Proses
    new Chart(document.getElementById('chartProses'), {
        type: 'bar',
        data: {
            labels: prosesLabels,
            datasets: [{
                label: 'Jumlah Item',
                data: prosesCounts,
                backgroundColor: ['#C0001C', '#3b82f6', '#8b5cf6'],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#f0f0f0' } }
            }
        }
    });

    // Chart 4: Bar Avg Strength
    new Chart(document.getElementById('chartStrength'), {
        type: 'bar',
        data: {
            labels: strengthLabels,
            datasets: [{
                label: 'Avg Strength (Day)',
                data: strengthValues,
                backgroundColor: strengthValues.map(v =>
                    v <= 0 ? '#ef4444' : v < 2 ? '#f59e0b' : '#22c55e'
                ),
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => `Avg: ${ctx.raw} hari`
                    }
                }
            },
            scales: {
                x: { grid: { display: false } },
                y: {
                    grid: { color: '#f0f0f0' },
                    ticks: { callback: v => v + ' hari' }
                }
            }
        }
    });
});
</script>
@endpush
@endif

@endsection
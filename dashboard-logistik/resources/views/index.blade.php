@extends('layouts.app')

@push('styles')
<style>        /* ===== HERO SECTION ===== */
        .hero {
            background: var(--red-main);
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }

        .hero-title-block h2 {
            font-size: 26px;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
        }

        .hero-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 5px;
        }

        .hero-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
            color: rgba(255,255,255,0.95);
            font-size: 13px;
            font-weight: 700;
        }

        .hero-meta .material-icons { font-size: 14px; }

        /* Stat cards */
        .stat-cards {
            display: flex;
            gap: 12px;
        }

        .stat-card {
            border-radius: 10px;
            padding: 14px 20px;
            text-align: center;
            min-width: 110px;
            position: relative;
            overflow: hidden;
        }

        /* Over stock = blue */
        .stat-card:nth-child(1) { background: #2563eb; }
        /* Limited = amber/orange */
        .stat-card.limited { background: #d97706; }
        /* Zero = red */
        .stat-card.zero { background: #dc2626; }

        .stat-card-label {
            font-size: 9px;
            font-weight: 700;
            color: rgba(255,255,255,0.75);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            display: block;
            margin-bottom: 4px;
        }

        .stat-card-value {
            font-size: 36px;
            font-weight: 900;
            color: white;
            line-height: 1;
        }

        .action-section {
            background: #111827;
            padding: 0 32px;
            display: flex;
            gap: 0;
            flex-wrap: wrap;
            border-bottom: 1px solid rgba(255,255,255,.06);
        }

        .action-btn {
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 7px;
            color: rgba(255,255,255,.5);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
        }

        .action-btn:hover { color: white; border-bottom-color: rgba(255,255,255,.3); }
        .action-btn.active { color: white; border-bottom-color: var(--red-main); }
        .action-btn .material-icons { font-size: 16px; }

        /* ===== DASHBOARD BODY ===== */
        .dashboard-body {
            padding: 20px 28px;
            display: grid;
            grid-template-columns: 220px 1fr 280px;
            gap: 18px;
        }

        @media (max-width: 1024px) {
            .dashboard-body { grid-template-columns: 1fr 1fr; }
            .dashboard-body > .card:last-child { grid-column: span 2; }
        }

        @media (max-width: 768px) {
            .hero { flex-direction: column; align-items: flex-start; padding: 20px; }
            .hero-title-block { width: 100%; margin-bottom: 15px; }
            .stat-cards { width: 100%; overflow-x: auto; padding-bottom: 5px; }
            .stat-card { flex: 1; min-width: 100px; padding: 10px; }
            .stat-card-value { font-size: 24px; }
            
            .dashboard-body { grid-template-columns: 1fr; padding: 15px; }
            .dashboard-body > .card { grid-column: span 1 !important; }
            
            .charts-grid-dashboard { grid-template-columns: 1fr; }
            .chart-card { padding: 15px; }
            .action-section { padding: 0 15px; overflow-x: auto; flex-wrap: nowrap; }
            .action-btn { padding: 10px 12px; }
        }

        /* ===== CARD BASE ===== */
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #eee;
            overflow: hidden;
        }

        .card-header {
            padding: 14px 16px 10px;
            border-bottom: 1px solid #f5f5f5;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-icon {
            width: 28px; height: 28px;
            background: #fff0f2;
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
        }

        .card-icon .material-icons { font-size: 16px; color: var(--red-main); }

        .card-header h3 {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #333;
        }

        .card-body { padding: 14px 16px; }

        /* ===== INVENTORY LEVEL ===== */
        .inv-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f8f8f8;
        }

        .inv-item:last-child { border-bottom: none; }

        .inv-name {
            font-size: 12px;
            font-weight: 600;
            color: #444;
        }

        .inv-badge {
            font-size: 12px;
            font-weight: 800;
            color: var(--red-main);
            background: #fff0f2;
            padding: 3px 10px;
            border-radius: 20px;
            min-width: 48px;
            text-align: center;
        }

        .inv-badge.ok { color: #16a34a; background: #f0fdf4; }
        .inv-badge.warn { color: #d97706; background: #fffbeb; }

        /* ===== INHOUSE PROSES ===== */
        .proses-item { padding: 10px 0; border-bottom: 1px solid #f8f8f8; }
        .proses-item:last-child { border-bottom: none; }

        .proses-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 6px;
        }

        .proses-name { font-size: 11px; font-weight: 700; color: #444; }
        .proses-value { font-size: 14px; font-weight: 900; color: #1a1a1a; }

        .progress-track {
            height: 6px;
            background: #f0f0f0;
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 99px;
            background: var(--red-main);
            transition: width 1s ease;
        }

        .progress-fill.danger { background: #ef4444; }
        .progress-fill.warn { background: #f59e0b; }
        .progress-fill.ok { background: #22c55e; }

        /* ===== SUBCONT PROSES ===== */
        .subcont-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .subcont-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 12px;
            background: #fafafa;
            border-radius: 8px;
            border: 1px solid #f0f0f0;
        }

        .subcont-name { font-size: 12px; font-weight: 700; color: #444; }

        .subcont-val {
            font-size: 13px;
            font-weight: 800;
            color: var(--red);
        }

        .subcont-val.ok { color: #16a34a; }
        .subcont-val.warn { color: #d97706; }

        /* ===== UPLOAD BUTTON ===== */
        .upload-area {
            padding: 14px 16px;
            border-top: 1px solid #f5f5f5;
        }

        .btn-upload {
            width: 100%;
            background: var(--red-main);
            color: white;
            border: none;
            border-radius: 9px;
            padding: 11px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            transition: background .15s;
        }

        .btn-upload:hover { background: var(--red-dark); }
        .btn-upload .material-icons { font-size: 17px; }

        /* ===== ALERT ===== */
        .alert {
            margin: 16px 28px 0;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert.success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
        .alert.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert .material-icons { font-size: 17px; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #ddd; border-radius: 3px; }

        /* ===== CHART DASHBOARD STYLES ===== */
        .chart-section-header { grid-column: 1 / -1; margin-top: 30px; padding-top: 25px; border-top: 2px dashed #eee; display: flex; align-items: center; gap: 12px; }
        .chart-section-header h2 { font-size: 20px; font-weight: 900; color: var(--navy-dark); margin: 0; }
        .chart-date-pill { background: #f1f5f9; color: #64748b; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; }
        
        .chart-sum-grid { grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 25px; }
        .sum-card { background:white; border-radius:12px; padding:20px; border:1px solid #eee; display:flex; align-items:center; gap:16px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .sum-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; }
        .sum-icon .material-icons { font-size:24px; color:white; }
        .sum-icon.blue   { background:#3b82f6; }
        .sum-icon.green  { background:#22c55e; }
        .sum-icon.yellow { background:#f59e0b; }
        .sum-icon.red    { background:#ef4444; }
        .sum-val-lg { font-size:28px; font-weight:900; line-height:1; }
        .sum-lbl { font-size:11px; color:#888; font-weight:500; margin-top:3px; }

        .charts-grid-dashboard { grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .chart-card { background:white; border-radius:12px; border:1px solid #eee; padding:20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .chart-card.full { grid-column: 1 / -1; }
        .chart-card h3 { font-size:13px; font-weight:800; color:#1a1a1a; margin-bottom:4px; }
        .chart-card p { font-size:11px; color:#aaa; margin-bottom:16px; }
        .chart-container { position:relative; }
        .legend { display:flex; gap:16px; flex-wrap:wrap; margin-top:12px; }
        .legend-item { display:flex; align-items:center; gap:6px; font-size:11px; color:#555; font-weight:500; }
        .legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }

</style>
@endpush

@section('content')
    {{-- Alert flash messages --}}
    @if(session('success'))
    <div class="alert success">
        <span class="material-icons">check_circle</span>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="alert error">
        <span class="material-icons">error</span>
        {{ session('error') }}
    </div>
    @endif

    {{-- Hero --}}
    <div class="hero">
        <div class="hero-title-block">
            <h2>Selamat Datang</h2>
            <div class="hero-meta">
                <span><span class="material-icons">calendar_today</span> <span id="real-date">--</span></span>
                <span><span class="material-icons">schedule</span> <span id="real-time">--:--:--</span> WIB</span>
            </div>
        </div>

        <div class="stat-cards">
            <div class="stat-card">
                <span class="stat-card-label">Over Stock</span>
                <span class="stat-card-value">{{ $summary['over'] }}</span>
            </div>
            <div class="stat-card limited">
                <span class="stat-card-label">Limited</span>
                <span class="stat-card-value">{{ $summary['limited'] }}</span>
            </div>
            <div class="stat-card zero">
                <span class="stat-card-label">Zero Stock</span>
                <span class="stat-card-value">{{ $summary['zero'] }}</span>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="action-section">
        @php
        $actions = [
            ['icon' => 'bar_chart',              'label' => 'Rundown Stock', 'route' => 'rundown.index'],
            ['icon' => 'view_list',              'label' => 'Single Part',   'route' => 'single_part.index'],
            ['icon' => 'swap_horiz',             'label' => 'Mutasi Pallet', 'route' => null],
            ['icon' => 'factory',                'label' => 'SMR Vendor',   'route' => null],
            ['icon' => 'precision_manufacturing','label' => 'SMR',           'route' => null],
            ['icon' => 'receipt_long',           'label' => 'Data GR',      'route' => null],
            ['icon' => 'delete_sweep',           'label' => 'Data Scrapt',  'route' => null],
        ];
        @endphp
        @foreach($actions as $act)
            @if($act['route'])
            <a href="{{ route($act['route']) }}" class="action-btn">
                <span class="material-icons">{{ $act['icon'] }}</span>
                {{ $act['label'] }}
            </a>
            @else
            <span class="action-btn" style="opacity:0.45;cursor:not-allowed;" title="Coming soon">
                <span class="material-icons">{{ $act['icon'] }}</span>
                {{ $act['label'] }}
            </span>
            @endif
        @endforeach
    </div>

    {{-- Dashboard Body --}}
    <div class="dashboard-body">

        {{-- COL 1: Inventory Level --}}
        <div class="card">
            <div class="card-header">
                <div class="card-icon"><span class="material-icons">warehouse</span></div>
                <h3>Inventory Level</h3>
            </div>
            <div class="card-body">
                @php
                $displayInv = $hasData ? $inventoryLevel : [
                    'ADM'=>0.2, 'TMMIN'=>0.2, 'IAMI'=>4.2, 'HPM'=>2.1, 'FTI'=>2.0, 'GKD'=>9.7
                ];
                @endphp
                @foreach($displayInv as $label => $avg)
                @php $badgeClass = $avg <= 0 ? '' : ($avg < 2 ? 'warn' : 'ok'); @endphp
                <div class="inv-item">
                    <span class="inv-name">{{ $label }}</span>
                    <span class="inv-badge {{ $badgeClass }}">{{ number_format($avg, 1) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- COL 2: Inhouse Proses --}}
        <div class="card">
            <div class="card-header">
                <div class="card-icon"><span class="material-icons">settings_suggest</span></div>
                <h3>Inventory Level Inhouse Proses</h3>
            </div>
            <div class="card-body">
                @php
                $displayInhouse = $hasData ? $inhouseProses : [
                    'SUB'     => 1.4,
                    'PRESS A' => 2.1,
                    'PRESS B' => 1.6,
                    'PRESS C' => 1.5,
                    'PRESS D' => 1.7,
                ];
                $maxInhouse = max(array_values($displayInhouse)) ?: 1;
                @endphp
                @foreach($displayInhouse as $label => $avg)
                @php
                $pct       = min(100, round(($avg / $maxInhouse) * 100));
                $fillClass = $avg <= 0 ? 'danger' : ($avg < 1.5 ? 'warn' : ($avg >= 3 ? 'ok' : ''));
                @endphp
                <div class="proses-item">
                    <div class="proses-header">
                        <span class="proses-name">{{ $label }}</span>
                        <span class="proses-value">{{ number_format($avg, 1) }}</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill {{ $fillClass }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- COL 3: Subcont Proses + Upload --}}
        <div class="card" style="display:flex; flex-direction:column;">
            <div class="card-header">
                <div class="card-icon"><span class="material-icons">account_tree</span></div>
                <h3>Inventory Level Subcont Proses</h3>
            </div>
            <div class="card-body" style="flex:1;">
                @php
                $displaySubcont = $hasData ? $subcontProses : [
                    'AA'=>3.7, 'AL'=>2.7, 'CM'=>4.9, 'FTI'=>4.8,
                    'IKA'=>1.9, 'ISR'=>1.5, 'MPI'=>8.1, 'WK'=>8.0,
                ];
                @endphp
                <div class="subcont-grid">
                    @foreach($displaySubcont as $label => $avg)
                    @php $valClass = $avg <= 0 ? '' : ($avg < 2 ? '' : ($avg >= 5 ? 'ok' : 'warn')); @endphp
                    <div class="subcont-item">
                        <span class="subcont-name">{{ $label }}</span>
                        <span class="subcont-val {{ $valClass }}">{{ number_format($avg, 1) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Upload Data Button --}}
            <div class="upload-area">
                <form action="{{ route('stock.upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="excel_file" id="excel_input" class="d-none" style="display:none"
                        accept=".xlsx,.xls,.xlsm" onchange="this.form.submit()">
                    <label for="excel_input" style="cursor:pointer; display:block;">
                        <div class="btn-upload" onclick="document.getElementById('excel_input').click(); event.preventDefault();">
                            <span class="material-icons">upload_file</span>
                            Upload Data
                        </div>
                    </label>
                </form>
            </div>
        </div>

    </div>{{-- end dashboard-body --}}

    {{-- CHART ANALYTICS SECTION --}}
    <div class="dashboard-body" style="padding-top:0; margin-top:-20px;">
        <div class="chart-section-header">
            <span class="material-icons" style="color: var(--red-main); font-size: 28px;">analytics</span>
            <h2>Finish Part Analytics</h2>
            <span class="chart-date-pill">{{ $latestSheet ?: 'No Data' }}</span>
        </div>

        @if(!$hasChartData)
            <div style="grid-column: 1 / -1; background: #f9fafb; border: 1px dashed #ddd; border-radius: 12px; padding: 40px; text-align: center; color: #94a3b8; margin-top:20px;">
                <span class="material-icons" style="font-size: 48px; margin-bottom: 10px; opacity: 0.5;">bar_chart_off</span>
                <p style="font-size: 14px; font-weight: 600;">Tidak ada data Finish Part untuk dianalisis.</p>
                <p style="font-size: 12px; margin-top: 4px;">Silakan upload data Rundown Incoming kategori Finish Part terlebih dahulu.</p>
            </div>
        @else
            <div class="chart-sum-grid">
                <div class="sum-card">
                    <div class="sum-icon blue"><span class="material-icons">inventory_2</span></div>
                    <div>
                        <div class="sum-val-lg">{{ $totalAllFinish }}</div>
                        <div class="sum-lbl">Total Item</div>
                    </div>
                </div>
                <div class="sum-card">
                    <div class="sum-icon green"><span class="material-icons">trending_up</span></div>
                    <div>
                        <div class="sum-val-lg" style="color:#16a34a">{{ $totalOverFP }}</div>
                        <div class="sum-lbl">Over Stock</div>
                    </div>
                </div>
                <div class="sum-card">
                    <div class="sum-icon yellow"><span class="material-icons">warning</span></div>
                    <div>
                        <div class="sum-val-lg" style="color:#d97706">{{ $totalLimitedFP }}</div>
                        <div class="sum-lbl">Limited Stock</div>
                    </div>
                </div>
                <div class="sum-card">
                    <div class="sum-icon red"><span class="material-icons">remove_circle</span></div>
                    <div>
                        <div class="sum-val-lg" style="color:#dc2626">{{ $totalZeroFP }}</div>
                        <div class="sum-lbl">Critical / Zero</div>
                    </div>
                </div>
            </div>

            <div class="charts-grid-dashboard">
                <div class="chart-card full">
                    <h3>Stock Level per Customer (Finish Part)</h3>
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

                <div class="chart-card">
                    <h3>Distribusi Status</h3>
                    <p>Perbandingan status item finish part</p>
                    <div class="chart-container" style="height:280px">
                        <canvas id="chartRemarks"></canvas>
                    </div>
                </div>

                <div class="chart-card">
                    <h3>Top 10 Vendor Distribution</h3>
                    <p>Jumlah item finish part per vendor</p>
                    <div class="chart-container" style="height:280px">
                        <canvas id="chartProses"></canvas>
                    </div>
                </div>

                <div class="chart-card full">
                    <h3>Rata-rata Strength (Day) per Customer</h3>
                    <p>Berapa hari rata-rata stok finish part bertahan per customer</p>
                    <div class="chart-container" style="height:260px">
                        <canvas id="chartStrength"></canvas>
                    </div>
                </div>
            </div>
        @endif
    </div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
@if($hasChartData)
document.addEventListener('DOMContentLoaded', function() {
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

    const remarksColors = ['#f59e0b','#22c55e','#3b82f6','#ef4444','#9333ea','#64748b','#059669','#92400e'];

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
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, grid: { color: '#f0f0f0' } } } }
    });

    new Chart(document.getElementById('chartRemarks'), {
        type: 'doughnut',
        data: { labels: remarksLabels, datasets: [{ data: remarksCounts, backgroundColor: remarksColors, borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { font: { size: 11 }, padding: 12 } } }, cutout: '60%' }
    });

    new Chart(document.getElementById('chartProses'), {
        type: 'bar',
        data: { labels: prosesLabels, datasets: [{ label: 'Jumlah Item', data: prosesCounts, backgroundColor: ['#C0001C', '#3b82f6', '#8b5cf6'], borderRadius: 8 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#f0f0f0' } } } }
    });

    new Chart(document.getElementById('chartStrength'), {
        type: 'bar',
        data: { labels: strengthLabels, datasets: [{ label: 'Avg Strength (Day)', data: strengthValues, backgroundColor: strengthValues.map(v => v <= 0 ? '#ef4444' : v < 2 ? '#f59e0b' : '#22c55e'), borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => `Avg: ${ctx.raw} hari` } } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#f0f0f0' }, ticks: { callback: v => v + ' hari' } } } }
    });
});
@endif

function updateDashboardClock(){
    var now=new Date();
    var optsDate={timeZone:'Asia/Jakarta',day:'numeric',month:'long',year:'numeric'};
    var optsTime={timeZone:'Asia/Jakarta',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false};
    var d=new Intl.DateTimeFormat('id-ID',optsDate).format(now);
    var t=new Intl.DateTimeFormat('id-ID',optsTime).format(now);
    var el1=document.getElementById('real-date');
    var el2=document.getElementById('real-time');
    if(el1) el1.textContent=d;
    if(el2) el2.textContent=t;
}
setInterval(updateDashboardClock,1000);updateDashboardClock();
</script>
@endpush
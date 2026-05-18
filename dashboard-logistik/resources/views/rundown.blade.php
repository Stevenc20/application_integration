@extends('layouts.app')

@push('styles')
<style>
.page-hero{background:var(--red-main);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
.page-hero-left{display:flex;align-items:center;gap:12px;}
.page-hero-left .material-icons{font-size:26px;color:rgba(255,255,255,.7);}
.page-hero h2{font-size:20px;font-weight:900;color:white;}
.page-hero p{font-size:11px;color:rgba(255,255,255,.65);margin-top:2px;}
.stat-pills{display:flex;gap:10px;flex-wrap:wrap;}
.stat-pill{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.2);border-radius:8px;padding:8px 16px;text-align:center;min-width:90px;}
.stat-pill .pill-val{font-size:20px;font-weight:900;color:white;line-height:1;display:block;}
.stat-pill .pill-lbl{font-size:9px;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.6px;margin-top:2px;display:block;}
.stat-pill.limited{border-top:2px solid #FFD700;}
.stat-pill.zero{border-top:2px solid #ff6b6b;}
.toolbar{background:white;border-bottom:1px solid #e2e2e7;padding:12px 24px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.toolbar-search{display:flex;align-items:center;gap:8px;background:#f4f3f8;border:1px solid #c3c6d1;border-radius:8px;padding:7px 12px;flex:1;min-width:160px;max-width:240px;}
.toolbar-search .material-icons{font-size:16px;color:#737780;}
.toolbar-search input{border:none;background:transparent;outline:none;font-size:12px;font-family:'Inter',sans-serif;width:100%;}
.filter-select{border:1px solid #c3c6d1;background:#f4f3f8;border-radius:8px;padding:7px 8px;font-size:11px;font-family:'Inter',sans-serif;color:#43474f;outline:none;}
.filter-select:focus{border-color:var(--red-main);}
.btn-filter{background:var(--red-main);color:white;border:none;border-radius:8px;padding:8px 14px;font-size:11px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:5px;}
.btn-reset{background:#f0f0f0;color:#555;border:none;border-radius:8px;padding:8px 10px;font-size:11px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-flex;}
.result-count{margin-left:auto;font-size:12px;color:#737780;white-space:nowrap;}
.result-count strong{color:var(--red-main);font-weight:800;}
.vendor-bar{background:white;border-bottom:1px solid #e2e2e7;padding:8px 24px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.vendor-label{font-size:10px;font-weight:700;color:#737780;text-transform:uppercase;letter-spacing:.8px;white-space:nowrap;}
.cust-pill{display:inline-flex;padding:3px 11px;border-radius:20px;font-size:10px;font-weight:700;cursor:pointer;border:1px solid #c3c6d1;background:white;color:#43474f;text-decoration:none;white-space:nowrap;transition:all .15s;}
.cust-pill:hover{border-color:var(--red-main);color:var(--red-main);}
.cust-pill.active{background:var(--red-main);color:white;border-color:var(--red-main);}
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;font-size:11px;min-width:1400px;}
thead tr{background:#1a1a1a;}
thead th{padding:9px 10px;text-align:left;font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#ccc;white-space:nowrap;}
thead th a{color:inherit;text-decoration:none;display:flex;align-items:center;gap:3px;}
thead th a:hover{color:white;}
thead th .material-icons{font-size:13px;}
tbody tr{border-bottom:1px solid #f0f0f0;}
tbody tr:hover{background:#fafafa;}
tbody td{padding:7px 10px;color:#333;white-space:nowrap;}
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:9px;font-weight:800;text-transform:uppercase;}
.badge-ok{background:#f0fdf4;color:#16a34a;}.badge-minim{background:#fffbeb;color:#d97706;}.badge-kosong{background:#fef2f2;color:#dc2626;}.badge-over{background:#eff6ff;color:#2563eb;}.badge-runout{background:#fdf4ff;color:#9333ea;}.badge-common{background:#f8fafc;color:#64748b;}.badge-noorder{background:#fef3c7;color:#92400e;}.badge-new{background:#ecfdf5;color:#065f46;}.badge-default{background:#f1f5f9;color:#475569;}
.proses-badge{display:inline-block;padding:2px 7px;border-radius:4px;font-size:9px;font-weight:700;text-transform:uppercase;}
.proses-stamping{background:#fef9c3;color:#854d0e;}.proses-subcont{background:#ede9fe;color:#7c3aed;}.proses-subassy{background:#e0f2fe;color:#0369a1;}
.movement-badge{display:inline-block;padding:2px 7px;border-radius:4px;font-size:9px;font-weight:700;}
.movement-fast{background:#dcfce7;color:#15803d;}.movement-medium{background:#fef9c3;color:#854d0e;}.movement-slow{background:#fee2e2;color:#dc2626;}
.strength-wrap{display:flex;align-items:center;gap:6px;}
.strength-val{font-weight:800;font-size:12px;min-width:32px;text-align:right;}
.strength-danger{color:#dc2626;}.strength-warn{color:#d97706;}.strength-ok{color:#16a34a;}.strength-over{color:#2563eb;}
.mini-bar{flex:1;height:4px;background:#f0f0f0;border-radius:99px;overflow:hidden;min-width:40px;}
.mini-fill{height:100%;border-radius:99px;}
.fill-danger{background:#ef4444;}.fill-warn{background:#f59e0b;}.fill-ok{background:#22c55e;}.fill-over{background:#3b82f6;}
.no-data{text-align:center;padding:60px 20px;color:#bbb;}
.no-data .material-icons{font-size:48px;display:block;margin-bottom:12px;}
.empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 20px;text-align:center;}
.empty-state .material-icons{font-size:64px;color:#ddd;margin-bottom:16px;}
.empty-state h3{font-size:18px;font-weight:700;color:#aaa;margin-bottom:8px;}
.empty-state p{font-size:13px;color:#ccc;max-width:320px;line-height:1.6;}
.btn-go{margin-top:20px;background:var(--red-main);color:white;border:none;border-radius:10px;padding:12px 24px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:8px;text-decoration:none;}
.alert{margin:14px 24px 0;padding:10px 16px;border-radius:8px;font-size:12px;font-weight:600;display:flex;align-items:center;gap:8px;}
.alert.success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.alert.error{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;}
.alert .material-icons{font-size:17px;}
.pagination-wrap{padding:16px 24px 28px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.pagination-info{font-size:12px;color:#737780;}
.pagination-info strong{color:#333;}
.pagination{display:flex;gap:4px;align-items:center;}
.page-btn{width:32px;height:32px;border-radius:6px;border:1px solid #e5e5e5;background:white;font-size:12px;font-weight:600;color:#555;display:flex;align-items:center;justify-content:center;text-decoration:none;}
.page-btn:hover{border-color:var(--red-main);color:var(--red-main);}
.page-btn.active{background:var(--red-main);color:white;border-color:var(--red-main);}
.page-btn.disabled{opacity:.35;pointer-events:none;}
.page-btn .material-icons{font-size:15px;}
</style>
@endpush

@section('content')

    @if(session('success'))
    <div class="alert success">
        <span class="material-icons">check_circle</span> {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert error">
        <span class="material-icons">error</span> {{ session('error') }}
    </div>
    @endif

    <div class="page-hero">
        <div class="page-hero-left">
            <span class="material-icons">bar_chart</span>
            <div>
                <h2>Rundown Stock</h2>
                <p>Detail inventory finish part — Sheet RUNDOWN STOCK FP</p>
            </div>
        </div>
        <div class="stat-pills">
            <div class="stat-pill">
                <span class="pill-val">{{ $total }}</span>
                <span class="pill-lbl">Total Item</span>
            </div>
            <div class="stat-pill">
                <span class="pill-val">{{ $overStock }}</span>
                <span class="pill-lbl">Over Stock</span>
            </div>
            <div class="stat-pill limited">
                <span class="pill-val">{{ $limitedStock }}</span>
                <span class="pill-lbl">Limited</span>
            </div>
            <div class="stat-pill zero">
                <span class="pill-val">{{ $zeroStock }}</span>
                <span class="pill-lbl">Zero/Kosong</span>
            </div>
        </div>
    </div>

    @if(!$hasData)

    <div class="empty-state">
        <span class="material-icons">upload_file</span>
        <h3>Belum ada data</h3>
        <p>Upload file Excel XLSM dari halaman Dashboard untuk melihat data Rundown Stock.</p>
        <a href="{{ route('stock.index') }}" class="btn-go-dashboard">
            <span class="material-icons">arrow_back</span> Ke Dashboard
        </a>
    </div>

    @else

    <form method="GET" action="{{ route('rundown.index') }}" id="filterForm">
        {{-- Customer Quick Filter Buttons --}}
        <div style="background:white; border-bottom:1px solid #eee; padding:10px 28px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <span style="font-size:10px; font-weight:700; color:#999; text-transform:uppercase; letter-spacing:0.8px; white-space:nowrap;">Customer:</span>
            <a href="{{ route('rundown.index', array_merge(request()->except(['customer','page']), ['customer'=>''])) }}"
               style="text-decoration:none;">
                <span style="display:inline-flex; align-items:center; padding:4px 12px; border-radius:20px; font-size:10px; font-weight:700; cursor:pointer; border:1px solid {{ $filterCustomer==='' ? '#C0001C' : '#e5e5e5' }}; background:{{ $filterCustomer==='' ? '#C0001C' : 'white' }}; color:{{ $filterCustomer==='' ? 'white' : '#555' }};">
                    Semua
                </span>
            </a>
            @foreach($allCustomer as $c)
            <a href="{{ route('rundown.index', array_merge(request()->except(['customer','page']), ['customer'=>$c])) }}"
               style="text-decoration:none;">
                <span style="display:inline-flex; align-items:center; padding:4px 12px; border-radius:20px; font-size:10px; font-weight:700; cursor:pointer; border:1px solid {{ $filterCustomer===$c ? '#C0001C' : '#e5e5e5' }}; background:{{ $filterCustomer===$c ? '#C0001C' : 'white' }}; color:{{ $filterCustomer===$c ? 'white' : '#555' }}; transition:all .15s;">
                    {{ $c }}
                </span>
            </a>
            @endforeach
        </div>

        {{-- Filter Toolbar --}}
        <div class="toolbar">
            <div class="toolbar-search">
                <span class="material-icons">search</span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Job No, Part Number..." onchange="document.getElementById('filterForm').submit()">
            </div>
            <select name="customer" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Semua Customer</option>
                @foreach($allCustomer as $c)
                <option value="{{ $c }}" {{ $filterCustomer === $c ? 'selected' : '' }}>{{ $c }}</option>
                @endforeach
            </select>
            <select name="proses" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Semua Proses</option>
                @foreach($allProses as $p)
                <option value="{{ $p }}" {{ $filterProses === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
            </select>
            <select name="type_of_part" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Semua Type Part</option>
                @foreach($allTypeOfPart as $t)
                <option value="{{ $t }}" {{ $filterType === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
            <select name="stock_movement" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Semua Movement</option>
                @foreach($allMovement as $m)
                <option value="{{ $m }}" {{ $filterMovement === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
            <select name="remarks" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Semua Status</option>
                @foreach($allRemarks as $r)
                <option value="{{ $r }}" {{ $filterRemarks === $r ? 'selected' : '' }}>{{ $r }}</option>
                @endforeach
            </select>
            <input type="hidden" name="sort" value="{{ $sortBy }}">
            <input type="hidden" name="dir" value="{{ $sortDir }}">
            <button type="submit" class="btn-filter">
                <span class="material-icons">filter_list</span> Filter
            </button>
            <a href="{{ route('rundown.index') }}" class="btn-reset">Reset</a>
            <span class="result-count">Menampilkan <strong>{{ $total }}</strong> item</span>
        </div>
    </form>

    @if(isset($items) && $items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->lastPage() > 1)
    <div class="pagination-wrap" style="padding:10px 24px; border-bottom:1px solid #f0f0f0;">
        <div class="pagination-info">Menampilkan <strong>{{ $items->firstItem() }}-{{ $items->lastItem() }}</strong> dari <strong>{{ $items->total() }}</strong></div>
        <div class="pagination">
            @if($items->onFirstPage())
            <span class="page-btn disabled"><span class="material-icons">chevron_left</span></span>
            @else
            <a class="page-btn" href="{{ $items->previousPageUrl() }}"><span class="material-icons">chevron_left</span></a>
            @endif

            @php
            $pStart = max(1, $items->currentPage() - 2);
            $pEnd   = min($items->lastPage(), $items->currentPage() + 2);
            @endphp

            @if($pStart > 1)
            <a class="page-btn" href="{{ $items->url(1) }}">1</a>
            @if($pStart > 2)
            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
            @endif
            @endif

            @for($p = $pStart; $p <= $pEnd; $p++)
            @if($p === $items->currentPage())
            <span class="page-btn active">{{ $p }}</span>
            @else
            <a class="page-btn" href="{{ $items->url($p) }}">{{ $p }}</a>
            @endif
            @endfor

            @if($pEnd < $items->lastPage())
            @if($pEnd < $items->lastPage() - 1)
            <span style="padding:0 4px;color:#bbb;font-size:12px">...</span>
            @endif
            <a class="page-btn" href="{{ $items->url($items->lastPage()) }}">{{ $items->lastPage() }}</a>
            @endif

            @if($items->hasMorePages())
            <a class="page-btn" href="{{ $items->nextPageUrl() }}"><span class="material-icons">chevron_right</span></a>
            @else
            <span class="page-btn disabled"><span class="material-icons">chevron_right</span></span>
            @endif
        </div>
    </div>
    @endif

    <div class="top-scrollbar" id="topScrollbarRundown">
        <div class="top-scrollbar-dummy" id="topScrollbarDummyRundown"></div>
    </div>

    <div class="table-wrap" id="tableWrapRundown">
        @if($items->isEmpty())
        <div class="no-data">
            <span class="material-icons">search_off</span>
            <p>Tidak ada data yang cocok</p>
        </div>
        @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>
                        @php $dir_job = ($sortBy==='job_no' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['sort'=>'job_no','dir'=>$dir_job]) }}">
                            Job No
                            @if($sortBy==='job_no')
                            <span class="material-icons">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>Part Number</th>
                    <th>
                        @php $dir_cust = ($sortBy==='customer' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['sort'=>'customer','dir'=>$dir_cust]) }}">
                            Customer
                            @if($sortBy==='customer')
                            <span class="material-icons">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>Proses</th>
                    <th>Source</th>
                    <th>Type Part</th>
                    <th>
                        @php $dir_mov = ($sortBy==='stock_movement' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['sort'=>'stock_movement','dir'=>$dir_mov]) }}">
                            Movement
                            @if($sortBy==='stock_movement')
                            <span class="material-icons">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </a>
                    </th>
                    <th style="text-align:right">Pcs/Day</th>
                    <th style="text-align:right">
                        @php $dir_fg = ($sortBy==='stock_fg' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['sort'=>'stock_fg','dir'=>$dir_fg]) }}" style="justify-content:flex-end">
                            Stock FG
                            @if($sortBy==='stock_fg')
                            <span class="material-icons">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>
                        @php $dir_str = ($sortBy==='strength' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['sort'=>'strength','dir'=>$dir_str]) }}">
                            Strength(Day)
                            @if($sortBy==='strength')
                            <span class="material-icons">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </a>
                    </th>
                    <th style="text-align:right">Stock SAP</th>
                    <th style="text-align:right">Diff</th>
                    <th style="text-align:right">Accuracy</th>
                    <th>Status</th>
                    <th style="text-align:right">Min Stock</th>
                    <th style="text-align:right">Max Stock</th>
                    <th style="text-align:right">Shortage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $i => $item)
                @php
                $strength = (float) $item->strength;
                $rowNum   = ($items->currentPage() - 1) * $perPage + $i + 1;

                // Strength color & bar
                if ($strength <= 0) {
                    $sc = 'danger'; $bp = 0;
                } elseif ($strength < 1) {
                    $sc = 'danger'; $bp = max(5, min(100, $strength * 20));
                } elseif ($strength < 2) {
                    $sc = 'warn'; $bp = min(100, $strength * 20);
                } elseif ($strength < 5) {
                    $sc = 'ok'; $bp = min(100, $strength * 12);
                } else {
                    $sc = 'over'; $bp = 100;
                }

                // Proses badge class
                $pl = strtolower($item->proses ?? '');
                if (str_contains($pl, 'stamp')) {
                    $prosesCls = 'proses-stamping';
                } elseif (str_contains($pl, 'subcont')) {
                    $prosesCls = 'proses-subcont';
                } else {
                    $prosesCls = 'proses-subassy';
                }

                // Movement badge class
                $mv = strtolower($item->stock_movement ?? '');
                if (str_contains($mv, 'fast')) {
                    $movCls = 'movement-fast';
                } elseif (str_contains($mv, 'medium')) {
                    $movCls = 'movement-medium';
                } else {
                    $movCls = 'movement-slow';
                }

                // Remarks badge class
                $rem = strtolower(trim($item->remarks ?? ''));
                if ($rem === 'ok') {
                    $remCls = 'badge-ok';
                } elseif ($rem === 'minim') {
                    $remCls = 'badge-minim';
                } elseif ($rem === 'kosong') {
                    $remCls = 'badge-kosong';
                } elseif ($rem === 'over') {
                    $remCls = 'badge-over';
                } elseif ($rem === 'run out') {
                    $remCls = 'badge-runout';
                } elseif ($rem === 'common part') {
                    $remCls = 'badge-common';
                } elseif ($rem === 'no order') {
                    $remCls = 'badge-noorder';
                } elseif ($rem === 'new model') {
                    $remCls = 'badge-new';
                } else {
                    $remCls = 'badge-default';
                }

                $acc      = (float) $item->accuracy * 100;
                $diffColor = $item->stock_diff < 0 ? '#dc2626' : '#16a34a';
                $shortColor = $item->stock_shortage < 0 ? '#dc2626' : '#333';
                @endphp
                <tr>
                    <td style="color:#bbb;font-size:10px">{{ $rowNum }}</td>
                    <td style="font-weight:700;color:#1a1a1a">{{ $item->job_no }}</td>
                    <td style="color:#666;max-width:150px;overflow:hidden;text-overflow:ellipsis" title="{{ $item->part_number }}">{{ $item->part_number }}</td>
                    <td style="font-weight:600;color:#444;font-size:10px">{{ $item->customer }}</td>
                    <td><span class="proses-badge {{ $prosesCls }}">{{ $item->proses }}</span></td>
                    <td style="color:#666;font-size:10px">{{ $item->source }}</td>
                    <td style="font-size:10px;color:#555">{{ $item->type_of_part }}</td>
                    <td><span class="movement-badge {{ $movCls }}">{{ $item->stock_movement }}</span></td>
                    <td style="text-align:right;font-size:10px;color:#888">{{ number_format($item->pcs_day, 0) }}</td>
                    <td style="text-align:right;font-weight:700">{{ number_format($item->stock_fg, 0) }}</td>
                    <td>
                        <div class="strength-wrap">
                            <div class="mini-bar">
                                <div class="mini-fill fill-{{ $sc }}" style="width:{{ $bp }}%"></div>
                            </div>
                            <span class="strength-val strength-{{ $sc }}">{{ number_format($strength, 1) }}</span>
                        </div>
                    </td>
                    <td style="text-align:right;font-size:10px">{{ number_format($item->stock_sap, 0) }}</td>
                    <td style="text-align:right;font-size:10px;color:{{ $diffColor }}">{{ number_format($item->stock_diff, 0) }}</td>
                    <td style="text-align:right;font-size:10px">{{ number_format($acc, 1) }}%</td>
                    <td><span class="badge {{ $remCls }}">{{ $item->remarks ?: '-' }}</span></td>
                    <td style="text-align:right;font-size:10px;color:#888">{{ number_format($item->min_stock, 0) }}</td>
                    <td style="text-align:right  ;font-size:10px;color:#888">{{ number_format($item->max_stock, 0) }}</td>
                    <td style="text-align:right;font-size:10px;color:{{ $shortColor }}">{{ number_format($item->stock_shortage, 0) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>



    @endif

@endsection

@push('scripts')
<script>
// Sync Top Scrollbar
const tableWrap = document.getElementById('tableWrapRundown');
const topScrollbar = document.getElementById('topScrollbarRundown');
const topScrollbarDummy = document.getElementById('topScrollbarDummyRundown');
if(tableWrap && topScrollbar && topScrollbarDummy) {
    const table = tableWrap.querySelector('table');
    if(table) {
        const updateDummyWidth = () => {
            topScrollbarDummy.style.width = table.offsetWidth + 'px';
        };
        updateDummyWidth();
        topScrollbar.addEventListener('scroll', function() {
            tableWrap.scrollLeft = topScrollbar.scrollLeft;
        });
        tableWrap.addEventListener('scroll', function() {
            topScrollbar.scrollLeft = tableWrap.scrollLeft;
        });
        window.addEventListener('resize', updateDummyWidth);
    } else {
        topScrollbar.style.display = 'none';
    }
}
</script>
@endpush
@extends('layouts.app')

@push('styles')
<style>
.page-hero{background:var(--red-main);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
.page-hero-left{display:flex;align-items:center;gap:12px;}
.page-hero-left .material-icons{font-size:26px;color:rgba(255,255,255,.7);}
.page-hero h2{font-size:20px;font-weight:900;color:white;}
.page-hero p{font-size:11px;color:rgba(255,255,255,.65);margin-top:2px;}
.stat-pills{display:flex;gap:10px;flex-wrap:wrap;}
.stat-pill{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.2);border-radius:8px;padding:8px 16px;text-align:center;min-width:95px;}
.stat-pill .pill-val{font-size:18px;font-weight:900;color:white;line-height:1;display:block;}
.stat-pill .pill-lbl{font-size:9px;font-weight:700;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.6px;margin-top:2px;display:block;}
.stat-pill.warn-stock{border-top:2px solid #f59e0b;}
.stat-pill.success-stock{border-top:2px solid #22c55e;}
.stat-pill.danger-stock{border-top:2px solid #ef4444;}

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

.table-wrap{overflow-x:auto;}
.top-scrollbar { width: 100%; overflow-x: auto; overflow-y: hidden; height: 18px; margin-bottom: 4px; }
.top-scrollbar-dummy { height: 1px; }
.top-scrollbar::-webkit-scrollbar { height: 16px; }
.top-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.top-scrollbar::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
.top-scrollbar::-webkit-scrollbar-thumb:hover { background: #333; }

.table-wrap::-webkit-scrollbar { height: 16px; }
.table-wrap::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.table-wrap::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
.table-wrap::-webkit-scrollbar-thumb:hover { background: #333; }

@media (max-width: 768px) {
    .top-scrollbar::-webkit-scrollbar { height: 20px; }
    .table-wrap::-webkit-scrollbar { height: 20px; }
    .top-scrollbar { height: 22px; }
}

table{width:100%;border-collapse:collapse;font-size:11px;min-width:1200px;}
thead tr{background:#1a1a1a;}
thead th{padding:9px 10px;text-align:left;font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#ccc;white-space:nowrap;}
thead th a{color:inherit;text-decoration:none;display:flex;align-items:center;gap:3px;}
thead th a:hover{color:white;}
thead th .material-icons{font-size:13px;}
tbody tr{border-bottom:1px solid #f0f0f0;}
tbody tr:hover{background:#fafafa;}
tbody td{padding:8px 10px;color:#333;white-space:nowrap;}

.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:9px;font-weight:800;text-transform:uppercase;}
.badge-close{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;}
.badge-unclose{background:#fff5f5;color:#e11d48;border:1px solid #fecdd3;}
.badge-smr{background:#f1f5f9;color:#334155;border:1px solid #e2e8f0;font-weight:700;}

.val-bold{font-weight:800;color:#111827;}
.problem-text{max-width:220px;white-space:normal;word-break:break-word;color:#4b5563;font-size:10.5px;}

.empty-state{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:80px 20px;text-align:center;}
.empty-state .material-icons{font-size:64px;color:#ddd;margin-bottom:16px;}
.empty-state h3{font-size:18px;font-weight:700;color:#aaa;margin-bottom:8px;}
.empty-state p{font-size:13px;color:#ccc;max-width:320px;line-height:1.6;}
.btn-go-dashboard{margin-top:20px;background:var(--red-main);color:white;border:none;border-radius:10px;padding:12px 24px;font-size:13px;font-weight:700;display:inline-flex;align-items:center;gap:8px;text-decoration:none;}

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

    <div class="page-hero">
        <div class="page-hero-left">
            <span class="material-icons">factory</span>
            <div>
                <h2>SMR Vendor</h2>
                <p>Data Supplier Quality SMR Vendor — Sheet SMR VENDOR</p>
            </div>
        </div>
        <div class="stat-pills">
            <div class="stat-pill">
                <span class="pill-val">{{ number_format($totalItems) }}</span>
                <span class="pill-lbl">Total Item</span>
            </div>
            <div class="stat-pill warn-stock">
                <span class="pill-val">{{ number_format($totalQty) }}</span>
                <span class="pill-lbl">Qty Claim NG</span>
            </div>
            <div class="stat-pill success-stock">
                <span class="pill-val">{{ number_format($totalRepl) }}</span>
                <span class="pill-lbl">Qty Replacement</span>
            </div>
            <div class="stat-pill danger-stock">
                <span class="pill-val">{{ number_format($uncloseCount) }}</span>
                <span class="pill-lbl">Open SMR</span>
            </div>
            <div class="stat-pill">
                <span class="pill-val">{{ number_format($closeRate, 1) }}%</span>
                <span class="pill-lbl">Close Rate</span>
            </div>
        </div>
    </div>

    @if(!$hasData)

    <div class="empty-state">
        <span class="material-icons">upload_file</span>
        <h3>Belum ada data SMR Vendor</h3>
        <p>Upload file Excel XLSM dari halaman Dashboard untuk memuat data SMR Vendor.</p>
        <a href="{{ route('stock.index') }}" class="btn-go-dashboard">
            <span class="material-icons">arrow_back</span> Ke Dashboard
        </a>
    </div>

    @else

    <form method="GET" action="{{ route('smr_vendor.index') }}" id="filterForm">
        {{-- Vendor Quick Filter Buttons --}}
        <div style="background:white; border-bottom:1px solid #eee; padding:10px 28px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <span style="font-size:10px; font-weight:700; color:#999; text-transform:uppercase; letter-spacing:0.8px; white-space:nowrap;">Vendor:</span>
            <a href="{{ route('smr_vendor.index', array_merge(request()->except(['vendor','page']), ['vendor'=>''])) }}"
               style="text-decoration:none;">
                <span style="display:inline-flex; align-items:center; padding:4px 12px; border-radius:20px; font-size:10px; font-weight:700; cursor:pointer; border:1px solid {{ $filterVendor==='' ? '#C0001C' : '#e5e5e5' }}; background:{{ $filterVendor==='' ? '#C0001C' : 'white' }}; color:{{ $filterVendor==='' ? 'white' : '#555' }};">
                     Semua
                </span>
            </a>
            @foreach($allVendors as $v)
            <a href="{{ route('smr_vendor.index', array_merge(request()->except(['vendor','page']), ['vendor'=>$v])) }}"
               style="text-decoration:none;">
                <span style="display:inline-flex; align-items:center; padding:4px 12px; border-radius:20px; font-size:10px; font-weight:700; cursor:pointer; border:1px solid {{ $filterVendor===$v ? '#C0001C' : '#e5e5e5' }}; background:{{ $filterVendor===$v ? '#C0001C' : 'white' }}; color:{{ $filterVendor===$v ? 'white' : '#555' }}; transition:all .15s;">
                    {{ $v }}
                </span>
            </a>
            @endforeach
        </div>

        {{-- Filter Toolbar --}}
        <div class="toolbar">
            <div class="toolbar-search">
                <span class="material-icons">search</span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari Part Name, No SMR, Problem..." onchange="document.getElementById('filterForm').submit()">
            </div>
            <select name="vendor" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Semua Vendor</option>
                @foreach($allVendors as $v)
                <option value="{{ $v }}" {{ $filterVendor === $v ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <select name="status_barang" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Semua Status</option>
                @foreach($allStatuses as $s)
                <option value="{{ $s }}" {{ $filterStatus === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
            <select name="month" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Semua Bulan</option>
                @foreach($allMonths as $m)
                <option value="{{ $m }}" {{ $filterMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
            <input type="hidden" name="sort" value="{{ $sortBy }}">
            <input type="hidden" name="dir" value="{{ $sortDir }}">
            <button type="submit" class="btn-filter">
                <span class="material-icons">search</span> Cari
            </button>
            <a href="{{ route('smr_vendor.index') }}" class="btn-reset">Kembali</a>
            <span class="result-count">Menampilkan <strong>{{ $items->total() }}</strong> item</span>
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

    <div class="top-scrollbar" id="topScrollbarSmr">
        <div class="top-scrollbar-dummy" id="topScrollbarDummySmr"></div>
    </div>

    <div class="table-wrap" id="tableWrapSmr">
        @if($items->isEmpty())
        <div class="no-data">
            <span class="material-icons">search_off</span>
            <p>Tidak ada data SMR Vendor yang cocok</p>
        </div>
        @else
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>
                        @php $dir_month = ($sortBy==='month' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['sort'=>'month','dir'=>$dir_month]) }}">
                            Bulan
                            @if($sortBy==='month')
                            <span class="material-icons">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>
                        @php $dir_vendor = ($sortBy==='vendor' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['sort'=>'vendor','dir'=>$dir_vendor]) }}">
                            Vendor
                            @if($sortBy==='vendor')
                            <span class="material-icons">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>
                        @php $dir_smr = ($sortBy==='no_smr' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['sort'=>'no_smr','dir'=>$dir_smr]) }}">
                            No SMR
                            @if($sortBy==='no_smr')
                            <span class="material-icons">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>Part Name</th>
                    <th style="text-align:right">Qty</th>
                    <th>Problem</th>
                    <th>Tanggal Keluar</th>
                    <th>Tanggal Masuk</th>
                    <th style="text-align:right">Qty Pengganti</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $i => $item)
                @php
                $rowNum = ($items->currentPage() - 1) * $perPage + $i + 1;
                $statusCls = strtolower($item->status_barang) === 'close' ? 'badge-close' : 'badge-unclose';
                @endphp
                <tr>
                    <td style="color:#bbb;font-size:10px">{{ $rowNum }}</td>
                    <td style="font-weight:600;color:#555">{{ $item->month ?: '-' }}</td>
                    <td style="font-weight:700;color:#1a1a1a">{{ $item->vendor }}</td>
                    <td><span class="badge badge-smr">{{ $item->no_smr ?: '-' }}</span></td>
                    <td style="font-weight:600;color:#111827">{{ $item->part_name }}</td>
                    <td style="text-align:right;font-weight:700;color:#f59e0b">{{ number_format($item->qty) }}</td>
                    <td class="problem-text">{{ $item->problem ?: '-' }}</td>
                    <td>{{ $item->tanggal_keluar ? $item->tanggal_keluar->format('d-m-Y') : '-' }}</td>
                    <td>{{ $item->tanggal_masuk ? $item->tanggal_masuk->format('d-m-Y') : '-' }}</td>
                    <td style="text-align:right;font-weight:700;color:#22c55e">{{ number_format($item->qty_pengganti) }}</td>
                    <td><span class="badge {{ $statusCls }}">{{ $item->status_barang ?: '-' }}</span></td>
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
document.addEventListener('DOMContentLoaded', function() {
    // Sync Top Scrollbar
    const tableWrap = document.getElementById('tableWrapSmr');
    const topScrollbar = document.getElementById('topScrollbarSmr');
    const topScrollbarDummy = document.getElementById('topScrollbarDummySmr');
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
});
</script>
@endpush

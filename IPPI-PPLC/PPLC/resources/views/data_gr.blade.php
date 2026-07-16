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
.stat-pill.success-stock{border-top:2px solid #22c55e;}
.stat-pill.danger-stock{border-top:2px solid #ef4444;}
.stat-pill.warn-stock{border-top:2px solid #f59e0b;}

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

table{width:100%;border-collapse:collapse;font-size:11px;min-width:1450px;}
thead tr{background:#1a1a1a;}
thead th{padding:9px 10px;text-align:left;font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#ccc;white-space:nowrap;}
thead th a{color:inherit;text-decoration:none;display:flex;align-items:center;gap:3px;}
thead th a:hover{color:white;}
thead th .material-icons{font-size:13px;}
tbody tr{border-bottom:1px solid #f0f0f0;}
tbody tr:hover{background:#fafafa;}
tbody td{padding:8px 10px;color:#333;white-space:nowrap;}

.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:9px;font-weight:800;text-transform:uppercase;}
.badge-success{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;}
.badge-error{background:#fff5f5;color:#e11d48;border:1px solid #fecdd3;}
.badge-neutral{background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;}

.val-bold{font-weight:800;color:#111827;}
.sap-message-text{max-width:200px;white-space:normal;word-break:break-word;color:#4b5563;font-size:10px;}

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
            <span class="material-icons">receipt_long</span>
            <div>
                <h2>Data GR (Goods Receipt)</h2>
                <p>Log Transaksi Penerimaan Barang — Sheet DATA GR</p>
            </div>
        </div>
        <div class="stat-pills">
            <div class="stat-pill">
                <span class="pill-val">{{ number_format($totalItems) }}</span>
                <span class="pill-lbl">Total Transaksi</span>
            </div>
            <div class="stat-pill warn-stock">
                <span class="pill-val">{{ number_format($totalQty) }}</span>
                <span class="pill-lbl">Total Qty GR</span>
            </div>
            <div class="stat-pill success-stock">
                <span class="pill-val">{{ number_format($successCount) }}</span>
                <span class="pill-lbl">SAP Success</span>
            </div>
            <div class="stat-pill danger-stock">
                <span class="pill-val">{{ number_format($failCount) }}</span>
                <span class="pill-lbl">SAP Pending/Fail</span>
            </div>
        </div>
    </div>

    @if(!$hasData)

    <div class="empty-state">
        <span class="material-icons">upload_file</span>
        <h3>Belum ada data GR</h3>
        <p>Upload file Excel XLSM dari halaman Dashboard untuk memuat data GR.</p>
        <a href="{{ route('stock.index') }}" class="btn-go-dashboard">
            <span class="material-icons">arrow_back</span> Ke Dashboard
        </a>
    </div>

    @else

    <form method="GET" action="{{ route('data_gr.index') }}" id="filterForm">
        {{-- Vendor Quick Filter Buttons --}}
        <div style="background:white; border-bottom:1px solid #eee; padding:10px 28px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <span style="font-size:10px; font-weight:700; color:#999; text-transform:uppercase; letter-spacing:0.8px; white-space:nowrap;">Vendor:</span>
            <a href="{{ route('data_gr.index', array_merge(request()->except(['vendor_name','page']), ['vendor_name'=>''])) }}"
               style="text-decoration:none;">
                <span style="display:inline-flex; align-items:center; padding:4px 12px; border-radius:20px; font-size:10px; font-weight:700; cursor:pointer; border:1px solid {{ $filterVendor==='' ? '#C0001C' : '#e5e5e5' }}; background:{{ $filterVendor==='' ? '#C0001C' : 'white' }}; color:{{ $filterVendor==='' ? 'white' : '#555' }};">
                     Semua
                </span>
            </a>
            @php $limitedVendors = $allVendors->take(12); @endphp
            @foreach($limitedVendors as $v)
            <a href="{{ route('data_gr.index', array_merge(request()->except(['vendor_name','page']), ['vendor_name'=>$v])) }}"
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
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari PO, Job No, Material, DN..." onchange="document.getElementById('filterForm').submit()">
            </div>
            <select name="vendor_name" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Semua Vendor</option>
                @foreach($allVendors as $v)
                <option value="{{ $v }}" {{ $filterVendor === $v ? 'selected' : '' }}>{{ $v }}</option>
                @endforeach
            </select>
            <select name="gr_status" class="filter-select" onchange="document.getElementById('filterForm').submit()">
                <option value="">Semua Status GR</option>
                @foreach($allStatuses as $s)
                <option value="{{ $s }}" {{ $filterStatus === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
            <input type="hidden" name="sort" value="{{ $sortBy }}">
            <input type="hidden" name="dir" value="{{ $sortDir }}">
            <button type="submit" class="btn-filter">
                <span class="material-icons">search</span> Cari
            </button>
            <a href="{{ route('data_gr.index') }}" class="btn-reset">Kembali</a>
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

    <div class="top-scrollbar" id="topScrollbarGr">
        <div class="top-scrollbar-dummy" id="topScrollbarDummyGr"></div>
    </div>

    <div class="table-wrap" id="tableWrapGr">
        @if($items->isEmpty())
        <div class="no-data">
            <span class="material-icons">search_off</span>
            <p>Tidak ada data GR yang cocok</p>
        </div>
        @else
        <table>
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th>Status GR</th>
                    <th>
                        @php $dir_po = ($sortBy==='po_number' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['sort'=>'po_number','dir'=>$dir_po]) }}">
                            PO Number
                            @if($sortBy==='po_number')
                            <span class="material-icons">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>Job No</th>
                    <th>Material</th>
                    <th>Vendor Name</th>
                    <th style="text-align:right">QTY</th>
                    <th>DN Number</th>
                    <th>Kanban</th>
                    <th>GR Number EDN</th>
                    <th>DN Date</th>
                    <th>
                        @php $dir_gr_date = ($sortBy==='gr_date' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                        <a href="{{ request()->fullUrlWithQuery(['sort'=>'gr_date','dir'=>$dir_gr_date]) }}">
                            GR Date
                            @if($sortBy==='gr_date')
                            <span class="material-icons">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                            @endif
                        </a>
                    </th>
                    <th>GR Number SAP</th>
                    <th>SAP Message</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $i => $item)
                @php
                $rowNum = ($items->currentPage() - 1) * $perPage + $i + 1;
                $statusCls = 'badge-neutral';
                if(str_contains(strtolower($item->gr_status), 'success')) {
                    $statusCls = 'badge-success';
                } elseif(str_contains(strtolower($item->gr_status), 'fail') || str_contains(strtolower($item->gr_status), 'error')) {
                    $statusCls = 'badge-error';
                }
                @endphp
                <tr>
                    <td style="color:#bbb;font-size:10px">{{ $rowNum }}</td>
                    <td><span class="badge {{ $statusCls }}">{{ $item->gr_status }}</span></td>
                    <td style="font-weight:700;color:#111827">{{ $item->po_number }}</td>
                    <td style="font-weight:600;color:#475569">{{ $item->job_number }}</td>
                    <td style="font-weight:600;color:#1a1a1a">{{ $item->material }}</td>
                    <td style="color:#555">{{ $item->vendor_name }}</td>
                    <td style="text-align:right;font-weight:700;color:#111827">{{ number_format($item->qty) }}</td>
                    <td>{{ $item->dn_number }}</td>
                    <td style="color:#666">{{ $item->kanban_number }}</td>
                    <td>{{ $item->gr_number_edn }}</td>
                    <td>{{ $item->dn_date ? $item->dn_date->format('d-m-Y') : '-' }}</td>
                    <td>{{ $item->gr_date ? $item->gr_date->format('d-m-Y H:i:s') : '-' }}</td>
                    <td style="font-weight:700;color:#1e3a8a">{{ $item->gr_number_sap ?: '-' }}</td>
                    <td class="sap-message-text">{{ $item->sap_message ?: '-' }}</td>
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
    const tableWrap = document.getElementById('tableWrapGr');
    const topScrollbar = document.getElementById('topScrollbarGr');
    const topScrollbarDummy = document.getElementById('topScrollbarDummyGr');
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

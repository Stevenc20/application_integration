@extends('layouts.app')

@section('title', 'Master Data Stamping')

@push('styles')
<style>
.page-hero { background: var(--red-main); padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.page-hero-left { display: flex; align-items: center; gap: 12px; }
.page-hero-left .material-icons { font-size: 26px; color: rgba(255,255,255,.7); }
.page-hero h2 { font-size: 20px; font-weight: 900; color: white; }
.page-hero p { font-size: 11px; color: rgba(255,255,255,.65); margin-top: 2px; }
.hero-btn-sync { background: white; color: var(--red-main); border: none; border-radius: 8px; padding: 10px 16px; font-size: 11px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 6px; text-decoration: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.2s; }
.hero-btn-sync:hover { background: #fef2f2; transform: translateY(-1px); }

.toolbar { background: white; border-bottom: 1px solid #e2e2e7; padding: 12px 24px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.toolbar-search { display: flex; align-items: center; gap: 8px; background: #f4f3f8; border: 1px solid #c3c6d1; border-radius: 8px; padding: 7px 12px; flex: 1; min-width: 160px; max-width: 280px; }
.toolbar-search .material-icons { font-size: 16px; color: #737780; }
.toolbar-search input { border: none; background: transparent; outline: none; font-size: 12px; font-family: 'Inter',sans-serif; width: 100%; }
.btn-filter { background: var(--red-main); color: white; border: none; border-radius: 8px; padding: 8px 14px; font-size: 11px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 5px; }
.btn-reset { background: #f0f0f0; color: #555; border: none; border-radius: 8px; padding: 8px 10px; font-size: 11px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; }
.result-count { margin-left: auto; font-size: 12px; color: #737780; white-space: nowrap; }
.result-count strong { color: var(--red-main); font-weight: 800; }

.table-wrap { overflow-x: auto; }
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

table { width: 100%; border-collapse: collapse; font-size: 11px; min-width: 1600px; }
thead tr { background: #1a1a1a; }
thead th { padding: 9px 10px; text-align: left; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: #ccc; white-space: nowrap; }
tbody tr { border-bottom: 1px solid #f0f0f0; }
tbody tr:hover { background: #fafafa; }
tbody td { padding: 7px 10px; color: #333; white-space: nowrap; }

.badge-common { background: #f8fafc; color: #64748b; font-size: 9px; font-weight: 800; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; }
.alert { margin: 14px 24px 0; padding: 10px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
.alert.success { background: #f0fdf4; color: #15803d; border: 1px solid #bbf7d0; }
.alert.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.alert .material-icons { font-size: 17px; }

.pagination-wrap { padding: 16px 24px 28px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.pagination-info { font-size: 12px; color: #737780; }
.pagination-info strong { color: #333; }
.pagination { display: flex; gap: 4px; align-items: center; }
.page-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid #e5e5e5; background: white; font-size: 12px; font-weight: 600; color: #555; display: flex; align-items: center; justify-content: center; text-decoration: none; }
.page-btn:hover { border-color: var(--red-main); color: var(--red-main); }
.page-btn.active { background: var(--red-main); color: white; border-color: var(--red-main); }
.page-btn.disabled { opacity: .35; pointer-events: none; }
.page-btn .material-icons { font-size: 15px; }

.no-data { text-align: center; padding: 60px 20px; color: #bbb; }
.no-data .material-icons { font-size: 48px; display: block; margin-bottom: 12px; }
</style>
@endpush

@section('content')
    <div class="page-hero">
        <div class="page-hero-left">
            <span class="material-icons">storage</span>
            <div>
                <h2>Master Data Stamping</h2>
                <p>Kumpulan data master penstempelan untuk acuan pengisian manual di Schedule Stamping</p>
            </div>
        </div>
        <form action="{{ route('master_stamping.import') }}" method="POST" enctype="multipart/form-data" style="display:flex; align-items:center; gap:10px;">
            @csrf
            <input type="file" name="excel_file" accept=".xlsx,.xls,.xlsm" required style="display:none;" id="masterFileInput" onchange="if(confirm('Apakah Anda yakin ingin mengupload dan memperbarui data master dari file ini?')) this.form.submit(); else this.value='';">
            <button type="button" class="hero-btn-sync" onclick="document.getElementById('masterFileInput').click()">
                <span class="material-icons">upload_file</span>
                Upload Master Excel
            </button>
        </form>
    </div>

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

    <div class="toolbar">
        <form action="{{ route('master_stamping.index') }}" method="GET" style="display:flex; align-items:center; gap:8px; width:100%; flex-wrap:wrap;">
            @if($shift)
            <input type="hidden" name="shift" value="{{ $shift }}">
            @endif
            <div class="toolbar-search">
                <span class="material-icons">search</span>
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari Job No, Master, Part Name...">
            </div>
            <button type="submit" class="btn-filter"><span class="material-icons">search</span>Cari</button>
            @if($search)
            <a href="{{ route('master_stamping.index', ['shift' => $shift]) }}" class="btn-reset">Kembali</a>
            @endif

            <!-- Shift Filters Button Group -->
            <div style="display:flex; gap:4px; background:#f1f5f9; padding:4px; border-radius:8px; align-items:center; margin-left:8px; border: 1px solid #e2e8f0;">
                <a href="{{ route('master_stamping.index', array_merge(request()->query(), ['shift' => ''])) }}" 
                   style="padding:6px 12px; border-radius:6px; font-size:11px; font-weight:700; text-decoration:none; transition:all 0.15s; {{ $shift === '' ? 'background:white; color:#0f172a; box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:#64748b;' }}">
                    Semua
                </a>
                <a href="{{ route('master_stamping.index', array_merge(request()->query(), ['shift' => 'pagi'])) }}" 
                   style="padding:6px 12px; border-radius:6px; font-size:11px; font-weight:700; text-decoration:none; transition:all 0.15s; {{ $shift === 'pagi' ? 'background:white; color:#0f172a; box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:#64748b;' }}">
                    Shift Pagi (Sheet 1)
                </a>
                <a href="{{ route('master_stamping.index', array_merge(request()->query(), ['shift' => 'malam'])) }}" 
                   style="padding:6px 12px; border-radius:6px; font-size:11px; font-weight:700; text-decoration:none; transition:all 0.15s; {{ $shift === 'malam' ? 'background:white; color:#0f172a; box-shadow:0 1px 3px rgba(0,0,0,0.1);' : 'color:#64748b;' }}">
                    Shift Malam (Sheet 2)
                </a>
            </div>

            <div class="result-count">
                Total Master Data: <strong>{{ number_format($totalDb, 0) }}</strong>
            </div>
        </form>
    </div>

    @if($items->isNotEmpty() && $items->lastPage() > 1)
    <div class="pagination-wrap" style="padding-bottom: 12px; border-bottom: 1px solid #f0f0f0;">
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

    <div class="top-scrollbar" id="topScrollbarMaster">
        <div class="top-scrollbar-dummy" id="topScrollbarDummyMaster"></div>
    </div>

    <div class="table-wrap" id="tableWrapMaster">
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
                    <th>Job No</th>
                    <th>Job Master</th>
                    <th>Shift</th>
                    <th>Proses Line</th>
                    <th>Mach</th>
                    <th>Part Number</th>
                    <th>Part Name</th>
                    <th style="text-align:right">Qty/Unit</th>
                    <th>Type Pallet</th>
                    <th style="text-align:right">Qty/Pallet</th>
                    <th style="text-align:right">CT (sec)</th>
                    <th style="text-align:right">DCT</th>
                    <th style="text-align:right">Reg Active</th>
                    <th style="text-align:right">MCT</th>
                    <th style="text-align:right">TPT</th>
                    <th>Customer</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $idx => $item)
                <tr>
                    <td style="color:#bbb; font-size:10px">{{ $items->firstItem() + $idx }}</td>
                    <td style="font-weight:700; color:#1a1a1a">{{ $item->job_no }}</td>
                    <td style="font-weight:600; color:#444">{{ $item->job_master }}</td>
                    <td>
                        @if($item->is_shift_pagi && $item->is_shift_malam)
                            <span style="background:#dbeafe; color:#1e40af; font-size:9px; font-weight:800; padding:2px 6px; border-radius:4px; text-transform:uppercase; margin-right:2px;">Pagi</span>
                            <span style="background:#faf5ff; color:#6b21a8; font-size:9px; font-weight:800; padding:2px 6px; border-radius:4px; text-transform:uppercase;">Malam</span>
                        @elseif($item->is_shift_pagi)
                            <span style="background:#dbeafe; color:#1e40af; font-size:9px; font-weight:800; padding:2px 6px; border-radius:4px; text-transform:uppercase;">Pagi</span>
                        @elseif($item->is_shift_malam)
                            <span style="background:#faf5ff; color:#6b21a8; font-size:9px; font-weight:800; padding:2px 6px; border-radius:4px; text-transform:uppercase;">Malam</span>
                        @else
                            <span style="color:#94a3b8; font-size:9px; font-weight:800;">-</span>
                        @endif
                    </td>
                    <td>{{ $item->proses_line }}</td>
                    <td>{{ $item->mach }}</td>
                    <td style="font-family:monospace">{{ $item->part_no }}</td>
                    <td style="color:#666">{{ $item->part_name }}</td>
                    <td style="text-align:right">{{ $item->qty_unit ? number_format($item->qty_unit, 0) : '-' }}</td>
                    <td><span class="badge-common">{{ $item->type_pallet ?: '-' }}</span></td>
                    <td style="text-align:right">{{ $item->qty_pallet ? number_format($item->qty_pallet, 0) : '-' }}</td>
                    <td style="text-align:right">{{ $item->ct_detik ? number_format($item->ct_detik, 1) : '-' }}</td>
                    <td style="text-align:right">{{ $item->dct ? number_format($item->dct, 1) : '-' }}</td>
                    <td style="text-align:right">{{ $item->reg_active ? number_format($item->reg_active, 1) : '-' }}</td>
                    <td style="text-align:right">{{ $item->mct ? number_format($item->mct, 1) : '-' }}</td>
                    <td style="text-align:right">{{ $item->tpt ? number_format($item->tpt, 1) : '-' }}</td>
                    <td>{{ $item->customer }}</td>
                    <td style="color:#888">{{ $item->remarks ?: '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    @if($items->isNotEmpty() && $items->lastPage() > 1)
    <div class="pagination-wrap" style="margin-top: 12px; border-top: 1px solid #f0f0f0;">
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sync Top Scrollbar
    const tableWrap = document.getElementById('tableWrapMaster');
    const topScrollbar = document.getElementById('topScrollbarMaster');
    const topScrollbarDummy = document.getElementById('topScrollbarDummyMaster');
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

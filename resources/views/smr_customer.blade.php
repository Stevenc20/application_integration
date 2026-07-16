@extends('layouts.app')

@section('title', 'SMR Customer')

@push('styles')
<style>
.table-wrap::-webkit-scrollbar { height: 16px; }
.table-wrap::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.table-wrap::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
.table-wrap::-webkit-scrollbar-thumb:hover { background: #333; }
.top-scrollbar { width: 100%; overflow-x: auto; overflow-y: hidden; height: 18px; margin-bottom: 4px; }
.top-scrollbar-dummy { height: 1px; }
.top-scrollbar::-webkit-scrollbar { height: 16px; }
.top-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.top-scrollbar::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
.top-scrollbar::-webkit-scrollbar-thumb:hover { background: #333; }
@media (max-width: 768px) {
    .top-scrollbar::-webkit-scrollbar { height: 20px; }
    .table-wrap::-webkit-scrollbar { height: 20px; }
    .top-scrollbar { height: 22px; }
}
</style>
@endpush

@section('content')
<div class="space-y-6">

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <span class="material-icons text-3xl">precision_manufacturing</span>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">SMR Customer</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Data Claim SMR Customer — Sheet SMR CUSTOMER</p>
            </div>
        </div>
        <div class="relative flex gap-3 flex-wrap">
            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 text-center ring-1 ring-white/10 min-w-[100px]">
                <div class="text-xl font-black text-white">{{ number_format($totalItems) }}</div>
                <div class="text-[9px] font-bold text-white/60 uppercase tracking-wider mt-0.5">Total Item</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 text-center ring-1 ring-white/10 min-w-[100px] border-t-2 border-amber-400">
                <div class="text-xl font-black text-white">{{ number_format($totalQtySMR) }}</div>
                <div class="text-[9px] font-bold text-white/60 uppercase tracking-wider mt-0.5">Qty Claim SMR</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 text-center ring-1 ring-white/10 min-w-[100px] border-t-2 border-red-400">
                <div class="text-xl font-black text-white">Rp {{ number_format($totalCostReject, 0, ',', '.') }}</div>
                <div class="text-[9px] font-bold text-white/60 uppercase tracking-wider mt-0.5">Cost Rejection</div>
            </div>
            <div class="bg-white/10 backdrop-blur-sm rounded-xl px-5 py-3 text-center ring-1 ring-white/10 min-w-[100px] border-t-2 border-cyan-400">
                <div class="text-xl font-black text-white">{{ number_format($avgRejectRate * 100, 4) }}%</div>
                <div class="text-[9px] font-bold text-white/60 uppercase tracking-wider mt-0.5">Avg Rejection Rate</div>
            </div>
        </div>
    </div>

    @if(!$hasData)

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col items-center justify-center py-20">
        <span class="material-icons text-6xl text-slate-300 mb-4">upload_file</span>
        <h3 class="text-lg font-black text-slate-400 mb-2">Belum ada data SMR Customer</h3>
        <p class="text-sm text-slate-300 max-w-xs text-center leading-relaxed">Upload file Excel XLSM dari halaman Dashboard untuk memuat data SMR Customer.</p>
    </div>

    @else

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        {{-- Customer Quick Filter Buttons --}}
        <div class="px-6 py-3 border-b border-slate-100 flex items-center gap-2 flex-wrap">
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mr-1">Customer:</span>
            <a href="{{ route('smr_customer.index', array_merge(request()->except(['customer','page']), ['customer'=>''])) }}"
               class="px-3 py-1.5 rounded-full text-[10px] font-bold border transition-all {{ $filterCustomer==='' ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-400' }}">
                Semua
            </a>
            @foreach($allCustomers as $c)
            <a href="{{ route('smr_customer.index', array_merge(request()->except(['customer','page']), ['customer'=>$c])) }}"
               class="px-3 py-1.5 rounded-full text-[10px] font-bold border transition-all {{ $filterCustomer===$c ? 'bg-slate-800 text-white border-slate-800' : 'bg-white text-slate-500 border-slate-200 hover:border-slate-400' }}">
                {{ $c }}
            </a>
            @endforeach
        </div>

        {{-- Toolbar --}}
        <form method="GET" action="{{ route('smr_customer.index') }}" id="filterForm" class="px-6 py-4 border-b border-slate-100">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 flex-1 min-w-[160px] max-w-[240px] focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                    <span class="material-icons text-sm text-slate-400">search</span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari Customer, Part Name, No SMR..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400" onchange="document.getElementById('filterForm').submit()">
                </div>
                <select name="customer" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px] cursor-pointer min-w-[140px]" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Semua Customer</option>
                    @foreach($allCustomers as $c)
                    <option value="{{ $c }}" {{ $filterCustomer === $c ? 'selected' : '' }}>{{ $c }}</option>
                    @endforeach
                </select>
                <select name="quarterly" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px] cursor-pointer min-w-[130px]" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Semua Quarter</option>
                    @foreach($allQuarters as $q)
                    <option value="{{ $q }}" {{ $filterQuarter === $q ? 'selected' : '' }}>{{ $q }}</option>
                    @endforeach
                </select>
                <select name="month" class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm font-medium text-slate-600 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400 h-[42px] cursor-pointer min-w-[130px]" onchange="document.getElementById('filterForm').submit()">
                    <option value="">Semua Bulan</option>
                    @foreach($allMonths as $m)
                    <option value="{{ $m }}" {{ $filterMonth === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="sort" value="{{ $sortBy }}">
                <input type="hidden" name="dir" value="{{ $sortDir }}">
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center gap-1">
                    <span class="material-icons text-sm">search</span> Cari
                </button>
                <a href="{{ route('smr_customer.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center">Reset</a>
                <span class="ml-auto text-xs font-medium text-slate-500">Menampilkan <strong class="font-black text-slate-700">{{ $items->total() }}</strong> item</span>
            </div>
        </form>

        {{-- Pagination Top --}}
        @if(isset($items) && $items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->lastPage() > 1)
        <div class="px-6 py-3 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row items-center justify-between gap-3">
            <div class="text-xs font-medium text-slate-500">
                Menampilkan <strong class="font-black text-slate-700">{{ $items->firstItem() }}-{{ $items->lastItem() }}</strong> dari <strong class="font-black text-slate-700">{{ $items->total() }}</strong>
            </div>
            <div class="flex items-center gap-1">
                @if($items->onFirstPage())
                <span class="w-8 h-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center opacity-40 cursor-default"><span class="material-icons text-sm text-slate-500">chevron_left</span></span>
                @else
                <a class="w-8 h-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center hover:border-slate-800 hover:text-slate-800 transition-all" href="{{ $items->previousPageUrl() }}"><span class="material-icons text-sm">chevron_left</span></a>
                @endif

                @php
                $pStart = max(1, $items->currentPage() - 2);
                $pEnd   = min($items->lastPage(), $items->currentPage() + 2);
                @endphp

                @if($pStart > 1)
                <a class="w-8 h-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-xs font-bold text-slate-600 hover:border-slate-800 hover:text-slate-800 transition-all" href="{{ $items->url(1) }}">1</a>
                @if($pStart > 2)
                <span class="px-1 text-xs text-slate-300">...</span>
                @endif
                @endif

                @for($p = $pStart; $p <= $pEnd; $p++)
                @if($p === $items->currentPage())
                <span class="w-8 h-8 rounded-lg bg-slate-800 text-white flex items-center justify-center text-xs font-bold">{{ $p }}</span>
                @else
                <a class="w-8 h-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-xs font-bold text-slate-600 hover:border-slate-800 hover:text-slate-800 transition-all" href="{{ $items->url($p) }}">{{ $p }}</a>
                @endif
                @endfor

                @if($pEnd < $items->lastPage())
                @if($pEnd < $items->lastPage() - 1)
                <span class="px-1 text-xs text-slate-300">...</span>
                @endif
                <a class="w-8 h-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-xs font-bold text-slate-600 hover:border-slate-800 hover:text-slate-800 transition-all" href="{{ $items->url($items->lastPage()) }}">{{ $items->lastPage() }}</a>
                @endif

                @if($items->hasMorePages())
                <a class="w-8 h-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center hover:border-slate-800 hover:text-slate-800 transition-all" href="{{ $items->nextPageUrl() }}"><span class="material-icons text-sm">chevron_right</span></a>
                @else
                <span class="w-8 h-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center opacity-40 cursor-default"><span class="material-icons text-sm text-slate-500">chevron_right</span></span>
                @endif
            </div>
        </div>
        @endif

        {{-- Synced Top Scrollbar --}}
        <div class="top-scrollbar" id="topScrollbarSmrCust">
            <div class="top-scrollbar-dummy" id="topScrollbarDummySmrCust"></div>
        </div>

        {{-- Table --}}
        <div class="table-wrap overflow-x-auto" id="tableWrapSmrCust">
            @if($items->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <span class="material-icons text-5xl text-slate-300 mb-3">search_off</span>
                <p class="text-sm font-medium text-slate-400">Tidak ada data SMR Customer yang cocok</p>
            </div>
            @else
            <table class="w-full text-left border-collapse min-w-[1400px]">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-[50px]">#</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">
                            @php $dir_date = ($sortBy==='date' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ request()->fullUrlWithQuery(['sort'=>'date','dir'=>$dir_date]) }}" class="flex items-center gap-1 text-inherit no-underline hover:text-slate-800">
                                Tanggal
                                @if($sortBy==='date')
                                <span class="material-icons text-xs">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Bulan</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Quarter</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">
                            @php $dir_cust = ($sortBy==='customer' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ request()->fullUrlWithQuery(['sort'=>'customer','dir'=>$dir_cust]) }}" class="flex items-center gap-1 text-inherit no-underline hover:text-slate-800">
                                Customer
                                @if($sortBy==='customer')
                                <span class="material-icons text-xs">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">
                            @php $dir_smr = ($sortBy==='no_smr' && $sortDir==='asc') ? 'desc' : 'asc'; @endphp
                            <a href="{{ request()->fullUrlWithQuery(['sort'=>'no_smr','dir'=>$dir_smr]) }}" class="flex items-center gap-1 text-inherit no-underline hover:text-slate-800">
                                No SMR
                                @if($sortBy==='no_smr')
                                <span class="material-icons text-xs">{{ $sortDir==='asc' ? 'arrow_upward' : 'arrow_downward' }}</span>
                                @endif
                            </a>
                        </th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Job No</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Part Number</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Part Name</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Qty SMR</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Total Prod</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Cost Reject</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">Reject Rate</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Problem</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">Countermeasures</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($items as $i => $item)
                    @php
                    $rowNum = ($items->currentPage() - 1) * $perPage + $i + 1;
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-xs text-slate-300">{{ $rowNum }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-500">{{ $item->date ? $item->date->format('d-m-Y') : '-' }}</td>
                        <td class="py-3 px-4 text-xs text-slate-500">{{ $item->month ?: '-' }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-400">{{ $item->quarterly ?: '-' }}</td>
                        <td class="py-3 px-4"><span class="px-2 py-0.5 rounded-full text-[10px] font-black border bg-blue-50 text-blue-700 border-blue-200">{{ $item->customer ?: '-' }}</span></td>
                        <td class="py-3 px-4"><span class="px-2 py-0.5 rounded-full text-[10px] font-black border bg-slate-100 text-slate-600 border-slate-200">{{ $item->no_smr ?: '-' }}</span></td>
                        <td class="py-3 px-4 text-xs font-black text-slate-800">{{ $item->job_no ?: '-' }}</td>
                        <td class="py-3 px-4 text-xs text-slate-500">{{ $item->part_number ?: '-' }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-800">{{ $item->part_name ?: '-' }}</td>
                        <td class="py-3 px-4 text-xs font-black text-red-500 text-right">{{ number_format($item->qty_smr) }}</td>
                        <td class="py-3 px-4 text-xs text-slate-500 text-right">{{ number_format($item->total_production) }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-red-700 text-right">Rp {{ number_format($item->cost_rijection, 0, ',', '.') }}</td>
                        <td class="py-3 px-4 text-xs font-black text-amber-500 text-right">{{ number_format($item->rijection_rate * 100, 4) }}%</td>
                        <td class="py-3 px-4 text-xs text-slate-400 max-w-[200px] break-words">{{ $item->problem ?: '-' }}</td>
                        <td class="py-3 px-4 text-xs text-emerald-600 font-bold max-w-[220px] break-words">{{ $item->countermeasures ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>
    </div>

    @endif

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableWrap = document.getElementById('tableWrapSmrCust');
    const topScrollbar = document.getElementById('topScrollbarSmrCust');
    const topScrollbarDummy = document.getElementById('topScrollbarDummySmrCust');
    if(tableWrap && topScrollbar && topScrollbarDummy) {
        const table = tableWrap.querySelector('table');
        if(table) {
            const updateDummyWidth = () => { topScrollbarDummy.style.width = table.offsetWidth + 'px'; };
            updateDummyWidth();
            topScrollbar.addEventListener('scroll', function() { tableWrap.scrollLeft = topScrollbar.scrollLeft; });
            tableWrap.addEventListener('scroll', function() { topScrollbar.scrollLeft = tableWrap.scrollLeft; });
            window.addEventListener('resize', updateDummyWidth);
        } else {
            topScrollbar.style.display = 'none';
        }
    }
});
</script>
@endpush

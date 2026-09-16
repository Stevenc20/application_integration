@extends('layouts.app')

@section('title', 'Master Data Stamping')

@section('content')
<div class="space-y-6">

    {{-- Alert Messages --}}
    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl flex items-center gap-3 border border-emerald-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('error') }}</span>
    </div>
    @endif

    {{-- Hero Section --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" /></svg>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Master Data Stamping</h1>
                <p class="text-rose-200 text-sm font-semibold mt-1">Kumpulan data master penstempelan untuk acuan pengisian manual di Schedule Stamping</p>
            </div>
        </div>
        
        <div class="relative flex gap-3 flex-wrap">
            <form action="{{ route('master_stamping.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="file" name="excel_file" accept=".xlsx,.xls,.xlsm" required style="display:none;" id="masterFileInput" onchange="if(confirm('Apakah Anda yakin ingin mengupload dan memperbarui data master dari file ini?')) this.form.submit(); else this.value='';">
                <button type="button" class="bg-white hover:bg-rose-50 text-red-600 font-bold py-2.5 px-5 rounded-xl transition-all flex items-center gap-2 text-sm shadow-xl border border-white" onclick="document.getElementById('masterFileInput').click()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                    Upload Master Excel
                </button>
            </form>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-100 bg-white">
            <form action="{{ route('master_stamping.index') }}" method="GET" class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
                @if($shift)
                <input type="hidden" name="shift" value="{{ $shift }}">
                @endif
                
                <div class="flex items-center gap-2 flex-wrap flex-1">
                    <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full lg:max-w-[300px] focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari Job No, Master, Part Name..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400">
                    </div>

                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px]">Cari</button>
                    @if($search)
                        <a href="{{ route('master_stamping.index', ['shift' => $shift]) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl transition-all text-sm h-[42px] flex items-center">Reset</a>
                    @endif

                    <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl ml-0 lg:ml-2 border border-slate-200">
                        <a href="{{ route('master_stamping.index', array_merge(request()->query(), ['shift' => ''])) }}" 
                           class="px-4 py-1.5 rounded-lg text-xs font-black transition-all {{ $shift === '' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            Semua
                        </a>
                        <a href="{{ route('master_stamping.index', array_merge(request()->query(), ['shift' => 'pagi'])) }}" 
                           class="px-4 py-1.5 rounded-lg text-xs font-black transition-all {{ $shift === 'pagi' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            Shift Pagi (Sheet 1)
                        </a>
                        <a href="{{ route('master_stamping.index', array_merge(request()->query(), ['shift' => 'malam'])) }}" 
                           class="px-4 py-1.5 rounded-lg text-xs font-black transition-all {{ $shift === 'malam' ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            Shift Malam (Sheet 2)
                        </a>
                    </div>
                </div>

                <div class="text-sm font-medium text-slate-500 xl:ml-auto border bg-slate-50 border-slate-200 px-4 py-2.5 rounded-xl whitespace-nowrap">
                    Total Master Data: <span class="font-black text-rose-600">{{ number_format($totalDb, 0) }}</span>
                </div>
            </form>
        </div>

        <div id="topScrollbarMaster" class="w-full overflow-x-auto overflow-y-hidden h-4 mb-1 scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-slate-100 hidden md:block">
            <div id="topScrollbarDummyMaster" class="h-1"></div>
        </div>

        <div id="tableWrapMaster" class="overflow-x-auto scrollbar-thin scrollbar-thumb-slate-300 scrollbar-track-slate-100 pb-2">
            @if($items->isEmpty())
            <div class="py-16 text-center text-slate-500 font-medium">
                <div class="flex flex-col items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    Tidak ada data yang cocok.
                </div>
            </div>
            @else
            <table class="w-full text-left border-collapse min-w-[1800px]">
                <thead>
                    <tr class="bg-slate-50 border-y border-slate-200">
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap w-12 text-center">#</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">JOB NO</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">JOB MASTER</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">SHIFT</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">PROSES LINE</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">MACH</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">PART NUMBER</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">PART NAME</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">QTY/UNIT</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">TYPE PALLET</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">QTY/PALLET</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">CT (SEC)</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">DCT</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">REG ACTIVE</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">MCT</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">TPT</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">CUSTOMER</th>
                        <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">REMARKS</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($items as $idx => $item)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-xs font-medium text-slate-400 text-center">{{ $items->firstItem() + $idx }}</td>
                        <td class="py-3 px-4 text-xs font-black text-blue-600">{{ $item->job_no }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-800">{{ $item->job_master }}</td>
                        <td class="py-3 px-4 text-center">
                            @if($item->is_shift_pagi && $item->is_shift_malam)
                                <div class="flex gap-1 justify-center">
                                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider">Pagi</span>
                                    <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider">Malam</span>
                                </div>
                            @elseif($item->is_shift_pagi)
                                <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider">Pagi</span>
                            @elseif($item->is_shift_malam)
                                <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-wider">Malam</span>
                            @else
                                <span class="text-slate-400 font-bold">-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $item->proses_line }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $item->mach }}</td>
                        <td class="py-3 px-4 text-xs font-mono font-bold text-slate-700 bg-slate-50 rounded px-1.5 inline-block my-1">{{ $item->part_no }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $item->part_name }}</td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-800 text-right">{{ $item->qty_unit ? number_format($item->qty_unit, 0) : '-' }}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-md text-[10px] font-bold">{{ $item->type_pallet ?: '-' }}</span>
                        </td>
                        <td class="py-3 px-4 text-xs font-bold text-slate-800 text-right">{{ $item->qty_pallet ? number_format($item->qty_pallet, 0) : '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600 text-right">{{ $item->ct_detik ? number_format($item->ct_detik, 1) : '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600 text-right">{{ $item->dct ? number_format($item->dct, 1) : '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600 text-right">{{ $item->reg_active ? number_format($item->reg_active, 1) : '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600 text-right">{{ $item->mct ? number_format($item->mct, 1) : '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600 text-right">{{ $item->tpt ? number_format($item->tpt, 1) : '-' }}</td>
                        <td class="py-3 px-4 text-xs font-medium text-slate-600">{{ $item->customer }}</td>
                        <td class="py-3 px-4 text-[11px] text-slate-500 italic max-w-xs truncate">{{ $item->remarks ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        @if($items->isNotEmpty() && $items->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="text-xs font-medium text-slate-500">
                Menampilkan <span class="font-black text-slate-700">{{ $items->firstItem() ?? 0 }}-{{ $items->lastItem() ?? 0 }}</span> dari <span class="font-black text-slate-700">{{ $items->total() }}</span>
            </div>
            <div>
                {{ $items->links('pagination::tailwind') }}
            </div>
        </div>
        @endif
    </div>

</div>
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
            topScrollbar.classList.add('hidden');
        }
    }
});
</script>
@endpush

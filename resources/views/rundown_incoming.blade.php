@extends('layouts.app')

@push('styles')
<style>
    .top-scrollbar::-webkit-scrollbar { height: 16px; }
    .top-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .top-scrollbar::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
    .top-scrollbar::-webkit-scrollbar-thumb:hover { background: #333; }
    .table-wrap::-webkit-scrollbar { height: 16px; }
    .table-wrap::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .table-wrap::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
    .table-wrap::-webkit-scrollbar-thumb:hover { background: #333; }
</style>
@endpush

@section('content')
    @if(session('sp_success'))
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl flex items-center gap-3 border border-emerald-100 shadow-sm mb-6 mt-4">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-sm font-semibold">{{ session('sp_success') }}</span>
    </div>
    @endif
    @if(session('sp_error'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm mb-6 mt-4">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-sm font-semibold">{{ session('sp_error') }}</span>
    </div>
    @endif

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-6 shadow-xl relative overflow-hidden mb-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                    <span class="material-icons text-2xl">view_list</span>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight">Rundown Incoming</h1>
                    <p class="text-rose-200 text-sm font-semibold mt-0.5">Rundown incoming stock harian per vendor</p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                <div class="bg-blue-500 text-white rounded-xl px-4 py-2.5 text-center min-w-[100px] shadow-lg">
                    <div class="text-[10px] font-black uppercase tracking-wider text-blue-100">OVER STOCK</div>
                    <div class="text-2xl font-black leading-tight">{{ $countOver ?? 0 }}</div>
                </div>
                <div class="bg-emerald-500 text-white rounded-xl px-4 py-2.5 text-center min-w-[100px] shadow-lg">
                    <div class="text-[10px] font-black uppercase tracking-wider text-emerald-100">STANDAR</div>
                    <div class="text-2xl font-black leading-tight">{{ $countStandar ?? 0 }}</div>
                </div>
                <div class="bg-red-500 text-white rounded-xl px-4 py-2.5 text-center min-w-[100px] shadow-lg ring-1 ring-white/20">
                    <div class="text-[10px] font-black uppercase tracking-wider text-red-100">CRITICAL STOCK</div>
                    <div class="text-2xl font-black leading-tight">{{ $countMinim ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>

    @if(!$hasData)
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12">
        <div class="flex flex-col items-center justify-center text-center py-12">
            <svg class="w-14 h-14 text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <h3 class="text-base font-bold text-slate-600 mb-2">Belum ada data Rundown Incoming</h3>
            <p class="text-sm text-slate-400">Silakan upload file Excel Rundown Incoming Vendor di sudut kanan atas.</p>
        </div>
    </div>
    @else
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5">
            {{-- Row 1: Search + Actions --}}
            <form action="{{ route('rundown_incoming.index') }}" method="GET" id="toolbarForm">
                <div class="flex items-center justify-between flex-wrap gap-3 mb-4 pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2">
                            <span class="material-icons text-slate-400 text-sm">search</span>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Cari Job No, Kategori..." class="bg-transparent border-none outline-none text-xs text-slate-700 w-44 placeholder:text-slate-400">
                        </div>
                        <button type="submit" class="inline-flex items-center gap-1 bg-slate-800 hover:bg-slate-900 text-white rounded-xl px-3.5 py-2 text-xs font-bold transition-all">
                            <span class="material-icons text-sm">search</span>Cari
                        </button>
                        <a href="{{ route('rundown_incoming.index', ['sheet' => $selectedSheet]) }}" class="inline-flex items-center bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl px-3.5 py-2 text-xs font-bold transition-all">Kembali</a>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <div class="flex items-center gap-2 pr-3 border-r border-slate-200">
                            <a href="{{ route('rundown_incoming.template') }}" class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl px-3 py-2 text-xs font-bold transition-all">
                                <span class="material-icons text-sm">file_download</span> Download Template
                            </a>
                            <button type="button" onclick="openRiDelete()" class="inline-flex items-center gap-1.5 bg-red-500 hover:bg-red-600 text-white rounded-xl px-3 py-2 text-xs font-bold transition-all">
                                <span class="material-icons text-sm">delete</span> Delete
                            </button>
                            <button type="button" onclick="openRiIncoming()" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl px-3 py-2 text-xs font-bold transition-all">
                                <span class="material-icons text-sm">add_shopping_cart</span> Add Incoming
                            </button>
                            <button type="button" onclick="openRiAddJob()" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl px-3 py-2 text-xs font-bold transition-all">
                                <span class="material-icons text-sm">add</span> Add Job No
                            </button>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="openRiExport()" class="inline-flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl px-3 py-2 text-xs font-bold transition-all">
                                <span class="material-icons text-sm">file_download</span> Export
                            </button>
                            <label for="sp_excel_input" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl px-3 py-2 text-xs font-bold transition-all cursor-pointer">
                                <span class="material-icons text-sm">upload_file</span> Upload
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Row 2: Selectors --}}
                <div class="flex items-center gap-3 flex-wrap">
                    <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-3 py-2 w-44">
                        <span class="material-icons text-red-500 text-sm">calendar_month</span>
                        <input type="date" id="calendarInput" onchange="convertAndSubmitDate(this.value)" class="border-none outline-none text-xs font-semibold text-slate-700 bg-transparent w-full cursor-pointer">
                        <input type="hidden" name="sheet" id="sheetHidden" value="{{ $selectedSheet }}">
                    </div>

                    {{-- Vendor Dropdown --}}
                    <div class="relative" id="vendorDropdownContainer">
                        <button type="button" onclick="toggleRiVendorDropdown(event)" class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-700 hover:border-red-400 transition-all min-w-[180px]">
                            <span class="material-icons text-red-400 text-sm">factory</span>
                            <span class="flex-1 text-left">{{ $filterVendor ?: 'Semua Vendor' }}</span>
                            <span class="material-icons text-slate-400 transition-transform text-base" id="vendorArrow">expand_more</span>
                        </button>
                        <div class="absolute top-full left-0 right-0 mt-0 bg-white border border-red-400 border-t-0 rounded-b-xl shadow-lg z-[9999] hidden max-h-72 overflow-y-auto" id="vendorDropdownContent">
                            <a href="{{ request()->fullUrlWithQuery(['vendor'=>null, 'page'=>null]) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 border-b border-slate-100 last:border-b-0 {{ $filterVendor==='' ? 'bg-red-500 text-white hover:bg-red-600 hover:text-white' : '' }}">
                                <span class="material-icons text-sm">apps</span> Semua Vendor
                            </a>
                            @foreach($allVendors as $v)
                            <a href="{{ request()->fullUrlWithQuery(['vendor'=>$v, 'page'=>null]) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 border-b border-slate-100 last:border-b-0 {{ $filterVendor===$v ? 'bg-red-500 text-white hover:bg-red-600 hover:text-white' : '' }}">
                                <span class="material-icons text-sm">business</span> {{ $v }}
                            </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Customer Dropdown --}}
                    <div class="relative" id="customerDropdownContainer">
                        <button type="button" onclick="toggleRiCustomerDropdown(event)" class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-700 hover:border-red-400 transition-all min-w-[180px]">
                            <span class="material-icons text-red-400 text-sm">person</span>
                            <span class="flex-1 text-left">{{ $filterCustomer ?: 'Semua Customer' }}</span>
                            <span class="material-icons text-slate-400 transition-transform text-base" id="customerArrow">expand_more</span>
                        </button>
                        <div class="absolute top-full left-0 right-0 mt-0 bg-white border border-red-400 border-t-0 rounded-b-xl shadow-lg z-[9999] hidden max-h-72 overflow-y-auto" id="customerDropdownContent">
                            <a href="{{ request()->fullUrlWithQuery(['customer'=>null, 'page'=>null]) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 border-b border-slate-100 last:border-b-0 {{ $filterCustomer==='' ? 'bg-red-500 text-white hover:bg-red-600 hover:text-white' : '' }}">
                                <span class="material-icons text-sm">apps</span> Semua Customer
                            </a>
                            @foreach($allCustomers as $c)
                            <a href="{{ request()->fullUrlWithQuery(['customer'=>$c, 'page'=>null]) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 border-b border-slate-100 last:border-b-0 {{ $filterCustomer===$c ? 'bg-red-500 text-white hover:bg-red-600 hover:text-white' : '' }}">
                                <span class="material-icons text-sm">person_outline</span> {{ $c }}
                            </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Category Dropdown --}}
                    <div class="relative" id="categoryDropdownContainer">
                        <button type="button" onclick="toggleRiCategoryDropdown(event)" class="flex items-center gap-2 bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-700 hover:border-red-400 transition-all min-w-[180px]">
                            <span class="material-icons text-red-400 text-sm">category</span>
                            @if($filterCategory === 'ALL')
                                <span class="bg-slate-100 text-slate-600 border border-slate-300 rounded px-2 py-0.5 text-[10px] font-bold">ALL CATEGORIES</span>
                            @elseif($filterCategory === 'FINISH PART')
                                <span class="bg-emerald-50 text-emerald-700 border border-emerald-300 rounded px-2 py-0.5 text-[10px] font-bold">FINISH PART</span>
                            @else
                                <span class="bg-blue-50 text-blue-700 border border-blue-300 rounded px-2 py-0.5 text-[10px] font-bold">SINGLE PART</span>
                            @endif
                            <span class="material-icons text-slate-400 transition-transform text-base ml-auto" id="categoryArrow">expand_more</span>
                        </button>
                        <div class="absolute top-full left-0 right-0 mt-0 bg-white border border-red-400 border-t-0 rounded-b-xl shadow-lg z-[9999] overflow-visible" id="categoryDropdownContent">
                            <a href="{{ request()->fullUrlWithQuery(['category'=>'ALL', 'customer'=>null, 'page'=>null]) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 border-b border-slate-100 last:border-b-0 {{ $filterCategory==='ALL' ? 'bg-red-500 text-white hover:bg-red-600 hover:text-white' : '' }}">
                                <span class="material-icons text-sm text-slate-500">apps</span>
                                <span>ALL CATEGORIES</span>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['category'=>'SINGLE PART', 'customer'=>null, 'page'=>null]) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 border-b border-slate-100 last:border-b-0 {{ $filterCategory==='SINGLE PART' ? 'bg-red-500 text-white hover:bg-red-600 hover:text-white' : '' }}">
                                <span class="material-icons text-sm text-blue-600">label</span>
                                <span>SINGLE PART</span>
                                <span class="ml-auto text-[10px] text-slate-400">assy input</span>
                            </a>
                            <a href="{{ request()->fullUrlWithQuery(['category'=>'FINISH PART', 'customer'=>null, 'page'=>null]) }}" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-600 hover:bg-red-50 hover:text-red-600 border-b border-slate-100 last:border-b-0 {{ $filterCategory==='FINISH PART' ? 'bg-red-500 text-white hover:bg-red-600 hover:text-white' : '' }}">
                                <span class="material-icons text-sm text-emerald-600">label</span>
                                <span>FINISH PART</span>
                                <span class="ml-auto text-[10px] text-slate-400">order input</span>
                            </a>
                        </div>
                    </div>

                    <input type="hidden" name="vendor" value="{{ $filterVendor }}">
                    <input type="hidden" name="customer" value="{{ $filterCustomer }}">
                    <input type="hidden" name="category" value="{{ $filterCategory }}">
                    <input type="hidden" name="sort" value="{{ $sortBy }}">
                    <input type="hidden" name="dir" value="{{ $sortDir }}">
                </div>
            </form>

            <div class="hidden">
                <form action="{{ route('rundown_incoming.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <input type="file" name="excel_file" id="sp_excel_input" accept=".xlsx,.xls,.xlsm" onchange="this.form.submit()">
                </form>
            </div>
        </div>

        {{-- Legend --}}
        <div class="px-5 py-2.5 bg-slate-50 border-b border-slate-100 flex items-center gap-4 flex-wrap text-[11px] text-slate-500">
            <span class="font-bold text-slate-400">KETERANGAN INPUT:</span>
            <span class="flex items-center gap-1.5">
                <span class="bg-blue-50 text-blue-700 border border-blue-300 rounded px-2 py-0.5 text-[9px] font-bold">SINGLE PART</span>
                → Input <strong>ASSY</strong> yang aktif
            </span>
            <span class="flex items-center gap-1.5">
                <span class="bg-emerald-50 text-emerald-700 border border-emerald-300 rounded px-2 py-0.5 text-[9px] font-bold">FINISH PART</span>
                → Input customer order (IAMI/GKD/SAP/KAP/GMO) aktif sesuai <strong>Customer</strong>
            </span>
            <span class="flex items-center gap-1.5 text-slate-300">
                <span class="inline-block w-6 h-3.5 bg-slate-50 border border-dashed border-slate-300 rounded"></span> = Read-only (tidak aktif)
            </span>
            <span class="flex items-center gap-1.5">
                <span class="inline-block w-6 h-3.5 bg-white border-2 border-emerald-600 rounded"></span> = <span class="text-emerald-700 font-bold">Input aktif Finish Part</span>
            </span>
        </div>

        {{-- Top Scrollbar --}}
        <div class="top-scrollbar w-full overflow-x-auto overflow-y-hidden h-4 mb-1" id="topScrollbar">
            <div class="h-px" id="topScrollbarDummy"></div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto table-wrap" id="tableWrap">
            @if($items->isEmpty())
            <div class="flex flex-col items-center justify-center py-16 text-slate-400">
                <svg class="w-12 h-12 text-slate-200 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <p class="text-sm font-semibold">Tidak ada data yang cocok</p>
            </div>
            @else
            <table class="w-full text-[11px] min-w-[1500px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-3 py-2.5 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap w-9">#</th>
                        <th class="px-3 py-2.5 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap"><a href="{{ request()->fullUrlWithQuery(['sort'=>'job_no','dir'=>$sortBy==='job_no'&&$sortDir==='asc'?'desc':'asc']) }}" class="inline-flex items-center gap-1 text-inherit no-underline">JOB NO @if($sortBy==='job_no')<span class="material-icons text-xs text-slate-400">{{$sortDir==='asc'?'arrow_upward':'arrow_downward'}}</span>@endif</a></th>
                        <th class="px-3 py-2.5 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">JOB NO FINISH</th>
                        <th class="px-3 py-2.5 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">TYPE PALLET</th>
                        <th class="px-3 py-2.5 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">KATEGORI</th>
                        <th class="px-3 py-2.5 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">CUSTOMER</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">PRICE/PC</th>
                        <th class="px-3 py-2.5 text-left font-black text-slate-500 uppercase tracking-wider whitespace-nowrap"><a href="{{ request()->fullUrlWithQuery(['sort'=>'vendor','dir'=>$sortBy==='vendor'&&$sortDir==='asc'?'desc':'asc']) }}" class="inline-flex items-center gap-1 text-inherit no-underline">VENDOR @if($sortBy==='vendor')<span class="material-icons text-xs text-slate-400">{{$sortDir==='asc'?'arrow_upward':'arrow_downward'}}</span>@endif</a></th>
                        <th class="px-3 py-2.5 text-center font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">STATUS</th>
                        <th class="px-3 py-2.5 text-center font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">MOVEMENT</th>
                        <th class="px-3 py-2.5 text-center font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">CYCLE ISSUE</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">STOCK AWAL ✏️</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">INCOMING ✏️</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap" title="Aktif untuk Single Part">ASSY ✏️</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap" title="Aktif untuk Finish Part: customer IAMI">IAMI ✏️</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap" title="Aktif untuk Finish Part: customer GKD">GKD ✏️</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap" title="Aktif untuk Finish Part: customer SAP">SAP ✏️</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap" title="Aktif untuk Finish Part: customer KAP">KAP ✏️</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap" title="Aktif untuk Finish Part: customer GMO/TMMIN/FTI">IKAR/TMMIN/FTI ✏️</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">STOK AKHIR</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">ALL PRICE</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">PCS/DAY ✏️</th>
                        <th class="px-3 py-2.5 text-right font-black text-slate-500 uppercase tracking-wider whitespace-nowrap">STRENGTH</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($items as $i => $item)
                    @php
                        $isSinglePart = strtoupper(trim($item->category ?? '')) !== 'FINISH PART';
                        $isFinishPart = !$isSinglePart;
                        $searchKey = strtoupper(trim($item->job_no));
                        $customerUpper = strtoupper(trim($item->customer ?? ''));
                        if (str_contains($customerUpper, 'KAP'))        $activeCustomerField = 'kap';
                        elseif (str_contains($customerUpper, 'SAP'))    $activeCustomerField = 'sap';
                        elseif (str_contains($customerUpper, 'IAMI'))   $activeCustomerField = 'iami';
                        elseif (str_contains($customerUpper, 'GKD'))    $activeCustomerField = 'gkd';
                        elseif (str_contains($customerUpper, 'GMO') || str_contains($customerUpper, 'TMMIN') || str_contains($customerUpper, 'FTI')) $activeCustomerField = 'gmo';
                        else $activeCustomerField = 'iami';
                    @endphp
                    <tr id="row-{{ $item->id }}" class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-3 py-2.5 text-slate-400">{{ ($items->currentPage()-1)*$perPage + $i + 1 }}</td>
                        <td class="px-3 py-2.5 font-black text-slate-800 whitespace-nowrap">{{ $item->job_no }}</td>
                        <td class="px-3 py-2.5 text-slate-500 whitespace-nowrap">
                            @if($isFinishPart && $relatedSingleParts->has($searchKey))
                                <div onclick="toggleSingleParts('{{ $item->id }}')" class="cursor-pointer inline-flex items-center gap-1 font-bold text-slate-700">
                                    <span>{{ $item->job_no_finish ?: count($relatedSingleParts[$searchKey]) . ' Parts' }}</span>
                                    <span class="material-icons text-sm text-red-500 bg-red-100 rounded-full" id="icon-toggle-{{ $item->id }}">expand_more</span>
                                </div>
                            @else
                                {{ $item->job_no_finish }}
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-slate-500 whitespace-nowrap">{{ $item->type_pallet }}</td>
                        <td class="px-3 py-2.5 whitespace-nowrap">
                            @if($isFinishPart)
                                <span class="bg-emerald-50 text-emerald-700 border border-emerald-300 rounded-lg px-2 py-1 text-[10px] font-bold">FINISH PART</span>
                            @else
                                <span class="bg-blue-50 text-blue-700 border border-blue-300 rounded-lg px-2 py-1 text-[10px] font-bold">SINGLE PART</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-slate-600 whitespace-nowrap">{{ $item->customer }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            <input type="number" class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs font-semibold text-right w-24 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400 price-input-{{ $item->id }}" data-id="{{ $item->id }}" data-field="price_pc" value="{{ number_format($item->price_pc, 0, '.', '') }}" onchange="saveInline(this)" step="1">
                        </td>
                        <td class="px-3 py-2.5 font-bold text-slate-600 whitespace-nowrap">{{ $item->vendor }}</td>
                        <td class="px-3 py-2.5 text-center whitespace-nowrap" id="status-{{ $item->id }}">
                            @php $stClass = strtolower($item->status ?: 'standar'); @endphp
                            @if($stClass === 'over')
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold text-white bg-blue-500">{{ $item->status }}</span>
                            @elseif($stClass === 'standar')
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold text-white bg-emerald-500">{{ $item->status }}</span>
                            @else
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold text-white bg-red-500">{{ $item->status }}</span>
                            @endif
                        </td>
                        <td class="px-3 py-2.5 text-center whitespace-nowrap">
                            <span class="bg-slate-100 text-slate-600 border border-slate-300 rounded-lg px-2 py-1 text-[10px] font-bold">{{ $item->movement }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-center whitespace-nowrap">
                            <input type="number" class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs font-bold text-center w-14 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $item->id }}" data-field="cycle_issue" value="{{ $item->cycle_issue }}" onchange="saveInline(this)">
                        </td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            <input type="number" class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs font-semibold text-right w-24 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $item->id }}" data-field="stock_awal" value="{{ number_format($item->stock_awal, 0, '.', '') }}" onchange="saveInline(this)">
                        </td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            <input type="number" class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs font-semibold text-right w-24 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $item->id }}" data-field="incoming" value="{{ number_format($item->incoming, 0, '.', '') }}" onchange="saveInline(this)">
                        </td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            @if($isSinglePart)
                                <input type="number" class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs font-semibold text-right w-24 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $item->id }}" data-field="assy" value="{{ number_format($item->assy, 0, '.', '') }}" onchange="saveInline(this)">
                            @else
                                <span class="inline-block w-24 text-right text-xs font-semibold text-slate-400 bg-slate-50 border border-dashed border-slate-200 rounded-lg px-2 py-1.5">{{ number_format($item->assy, 0, ',', '.') }}</span>
                            @endif
                        </td>
                        @foreach(['iami','gkd','sap','kap','gmo'] as $coField)
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            @if($isFinishPart && $activeCustomerField === $coField)
                                <input type="number" class="border-2 border-emerald-600 rounded-lg px-2 py-1.5 text-xs font-semibold text-right w-24 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400" data-id="{{ $item->id }}" data-field="{{ $coField }}" value="{{ number_format($item->$coField, 0, '.', '') }}" onchange="saveInline(this)">
                            @elseif($isSinglePart)
                                <span class="inline-block w-24 text-right text-xs font-semibold text-slate-400 bg-slate-50 border border-dashed border-slate-200 rounded-lg px-2 py-1.5">{{ number_format($item->$coField, 0, ',', '.') }}</span>
                            @else
                                <span class="inline-block w-24 text-right text-xs font-semibold text-slate-300 bg-slate-50 border border-dashed border-slate-200 rounded-lg px-2 py-1.5">{{ number_format($item->$coField, 0, ',', '.') }}</span>
                            @endif
                        </td>
                        @endforeach
                        <td class="px-3 py-2.5 text-right font-bold text-xs whitespace-nowrap {{ $item->stok_akhir < 0 ? 'text-red-500' : 'text-slate-700' }}" id="stok-{{ $item->id }}">{{ number_format($item->stok_akhir, 0, ',', '.') }}</td>
                        <td class="px-3 py-2.5 text-right font-bold text-xs whitespace-nowrap" id="allprice-{{ $item->id }}">Rp {{ number_format($item->all_price, 0, ',', '.') }}</td>
                        <td class="px-3 py-2.5 text-right whitespace-nowrap">
                            <input type="number" class="border border-slate-200 rounded-lg px-2 py-1.5 text-xs font-semibold text-right w-24 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $item->id }}" data-field="pcs_day" value="{{ number_format($item->pcs_day, 0, '.', '') }}" step="0.01" onchange="saveInline(this)">
                        </td>
                        <td class="px-3 py-2.5 text-right font-bold text-xs whitespace-nowrap {{ $item->strength < 2 ? 'text-red-500' : ($item->strength >= 5 ? 'text-blue-500' : 'text-emerald-500') }}" id="str-{{ $item->id }}">{{ number_format($item->strength, 2) }}</td>
                    </tr>

                    {{-- Collapsible Single Parts Sub-row --}}
                    @if($isFinishPart && $relatedSingleParts->has($searchKey))
                    <tr id="sp-row-{{ $item->id }}" class="hidden bg-slate-50">
                        <td colspan="23" class="px-6 py-3 border-b border-slate-200">
                            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm overflow-x-auto">
                                <div class="text-xs font-black text-slate-700 mb-3 flex items-center gap-2 uppercase tracking-wider">
                                    <span class="material-icons text-sm text-red-500">subdirectory_arrow_right</span>
                                    Komponen Single Part untuk {{ $item->job_no }}
                                </div>
                                <table class="w-full min-w-[1500px] border border-slate-100 rounded-lg overflow-hidden text-[10px]">
                                    <thead>
                                        <tr class="bg-slate-50 border-b border-slate-200">
                                            <th class="px-2.5 py-2 text-left font-bold text-slate-500 w-9">#</th>
                                            <th class="px-2.5 py-2 text-left font-bold text-slate-500 whitespace-nowrap">JOB NO</th>
                                            <th class="px-2.5 py-2 text-left font-bold text-slate-500 whitespace-nowrap">JOB NO FINISH</th>
                                            <th class="px-2.5 py-2 text-left font-bold text-slate-500 whitespace-nowrap">TYPE PALLET</th>
                                            <th class="px-2.5 py-2 text-left font-bold text-slate-500 whitespace-nowrap">KATEGORI</th>
                                            <th class="px-2.5 py-2 text-left font-bold text-slate-500 whitespace-nowrap">CUSTOMER</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">PRICE/PC</th>
                                            <th class="px-2.5 py-2 text-left font-bold text-slate-500 whitespace-nowrap">VENDOR</th>
                                            <th class="px-2.5 py-2 text-center font-bold text-slate-500 whitespace-nowrap">STATUS</th>
                                            <th class="px-2.5 py-2 text-center font-bold text-slate-500 whitespace-nowrap">MOVEMENT</th>
                                            <th class="px-2.5 py-2 text-center font-bold text-slate-500 whitespace-nowrap">CYCLE ISSUE</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">STOCK AWAL ✏️</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">INCOMING ✏️</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">ASSY ✏️</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">IAMI</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">GKD</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">SAP</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">KAP</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">IKAR/TMMIN/FTI</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">STOK AKHIR</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">ALL PRICE</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">PCS/DAY ✏️</th>
                                            <th class="px-2.5 py-2 text-right font-bold text-slate-500 whitespace-nowrap">STRENGTH</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($relatedSingleParts[$searchKey] as $spIdx => $sp)
                                        <tr class="bg-white hover:bg-slate-50/50 transition-colors">
                                            <td class="px-2.5 py-2 text-slate-400 text-[11px]">{{ (($items->currentPage()-1)*$perPage + $i + 1) . '.' . ($spIdx + 1) }}</td>
                                            <td class="px-2.5 py-2 font-bold text-slate-800 text-[11px] whitespace-nowrap">{{ $sp->job_no }}</td>
                                            <td class="px-2.5 py-2 text-slate-500 text-[11px] whitespace-nowrap">{{ $sp->job_no_finish }}</td>
                                            <td class="px-2.5 py-2 text-slate-500 text-[11px] whitespace-nowrap">{{ $sp->type_pallet ?: '-' }}</td>
                                            <td class="px-2.5 py-2 whitespace-nowrap">
                                                <span class="bg-blue-50 text-blue-700 border border-blue-300 rounded-lg px-2 py-0.5 text-[9px] font-bold">SINGLE PART</span>
                                            </td>
                                            <td class="px-2.5 py-2 text-slate-500 text-[11px] whitespace-nowrap">{{ $sp->customer ?: '-' }}</td>
                                            <td class="px-2.5 py-2 text-right whitespace-nowrap">
                                                <input type="number" class="border border-slate-200 rounded-lg px-2 py-1 text-xs font-semibold text-right w-20 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $sp->id }}" data-field="price_pc" value="{{ number_format($sp->price_pc, 0, '.', '') }}" onchange="saveInline(this)" step="1">
                                            </td>
                                            <td class="px-2.5 py-2 text-slate-500 text-[11px] font-semibold whitespace-nowrap">{{ $sp->vendor }}</td>
                                            <td class="px-2.5 py-2 text-center whitespace-nowrap" id="status-{{ $sp->id }}">
                                                @php $spStClass = strtolower($sp->status ?: 'standar'); @endphp
                                                @if($spStClass === 'over')
                                                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold text-white bg-blue-500">{{ $sp->status }}</span>
                                                @elseif($spStClass === 'standar')
                                                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold text-white bg-emerald-500">{{ $sp->status }}</span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-bold text-white bg-red-500">{{ $sp->status }}</span>
                                                @endif
                                            </td>
                                            <td class="px-2.5 py-2 text-center whitespace-nowrap">
                                                <span class="bg-slate-100 text-slate-600 border border-slate-300 rounded px-2 py-0.5 text-[9px] font-bold">{{ $sp->movement }}</span>
                                            </td>
                                            <td class="px-2.5 py-2 text-center whitespace-nowrap">
                                                <input type="number" class="border border-slate-200 rounded-lg px-2 py-1 text-xs font-bold text-center w-12 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $sp->id }}" data-field="cycle_issue" value="{{ $sp->cycle_issue }}" onchange="saveInline(this)">
                                            </td>
                                            <td class="px-2.5 py-2 text-right whitespace-nowrap">
                                                <input type="number" class="border border-slate-200 rounded-lg px-2 py-1 text-xs font-semibold text-right w-20 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $sp->id }}" data-field="stock_awal" value="{{ number_format($sp->stock_awal, 0, '.', '') }}" onchange="saveInline(this)">
                                            </td>
                                            <td class="px-2.5 py-2 text-right whitespace-nowrap">
                                                <input type="number" class="border border-slate-200 rounded-lg px-2 py-1 text-xs font-semibold text-right w-20 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $sp->id }}" data-field="incoming" value="{{ number_format($sp->incoming, 0, '.', '') }}" onchange="saveInline(this)">
                                            </td>
                                            <td class="px-2.5 py-2 text-right whitespace-nowrap">
                                                <input type="number" class="border border-slate-200 rounded-lg px-2 py-1 text-xs font-semibold text-right w-20 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $sp->id }}" data-field="assy" value="{{ number_format($sp->assy, 0, '.', '') }}" onchange="saveInline(this)">
                                            </td>
                                            @foreach(['iami','gkd','sap','kap','gmo'] as $coField)
                                            <td class="px-2.5 py-2 text-right whitespace-nowrap">
                                                <span class="inline-block w-20 text-right text-xs font-semibold text-slate-400 bg-slate-50 border border-dashed border-slate-200 rounded-lg px-2 py-1">{{ number_format($sp->$coField, 0, ',', '.') }}</span>
                                            </td>
                                            @endforeach
                                            <td class="px-2.5 py-2 text-right font-bold text-[11px] whitespace-nowrap {{ $sp->stok_akhir < 0 ? 'text-red-500' : 'text-slate-700' }}" id="stok-{{ $sp->id }}">{{ number_format($sp->stok_akhir, 0, ',', '.') }}</td>
                                            <td class="px-2.5 py-2 text-right font-bold text-[11px] whitespace-nowrap" id="allprice-{{ $sp->id }}">Rp {{ number_format($sp->all_price, 0, ',', '.') }}</td>
                                            <td class="px-2.5 py-2 text-right whitespace-nowrap">
                                                <input type="number" class="border border-slate-200 rounded-lg px-2 py-1 text-xs font-semibold text-right w-20 focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400" data-id="{{ $sp->id }}" data-field="pcs_day" value="{{ number_format($sp->pcs_day, 0, '.', '') }}" step="0.01" onchange="saveInline(this)">
                                            </td>
                                            <td class="px-2.5 py-2 text-right font-bold text-[11px] whitespace-nowrap {{ $sp->strength < 2 ? 'text-red-500' : ($sp->strength >= 5 ? 'text-blue-500' : 'text-emerald-500') }}" id="str-{{ $sp->id }}">{{ number_format($sp->strength, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
            <div class="text-xs text-slate-500">
                @if($items instanceof \Illuminate\Pagination\LengthAwarePaginator)
                Menampilkan <strong class="text-slate-700">{{ $items->firstItem() }}</strong> hingga <strong class="text-slate-700">{{ $items->lastItem() }}</strong> dari <strong class="text-slate-700">{{ $items->total() }}</strong>
                @endif
            </div>
            @if($items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->hasPages())
                {{ $items->links('pagination::tailwind') }}
            @endif
        </div>
    </div>
    @endif
@endsection

@push('modals')
    {{-- MODAL ADD JOB --}}
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto p-5 hidden" id="riAddJobModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl">
            <h3 class="text-base font-black text-slate-800 mb-5 flex items-center gap-2">
                <span class="material-icons text-red-600 text-xl">add_circle</span> Add New Job No
            </h3>
            <form action="{{ route('rundown_incoming.add') }}" method="POST">
                @csrf
                <input type="hidden" name="sheet_date" value="{{ $selectedSheet }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Job No <span class="text-red-500">*</span></label>
                        <input type="text" name="job_no" required placeholder="e.g. JOB001" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Vendor <span class="text-red-500">*</span></label>
                        <input type="text" name="vendor" required placeholder="e.g. VENDOR A" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Job No Finish</label>
                        <input type="text" name="job_no_finish" placeholder="e.g. SC-0743" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Type Pallet</label>
                        <input type="text" name="type_pallet" placeholder="e.g. TP-332" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kategori</label>
                        <select name="category" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                            <option value="SINGLE PART">SINGLE PART</option>
                            <option value="FINISH PART">FINISH PART</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Customer</label>
                        <input type="text" name="customer" placeholder="e.g. IAMI" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Stock Awal</label>
                        <input type="number" name="stock_awal" value="0" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pcs/Day <span class="text-red-500">*</span></label>
                        <input type="number" name="pcs_day" step="0.01" required value="1" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400">
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeRiAddJob()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold transition-colors">Batal</button>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors">Simpan Job</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL ADD INCOMING --}}
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto p-5 hidden" id="riIncomingModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <h3 class="text-base font-black text-slate-800 mb-5 flex items-center gap-2">
                <span class="material-icons text-slate-700 text-xl">add_shopping_cart</span> Add Incoming Stock
            </h3>
            <form action="{{ route('rundown_incoming.add_incoming') }}" method="POST">
                @csrf
                <input type="hidden" name="sheet_date" value="{{ $selectedSheet }}">
                <div class="flex flex-col gap-1.5 mb-4">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Job Number <span class="text-red-500">*</span></label>
                    <input type="text" name="job_no" id="jobNoIncoming" required placeholder="Masukkan Job Number" class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400 w-full">
                </div>
                <div class="flex flex-col gap-1.5 mb-5">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Incoming Qty <span class="text-red-500">*</span></label>
                    <input type="number" name="incoming" required value="0" class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400 w-full">
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeRiIncoming()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold transition-colors">Batal</button>
                    <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors">Simpan Incoming</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EXPORT --}}
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto p-5 hidden" id="riExportModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <h3 class="text-base font-black text-slate-800 mb-5 flex items-center gap-2">
                <span class="material-icons text-emerald-500 text-xl">file_download</span> Export Data Excel
            </h3>
            <p class="text-xs text-slate-500 mb-5">Pilih bulan dan tahun data yang ingin Anda export ke format Excel.</p>
            <form action="{{ route('rundown_incoming.export') }}" method="GET">
                <div class="flex flex-col gap-1.5 mb-4">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pilih Bulan <span class="text-red-500">*</span></label>
                    <select name="month" required class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400 w-full">
                        @php
                            $months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
                            $mIdx = (int)now()->format('m') - 1;
                        @endphp
                        @foreach($months as $idx => $m)
                            <option value="{{ $m }}" {{ $idx === $mIdx ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5 mb-5">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pilih Tahun <span class="text-red-500">*</span></label>
                    <select name="year" required class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400 w-full">
                        @for($y = date('Y'); $y >= 2024; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeRiExport()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold transition-colors">Batal</button>
                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors">Download Excel</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL DELETE JOB --}}
    <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto p-5 hidden" id="riDeleteModal">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm shadow-2xl">
            <h3 class="text-base font-black text-slate-800 mb-5 flex items-center gap-2">
                <span class="material-icons text-red-500 text-xl">delete_forever</span> Hapus Job No
            </h3>
            <p class="text-xs text-slate-500 mb-5">Anda akan menghapus Job No dari tanggal <strong class="text-slate-700">{{ $selectedSheet }}</strong>. Tindakan ini tidak dapat dibatalkan.</p>
            <form action="{{ route('rundown_incoming.delete') }}" method="POST">
                @csrf
                @method('DELETE')
                <input type="hidden" name="sheet_date" value="{{ $selectedSheet }}">
                <div class="flex flex-col gap-1.5 mb-5">
                    <label class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Masukkan Job No <span class="text-red-500">*</span></label>
                    <input type="text" name="job_no" required placeholder="e.g. JOB001" class="border border-slate-200 rounded-xl px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-red-400 focus:border-red-400 w-full">
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeRiDelete()" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold transition-colors">Batal</button>
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors">Hapus Sekarang</button>
                </div>
            </form>
        </div>
    </div>
@endpush

@push('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';

    function openRiAddJob() { document.getElementById('riAddJobModal').classList.remove('hidden'); }
    function closeRiAddJob() { document.getElementById('riAddJobModal').classList.add('hidden'); }

    function openRiIncoming() {
        document.getElementById('riIncomingModal').classList.remove('hidden');
        setTimeout(() => {
            const el = document.getElementById('jobNoIncoming');
            if (el) el.focus();
        }, 100);
    }
    function closeRiIncoming() { document.getElementById('riIncomingModal').classList.add('hidden'); }

    function openRiExport() { document.getElementById('riExportModal').classList.remove('hidden'); }
    function closeRiExport() { document.getElementById('riExportModal').classList.add('hidden'); }

    function openRiDelete() { document.getElementById('riDeleteModal').classList.remove('hidden'); }
    function closeRiDelete() { document.getElementById('riDeleteModal').classList.add('hidden'); }

    document.addEventListener('click', function(e) {
        ['riAddJobModal', 'riIncomingModal', 'riExportModal', 'riDeleteModal'].forEach(id => {
            const el = document.getElementById(id);
            if (e.target === el) {
                el.classList.add('hidden');
            }
        });
    });

    function toggleSingleParts(id) {
        const row = document.getElementById('sp-row-' + id);
        const icon = document.getElementById('icon-toggle-' + id);
        if (row.classList.contains('hidden')) {
            row.classList.remove('hidden');
            icon.textContent = 'expand_less';
            icon.className = 'material-icons text-sm text-white bg-red-500 rounded-full';
        } else {
            row.classList.add('hidden');
            icon.textContent = 'expand_more';
            icon.className = 'material-icons text-sm text-red-500 bg-red-100 rounded-full';
        }
    }

    function toggleRiVendorDropdown(e) {
        if (e) e.stopPropagation();
        document.getElementById('vendorDropdownContent').classList.toggle('hidden');
        closeRiCustomerDropdown();
        closeRiCategoryDropdown();
    }

    function toggleRiCustomerDropdown(e) {
        if (e) e.stopPropagation();
        document.getElementById('customerDropdownContent').classList.toggle('hidden');
        closeRiVendorDropdown();
        closeRiCategoryDropdown();
    }

    function toggleRiCategoryDropdown(e) {
        if (e) e.stopPropagation();
        document.getElementById('categoryDropdownContent').classList.toggle('hidden');
        closeRiVendorDropdown();
        closeRiCustomerDropdown();
    }

    function closeRiVendorDropdown() { document.getElementById('vendorDropdownContent').classList.add('hidden'); }
    function closeRiCustomerDropdown() { document.getElementById('customerDropdownContent').classList.add('hidden'); }
    function closeRiCategoryDropdown() { document.getElementById('categoryDropdownContent').classList.add('hidden'); }

    document.addEventListener('click', function(e) {
        const v = document.getElementById('vendorDropdownContainer');
        const c = document.getElementById('customerDropdownContainer');
        const cat = document.getElementById('categoryDropdownContainer');
        if (v && !v.contains(e.target)) closeRiVendorDropdown();
        if (c && !c.contains(e.target)) closeRiCustomerDropdown();
        if (cat && !cat.contains(e.target)) closeRiCategoryDropdown();
    });

    document.addEventListener('DOMContentLoaded', function() {
        const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
        const selected = "{{ $selectedSheet }}";
        if (selected) {
            const parts = selected.split(' ');
            if (parts.length >= 2) {
                const d = parseInt(parts[0]);
                const mStr = parts[1].toUpperCase();
                const mIndex = months.indexOf(mStr);
                if (mIndex >= 0) {
                    const m = (mIndex + 1).toString().padStart(2, '0');
                    const day = d.toString().padStart(2, '0');
                    document.getElementById('calendarInput').value = new Date().getFullYear() + '-' + m + '-' + day;
                }
            }
        }

        const tableWrap = document.getElementById('tableWrap');
        const topScrollbar = document.getElementById('topScrollbar');
        const topScrollbarDummy = document.getElementById('topScrollbarDummy');
        if (tableWrap && topScrollbar && topScrollbarDummy) {
            const table = tableWrap.querySelector('table');
            if (table) {
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

    function convertAndSubmitDate(val) {
        if (!val) return;
        const d = new Date(val);
        const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
        const day = d.getDate().toString().padStart(2, '0');
        const sheetName = day + ' ' + months[d.getMonth()];
        document.getElementById('sheetHidden').value = sheetName;
        document.getElementById('toolbarForm').submit();
    }

    function formatRp(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(num));
    }
    function formatNum(num) {
        return new Intl.NumberFormat('id-ID').format(Math.round(num));
    }

    function saveInline(input) {
        var id    = input.dataset.id;
        var field = input.dataset.field;
        var val   = parseFloat(input.value) || 0;

        input.classList.add('bg-amber-50', 'border-amber-400');

        fetch('{{ route("rundown_incoming.inline") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ id: id, field: field, value: val })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                input.classList.remove('bg-amber-50', 'border-amber-400');
                input.classList.add('bg-emerald-50', 'border-emerald-500');
                setTimeout(() => input.classList.remove('bg-emerald-50', 'border-emerald-500'), 1500);

                document.getElementById('stok-'+id).textContent = formatNum(data.stok_akhir);
                document.getElementById('stok-'+id).className = 'px-3 py-2.5 text-right font-bold text-xs whitespace-nowrap ' + (data.stok_akhir < 0 ? 'text-red-500' : 'text-slate-700');

                document.getElementById('allprice-'+id).textContent = formatRp(data.all_price);

                var strCell = document.getElementById('str-'+id);
                strCell.textContent = new Intl.NumberFormat('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}).format(data.strength);
                strCell.className = 'px-3 py-2.5 text-right font-bold text-xs whitespace-nowrap ' + (data.strength < 2 ? 'text-red-500' : (data.strength >= 5 ? 'text-blue-500' : 'text-emerald-500'));

                var statusBadge = document.getElementById('status-'+id).querySelector('span');
                if (statusBadge) {
                    statusBadge.textContent = data.status;
                    var st = data.status.toLowerCase();
                    statusBadge.className = 'px-2.5 py-1 rounded-lg text-[10px] font-bold text-white ' + (st === 'over' ? 'bg-blue-500' : (st === 'standar' ? 'bg-emerald-500' : 'bg-red-500'));
                }

                if (field === 'price_pc' && data.price_pc !== undefined) {
                    input.value = data.price_pc;
                }

                if (data.parent) {
                    var pId = data.parent.id;
                    var pInput = document.querySelector('.price-input-' + pId);
                    if (pInput) pInput.value = data.parent.price_pc;
                    var pAllPrice = document.getElementById('allprice-' + pId);
                    if (pAllPrice) pAllPrice.textContent = formatRp(data.parent.all_price);
                }
            }
        })
        .catch(err => {
            input.classList.remove('bg-amber-50', 'border-amber-400');
            alert('Gagal menyimpan!');
        });
    }
</script>
@endpush

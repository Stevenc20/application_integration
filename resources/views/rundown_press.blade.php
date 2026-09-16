@extends('layouts.app')

@section('title', 'Simulasi Press')

@push('styles')
<style>
.top-scrollbar { width: 100%; overflow-x: auto; overflow-y: hidden; height: 18px; margin-bottom: 4px; }
.top-scrollbar-dummy { height: 1px; }
.top-scrollbar::-webkit-scrollbar { height: 16px; }
.top-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.top-scrollbar::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
.top-scrollbar::-webkit-scrollbar-thumb:hover { background: #333; }

.table-wrap { overflow-x: auto; }
.table-wrap::-webkit-scrollbar { height: 16px; }
.table-wrap::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
.table-wrap::-webkit-scrollbar-thumb { background: #000; border-radius: 10px; border: 2px solid #f1f1f1; }
.table-wrap::-webkit-scrollbar-thumb:hover { background: #333; }

.inline-input { width: 80px; border: 1.5px solid #e2e8f0; border-radius: 6px; padding: 4px 7px; font-size: 11px; font-weight: 600; text-align: right; outline: none; transition: all .15s; }
.inline-input:focus { border-color: #f43f5e; box-shadow: 0 0 0 3px rgba(244,63,94,0.1); }
.inline-input.saving { background: #fffbeb; border-color: #f59e0b; }
.inline-input.saved { border-color: #22c55e; background: #f0fdf4; }

.date-dropdown-container { position: relative; z-index: 160; display: inline-block; min-width: 160px; }
.date-dropdown-content { position: absolute; top: 100%; left: 0; background: white; border: 1px solid #f43f5e; border-top: none; border-radius: 0 0 8px 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.12); max-height: 320px; overflow-y: auto; z-index: 161; display: none; box-sizing: border-box; min-width: 200px; }
.date-dropdown-container.active .date-dropdown-content { display: block; }

.vendor-dropdown-container { position: relative; z-index: 150; display: inline-block; min-width: 160px; }
.vendor-dropdown-content { position: absolute; top: 100%; left: 0; background: white; border: 1px solid #f43f5e; border-top: none; border-radius: 0 0 8px 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); max-height: 280px; overflow-y: auto; z-index: 151; display: none; box-sizing: border-box; min-width: 160px; }
.vendor-dropdown-container.active .vendor-dropdown-content { display: block; }
</style>
@endpush

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-600 p-4 rounded-xl flex items-center gap-3 border border-emerald-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
        <span class="text-sm font-semibold">{{ session('error') }}</span>
    </div>
    @endif

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-white tracking-tight">Simulasi Press</h1>
                    <p class="text-rose-200 text-sm font-semibold mt-1">Inventory simulasi press harian per vendor &mdash; {{ $selectedSheet }}</p>
                </div>
            </div>

            <div class="flex gap-3 overflow-x-auto pb-2 md:pb-0">
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 ring-1 ring-white/20 text-center flex-shrink-0 border-b-2 border-blue-400">
                    <p class="text-[10px] font-black text-rose-200 uppercase tracking-widest">OVER STOCK</p>
                    <p class="text-white font-bold text-xl">{{ $countOver }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 ring-1 ring-white/20 text-center flex-shrink-0 border-b-2 border-emerald-400">
                    <p class="text-[10px] font-black text-rose-200 uppercase tracking-widest">STANDAR</p>
                    <p class="text-white font-bold text-xl">{{ $countStandar }}</p>
                </div>
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-5 py-3 ring-1 ring-white/20 text-center flex-shrink-0 border-b-2 border-red-400">
                    <p class="text-[10px] font-black text-rose-200 uppercase tracking-widest">CRITICAL</p>
                    <p class="text-white font-bold text-xl">{{ $countMinim }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
        <div class="px-6 py-4 border-b border-slate-100 bg-white">
            <form action="{{ route('rundown_press.index') }}" method="GET" id="toolbarForm" class="w-full">
                <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-3 flex-wrap">
                        {{-- Search Box --}}
                        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 w-full max-w-[220px] focus-within:border-rose-400 focus-within:ring-2 focus-within:ring-rose-100 transition-all">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Cari Job No, Vendor..." class="bg-transparent border-none outline-none text-sm w-full font-medium text-slate-700 placeholder-slate-400" onchange="document.getElementById('toolbarForm').submit()">
                        </div>

                        <a href="{{ route('rundown_press.index', ['sheet' => $selectedSheet]) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2 px-4 rounded-xl transition-all text-sm h-[38px] flex items-center">Reset</a>

                        {{-- Date Dropdown --}}
                        <input type="hidden" name="sheet" id="sheetHidden" value="{{ $selectedSheet }}">
                        <div class="date-dropdown-container" id="dateDropdownContainer">
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-medium text-slate-600 hover:border-rose-400 transition-all cursor-pointer flex items-center gap-2 h-[38px]" onclick="toggleDateDropdown(event)">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span id="dateDropdownLabel">{{ $selectedSheet ?: 'Pilih Tanggal' }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            <div class="date-dropdown-content">
                                <div class="p-3 bg-slate-50 border-b border-slate-100">
                                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pilih dari Kalender:</div>
                                    <input type="date" id="calendarInput" class="w-full border border-slate-200 rounded-lg p-1.5 text-xs font-medium text-slate-700 outline-none focus:border-rose-400 focus:ring-1 focus:ring-rose-400" onclick="event.stopPropagation()" onchange="submitCalendarDate(this.value)">
                                </div>
                                @if(isset($availableSheets) && count($availableSheets) > 0)
                                    <div class="max-h-[200px] overflow-y-auto">
                                        @foreach($availableSheets as $sh)
                                            <a href="{{ request()->fullUrlWithQuery(['sheet' => $sh, 'page' => null]) }}" class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium border-b border-slate-50 transition-colors {{ $selectedSheet === $sh ? 'bg-rose-50 text-rose-600 font-bold' : 'text-slate-600 hover:bg-slate-50' }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 {{ $selectedSheet === $sh ? 'text-rose-500' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                                {{ $sh }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Vendor Dropdown --}}
                        <div class="vendor-dropdown-container" id="vendorDropdownContainer">
                            <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm font-medium text-slate-600 hover:border-rose-400 transition-all cursor-pointer flex items-center gap-2 h-[38px]" onclick="toggleVendorDropdown(event)">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1" /></svg>
                                <span>{{ $filterVendor ?: 'Semua Vendor' }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto text-slate-400 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </div>
                            <div class="vendor-dropdown-content">
                                <a href="{{ request()->fullUrlWithQuery(['vendor' => null, 'page' => null]) }}" class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium border-b border-slate-50 transition-colors {{ $filterVendor === '' ? 'bg-rose-50 text-rose-600 font-bold' : 'text-slate-600 hover:bg-slate-50' }}">
                                    Semua Vendor
                                </a>
                                @foreach($allVendors as $v)
                                    <a href="{{ request()->fullUrlWithQuery(['vendor' => $v, 'page' => null]) }}" class="flex items-center gap-2 px-4 py-2.5 text-xs font-medium border-b border-slate-50 transition-colors {{ $filterVendor === $v ? 'bg-rose-50 text-rose-600 font-bold' : 'text-slate-600 hover:bg-slate-50' }}">
                                        {{ $v }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <input type="hidden" name="vendor" value="{{ $filterVendor }}">
                        <input type="hidden" name="sort" value="{{ $sortBy }}">
                        <input type="hidden" name="dir" value="{{ $sortDir }}">
                    </div>

                    {{-- Right Actions --}}
                    <div class="flex items-center gap-3">
                        <label for="press_excel_input" class="bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-4 rounded-xl transition-all flex items-center gap-2 text-sm shadow-sm cursor-pointer h-[38px]">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
                            Upload Excel
                        </label>

                        @if($hasData && $selectedSheet)
                        <button type="button" id="syncStampingBtn" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-xl transition-all flex items-center gap-2 text-sm shadow-sm h-[38px]" onclick="syncStokToStamping('{{ $selectedSheet }}')">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                            Sync ke Stamping
                        </button>
                        @endif
                    </div>
                </div>
            </form>
            <form action="{{ route('rundown_press.upload') }}" method="POST" enctype="multipart/form-data" id="uploadPressForm" class="hidden">
                @csrf
                <input type="file" name="excel_file" id="press_excel_input" accept=".xlsx,.xls,.xlsm" onchange="this.form.submit()">
            </form>
        </div>

        @if(!$hasData)
        <div class="py-16 text-center bg-slate-50">
            <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-200 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" /></svg>
            </div>
            <h3 class="text-lg font-black text-slate-700 tracking-tight mb-2">Belum ada data Simulasi Press</h3>
            <p class="text-sm text-slate-500 font-medium">Upload file Excel yang berisi sheet per tanggal<br>(contoh nama sheet: "01 MEI", "02 MEI", dst.)</p>
        </div>
        @else
            <div class="top-scrollbar px-6 mt-4" id="topScrollbar">
                <div class="top-scrollbar-dummy" id="topScrollbarDummy"></div>
            </div>

            <div class="table-wrap px-6 pb-6" id="tableWrap">
                @if($items->isEmpty())
                <div class="py-12 text-center border-t border-slate-100 mt-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-slate-200 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <p class="text-slate-500 font-medium text-sm">Tidak ada data untuk tanggal <strong>{{ $selectedSheet }}</strong></p>
                </div>
                @else
                <table class="w-full text-left border-collapse min-w-[1800px]">
                    <thead>
                        <tr class="bg-slate-50 border-y border-slate-200">
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap rounded-tl-xl w-12 text-center">#</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">JOB NO (SCHEDULE)</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">JOB NO (STAMPING)</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap">MAKER / VENDOR</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-center">STATUS</th>
                            <th class="py-3 px-4 text-[10px] font-black text-rose-500 uppercase tracking-widest whitespace-nowrap text-right">S. AWAL ✏️</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">QTY/KBN</th>
                            <th class="py-3 px-4 text-[10px] font-black text-rose-500 uppercase tracking-widest whitespace-nowrap text-right">MDFO (INC) ✏️</th>
                            <th class="py-3 px-4 text-[10px] font-black text-rose-500 uppercase tracking-widest whitespace-nowrap text-right">ORDER ✏️</th>
                            <th class="py-3 px-4 text-[10px] font-black text-rose-500 uppercase tracking-widest whitespace-nowrap text-right">PLAN DAY ✏️</th>
                            <th class="py-3 px-4 text-[10px] font-black text-rose-500 uppercase tracking-widest whitespace-nowrap text-right">PLAN NIGHT ✏️</th>
                            <th class="py-3 px-4 text-[10px] font-black text-rose-500 uppercase tracking-widest whitespace-nowrap text-right">ACT PROD ✏️</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right">S. AKHIR</th>
                            <th class="py-3 px-4 text-[10px] font-black text-rose-500 uppercase tracking-widest whitespace-nowrap text-right">PCS/DAY ✏️</th>
                            <th class="py-3 px-4 text-[10px] font-black text-slate-500 uppercase tracking-widest whitespace-nowrap text-right rounded-tr-xl">STRENGTH</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($items as $i => $item)
                        @php
                            $st = (float)$item->strength;
                            $stColor = $st < 2 ? 'text-red-600' : ($st >= 5 ? 'text-blue-600' : 'text-emerald-600');
                            $stClass = strtolower($item->status ?: 'standar');
                            if ($stClass === 'standar') $badgeCls = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                            elseif ($stClass === 'over') $badgeCls = 'bg-blue-100 text-blue-800 border border-blue-200';
                            elseif ($stClass === 'minim' || $stClass === 'critical' || $stClass === 'limited') $badgeCls = 'bg-red-100 text-red-800 border border-red-200';
                            else $badgeCls = 'bg-slate-100 text-slate-800 border border-slate-200';
                        @endphp
                        <tr id="row-{{ $item->id }}" class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-4 text-xs font-medium text-slate-400 text-center">{{ ($items->currentPage()-1)*$items->perPage() + $i + 1 }}</td>
                            <td class="py-3 px-4 text-xs font-black text-slate-800">{{ $item->job_no }}</td>
                            <td class="py-3 px-4 text-[11px] font-bold text-slate-500">{{ $item->tipe }}</td>
                            <td class="py-3 px-4 text-[11px] font-bold text-slate-600">{{ $item->vendor }}</td>
                            <td class="py-3 px-4 text-center" id="status-{{ $item->id }}">
                                <span class="badge-status px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider {{ $badgeCls }}">{{ $item->status }}</span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="stock_awal" value="{{ number_format($item->stock_awal, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>
                            <td class="py-3 px-4 text-xs font-bold text-slate-600 text-right">{{ number_format($item->price, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="incoming" value="{{ number_format($item->incoming, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>
                            <td class="py-3 px-4 text-right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="spare_part" value="{{ number_format($item->spare_part, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>
                            <td class="py-3 px-4 text-right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="plan_day" value="{{ number_format($item->plan_day, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>
                            <td class="py-3 px-4 text-right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="plan_night" value="{{ number_format($item->plan_night, 0, '.', '') }}" onchange="saveInline(this)">
                            </td>
                            <td class="py-3 px-4 text-right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="actual_prod" value="{{ $item->actual_prod !== null ? number_format($item->actual_prod, 0, '.', '') : '' }}" onchange="saveInline(this)">
                            </td>
                            <td class="py-3 px-4 text-sm font-black text-right {{ $item->stok_akhir < 0 ? 'text-red-600' : 'text-slate-800' }}" id="stok-{{ $item->id }}">
                                {{ number_format($item->stok_akhir, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                <input type="number" class="inline-input" data-id="{{ $item->id }}" data-field="pcs_day" value="{{ number_format($item->pcs_day, 2, '.', '') }}" step="0.01" onchange="saveInline(this)">
                            </td>
                            <td class="py-3 px-4 text-xs font-black text-right {{ $stColor }}" id="str-{{ $item->id }}">
                                {{ number_format($item->strength, 2, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

            {{-- Pagination --}}
            @if(isset($items) && $items instanceof \Illuminate\Pagination\LengthAwarePaginator && $items->lastPage() > 1)
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="text-xs font-medium text-slate-500">
                    Menampilkan <span class="font-black text-slate-700">{{ $items->firstItem() }}-{{ $items->lastItem() }}</span> dari <span class="font-black text-slate-700">{{ $items->total() }}</span>
                </div>

                <div class="flex items-center gap-1.5">
                    @if($items->onFirstPage())
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-400 cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </span>
                    @else
                    <a href="{{ $items->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </a>
                    @endif

                    @php
                    $pStart = max(1, $items->currentPage() - 2);
                    $pEnd   = min($items->lastPage(), $items->currentPage() + 2);
                    @endphp

                    @if($pStart > 1)
                    <a href="{{ $items->url(1) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">1</a>
                    @if($pStart > 2)
                    <span class="px-1 text-slate-400">...</span>
                    @endif
                    @endif

                    @for($p = $pStart; $p <= $pEnd; $p++)
                    @if($p === $items->currentPage())
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-rose-600 text-xs font-bold text-white shadow-md shadow-rose-200 border border-rose-600">{{ $p }}</span>
                    @else
                    <a href="{{ $items->url($p) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">{{ $p }}</a>
                    @endif
                    @endfor

                    @if($pEnd < $items->lastPage())
                    @if($pEnd < $items->lastPage() - 1)
                    <span class="px-1 text-slate-400">...</span>
                    @endif
                    <a href="{{ $items->url($items->lastPage()) }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-xs font-bold text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">{{ $items->lastPage() }}</a>
                    @endif

                    @if($items->hasMorePages())
                    <a href="{{ $items->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white border border-slate-200 text-slate-600 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </a>
                    @else
                    <span class="w-8 h-8 flex items-center justify-center rounded-lg bg-slate-100 text-slate-400 cursor-not-allowed">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </span>
                    @endif
                </div>
            </div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = '{{ csrf_token() }}';

document.addEventListener('DOMContentLoaded', function() {
    const tableWrap = document.getElementById('tableWrap');
    const topScrollbar = document.getElementById('topScrollbar');
    const topScrollbarDummy = document.getElementById('topScrollbarDummy');
    if(tableWrap && topScrollbar && topScrollbarDummy) {
        const table = tableWrap.querySelector('table');
        if(table) {
            const updateWidth = () => { topScrollbarDummy.style.width = table.offsetWidth + 'px'; };
            updateWidth();
            topScrollbar.addEventListener('scroll', () => { tableWrap.scrollLeft = topScrollbar.scrollLeft; });
            tableWrap.addEventListener('scroll', () => { topScrollbar.scrollLeft = tableWrap.scrollLeft; });
            window.addEventListener('resize', updateWidth);
        } else {
            topScrollbar.style.display = 'none';
        }
    }

    const selected = "{{ $selectedSheet }}";
    if(selected) {
        const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
        const parts = selected.split(' ');
        if(parts.length >= 2) {
            const mIndex = months.indexOf(parts[1].toUpperCase());
            if(mIndex >= 0) {
                const m = (mIndex + 1).toString().padStart(2, '0');
                const day = parseInt(parts[0]).toString().padStart(2, '0');
                const year = parts[2] ? parts[2] : new Date().getFullYear();
                const calInput = document.getElementById('calendarInput');
                if(calInput) calInput.value = `${year}-${m}-${day}`;
            }
        }
    }
});

function submitCalendarDate(val) {
    if(!val) return;
    const parts = val.split('-');
    if (parts.length < 3) return;
    const monthIndex = parseInt(parts[1], 10) - 1;
    const day = parts[2].padStart(2, '0');

    const months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
    const sheetName = day + ' ' + months[monthIndex];

    const url = new URL(window.location.href);
    url.searchParams.set('sheet', sheetName);
    url.searchParams.set('page', '');
    window.location.href = url.toString();
}

function toggleDateDropdown(e) {
    if(e) e.stopPropagation();
    document.getElementById('dateDropdownContainer').classList.toggle('active');
    const vc = document.getElementById('vendorDropdownContainer');
    if(vc) vc.classList.remove('active');
}
document.addEventListener('click', function(e) {
    const dc = document.getElementById('dateDropdownContainer');
    const cal = document.getElementById('calendarInput');
    if (dc) {
        if (!dc.contains(e.target) && e.target !== cal && document.activeElement !== cal) {
            dc.classList.remove('active');
        }
    }
});

function toggleVendorDropdown(e) {
    if(e) e.stopPropagation();
    document.getElementById('vendorDropdownContainer').classList.toggle('active');
    const dc = document.getElementById('dateDropdownContainer');
    if(dc) dc.classList.remove('active');
}
document.addEventListener('click', function(e) {
    const vc = document.getElementById('vendorDropdownContainer');
    if(vc && !vc.contains(e.target)) vc.classList.remove('active');
});

function formatNum(num) {
    return new Intl.NumberFormat('id-ID').format(Math.round(num));
}

function saveInline(input) {
    const id    = input.dataset.id;
    const field = input.dataset.field;
    let val     = input.value;

    if (val === '' && field === 'actual_prod') {
        val = null;
    } else {
        val = parseFloat(val) || 0;
    }

    input.classList.add('saving');

    fetch('{{ route("rundown_press.inline") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        body: JSON.stringify({ id: id, field: field, value: val })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            input.classList.remove('saving');
            input.classList.add('saved');
            setTimeout(() => input.classList.remove('saved'), 1500);

            const stokEl = document.getElementById('stok-' + id);
            if(stokEl) {
                stokEl.textContent = formatNum(data.stok_akhir);
                stokEl.className = data.stok_akhir < 0 ? 'py-3 px-4 text-sm font-black text-right text-red-600' : 'py-3 px-4 text-sm font-black text-right text-slate-800';
            }

            const strEl = document.getElementById('str-' + id);
            if(strEl) {
                strEl.textContent = new Intl.NumberFormat('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2}).format(data.strength);
                const st = data.strength;
                strEl.className = 'py-3 px-4 text-xs font-black text-right ' + (st <= 0 ? 'text-red-600' : (st < 2 ? 'text-red-600' : (st < 5 ? 'text-emerald-600' : 'text-blue-600')));
            }

            const statusEl = document.getElementById('status-' + id);
            if(statusEl) {
                const badge = statusEl.querySelector('.badge-status');
                if(badge) {
                    badge.textContent = data.status;
                    let stClass = data.status.toLowerCase();
                    let badgeCls = 'bg-slate-100 text-slate-800 border border-slate-200';
                    if (stClass === 'standar') badgeCls = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                    else if (stClass === 'over') badgeCls = 'bg-blue-100 text-blue-800 border border-blue-200';
                    else if (stClass === 'minim' || stClass === 'critical' || stClass === 'limited') badgeCls = 'bg-red-100 text-red-800 border border-red-200';
                    badge.className = 'badge-status px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wider ' + badgeCls;
                }
            }

            if (data.pcs_day !== undefined) {
                const pcsDayInput = document.querySelector(`input[data-id="${id}"][data-field="pcs_day"]`);
                if (pcsDayInput) {
                    pcsDayInput.value = parseFloat(data.pcs_day).toFixed(2);
                }
            }

            if (data.actual_prod !== undefined) {
                const actProdInput = document.querySelector(`input[data-id="${id}"][data-field="actual_prod"]`);
                if (actProdInput) {
                    actProdInput.value = data.actual_prod !== null ? parseFloat(data.actual_prod).toFixed(0) : '';
                }
            }
        } else {
            input.classList.remove('saving');
            alert('Gagal menyimpan: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(() => {
        input.classList.remove('saving');
        alert('Gagal menyimpan!');
    });
}

function syncStokToStamping(sheetDate) {
    const btn = document.getElementById('syncStampingBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Menyinkronkan...';
    }

    fetch('/rundown-press/sync-to-stamping?sheet=' + encodeURIComponent(sheetDate), {
        method: 'GET',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Sync ke Stamping';
        }
        if (data.success) {
            showToast('✅ ' + data.message, '#16a34a');
        } else {
            showToast('❌ ' + (data.error || 'Gagal sync'), '#dc2626');
        }
    })
    .catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg> Sync ke Stamping';
        }
        showToast('❌ Gagal menghubungi server', '#dc2626');
    });
}

function showToast(msg, color) {
    const el = document.createElement('div');
    el.style.cssText = `position:fixed;bottom:24px;right:24px;background:${color};color:white;padding:12px 20px;border-radius:10px;font-size:13px;font-weight:700;z-index:9999;box-shadow:0 4px 16px rgba(0,0,0,0.25);transition:opacity .4s`;
    el.textContent = msg;
    document.body.appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 400); }, 3000);
}
</script>
@endpush

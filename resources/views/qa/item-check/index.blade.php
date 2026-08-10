@extends('layouts.app')
@section('content')
    @section('title', 'Item Check Dashboard')
    <div class='mb-6'><h1 class='text-2xl font-black text-slate-800'>Dashboard Item Check (Inspeksi Harian)</h1></div>

    <div x-data="itemCheckDashboard()" x-init="init()" class="space-y-6">
        
        {{-- Flash Messages --}}
        @if(session('error'))
        <div class="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-2xl mb-6 shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-bold text-rose-700">{{ session('error') }}</p>
                </div>
            </div>
        </div>
        @endif
        {{-- Summary Widgets --}}
        @php
            $totalSchedule = $schedules->count();
            $inProgress = $itemChecks->where('status', 'in_progress')->count();
            $selesai = $itemChecks->whereIn('status', ['finished', 'approved'])->count();
            $totalNg = $itemChecks->sum('reject') + $itemChecks->sum('repair');
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between group hover:border-blue-200 transition-colors">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Schedule</p>
                    <p class="text-2xl font-black text-slate-800">{{ $totalSchedule }} <span class="text-xs text-slate-400 font-bold ml-1">Part</span></p>
                </div>
                <div class="w-12 h-12 rounded-[14px] bg-blue-50 text-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between group hover:border-emerald-200 transition-colors">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Part Selesai</p>
                    <p class="text-2xl font-black text-slate-800">{{ $selesai }} <span class="text-xs text-slate-400 font-bold ml-1">Part</span></p>
                </div>
                <div class="w-12 h-12 rounded-[14px] bg-emerald-50 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between group hover:border-amber-200 transition-colors">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">In Progress</p>
                    <p class="text-2xl font-black text-slate-800">{{ $inProgress }} <span class="text-xs text-slate-400 font-bold ml-1">Part</span></p>
                </div>
                <div class="w-12 h-12 rounded-[14px] bg-amber-50 text-amber-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm flex items-center justify-between group hover:border-rose-200 transition-colors">
                <div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total NG</p>
                    <p class="text-2xl font-black text-slate-800">{{ $totalNg }} <span class="text-xs text-slate-400 font-bold ml-1">Pcs</span></p>
                </div>
                <div class="w-12 h-12 rounded-[14px] bg-rose-50 text-rose-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Operator Notification Banner -->
        <template x-if="counts.revision > 0 && role === 'Operator'">
            <div class="mb-6 p-4 bg-rose-50 border-2 border-rose-200 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-rose-100 rounded-xl flex items-center justify-center text-rose-600 animate-bounce">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-rose-800 font-black text-sm uppercase tracking-wider">Perhatian! Ada Dokumen Revisi</h3>
                        <p class="text-rose-600 text-[11px] font-bold mt-0.5">Terdapat <span class="font-black text-rose-700 text-xs" x-text="counts.revision"></span> dokumen yang ditolak (direvisi) oleh GL dan butuh perbaikan segera.</p>
                    </div>
                </div>
                <button @click="filter = 'revision'" class="px-4 py-2.5 bg-rose-600 text-white rounded-xl text-xs font-black shadow-lg shadow-rose-600/30 hover:bg-rose-700 transition-colors active:scale-95">
                    Lihat Revisi &rarr;
                </button>
            </div>
        </template>

        <!-- Modern Header Action Section (QPR Style) -->
        <div class="flex flex-col md:flex-row gap-4">
            
            {{-- Unified Search & Filter Bar --}}
            <div class="flex-1 flex items-center bg-white border-2 border-slate-100 rounded-2xl p-1.5 shadow-sm focus-within:border-blue-400/60 focus-within:shadow-blue-400/10 transition-all duration-300">
                <div class="pl-4 pr-3 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Cari No. Job, Part Name, atau Part No..."
                    class="flex-1 bg-transparent border-none focus:ring-0 text-sm font-semibold text-slate-800 placeholder-slate-400 py-2.5 outline-none"
                >
                
                {{-- Reset Search --}}
                <template x-if="searchQuery">
                    <button @click="searchQuery = ''" class="mr-3 px-2 py-1 bg-rose-50 text-rose-600 rounded-lg text-[10px] font-bold hover:bg-rose-100 transition-colors flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </button>
                </template>
            </div>
            
            {{-- Action Buttons --}}
            <div class="flex items-stretch gap-2 shrink-0 h-[56px]">
                <button @click="isSyncing = true; window.location.href = '?sync=true'" class="px-4 bg-white border-2 border-slate-100 rounded-2xl text-slate-400 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-all active:scale-95 flex items-center justify-center group" title="Tarik Data SAP Terbaru">
                    <svg x-show="!isSyncing" class="w-5 h-5 group-hover:rotate-180 transition-transform duration-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    <svg x-show="isSyncing" style="display: none;" class="w-5 h-5 animate-spin text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </button>
            </div>
        </div>

        <!-- Filter Tabs (QPR Style) -->
        <div class="flex gap-2 overflow-x-auto pb-4 scrollbar-hide snap-x relative items-center">
            <template x-for="tab in availableTabs" :key="tab.id">
                <button 
                    @click="filter = tab.id"
                    class="px-4 py-2 rounded-full font-black text-[10px] md:text-[11px] uppercase tracking-wider whitespace-nowrap transition-all border-2 snap-start flex items-center gap-1.5"
                    :class="filter === tab.id 
                        ? 'bg-slate-800 border-slate-800 text-white shadow-lg shadow-slate-800/20' 
                        : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50'"
                    x-html="tab.label"
                ></button>
            </template>
            
            <div class="ml-auto pl-4 shrink-0">
                <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-3 py-1.5 rounded-lg"
                      x-text="'Total: ' + filteredChecks.length + ' Data'">
                </span>
            </div>
        </div>

        {{-- DAFTAR ITEM CHECK BERJALAN & SELESAI --}}
        <div>
            <template x-if="filteredChecks.length === 0">
                <div class="bg-white rounded-[24px] border border-slate-100 p-12 text-center shadow-sm">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700">Tidak ada data ditemukan</h3>
                    <p class="text-xs text-slate-500 mt-1">Belum ada inspeksi yang sesuai dengan filter ini.</p>
                </div>
            </template>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" x-show="filteredChecks.length > 0">
                <template x-for="item in filteredChecks" :key="item.id">
                    <div class="relative flex flex-col p-6 rounded-[24px] overflow-hidden group hover:shadow-2xl hover:scale-[1.02] transition-all duration-300 bg-white border border-slate-200 shadow-xl shadow-slate-200/40">
                        <div class="flex justify-between items-start mb-6">
                            <span class="inline-flex items-center px-3 py-1 bg-slate-100 text-slate-600 text-[9px] font-black uppercase tracking-[0.1em] rounded-full border border-slate-200" x-text="item.schedule?.job_no || '-'"></span>
                            
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-[0.1em] border"
                                      :class="{
                                          'bg-blue-50 text-blue-600 border-blue-200': item.status === 'in_progress',
                                          'bg-rose-100 text-rose-700 border-rose-200': item.status === 'revision',
                                          'bg-amber-100 text-amber-700 border-amber-200': item.status === 'waiting_gl' || item.status === 'waiting_qc_approval',
                                          'bg-orange-100 text-orange-700 border-orange-200': item.status === 'waiting_foreman',
                                          'bg-emerald-100 text-emerald-700 border-emerald-200': item.status === 'finished' || item.status === 'approved'
                                      }"
                                      x-text="formatStatus(item)">
                                </span>
                        </div>
                        
                        <div class="flex justify-between items-start gap-3 mb-1.5">
                            <h3 class="text-slate-800 font-black text-lg leading-tight line-clamp-2" x-text="item.master_template?.part_name || '-'"></h3>
                            <template x-if="((item.reject || 0) + (item.repair || 0)) > 0">
                                <span class="shrink-0 px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider bg-rose-50 text-rose-600 border border-rose-200 shadow-sm flex items-center gap-1.5" title="Total Temuan NG (Repair + Reject)">
                                    <svg class="w-3.5 h-3.5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span x-text="((item.reject || 0) + (item.repair || 0)) + ' NG'"></span>
                                </span>
                            </template>
                        </div>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-6 line-clamp-1 min-h-[16px]" x-text="item.master_template?.part_no || '-'"></p>

                        <div class="space-y-4 mb-6 mt-auto">
                            <div class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <div>
                                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Operator</p>
                                    <p class="text-xs text-slate-700 font-semibold mt-0.5" x-text="item.operator?.name || 'Unknown'"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <div>
                                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Tanggal</p>
                                    <p class="text-xs text-slate-700 font-semibold mt-0.5" x-text="formatDate(item.tanggal)"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                <div>
                                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Aktual Produksi</p>
                                    <p class="text-xs font-semibold mt-0.5">
                                        <span class="text-slate-800" x-text="item.total_produksi > 0 ? item.total_produksi : (item.schedule?.actual_qty || 0)"></span>
                                        <span class="text-[10px] text-slate-400 font-bold mx-0.5">/</span>
                                        <span class="text-slate-500" x-text="item.schedule?.target_qty || 0"></span>
                                        <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider ml-0.5">PCS</span>
                                    </p>
                                </div>
                            </div>
                        </div>



                        {{-- Progress & Quality Metrics --}}
                        <div class="mb-6">
                            <div class="flex items-center justify-between text-[10px] font-bold mb-2">
                                <span class="text-slate-500 uppercase tracking-wider">Progress Inspeksi</span>
                                <span class="text-blue-600" x-text="['finished','approved'].includes(item.status) ? `100% (${item.qa_checked || 0}/${item.required_samples || 0} Sampel)` : `${item.required_samples > 0 ? Math.min(100, Math.round(((item.qa_checked || 0) / item.required_samples) * 100)) : 0}% (${item.qa_checked || 0}/${item.required_samples || 0} Sampel)`"></span>
                            </div>
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden mb-4 flex">
                                <!-- Default Blue Bar (if no OK/NG data yet) -->
                                <template x-if="(item.qa_ok || 0) == 0 && (item.qa_ng || 0) == 0">
                                    <div class="bg-blue-500 h-full transition-all duration-500" 
                                         :style="`width: ${['finished','approved'].includes(item.status) ? 100 : (item.required_samples > 0 ? Math.min(100, Math.round(((item.qa_checked || 0) / item.required_samples) * 100)) : 0)}%`"></div>
                                </template>
                                
                                <!-- Multi-Segment Bar (Green for OK, Red for NG) -->
                                <template x-if="(item.qa_ok || 0) > 0 || (item.qa_ng || 0) > 0">
                                    <div class="h-full flex transition-all duration-500" 
                                         :style="`width: ${['finished','approved'].includes(item.status) ? 100 : (item.required_samples > 0 ? Math.min(100, Math.round(((item.qa_checked || 0) / item.required_samples) * 100)) : 0)}%`">
                                        <div class="bg-emerald-500 h-full transition-all duration-500" :style="`width: ${((item.qa_ok || 0) / (item.qa_checked || 1)) * 100}%`" title="OK"></div>
                                        <div class="bg-rose-500 h-full transition-all duration-500" :style="`width: ${((item.qa_ng || 0) / (item.qa_checked || 1)) * 100}%`" title="NG"></div>
                                    </div>
                                </template>
                            </div>
                            
                            {{-- Quality Metrics Grid --}}
                            <div class="grid grid-cols-2 gap-2" x-show="item.qa_checked > 0">
                                <div class="bg-emerald-50/50 rounded-xl p-2.5 border border-emerald-100 flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center">
                                            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        </div>
                                        <span class="text-[10px] font-black text-emerald-700 tracking-wider">OK</span>
                                    </div>
                                    <span class="text-sm font-black text-emerald-700">
                                        <span x-text="item.qa_ok || 0"></span>
                                        <span class="text-[9px] font-bold text-emerald-600/70 ml-0.5">SMPL</span>
                                    </span>
                                </div>
                                <div class="rounded-xl p-2.5 border flex items-center justify-between transition-colors" :class="item.qa_ng > 0 ? 'bg-rose-50 border-rose-200 shadow-sm' : 'bg-slate-50 border-slate-100'">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-5 h-5 rounded-full flex items-center justify-center transition-colors" :class="item.qa_ng > 0 ? 'bg-rose-100' : 'bg-slate-200'">
                                            <svg class="w-3 h-3 transition-colors" :class="item.qa_ng > 0 ? 'text-rose-600' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </div>
                                        <span class="text-[10px] font-black tracking-wider transition-colors" :class="item.qa_ng > 0 ? 'text-rose-700' : 'text-slate-500'">NG</span>
                                    </div>
                                    <span class="text-sm font-black transition-colors" :class="item.qa_ng > 0 ? 'text-rose-700' : 'text-slate-500'">
                                        <span x-text="item.qa_ng || 0"></span>
                                        <span class="text-[9px] font-bold ml-0.5 transition-colors" :class="item.qa_ng > 0 ? 'text-rose-600/70' : 'text-slate-400'">SMPL</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                            <a :href="`/item-check/${item.id}/form`" class="w-full py-3 px-3 text-center bg-slate-800 text-white rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 shadow-lg hover:bg-slate-900">
                                Buka Dokumen <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        </div>
                    </div>
                </template>
        </div>

        {{-- Jadwal Produksi (Task Board) --}}
        <div class="bg-white rounded-[24px] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-black text-slate-800 tracking-tight">Jadwal Produksi Hari Ini</h2>
                    <p class="text-xs font-semibold text-slate-500 mt-1">Daftar Part yang harus diinspeksi sesuai target SAP</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    {{-- Filter Line --}}
                    <select x-model="schedLine" class="text-xs font-bold text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 outline-none focus:ring-2 focus:ring-blue-100 cursor-pointer max-w-[120px]">
                        <option value="">Semua Line</option>
                        <template x-for="line in uniqueSchedLines" :key="line">
                            <option :value="line" x-text="line"></option>
                        </template>
                    </select>

                    <button @click="schedLine = ''" x-show="schedLine" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition-colors" title="Reset Filter" style="display: none;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>

                    <div class="w-px h-6 bg-slate-200 mx-1 hidden md:block"></div>

                    <button @click="window.location.href = '?sync=true'" class="p-1.5 bg-slate-50 hover:bg-slate-100 text-slate-500 rounded-lg transition-colors" title="Tarik Data SAP Terbaru">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    </button>
                    <input type="date" 
                           x-model="filterDate" 
                           @change="updateDate()"
                           class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-[11px] font-black tracking-widest shadow-md shadow-slate-300 outline-none border-none cursor-pointer focus:ring-2 focus:ring-slate-400 [color-scheme:dark]">
                </div>
            </div>

            <!-- Tambahan Filter Pills untuk Schedule -->
            <div class="px-6 pt-4 flex gap-2 overflow-x-auto scrollbar-hide items-center">
                <button @click="schedFilter = 'semua'"
                        class="px-4 py-2 rounded-full font-black text-[10px] md:text-[11px] uppercase tracking-wider whitespace-nowrap transition-all border-2 flex items-center gap-2"
                        :class="schedFilter === 'semua' ? 'bg-slate-800 border-slate-800 text-white shadow-lg shadow-slate-800/20' : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50'">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    SEMUA (<span x-text="schedCounts.semua"></span>)
                </button>
                <button @click="schedFilter = 'aktual'"
                        class="px-4 py-2 rounded-full font-black text-[10px] md:text-[11px] uppercase tracking-wider whitespace-nowrap transition-all border-2 flex items-center gap-2"
                        :class="schedFilter === 'aktual' ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-600/20' : 'bg-white border-indigo-200 text-indigo-500 hover:border-indigo-300 hover:bg-indigo-50'">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    ADA AKTUAL PRODUKSI (<span x-text="schedCounts.aktual"></span>)
                </button>
            </div>

            <div class="p-6 relative">
                @if($schedules->isEmpty())
                <div class="text-center py-12">
                    <div class="w-16 h-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700">Belum Ada Jadwal Produksi</h3>
                    <p class="text-xs text-slate-500 mt-1">Tidak ada tarikan jadwal dari SAP untuk hari ini.</p>
                </div>
                @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($schedules as $schedule)
                    @php
                        // Aggregation logic for view
                        $checkedTotal = 0;
                        $ngTotal = 0;
                        
                        foreach ($schedule->itemChecks as $ic) {
                            $samples = []; // struktur: [ colIndex => ['is_ng' => false] ]
                            
                            $processData = function($data, $isVisual) use (&$samples) {
                                if (!is_array($data)) return;
                                foreach ($data as $key => $val) {
                                    if (preg_match('/_(\d+)$/', $key, $matches)) {
                                        $col = $matches[1];
                                        $strVal = strtolower(trim((string)$val));
                                        
                                        if ($strVal === '') continue;

                                        if (!isset($samples[$col])) {
                                            $samples[$col] = ['is_ng' => false, 'has_judgement' => false];
                                        }

                                        if ($isVisual) {
                                            if (in_array($strVal, ['ok', 'ng'])) {
                                                $samples[$col]['has_judgement'] = true;
                                            }
                                            if ($strVal === 'ng') {
                                                $samples[$col]['is_ng'] = true;
                                            }
                                        } else {
                                            // Untuk dimensi, kita asumsikan jika ada isinya berarti sedang dikerjakan.
                                            // Namun agar akurat, sampel HANYA dihitung utuh jika bagian visualnya ada judgement OK/NG.
                                        }
                                    }
                                }
                            };
                            
                            $processData($ic->hasil_visual, true);
                            $processData($ic->hasil_dimensi, false);
                            
                            $validSamples = collect($samples)->where('has_judgement', true);
                            $checkedTotal += $validSamples->count();
                            $ngTotal += $validSamples->where('is_ng', true)->count();
                        }
                        
                        $okTotal = $checkedTotal - $ngTotal;
                        $activeTotalProduksi = 0;
                        if ($schedule->itemChecks->count() > 0) {
                            $firstIc = $schedule->itemChecks->first();
                            if ($firstIc->total_produksi > 0) {
                                $activeTotalProduksi = $firstIc->total_produksi;
                            }
                        }
                        
                        $denom = $activeTotalProduksi > 0 ? $activeTotalProduksi : ($schedule->actual_qty > 0 ? $schedule->actual_qty : ($schedule->target_qty > 0 ? $schedule->target_qty : 0));
                        
                        // Hitung Required Samples
                        $requiredCount = 0;
                        if ($schedule->masterTemplate && is_array($schedule->masterTemplate->sampling_cols)) {
                            $cols = $schedule->masterTemplate->sampling_cols;
                            if ($denom > 0) {
                                $baseCols = array_filter($cols, function($c) use ($denom) {
                                    return $c <= $denom;
                                });
                                if (empty($baseCols) || end($baseCols) != $denom) {
                                    $baseCols[] = (int) $denom;
                                }
                                $requiredCount = count(array_unique($baseCols));
                            } else {
                                $requiredCount = count($cols);
                            }
                        } else if ($schedule->masterTemplate && $schedule->masterTemplate->max_sample > 0) {
                            $requiredCount = $denom > 0 ? min($denom, $schedule->masterTemplate->max_sample) : $schedule->masterTemplate->max_sample;
                        }
                        
                        // Cek status apakah sudah selesai
                        $isFinished = $schedule->itemChecks->whereIn('status', ['finished', 'approved'])->count() > 0;
                        $progress = $isFinished ? 100 : ($requiredCount > 0 ? min(100, round(($checkedTotal / $requiredCount) * 100)) : 0);
                        $isOperator = auth()->user()->role === 'Operator';
                        
                        $j = $schedule->job_no;
                        $prefix = strpos($j, '-') !== false ? substr($j, 0, strpos($j, '-')) : $j;
                        $prefix = strtoupper($prefix);
                    @endphp
                    
                    <div class="border border-slate-200 rounded-[20px] p-5 hover:border-blue-300 transition-colors bg-white relative overflow-hidden group"
                         x-show="matchesSchedule('{{ $schedule->line }}', '{{ addslashes($schedule->job_no) }}', '{{ addslashes($schedule->part_name) }}', '{{ addslashes($schedule->part_no) }}', {{ (int) $schedule->actual_qty }})">
                        
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-1 bg-red-600 text-white text-[9px] font-black uppercase tracking-widest rounded shadow-sm shadow-slate-300">
                                        Antrian #{{ $loop->iteration }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-wider rounded">
                                        {{ $schedule->job_no }}
                                    </span>
                                </div>
                                <h3 class="font-black text-slate-800 text-[15px] mt-1 tracking-tight line-clamp-1" title="{{ $schedule->part_name }}">
                                    {{ $schedule->part_name }}
                                </h3>
                                <p class="text-xs font-bold text-blue-600 mt-0.5">{{ $schedule->part_no }}</p>
                            </div>
                            <div class="flex justify-end gap-4 text-right">
                                <div>
                                    <span class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Actual Produksi</span>
                                    <span class="text-xl font-black text-blue-600">{{ $activeTotalProduksi > 0 ? $activeTotalProduksi : $schedule->actual_qty }} <span class="text-xs text-slate-400 font-bold ml-0.5">pcs</span></span>
                                </div>
                            </div>
                        </div>
 
                        {{-- Progress Bar --}}
                        <div class="mb-5">
                            <div class="flex items-center justify-between text-[10px] font-bold mb-2">
                                <span class="text-slate-500 uppercase tracking-wider">Progress Inspeksi</span>
                                <span class="text-blue-600">{{ $progress }}% ({{ $checkedTotal }}/{{ $requiredCount }} Sampel)</span>
                            </div>
                            <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden flex">
                                @if($checkedTotal == 0)
                                    <div class="bg-blue-500 h-full transition-all duration-500" style="width: {{ $progress }}%"></div>
                                @else
                                    <div class="h-full flex transition-all duration-500" style="width: {{ $progress }}%">
                                        <div class="bg-emerald-500 h-full transition-all duration-500" style="width: {{ ($okTotal / max(1, $checkedTotal)) * 100 }}%" title="OK"></div>
                                        <div class="bg-rose-500 h-full transition-all duration-500" style="width: {{ ($ngTotal / max(1, $checkedTotal)) * 100 }}%" title="NG"></div>
                                    </div>
                                @endif
                            </div>
                        </div>


                        {{-- ─── Panel Komparasi: Data Produksi (PPC) vs Temuan QA ─── --}}
                        <div class="mb-5 rounded-2xl overflow-hidden border border-slate-200 shadow-sm">
                            <div class="bg-slate-800 text-white px-4 py-2.5 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                                    <span class="text-[11px] font-black uppercase tracking-widest text-slate-100">Komparasi Produksi vs QA</span>
                                </div>
                                @if($schedule->shift_name)
                                <span class="text-[9px] font-bold bg-slate-700 text-slate-300 px-2.5 py-1 rounded-md border border-slate-600">{{ $schedule->shift_name }}</span>
                                @endif
                            </div>

                            <div class="flex flex-col bg-slate-50 divide-y divide-slate-200">
                                {{-- KIRI: PRODUKSI --}}
                                <div class="p-4 bg-white">
                                    <div class="flex items-start justify-between mb-3 gap-2">
                                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-tight">Laporan Produksi</h4>
                                        <span class="bg-blue-50 text-blue-600 text-[8px] font-black px-1.5 py-0.5 rounded border border-blue-100 shrink-0">DATA PRD</span>
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="bg-emerald-50 rounded-lg p-2 border border-emerald-100 text-center">
                                            <span class="block text-[9px] font-bold text-emerald-600 mb-1">OK</span>
                                            <span class="text-sm font-black text-emerald-700">{{ $schedule->ok_qty ?? 0 }}</span>
                                        </div>
                                        <div class="bg-amber-50 rounded-lg p-2 border border-amber-100 text-center">
                                            <span class="block text-[9px] font-bold text-amber-600 mb-1">REPAIR</span>
                                            <span class="text-sm font-black text-amber-700">{{ $schedule->repair_qty ?? 0 }}</span>
                                        </div>
                                        <div class="bg-rose-50 rounded-lg p-2 border border-rose-100 text-center">
                                            <span class="block text-[9px] font-bold text-rose-600 mb-1">REJECT</span>
                                            <span class="text-sm font-black text-rose-700">{{ $schedule->ng_qty ?? 0 }}</span>
                                        </div>
                                    </div>
                                    
                                    @if($schedule->production_repair_notes || $schedule->production_reject_notes)
                                    <div class="mt-3 pt-3 border-t border-slate-100">
                                        <span class="block text-[9px] font-bold text-slate-500 mb-1.5">Catatan Produksi:</span>
                                        <div class="flex flex-wrap gap-1">
                                            @if($schedule->production_repair_notes)
                                                @foreach(array_slice($schedule->production_repair_notes, 0, 1) as $note)
                                                <span class="inline-flex bg-amber-100 text-amber-700 text-[9px] px-1.5 py-0.5 rounded font-semibold">{{ $note['defect'] ?? 'Repair' }}</span>
                                                @endforeach
                                            @endif
                                            @if($schedule->production_reject_notes)
                                                @foreach(array_slice($schedule->production_reject_notes, 0, 1) as $note)
                                                <span class="inline-flex bg-rose-100 text-rose-700 text-[9px] px-1.5 py-0.5 rounded font-semibold">{{ $note['defect'] ?? 'Reject' }}</span>
                                                @endforeach
                                            @endif
                                            @if(count($schedule->production_repair_notes ?? []) > 1 || count($schedule->production_reject_notes ?? []) > 1)
                                                <span class="inline-flex bg-slate-100 text-slate-500 text-[9px] px-1.5 py-0.5 rounded font-semibold">+ Lainnya</span>
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                {{-- KANAN: QA --}}
                                <div class="p-4 bg-white">
                                    <div class="flex items-start justify-between mb-3 gap-2">
                                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-tight">Inspeksi QA</h4>
                                        <span class="bg-indigo-50 text-indigo-600 text-[8px] font-black px-1.5 py-0.5 rounded border border-indigo-100 shrink-0">HASIL QA</span>
                                    </div>
                                    
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="flex flex-col justify-center items-center p-3 rounded-xl border {{ $okTotal > 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-slate-50 border-slate-200' }}">
                                            <span class="block text-[10px] font-bold {{ $okTotal > 0 ? 'text-emerald-600' : 'text-slate-400' }} mb-1">OK</span>
                                            <div class="flex items-baseline gap-1">
                                                <span class="text-xl font-black {{ $okTotal > 0 ? 'text-emerald-700' : 'text-slate-500' }}">{{ $okTotal }}</span>
                                                <span class="text-[9px] font-bold {{ $okTotal > 0 ? 'text-emerald-600/70' : 'text-slate-400' }}">SMPL</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col justify-center items-center p-3 rounded-xl border {{ $ngTotal > 0 ? 'bg-rose-50 border-rose-200' : 'bg-slate-50 border-slate-200' }}">
                                            <span class="block text-[10px] font-bold {{ $ngTotal > 0 ? 'text-rose-600' : 'text-slate-400' }} mb-1">NG</span>
                                            <div class="flex items-baseline gap-1">
                                                <span class="text-xl font-black {{ $ngTotal > 0 ? 'text-rose-700' : 'text-slate-500' }}">{{ $ngTotal }}</span>
                                                <span class="text-[9px] font-bold {{ $ngTotal > 0 ? 'text-rose-600/70' : 'text-slate-400' }}">SMPL</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Alert diskrepansi jika Produksi lapor 0 NG tapi QA temukan NG --}}
                            @if($ngTotal > 0 && ($schedule->ng_qty ?? 0) == 0 && ($schedule->repair_qty ?? 0) == 0)
                            <div class="bg-gradient-to-r from-rose-500 to-red-600 px-4 py-2.5 flex items-center justify-between border-t border-rose-600">
                                <div class="flex items-center gap-2.5">
                                    <div class="bg-white/20 p-1 rounded-md">
                                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    </div>
                                    <span class="text-[11px] font-black text-white tracking-wide">DISKREPANSI DATA TERDETEKSI</span>
                                </div>
                                <span class="text-[10px] font-semibold text-rose-100 bg-black/20 px-2 py-1 rounded">QA temukan {{ $ngTotal }} NG namun Produksi 0!</span>
                            </div>
                            @endif
                        </div>



                        {{-- Action Button --}}
                        <div class="flex gap-2">
                            @if($isOperator || auth()->user()->role === 'QC')
                                @if($schedule->master_template_id)
                                    @php
                                        $existingCheck = $schedule->itemChecks->first();
                                    @endphp
                                    
                                    @if($existingCheck)
                                        @if($existingCheck->operator_id === auth()->id() && in_array($existingCheck->status, ['in_progress', 'waiting_gl', 'waiting_foreman']))
                                            <a href="{{ route('item-check.form', $existingCheck->id) }}" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-black py-3 rounded-xl flex items-center justify-center transition-all shadow-lg shadow-blue-600/30 active:scale-95">
                                                Lanjutkan Cek <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            </a>
                                        @else
                                            <a href="{{ route('item-check.form', $existingCheck->id) }}" class="flex-1 bg-blue-50 border border-blue-200 hover:bg-blue-100 hover:border-blue-300 text-blue-600 text-xs font-black py-3 rounded-xl flex items-center justify-center transition-all shadow-sm">
                                                Lihat Progress <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </a>
                                        @endif
                                    @else
                                        @php
                                            $hasFormula = !empty($schedule->masterTemplate->sampling_cols) || $schedule->masterTemplate->max_sample > 0 || $schedule->masterTemplate->tact_time > 0 || $schedule->masterTemplate->ct_dimensi > 0;
                                        @endphp
                                        
                                        @if($hasFormula)
                                            <form action="{{ route('item-check.start', $schedule->id) }}" method="POST" class="flex-1 flex">
                                                @csrf
                                                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white text-xs font-black py-3 rounded-xl transition-all active:scale-95 shadow-lg shadow-slate-300">
                                                    Ambil & Mulai Cek
                                                </button>
                                            </form>
                                        @else
                                            <button disabled class="w-full flex-1 bg-amber-50 border border-amber-200 text-amber-600 text-xs font-black py-3 rounded-xl cursor-not-allowed flex items-center justify-center shadow-sm" title="Sampling Formula (Waktu/Target) belum diisi oleh Leader">
                                                <svg class="w-4 h-4 mr-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                Formula Belum Diisi
                                            </button>
                                        @endif
                                    @endif
                                @else
                                    <button disabled class="w-full flex-1 bg-slate-50 border border-slate-200 text-slate-400 text-xs font-black py-3 rounded-xl cursor-not-allowed flex items-center justify-center shadow-sm" title="Standar Inspeksi belum dibuat oleh Admin">
                                        <svg class="w-4 h-4 mr-1.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Template Belum Ada
                                    </button>
                                @endif
                            @else
                                @if($schedule->itemChecks->count() > 0)
                                    <a href="{{ route('item-check.form', $schedule->itemChecks->first()->id) }}" class="flex-1 bg-blue-50 border border-blue-200 hover:bg-blue-100 hover:border-blue-300 text-blue-600 text-xs font-black py-3 rounded-xl flex items-center justify-center transition-all shadow-sm">
                                        Lihat Progress <svg class="ml-1.5 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                @else
                                    <div class="flex-1 flex gap-2">
                                        <button class="flex-1 bg-slate-50 border border-slate-200 text-slate-400 text-xs font-black py-3 rounded-xl cursor-not-allowed flex items-center justify-center">
                                            Belum Dimulai
                                        </button>
                                        @if($schedule->master_template_id)
                                            @if(auth()->user()->role === 'Admin' || auth()->user()->role === 'QC')
                                                <a href="{{ route('item-check.preview', $schedule->master_template_id) }}?actual_qty={{ $schedule->actual_qty > 0 ? $schedule->actual_qty : $schedule->target_qty }}&schedule_id={{ $schedule->id }}" 
                                                   class="w-[48px] bg-amber-50 border border-amber-200 hover:bg-amber-100 hover:border-amber-300 text-amber-600 rounded-xl flex items-center justify-center transition-all shadow-sm"
                                                   title="Preview Form Template">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                </a>
                                            @endif
                                        @else
                                            <button disabled
                                               class="w-[48px] bg-slate-50 border border-slate-200 text-slate-300 rounded-xl flex items-center justify-center transition-all shadow-sm cursor-not-allowed"
                                               title="Master Template Belum Ada">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                            </button>
                                        @endif
                                    </div>
                            @endif
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                
                {{-- Fallback when filtered list is empty --}}
                <div x-show="schedules.filter(s => matchesSchedule(s.line, s.job_no, s.part_name, s.part_no, s.actual_qty)).length === 0" 
                     style="display: none;" 
                     class="text-center py-12">
                    <div class="w-16 h-16 mx-auto bg-slate-50 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-sm font-bold text-slate-700">Jadwal Tidak Ditemukan</h3>
                    <p class="text-xs text-slate-500 mt-1">Coba sesuaikan filter Line Anda.</p>
                </div>
                @endif
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('itemCheckDashboard', () => ({
                role: '{{ auth()->user()->role ?? '' }}',
                isSyncing: false,
                itemChecks: {!! json_encode($itemChecks->map(function($ic) {
                    return [
                        'id' => $ic->id,
                        'status' => $ic->status,
                        'tanggal' => $ic->tanggal,
                        'qa_checked' => $ic->qa_checked,
                        'qa_ok' => $ic->qa_ok,
                        'qa_ng' => $ic->qa_ng,
                        'reject' => $ic->reject,
                        'repair' => $ic->repair,
                        'total_produksi' => $ic->total_produksi,
                        'required_samples' => $ic->required_samples,
                        'gl_signed' => !empty($ic->paraf_foreman),
                        'schedule' => $ic->schedule ? [
                            'id' => $ic->schedule->id,
                            'job_no' => $ic->schedule->job_no,
                            'actual_qty' => $ic->schedule->actual_qty,
                            'target_qty' => $ic->schedule->target_qty
                        ] : null,
                        'master_template' => $ic->masterTemplate ? [
                            'job_no' => $ic->masterTemplate->job_no,
                            'part_name' => $ic->masterTemplate->part_name,
                            'part_no' => $ic->masterTemplate->part_no
                        ] : null,
                        'operator' => $ic->operator ? ['name' => $ic->operator->name] : null,
                    ];
                })->values()->all()) !!},
                schedules: {!! json_encode($schedules->map(function($s) {
                    return [
                        'id' => $s->id,
                        'line' => $s->line,
                        'job_no' => $s->job_no,
                        'part_name' => $s->part_name,
                        'part_no' => $s->part_no,
                        'actual_qty' => $s->actual_qty ?? 0
                    ];
                })->values()->all()) !!},
                filter: 'semua',
                schedFilter: 'semua',
                searchQuery: '',
                schedLine: '',
                filterDate: new URLSearchParams(window.location.search).get('date') || '{{ \Carbon\Carbon::today()->format("Y-m-d") }}',
                
                updateDate() {
                    const url = new URL(window.location.href);
                    url.searchParams.set('date', this.filterDate);
                    window.location.href = url.toString();
                },
                
                init() {
                    console.log('Item Check Dashboard initialized');
                    
                    // Auto-refresh dihapus sesuai permintaan user, 
                    // data di-sync otomatis dari controller saat halaman dimuat.
                },

                get schedCounts() {
                    return {
                        semua: this.schedules.length,
                        aktual: this.schedules.filter(s => parseInt(s.actual_qty) > 0).length
                    };
                },

                get uniqueSchedLines() {
                    const lines = new Set();
                    this.schedules.forEach(s => {
                        if (s.line) lines.add(s.line);
                    });
                    return Array.from(lines).sort();
                },

                matchesSchedule(line, jobNo, partName, partNo, actualQty) {
                    let lineMatch = this.schedLine === '' || this.schedLine === line;
                    let searchMatch = true;
                    if (this.searchQuery.trim() !== '') {
                        const q = this.searchQuery.toLowerCase();
                        searchMatch = (jobNo || '').toLowerCase().includes(q) || 
                                      (partName || '').toLowerCase().includes(q) || 
                                      (partNo || '').toLowerCase().includes(q);
                    }
                    let filterMatch = true;
                    if (this.schedFilter === 'aktual') {
                        filterMatch = parseInt(actualQty) > 0;
                    }
                    return lineMatch && searchMatch && filterMatch;
                },

                get groupedChecks() {
                    let grouped = [];
                    let seenSchedules = new Set();
                    for (let item of this.itemChecks) {
                        if (item.schedule && item.schedule.id) {
                            if (!seenSchedules.has(item.schedule.id)) {
                                seenSchedules.add(item.schedule.id);
                                
                                let tandemItems = this.itemChecks.filter(i => i.schedule?.id === item.schedule.id);
                                
                                if (tandemItems.length > 1) {
                                    let displayItem = JSON.parse(JSON.stringify(tandemItems[0]));
                                    
                                    // Combine part names
                                    let p1 = tandemItems[0].master_template?.part_name || '';
                                    let p2 = tandemItems[1].master_template?.part_name || '';
                                    if (p1 && p2) {
                                        let p1Base = p1.replace(/RH|LH/gi, '').trim();
                                        let p2Base = p2.replace(/RH|LH/gi, '').trim();
                                        if (p1Base === p2Base && p1Base !== '') {
                                            displayItem.master_template.part_name = p1Base + ' RH/LH';
                                        } else {
                                            displayItem.master_template.part_name = p1 + ' / ' + p2;
                                        }
                                    }

                                    // Combine part numbers
                                    displayItem.master_template.part_no = tandemItems.map(i => i.master_template?.part_no).join(' / ');
                                    
                                    // Combine job numbers
                                    let j1 = tandemItems[0].master_template?.job_no || tandemItems[0].schedule?.job_no || '';
                                    let j2 = tandemItems[1].master_template?.job_no || tandemItems[1].schedule?.job_no || '';
                                    if (j1 && j2) {
                                        if (j1 !== j2) {
                                            displayItem.schedule.job_no = j1 + ' / ' + j2;
                                        } else {
                                            // Handle identical job numbers (e.g. K-4047 and K-4047)
                                            let match = j1.match(/^([a-zA-Z\-_]+)(\d+)$/);
                                            if (match) {
                                                let prefix = match[1];
                                                let numStr = match[2];
                                                let num = parseInt(numStr, 10);
                                                let nextNum = (num % 2 !== 0) ? num + 1 : num - 1;
                                                let smaller = Math.min(num, nextNum);
                                                let larger = Math.max(num, nextNum);
                                                let smallerStr = smaller.toString().padStart(numStr.length, '0');
                                                let largerStr = larger.toString().padStart(numStr.length, '0');
                                                displayItem.schedule.job_no = prefix + smallerStr + ' / ' + prefix + largerStr;
                                            } else {
                                                displayItem.schedule.job_no = j1;
                                            }
                                        }
                                    }
                                    
                                    // Aggregate metrics
                                    displayItem.qa_checked = tandemItems.reduce((sum, i) => sum + (i.qa_checked || 0), 0);
                                    displayItem.required_samples = tandemItems.reduce((sum, i) => sum + (i.required_samples || 0), 0);
                                    displayItem.qa_ok = tandemItems.reduce((sum, i) => sum + (i.qa_ok || 0), 0);
                                    displayItem.qa_ng = tandemItems.reduce((sum, i) => sum + (i.qa_ng || 0), 0);
                                    displayItem.reject = tandemItems.reduce((sum, i) => sum + (i.reject || 0), 0);
                                    displayItem.repair = tandemItems.reduce((sum, i) => sum + (i.repair || 0), 0);
                                    
                                    // Aggregate Status (if one is waiting, card is waiting, else finished)
                                    // Simplified logic: use the first item's status, or find the "lowest" status
                                    const statuses = tandemItems.map(i => i.status);
                                    if (statuses.includes('in_progress')) displayItem.status = 'in_progress';
                                    else if (statuses.includes('waiting_qc_approval')) displayItem.status = 'waiting_qc_approval';
                                    else if (statuses.includes('waiting_gl')) displayItem.status = 'waiting_gl';
                                    else if (statuses.includes('waiting_foreman')) displayItem.status = 'waiting_foreman';
                                    else if (statuses.includes('waiting_supervisor')) displayItem.status = 'waiting_supervisor';
                                    
                                    grouped.push(displayItem);
                                } else {
                                    let clonedItem = JSON.parse(JSON.stringify(item));
                                    if (clonedItem.master_template?.job_no) {
                                        clonedItem.schedule.job_no = clonedItem.master_template.job_no;
                                    }
                                    grouped.push(clonedItem);
                                }
                            }
                        } else {
                            grouped.push(item);
                        }
                    }
                    return grouped;
                },

                get counts() {
                    return {
                        semua: this.groupedChecks.length,
                        revision: this.groupedChecks.filter(i => i.status === 'revision').length,
                        waiting_gl: this.groupedChecks.filter(i => i.status === 'waiting_gl' || (i.status === 'waiting_qc_approval' && !i.gl_signed)).length,
                        waiting_foreman: this.groupedChecks.filter(i => i.status === 'waiting_foreman' || (i.status === 'waiting_qc_approval' && i.gl_signed)).length,
                        finished: this.groupedChecks.filter(i => ['finished', 'approved', 'locked'].includes(i.status)).length,
                        ada_ng: this.groupedChecks.filter(i => (i.qa_ng || 0) > 0).length,
                    };
                },

                get availableTabs() {
                    const ic = {
                        grid: '<svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>',
                        clock: '<svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                        user: '<svg class="w-4 h-4 shrink-0 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
                        check: '<svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                        alert: '<svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                        ng: '<svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                    };
                    const mkTab = (icon, text) => `${icon}<span>${text}</span>`;
                    return [
                        { id: 'semua', label: mkTab(ic.grid, 'Semua (' + this.counts.semua + ')') },
                        { id: 'revision', label: mkTab(ic.alert, 'Revisi (' + this.counts.revision + ')') },
                        { id: 'ada_ng', label: mkTab(ic.ng, 'Ada Temuan NG (' + this.counts.ada_ng + ')') },
                        { id: 'waiting_gl', label: mkTab(ic.clock, 'Menunggu GL (' + this.counts.waiting_gl + ')') },
                        { id: 'waiting_foreman', label: mkTab(ic.user, 'Menunggu Foreman (' + this.counts.waiting_foreman + ')') },
                        { id: 'finished', label: mkTab(ic.check, 'Selesai (' + this.counts.finished + ')') }
                    ];
                },

                get filteredChecks() {
                    return this.groupedChecks.filter(item => {
                        // Status Filter
                        let statusMatch = true;
                        if (this.filter === 'waiting_gl') statusMatch = item.status === 'waiting_gl' || (item.status === 'waiting_qc_approval' && !item.gl_signed);
                        else if (this.filter === 'waiting_foreman') statusMatch = item.status === 'waiting_foreman' || (item.status === 'waiting_qc_approval' && item.gl_signed);
                        else if (this.filter === 'finished') statusMatch = ['finished', 'approved', 'locked'].includes(item.status);
                        else if (this.filter === 'ada_ng') statusMatch = (item.qa_ng || 0) > 0;
                        else if (this.filter === 'revision') statusMatch = item.status === 'revision';
                        
                        // Search Filter
                        let searchMatch = true;
                        if (this.searchQuery.trim() !== '') {
                            const q = this.searchQuery.toLowerCase();
                            const jobNo = (item.schedule?.job_no || '').toLowerCase();
                            const partName = (item.master_template?.part_name || '').toLowerCase();
                            const partNo = (item.master_template?.part_no || '').toLowerCase();
                            searchMatch = jobNo.includes(q) || partName.includes(q) || partNo.includes(q);
                        }

                        return statusMatch && searchMatch;
                    });
                },

                formatStatus(item) {
                    if (item.status === 'waiting_qc_approval') {
                        return item.gl_signed ? 'Menunggu Foreman' : 'Menunggu GL';
                    }
                    if (item.status === 'locked' || item.status === 'ready_for_qc') {
                        return 'Selesai';
                    }
                    const map = {
                        in_progress: 'In Progress',
                        revision: 'Revisi',
                        waiting_gl: 'Menunggu GL',
                        waiting_foreman: 'Menunggu Foreman',
                        finished: 'Selesai',
                        approved: 'Selesai'
                    };
                    return map[item.status] || item.status;
                },

                formatDate(dateString) {
                    if (!dateString) return '-';
                    const date = new Date(dateString);
                    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
                }
            }));
        });
    </script>
    @endpush
@endsection

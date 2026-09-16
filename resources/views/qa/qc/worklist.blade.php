@extends('layouts.app')
@section('content')

    <div x-data="qcWorklist({ apiUrl: '{{ url('/') }}' })" class="max-w-4xl mx-auto space-y-6">
        
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
            <div>
                <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mb-1">Quality Control</p>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight">Antrian Kerja QC</h1>
                <p class="text-slate-500 text-sm font-semibold mt-1">Silakan pilih Lembar Inspeksi yang akan diisi Item Check-nya.</p>
            </div>
            <button @click="handleRefresh()" :disabled="loading || refreshing" class="px-5 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-bold hover:bg-slate-50 hover:border-blue-200 transition-all shadow-sm flex items-center gap-2 disabled:opacity-50">
                <svg class="w-4 h-4" :class="refreshing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Refresh
            </button>
        </div>

        {{-- Stats Quick View --}}
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 flex items-center gap-5">
            <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center text-2xl shadow-sm">🛠️</div>
            <div>
                <p class="text-[10px] font-black text-blue-700 uppercase tracking-widest mb-1">Tugas Menunggu</p>
                <p class="text-2xl font-black text-blue-900"><span x-text="items.length"></span> Lembar Inspeksi</p>
            </div>
        </div>

        {{-- Task List --}}
        <div class="space-y-4">
            <template x-if="loading && items.length === 0">
                <div class="py-20 text-center">
                    <div class="w-10 h-10 border-4 border-slate-200 border-t-blue-500 rounded-full animate-spin mx-auto mb-4"></div>
                    <p class="text-slate-500 font-bold">Memuat antrian tugas...</p>
                </div>
            </template>

            <template x-if="!loading && items.length === 0">
                <div class="py-20 text-center bg-white border-2 border-dashed border-slate-200 rounded-3xl">
                    <div class="text-5xl mb-4">✅</div>
                    <h3 class="text-xl font-black text-slate-800 mb-2">Antrian Bersih!</h3>
                    <p class="text-slate-500 text-sm font-semibold">Tidak ada Lembar Inspeksi yang menunggu pengecekan QC saat ini.</p>
                </div>
            </template>

            <template x-if="!loading && items.length > 0">
                <div class="grid grid-cols-1 gap-4">
                    <template x-for="item in items" :key="item.id">
                        <div @click="processLI(item)" class="bg-white border border-slate-200 rounded-2xl p-5 flex flex-col md:flex-row md:items-center justify-between gap-6 hover:border-blue-400 hover:shadow-lg hover:shadow-blue-500/10 transition-all cursor-pointer group">
                            
                            <div class="flex items-center gap-5">
                                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">📋</div>
                                <div>
                                    <div class="flex items-center gap-3 mb-1.5">
                                        <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-md text-[10px] font-black tracking-wider" x-text="item.no_form"></span>
                                        <span class="text-[11px] font-bold text-slate-400" x-text="fmtDate(item.updated_at || item.tgl_bulan)"></span>
                                    </div>
                                    <h3 class="text-base font-black text-slate-800 group-hover:text-blue-600 transition-colors" x-text="item.part_name"></h3>
                                    <p class="text-xs font-semibold text-slate-500 mt-1">
                                        Job: <span x-text="item.job_no"></span> · Type: <span x-text="item.type"></span> · Shift: <span x-text="item.shift"></span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between md:justify-end gap-6 md:w-auto w-full pt-4 md:pt-0 border-t md:border-t-0 border-slate-100">
                                <div class="text-left md:text-right">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Status</p>
                                    <p class="text-xs font-black text-emerald-600">Siap Isi QC</p>
                                </div>
                                <button class="px-5 py-2.5 bg-blue-500 text-white rounded-xl text-xs font-black hover:bg-blue-600 transition-all shadow-md shadow-blue-500/20 whitespace-nowrap">
                                    Proses Pengecekan
                                </button>
                            </div>

                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
@endsection

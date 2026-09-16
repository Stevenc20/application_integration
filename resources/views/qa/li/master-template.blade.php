@extends('layouts.app')
@section('content')
<div class="font-['Plus_Jakarta_Sans'] pb-16" x-data="liMasterTemplate()" x-init="init()">

{{-- TOAST --}}
<template x-if="toast">
  <div class="fixed top-5 right-5 z-[9999] flex items-center gap-3 px-5 py-3 rounded-2xl shadow-2xl text-sm font-bold transition-all"
       :class="toast.type==='success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'">
    <span x-text="toast.type==='success' ? '✓' : '✕'"></span>
    <span x-text="toast.msg"></span>
  </div>
</template>

{{-- PAGE HEADER --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Master Template Part</h1>
        <p class="text-sm text-slate-500 mt-1">Kelola template inspeksi produksi dengan standar terbaru.</p>
    </div>
    <div class="flex items-center gap-3">
        <button @click="syncFromLi()" :disabled="syncing"
                class="flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 rounded-xl text-sm font-semibold hover:bg-slate-50 transition-all shadow-sm">
            <template x-if="syncing">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </template>
            <template x-if="!syncing">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </template>
            <span x-text="syncing ? 'Sinkronisasi...' : 'Tarik Data dari LI'"></span>
        </button>
        <button @click="openCreateModal()"
                class="flex items-center gap-2 px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition-all shadow-sm shadow-red-600/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Template Baru
        </button>
    </div>
</div>

{{-- SEARCH --}}
<div class="mb-5">
    <div class="relative w-full">
        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input x-model="search" @input.debounce.400ms="fetchTemplates()"
               placeholder="Search template..."
               class="w-full pl-11 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-medium text-slate-800 outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all placeholder:text-slate-400 shadow-sm">
    </div>
</div>

{{-- FILTER TABS --}}
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
        {{-- Tab Semua --}}
        <button @click="activeGroup = 'Semua'; filterTemplates()"
                :class="activeGroup === 'Semua'
                    ? 'bg-red-50 text-red-700 border-red-200'
                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border transition-all shadow-sm">
            Semua
            <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold"
                  :class="activeGroup === 'Semua' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600'"
                  x-text="templates.length"></span>
        </button>
        {{-- Dynamic tabs per prefix --}}
        <template x-for="grp in uniqueGroups" :key="grp">
            <button @click="activeGroup = grp; filterTemplates()"
                    :class="activeGroup === grp
                        ? 'bg-red-50 text-red-700 border-red-200'
                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'"
                    class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border transition-all shadow-sm">
                <span x-text="grp"></span>
                <span class="inline-flex items-center justify-center min-w-[20px] h-5 px-1.5 rounded-full text-[11px] font-bold"
                      :class="activeGroup === grp ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600'"
                      x-text="templates.filter(t => getJobPrefix(t.job_no) === grp).length"></span>
            </button>
        </template>
    </div>
</div>


{{-- SEARCH + STATS --}}


{{-- LOADING STATE --}}
<template x-if="loading">
    <div class="space-y-3">
        <template x-for="i in 5" :key="i">
            <div class="bg-white rounded-2xl h-16 animate-pulse border border-slate-100"></div>
        </template>
    </div>
</template>

{{-- EMPTY STATE --}}
<template x-if="!loading && filteredTemplates.length === 0">
    <div class="bg-white rounded-3xl border-2 border-dashed border-slate-200 py-20 text-center">
        <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        </div>
        <p class="text-sm font-black text-slate-500">Belum ada Template</p>
        <p class="text-xs text-slate-400 mt-1">Buat template pertama, atau simpan dari halaman Lembar Inspeksi.</p>
        <button @click="openCreateModal()" class="mt-5 px-6 py-2.5 bg-gradient-to-r from-red-600 to-rose-600 text-white rounded-xl text-xs font-black hover:from-red-700 hover:to-rose-700 transition-all shadow-lg shadow-red-500/25">+ Buat Sekarang</button>
    </div>
</template>

{{-- TEMPLATE LIST --}}
<template x-if="!loading && filteredTemplates.length > 0">
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden pb-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left">
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">JOB NO</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">PART NO</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">PART NAME</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest hidden md:table-cell">TYPE</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest hidden lg:table-cell">DIMENSI</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">AKSI</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    <template x-for="t in paginatedTemplates" :key="t.id">
                        <tr class="hover:bg-slate-50/60 transition-colors group">
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-black rounded-lg border shadow-sm tracking-wide"
                                      :class="getBadgeClass(getJobPrefix(t.job_no))"
                                      x-text="t.job_no || '—'"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-800 text-[13px]" x-text="t.part_no"></span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-800 text-[13px]" x-text="t.part_name || '—'"></p>
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                <span class="font-bold text-slate-700 text-[12px]" x-text="t.type || '—'"></span>
                            </td>
                            <td class="px-6 py-4 hidden lg:table-cell">
                                <div class="flex flex-wrap gap-1.5">
                                    <template x-for="i in [1,2,3,4,5]" :key="i">
                                        <span x-show="t['dimensi'+i]"
                                              class="text-[11px] px-2.5 py-1 bg-slate-50 text-slate-600 font-semibold rounded-md border border-slate-100"
                                              x-text="'D'+i+': '+t['dimensi'+i]"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openEditModal(t)"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-400 hover:text-red-600 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        {{-- Pagination footer match --}}
        <div class="px-6 mt-4 flex items-center justify-between text-xs text-slate-500 font-medium">
            <span x-text="'Menampilkan ' + (filteredTemplates.length === 0 ? 0 : ((currentPage - 1) * perPage + 1)) + ' - ' + Math.min(currentPage * perPage, filteredTemplates.length) + ' dari ' + filteredTemplates.length + ' data' + (activeGroup !== 'Semua' || search ? ' (Total ' + templates.length + ')' : '')"></span>
            
            <div class="flex items-center gap-1" x-show="totalPages > 1">
                <button @click="currentPage = Math.max(1, currentPage - 1)" 
                        :disabled="currentPage === 1"
                        :class="currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100 text-slate-600'"
                        class="w-6 h-6 flex items-center justify-center rounded text-slate-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                
                <template x-for="page in totalPages" :key="page">
                    <button @click="currentPage = page"
                            :class="currentPage === page ? 'bg-red-100 text-red-600 font-bold' : 'hover:bg-slate-100 text-slate-600'"
                            class="w-6 h-6 flex items-center justify-center rounded transition-colors"
                            x-text="page"></button>
                </template>

                <button @click="currentPage = Math.min(totalPages, currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        :class="currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-100 text-slate-600'"
                        class="w-6 h-6 flex items-center justify-center rounded text-slate-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </div>
</template>

{{-- ═══════════ EDIT / CREATE MODAL ═══════════ --}}
<template x-if="showModal">
<div class="fixed inset-0 z-[1000] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4"
     @click.self="showModal = false">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-[1400px] h-[95vh] flex flex-col overflow-hidden">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-red-50 shrink-0">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-red-600 rounded-xl flex items-center justify-center font-black text-white text-lg shrink-0">T</div>
                <div>
                    <h2 class="text-base font-black text-slate-800 uppercase tracking-wide" x-text="editingId ? 'EDIT MASTER TEMPLATE: ' + form.part_no : 'BUAT MASTER TEMPLATE'"></h2>
                    <p class="text-[10px] font-black text-red-600 uppercase tracking-widest mt-0.5">MANAJEMEN STANDAR INSPEKSI</p>
                </div>
            </div>
            <button @click="showModal = false" class="w-9 h-9 rounded-xl bg-white border border-slate-200 hover:bg-slate-200 flex items-center justify-center text-slate-500 transition-all shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Modal Body (2 Columns) --}}
        <div class="flex-1 overflow-hidden flex flex-col lg:flex-row">
            
            {{-- KIRI: Identitas & Sketch --}}
            <div class="w-full lg:w-[400px] xl:w-[500px] flex flex-col border-r border-slate-200 bg-slate-50 overflow-y-auto">
                <div class="p-5 space-y-5">
                    {{-- Identitas Part --}}
                    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-100 pb-2">Identitas Part</h3>
                        <div class="space-y-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-[9px] font-black text-slate-500 uppercase">Job No</label>
                                <input x-model="form.job_no" placeholder="Job No"
                                       class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 outline-none focus:border-red-500 transition-all">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[9px] font-black text-slate-500 uppercase">Part No <span class="text-red-500">*</span></label>
                                <input x-model="form.part_no" :disabled="!!editingId" placeholder="Part No"
                                       class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 outline-none focus:border-red-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[9px] font-black text-slate-500 uppercase">Part Name</label>
                                <input x-model="form.part_name" placeholder="Nama Part"

                                       class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 outline-none focus:border-red-500 transition-all">
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-black text-slate-500 uppercase">Type</label>
                                    <input x-model="form.type" placeholder="Type" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 outline-none focus:border-red-500 transition-all">
                                </div>
                                <div class="flex flex-col gap-1">
                                    <label class="text-[9px] font-black text-slate-500 uppercase">Type Pallet</label>
                                    <input x-model="form.type_pallet" placeholder="Pallet" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 outline-none focus:border-red-500 transition-all">
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[9px] font-black text-slate-500 uppercase">Spec Material</label>
                                <input x-model="form.spec_material" placeholder="Material" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 outline-none focus:border-red-500 transition-all">
                            </div>
                        </div>
                    </div>

                    {{-- Sampling Formula --}}
                    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
                        <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 border-b border-slate-100 pb-2">Sampling Formula</h3>
                        <div class="grid grid-cols-3 gap-3">
                            <div class="flex flex-col gap-1">
                                <label class="text-[8px] font-black text-slate-500 uppercase">Tact Time (dt)</label>
                                <input type="number" step="0.1" x-model.number="form.tact_time" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 outline-none focus:border-red-500 text-center">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[8px] font-black text-slate-500 uppercase">CT Dimensi</label>
                                <input type="number" step="0.1" x-model.number="form.ct_dimensi" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 outline-none focus:border-red-500 text-center">
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-[8px] font-black text-slate-500 uppercase">CT Tnp Dim</label>
                                <input type="number" step="0.1" x-model.number="form.ct_tanpa_dimensi" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-bold text-slate-800 outline-none focus:border-red-500 text-center">
                            </div>
                        </div>
                    </div>

                    {{-- Sketch Part --}}
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col">
                        <div class="bg-red-600 px-4 py-2 flex items-center justify-between">
                            <span class="text-[10px] font-black text-white uppercase tracking-widest">SKETCH PART</span>
                        </div>
                        <div class="p-4 flex-1 flex flex-col items-center justify-center min-h-[250px] bg-slate-50 relative group">
                            <template x-if="form.image_path">
                                <img :src="form.image_path.startsWith('data:') ? form.image_path : (form.image_path.includes('/storage/') ? '/storage/' + form.image_path.split('/storage/')[1] : '/storage/' + form.image_path)" class="max-w-full max-h-[300px] object-contain rounded-lg shadow-sm border border-slate-200">
                            </template>

                            <template x-if="!form.image_path">
                                <div class="text-center">
                                    <div class="w-16 h-16 bg-slate-200 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Belum ada Sketch</p>
                                </div>
                            </template>
                            
                            {{-- Upload Button Overlay --}}
                            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <label class="cursor-pointer px-5 py-2.5 bg-white text-slate-800 rounded-xl text-xs font-black shadow-xl hover:scale-105 transition-all">
                                    Upload Sketch
                                    <input type="file" accept="image/*" class="hidden" @change="handleSketchUpload">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- KANAN: Standard Table --}}
            <div class="flex-1 bg-white overflow-y-auto">
                <div class="bg-red-600 px-4 py-2 flex items-center justify-between sticky top-0 z-20 shadow-sm">
                    <span class="text-[10px] font-black text-white uppercase tracking-widest">STANDARD (REFERENCE)</span>
                </div>
                <table class="w-full border-collapse text-[10px]">
                    <thead class="sticky top-8 bg-slate-50 border-b border-slate-200 z-10 shadow-sm">
                        <tr>
                            <th class="px-3 py-3 text-center font-black text-slate-400 w-10 border-r border-slate-100">No</th>
                            <th class="px-3 py-3 text-left font-black text-slate-500 border-r border-slate-100">Item Check</th>
                            <th class="px-3 py-3 text-left font-black text-slate-500">Metode</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        {{-- DIMENSI --}}
                        <tr class="bg-red-50/50">
                            <td colspan="3" class="px-3 py-3 text-[9px] font-black text-red-600 uppercase tracking-widest">DIMENSI</td>
                        </tr>
                        <template x-for="(dim, i) in form.dimensi" :key="'dim_'+i">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-3 py-2 text-center font-black text-slate-800 border-r border-slate-100" x-text="i+1"></td>
                                <td class="px-3 py-2 border-r border-slate-100 cursor-pointer" @click="openDimensiModal(i)">
                                    <div class="flex flex-col gap-1 pointer-events-none">
                                        <input readonly :value="dim.value" placeholder="Nilai Standar (Contoh: 12.5+0.1/-0.1)" class="w-full px-2 py-1.5 bg-red-50 text-red-700 border border-red-100 rounded text-[10px] font-bold outline-none placeholder:text-red-300">
                                        {{-- Badge: nominal sudah terisi dari import/backfill --}}
                                        <template x-if="dim.nominal !== null && dim.nominal !== undefined">
                                            <span class="inline-flex items-center gap-1 text-[8px] font-black text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded-full w-fit">
                                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                Nominal: <span x-text="dim.nominal"></span>
                                                <span x-show="dim.plus !== null" x-text="' +' + dim.plus + '/-' + dim.minus"></span>
                                            </span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-3 py-2 cursor-pointer" @click="openDimensiModal(i)">
                                    <input readonly :value="dim.method" placeholder="Metode" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded text-[10px] font-bold outline-none pointer-events-none">
                                </td>
                            </tr>

                        </template>


                        {{-- APPEARANCE --}}
                        <tr class="bg-red-50/50">
                            <td colspan="3" class="px-3 py-3 text-[9px] font-black text-red-600 uppercase tracking-widest">APPEARANCE</td>
                        </tr>
                        <template x-for="(app, i) in form.appearance" :key="'app_'+i">
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-3 py-2 text-center font-black text-slate-800 border-r border-slate-100" x-text="i+6"></td>
                                <td colspan="2" class="px-3 py-2 cursor-pointer" @click="openAppStandardModal(i)">
                                    <input readonly :value="form.appearance[i]" placeholder="Standar Appearance" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded text-[10px] font-bold outline-none focus:border-red-400 pointer-events-none">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

        </div>

        {{-- Modal Footer --}}
        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-white shrink-0">
            <button @click="showModal = false"
                    class="px-5 py-2.5 bg-white border-2 border-slate-200 text-slate-600 rounded-xl text-xs font-black hover:bg-slate-100 transition-all">
                Batal
            </button>
            <button @click="submitForm()" :disabled="saving"
                    class="px-8 py-2.5 bg-red-600 text-white rounded-xl text-xs font-black hover:bg-red-700 transition-all shadow-lg shadow-red-600/20 disabled:opacity-50 flex items-center gap-2">
                <template x-if="saving">
                    <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                </template>
                <span x-text="saving ? 'Menyimpan...' : (editingId ? 'Simpan Perubahan Master' : 'Buat Template Baru')"></span>
            </button>
        </div>
    </div>
</div>
</template>

    {{-- MODAL SETTING APPEARANCE --}}
    <div x-show="showAppStandardModal" x-cloak 
         class="fixed inset-0 z-[10000] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showAppStandardModal = false"></div>
        <div class="relative bg-white rounded-[2rem] w-full max-w-lg shadow-2xl overflow-hidden border border-slate-100 z-10 flex flex-col max-h-[90vh]"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100">
            
            <div class="px-6 py-5 bg-slate-50 border-b border-slate-100 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="text-base font-black text-slate-800 uppercase tracking-wider mb-1">Setting Standar Appearance</h3>
                    <p class="text-[10px] text-slate-500 font-bold">Pilih beberapa standar (opsional) & tambahkan custom</p>
                </div>
                <div class="px-3 py-1 bg-red-600 text-white text-[10px] font-black rounded-full shadow-sm" x-text="'ITEM #' + ((appStandardTargetRi||0) + 6)"></div>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Tipe Marking (Pilih jika ada)</label>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="m in ['X', 'R', 'L', 'XR', 'XL', 'UR', 'UL', 'JR', 'JL', '2L', '2R', 'MR', 'ML']" :key="m">
                            <label class="cursor-pointer">
                                <input type="radio" name="markingType" :value="m" x-model="appStandardMarking" class="peer hidden">
                                <div class="px-3 py-1.5 text-xs font-bold border-2 border-slate-200 text-slate-500 rounded-lg peer-checked:border-red-500 peer-checked:bg-red-50 peer-checked:text-red-700 transition-all hover:bg-slate-100">
                                    <span x-text="m"></span>
                                </div>
                            </label>
                        </template>
                        <label class="cursor-pointer">
                            <input type="radio" name="markingType" value="" x-model="appStandardMarking" class="peer hidden">
                            <div class="px-3 py-1.5 text-xs font-bold border-2 border-slate-200 text-slate-500 rounded-lg peer-checked:border-slate-500 peer-checked:bg-slate-200 peer-checked:text-slate-800 transition-all hover:bg-slate-100">
                                Tidak Ada
                            </div>
                        </label>
                    </div>
                </div>

                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 mt-2">Pre-defined Standar (Bisa Pilih > 1)</label>
                <div class="grid grid-cols-1 gap-2">
                    <template x-for="preset in appStandardPresets" :key="preset">
                        <label class="flex items-center gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:border-red-300 transition-colors" 
                               :class="appStandardSelected.includes(preset) ? 'border-red-500 bg-red-50/50 shadow-inner' : ''">
                            <input type="checkbox" :value="preset" x-model="appStandardSelected" class="w-4 h-4 text-red-600 border-2 border-slate-300 rounded focus:ring-red-500">
                            <span class="text-xs font-bold text-slate-700" x-text="preset"></span>
                        </label>
                    </template>
                </div>

                <div class="mt-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Standar Kustom / Lainnya</label>
                    <textarea x-model="appStandardCustom" rows="2" 
                              placeholder="Ketik standar tambahan di sini (Opsional)..."
                              class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-100 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:border-red-500 transition-all outline-none resize-none"></textarea>
                </div>
                
                {{-- Preview --}}
                <div class="bg-slate-900 rounded-2xl p-4 flex flex-col gap-1 border border-slate-800 shadow-inner mt-4">
                    <span class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Preview Standar:</span>
                    <span class="text-[11px] font-bold text-white leading-relaxed" x-text="[appStandardMarking ? ('Marking ' + appStandardMarking + ' harus jelas / nyata') : '', ...appStandardSelected, appStandardCustom.trim()].filter(Boolean).join(', ') || '(Belum ada yang dipilih)'"></span>
                </div>
            </div>

            <div class="bg-slate-50 p-5 border-t border-slate-100 flex flex-wrap gap-3 shrink-0">
                <button @click="clearAppStandard()" 
                        class="px-5 py-3 bg-rose-50 border-2 border-rose-100 rounded-xl text-[10px] font-black text-rose-600 uppercase tracking-widest hover:bg-rose-100 transition-all flex items-center gap-2">
                    Kosongkan
                </button>
                <div class="flex-1 flex justify-end gap-3">
                    <button @click="showAppStandardModal = false" class="px-5 py-3 bg-white border-2 border-slate-200 text-slate-600 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">Batal</button>
                    <button @click="saveAppStandardModal()" class="px-6 py-3 bg-red-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-600/20 hover:bg-red-700 active:scale-95 transition-all">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL SETTING DIMENSI --}}
    <div x-show="showDimModal" x-cloak 
         class="fixed inset-0 z-[10000] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDimModal = false"></div>
        <div class="relative bg-white rounded-[2.5rem] w-full max-w-md shadow-2xl overflow-hidden border border-slate-100 z-10"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100">
            
            <div class="px-8 pt-8 pb-6 bg-slate-50 border-b border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-wider">Setting Standar Dimensi</h3>
                    <div class="px-3 py-1 bg-red-600 text-white text-[10px] font-black rounded-full" x-text="'ITEM #' + (targetDimIdx + 1)"></div>
                </div>
                <p class="text-xs text-slate-400 font-bold">Atur nilai nominal dan batas toleransi untuk validasi otomatis.</p>
            </div>

            <div class="p-8 space-y-5">
                {{-- Nominal: Full-width stepper --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nominal / Nilai</label>
                        {{-- Step size pills --}}
                        <div class="flex gap-1">
                            <template x-for="s in [0.001, 0.01, 0.1, 1]" :key="s">
                                <button type="button"
                                        @click="tempDim._step = s"
                                        :class="tempDim._step == s ? 'bg-red-600 text-white shadow-sm' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                                        class="px-2 py-0.5 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all"
                                        x-text="s"></button>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button"
                                @click="tempDim.nominal = (Math.round((parseFloat(tempDim.nominal||0) - (tempDim._step||0.01)) * 100000) / 100000).toString()"
                                class="w-14 h-14 flex-shrink-0 flex items-center justify-center bg-slate-100 hover:bg-red-50 hover:text-red-600 border-2 border-slate-200 hover:border-red-200 rounded-2xl text-xl font-black text-slate-500 transition-all active:scale-90 select-none">-</button>
                        <input type="number"
                               inputmode="decimal"
                               step="any"
                               x-model="tempDim.nominal"
                               class="flex-1 min-w-0 px-4 py-3.5 bg-slate-50 border-2 border-slate-200 rounded-2xl text-2xl font-black text-slate-800 focus:bg-white focus:border-red-500 transition-all outline-none text-center"
                               placeholder="0.00">
                        <button type="button"
                                @click="tempDim.nominal = (Math.round((parseFloat(tempDim.nominal||0) + (tempDim._step||0.01)) * 100000) / 100000).toString()"
                                class="w-14 h-14 flex-shrink-0 flex items-center justify-center bg-slate-100 hover:bg-emerald-50 hover:text-emerald-600 border-2 border-slate-200 hover:border-emerald-200 rounded-2xl text-xl font-black text-slate-500 transition-all active:scale-90 select-none">+</button>
                    </div>
                </div>

                {{-- Toleransi Row --}}
                <div class="grid grid-cols-2 gap-4">
                    {{-- Plus --}}
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Toleransi (+)</label>
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="tempDim.plus = Math.max(0, Math.round((parseFloat(tempDim.plus||0) - (tempDim._step||0.01)) * 100000) / 100000)"
                                    class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-emerald-50 hover:bg-emerald-100 border-2 border-emerald-200 rounded-xl text-base font-black text-emerald-600 transition-all active:scale-90 select-none">-</button>
                            <input type="number" step="any" inputmode="decimal" x-model="tempDim.plus"
                                   class="flex-1 min-w-0 px-2 py-2.5 bg-emerald-50 border-2 border-emerald-100 rounded-xl text-sm font-black text-emerald-700 focus:bg-white focus:border-emerald-500 transition-all outline-none text-center">
                            <button type="button"
                                    @click="tempDim.plus = (Math.round((parseFloat(tempDim.plus||0) + (tempDim._step||0.01)) * 100000) / 100000)"
                                    class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-emerald-50 hover:bg-emerald-100 border-2 border-emerald-200 rounded-xl text-base font-black text-emerald-600 transition-all active:scale-90 select-none">+</button>
                        </div>
                    </div>
                    {{-- Minus --}}
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Toleransi (-)</label>
                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    @click="tempDim.minus = Math.max(0, Math.round((parseFloat(tempDim.minus||0) - (tempDim._step||0.01)) * 100000) / 100000)"
                                    class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-rose-50 hover:bg-rose-100 border-2 border-rose-200 rounded-xl text-base font-black text-rose-600 transition-all active:scale-90 select-none">-</button>
                            <input type="number" step="any" inputmode="decimal" x-model="tempDim.minus"
                                   class="flex-1 min-w-0 px-2 py-2.5 bg-rose-50 border-2 border-rose-100 rounded-xl text-sm font-black text-rose-700 focus:bg-white focus:border-rose-500 transition-all outline-none text-center">
                            <button type="button"
                                    @click="tempDim.minus = (Math.round((parseFloat(tempDim.minus||0) + (tempDim._step||0.01)) * 100000) / 100000)"
                                    class="w-10 h-10 flex-shrink-0 flex items-center justify-center bg-rose-50 hover:bg-rose-100 border-2 border-rose-200 rounded-xl text-base font-black text-rose-600 transition-all active:scale-90 select-none">+</button>
                        </div>
                    </div>
                </div>

                {{-- Metode --}}
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Metode Pengecekan</label>
                    <select x-model="tempDim.method" 
                            class="w-full px-4 py-3 bg-slate-50 border-2 border-slate-100 rounded-xl text-sm font-bold text-slate-700 focus:bg-white focus:border-red-500 transition-all outline-none">
                        <option value="">Pilih Metode</option>
                        <option value="Vernier Caliper">Vernier Caliper</option>
                        <option value="Check Jig">Check Jig</option>
                        <option value="Visual">Visual</option>
                        <option value="Micro Meter">Micro Meter</option>
                    </select>
                </div>

                {{-- Preview --}}
                <div class="bg-slate-900 rounded-2xl p-4 flex flex-col items-center justify-center gap-1 border border-slate-800 shadow-inner">
                    <span class="text-[8px] font-black text-slate-500 uppercase tracking-widest">Preview Tampilan:</span>
                    <span class="text-sm font-black text-white" x-text="
                        (tempDim.item && tempDim.item.toUpperCase().includes('SPEC MATERIAL')) 
                        ? (tempDim.nominal || '') 
                        : ((tempDim.item && tempDim.item.toUpperCase().includes('JUMLAH HOLE')) 
                            ? ((tempDim.nominal ? tempDim.nominal + ' pcs' : '')) 
                            : ((tempDim.nominal ? 'Ø ' + tempDim.nominal + ' mm' : '') + (tempDim.plus || tempDim.minus ? ' +' + (tempDim.plus||0) + '/-' + (tempDim.minus||0) : ''))
                          )
                    "></span>
                </div>
            </div>

            <div class="bg-slate-50 p-6 flex flex-wrap gap-3">
                <button @click="clearDimSettings()" 
                        class="px-5 py-3.5 bg-rose-50 border-2 border-rose-100 rounded-2xl text-[10px] font-black text-rose-600 uppercase tracking-widest hover:bg-rose-100 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    Hapus
                </button>
                <button @click="showDimModal = false" 
                        class="flex-1 py-3.5 bg-white border-2 border-slate-200 rounded-2xl text-[10px] font-black text-slate-400 uppercase tracking-widest hover:bg-slate-100 transition-all">
                    Batal
                </button>
                <button @click="saveDimSettings()" 
                        class="flex-[2] py-3.5 bg-red-600 rounded-2xl text-[10px] font-black text-white uppercase tracking-widest shadow-lg shadow-red-600/30 hover:bg-red-700 transition-all active:scale-95">
                    ✓ Simpan Standar
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL KONFIRMASI SYNC --}}
    <div x-show="showSyncModal" x-cloak 
         class="fixed inset-0 z-[10000] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showSyncModal = false"></div>
        <div class="relative bg-white rounded-[2rem] w-full max-w-sm shadow-2xl p-6 z-10 text-center border border-slate-100"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100">
            <!-- <div class="w-16 h-16 bg-blue-50 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-red-100 shadow-inner">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div> -->
            <h3 class="text-base font-black text-slate-800 uppercase tracking-widest mb-2">Tarik Data dari LI?</h3>
            <p class="text-[11px] font-bold text-slate-500 mb-6 leading-relaxed">Aksi ini akan menyinkronkan Part No baru dari Lembar Inspeksi ke Master Template.<br><br><span class="text-red-600 font-black">Data yang sudah Anda edit tidak akan tertimpa/terhapus.</span></p>
            <div class="flex gap-3">
                <button @click="showSyncModal = false" class="flex-1 py-3 bg-slate-50 text-slate-600 border-2 border-slate-200 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-slate-100 transition-colors">Batal</button>
                <button @click="executeSync()" class="flex-1 py-3 bg-red-600 text-white border-2 border-red-600 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-red-600/30 hover:bg-red-700 transition-all active:scale-95">Ya, Tarik Data</button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function liMasterTemplate() {
    return {
        templates: [],
        filteredTemplates: [],
        uniqueGroups: [],
        activeGroup: 'Semua',
        loading: true,
        search: '',
        currentPage: 1,
        perPage: 10,
        
        get paginatedTemplates() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredTemplates.slice(start, start + this.perPage);
        },
        
        get totalPages() {
            return Math.ceil(this.filteredTemplates.length / this.perPage) || 1;
        },
        toast: null,
        showModal: false,
        saving: false,
        syncing: false,
        editingId: null,
        form: {},


        showDimModal: false,
        targetDimIdx: 0,
        tempDim: { nominal: '', plus: '', minus: '', method: '', item: '', _step: 0.01 },

        showSyncModal: false,

        showAppStandardModal: false,
        appStandardTargetRi: 0,
        appStandardMarking: '',
        appStandardSelected: [],
        appStandardCustom: '',
        appStandardPresets: ['Tidak Pecah', 'Tidak Neck', 'Tidak Karat', 'Tidak Pecok', 'Tidak Benjol', 'Tidak Gelombang', 'Tidak Sockline', 'Tidak Baret', 'Tidak Burry', 'Tidak Keriput', 'Tidak Mencuat', 'Tidak Penyok', 'Flange Tidak Miring'],

        openDimensiModal(idx) {
            this.targetDimIdx = idx;
            const dim = this.form.dimensi[idx];
            // Prefer structured fields; fall back to parsing the display value
            let nominal = dim.nominal !== null && dim.nominal !== undefined ? String(dim.nominal) : '';
            let plus    = dim.plus    !== null && dim.plus    !== undefined ? String(dim.plus)    : '';
            let minus   = dim.minus   !== null && dim.minus   !== undefined ? String(dim.minus)   : '';

            // Fallback: try to parse from the display string if structured fields are empty
            if (!nominal && dim.value) {
                const m = dim.value.match(/[\u00d8Ø]?\s*([0-9.,]+)\s*(?:mm)?(?:\s*[+]([0-9.,]+))?(?:\/-([0-9.,]+))?/);
                if (m && m[1]) {
                    nominal = m[1].replace(',', '.');
                    plus    = m[2] ? m[2].replace(',', '.') : '';
                    minus   = m[3] ? m[3].replace(',', '.') : '';
                } else if (dim.value.includes('pcs')) {
                    nominal = dim.value.replace(/[^0-9.]/g, '');
                }
            }

            this.tempDim = {
                nominal,
                plus,
                minus,
                method: dim.method || '',
                item:   dim.item   || '',
                _step: 0.01
            };
            this.showDimModal = true;
        },
        saveDimSettings() {
            let val = '';
            if (this.tempDim.nominal) {
                if (this.tempDim.item && this.tempDim.item.toUpperCase().includes('HOLE')) {
                    val = this.tempDim.nominal + ' pcs';
                } else {
                    val = 'Ø ' + this.tempDim.nominal + ' mm';
                    if (this.tempDim.plus || this.tempDim.minus) {
                        val += ` +${this.tempDim.plus||0}/-${this.tempDim.minus||0}`;
                    }
                }
            }
            this.form.dimensi[this.targetDimIdx].value   = val;
            this.form.dimensi[this.targetDimIdx].nominal  = this.tempDim.nominal  !== '' ? parseFloat(this.tempDim.nominal)  : null;
            this.form.dimensi[this.targetDimIdx].plus     = this.tempDim.plus     !== '' ? parseFloat(this.tempDim.plus)     : null;
            this.form.dimensi[this.targetDimIdx].minus    = this.tempDim.minus    !== '' ? parseFloat(this.tempDim.minus)    : null;
            this.form.dimensi[this.targetDimIdx].method   = this.tempDim.method;
            this.form.dimensi[this.targetDimIdx].item     = this.tempDim.item;
            this.showDimModal = false;
        },
        clearDimSettings() {
            this.form.dimensi[this.targetDimIdx].value = '';
            this.form.dimensi[this.targetDimIdx].method = '';
            this.form.dimensi[this.targetDimIdx].item = '';
            this.showDimModal = false;
        },

        openAppStandardModal(idx) {
            this.appStandardTargetRi = idx;
            const current = this.form.appearance[idx] || '';
            this.appStandardMarking = '';
            this.appStandardSelected = [];
            this.appStandardCustom = '';
            
            if (current) {
                let parts = current.split(',').map(s=>s.trim());
                parts.forEach(p => {
                    if (p.startsWith('Marking') && p.includes('harus jelas')) {
                        const m = p.match(/Marking\s+(.*?)\s+harus/);
                        if(m) this.appStandardMarking = m[1];
                    } else if (this.appStandardPresets.includes(p)) {
                        this.appStandardSelected.push(p);
                    } else {
                        this.appStandardCustom += (this.appStandardCustom ? ', ' : '') + p;
                    }
                });
            }
            this.showAppStandardModal = true;
        },
        saveAppStandardModal() {
            let parts = [];
            if (this.appStandardMarking) parts.push(`Marking ${this.appStandardMarking} harus jelas / nyata`);
            parts.push(...this.appStandardSelected);
            if (this.appStandardCustom.trim()) parts.push(this.appStandardCustom.trim());
            
            this.form.appearance[this.appStandardTargetRi] = parts.join(', ');
            this.showAppStandardModal = false;
        },
        clearAppStandard() {
            this.appStandardMarking = '';
            this.appStandardSelected = [];
            this.appStandardCustom = '';
            this.form.appearance[this.appStandardTargetRi] = '';
            this.showAppStandardModal = false;
        },

        async init() {
            await this.fetchTemplates();
        },

        showToast(type, msg) {
            this.toast = { type, msg };
            setTimeout(() => { this.toast = null; }, 3500);
        },

        async fetchTemplates() {
            this.loading = true;
            try {
                const url = '/api/li-templates' + (this.search ? '?q=' + encodeURIComponent(this.search) : '');
                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                this.templates = await res.json();
                this.buildUniqueGroups();
                this.filterTemplates();
            } catch(e) {
                this.showToast('error', 'Gagal memuat data template.');
            } finally {
                this.loading = false;
                if (typeof window.hideSkeleton === 'function') window.hideSkeleton();
            }
        },

        filterTemplates() {
            this.currentPage = 1; // Reset halaman ke 1 saat filter berubah
            if (this.activeGroup === 'Semua') {
                this.filteredTemplates = this.templates;
            } else {
                this.filteredTemplates = this.templates.filter(t =>
                    this.getJobPrefix(t.job_no) === this.activeGroup
                );
            }
        },

        getJobPrefix(jobNo) {
            if (!jobNo) return '—';
            const dash = jobNo.indexOf('-');
            return dash > 0 ? jobNo.substring(0, dash).toUpperCase() : jobNo.toUpperCase();
        },

        getBadgeClass(prefix) {
            if (!prefix || prefix === '—') return 'bg-slate-100 text-slate-600 border-slate-200';
            
            // Hardcode warna untuk prefix umum agar pasti berbeda jauh
            const known = {
                'GT': 'bg-blue-50 text-blue-700 border-blue-200',
                'K': 'bg-rose-50 text-rose-700 border-rose-200',
                'AES': 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'RCS': 'bg-violet-50 text-violet-700 border-violet-200'
            };
            if (known[prefix]) return known[prefix];

            const palettes = [
                'bg-amber-50 text-amber-700 border-amber-200',
                'bg-cyan-50 text-cyan-700 border-cyan-200',
                'bg-orange-50 text-orange-700 border-orange-200',
                'bg-teal-50 text-teal-700 border-teal-200',
                'bg-pink-50 text-pink-700 border-pink-200',
                'bg-indigo-50 text-indigo-700 border-indigo-200',
                'bg-lime-50 text-lime-700 border-lime-200',
                'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
            ];
            
            // Hash sederhana dari string prefix
            let hash = 0;
            for (let i = 0; i < prefix.length; i++) hash = (hash * 31 + prefix.charCodeAt(i)) & 0xffff;
            return palettes[hash % palettes.length];
        },

        buildUniqueGroups() {
            const seen = new Set();
            this.templates.forEach(t => {
                const prefix = this.getJobPrefix(t.job_no);
                if (prefix && prefix !== '—') seen.add(prefix);
            });
            // Sort alphabetically so tabs are consistent
            this.uniqueGroups = Array.from(seen).sort();
        },

        blankForm() {
            return {
                job_no: '',
                part_no: '',
                part_name: '',

                type: '',
                spec_material: '',
                type_pallet: '',
                image_path: '',
                tact_time: 0,
                ct_dimensi: 0,
                ct_tanpa_dimensi: 0,
                dimensi: Array.from({ length: 7 }, (_, i) => ({ item: '', value: '', method: '', nominal: null, plus: null, minus: null })),
                appearance: Array(9).fill(''),
            };
        },


        openCreateModal() {
            this.editingId = null;
            this.form = this.blankForm();
            this.showModal = true;
        },

        openEditModal(t) {
            this.editingId = t.id;
            this.form = {
                job_no: t.job_no || '',
                part_no: t.part_no,
                part_name: t.part_name || '',

                type: t.type || '',
                spec_material: t.spec_material || '',
                type_pallet: t.type_pallet || '',
                image_path: t.image_path || '',
                tact_time: t.tact_time || 0,
                ct_dimensi: t.ct_dimensi || 0,
                ct_tanpa_dimensi: t.ct_tanpa_dimensi || 0,
                dimensi: Array.from({ length: 7 }, (_, i) => ({
                    item:    t['dimensi' + (i+1) + '_item']   || '',
                    value:   t['dimensi' + (i+1)]             || '',
                    method:  t['dimensi' + (i+1) + '_method'] || '',
                    nominal: t['dimensi' + (i+1) + '_nominal'] !== null && t['dimensi' + (i+1) + '_nominal'] !== undefined ? t['dimensi' + (i+1) + '_nominal'] : null,
                    plus:    t['dimensi' + (i+1) + '_plus']    !== null && t['dimensi' + (i+1) + '_plus']    !== undefined ? t['dimensi' + (i+1) + '_plus']    : null,
                    minus:   t['dimensi' + (i+1) + '_minus']   !== null && t['dimensi' + (i+1) + '_minus']   !== undefined ? t['dimensi' + (i+1) + '_minus']   : null,
                })),
                appearance: Array.from({ length: 9 }, (_, i) => t['appearance' + (i+6)] || ''),
            };
            this.showModal = true;
        },

        handleSketchUpload(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.form.image_path = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        async submitForm() {

            if (!this.form.part_no.trim()) {
                this.showToast('error', 'Part No wajib diisi!');
                return;
            }
            this.saving = true;
            try {
                const payload = {
                    job_no: this.form.job_no,
                    part_no: this.form.part_no,
                    part_name: this.form.part_name,

                    type: this.form.type,
                    spec_material: this.form.spec_material,
                    type_pallet: this.form.type_pallet,
                    tact_time: this.form.tact_time,
                    ct_dimensi: this.form.ct_dimensi,
                    ct_tanpa_dimensi: this.form.ct_tanpa_dimensi,
                };
                if (this.form.image_path && this.form.image_path.startsWith('data:')) {
                    payload.image_path = this.form.image_path;
                }

                this.form.dimensi.forEach((d, i) => {
                    payload['dimensi' + (i+1)]           = d.value;
                    payload['dimensi' + (i+1) + '_item']    = d.item;
                    payload['dimensi' + (i+1) + '_method']  = d.method;
                    payload['dimensi' + (i+1) + '_nominal'] = d.nominal !== null && d.nominal !== undefined ? d.nominal : null;
                    payload['dimensi' + (i+1) + '_plus']    = d.plus    !== null && d.plus    !== undefined ? d.plus    : null;
                    payload['dimensi' + (i+1) + '_minus']   = d.minus   !== null && d.minus   !== undefined ? d.minus   : null;
                });
                this.form.appearance.forEach((a, i) => {
                    payload['appearance' + (i+6)] = a;
                });

                const res = await fetch('/api/li-templates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal menyimpan.');
                this.showToast('success', data.message || 'Template disimpan!');
                this.showModal = false;
                await this.fetchTemplates();
            } catch(e) {
                this.showToast('error', e.message || 'Terjadi kesalahan.');
            } finally {
                this.saving = false;
            }
        },

        async deleteTemplate(t) {
            if (!confirm('Hapus template untuk Part No: ' + t.part_no + '?')) return;
            try {
                const res = await fetch('/api/li-templates/' + encodeURIComponent(t.part_no), {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                if (res.ok) {
                    this.showToast('success', 'Template berhasil dihapus.');
                    await this.fetchTemplates();
                } else {
                    this.showToast('error', 'Gagal menghapus template.');
                }
            } catch(e) {
                this.showToast('error', 'Terjadi kesalahan.');
            }
        },

        syncFromLi() {
            this.showSyncModal = true;
        },

        async executeSync() {
            this.showSyncModal = false;
            this.syncing = true;
            try {
                const res = await fetch('/api/li-templates/sync-from-li', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                    }
                });
                const data = await res.json();
                if (res.ok) {
                    this.showToast('success', data.message || 'Sinkronisasi berhasil.');
                    await this.fetchTemplates();
                } else {
                    this.showToast('error', data.message || 'Gagal sinkronisasi.');
                }
            } catch(e) {
                this.showToast('error', 'Terjadi kesalahan jaringan.');
            } finally {
                this.syncing = false;
            }
        },
    };
}

</script>
@endpush

@endsection

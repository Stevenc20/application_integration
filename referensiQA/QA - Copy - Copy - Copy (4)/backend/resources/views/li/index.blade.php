<x-app-layout>
    <x-slot name="title">Daftar Master LI</x-slot>
    <x-slot name="pageTitle">Daftar Master LI</x-slot>

    <div x-data="liList()" x-init="init()" class="space-y-4 sm:space-y-6 md:space-y-8" :class="archiveMode ? 'bg-slate-50/50 -m-4 p-4 rounded-3xl' : ''">
        {{-- 🔔 Notifikasi antrian produksi dipindah ke dropdown bell icon di navbar (app-layout.blade.php) --}}

        {{-- Banner Arsip --}}
        <div x-show="archiveMode" x-cloak
             class="bg-slate-800 text-white p-4 rounded-[24px] shadow-lg flex items-center gap-4">
            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <div>
                <h3 class="font-black text-lg">Mode Arsip Aktif</h3>
                <p class="text-xs text-slate-300">Menampilkan data Master Template yang berumur lebih dari 2 bulan (Soft Archive).</p>
            </div>
        </div>

        <!-- Header Action Section -->
        <div class="bg-white p-4 rounded-[24px] border border-slate-100 shadow-lg shadow-slate-200/40 space-y-3">
            {{-- Baris 1: Search + Tombol --}}
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="relative flex-1 w-full group">
                    <span class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </span>
                    <input
                        type="text"
                        x-model="search"
                        placeholder="Cari No. Form, Part Name, Job No, Lokasi…"
                        class="w-full pl-12 pr-4 py-3.5 bg-slate-50/50 border border-slate-200 rounded-[18px] focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-sm font-semibold shadow-sm text-slate-700 placeholder-slate-400"
                    >
                </div>
                <div class="flex items-center gap-3 w-full md:w-auto">
                    <button @click="loadData()" class="flex-1 md:flex-none p-3.5 bg-white border border-slate-200 rounded-[18px] text-slate-500 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-all shadow-sm active:scale-95 flex items-center justify-center" title="Refresh Data">
                        <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </button>
                    @if(auth()->user()->role === 'Admin' || auth()->user()->role === 'Supervisor')
                    <button @click="toggleArchiveMode()" 
                            class="flex-1 md:flex-none flex items-center justify-center gap-2 px-5 py-3.5 font-black rounded-[18px] transition-all shadow-sm active:scale-95 whitespace-nowrap"
                            :class="archiveMode ? 'bg-slate-800 text-white hover:bg-slate-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        <span x-text="archiveMode ? 'Tutup Arsip' : 'Arsip'"></span>
                    </button>
                    @endif
                    @if(auth()->user()->role === 'Leader' || auth()->user()->role === 'Admin')
                    <div class="relative flex-1 md:flex-none">
                        <input type="file" x-ref="excelInput" @change="uploadExcel" accept=".xlsx,.xls" class="hidden">
                        <button type="button" @click="$refs.excelInput.click()" 
                                class="w-full flex items-center justify-center gap-2 px-6 py-3.5 bg-emerald-600 text-white font-black rounded-[18px] hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-600/30 active:scale-95 whitespace-nowrap"
                                :disabled="uploadingExcel">
                            <svg x-show="!uploadingExcel" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                            <svg x-show="uploadingExcel" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="uploadingExcel ? 'Mengunggah...' : 'Upload Excel'"></span>
                        </button>
                    </div>

                    <a href="{{ url('/li/create?new=1') }}" x-show="!archiveMode" class="flex-1 md:flex-none flex items-center justify-center gap-2 px-6 py-3.5 bg-red-600 text-white font-black rounded-[18px] hover:bg-red-700 transition-all shadow-lg shadow-red-600/30 active:scale-95 whitespace-nowrap">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                        Buat Baru
                    </a>
                    @endif
                </div>
            </div>

            {{-- Baris 2: Filter Inline --}}
            <div class="flex flex-col sm:flex-row gap-3 items-center">
                {{-- Filter Bulan --}}
                <select x-model="filterBulan"
                        class="flex-1 text-xs font-bold text-slate-600 bg-slate-50 border border-slate-200 rounded-[14px] px-4 py-2.5 outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all cursor-pointer">
                    <option value="">Semua Bulan</option>
                    <template x-for="opt in bulanOptions" :key="opt.value">
                        <option :value="opt.value" x-text="opt.label"></option>
                    </template>
                </select>

                {{-- Filter Line --}}
                <select x-model="filterLine"
                        class="flex-1 text-xs font-bold text-slate-600 bg-slate-50 border border-slate-200 rounded-[14px] px-4 py-2.5 outline-none focus:border-blue-400 focus:ring-4 focus:ring-blue-500/10 transition-all cursor-pointer">
                    <option value="">Semua Line</option>
                    <template x-for="line in uniqueLines" :key="line">
                        <option :value="line" x-text="line"></option>
                    </template>
                </select>

                {{-- Reset Filter (muncul jika ada filter aktif) --}}
                <template x-if="filterBulan || filterLine">
                    <button @click="filterBulan = ''; filterLine = ''"
                            class="flex items-center gap-1.5 px-4 py-2.5 text-xs font-black text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-[14px] transition-all whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        Reset Filter
                    </button>
                </template>
            </div>
        </div>


        <!-- Filter Tabs -->
        <div class="flex gap-2 overflow-x-auto pb-4 scrollbar-hide snap-x">
            <template x-for="tab in availableTabs" :key="tab.id">
                <button 
                    @click="activeFilter = tab.id"
                    class="px-4 py-2 rounded-full font-black text-[10px] md:text-[11px] uppercase tracking-wider whitespace-nowrap transition-all border-2 snap-start flex items-center gap-1.5"
                    :class="activeFilter === tab.id 
                        ? 'bg-slate-800 border-slate-800 text-white shadow-lg shadow-slate-800/20' 
                        : 'bg-white border-slate-200 text-slate-500 hover:border-slate-300 hover:bg-slate-50'"
                    x-html="tab.label"
                ></button>
            </template>
        </div>

        <!-- Mobile / tablet: kartu -->
        <div class="md:hidden space-y-3">
            <template x-for="item in paginatedData" :key="'m-' + item.id">
                <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm active:bg-slate-50/80 transition-colors">
                    <div class="flex justify-between items-start gap-2 mb-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-wide" x-text="item.no_form"></p>
                            <p class="text-[15px] font-black text-slate-800 tracking-tight leading-tight mt-0.5 truncate" x-text="item.part_name || '-'"></p>
                            
                            <template x-if="!hasFormula(item)">
                                <div class="mt-1">
                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-700 border border-amber-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        Formula Kosong
                                    </span>
                                </div>
                            </template>

                            <div class="flex flex-wrap items-center gap-2 mt-1.5 text-[10px] font-bold">
                                <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded" x-text="item.job_no || '-'"></span>
                                <span class="px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded" x-text="item.part_no || '-'"></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-slate-100 flex items-center justify-center text-slate-500">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-700 uppercase" x-text="item.type || '-'"></p>
                                <p class="text-[9px] font-bold text-slate-400">Tipe Part</p>
                            </div>
                        </div>
                        <span class="shrink-0 px-2.5 py-1 rounded-lg text-[9px] font-bold uppercase border flex items-center gap-1.5"
                              :style="'background:' + getCustomStatus(item).bg + '; color:' + getCustomStatus(item).color + '; border-color:' + getCustomStatus(item).border"
                              x-html="getCustomStatus(item).label"></span>
                    </div>

                    <template x-if="item.status === 'revision'">
                        <p class="text-[9px] text-amber-600 font-semibold mt-2 px-2 py-1 bg-amber-50 rounded" x-text="'→ ' + item.catatan"></p>
                    </template>
                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <a :href="`{{ url('/li') }}/${item.id}/edit`"
                           class="flex-1 min-h-[44px] flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-black">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7"/></svg>
                            Lihat / Proses
                        </a>
                        <button type="button" x-show="canDelete(item)" @click="deleteLi(item)"
                                class="min-h-[44px] min-w-[44px] flex items-center justify-center p-2.5 text-rose-600 bg-rose-50 border border-rose-200 rounded-xl hover:bg-rose-100 transition-all"
                                title="Hapus LI">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>
            </template>
            <template x-if="filteredData.length === 0 && !loading">
                <p class="text-center text-slate-400 font-bold py-12">Data tidak ditemukan</p>
            </template>

            <!-- Mobile Pagination -->
            <template x-if="totalPages > 1 && filteredData.length > 0">
                <div class="flex items-center justify-between py-4 border-t border-slate-100">
                    <button @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1" class="w-10 h-10 flex items-center justify-center rounded-2xl bg-white border border-slate-200 text-slate-400 hover:text-slate-600 disabled:opacity-50 transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </button>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest" x-text="`Hal ${currentPage} / ${totalPages}`"></span>
                    <button @click="if(currentPage < totalPages) currentPage++" :disabled="currentPage === totalPages" class="w-10 h-10 flex items-center justify-center rounded-2xl bg-white border border-slate-200 text-slate-400 hover:text-slate-600 disabled:opacity-50 transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <!-- Desktop: tabel -->
        <div class="hidden md:block bg-white border border-slate-100 rounded-[24px] shadow-xl shadow-slate-200/40 overflow-hidden">
            <div class="overflow-x-auto scroll-hint">
                <table class="w-full text-left border-collapse min-w-[800px]">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-100">
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Dokumen</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">No. Job</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">No. Part</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Nama Part</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Type Part</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Status</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="item in paginatedData" :key="item.id">
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-6 py-5">
                                    <p class="text-[11px] font-black text-slate-800 mb-0.5" x-text="item.no_form || '-'"></p>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase" x-text="new Date(item.created_at).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})"></p>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="inline-flex items-center px-2 py-1 rounded bg-slate-100 text-slate-700 text-[11px] font-black tracking-wide" x-text="item.job_no || '-'"></span>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="text-[12px] font-black text-blue-600 tracking-wide" x-text="item.part_no || '-'"></span>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="text-[13px] font-black text-slate-800 tracking-tight" x-text="item.part_name || '-'"></p>
                                    <template x-if="!hasFormula(item)">
                                        <div class="mt-1.5">
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-700 border border-amber-200" title="Formula Sampling & Timer belum diisi">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                                Formula Kosong
                                            </span>
                                        </div>
                                    </template>
                                </td>
                                <td class="px-6 py-5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-bold uppercase" x-text="item.type || '-'"></span>
                                </td>
                                <td class="px-6 py-5">
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border"
                                        :style="'background:' + getCustomStatus(item).bg + '; color:' + getCustomStatus(item).color + '; border-color:' + getCustomStatus(item).border"
                                        x-html="getCustomStatus(item).label"
                                    ></span>
                                    <template x-if="item.status === 'revision'">
                                        <p class="text-[9px] text-amber-600 font-semibold mt-1.5 truncate max-w-[160px]" x-text="'→ ' + item.catatan"></p>
                                    </template>
                                </td>
                                <td class="px-6 py-5 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        
                                        {{-- Admin / Leader: edit --}}
                                        <template x-if="role === 'Leader' || role === 'Admin'">
                                            <a :href="`{{ url('/li') }}/${item.id}/edit`"
                                               class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all"
                                               title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </a>
                                        </template>

                                        {{-- Admin / Leader: hapus (Leader terbatas status awal) --}}
                                        <template x-if="canDelete(item)">
                                            <button type="button" @click="deleteLi(item)"
                                                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                                                    title="Hapus LI">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </template>

                                        {{-- Tombol Restore (hanya saat mode arsip) --}}
                                        <template x-if="archiveMode && (role === 'Admin' || role === 'Supervisor')">
                                            <button @click="restoreTask(item)"
                                                    class="flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white 
                                                           rounded-lg text-[10px] font-black hover:bg-emerald-700 transition-all shadow-md">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                                Restore
                                            </button>
                                        </template>

                                        {{-- Group Leader: tombol verifikasi QC --}}
                                        <template x-if="role === 'Group Leader' && item.status === 'waiting_qc_approval' && !item.paraf_gl_bottom">
                                            <a :href="`{{ url('/li') }}/${item.id}/edit`"
                                               class="flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-[10px] font-black hover:bg-indigo-700 transition-all">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                                Verifikasi GL
                                            </a>
                                        </template>

                                        {{-- QC Foreman (Pak Dedy): tombol verifikasi QC item check --}}
                                        <template x-if="role === 'Foreman' && isQCForeman && item.status === 'waiting_qc_approval' && !item.paraf_foreman_bottom">
                                            <a :href="`{{ url('/li') }}/${item.id}/edit`"
                                               class="flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-[10px] font-black hover:bg-emerald-700 transition-all">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                                Verifikasi QC
                                            </a>
                                        </template>

                                        {{-- Production Foreman (Pak Azriel, dll): tombol Checked LI --}}
                                        <template x-if="role === 'Foreman' && !isQCForeman && item.status === 'waiting_qc_approval' && !item.paraf_foreman_bottom">
                                            <a :href="`{{ url('/li') }}/${item.id}/edit`"
                                               class="flex items-center gap-1 px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-[10px] font-black hover:bg-indigo-700 transition-all">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                Verifikasi FM
                                            </a>
                                        </template>

                                        {{-- Production Foreman (Pak Azriel, dll): tombol Checked dokumen LI --}}
                                        <template x-if="role === 'Foreman' && !isQCForeman && (item.status === 'submitted' || item.status === 'waiting_foreman')">
                                            <a :href="`{{ url('/li') }}/${item.id}/edit`"
                                               class="flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-[10px] font-black hover:bg-emerald-700 transition-all">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                Checked
                                            </a>
                                        </template>

                                        {{-- Supervisor: tombol Approve --}}
                                        <template x-if="role === 'Supervisor' && item.status === 'waiting_supervisor'">
                                            <a :href="`{{ url('/li') }}/${item.id}/edit`"
                                               class="flex items-center gap-1 px-3 py-1.5 bg-purple-600 text-white 
                                                      rounded-lg text-[10px] font-black hover:bg-purple-700 transition-all">
                                                ✓ Approve
                                            </a>
                                        </template>



                                        {{-- Semua role: tombol lihat --}}
                                        <a :href="`{{ url('/li') }}/${item.id}/edit`"
                                           class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-all"
                                           title="Lihat">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <template x-if="filteredData.length === 0">
                <div class="py-20 text-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-slate-400 font-bold">Data tidak ditemukan</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">Coba kata kunci lain</p>
                </div>
            </template>

            <!-- Desktop Pagination -->
            <template x-if="totalPages > 1 && filteredData.length > 0">
                <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest" x-text="`Halaman ${currentPage} dari ${totalPages} (${filteredData.length} Data)`"></span>
                    <div class="flex items-center gap-1.5">
                        <button @click="if(currentPage > 1) currentPage--" :disabled="currentPage === 1" class="w-8 h-8 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-slate-600 hover:border-slate-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        
                        <div class="flex gap-1">
                            <template x-for="page in pageNumbers" :key="page">
                                <button @click="if(page !== '...') currentPage = page" 
                                        class="min-w-[32px] h-8 px-2 flex items-center justify-center rounded-xl text-[11px] font-black transition-all"
                                        :class="currentPage === page ? 'bg-slate-800 text-white shadow-md' : (page === '...' ? 'cursor-default text-slate-400' : 'bg-white border border-slate-200 text-slate-500 hover:border-slate-300 shadow-sm')"
                                        x-text="page" :disabled="page === '...'"></button>
                            </template>
                        </div>

                        <button @click="if(currentPage < totalPages) currentPage++" :disabled="currentPage === totalPages" class="w-8 h-8 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-slate-600 hover:border-slate-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Toast --}}
        <div x-show="toast" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="fixed bottom-24 md:bottom-8 left-1/2 -translate-x-1/2 z-[10001] px-5 py-3 rounded-2xl shadow-2xl text-sm font-bold border flex items-center gap-2 max-w-[90vw]"
             :class="toast?.type === 'success' ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-rose-600 text-white border-rose-500'">
            <span x-text="toast?.type === 'success' ? '✓' : '✕'"></span>
            <span x-text="toast?.msg"></span>
        </div>

        {{-- Modal konfirmasi hapus LI --}}
        <div x-show="showDeleteModal" x-cloak
             class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
             @keydown.escape.window="closeDeleteModal()">
            <div x-show="showDeleteModal"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.outside="closeDeleteModal()"
                 class="bg-white w-full max-w-md rounded-[28px] shadow-2xl overflow-hidden border border-slate-200/80">
                <div class="p-6 sm:p-7 border-b border-slate-100 bg-gradient-to-br from-rose-50 to-white">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 shrink-0 bg-rose-100 border-2 border-rose-200 rounded-2xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-black text-slate-800 leading-tight">Hapus Lembar Inspeksi?</h3>
                            <p class="text-xs text-slate-500 font-semibold mt-1">Tindakan ini tidak dapat dibatalkan dari daftar.</p>
                        </div>
                        <button type="button" @click="closeDeleteModal()" :disabled="deleting"
                                class="shrink-0 w-9 h-9 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <div class="p-6 sm:p-7 space-y-4" x-show="deleteTarget">
                    <div class="rounded-2xl border-2 border-slate-100 bg-slate-50 p-4 space-y-2">
                        <div class="flex justify-between gap-2 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <span>No. Form</span>
                            <span class="text-slate-600 font-mono normal-case tracking-normal" x-text="deleteTarget?.no_form || '—'"></span>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Part</p>
                            <p class="text-sm font-extrabold text-slate-800 leading-snug" x-text="deleteTarget?.part_name || '—'"></p>
                        </div>
                        <div class="flex flex-wrap gap-2 pt-1">
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white border border-slate-200 text-slate-600"
                                  x-text="'JOB: ' + (deleteTarget?.job_no || '—')"></span>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[10px] font-bold border"
                                  :style="deleteTarget ? ('background:' + getCustomStatus(deleteTarget).bg + '; color:' + getCustomStatus(deleteTarget).color + '; border-color:' + getCustomStatus(deleteTarget).border) : ''"
                                  x-html="deleteTarget ? getCustomStatus(deleteTarget).label : ''"></span>
                        </div>
                    </div>

                    <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 font-semibold leading-relaxed"
                       x-show="role === 'Leader'">
                        Leader hanya dapat menghapus LI tahap awal (Draft / Revisi / menunggu Foreman).
                    </p>
                    <p class="text-xs text-slate-600 bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 font-medium leading-relaxed"
                       x-show="role === 'Admin'">
                        Data akan hilang dari daftar. Record tetap tersimpan sebagai soft-delete di database.
                    </p>

                    <p x-show="deleteError" class="text-xs font-bold text-rose-600 bg-rose-50 border border-rose-200 rounded-xl px-4 py-3" x-text="deleteError"></p>
                </div>

                <div class="p-4 sm:p-6 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row gap-3">
                    <button type="button" @click="closeDeleteModal()" :disabled="deleting"
                            class="flex-1 min-h-[48px] px-5 py-3 rounded-2xl border-2 border-slate-200 bg-white text-sm font-black text-slate-600 hover:bg-slate-100 transition-all disabled:opacity-50">
                        Batal
                    </button>
                    <button type="button" @click="confirmDelete()" :disabled="deleting"
                            class="flex-1 min-h-[48px] px-5 py-3 rounded-2xl bg-rose-600 text-white text-sm font-black shadow-lg shadow-rose-600/25 hover:bg-rose-700 transition-all disabled:opacity-60 flex items-center justify-center gap-2 active:scale-[0.98]">
                        <svg x-show="deleting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="deleting ? 'Menghapus...' : 'Ya, Hapus'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function liList() {
            return {
                data: [],
                search: '',
                filterBulan: '',
                filterLine: '',
                activeFilter: 'all',
                archiveMode: false,
                loading: false,
                role: '{{ auth()->user()->role ?? 'Guest' }}',
                userName: '{{ auth()->user()->name ?? '' }}',
                userId: {{ auth()->id() ?? 'null' }},
                isQCForeman: {{ auth()->user()?->employee_id === 'EMP-003' ? 'true' : 'false' }},
                currentPage: 1,
                perPage: 10,
                showDeleteModal: false,
                deleteTarget: null,
                deleting: false,
                deleteError: '',
                toast: null,
                uploadingExcel: false,

                async init() {
                    window.deferSkeletonHide = true; // Beri tahu layout untuk TAHAN skeleton!
                    
                    const defaultFilter = {
                        Leader:     'revision',
                        Foreman:    this.isQCForeman ? 'finished' : 'waiting_foreman',
                        Supervisor: 'spv_approve',
                        Operator:   'all',
                        'Group Leader': 'all',
                        Admin:      'all'
                    };
                    let initialFilter = defaultFilter[this.role] || 'all';
                    
                    // Pastikan initialFilter ada di dalam daftar tab, jika tidak fallback ke 'all'
                    setTimeout(() => {
                        const tabExists = this.availableTabs.some(t => t.id === initialFilter);
                        this.activeFilter = tabExists ? initialFilter : 'all';
                    }, 50);
                    
                    this.activeFilter = initialFilter;
                    this.loadData();

                    this.$watch('search', () => this.currentPage = 1);
                    this.$watch('filterBulan', () => this.currentPage = 1);
                    this.$watch('filterLine', () => this.currentPage = 1);
                    this.$watch('activeFilter', () => this.currentPage = 1);
                },

                async uploadExcel(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('file', file);

                    this.uploadingExcel = true;
                    this.showToast('success', 'Sedang membedah file Excel...');

                    try {
                        const res = await axios.post('/api/inspeksi/import-excel', formData, {
                            headers: { 'Content-Type': 'multipart/form-data' }
                        });
                        
                        this.showToast('success', res.data.message);
                        
                        // Jika ada error pada beberapa tab, tampilkan di alert
                        if (res.data.errors && res.data.errors.length > 0) {
                            setTimeout(() => {
                                alert("Beberapa tab gagal diimport:\n" + res.data.errors.join('\n'));
                            }, 500);
                        }
                        
                        this.loadData();
                    } catch (err) {
                        const msg = err.response?.data?.message || 'Gagal mengupload file Excel';
                        this.showToast('error', msg);
                    } finally {
                        this.uploadingExcel = false;
                        this.$refs.excelInput.value = ''; // Reset input
                    }
                },

                async loadData() {
                    this.loading = true;
                    try {
                        const url = this.archiveMode ? '/api/inspeksi?archived=1' : '/api/inspeksi';
                        const res = await axios.get(url);
                        this.data = Array.isArray(res.data) ? res.data : (res.data.data || []);
                    } catch (e) {
                        console.error(e);
                    } finally {
                        this.loading = false;
                        // Lepas skeleton!
                        window.dispatchEvent(new CustomEvent('page-ready'));
                    }
                },

                toggleArchiveMode() {
                    this.archiveMode = !this.archiveMode;
                    this.activeFilter = 'all'; // reset filter saat ganti mode
                    this.loadData();
                },

                async restoreTask(item) {
                    if (!confirm(`Kembalikan data ${item.no_form} dari arsip ke daftar aktif?`)) return;
                    
                    try {
                        await axios.post(`/api/inspeksi/${item.id}/restore`);
                        this.loadData();
                        this.showToast('success', 'Data berhasil dikembalikan!');
                    } catch (e) {
                        this.showToast('error', e.response?.data?.message || 'Gagal restore data');
                    }
                },

                async claimTask(item) {
                    if (!confirm(`Ambil tugas untuk ${item.part_name}?`)) return;
                    
                    try {
                        const res = await axios.post(`/api/inspeksi/${item.id}/claim`);
                        // Gunakan window.dispatchEvent untuk trigger toast global jika tersedia
                        // Atau cukup reload data
                        this.loadData();
                        alert('Tugas berhasil diklaim!');
                    } catch (e) {
                        alert(e.response?.data?.message || 'Gagal klaim tugas');
                    }
                },

                canDelete(item) {
                    if (this.role === 'Admin') return true;
                    if (this.role === 'Leader') {
                        return ['draft', 'revision', 'submitted', 'waiting_foreman'].includes(item.status);
                    }
                    return false;
                },

                deleteLi(item) {
                    this.deleteTarget = item;
                    this.deleteError = '';
                    this.showDeleteModal = true;
                },

                closeDeleteModal() {
                    if (this.deleting) return;
                    this.showDeleteModal = false;
                    this.deleteTarget = null;
                    this.deleteError = '';
                },

                showToast(type, msg) {
                    this.toast = { type, msg };
                    setTimeout(() => { this.toast = null; }, 3500);
                },

                async confirmDelete() {
                    if (!this.deleteTarget || this.deleting) return;
                    this.deleting = true;
                    this.deleteError = '';
                    const id = this.deleteTarget.id;
                    try {
                        await axios.delete(`/api/inspeksi/${id}`);
                        this.data = this.data.filter(d => d.id !== id);
                        this.closeDeleteModal();
                        this.showToast('success', 'Lembar Inspeksi berhasil dihapus.');
                    } catch (e) {
                        this.deleteError = e.response?.data?.message || 'Gagal menghapus Lembar Inspeksi.';
                    } finally {
                        this.deleting = false;
                    }
                },

                get filteredData() {
                    return this.data.filter(d => {
                        // 1. Filter teks pencarian
                        const q = this.search.toLowerCase();
                        const matchesSearch = !q || [d.no_form, d.job_no, d.part_name, d.part_no, d.status, d.lokasi, d.shift]
                            .some(v => (v || '').toLowerCase().includes(q));
                        if (!matchesSearch) return false;

                        // 2. Filter Bulan/Tahun (dari tgl_bulan)
                        if (this.filterBulan) {
                            const tgl = d.tgl_bulan || d.created_at;
                            if (!tgl) return false;
                            const d2 = new Date(tgl);
                            const yearMonth = d2.getFullYear() + '-' + String(d2.getMonth() + 1).padStart(2, '0');
                            if (yearMonth !== this.filterBulan) return false;
                        }


                        // Filter Line
                        if (this.filterLine) {
                            if (!d.lokasi || !d.lokasi.includes(this.filterLine)) return false;
                        }

                        // 4. Filter Tab Status
                        if (this.activeFilter === 'no_formula') return !this.hasFormula(d);
                        if (this.activeFilter === 'revision') return d.status === 'revision' || d.status === 'draft';
                        if (this.activeFilter === 'waiting_foreman') return d.status === 'waiting_foreman' || d.status === 'submitted';
                        if (this.activeFilter === 'spv_approve') return d.status === 'waiting_supervisor';
                        if (this.activeFilter === 'finished') return ['finished', 'approved', 'locked', 'ready_for_qc', 'waiting_qc_approval'].includes(d.status);

                        return true;
                    });
                },

                get paginatedData() {
                    const start = (this.currentPage - 1) * this.perPage;
                    const end = start + this.perPage;
                    return this.filteredData.slice(start, end);
                },

                get uniqueLines() {
                    const seen = new Set();
                    this.data.forEach(d => {
                        if (d.lokasi && d.lokasi.trim() !== '') {
                            seen.add(d.lokasi.trim());
                        }
                    });
                    return Array.from(seen).sort();
                },



                get totalPages() {
                    return Math.ceil(this.filteredData.length / this.perPage) || 1;
                },

                get pageNumbers() {
                    let pages = [];
                    for (let i = 1; i <= this.totalPages; i++) {
                        if (i === 1 || i === this.totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                            pages.push(i);
                        } else if (pages[pages.length - 1] !== '...') {
                            pages.push('...');
                        }
                    }
                    return pages;
                },

                // Opsi dropdown bulan — dibangun otomatis dari data yang ada
                get bulanOptions() {
                    const seen = new Set();
                    const opts = [];
                    const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
                    // Urutkan data dari terbaru
                    const sorted = [...this.data].sort((a, b) => {
                        return new Date(b.tgl_bulan || b.created_at) - new Date(a.tgl_bulan || a.created_at);
                    });
                    for (const d of sorted) {
                        const tgl = d.tgl_bulan || d.created_at;
                        if (!tgl) continue;
                        const dt = new Date(tgl);
                        const key = dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0');
                        if (!seen.has(key)) {
                            seen.add(key);
                            opts.push({
                                value: key,
                                label: monthNames[dt.getMonth()] + ' ' + dt.getFullYear()
                            });
                        }
                    }
                    return opts;
                },



                get availableTabs() {
                    const ic = {
                        warn: '<svg class="w-4 h-4 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>',
                        clock: '<svg class="w-4 h-4 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                        lock: '<svg class="w-4 h-4 shrink-0 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>',
                        check: '<svg class="w-4 h-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
                        search: '<svg class="w-4 h-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>',
                        flag: '<svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>',
                        grid: '<svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>'
                    };

                    const mkTab = (icon, text) => `${icon}<span>${text}</span>`;

                    const tabs = [
                        { id: 'all', label: mkTab(ic.grid, 'Semua (' + this.data.length + ')') }
                    ];

                    const revisionCount = this.data.filter(d => d.status === 'revision' || d.status === 'draft').length;
                    const noFormulaCount = this.data.filter(d => !this.hasFormula(d)).length;
                    const restTabs = [];
                    
                    // Selalu tampilkan tab ini meskipun 0, agar user tahu fiturnya ada
                    restTabs.push({ id: 'no_formula', label: mkTab(ic.warn, 'Formula Kosong (' + noFormulaCount + ')') });

                    if (revisionCount > 0) {
                        restTabs.push({ id: 'revision', label: mkTab(ic.warn, 'Perlu Revisi (' + revisionCount + ')') });
                    }
                    
                    restTabs.push(
                        { id: 'waiting_foreman', label: mkTab(ic.clock, 'Menunggu Foreman (' + this.data.filter(d => d.status === 'waiting_foreman' || d.status === 'submitted').length + ')') },
                        { id: 'spv_approve', label: mkTab(ic.lock, 'Menunggu SPV (' + this.data.filter(d => d.status === 'waiting_supervisor').length + ')') },
                        { id: 'finished', label: mkTab(ic.flag, 'Selesai (' + this.data.filter(d => ['finished', 'approved', 'locked', 'ready_for_qc', 'waiting_qc_approval'].includes(d.status)).length + ')') }
                    );

                    tabs.push(...restTabs);

                    const priority = {
                        Leader: ['revision', 'all', 'waiting_foreman', 'spv_approve', 'finished'],
                        Foreman: ['waiting_foreman', 'all', 'revision', 'spv_approve', 'finished'],
                        Supervisor: ['spv_approve', 'all', 'waiting_foreman', 'revision', 'finished'],
                        Operator: ['all', 'finished'],
                        'Group Leader': ['all', 'finished'],
                        Admin: ['all', 'revision', 'waiting_foreman', 'spv_approve', 'finished'],
                    };

                    const order = priority[this.role] || ['all', 'no_formula', 'revision', 'waiting_foreman', 'spv_approve', 'finished'];
                    const orderedRest = order.map(id => tabs.find(t => t.id === id)).filter(Boolean);

                    // Selalu pasang tab formula kosong jika tidak ada di dalam urutan
                    if (!orderedRest.find(t => t.id === 'no_formula')) {
                        const tabNoFormula = tabs.find(t => t.id === 'no_formula');
                        if (tabNoFormula) orderedRest.splice(1, 0, tabNoFormula);
                    }

                    return orderedRest;
                },

                hasFormula(item) {
                    const hasCols = item.sampling_cols && (
                        (Array.isArray(item.sampling_cols) && item.sampling_cols.length > 0) || 
                        (typeof item.sampling_cols === 'string' && item.sampling_cols.length > 2)
                    );
                    
                    const maxS = parseFloat(item.max_sample) || 0;
                    const tt = parseFloat(item.tact_time) || 0;
                    const ctD = parseFloat(item.ct_dimensi) || 0;
                    
                    const hasSamplingTarget = hasCols || maxS > 0;
                    const hasTimerTarget = tt > 0 && ctD > 0;
                    
                    return hasSamplingTarget && hasTimerTarget;
                },

                getJobPrefix(jobNo) {
                    if (!jobNo) return '-';
                    const dash = jobNo.indexOf('-');
                    return dash > 0 ? jobNo.substring(0, dash).toUpperCase() : jobNo.toUpperCase();
                },

                getBadgeClass(prefix) {
                    if (!prefix || prefix === '-') return 'bg-slate-100 text-slate-600 border-slate-200';
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
                        'bg-fuchsia-50 text-fuchsia-700 border-fuchsia-200',
                    ];
                    let hash = 0;
                    for (let i = 0; i < prefix.length; i++) hash = (hash * 31 + prefix.charCodeAt(i)) & 0xffff;
                    return palettes[hash % palettes.length];
                },

                getStatus(status) {
                    const map = {
                        draft:                { label: "Draft",              bg: "#F1F5F9", border: "#CBD5E1", color: "#475569" },
                        submitted:            { label: "Menunggu Foreman",    bg: "#FFF7ED", border: "#FB923C", color: "#C2410C" },
                        waiting_foreman:      { label: "Menunggu Foreman",    bg: "#FFF7ED", border: "#FB923C", color: "#C2410C" },
                        revision:             { label: '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg> <span>Perlu Revisi</span>', bg: "#FEF2F2", border: "#FCA5A5", color: "#B91C1C" },
                        waiting_supervisor:   { label: "Menunggu SPV",        bg: "#F5F3FF", border: "#C4B5FD", color: "#6D28D9" },
                        ready_for_qc:         { label: '<span>Selesai</span> <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>', bg: "#F0FDF4", border: "#4ADE80", color: "#15803D" },
                        locked:               { label: '<span>Selesai</span> <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>', bg: "#F0FDF4", border: "#4ADE80", color: "#15803D" },
                        waiting_qc_approval:  { label: '<span>Selesai</span> <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>', bg: "#F0FDF4", border: "#4ADE80", color: "#15803D" },
                        finished:             { label: '<span>Selesai</span> <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>', bg: "#F0FDF4", border: "#4ADE80", color: "#15803D" },
                        approved:             { label: '<span>Selesai</span> <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>', bg: "#F0FDF4", border: "#4ADE80", color: "#15803D" },
                    };
                    return map[status] || { label: status || "—", bg: "#F1F5F9", border: "#CBD5E1", color: "#64748B" };
                },


                // ─── CHECK AKURASI (Demo Simulasi — nanti diganti API Produksi) ──────
                getAkurasiStatus(item) {
                    // DEMO: variasikan berdasarkan item.id agar tampil beragam di list
                    // Nanti REPLACE baris ini: 
                    //   const lkh = item.produksi_actual_qty; (dari API produksi)
                    //   if (!lkh) return waiting...
                    //   if (qty === lkh) return match...
                    //   else return unmatch...
                    const id = parseInt(item.id) || 0;
                    const mod = id % 3;
                    if (mod === 0) {
                        // Simulasi: data cocok
                        return { 
                            label: 'Data Akurat ✓', 
                            icon: '✓', 
                            cls: 'bg-emerald-100 text-emerald-700' 
                        };
                    } else if (mod === 1) {
                        // Simulasi: ada selisih
                        return { 
                            label: 'Selisih -50 pcs', 
                            icon: '⚠', 
                            cls: 'bg-rose-100 text-rose-700 animate-pulse' 
                        };
                    } else {
                        // Simulasi: menunggu data dari LKH
                        return { 
                            label: 'Waiting LKH', 
                            icon: '…', 
                            cls: 'bg-slate-100 text-slate-400' 
                        };
                    }
                },

                getCustomStatus(item) {
                    return this.getStatus(item.status);
                },

                getOverallJudgement(item) {
                    // Judgement hanya valid setelah proses QC selesai sepenuhnya
                    const notFinalYet = ['draft', 'submitted', 'waiting_foreman', 'revision',
                                         'waiting_supervisor', 'locked', 'ready_for_qc', 'waiting_qc_approval'];
                    if (notFinalYet.includes(item.status)) {
                        return null;
                    }

                    const j = item.qg_judgement;
                    if (!j) return null;

                    // Kalau sudah format OK/NG biasa
                    if (j === 'OK' || j === 'NG') return j;

                    // Kalau formatnya JSON string misal {"10":"OK", "11":"NG"}
                    try {
                        const obj = JSON.parse(j);
                        // Jika ada satupun value yang mengandung "NG"
                        const values = Object.values(obj);
                        if (values.some(v => typeof v === 'string' && v.toUpperCase().includes('NG'))) {
                            return 'NG';
                        }
                        return 'OK'; // Default OK kalau JSON tapi isinya OK semua
                    } catch (e) {
                        // Kalau bukan JSON dan bukan OK/NG, anggap string itu sendiri 
                        // (tapi fallback ke pencarian kata NG)
                        return j.toUpperCase().includes('NG') ? 'NG' : 'OK';
                    }
                }
            }
        }
    </script>
    @endpush
</x-app-layout>

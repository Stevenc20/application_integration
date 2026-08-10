@extends('layouts.app')
@section('content')
    @section('title', 'Dashboard Rekap Bulanan')
    <div class='mb-6'><h1 class='text-2xl font-black text-slate-800'>Rekap Bulanan</h1></div>

    {{-- Load Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div x-data="rekapDashboard()" x-init="init()" class="space-y-6">

        {{-- Filter & Header --}}
        <div class="relative overflow-hidden bg-gradient-to-br from-slate-900 via-[#0F172A] to-indigo-950 p-6 sm:p-8 rounded-[2rem] shadow-2xl flex flex-wrap items-center justify-between gap-6 border border-slate-800">
            <!-- Decorative background elements -->
            <div class="absolute top-0 right-0 w-72 h-72 bg-indigo-500/10 rounded-full blur-3xl transform translate-x-1/3 -translate-y-1/3 pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-56 h-56 bg-sky-500/10 rounded-full blur-3xl transform -translate-x-1/3 translate-y-1/3 pointer-events-none"></div>

            <div class="relative z-10 flex items-center gap-5">
                <div class="w-14 h-14 bg-white/5 backdrop-blur-md border border-white/10 text-white rounded-2xl flex items-center justify-center shadow-inner">
                    <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <h2 class="text-2xl font-black text-white tracking-tight">Rekapitulasi Kinerja</h2>
                    <p class="text-sm font-semibold text-slate-400 mt-1" x-text="periodeLabel"></p>
                </div>
            </div>

            <div class="relative z-10 flex items-center gap-3 flex-wrap bg-white/5 p-2 rounded-2xl backdrop-blur-sm border border-white/10">
                <select x-model="filterBulan" @change="onFilterChange()" class="px-4 py-2.5 bg-slate-800/80 border border-slate-700/50 rounded-xl text-sm font-bold text-slate-200 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 cursor-pointer hover:bg-slate-700 transition-colors">
                    <option value="">Semua Bulan</option>
                    <option value="01">Januari</option>
                    <option value="02">Februari</option>
                    <option value="03">Maret</option>
                    <option value="04">April</option>
                    <option value="05">Mei</option>
                    <option value="06">Juni</option>
                    <option value="07">Juli</option>
                    <option value="08">Agustus</option>
                    <option value="09">September</option>
                    <option value="10">Oktober</option>
                    <option value="11">November</option>
                    <option value="12">Desember</option>
                </select>
                <select x-model="filterTahun" @change="onFilterChange()" class="px-4 py-2.5 bg-slate-800/80 border border-slate-700/50 rounded-xl text-sm font-bold text-slate-200 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 cursor-pointer hover:bg-slate-700 transition-colors">
                    <template x-for="y in tahunOptions" :key="y"><option :value="y" x-text="y"></option></template>
                </select>
                <button @click="loadData()" title="Refresh Data" class="w-11 h-11 flex items-center justify-center bg-indigo-600 text-white rounded-xl hover:bg-indigo-500 transition-all duration-300 shadow-lg shadow-indigo-600/30 hover:shadow-indigo-500/50 hover:-translate-y-0.5">
                    <svg class="w-5 h-5" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </button>
                <div class="w-px h-8 bg-slate-700/50 mx-1 hidden sm:block"></div>
                <button @click="downloadCSV()" title="Download Laporan CSV" class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white text-sm font-black rounded-xl hover:from-emerald-500 hover:to-emerald-400 transition-all duration-300 shadow-lg shadow-emerald-600/30 hover:shadow-emerald-500/50 hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Unduh CSV
                </button>
            </div>
        </div>

        {{-- Loading Skeleton --}}
        <template x-if="loading">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 animate-pulse">
                <template x-for="i in 5"><div class="bg-slate-100 rounded-2xl h-24"></div></template>
            </div>
        </template>

        {{-- Empty State --}}
        <template x-if="!loading && metrik.total_li === 0">
            <div class="bg-white rounded-2xl border-2 border-dashed border-slate-200 p-16 text-center">
                <div class="text-5xl mb-4">📭</div>
                <h3 class="text-lg font-black text-slate-600 mb-2">Tidak ada data pada periode ini</h3>
                <p class="text-sm text-slate-400 font-medium" x-text="'Coba pilih bulan atau tahun yang berbeda. Periode terpilih: ' + periodeLabel"></p>
            </div>
        </template>

        {{-- Konten Utama (tampil jika ada data) --}}
        <template x-if="!loading && metrik.total_li > 0">
            <div class="space-y-6">

                {{-- Metrik Utama --}}
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-5">
                    <div class="bg-white p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/60 hover:-translate-y-1 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-indigo-50 rounded-full group-hover:scale-110 transition-transform duration-500 pointer-events-none"></div>
                        <div class="relative z-10">
                            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">Total LI</p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-slate-800 tracking-tight" x-text="metrik.total_li"></span>
                                <span class="text-xs font-bold text-slate-400">Doc</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-emerald-500 to-emerald-400 p-6 rounded-3xl shadow-lg shadow-emerald-500/20 hover:-translate-y-1 hover:shadow-emerald-500/40 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/20 rounded-full group-hover:scale-110 transition-transform duration-500 pointer-events-none"></div>
                        <div class="relative z-10">
                            <div class="w-10 h-10 bg-white/20 text-white rounded-2xl flex items-center justify-center mb-4 backdrop-blur-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="text-[11px] font-black text-emerald-100 uppercase tracking-widest mb-1">Total OK</p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-white tracking-tight" x-text="metrik.total_ok"></span>
                                <span class="text-xs font-bold text-emerald-100" x-text="(metrik.total_li ? Math.round((metrik.total_ok/metrik.total_li)*100) : 0) + '%'"></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-rose-500 to-rose-400 p-6 rounded-3xl shadow-lg shadow-rose-500/20 hover:-translate-y-1 hover:shadow-rose-500/40 transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-white/20 rounded-full group-hover:scale-110 transition-transform duration-500 pointer-events-none"></div>
                        <div class="relative z-10">
                            <div class="w-10 h-10 bg-white/20 text-white rounded-2xl flex items-center justify-center mb-4 backdrop-blur-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <p class="text-[11px] font-black text-rose-100 uppercase tracking-widest mb-1">Total NG</p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-white tracking-tight" x-text="metrik.total_ng"></span>
                                <span class="text-xs font-bold text-rose-100" x-text="metrik.ng_rate + '% Rate'"></span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/60 hover:-translate-y-1 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-amber-50 rounded-full group-hover:scale-110 transition-transform duration-500 pointer-events-none"></div>
                        <div class="relative z-10">
                            <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243-4.243 3 3 0 004.243 4.243z"/></svg>
                            </div>
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Repair</p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-amber-500 tracking-tight" x-text="metrik.total_repair"></span>
                                <span class="text-xs font-bold text-slate-400">Pcs</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/60 hover:-translate-y-1 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 relative overflow-hidden group">
                        <div class="absolute -right-6 -top-6 w-24 h-24 bg-slate-50 rounded-full group-hover:scale-110 transition-transform duration-500 pointer-events-none"></div>
                        <div class="relative z-10">
                            <div class="w-10 h-10 bg-slate-100 text-slate-600 rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </div>
                            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Reject</p>
                            <div class="flex items-baseline gap-2">
                                <span class="text-4xl font-black text-slate-700 tracking-tight" x-text="metrik.total_reject"></span>
                                <span class="text-xs font-bold text-slate-400">Pcs</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- NG Rate Progress Bar --}}
                <div class="bg-white p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/60">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-black text-slate-800 flex items-center gap-2">
                            <span class="w-2 h-6 bg-indigo-500 rounded-full"></span>
                            Rasio Kualitas Produksi
                        </h3>
                        <span class="text-sm font-black px-4 py-1.5 rounded-xl border"
                              :class="metrik.ng_rate > 20 ? 'bg-rose-50 text-rose-600 border-rose-100' : metrik.ng_rate > 10 ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100'"
                              x-text="'NG Rate: ' + metrik.ng_rate + '%'"></span>
                    </div>
                    <div class="flex h-5 rounded-2xl overflow-hidden bg-slate-100 gap-1 p-1">
                        <div class="bg-gradient-to-r from-emerald-500 to-emerald-400 h-full rounded-xl transition-all duration-1000 ease-out shadow-inner"
                             :style="'width:' + (metrik.total_li ? Math.round((metrik.total_ok/metrik.total_li)*100) : 0) + '%'">
                        </div>
                        <div class="bg-gradient-to-r from-rose-500 to-rose-400 h-full rounded-xl transition-all duration-1000 ease-out shadow-inner"
                             :style="'width:' + (metrik.total_li ? Math.round((metrik.total_ng/metrik.total_li)*100) : 0) + '%'">
                        </div>
                    </div>
                    <div class="flex justify-between mt-3 text-sm font-bold text-slate-500 px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-emerald-700" x-text="'OK (' + metrik.total_ok + ' form)'"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-rose-700" x-text="'NG (' + metrik.total_ng + ' form)'"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                        </div>
                    </div>
                </div>

                {{-- Grafik --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/60 flex flex-col">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-800">Trend Mingguan</h3>
                                <p class="text-xs font-semibold text-slate-400">Distribusi hasil inspeksi OK vs NG</p>
                            </div>
                        </div>
                        <div class="relative flex-1 min-h-[260px] w-full"><canvas id="trendChart"></canvas></div>
                    </div>
                    <div class="bg-white p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/60 flex flex-col">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-slate-800">Distribusi Per Shift</h3>
                                <p class="text-xs font-semibold text-slate-400">Total form yang diselesaikan</p>
                            </div>
                        </div>
                        <div class="relative flex-1 min-h-[260px] w-full flex justify-center"><canvas id="shiftChart"></canvas></div>
                    </div>
                </div>

                {{-- Top 5 --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/60">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-slate-800">Top Defect / Problem</h3>
                                    <p class="text-xs font-semibold text-slate-400">Masalah kualitas tersering</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(item, index) in Object.entries(top_defects)" :key="item[0]">
                                <div class="flex items-center gap-4 p-4 bg-white hover:bg-slate-50 rounded-2xl border border-slate-100/60 transition-colors shadow-sm group">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shrink-0 transition-all duration-300 group-hover:scale-110"
                                         :class="index === 0 ? 'bg-rose-500 text-white shadow-md shadow-rose-500/30' : index === 1 ? 'bg-amber-500 text-white shadow-md shadow-amber-500/30' : index === 2 ? 'bg-emerald-500 text-white shadow-md shadow-emerald-500/30' : 'bg-slate-100 text-slate-500'">
                                        <span x-text="'#' + (index + 1)"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-black text-slate-700 block truncate group-hover:text-rose-600 transition-colors" x-text="item[0]"></span>
                                        <div class="w-full bg-slate-100 h-1.5 rounded-full mt-2 overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-rose-500 to-rose-400 rounded-full" :style="`width: ${(item[1]/Math.max(...Object.values(top_defects))) * 100}%`"></div>
                                        </div>
                                    </div>
                                    <div class="shrink-0 flex flex-col items-end">
                                        <span class="text-lg font-black text-slate-800" x-text="item[1]"></span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kasus</span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="Object.keys(top_defects).length === 0" class="p-10 text-center text-emerald-600 font-bold text-sm bg-emerald-50/50 rounded-2xl border border-dashed border-emerald-200 flex flex-col items-center gap-3">
                                <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-2xl mb-1">🎉</div>
                                Luar biasa! Tidak ada data defect pada periode ini.
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100/60">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-black text-slate-800">Top Part Diinspeksi</h3>
                                    <p class="text-xs font-semibold text-slate-400">Intensitas pengecekan part</p>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <template x-for="(item, index) in Object.entries(top_parts)" :key="item[0]">
                                <div class="flex items-center gap-4 p-4 bg-white hover:bg-slate-50 rounded-2xl border border-slate-100/60 transition-colors shadow-sm group">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-black shrink-0 transition-all duration-300 group-hover:scale-110"
                                         :class="index === 0 ? 'bg-indigo-500 text-white shadow-md shadow-indigo-500/30' : index === 1 ? 'bg-sky-500 text-white shadow-md shadow-sky-500/30' : index === 2 ? 'bg-cyan-500 text-white shadow-md shadow-cyan-500/30' : 'bg-slate-100 text-slate-500'">
                                        <span x-text="'#' + (index + 1)"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm font-black text-slate-700 block truncate group-hover:text-indigo-600 transition-colors" x-text="item[0]"></span>
                                        <div class="w-full bg-slate-100 h-1.5 rounded-full mt-2 overflow-hidden">
                                            <div class="h-full bg-gradient-to-r from-indigo-500 to-sky-400 rounded-full" :style="`width: ${(item[1]/Math.max(...Object.values(top_parts))) * 100}%`"></div>
                                        </div>
                                    </div>
                                    <div class="shrink-0 flex flex-col items-end">
                                        <span class="text-lg font-black text-slate-800" x-text="item[1]"></span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Form</span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="Object.keys(top_parts).length === 0" class="p-10 text-center text-slate-400 font-bold text-sm bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
                                Tidak ada data part
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </template>

    </div>

    @push('scripts')
    <script>
    function rekapDashboard() {
        return {
            filterBulan: String(new Date().getMonth() + 1).padStart(2, '0'),
            filterTahun: String(new Date().getFullYear()),
            loading: true,
            metrik: { total_li: 0, total_produksi: 0, total_ok: 0, total_ng: 0, ng_rate: 0, total_repair: 0, total_reject: 0 },
            per_shift: {},
            top_parts: {},
            top_defects: {},
            trend_mingguan: {},
            trendChart: null,
            shiftChart: null,

            get tahunOptions() {
                const cur = new Date().getFullYear();
                return [cur, cur - 1, cur - 2];
            },

            get periodeLabel() {
                const bulanMap = {'01':'Januari','02':'Februari','03':'Maret','04':'April','05':'Mei','06':'Juni','07':'Juli','08':'Agustus','09':'September','10':'Oktober','11':'November','12':'Desember'};
                const b = this.filterBulan ? bulanMap[this.filterBulan] + ' ' + this.filterTahun : 'Semua Bulan ' + this.filterTahun;
                return 'Periode: ' + b;
            },

            async init() {
                this.loadData();
            },

            onFilterChange() {
                this.$nextTick(() => this.loadData());
            },

            async loadData() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({ tahun: this.filterTahun });
                    if (this.filterBulan) params.set('bulan', this.filterBulan);

                    const res = await axios.get('/api/inspeksi/rekap-bulanan?' + params.toString());
                    const data = res.data;

                    this.metrik       = data.metrik;
                    this.per_shift    = data.per_shift;
                    this.top_parts    = data.top_parts;
                    this.top_defects  = data.top_defects;
                    this.trend_mingguan = data.trend_mingguan;

                    if (this.metrik.total_li > 0) {
                        this.$nextTick(() => {
                            setTimeout(() => {
                                this.renderCharts();
                            }, 100);
                        });
                    }
                } catch (e) {
                    console.error('Failed to load rekap data:', e);
                } finally {
                    this.loading = false;
                }
            },

            renderCharts() {
                const trendCtx = document.getElementById('trendChart');
                const shiftCtx = document.getElementById('shiftChart');
                if (!trendCtx || !shiftCtx) return;

                let existTrend = Chart.getChart('trendChart');
                if (existTrend) existTrend.destroy();

                let existShift = Chart.getChart('shiftChart');
                if (existShift) existShift.destroy();

                const weeks = Object.keys(this.trend_mingguan);
                const okData = weeks.map(w => this.trend_mingguan[w].OK);
                const ngData = weeks.map(w => this.trend_mingguan[w].NG);

                this.trendChart = new Chart(trendCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: weeks,
                        datasets: [
                            { label: 'OK', data: okData, backgroundColor: '#34d399', borderRadius: 6 },
                            { label: 'NG', data: ngData, backgroundColor: '#fb7185', borderRadius: 6 }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'top', labels: { font: { weight: 'bold' } } } },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                            x: { grid: { display: false } }
                        }
                    }
                });

                const shiftLabels = Object.keys(this.per_shift);
                const shiftData   = Object.values(this.per_shift);

                this.shiftChart = new Chart(shiftCtx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: shiftLabels,
                        datasets: [{ data: shiftData, backgroundColor: ['#818cf8', '#38bdf8', '#a78bfa'], borderWidth: 0, hoverOffset: 4 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '68%',
                        plugins: { legend: { position: 'right', labels: { font: { weight: 'bold' } } } }
                    }
                });
            },

            downloadCSV() {
                const periode = this.periodeLabel;
                const bulanMap = {'01':'Januari','02':'Februari','03':'Maret','04':'April','05':'Mei','06':'Juni','07':'Juli','08':'Agustus','09':'September','10':'Oktober','11':'November','12':'Desember'};
                const bulanLabel = this.filterBulan ? bulanMap[this.filterBulan] : 'Semua Bulan';
                const ngRateColor = this.metrik.ng_rate > 20 ? '#dc2626' : this.metrik.ng_rate > 10 ? '#d97706' : '#16a34a';

                const shiftRows = Object.entries(this.per_shift)
                    .map(([s, c]) => `<tr><td>${s}</td><td style="text-align:center;font-weight:bold">${c}</td><td></td></tr>`).join('');

                const defectRows = Object.keys(this.top_defects).length
                    ? Object.entries(this.top_defects).map(([d, c]) =>
                        `<tr><td>${d}</td><td style="text-align:center;font-weight:bold;color:#dc2626">${c}</td><td>Kasus</td></tr>`).join('')
                    : `<tr><td colspan="3" style="color:#16a34a;font-weight:bold;text-align:center">✓ Tidak ada defect — Bagus!</td></tr>`;

                const partRows = Object.keys(this.top_parts).length
                    ? Object.entries(this.top_parts).map(([p, c]) =>
                        `<tr><td>${p}</td><td style="text-align:center;font-weight:bold">${c}</td><td>Form</td></tr>`).join('')
                    : `<tr><td colspan="3" style="text-align:center;color:#94a3b8">Tidak ada data</td></tr>`;

                const html = `
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head><meta charset="UTF-8">
<style>
  body { font-family: Calibri, Arial, sans-serif; font-size: 10pt; }
  table { border-collapse: collapse; width: 100%; }
  td, th { border: 1px solid #d1d5db; padding: 6px 10px; }
  .hd-main { background:#1e3a5f; color:white; font-size:14pt; font-weight:bold; text-align:center; border:none; padding:12px; }
  .hd-sub  { background:#334d6e; color:#e2e8f0; font-size:9pt; text-align:center; border:none; padding:4px; }
  .hd-section { background:#1e40af; color:white; font-weight:bold; font-size:10pt; padding:7px 10px; }
  .hd-col  { background:#dbeafe; color:#1e3a5f; font-weight:bold; text-align:center; }
  .ok-cell { color:#16a34a; font-weight:bold; }
  .ng-cell { color:#dc2626; font-weight:bold; }
  .spacer  { border:none; background:#f8fafc; height:12px; }
  .total-row { background:#f0f9ff; font-weight:bold; }
</style>
</head>
<body>
<table>
  <!-- HEADER -->
  <tr><td colspan="3" class="hd-main">PT. INTI PANTJA PRESS INDUSTRI</td></tr>
  <tr><td colspan="3" class="hd-sub">LAPORAN REKAP LEMBAR INSPEKSI (QA SECTION)</td></tr>
  <tr><td colspan="3" class="hd-sub">Periode: ${bulanLabel} ${this.filterTahun} &nbsp;|&nbsp; Diekspor: ${new Date().toLocaleString('id-ID')}</td></tr>
  <tr><td colspan="3" class="spacer"></td></tr>

  <!-- RINGKASAN METRIK -->
  <tr><td colspan="3" class="hd-section">📊 RINGKASAN METRIK</td></tr>
  <tr><th class="hd-col">Indikator</th><th class="hd-col">Nilai</th><th class="hd-col">Satuan</th></tr>
  <tr class="total-row"><td>Total Lembar Inspeksi</td><td style="text-align:center;font-size:12pt;font-weight:bold">${this.metrik.total_li}</td><td>Dokumen</td></tr>
  <tr><td>Total Inspeksi OK</td><td style="text-align:center;font-weight:bold;color:#16a34a">${this.metrik.total_ok}</td><td class="ok-cell">${this.metrik.total_li ? Math.round(this.metrik.total_ok/this.metrik.total_li*100) : 0}%</td></tr>
  <tr><td>Total Inspeksi NG</td><td style="text-align:center;font-weight:bold;color:#dc2626">${this.metrik.total_ng}</td><td class="ng-cell">${this.metrik.ng_rate}%</td></tr>
  <tr><td>NG Rate</td><td style="text-align:center;font-weight:bold;color:${ngRateColor}">${this.metrik.ng_rate}%</td><td style="color:${ngRateColor}">${this.metrik.ng_rate > 20 ? '⚠ Tinggi' : this.metrik.ng_rate > 10 ? '⚡ Sedang' : '✓ Baik'}</td></tr>
  <tr><td>Total Produksi</td><td style="text-align:center;font-weight:bold">${this.metrik.total_produksi}</td><td>Pcs</td></tr>
  <tr><td>Total Repair</td><td style="text-align:center;font-weight:bold;color:#d97706">${this.metrik.total_repair}</td><td>Pcs</td></tr>
  <tr><td>Total Reject</td><td style="text-align:center;font-weight:bold;color:#dc2626">${this.metrik.total_reject}</td><td>Pcs</td></tr>
  <tr><td colspan="3" class="spacer"></td></tr>

  <!-- DISTRIBUSI SHIFT -->
  <tr><td colspan="3" class="hd-section">⏱ DISTRIBUSI INSPEKSI PER SHIFT</td></tr>
  <tr><th class="hd-col">Shift</th><th class="hd-col">Jumlah LI</th><th class="hd-col">Persentase</th></tr>
  ${Object.entries(this.per_shift).map(([s, c]) =>
    `<tr><td>${s}</td><td style="text-align:center;font-weight:bold">${c}</td>
    <td style="text-align:center">${this.metrik.total_li ? Math.round(c/this.metrik.total_li*100) : 0}%</td></tr>`
  ).join('')}
  <tr><td colspan="3" class="spacer"></td></tr>

  <!-- TOP DEFECT -->
  <tr><td colspan="3" class="hd-section">📉 TOP DEFECT / PROBLEM</td></tr>
  <tr><th class="hd-col">Nama Defect / Problem</th><th class="hd-col">Jumlah Kasus</th><th class="hd-col">Keterangan</th></tr>
  ${defectRows}
  <tr><td colspan="3" class="spacer"></td></tr>

  <!-- TOP PART -->
  <tr><td colspan="3" class="hd-section">⚙ TOP PART DIINSPEKSI</td></tr>
  <tr><th class="hd-col">Nama Part</th><th class="hd-col">Jumlah Form</th><th class="hd-col">Keterangan</th></tr>
  ${partRows}
  <tr><td colspan="3" class="spacer"></td></tr>

  <!-- FOOTER -->
  <tr><td colspan="3" style="border:none;color:#94a3b8;font-size:8pt;text-align:right;padding-top:4px">
    Dibuat otomatis oleh QA System IPPI &bull; ${new Date().toLocaleDateString('id-ID', {weekday:'long',year:'numeric',month:'long',day:'numeric'})}
  </td></tr>
</table>
</body></html>`;

                const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
                const url  = URL.createObjectURL(blob);
                const a    = document.createElement('a');
                a.href     = url;
                a.download = `Rekap-LI-${bulanLabel.replace(/ /g,'-')}-${this.filterTahun}.xls`;
                a.click();
                URL.revokeObjectURL(url);
            }
        }
    }
    </script>
    @endpush
@endsection

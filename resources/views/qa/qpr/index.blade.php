@extends('layouts.app')
@section('content')
    @section('title', 'QPR List')
    <div class='mb-6'><h1 class='text-2xl font-black text-slate-800'>Quality Problem Report</h1></div>

    <div x-data="qprList({ apiUrl: '{{ url('') }}', userRole: '{{ auth()->user()->role ?? 'Guest' }}', userId: {{ auth()->id() ?? 'null' }}, userName: '{{ auth()->user()->name ?? '' }}', userDepartment: '{{ auth()->user()->department ?? '' }}' })" x-init="init()" class="space-y-4 sm:space-y-6 md:space-y-8" :class="archiveMode ? 'bg-slate-50/50 -m-4 p-4 rounded-3xl' : ''">
        
        {{-- Banner Arsip --}}
        <div x-show="archiveMode" x-cloak
             class="bg-slate-800 text-white p-4 rounded-[24px] shadow-lg flex items-center gap-4">
            <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <div>
                <h3 class="font-black text-lg">Mode Arsip Aktif</h3>
                <p class="text-xs text-slate-300">Menampilkan data QPR yang berumur lebih dari 2 bulan.</p>
            </div>
        </div>


        <!-- Modern Header Action Section -->
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            
            {{-- Unified Search & Filter Bar --}}
            <div class="flex-1 flex items-center bg-white border-2 border-slate-100 rounded-2xl p-1.5 shadow-sm focus-within:border-rose-400/60 focus-within:shadow-rose-400/10 transition-all duration-300">
                <div class="pl-4 pr-3 text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </div>
                <input
                    type="text"
                    x-model="search"
                    placeholder="Cari No. QPR, No. Job, Part, atau Frekuensi (Sering/Kadang)..."
                    class="flex-1 bg-transparent border-none focus:ring-0 text-sm font-semibold text-slate-800 placeholder-slate-400 py-2.5 outline-none"
                >
                
                {{-- Reset Search / Month Info --}}
                <template x-if="filterBulan || search">
                    <button @click="filterBulan = ''; search = ''" class="mr-3 px-2 py-1 bg-rose-50 text-rose-600 rounded-lg text-[10px] font-bold hover:bg-rose-100 transition-colors flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </button>
                </template>

                <div class="h-8 w-px bg-slate-200 mx-1"></div>
                
                {{-- Month Filter Integrated --}}
                <div class="flex items-center gap-2 pr-2">
                    <select x-model="filterBulan"
                            class="text-xs font-bold text-slate-600 bg-slate-50 hover:bg-slate-100 border-none rounded-xl py-2 pl-3 pr-8 outline-none focus:ring-0 transition-colors cursor-pointer">
                        <option value="">Semua Bulan</option>
                        <template x-for="opt in bulanOptions" :key="opt.value">
                            <option :value="opt.value" x-text="opt.label"></option>
                        </template>
                    </select>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-stretch gap-2 shrink-0 h-[56px]">
                <button @click="loadData()" class="px-4 bg-white border-2 border-slate-100 rounded-2xl text-slate-400 hover:text-rose-600 hover:border-rose-200 hover:bg-rose-50 transition-all active:scale-95 flex items-center justify-center group" title="Refresh Data">
                    <svg class="w-5 h-5 group-hover:rotate-180 transition-transform duration-500" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                </button>
                
                @if(auth()->user()->role === 'Admin' || auth()->user()->role === 'Supervisor')
                <button @click="toggleArchiveMode()" 
                        class="px-5 flex items-center justify-center gap-2 font-black rounded-2xl transition-all active:scale-95 border-2"
                        :class="archiveMode ? 'bg-slate-800 border-slate-800 text-white shadow-lg shadow-slate-800/20' : 'bg-white border-slate-100 text-slate-600 hover:border-slate-200 hover:bg-slate-50'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    <span x-text="archiveMode ? 'Tutup Arsip' : 'Arsip'"></span>
                </button>
                @endif
                
                @if(auth()->user()->role !== 'Operator')
                    @if(auth()->user()->department === 'QA' || auth()->user()->department === 'Quality Assurance')
                    <a href="{{ url('/qpr/create') }}" x-show="!archiveMode" class="px-6 flex items-center justify-center gap-2 bg-gradient-to-r from-rose-500 to-red-600 text-white font-black rounded-2xl hover:from-rose-400 hover:to-red-500 transition-all shadow-lg shadow-rose-500/30 active:scale-95 border border-rose-400/50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                        Buat QPR Baru
                    </a>
                    @else
                    <a href="{{ url('/qpr/create?mode=request') }}" x-show="!archiveMode" class="px-6 flex items-center justify-center gap-2 bg-gradient-to-r from-orange-400 to-orange-500 text-white font-black rounded-2xl hover:from-orange-500 hover:to-orange-600 transition-all shadow-lg shadow-orange-500/30 active:scale-95 border border-orange-400/50">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                        Ajukan QPR
                    </a>
                    @endif
                @endif
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex gap-2 overflow-x-auto pb-6 scrollbar-hide snap-x relative items-center">
            <template x-for="tab in availableTabs" :key="tab.id">
                <button 
                    @click="activeFilter = tab.id"
                    class="px-5 py-2.5 rounded-[16px] font-black text-[10px] md:text-[11px] uppercase tracking-wider whitespace-nowrap transition-all border-2 snap-start flex items-center gap-2"
                    :class="activeFilter === tab.id 
                        ? 'bg-gradient-to-r from-rose-500 to-red-600 border-rose-400 text-white shadow-lg shadow-rose-500/30' 
                        : 'bg-white border-slate-100 text-slate-500 hover:border-rose-300 hover:text-rose-600 hover:bg-rose-50/50'"
                    x-html="tab.label"
                ></button>
            </template>
            
            <div class="ml-auto pl-4 shrink-0">
                <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-3 py-1.5 rounded-lg"
                      x-text="'Total: ' + filteredData.length + ' Data'">
                </span>
            </div>
        </div>

        <!-- Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <template x-for="item in paginatedData" :key="item.id">
                <div class="relative flex flex-col p-6 rounded-[24px] overflow-hidden group hover:shadow-2xl hover:scale-[1.02] transition-all duration-300"
                     :class="item.status === 'Close' ? 'bg-slate-50/80 border border-slate-200 shadow-xl shadow-slate-200/40 opacity-[0.95]' : 
                            (item.status === 'OPEN' && !item.assigned_foreman_id && item.created_by == userId ? 'bg-gradient-to-br from-orange-50 to-white border-2 border-orange-400 shadow-xl shadow-orange-500/20' : 
                            'bg-white border border-slate-200 shadow-xl shadow-slate-200/40')">
                    
                    <div class="relative z-10 flex flex-col h-full">
                        {{-- Top row: Badge and Delete --}}
                        <div class="flex items-center justify-between mb-6">
                            <span class="inline-flex px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-[0.1em] border"
                                  :class="`${getStatusStyles(item.status, item).bg} ${getStatusStyles(item.status, item).text} ${getStatusStyles(item.status, item).border}`"
                                  x-text="getStatusLabel(item.status, item)"></span>
                            
                            <template x-if="canDelete(item)">
                                <button type="button" @click.stop="deleteQpr(item)"
                                        class="text-slate-400 hover:text-rose-500 hover:bg-rose-50 p-1.5 rounded-lg transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </template>
                            <template x-if="!canDelete(item)">
                                <div class="w-8 h-8 flex items-center justify-center text-slate-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                </div>
                            </template>
                        </div>

                        {{-- Urgent Pengajuan QPR Assigned Banner --}}
                        <template x-if="item.status === 'OPEN' && !item.assigned_foreman_id && item.created_by == userId">
                            <div class="mb-5 bg-orange-100/80 border border-orange-300 rounded-[14px] p-3 flex items-center gap-3 relative overflow-hidden group/banner cursor-default">
                                <div class="absolute inset-0 bg-gradient-to-r from-orange-500/10 to-transparent"></div>
                                <div class="relative w-9 h-9 rounded-xl bg-orange-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-orange-500/30 group-hover/banner:scale-110 transition-transform">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                                </div>
                                <div class="relative flex-1">
                                    <p class="text-[9px] font-black text-orange-600 uppercase tracking-widest mb-0.5">Laporan NG Baru</p>
                                    <p class="text-[11px] text-orange-900 font-bold leading-tight">Pengajuan dari Produksi untuk part yang Anda Inspeksi.</p>
                                </div>
                                <div class="relative w-2 h-2 rounded-full bg-orange-500 animate-pulse mr-1"></div>
                            </div>
                        </template>



                        {{-- Urgent Signature Banner --}}
                        <template x-if="(needsSignature(item) || needsActionFromSeksi(item)) && !['Waiting A3 Report', 'Waiting Verif A3'].includes(item.status)">
                            <div class="mb-5 bg-amber-50 border border-amber-200/60 rounded-[14px] p-3 flex items-center gap-3 relative overflow-hidden group/banner cursor-default">
                                <div class="absolute inset-0 bg-gradient-to-r from-amber-500/5 to-transparent"></div>
                                <div class="relative w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-amber-500/20 group-hover/banner:scale-110 transition-transform">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </div>
                                <div class="relative flex-1">
                                    <p class="text-[9px] font-black text-amber-500 uppercase tracking-widest mb-0.5">Aksi Diperlukan</p>
                                    <p class="text-[11px] text-amber-800 font-bold leading-tight" 
                                       x-text="needsSignature(item) ? (['Group Leader', 'Foreman', 'Kasie QA', 'Kasie'].includes(role) || userDepartment === 'Quality Control' ? 'Dibutuhkan pengecekan & tanda tangan Anda' : 'Dibutuhkan pengisian tindakan perbaikan (PDCA)') : 'Langkah sebelumnya ditolak (NG). Mohon isi Action lanjutan.'"></p>
                                </div>
                                <div class="relative w-2 h-2 rounded-full bg-amber-500 animate-pulse mr-1"></div>
                            </div>
                        </template>

                        {{-- Urgent A3 Report Banner --}}
                        <template x-if="item.status === 'Waiting A3 Report' && needsActionFromSeksi(item)">
                            <div class="mb-5 bg-rose-50 border border-rose-200/60 rounded-[14px] p-3 flex items-center gap-3 relative overflow-hidden group/banner cursor-default">
                                <div class="absolute inset-0 bg-gradient-to-r from-rose-500/5 to-transparent"></div>
                                <div class="relative w-9 h-9 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-rose-500/20 group-hover/banner:scale-110 transition-transform">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div class="relative flex-1">
                                    <p class="text-[9px] font-black text-rose-500 uppercase tracking-widest mb-0.5">Peringatan Kritis</p>
                                    <p class="text-[11px] text-rose-800 font-bold leading-tight">Langkah perbaikan gagal 3x (NG). Diwajibkan membuat A3 Report.</p>
                                </div>
                                <div class="relative w-2 h-2 rounded-full bg-rose-500 animate-pulse mr-1"></div>
                            </div>
                        </template>

                        {{-- QA A3 Verification Banner --}}
                        <template x-if="item.status === 'Waiting Verif A3' && (['Admin', 'Kasie QA'].includes(role) || userDepartment === 'Quality Control')">
                            <div class="mb-5 bg-blue-50 border border-blue-200/60 rounded-[14px] p-3 flex items-center gap-3 relative overflow-hidden group/banner cursor-default">
                                <div class="absolute inset-0 bg-gradient-to-r from-blue-500/5 to-transparent"></div>
                                <div class="relative w-9 h-9 rounded-xl bg-blue-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-blue-500/20 group-hover/banner:scale-110 transition-transform">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <div class="relative flex-1">
                                    <p class="text-[9px] font-black text-blue-500 uppercase tracking-widest mb-0.5">Verifikasi A3 Report</p>
                                    <p class="text-[11px] text-blue-800 font-bold leading-tight">Dokumen A3 telah disubmit. Dibutuhkan verifikasi QA.</p>
                                </div>
                                <div class="relative w-2 h-2 rounded-full bg-blue-500 animate-pulse mr-1"></div>
                            </div>
                        </template>

                        {{-- Title & Subtitle --}}
                        <h3 class="text-slate-800 font-black text-lg mb-1.5 leading-tight" x-text="item.no_qpr || 'DRAFT BARU'"></h3>
                        <p class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-6 line-clamp-2 min-h-[32px] leading-relaxed" x-text="item.nama_part"></p>

                        {{-- Metadata --}}
                        <div class="space-y-4 mb-6 mt-auto">
                            <div class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                <div>
                                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">No. Job / Part</p>
                                    <p class="text-xs text-slate-700 font-semibold mt-0.5" x-text="(item.no_job || '-')"></p>
                                </div>
                            </div>
                            <template x-if="item.status === 'OPEN' && !item.assigned_foreman_id && item.reporter_name && item.reporter_name !== item.investigator_name">
                                <div class="flex items-start gap-3">
                                    <svg class="w-4 h-4 text-orange-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                                    <div>
                                        <p class="text-[9px] text-orange-500 font-bold uppercase tracking-widest">Pelapor (Requester)</p>
                                        <p class="text-xs text-slate-700 font-semibold mt-0.5" x-text="item.reporter_name"></p>
                                    </div>
                                </div>
                            </template>
                            
                            <div class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <div>
                                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Penemu (Investigator)</p>
                                    <p class="text-xs text-slate-700 font-semibold mt-0.5" x-text="(item.investigator_name || 'QC')"></p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <div>
                                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Tanggal</p>
                                    <p class="text-xs text-slate-700 font-semibold mt-0.5" x-text="new Date(item.tanggal).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'})"></p>
                                </div>
                            </div>

                            <template x-if="item.kategori_problem">
                                <div class="flex items-start gap-3">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                                    <div>
                                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Frekuensi</p>
                                        <div class="mt-1">
                                            <span class="px-2 py-0.5 rounded text-[10px] font-black tracking-wide" 
                                                :class="item.kategori_problem === 'Sering' ? 'bg-red-50 text-red-600 border border-red-200' : (item.kategori_problem === 'Kadang-Kadang' ? 'bg-orange-50 text-orange-600 border border-orange-200' : 'bg-slate-100 text-slate-600 border border-slate-200')"
                                                x-text="item.kategori_problem">
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <template x-if="item.remark && item.status === 'Revision'">
                                <div class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded-lg">
                                    <p class="text-[10px] text-amber-700 font-semibold line-clamp-2" x-text="'💬 ' + item.remark"></p>
                                </div>
                            </template>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-3 pt-4 border-t border-slate-100">
                            
                            {{-- State 1: Bisa di-edit atau Butuh Aksi/TTD --}}
                            <template x-if="canEdit(item) || needsSignature(item) || needsActionFromSeksi(item)">
                                <div class="flex items-center gap-3 w-full">
                                    <a :href="`{{ url('/qpr') }}/${item.id}/preview`" class="flex-1 py-3 px-3 text-center border border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-slate-600 hover:text-slate-800 rounded-xl text-xs font-bold transition-all">
                                        Lihat
                                    </a>
                                    <a :href="`{{ url('/qpr') }}/${item.id}/edit`" :class="getStatusStyles(item.status, item).btnGradient" class="flex-[2] py-3 px-3 text-center bg-gradient-to-r text-white rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 shadow-lg">
                                        <span x-text="(needsSignature(item) || needsActionFromSeksi(item)) ? 'Review & TTD QPR' : (item.status === 'OPEN' && !item.assigned_foreman_id ? 'Proses QPR' : 'Isi Form QPR')"></span> <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                    </a>
                                </div>
                            </template>

                            {{-- State 2: Hanya Lihat Dokumen --}}
                            <template x-if="!canEdit(item) && !needsSignature(item) && !needsActionFromSeksi(item)">
                                <a :href="`{{ url('/qpr') }}/${item.id}/preview`" 
                                   :class="getStatusStyles(item.status, item).btnGradient" 
                                   class="w-full py-3 px-3 text-center bg-gradient-to-r text-white rounded-xl text-xs font-black transition-all flex items-center justify-center gap-2 shadow-lg">
                                    <span x-text="item.status === 'Close' ? 'Lihat Arsip QPR' : 'Buka Dokumen QPR'"></span> 
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                                </a>
                            </template>

                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Pagination Controls --}}
        <div class="flex flex-col md:flex-row items-center justify-between mt-8 gap-4 px-2" x-show="totalPages > 1" x-cloak>
            <div class="flex items-center gap-2">
                <button @click="prevPage()" :disabled="currentPage === 1" class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                
                <template x-for="page in pageNumbers" :key="page">
                    <button @click="goToPage(page)" 
                            class="w-10 h-10 rounded-xl font-bold transition-all text-sm shadow-sm"
                            :class="page === currentPage ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : (page === '...' ? 'cursor-default text-slate-400 bg-transparent shadow-none' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50')"
                            x-text="page"
                            :disabled="page === '...'"></button>
                </template>

                <button @click="nextPage()" :disabled="currentPage === totalPages" class="w-10 h-10 rounded-xl bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center transition-colors shadow-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            
            <div class="text-sm font-semibold text-slate-500">
                Menampilkan <span x-text="startIndex"></span> - <span x-text="endIndex"></span> dari <span x-text="filteredData.length"></span> data
            </div>
        </div>

            <!-- Empty State -->
            <template x-if="!loading && filteredData.length === 0">
                <div class="py-20 text-center">
                    <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <p class="text-slate-400 font-bold">Data tidak ditemukan</p>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">Coba filter atau pencarian lain</p>
                </div>
            </template>
        </div>
    </div>

@endsection
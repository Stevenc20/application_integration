
<div x-data="notifications" x-show="showPopup" x-cloak
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95">

    <div class="bg-white w-full max-w-lg rounded-[32px] shadow-2xl overflow-hidden border border-white/20 relative"
         @click.away="closePopup()">
        
        {{-- Header --}}
        <div class="relative p-7 bg-gradient-to-br from-slate-50 to-white border-b border-slate-100">
            <div class="flex items-center gap-5">
                <div class="w-14 h-14 bg-red-600 rounded-2xl flex items-center justify-center shadow-lg shadow-red-600/20 rotate-3">
                    <svg class="w-7 h-7 text-white -rotate-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-xl font-black text-slate-800 leading-tight">Halo, <span x-text="userName"></span>!</h3>
                    <p class="text-sm text-slate-400 font-bold uppercase tracking-widest mt-0.5">Ada tugas yang menunggu TTD Anda</p>
                </div>
            </div>
            <button @click="closePopup()" class="absolute top-7 right-7 w-10 h-10 bg-white border-2 border-slate-100 rounded-xl flex items-center justify-center text-slate-400 hover:text-red-600 hover:border-red-200 transition-all shadow-sm group">
                <svg class="w-4 h-4 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Content --}}
        <div class="p-7 max-h-[60vh] overflow-y-auto space-y-4 custom-scrollbar">
            
            {{-- QPR List --}}
            <template x-if="pendingData.qprs.length > 0">
                <div class="space-y-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[.2em] px-1">Quick Problem Report (QPR)</p>
                    <template x-for="item in pendingData.qprs" :key="'qpr-'+item.id">
                        <a href="/qpr-list" class="group block bg-slate-50 border-2 border-slate-50 hover:border-red-100 hover:bg-red-50/30 p-4 rounded-2xl transition-all duration-300">
                            <div class="flex justify-between items-start mb-2">
                                <span class="px-3 py-1 bg-white border border-slate-200 text-slate-800 rounded-lg text-[11px] font-black font-mono shadow-sm" x-text="(item.qpr ? item.qpr.no_qpr : item.no_qpr) || 'Draft'"></span>
                                <span class="px-2.5 py-1 bg-red-100 text-red-600 rounded-full text-[9px] font-black uppercase tracking-wider" x-text="(item.qpr ? item.qpr.status : item.status)"></span>
                            </div>
                            
                            {{-- Peringatan Khusus untuk NG / Action Lanjutan --}}
                            <template x-if="(item.qpr ? item.qpr.status : item.status).includes('Waiting Action')">
                                <div class="mb-2 p-2 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
                                    <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <p class="text-[10px] text-amber-700 font-semibold leading-snug">Langkah sebelumnya ditolak (NG) oleh QA. Mohon segera isi Action lanjutan.</p>
                                </div>
                            </template>
                            
                            {{-- Peringatan Kritis untuk A3 Report --}}
                            <template x-if="(item.qpr ? item.qpr.status : item.status) === 'Waiting A3 Report'">
                                <div class="mb-2 p-2 bg-rose-50 border border-rose-200 rounded-lg flex items-start gap-2">
                                    <svg class="w-4 h-4 text-rose-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <p class="text-[10px] text-rose-700 font-semibold leading-snug">Langkah perbaikan gagal 3x berturut-turut (NG). <br><strong>Diwajibkan membuat A3 Report</strong> sebelum batas waktu <span x-text="fmtDate(item.qpr ? item.qpr.a3_due_date : item.a3_due_date)"></span>.</p>
                                </div>
                            </template>

                            <h4 class="text-sm font-black text-slate-800 mb-1 group-hover:text-red-700 transition-colors" x-text="(item.qpr ? item.qpr.nama_part : item.nama_part) || 'Tanpa Nama Part'"></h4>
                            <div class="flex items-center gap-3 text-[10px] font-bold text-slate-400">
                                <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> <span x-text="fmtDate(item.qpr ? item.qpr.tanggal : item.tanggal)"></span></span>
                                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                <span x-text="(item.qpr ? item.qpr.pic_seksi : item.pic_seksi) || '-'"></span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-red-100 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-red-600">Buka Daftar QPR</span>
                                <svg class="w-4 h-4 text-red-400 group-hover:text-red-600 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </div>
                        </a>
                    </template>
                </div>
            </template>

            {{-- LI List --}}
            <template x-if="pendingData.lis.length > 0">
                <div class="space-y-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[.2em] px-1">Lembar Inspeksi (LI)</p>
                    <template x-for="item in pendingData.lis" :key="'li-'+item.id">
                        <a :href="'/li/master-template'" class="group block bg-slate-50 border-2 border-slate-50 hover:border-blue-100 hover:bg-blue-50/30 p-4 rounded-2xl transition-all duration-300">
                            <div class="flex justify-between items-start mb-2">
                                <span class="px-3 py-1 bg-white border border-slate-200 text-slate-800 rounded-lg text-[11px] font-black font-mono shadow-sm" x-text="item.job_no"></span>
                                <span class="px-2.5 py-1 bg-blue-100 text-blue-600 rounded-full text-[9px] font-black uppercase tracking-wider">TTD PENDING</span>
                            </div>
                            <h4 class="text-sm font-black text-slate-800 mb-1 group-hover:text-blue-700 transition-colors" x-text="item.part_name"></h4>
                            <div class="flex items-center gap-3 text-[10px] font-bold text-slate-400">
                                <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> <span x-text="fmtDate(item.created_at)"></span></span>
                                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                <span x-text="item.model"></span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-blue-100 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-blue-600">Buka Master LI</span>
                                <svg class="w-4 h-4 text-blue-400 group-hover:text-blue-600 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </div>
                        </a>
                    </template>
                </div>
            </template>

            {{-- Item Check List --}}
            <template x-if="pendingData.ics && pendingData.ics.length > 0">
                <div class="space-y-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[.2em] px-1">Item Check (Harian)</p>
                    <template x-for="item in pendingData.ics" :key="'ic-'+item.id">
                        <a :href="'/item-check/' + item.id + '/form'" class="group block bg-slate-50 border-2 border-slate-50 hover:border-indigo-100 hover:bg-indigo-50/30 p-4 rounded-2xl transition-all duration-300">
                            <div class="flex justify-between items-start mb-2">
                                <span class="px-3 py-1 bg-white border border-slate-200 text-slate-800 rounded-lg text-[11px] font-black font-mono shadow-sm" x-text="(item.master_template ? item.master_template.job_no : '-')"></span>
                                <span class="px-2.5 py-1 bg-indigo-100 text-indigo-600 rounded-full text-[9px] font-black uppercase tracking-wider">TTD PENDING</span>
                            </div>
                            <h4 class="text-sm font-black text-slate-800 mb-1 group-hover:text-indigo-700 transition-colors" x-text="(item.master_template ? item.master_template.part_name : '-')"></h4>
                            <div class="flex items-center gap-3 text-[10px] font-bold text-slate-400">
                                <span class="flex items-center gap-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> <span x-text="fmtDate(item.updated_at || item.created_at)"></span></span>
                                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                <span x-text="(item.master_template ? item.master_template.model : '-')"></span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-indigo-100 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-indigo-600">Buka Dokumen Item Check</span>
                                <svg class="w-4 h-4 text-indigo-400 group-hover:text-indigo-600 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </div>
                        </a>
                    </template>
                </div>
            </template>

        </div>

        {{-- Footer --}}
        <div class="p-7 bg-slate-50 border-t border-slate-100 flex gap-4">
            <button @click="closePopup()" class="flex-1 py-4 bg-white border-2 border-slate-200 rounded-2xl text-sm font-black text-slate-500 hover:bg-slate-100 transition-all active:scale-95">Nanti Saja</button>
            <a href="{{ url('/approval') }}" class="flex-[2] py-4 bg-red-600 text-white rounded-2xl text-sm font-black shadow-lg shadow-red-600/30 hover:bg-red-700 transition-all flex items-center justify-center gap-2 active:scale-95">
                Proses Sekarang
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
</style>

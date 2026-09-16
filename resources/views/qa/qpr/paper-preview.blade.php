<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    @media print {
        @page { size: A4 portrait; margin: 0; }
        body { padding: 3mm 8mm; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .screen-only { display: none !important; }
        .print-only { display: block !important; }
        .print-only-flex { display: flex !important; }
        .print-only-grid { display: grid !important; }
        .print-only-table { display: table !important; }
        .print-only-tr { display: table-row !important; }
        .print-only-td { display: table-cell !important; }
    }
    .print-only, .print-only-flex, .print-only-grid, .print-only-table, .print-only-tr, .print-only-td { display: none; }
    /* Print form styles */
    .qpr-print-form { font-family: Arial, sans-serif; font-size: 9pt; color: #000; }
    .qpr-print-form table { border-collapse: collapse; width: 100%; }
    .qpr-print-form td, .qpr-print-form th { border: 1px solid #000; padding: 2px 4px; vertical-align: top; }
    .qpr-print-form .no-border { border: none; }
    .qpr-print-form .bold { font-weight: bold; }
    .qpr-print-form .center { text-align: center; }
    .qpr-print-form .underline { border-bottom: 1px solid #000; display: inline-block; min-width: 80px; }
    .qpr-print-form .section-title { font-weight: bold; font-size: 8pt; text-decoration: underline; }
    .qpr-print-form .big-title { font-size: 14pt; font-weight: bold; text-align: center; }
    .qpr-print-form .checkbox-cell { text-align: center; padding: 1px 3px; }
    .qpr-print-form .sig-cell { height: 50px; min-width: 90px; text-align: center; vertical-align: middle; }
    @media print { .qpr-print-form { zoom: 0.97; transform: scale(0.97); transform-origin: top center; } }
</style>

<div class="w-full mx-auto print:p-0 print:bg-white text-slate-800 screen-only" style="font-family: 'Plus Jakarta Sans', sans-serif;">

    <!-- Top Header (hidden in print) -->
    <div class="flex items-center justify-between mb-8 print:hidden">
        <div class="flex items-center gap-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-[#FEE2E2] text-[#E11D2A] rounded-[14px] flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <h1 class="text-[22px] font-extrabold text-[#0F172A] tracking-tight">Quality Problem Report (QPR)</h1>
                    <p class="text-[13px] text-slate-500 font-medium mt-0.5">Detail laporan problem kualitas</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <template x-if="form.status === 'Close'">
                <div class="flex gap-3">
                    <button type="button" onclick="window.print()" class="flex items-center gap-2 px-5 py-2.5 bg-white border border-slate-200 rounded-xl text-[13px] font-bold text-slate-700 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] hover:bg-slate-50 transition-colors">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Unduh PDF
                    </button>
                    <button type="button" onclick="window.print()" class="flex items-center gap-2 px-6 py-2.5 bg-[#E11D2A] text-white rounded-xl text-[13px] font-bold shadow-[0_4px_14px_0_rgba(225,29,42,0.39)] hover:bg-red-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Cetak
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- MAIN DOCUMENT CONTAINER (One big connected form) -->
    <div class="bg-white rounded-[24px] border border-slate-200 flex flex-col overflow-hidden print:shadow-none print:rounded-none print:border-slate-800">
        
        <!-- Hero Header -->
        <div class="flex border-b border-slate-200 print:border-slate-800 relative bg-white">
            <!-- Left Logo block -->
            <div class="w-[200px] shrink-0 bg-white p-6 flex flex-col justify-center items-center text-center border-r border-slate-200 print:border-slate-800">
                <h2 class="text-[#E11D2A] m-0 text-[32px] tracking-[-2px] font-[900] scale-y-125 mb-2">IPPI</h2>
                <div class="text-slate-800 text-[10px] font-bold tracking-[0.15em] leading-tight">PT. INTI PANTJA PRESS<br>INDUSTRI</div>
            </div>
            <!-- Right Info block -->
            <div class="flex-1 px-8 py-6 relative">
                <!-- watermark top right -->
                <!-- <div class="absolute top-0 right-0 bg-slate-50/80 px-6 py-3 border-b border-l border-slate-200 print:border-slate-800 text-right">
                    <p class="text-[10px] font-bold text-slate-500">FISM-QAD-03-03-01</p>
                </div> -->
                
                <div class="flex flex-col h-full justify-center mt-2 pr-28">
                    <!-- Top Row -->
                    <div class="grid grid-cols-[1fr_1fr_1fr] gap-4 pb-5 border-b border-slate-200 print:border-slate-800">
                        <!-- No. Job -->
                        <div class="flex gap-4 items-center">
                            <div class="w-10 h-10 rounded-[14px] bg-[#FEE2E2] text-[#E11D2A] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-[12px] text-slate-500 font-medium mb-0.5">No. Job</p>
                                <p class="text-[15px] font-bold text-[#0F172A]" x-text="form.no_job || '-'"></p>
                            </div>
                        </div>
                        <!-- Model -->
                        <div class="flex gap-4 items-center pl-2">
                            <div class="w-10 h-10 rounded-[14px] bg-[#FEE2E2] text-[#E11D2A] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-[12px] text-slate-500 font-medium mb-0.5">Model</p>
                                <p class="text-[15px] font-bold text-[#0F172A]" x-text="form.model || '-'"></p>
                            </div>
                        </div>
                        <!-- Tanggal -->
                        <div class="flex gap-4 items-center pl-4">
                            <div class="w-10 h-10 rounded-[14px] bg-[#FEE2E2] text-[#E11D2A] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-[12px] text-slate-500 font-medium mb-0.5">Tanggal</p>
                                <p class="text-[15px] font-bold text-[#0F172A]" x-text="form.tanggal ? new Date(form.tanggal).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}) : '-'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Row -->
                    <div class="grid grid-cols-[2fr_1fr] gap-4 pt-5">
                        <!-- Nama Part -->
                        <div class="flex gap-4 items-center">
                            <div class="w-10 h-10 rounded-[14px] bg-[#FEE2E2] text-[#E11D2A] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            </div>
                            <div>
                                <p class="text-[12px] text-slate-500 font-medium mb-0.5">Nama Part</p>
                                <p class="text-[15px] font-bold text-[#0F172A] uppercase" x-text="form.nama_part || '-'"></p>
                            </div>
                        </div>
                        <!-- No. QPR -->
                        <div class="flex gap-4 items-center pl-4">
                            <div class="w-10 h-10 rounded-[14px] bg-[#FEE2E2] text-[#E11D2A] flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <p class="text-[12px] text-slate-500 font-medium mb-0.5">No. QPR</p>
                                <p class="text-[15px] font-bold text-[#0F172A] uppercase whitespace-nowrap" x-text="form.no_qpr || '-'"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- 3 cards side by side -->
        <div class="grid grid-cols-[1fr_1.5fr_1.5fr] border-b border-slate-200 print:border-slate-800 bg-white">
            <!-- Kondisi Part -->
            <div class="p-6 flex flex-col justify-between border-r border-slate-200 print:border-slate-800">
                <div class="flex items-center gap-2 mb-6">
                    <div class="w-5 h-5 bg-red-600 rounded flex items-center justify-center text-white"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                    <h3 class="text-[11px] font-black text-slate-800 tracking-wide uppercase">KONDISI PART</h3>
                </div>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-[13px] text-slate-600 font-medium">Rework / PCS</span>
                        <div class="px-6 py-2 rounded bg-red-50 text-red-600 font-black text-[13px] min-w-[50px] text-center" x-text="form.rework_qty || '-'"></div>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-[13px] text-slate-600 font-medium">Reject / PCS</span>
                        <div class="px-6 py-2 rounded bg-slate-100 text-slate-600 font-black text-[13px] min-w-[50px] text-center" x-text="form.reject_qty || '-'"></div>
                    </div>
                </div>
            </div>

            <!-- Info Produksi -->
            <div class="p-6 flex flex-col justify-center border-r border-slate-200 print:border-slate-800">
                <div class="space-y-5">
                    <div class="flex items-center pb-4 border-b border-slate-100/70 border-dashed">
                        <div class="w-8 shrink-0"><svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>
                        <span class="text-[13px] text-slate-600 font-medium flex-1">Stock IPPI / PCS</span>
                        <span class="text-[14px] font-black text-slate-800" x-text="form.stock_ippi_qty || '-'"></span>
                    </div>
                    <div class="flex items-center pb-4 border-b border-slate-100/70 border-dashed">
                        <div class="w-8 shrink-0"><svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg></div>
                        <span class="text-[13px] text-slate-600 font-medium flex-1">Rencana Produksi</span>
                        <span class="text-[14px] font-black text-slate-800" x-text="form.rencana_produksi ? form.rencana_produksi.split('T')[0] : '-'"></span>
                    </div>
                    <div class="flex items-center">
                        <div class="w-8 shrink-0"><svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></div>
                        <span class="text-[13px] text-slate-600 font-medium flex-1">Proses Repair</span>
                        <span class="text-[14px] font-black text-slate-800" x-text="form.proses_repair || '-'"></span>
                    </div>
                </div>
            </div>

            <!-- Deskripsi Problem -->
            <div class="flex overflow-hidden">
                <div class="flex-1 p-6">
                    <div class="flex items-center gap-2 mb-6">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-wide">DESKRIPSI PROBLEM</h3>
                    </div>
                    <div class="space-y-4">
                        <template x-for="f in ['Baru Pertama', 'Kadang Kadang', 'Sering']">
                            <div class="flex items-center gap-3">
                                <div class="w-5 h-5 rounded flex items-center justify-center border"
                                     :class="(form.kategori_problem === f || (f === 'Kadang Kadang' && form.kategori_problem === 'Kadang-Kadang')) ? 'bg-red-600 border-red-600 text-white shadow-sm' : 'border-slate-300 text-transparent'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                </div>
                                <span class="text-[13px] font-medium text-slate-600" x-text="f"></span>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="w-[140px] border-l border-slate-200 print:border-slate-800 flex flex-col items-center justify-center bg-slate-50/50 p-4">
                    <p class="text-[11px] text-slate-500 font-medium text-center leading-snug mb-3">Last Date<br>Problem</p>
                    <span class="text-[14px] font-black text-slate-800" x-text="form.last_date_problem ? new Date(form.last_date_problem).toLocaleDateString('id-ID') : '—'"></span>
                </div>
            </div>
        </div>

        <!-- Sketch & Jenis Problem -->
        <div class="flex border-b border-slate-200 print:border-slate-800 bg-white min-h-[300px]">
            <div class="w-[75%] p-6 border-r border-slate-200 print:border-slate-800 flex flex-col relative" style="background-image: radial-gradient(#e2e8f0 1px, transparent 1px); background-size: 20px 20px;">
                <div class="flex items-center gap-2 mb-4 bg-white/80 backdrop-blur w-max px-3 py-1 rounded-lg">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-wide">SKETCH</h3>
                </div>
                <div class="flex-1 flex items-center justify-center">
                    <template x-if="form.sketches && form.sketches.length">
                        <div class="flex flex-wrap gap-4 justify-center items-center">
                            <template x-for="src in (form.sketches || [])">
                                <img :src="src" class="max-h-[240px] object-contain border border-slate-200 p-1 bg-white shadow-sm">
                            </template>
                        </div>
                    </template>
                </div>
            </div>
            <div class="w-[25%] p-6 flex flex-col bg-slate-50/30 border-l border-slate-200 print:border-slate-800">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-wide">AREA & JENIS PROBLEM</h3>
                </div>
                
                <div class="space-y-4 mb-6">
                    <template x-if="form.area_problems && Object.keys(form.area_problems).length > 0">
                        <div class="space-y-5">
                            <template x-for="(defects, areaKey) in form.area_problems">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-red-600 border border-red-100 flex items-center justify-center text-xs font-black shadow-sm" x-text="areaKey"></div>
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Area</span>
                                    </div>
                                    <div class="pl-[13px] border-l-2 border-slate-200 ml-[13px] space-y-2 py-1">
                                        <template x-for="t in defects">
                                            <div class="bg-red-50 border border-red-100 text-red-600 px-3 py-2 rounded-xl text-[11px] font-black flex items-center gap-2 shadow-sm">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                                <span x-text="t"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Fallback untuk data lama yang belum tersimpan dalam format area_problems -->
                    <template x-if="!form.area_problems || Object.keys(form.area_problems).length === 0">
                        <div class="space-y-4">
                            <div class="flex flex-wrap gap-2">
                                <template x-if="form.area">
                                    <template x-for="a in form.area.split(',').map(s=>s.trim()).filter(Boolean)">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-red-600 border border-red-100 flex items-center justify-center text-xs font-black shadow-sm" x-text="a"></div>
                                    </template>
                                </template>
                                <template x-if="!form.area">
                                    <div class="text-[13px] text-slate-400 italic">Belum ada area</div>
                                </template>
                            </div>
                            
                            <div class="space-y-2">
                                <template x-if="form.defect">
                                    <template x-for="t in form.defect.split(',').map(s=>s.trim()).filter(Boolean)">
                                        <div class="bg-red-50 border border-red-100 text-red-600 px-3 py-2 rounded-xl text-[11px] font-black flex items-center gap-2 shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            <span x-text="t"></span>
                                        </div>
                                    </template>
                                </template>
                                <template x-if="!form.defect">
                                    <div class="text-[13px] text-slate-400 italic">Belum ada problem</div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="w-full h-px bg-slate-200 border-t border-dashed border-slate-300 print:border-slate-800 my-6"></div>

                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-wide">KETERANGAN / DETAIL PROBLEM</h3>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <p class="text-[13px] text-slate-700 leading-relaxed break-words" x-text="form.defect_keterangan || '-'"></p>
                </div>
            </div>
        </div>

         <!-- Strip details -->
        <div class="flex justify-between border-b border-slate-200 print:border-slate-800 bg-white">
            <div class="flex items-center gap-4 w-1/4 border-r border-slate-200 print:border-slate-800 p-5 px-6">
                <svg class="w-5 h-5 text-[#E11D2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <div>
                    <p class="text-[12px] text-slate-500 font-medium mb-0.5">Lokasi Kejadian</p>
                    <p class="text-[14px] font-bold text-[#0F172A]" x-text="form.lokasi || '-'"></p>
                </div>
            </div>
            <div class="flex items-center gap-4 w-1/4 border-r border-slate-200 print:border-slate-800 p-5 px-6">
                <svg class="w-5 h-5 text-[#E11D2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <div>
                    <p class="text-[12px] text-slate-500 font-medium mb-0.5">Shift</p>
                    <p class="text-[14px] font-bold text-[#0F172A]" x-text="form.shift || '-'"></p>
                </div>
            </div>
            <div class="flex items-center gap-4 w-1/4 border-r border-slate-200 print:border-slate-800 p-5 px-6">
                <svg class="w-5 h-5 text-[#E11D2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="text-[12px] text-slate-500 font-medium mb-0.5">Jam</p>
                    <p class="text-[14px] font-bold text-[#0F172A]" x-text="form.jam || '-'"></p>
                </div>
            </div>
            <div class="flex items-center gap-4 w-1/4 p-5 px-6">
                <svg class="w-5 h-5 text-[#E11D2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <div>
                    <p class="text-[12px] text-slate-500 font-medium mb-0.5">Dokumen Referensi</p>
                    <p class="text-[14px] font-bold text-[#0F172A]" x-text="form.dokumen || '-'"></p>
                </div>
            </div>
        </div>

        <!-- Analisa Penyebab -->
        <div class="p-6 border-b border-slate-200 print:border-slate-800 bg-white">
            <div class="flex items-center gap-4 mb-6">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-wide">ANALISA PENYEBAB</h3>
                </div>
                
                <div class="flex items-center gap-2 flex-wrap">
                    <template x-if="form.analisa_man">
                        <div class="flex items-center">
                            <span class="bg-red-100 text-red-600 px-3 py-1 rounded-l-lg text-[10px] font-black uppercase tracking-wider">Man</span>
                            <span class="bg-slate-50 border border-slate-100 text-slate-500 px-3 py-1 rounded-r-lg text-[10px] font-medium" x-text="form.analisa_man_ket || 'Kesalahan Manusia'"></span>
                        </div>
                    </template>
                    <template x-if="form.analisa_method">
                        <div class="flex items-center">
                            <span class="bg-amber-100 text-amber-600 px-3 py-1 rounded-l-lg text-[10px] font-black uppercase tracking-wider">Method</span>
                            <span class="bg-slate-50 border border-slate-100 text-slate-500 px-3 py-1 rounded-r-lg text-[10px] font-medium" x-text="form.analisa_method_ket || '-'"></span>
                        </div>
                    </template>
                    <template x-if="form.analisa_machine">
                        <div class="flex items-center">
                            <span class="bg-blue-100 text-blue-600 px-3 py-1 rounded-l-lg text-[10px] font-black uppercase tracking-wider">Machine</span>
                            <span class="bg-slate-50 border border-slate-100 text-slate-500 px-3 py-1 rounded-r-lg text-[10px] font-medium" x-text="form.analisa_machine_ket || '-'"></span>
                        </div>
                    </template>
                    <template x-if="form.analisa_material">
                        <div class="flex items-center">
                            <span class="bg-emerald-100 text-emerald-600 px-3 py-1 rounded-l-lg text-[10px] font-black uppercase tracking-wider">Material</span>
                            <span class="bg-slate-50 border border-slate-100 text-slate-500 px-3 py-1 rounded-r-lg text-[10px] font-medium" x-text="form.analisa_material_ket || '-'"></span>
                        </div>
                    </template>
                    <template x-if="form.analisa_environment">
                        <div class="flex items-center">
                            <span class="bg-purple-100 text-purple-600 px-3 py-1 rounded-l-lg text-[10px] font-black uppercase tracking-wider">Environment</span>
                            <span class="bg-slate-50 border border-slate-100 text-slate-500 px-3 py-1 rounded-r-lg text-[10px] font-medium" x-text="form.analisa_environment_ket || '-'"></span>
                        </div>
                    </template>
                </div>
            </div>
            
            <div class="space-y-4 pl-8">
                <template x-for="(k, i) in [form.analisa_man_ket, form.analisa_method_ket, form.analisa_machine_ket, form.analisa_material_ket, form.analisa_environment_ket].filter(Boolean)">
                    <div class="flex items-center gap-4">
                        <div class="w-5 h-5 rounded border border-red-600 bg-white text-red-600 flex items-center justify-center shrink-0 shadow-sm"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                        <div class="flex-1 text-[13px] font-medium text-slate-600 pb-2 border-b border-dashed border-slate-200" x-text="k"></div>
                    </div>
                </template>
                <template x-if="[form.analisa_man_ket, form.analisa_method_ket, form.analisa_machine_ket, form.analisa_material_ket, form.analisa_environment_ket].filter(Boolean).length === 0">
                    <div class="flex items-center gap-4">
                        <div class="w-5 h-5 rounded border border-slate-200 bg-white flex items-center justify-center shrink-0"></div>
                        <div class="flex-1 text-[13px] font-medium text-slate-400 pb-2 border-b border-dashed border-slate-200">—</div>
                    </div>
                </template>
                <div class="flex items-center gap-4 opacity-50">
                    <div class="w-5 h-5 rounded border border-slate-300 bg-white flex items-center justify-center shrink-0"></div>
                    <div class="flex-1 border-b border-dashed border-slate-200 mt-2"></div>
                </div>
            </div>
        </div>

        <!-- 2 Actions side by side -->
        <div class="grid grid-cols-[1fr_1fr] border-b border-slate-200 print:border-slate-800 bg-white">
            <!-- Correction -->
            <div class="p-6 flex flex-col justify-start relative">
                <div class="flex items-center gap-2 mb-6 pl-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <h3 class="text-[10px] font-black text-emerald-700 uppercase tracking-widest">LANGKAH PENANGGULANGAN SEMENTARA (CORRECTION)</h3>
                </div>

                <table class="w-full text-left pl-2">
                    <thead>
                        <tr>
                            <th class="w-full"></th>
                            <th class="text-[10px] min-w-[90px] px-2 font-black text-slate-800 text-center pb-3 uppercase tracking-wider">Target</th>
                            <th class="text-[10px] min-w-[60px] px-2 font-black text-slate-800 text-center pb-3 uppercase tracking-wider">PIC</th>
                            <th class="text-[10px] min-w-[60px] px-2 font-black text-slate-800 text-center pb-3 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>

                    <tbody class="space-y-2">
                        <template x-for="item in (form.correction_items || []).filter(i=>i.text)">
                            <tr>
                                <td class="py-2 flex items-start gap-3 pr-4">
                                    <div class="w-5 h-5 rounded border border-emerald-600 bg-emerald-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                    <span class="text-[13px] font-bold text-slate-700 leading-snug break-words" x-text="item.text"></span>
                                </td>
                                <td class="text-[11px] font-medium text-slate-500 text-center px-2" x-text="(item.target || '').split('T')[0] || '-'"></td>
                                <td class="text-[11px] font-black text-slate-800 text-center px-2" x-text="item.pic || '-'"></td>
                                <td class="text-center px-2">
                                    <template x-if="item.status">
                                        <svg width="22" height="22" viewBox="0 0 28 28" class="mx-auto">
                                            <circle cx="14" cy="14" r="12" fill="white" stroke="#0F172A" stroke-width="2" />
                                            <path x-show="item.status === 'P'" d="M 14 14 L 14 2 A 12 12 0 0 1 26 14 Z" fill="#0F172A"></path>
                                            <path x-show="item.status === 'D'" d="M 14 14 L 14 26 A 12 12 0 0 0 14 2 Z" fill="#0F172A"></path>
                                            <path x-show="item.status === 'C'" d="M 14 14 L 14 2 A 12 12 0 1 1 2 14 Z" fill="#0F172A"></path>
                                            <path x-show="item.status === 'A'" d="M 14 2 A 12 12 0 1 1 13.999 2 Z" fill="#0F172A"></path>
                                            <line x1="14" y1="2" x2="14" y2="26" :stroke="item.status === 'A' ? 'white' : '#0F172A'" stroke-width="1.5" />
                                            <line x1="2" y1="14" x2="26" y2="14" :stroke="item.status === 'A' ? 'white' : '#0F172A'" stroke-width="1.5" />
                                        </svg>
                                    </template>
                                    <template x-if="!item.status"><span class="text-slate-300 text-[11px]">—</span></template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

            </div>
            
            <!-- Dampak -->
            <div class="p-6 flex flex-col justify-between border-r border-slate-200 print:border-slate-800 relative">
                <div class="flex items-start gap-2 mb-6 pl-2">
                    <svg class="w-5 h-5 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
                    <h3 class="text-[10px] font-black text-purple-700 uppercase tracking-widest leading-snug w-3/4">PENANGGULANGAN DAMPAK PENYEBAB MASALAH PADA PRODUK SEJENIS</h3>
                </div>
                
                <table class="w-full text-left pl-2 mb-6">
                    <thead>
                        <tr>
                            <th class="w-full"></th>
                            <th class="text-[10px] min-w-[90px] px-2 font-black text-slate-800 text-center pb-3 uppercase tracking-wider">Target</th>
                            <th class="text-[10px] min-w-[70px] px-2 font-black text-slate-800 text-center pb-3 uppercase tracking-wider">PIC Seksi</th>
                            <th class="text-[10px] min-w-[60px] px-2 font-black text-slate-800 text-center pb-3 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>

                    <tbody class="space-y-2">
                        <template x-for="item in (form.dampak_items || []).filter(i=>i.text)">
                            <tr>
                                <td class="py-2 flex items-start gap-3 pr-4">
                                    <div class="w-5 h-5 rounded border border-purple-600 bg-purple-600 text-white flex items-center justify-center shrink-0 shadow-sm mt-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg></div>
                                    <span class="text-[13px] font-bold text-slate-700 leading-snug break-words" x-text="item.text"></span>
                                </td>
                                <td class="text-[11px] font-medium text-slate-500 text-center px-2" x-text="(item.target || '').split('T')[0] || '-'"></td>
                                <td class="text-[11px] font-black text-slate-800 text-center px-2" x-text="item.pic_seksi || '-'"></td>
                                <td class="text-center px-2">
                                    <template x-if="item.status">
                                        <svg width="22" height="22" viewBox="0 0 28 28" class="mx-auto">
                                            <circle cx="14" cy="14" r="12" fill="white" stroke="#0F172A" stroke-width="2" />
                                            <path x-show="item.status === 'P'" d="M 14 14 L 14 2 A 12 12 0 0 1 26 14 Z" fill="#0F172A"></path>
                                            <path x-show="item.status === 'D'" d="M 14 14 L 14 26 A 12 12 0 0 0 14 2 Z" fill="#0F172A"></path>
                                            <path x-show="item.status === 'C'" d="M 14 14 L 14 2 A 12 12 0 1 1 2 14 Z" fill="#0F172A"></path>
                                            <path x-show="item.status === 'A'" d="M 14 2 A 12 12 0 1 1 13.999 2 Z" fill="#0F172A"></path>
                                            <line x1="14" y1="2" x2="14" y2="26" :stroke="item.status === 'A' ? 'white' : '#0F172A'" stroke-width="1.5" />
                                            <line x1="2" y1="14" x2="26" y2="14" :stroke="item.status === 'A' ? 'white' : '#0F172A'" stroke-width="1.5" />
                                        </svg>
                                    </template>
                                    <template x-if="!item.status"><span class="text-slate-300 text-[11px]">—</span></template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div class="flex items-center gap-3 pl-2 text-[11px] font-medium text-slate-500 pt-4 border-t border-slate-100 mt-auto">
                    PIC Langkah Perbaikan / Pencegahan : 
                    <span class="bg-red-50 text-red-600 px-4 py-1.5 rounded-lg font-black text-[13px]" x-text="form.pic_seksi || 'D/S'"></span>
                </div>
            </div>
        </div>

        <!-- Actions Table -->
        <div class="p-6 border-b border-slate-200 print:border-slate-800 bg-white">
            <div class="flex items-center gap-2 mb-6">
                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                <h3 class="text-[11px] font-black text-slate-800 uppercase tracking-widest">LANGKAH PERBAIKAN / PENCEGAHAN</h3>
            </div>
            <table class="w-full text-left border-t border-slate-200 pt-2">
                <thead>
                    <tr>
                        <th class="text-[10px] font-medium text-slate-500 pb-4 pt-3 pl-2">Tindakan</th>
                        <th class="text-[10px] font-medium text-slate-500 text-center pb-4 pt-3">Schedule</th>
                        <th class="text-[10px] font-medium text-slate-500 text-center pb-4 pt-3">Verif I</th>
                        <th class="text-[10px] font-medium text-slate-500 text-center pb-4 pt-3">Verif II</th>
                        <th class="text-[10px] font-medium text-slate-500 text-center pb-4 pt-3">Verif III</th>
                        <th class="text-[10px] font-medium text-slate-500 text-center pb-4 pt-3">PDCA</th>
                        <th class="text-[10px] font-medium text-slate-500 text-center pb-4 pt-3 pr-2">PIC</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(a, idx) in (form.actions || []).filter(a=>a.action)">
                        <tr class="border-t border-slate-100 hover:bg-slate-50/50 transition-colors">
                            <td class="py-3.5 pl-2 flex items-start gap-4">
                                <div class="w-6 h-6 rounded bg-red-600 text-white text-[10px] font-black flex items-center justify-center shrink-0 shadow-sm mt-0.5" x-text="idx+1"></div>
                                <div class="flex flex-col gap-1.5">
                                    <span class="text-[13px] font-bold text-slate-800 leading-snug" x-text="a.action"></span>
                                    
                                    <template x-if="a.evidence_remarks || a.evidence_file">
                                        <div class="flex flex-wrap gap-2 items-center text-[10px] mt-1">
                                            <template x-if="a.evidence_remarks">
                                                <span class="text-slate-500 italic bg-slate-50 px-2 py-0.5 rounded border border-slate-100" x-text="'Hasil: ' + a.evidence_remarks"></span>
                                            </template>
                                            <template x-if="a.evidence_file">
                                                <a :href="a.evidence_file" target="_blank" class="text-pink-600 font-bold hover:underline inline-flex items-center gap-1 bg-pink-50 px-2 py-0.5 rounded border border-pink-100 print:hidden" title="Lihat Foto Bukti">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                    Lampiran Bukti
                                                </a>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </td>
                            <td class="text-center text-[11px] font-medium text-slate-600" x-text="a.schedule ? a.schedule.split('T')[0] : '-'"></td>
                            <td class="text-center text-[11px] font-black">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-slate-800" x-text="a.tgl_verif_1 ? a.tgl_verif_1.split('T')[0] : '—'"></span>
                                    <template x-if="a.verif_1_status">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold" :class="a.verif_1_status === 'OK' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100'" x-text="a.verif_1_status"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="text-center text-[11px] font-black">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-slate-800" x-text="a.tgl_verif_2 ? a.tgl_verif_2.split('T')[0] : '—'"></span>
                                    <template x-if="a.verif_2_status">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold" :class="a.verif_2_status === 'OK' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100'" x-text="a.verif_2_status"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="text-center text-[11px] font-black">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-slate-800" x-text="a.tgl_verif_3 ? a.tgl_verif_3.split('T')[0] : '—'"></span>
                                    <template x-if="a.verif_3_status">
                                        <span class="px-2 py-0.5 rounded text-[9px] font-bold" :class="a.verif_3_status === 'OK' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-red-50 text-red-600 border border-red-100'" x-text="a.verif_3_status"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="text-center">
                                <template x-if="a.pdca">
                                    <svg width="20" height="20" viewBox="0 0 28 28" class="mx-auto">
                                        <circle cx="14" cy="14" r="12" fill="white" stroke="#0F172A" stroke-width="2" />
                                        <path x-show="a.pdca === 'P'" d="M 14 14 L 14 2 A 12 12 0 0 1 26 14 Z" fill="#0F172A" style="display: none;" />
                                        <path x-show="a.pdca === 'D'" d="M 14 14 L 14 26 A 12 12 0 0 0 14 2 Z" fill="#0F172A" style="display: none;" />
                                        <path x-show="a.pdca === 'C'" d="M 14 14 L 14 2 A 12 12 0 1 1 2 14 Z" fill="#0F172A" style="display: none;" />
                                        <path x-show="a.pdca === 'A'" d="M 14 2 A 12 12 0 1 1 13.999 2 Z" fill="#0F172A" style="display: none;" />
                                        <line x1="14" y1="2" x2="14" y2="26" :stroke="a.pdca === 'A' ? 'white' : '#0F172A'" stroke-width="1.5" />
                                        <line x1="2" y1="14" x2="26" y2="14" :stroke="a.pdca === 'A' ? 'white' : '#0F172A'" stroke-width="1.5" />
                                    </svg>
                                </template>
                                <template x-if="!a.pdca"><span class="text-slate-300">—</span></template>
                            </td>
                            <td class="text-center text-[13px] font-black text-slate-800 pr-2" x-text="a.pic || '-'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- A3 Report Summary -->
        <template x-if="form.is_a3_required">
            <div class="px-5 py-5 border-b border-red-100 bg-red-50/50 flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-black text-red-800 mb-1">A3 Report Diperlukan</h4>
                    <p class="text-[11px] text-red-600 font-medium mb-4">Telah terjadi kegagalan verifikasi 3 kali berturut-turut. Penanggulangan masalah ini dilanjutkan dan dilampirkan melalui dokumen A3 Report.</p>
                    <div class="flex flex-wrap items-center gap-x-10 gap-y-4">
                        <div>
                            <span class="text-[10px] font-bold text-red-400 block mb-0.5">DUE DATE A3</span>
                            <span class="text-[13px] font-black text-slate-800" x-text="form.a3_due_date ? new Date(form.a3_due_date).toLocaleDateString('id-ID', {day:'numeric', month:'short', year:'numeric'}) : 'Belum Ditentukan'"></span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-red-400 block mb-0.5">DOKUMEN / LINK</span>
                            <template x-if="form.a3_document">
                                <a :href="form.a3_document.startsWith('http') ? form.a3_document : '#'" target="_blank" class="text-[13px] font-black text-blue-600 hover:text-blue-800 underline break-all flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    <span x-text="form.a3_document"></span>
                                </a>
                            </template>
                            <template x-if="!form.a3_document">
                                <span class="text-[13px] font-black text-slate-400">Belum dilampirkan</span>
                            </template>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-red-400 block mb-0.5">STATUS VERIFIKASI A3</span>
                            <template x-if="form.status === 'Close'">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-black">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    APPROVED BY QA
                                </div>
                            </template>
                            <template x-if="form.status !== 'Close'">
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-black">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    WAITING APPROVAL
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Signatures -->
        <div class="flex min-h-[160px] bg-white">
            <!-- Disetujui badge -->
            <div class="w-48 flex flex-col justify-center items-center text-center relative !print:color-adjust-exact transition-colors" :class="getStatusBadgeStyles(form.status).bg" style="clip-path: polygon(0 0, 100% 0, 75% 100%, 0 100%); -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                <div class="flex flex-col items-center mr-8">
                    <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center mb-3 shadow-md transition-colors" :class="getStatusBadgeStyles(form.status).text">
                        <template x-if="form.status === 'Close'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </template>
                        <template x-if="form.status !== 'Close'">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </template>
                    </div>
                    <div class="text-white text-[11px] font-black tracking-widest uppercase" x-text="getStatusBadgeLabel(form.status)"></div>
                </div>
            </div>
            
            <!-- Signers -->
            <div class="flex-1 flex justify-around p-5 pl-0 items-center">
                
                <!-- Kasie / Seksi -->
                @php $authRole = auth()->user()->role ?? ''; $authName = auth()->user()->name ?? ''; @endphp


                <template x-for="(s, idx) in seksiSigners" :key="s.id">
                    <div class="flex flex-col items-center relative group/seksi">
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <span class="text-[11px] font-black text-slate-800" x-text="s.role"></span>
                            <div class="w-2 h-2 rounded-full bg-[#E11D2A]"></div>
                        </div>
                        <div class="text-[10px] font-bold text-slate-500 mb-2 invisible">D/S</div>
                        <template x-if="(userRole === 'Admin' || (userRole === 'Operator' && form.created_by == userId)) && form.status !== 'Close'">
                            <button type="button" @click="removeSigner(s.id)" class="absolute right-0 top-0 text-red-500 hover:text-red-700 opacity-0 group-hover/seksi:opacity-100 print:hidden"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                        </template>
                        
                        <template x-if="canSignRole(s.role)">
                            <div class="h-16 w-32 relative mb-2 flex items-center justify-center cursor-pointer group" @click="openSignaturePad(s.id)">
                                <template x-if="s.signature">
                                    <img :src="s.signature" class="max-h-full opacity-80 mix-blend-multiply">
                                </template>
                                <template x-if="!s.signature">
                                    <div class="text-[10px] text-slate-300 group-hover:text-red-500 font-bold transition-colors">TTD</div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!canSignRole(s.role)">
                            <div class="h-16 w-32 relative mb-2 flex items-center justify-center border border-dashed border-slate-200 rounded-lg bg-slate-50/60">
                                <template x-if="s.signature">
                                    <img :src="s.signature" class="max-h-full opacity-80 mix-blend-multiply">
                                </template>
                                <template x-if="!s.signature">
                                    <div class="text-[10px] text-slate-300 flex flex-col items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span class="text-[9px]">Terkunci</span>
                                    </div>
                                </template>
                            </div>
                        </template>
                        
                        <template x-if="seksiOptions.includes(s.role)">
                            <p class="w-32 text-center text-[11px] font-black text-slate-800" x-text="s.nama || 'Menunggu TTD'"></p>
                        </template>
                        <template x-if="!seksiOptions.includes(s.role)">
                            <input type="text" :value="s.nama" @input="updateSignerName(s.id, $event.target.value)" placeholder="Nama" class="w-32 text-center text-[11px] font-black text-slate-800 border-none p-0 outline-none focus:ring-0 bg-transparent placeholder-slate-300">
                        </template>
                    </div>
                </template>

                <!-- GL / Foreman -->
                <div class="flex flex-col items-center" x-data="{ get s() { return foremanSigner; } }">
                    <template x-if="s">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <span class="text-[11px] font-black text-slate-800">GL / Foreman</span>
                                <div class="w-2 h-2 rounded-full bg-[#E11D2A]"></div>
                            </div>
                            <div class="text-[10px] font-bold text-slate-500 mb-2 invisible">D/S</div>
                            
                            <template x-if="canSignRole('Foreman')">
                            <div>
                            <div class="h-16 w-32 relative mb-2 flex items-center justify-center cursor-pointer group" @click="openSignaturePad(s.id)">
                                <template x-if="s.signature">
                                    <img :src="s.signature" class="max-h-full opacity-80 mix-blend-multiply">
                                </template>
                                <template x-if="!s.signature">
                                    <div class="text-[10px] font-bold text-red-500">✍ TTD</div>
                                </template>
                            </div>
                            <p class="w-32 text-center text-[11px] font-black text-slate-800" x-text="s.nama || '{{ $authName }}'"></p>
                            </div>
                            </template>
                            <template x-if="!canSignRole('Foreman')">
                            <div>
                            <div class="h-16 w-32 relative mb-2 flex items-center justify-center border border-dashed border-slate-200 rounded-lg bg-slate-50/60">
                                <template x-if="s.signature">
                                    <img :src="s.signature" class="max-h-full opacity-80 mix-blend-multiply">
                                </template>
                                <template x-if="!s.signature">
                                    <div class="text-[10px] text-slate-300 flex flex-col items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span class="text-[9px]">Terkunci</span>
                                    </div>
                                </template>
                            </div>
                            <p class="w-32 text-center text-[11px] font-black text-slate-800" x-text="s.nama || 'Menunggu TTD'"></p>
                            </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Operator -->
                <div class="flex flex-col items-center" x-data="{ get s() { return operatorSigner; } }">
                    <template x-if="s">
                        <div class="flex flex-col items-center">
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <span class="text-[11px] font-black text-slate-800">Operator</span>
                                <div class="w-2 h-2 rounded-full bg-[#E11D2A]"></div>
                            </div>
                            <div class="text-[10px] font-bold text-slate-500 mb-2 invisible">D/S</div>
                            
                            <template x-if="canSignRole('Operator')">
                            <div>
                            <div class="h-16 w-32 relative mb-2 flex items-center justify-center cursor-pointer group" @click="openSignaturePad(s.id)">
                                <template x-if="s.signature">
                                    <img :src="s.signature" class="max-h-full opacity-80 mix-blend-multiply">
                                </template>
                                <template x-if="!s.signature">
                                    <div class="text-[10px] font-bold text-red-500">✍ TTD</div>
                                </template>
                            </div>
                            <p class="w-32 text-center text-[11px] font-black text-slate-800">{{ $authName }}</p>
                            </div>
                            </template>
                            <template x-if="!canSignRole('Operator')">
                            <div>
                            <div class="h-16 w-32 relative mb-2 flex items-center justify-center border border-dashed border-slate-200 rounded-lg bg-slate-50/60">
                                <template x-if="s.signature">
                                    <img :src="s.signature" class="max-h-full opacity-80 mix-blend-multiply">
                                </template>
                                <template x-if="!s.signature">
                                    <div class="text-[10px] text-slate-300 flex flex-col items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <span class="text-[9px]">Terkunci</span>
                                    </div>
                                </template>
                            </div>
                            <p class="w-32 text-center text-[11px] font-black text-slate-800" x-text="s.nama || '-'"></p>
                            </div>
                            </template>
                        </div>
                    </template>
                </div>
                
            </div>
        </div>
    </div> <!-- END OF MAIN CONTAINER -->

    <!-- Footer -->
    <div class="flex justify-between items-center px-2 py-4">
        <div class="flex items-center gap-2 text-[10px] font-bold text-slate-500">
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            Dibuat pada • <span x-text="form.created_at ? new Date(form.created_at).toLocaleDateString('id-ID', {day:'numeric',month:'long',year:'numeric'}) : '—'"></span> • <span x-text="form.created_at ? new Date(form.created_at).toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'}) : '—'"></span>
        </div>
        <!-- <div class="flex items-center gap-2 text-[10px] font-bold text-slate-500">
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Dicetak oleh • <span>{{ auth()->user()->name ?? '-' }} ({{ auth()->user()->employee_id ?? '-' }})</span>
        </div> -->
    </div>

</div>

{{-- Signature Modal --}}
<div x-show="showPadFor !== null" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm print:hidden" style="display: none;" x-transition>
    <div class="bg-white rounded-[24px] shadow-2xl w-full max-w-lg overflow-hidden border border-slate-200" @click.away="closeSignaturePad()">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
            <h3 class="text-lg font-black text-slate-800">Tanda Tangan Digital</h3>
            <button type="button" @click="closeSignaturePad()" class="text-slate-400 hover:text-red-500 transition-colors">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm font-medium text-slate-500 mb-3">Silahkan tanda tangan di dalam kotak berikut:</p>
            <div class="border-2 border-dashed border-slate-300 rounded-2xl bg-slate-50/50 overflow-hidden relative">
                <canvas id="signature-canvas" 
                        @mousedown="startDrawing($event)" @mousemove="draw($event)" @mouseup="stopDrawing()" @mouseleave="stopDrawing()"
                        @touchstart="startDrawing($event)" @touchmove="draw($event)" @touchend="stopDrawing()"
                        class="w-full h-56 cursor-crosshair touch-none"></canvas>
            </div>
            
            <div class="flex items-center justify-between mt-6">
                <button type="button" @click="clearSignature()" class="px-5 py-2.5 text-sm font-bold text-slate-600 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-colors">
                    Bersihkan
                </button>
                <div class="flex gap-3">
                    <button type="button" @click="closeSignaturePad()" class="px-5 py-2.5 text-sm font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition-colors">
                        Batal
                    </button>
                    <button type="button" @click="saveSignature()" class="px-8 py-2.5 text-sm font-black text-white bg-red-600 hover:bg-red-700 shadow-lg shadow-red-500/20 rounded-xl transition-all">
                        Simpan TTD
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ================================================================ --}}
{{-- PRINT-ONLY: Layout Formulir Fisik (Seperti Gambar 1) --}}
{{-- Hanya muncul saat print/unduh PDF, tersembunyi di layar --}}
{{-- ================================================================ --}}
<div class="print-only qpr-print-form" style="width:100%; max-width:100%;">

    {{-- Kotak besar pembungkus form (border tebal 2px) --}}
    <div style="border: 2px solid #000; width: 100%;">
        
        {{-- Header Perusahaan (Di dalam kotak) --}}
        <table style="margin-bottom:0; width: 100%;">
            <tr class="no-border">
                <td class="no-border" style="width:60%; padding:4px 8px 0 8px;">
                    <div style="font-weight:bold; font-size:10pt;">PT. INTI PANTJA PRESS INDUSTRI</div>
                </td>
                <td class="no-border" style="text-align:right; font-size:8pt; padding:4px 8px 0 8px;">FISM-QAD-03-03-01</td>
            </tr>
        </table>

        {{-- Header Judul Form --}}
        <div style="border-bottom: 2px solid #000; padding: 0 0 10px 0; text-align: center;">
            <div style="font-size: 18pt; font-weight: bold; letter-spacing: 1px; transform: scaleY(1.1); font-family: 'Times New Roman', serif;">FORMULIR QUALITY PROBLEM REPORT</div>
        </div>

        {{-- Baris 1: No Job, Model, Tanggal --}}
        <table style="width:100%; border-collapse:collapse; margin-bottom:0;">
            <tr>
                <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; width:33%; padding:4px 8px;">
                    <span style="font-weight:bold;">NO JOB :</span> <span style="font-family:'Comic Sans MS', cursive, sans-serif; font-size:12pt; margin-left:4px;" x-text="form.no_job || ''"></span>
                </td>
                <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; width:27%; padding:4px 8px;">
                    <span style="font-weight:bold;">MODEL :</span> <span style="font-family:'Comic Sans MS', cursive, sans-serif; font-size:12pt; margin-left:4px;" x-text="form.model || ''"></span>
                </td>
                <td style="border:none; border-bottom:1px solid #000; width:40%; padding:4px 8px;">
                    <span style="font-weight:bold;">TANGGAL :</span> <span style="font-family:'Comic Sans MS', cursive, sans-serif; font-size:12pt; margin-left:4px;" x-text="form.tanggal ? new Date(form.tanggal).toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'}) : ''"></span>
                </td>
            </tr>
        </table>

        {{-- Baris 2: Nama Part, No QPR --}}
        <table style="width:100%; border-collapse:collapse; margin-bottom:0;">
            <tr>
                <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; width:50%; padding:4px 8px;">
                    <span style="font-weight:bold;">NAMA PART :</span> <span style="font-family:'Comic Sans MS', cursive, sans-serif; font-size:12pt; margin-left:4px;" x-text="form.nama_part || ''"></span>
                </td>
                <td style="border:none; border-bottom:1px solid #000; width:50%; padding:4px 8px;">
                    <span style="font-weight:bold;">NO QPR :</span> <span style="font-family:'Comic Sans MS', cursive, sans-serif; font-size:12pt; margin-left:4px;" x-text="form.no_qpr || ''"></span>
                </td>
            </tr>
        </table>

        {{-- Baris 3: Kondisi Part, Stock, Deskripsi Problem --}}
        <table style="width:100%; border-collapse:collapse; margin-bottom:0;">
            <tr>
                {{-- KONDISI PART --}}
                <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; width:28%; padding:6px; vertical-align:top;">
                    <div style="font-weight:bold; text-decoration:underline; margin-bottom:12px;">KONDISI PART :</div>
                    <table style="width:100%; border:none; margin-top:12px; margin-bottom:8px;">
                        <tr>
                            <td style="border:none; width:60%; font-weight:bold; padding:4px 0;">REWORK / PCS</td>
                            <td style="border:1px solid #000; width:40%; text-align:center; height:24px; font-family:'Comic Sans MS', cursive, sans-serif; font-size:11pt;" x-text="form.rework_qty || '—'"></td>
                        </tr>
                        <tr><td colspan="2" style="border:none; height:12px;"></td></tr>
                        <tr>
                            <td style="border:none; font-weight:bold; padding:4px 0;">REJECT / PCS</td>
                            <td style="border:1px solid #000; text-align:center; height:24px; font-family:'Comic Sans MS', cursive, sans-serif; font-size:11pt;" x-text="form.reject_qty || '—'"></td>
                        </tr>
                    </table>
                </td>

                {{-- STOCK IPPI, RENCANA PRODUKSI, PROSES REPAIR --}}
                <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; width:34%; padding:0; vertical-align:top;">
                    <table style="width:100%; border:none; height:100%; border-collapse:collapse;">
                        <tr>
                            <td style="border:none; border-bottom:1px solid #000; padding:8px; font-weight:bold; height:33.33%;">
                                STOCK IPPI / PCS <span style="float:right;">: <span style="display:inline-block; width:80px; text-align:center; font-family:'Comic Sans MS', cursive, sans-serif; font-weight:normal; font-size:12pt;" x-text="form.stock_ippi_qty || ''"></span></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; border-bottom:1px solid #000; padding:8px; font-weight:bold; height:33.33%;">
                                RENCANA PRODUKSI <span style="float:right;">: <span style="display:inline-block; width:80px; text-align:center; font-family:'Comic Sans MS', cursive, sans-serif; font-weight:normal; font-size:12pt;" x-text="form.rencana_produksi ? form.rencana_produksi.split('T')[0] : ''"></span></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; padding:8px; font-weight:bold; height:33.34%;">
                                PROSES REPAIR <span style="float:right;">: <span style="display:inline-block; width:80px; text-align:center; font-family:'Comic Sans MS', cursive, sans-serif; font-weight:normal; font-size:12pt;" x-text="form.proses_repair || ''"></span></span>
                            </td>
                        </tr>
                    </table>
                </td>

                {{-- DESKRIPSI PROBLEM --}}
                <td style="border:none; border-bottom:1px solid #000; width:38%; padding:0; vertical-align:top;">
                    <table style="width:100%; border:none; height:100%; border-collapse:collapse;">
                        <tr>
                            <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; padding:4px; font-weight:bold; text-align:center; width:65%;">DESKRIPSI PROBLEM</td>
                            <td style="border:none; border-bottom:1px solid #000; padding:4px; font-weight:bold; text-align:center; width:35%;">Last Date<br>Problem</td>
                        </tr>
                        <tr>
                            <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; padding:8px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:16px; height:16px; border:2px solid #000; display:flex; align-items:center; justify-content:center; font-family:sans-serif; font-weight:bold; font-size:12px;" x-text="form.kategori_problem === 'Baru Pertama' ? '✓' : ''"></div>
                                    <span style="font-weight:bold; letter-spacing:1px; font-size:9pt;">BARU PERTAMA</span>
                                </div>
                            </td>
                            <td rowspan="3" style="border:none; padding:8px; text-align:center; vertical-align:top; font-family:'Comic Sans MS', cursive, sans-serif; font-size:11pt;" x-text="form.last_date_problem ? new Date(form.last_date_problem).toLocaleDateString('id-ID') : ''"></td>
                        </tr>
                        <tr>
                            <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; padding:8px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:16px; height:16px; border:2px solid #000; display:flex; align-items:center; justify-content:center; font-family:sans-serif; font-weight:bold; font-size:12px;" x-text="(form.kategori_problem === 'Kadang Kadang' || form.kategori_problem === 'Kadang-Kadang') ? '✓' : ''"></div>
                                    <span style="font-weight:bold; letter-spacing:1px; font-size:9pt;">KADANG - KADANG</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="border:none; border-right:1px solid #000; padding:8px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:16px; height:16px; border:2px solid #000; display:flex; align-items:center; justify-content:center; font-family:sans-serif; font-weight:bold; font-size:12px;" x-text="form.kategori_problem === 'Sering' ? '✓' : ''"></div>
                                    <span style="font-weight:bold; letter-spacing:1px; font-size:9pt;">SERING</span>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

    {{-- Sketch & Jenis Problem --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:0;">
        <tr>
            {{-- Sketch --}}
            <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; width:75%; padding:4px; vertical-align:middle; height:170px;">
                <div style="display:flex; flex-wrap:wrap; gap:4px; justify-content:center; align-items:center; height:100%;">
                    <template x-for="src in (form.sketches || [])">
                        <img :src="src" style="max-height:210px; max-width:100%; object-fit:contain;">
                    </template>
                </div>
            </td>
            
            {{-- Area & Jenis Problem Sidebar --}}
            <td style="border:none; border-bottom:1px solid #000; width:25%; padding:16px 12px; vertical-align:top;">
                <div style="font-size:9pt; text-align:center; margin-bottom:8px; font-weight:bold;">AREA PROBLEM</div>
                <template x-if="form.area">
                    <div style="text-align:center; margin-bottom:16px;">
                        <template x-for="a in form.area.split(',').map(s=>s.trim()).filter(Boolean)">
                            <div style="display:inline-block; border:1px solid #000; padding:2px 6px; font-size:10pt; font-family:'Comic Sans MS', cursive, sans-serif; margin:2px;" x-text="a"></div>
                        </template>
                    </div>
                </template>
                <template x-if="!form.area">
                    <div>
                        <div style="width:80%; height:14px; background-color:#e5e7eb; margin:0 auto 16px auto;"></div>
                    </div>
                </template>

                <div style="border-bottom:1px dashed #000; margin-bottom:12px;"></div>

                <div style="font-size:9pt; text-align:center; margin-bottom:8px; font-weight:bold;">JENIS PROBLEM</div>
                <template x-if="form.defect">
                    <div style="text-align:center; margin-bottom:30px;">
                        <template x-for="d in form.defect.split(',').map(s=>s.trim()).filter(Boolean)">
                            <div style="display:inline-block; border:1px solid #000; padding:4px 8px; font-size:10pt; font-family:'Comic Sans MS', cursive, sans-serif; margin:2px;" x-text="d"></div>
                        </template>
                    </div>
                </template>
                <template x-if="!form.defect">
                    <div>
                        <div style="width:80%; height:14px; background-color:#e5e7eb; margin:0 auto 10px auto;"></div>
                        <div style="width:80%; height:14px; background-color:#e5e7eb; margin:0 auto 30px auto;"></div>
                    </div>
                </template>
                
                <div style="font-size:9pt; margin-bottom:8px; font-weight:bold;">KETERANGAN DETAIL:</div>
                <template x-if="form.defect_keterangan">
                    <div style="font-size:9pt; font-family:'Comic Sans MS', cursive, sans-serif; white-space:pre-wrap; min-height:40px; border-bottom:1px solid #000; padding-bottom:8px;" x-text="form.defect_keterangan"></div>
                </template>
                <template x-if="!form.defect_keterangan">
                    <div>
                        <div style="border-bottom:1.5px dotted #000; width:100%; margin-bottom:20px;"></div>
                        <div style="border-bottom:1.5px dotted #000; width:100%;"></div>
                    </div>
                </template>
            </td>
        </tr>
    </table>

    {{-- Lokasi, Shift, Jam, Dokumen --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:0;">
        <tr>
            <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; width:25%; padding:8px 8px; vertical-align:top;">
                <span style="font-size:10pt;">Lokasi Kejadian :</span> <br> <span style="font-family:'Comic Sans MS', cursive, sans-serif; font-size:11pt;" x-text="form.lokasi || ''"></span>
            </td>
            <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; width:20%; padding:8px 8px; vertical-align:top;">
                <span style="font-size:10pt;">Shift :</span> <br> <span style="font-family:'Comic Sans MS', cursive, sans-serif; font-size:11pt;" x-text="form.shift || ''"></span>
            </td>
            <td style="border:none; border-bottom:1px solid #000; border-right:1px solid #000; width:20%; padding:8px 8px; vertical-align:top;">
                <span style="font-size:10pt;">Jam :</span> <br> <span style="font-family:'Comic Sans MS', cursive, sans-serif; font-size:11pt;" x-text="form.jam || ''"></span>
            </td>
            <td style="border:none; border-bottom:1px solid #000; padding:8px 8px; vertical-align:top;">
                <div style="margin-bottom:6px; font-size:10pt;">Dokumen Referensi pembuatan QPR :</div>
                <div style="font-family:'Comic Sans MS', cursive, sans-serif; font-size:11pt;" x-text="form.dokumen || ''"></div>
            </td>
        </tr>
    </table>

    {{-- Analisa Penyebab --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:0; border-bottom:2px solid #000;">
        <tr>
            <td colspan="2" class="section-title" style="background:#f0f0f0; border-bottom:1px solid #000; padding:6px 8px; font-weight:bold; font-size:9pt;">
                Analisa Penyebab ( Man, Method, Machines, Material, Environment ) :
            </td>
        </tr>
        <template x-for="k in [
            {key:'analisa_man', ket:'analisa_man_ket', label:'Man'},
            {key:'analisa_method', ket:'analisa_method_ket', label:'Method'},
            {key:'analisa_machine', ket:'analisa_machine_ket', label:'Machine'},
            {key:'analisa_material', ket:'analisa_material_ket', label:'Material'},
            {key:'analisa_environment', ket:'analisa_environment_ket', label:'Environment'}
        ].filter(x => form[x.key])">
            <tr>
                <td style="width:130px; border-right:1px solid #000; border-bottom:1px solid #000; padding:4px 8px; font-weight:bold; font-size:9pt; vertical-align:middle;">
                    <span style="margin-right:8px; font-size:12pt;">☑</span>
                    <span x-text="k.label"></span>
                </td>
                <td style="border-bottom:1px solid #000; padding:4px 8px; font-size:9pt; font-family:'Comic Sans MS', cursive, sans-serif; vertical-align:middle;" x-text="form[k.ket] || '-'"></td>
            </tr>
        </template>

        <!-- Placeholder empty rows to keep the table height looking normal -->
        <template x-if="[form.analisa_man, form.analisa_method, form.analisa_machine, form.analisa_material, form.analisa_environment].filter(Boolean).length < 2">
            <tr>
                <td style="width:130px; border-right:1px solid #000; border-bottom:1px solid #000; padding:4px 8px; font-weight:bold; font-size:12pt; vertical-align:middle;">☐</td>
                <td style="border-bottom:1px solid #000; padding:4px 8px;"></td>
            </tr>
        </template>
        <template x-if="[form.analisa_man, form.analisa_method, form.analisa_machine, form.analisa_material, form.analisa_environment].filter(Boolean).length === 0">
            <tr>
                <td style="width:130px; border-right:1px solid #000; border-bottom:1px solid #000; padding:4px 8px; font-weight:bold; font-size:12pt; vertical-align:middle;">☐</td>
                <td style="border-bottom:1px solid #000; padding:4px 8px;"></td>
            </tr>
        </template>
    </table>

    {{-- Correction & Dampak side by side (Unified Table for Perfect Alignment) --}}
    <div x-data="{ 
        get corr() { return (form.correction_items || []).filter(x => x.text); }, 
        get damp() { return (form.dampak_items || []).filter(x => x.text); }, 
        get maxLen() { return Math.max(this.corr.length, this.damp.length, 1); } 
    }">
        <table style="width:100%; border-collapse:collapse; margin-bottom:0; table-layout:fixed;">
            <colgroup>
                <col style="width:25px;">
                <col style="width:auto;">
                <col style="width:55px;">
                <col style="width:55px;">
                <col style="width:45px;">
                <col style="width:25px;">
                <col style="width:auto;">
                <col style="width:55px;">
                <col style="width:55px;">
                <col style="width:45px;">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="2" style="border:1px solid #000; border-left:none; background:#fff; font-size:8pt; padding:6px; text-align:center;">
                        Langkah Penanggulangan Sementara<br>(Correction) :
                    </th>
                    <th style="border:1px solid #000; font-size:8pt; padding:4px; width:55px; text-align:center;">Target</th>
                    <th style="border:1px solid #000; font-size:8pt; padding:4px; width:55px; text-align:center;">PIC</th>
                    <th style="border:1px solid #000; font-size:8pt; padding:4px; width:45px; text-align:center;">Status</th>
                    
                    <th colspan="2" style="border:1px solid #000; background:#fff; font-size:8pt; padding:4px; text-align:center;">
                        Penanggulangan terhadap dampak<br>penyebab masalah yg sama yg dapat<br>terjadi pada produk/ proses lain yang<br>sejenis :
                    </th>
                    <th style="border:1px solid #000; font-size:8pt; padding:4px; width:55px; text-align:center;">Target</th>
                    <th style="border:1px solid #000; font-size:8pt; padding:4px; width:55px; text-align:center;">PIC</th>
                    <th style="border:1px solid #000; border-right:none; font-size:8pt; padding:4px; width:45px; text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="i in Array.from({length: maxLen}, (_, idx) => idx)">
                    <tr style="vertical-align:top;">
                        {{-- CORRECTION LEFT --}}
                        <td style="border-left:none; border-right:none; border-bottom:1px solid #000; width:30px; text-align:right; padding:6px 4px 4px 4px; font-size:12pt; font-family:sans-serif;" 
                            x-text="corr[i] ? '☑' : '☐'"></td>
                        <td style="border-left:none; border-right:1px solid #000; border-bottom:1px solid #000; padding:8px 8px 6px 4px; font-size:9pt; font-family:'Comic Sans MS', cursive, sans-serif;" 
                            x-text="corr[i] ? corr[i].text : ''"></td>
                        <td style="border:1px solid #000; border-bottom:1px solid #000; text-align:center; padding:6px 4px; font-size:8pt;" 
                            x-text="(corr[i] && corr[i].target) ? corr[i].target.split('T')[0] : ''"></td>
                        <td style="border:1px solid #000; border-bottom:1px solid #000; text-align:center; padding:6px 4px; font-size:8pt;" 
                            x-text="corr[i] ? corr[i].pic : ''"></td>
                        <td style="border:1px solid #000; border-bottom:1px solid #000; text-align:center; padding:6px 4px;">
                            <template x-if="corr[i] && corr[i].status">
                                <svg width="14" height="14" viewBox="0 0 28 28" style="margin:0 auto; display:block;">
                                    <circle cx="14" cy="14" r="12" fill="white" stroke="#000" stroke-width="2"/>
                                    <path x-show="corr[i].status==='P'" d="M14 14 L14 2 A12 12 0 0 1 26 14 Z" fill="#000"/>
                                    <path x-show="corr[i].status==='D'" d="M14 14 L14 26 A12 12 0 0 0 14 2 Z" fill="#000"/>
                                    <path x-show="corr[i].status==='C'" d="M14 14 L14 2 A12 12 0 1 1 2 14 Z" fill="#000"/>
                                    <path x-show="corr[i].status==='A'" d="M14 2 A12 12 0 1 1 13.999 2 Z" fill="#000"/>
                                    <line x1="14" y1="2" x2="14" y2="26" :stroke="corr[i].status==='A'?'white':'#000'" stroke-width="1.5"/>
                                    <line x1="2" y1="14" x2="26" y2="14" :stroke="corr[i].status==='A'?'white':'#000'" stroke-width="1.5"/>
                                </svg>
                            </template>
                        </td>

                        {{-- DAMPAK RIGHT --}}
                        <td style="border-left:1px solid #000; border-right:none; border-bottom:1px solid #000; width:30px; text-align:right; padding:6px 4px 4px 4px; font-size:12pt; font-family:sans-serif;" 
                            x-text="damp[i] ? '☑' : '☐'"></td>
                        <td style="border-left:none; border-right:1px solid #000; border-bottom:1px solid #000; padding:8px 8px 6px 4px; font-size:9pt; font-family:'Comic Sans MS', cursive, sans-serif;" 
                            x-text="damp[i] ? damp[i].text : ''"></td>
                        <td style="border:1px solid #000; border-bottom:1px solid #000; text-align:center; padding:6px 4px; font-size:8pt;" 
                            x-text="(damp[i] && damp[i].target) ? damp[i].target.split('T')[0] : ''"></td>
                        <td style="border:1px solid #000; border-bottom:1px solid #000; text-align:center; padding:6px 4px; font-size:8pt;" 
                            x-text="damp[i] ? damp[i].pic_seksi : ''"></td>
                        <td style="border:1px solid #000; border-right:none; border-bottom:1px solid #000; text-align:center; padding:6px 4px;">
                            <template x-if="damp[i] && damp[i].status">
                                <svg width="14" height="14" viewBox="0 0 28 28" style="margin:0 auto; display:block;">
                                    <circle cx="14" cy="14" r="12" fill="white" stroke="#000" stroke-width="2"/>
                                    <path x-show="damp[i].status==='P'" d="M14 14 L14 2 A12 12 0 0 1 26 14 Z" fill="#000"/>
                                    <path x-show="damp[i].status==='D'" d="M14 14 L14 26 A12 12 0 0 0 14 2 Z" fill="#000"/>
                                    <path x-show="damp[i].status==='C'" d="M14 14 L14 2 A12 12 0 1 1 2 14 Z" fill="#000"/>
                                    <path x-show="damp[i].status==='A'" d="M14 2 A12 12 0 1 1 13.999 2 Z" fill="#000"/>
                                    <line x1="14" y1="2" x2="14" y2="26" :stroke="damp[i].status==='A'?'white':'#000'" stroke-width="1.5"/>
                                    <line x1="2" y1="14" x2="26" y2="14" :stroke="damp[i].status==='A'?'white':'#000'" stroke-width="1.5"/>
                                </svg>
                            </template>
                        </td>
                    </tr>
                </template>
                <tr>
                    <td colspan="5" style="border-top:1px solid #000; border-right:1px solid #000;"></td>
                    <td colspan="5" style="border-top:1px solid #000; padding:6px 8px; font-size:8pt;">
                        PIC Langkah Perbaikan / Pencegahan : Seksi <span x-text="form.pic_seksi || ''"></span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Langkah Perbaikan / Pencegahan --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:0; table-layout:fixed;">
        <thead>
            <tr style="background:#f0f0f0;">
                <th rowspan="2" style="text-align:center; width:50%;">Langkah Perbaikan / Pencegahan<br><small>( Corrective / Preventive Action )</small></th>
                <th rowspan="2" style="text-align:center; width:11%;">Schedule</th>
                <th colspan="3" style="text-align:center; width:24%;">Tanggal Verifikasi</th>
                <th colspan="2" style="text-align:center; width:7%;">PDCA</th>
                <th rowspan="2" style="text-align:center; width:8%;">PIC</th>
            </tr>
            <tr style="background:#f0f0f0;">
                <th style="text-align:center; width:8%;">I</th>
                <th style="text-align:center; width:8%;">II</th>
                <th style="text-align:center; width:8%;">III</th>
                <th colspan="2"></th>
            </tr>
        </thead>
        <tbody>
            <template x-for="(a, idx) in (form.actions || []).filter(a => a.action)">
                <tr>
                    <td x-text="a.action"></td>
                    <td style="text-align:center; font-size:8pt;" x-text="a.schedule ? a.schedule.split('T')[0] : ''"></td>
                    <td style="text-align:center; font-size:8pt;" x-text="a.tgl_verif_1 ? a.tgl_verif_1.split('T')[0] : ''"></td>
                    <td style="text-align:center; font-size:8pt;" x-text="a.tgl_verif_2 ? a.tgl_verif_2.split('T')[0] : ''"></td>
                    <td style="text-align:center; font-size:8pt;" x-text="a.tgl_verif_3 ? a.tgl_verif_3.split('T')[0] : ''"></td>
                    <td style="text-align:center;" colspan="2">
                        <template x-if="a.pdca">
                            <svg width="16" height="16" viewBox="0 0 28 28" style="margin:0 auto; display:block;">
                                <circle cx="14" cy="14" r="12" fill="white" stroke="#000" stroke-width="2"/>
                                <path x-show="a.pdca==='P'" d="M14 14 L14 2 A12 12 0 0 1 26 14 Z" fill="#000" style="display:none;"/>
                                <path x-show="a.pdca==='D'" d="M14 14 L14 26 A12 12 0 0 0 14 2 Z" fill="#000" style="display:none;"/>
                                <path x-show="a.pdca==='C'" d="M14 14 L14 2 A12 12 0 1 1 2 14 Z" fill="#000" style="display:none;"/>
                                <path x-show="a.pdca==='A'" d="M14 2 A12 12 0 1 1 13.999 2 Z" fill="#000" style="display:none;"/>
                                <line x1="14" y1="2" x2="14" y2="26" :stroke="a.pdca==='A'?'white':'#000'" stroke-width="1.5"/>
                                <line x1="2" y1="14" x2="26" y2="14" :stroke="a.pdca==='A'?'white':'#000'" stroke-width="1.5"/>
                            </svg>
                        </template>
                    </td>
                    <td style="text-align:center; font-size:8pt;" x-text="a.pic || ''"></td>
                </tr>
            </template>
            <template x-for="i in Math.max(0, 2 - (form.actions || []).filter(a => a.action).length)">
                <tr style="height:22px;"><td></td><td></td><td></td><td></td><td></td><td colspan="2"></td><td></td></tr>
            </template>
        </tbody>
    </table>

    {{-- Seksi Terkait & Tanda Tangan --}}
    <div x-data="{ 
        get seksiSigs() { 
            let sigs = form.approval_signatures || [];
            if (typeof sigs === 'string') {
                try { sigs = JSON.parse(sigs); } catch(e) { sigs = []; }
            }
            return sigs.filter(s => s.position === 'seksi');
        }
    }">
        <table style="margin-bottom:0; table-layout:fixed; width:100%;">
            <tr>
                <td colspan="4" class="center bold" style="background:#f0f0f0;">Seksi Terkait</td>
                <td class="center" style="width:15%; background:#f0f0f0;">Diperiksa oleh</td>
                <td class="center" style="width:15%; background:#f0f0f0;">Dibuat oleh</td>
            </tr>
            <tr>
                <template x-for="i in 4">
                    <td class="sig-cell">
                        <template x-if="seksiSigs[i-1] && seksiSigs[i-1].signature">
                            <img :src="seksiSigs[i-1].signature" style="max-height:45px; max-width:80px; object-fit:contain; display:block; margin:0 auto;">
                        </template>
                    </td>
                </template>
                <td class="sig-cell">
                    <template x-if="foremanSigner && foremanSigner.signature">
                        <img :src="foremanSigner.signature" style="max-height:45px; max-width:80px; object-fit:contain; display:block; margin:0 auto;">
                    </template>
                </td>
                <td class="sig-cell">
                    <template x-if="operatorSigner && operatorSigner.signature">
                        <img :src="operatorSigner.signature" style="max-height:45px; max-width:80px; object-fit:contain; display:block; margin:0 auto;">
                    </template>
                </td>
            </tr>
            <tr>
                <template x-for="i in 4">
                    <td class="center" style="font-size:8pt; padding-top:4px;" x-text="seksiSigs[i-1] ? seksiSigs[i-1].role : ''"></td>
                </template>
                <td class="center" style="font-size:8pt; padding-top:4px;">GL / Foreman</td>
                <td class="center" style="font-size:8pt; padding-top:4px;">Operator</td>
            </tr>
        </table>
    </div>
    </div> {{-- Akhir dari kotak besar pembungkus form --}}
    
</div>

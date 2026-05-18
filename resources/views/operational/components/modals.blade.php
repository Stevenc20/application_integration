{{-- FINISH JOB MODAL --}}
<div id="finishModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden z-[9999] items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all">
        <div class="p-8 space-y-6">
            <div class="text-center">
                <div class="w-20 h-20 rounded-3xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-4 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <h3 class="text-2xl font-black text-gray-800">Finalisasi Produksi</h3>
                <p class="text-gray-600 text-sm italic">Proses untuk item <b id="finishJobName" class="text-gray-800">-</b> telah selesai.</p>
            </div>
            
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                <label class="block text-xs font-black text-blue-400 uppercase tracking-widest mb-2">Pilih Item Berikutnya</label>
                <select id="nextSelect" class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-300 outline-none bg-white">
                    <option value="">AUTO – lanjut ke urutan berikutnya</option>
                </select>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="bg-blue-50 p-3 rounded-xl border border-blue-100">
                    <label class="block text-[10px] font-black text-blue-400 uppercase mb-1">Actual OK</label>
                    <input type="number" id="final-ok" value="0" class="w-full bg-transparent font-black text-blue-700 outline-none text-lg">
                </div>
                <div class="bg-orange-50 p-3 rounded-xl border border-orange-100">
                    <label class="block text-[10px] font-black text-orange-400 uppercase mb-1">Repair</label>
                    <input type="number" id="final-repair" value="0" class="w-full bg-transparent font-black text-orange-700 outline-none text-lg">
                </div>
                <div class="bg-red-50 p-3 rounded-xl border border-red-100">
                    <label class="block text-[10px] font-black text-red-400 uppercase mb-1">Reject</label>
                    <input type="number" id="final-reject" value="0" class="w-full bg-transparent font-black text-red-700 outline-none text-lg">
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <button onclick="submitFinalJob()" class="flex-1 px-4 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg shadow-blue-100 transition-all">Simpan & Selesai</button>
                <button onclick="closeFinishModal()" class="flex-1 px-4 py-3 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold text-sm transition-all">Batal</button>
            </div>
        </div>
    </div>
</div>

{{-- CONFIRM MODAL --}}
<div id="confirmModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden z-[9999] items-center justify-center p-4">
    <div id="confirmContent" class="bg-white rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all duration-300 scale-95 opacity-0">
        <div class="p-8 text-center">
            <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h4 id="confirmTitle" class="text-xl font-black text-gray-800 mb-2">Selesaikan Proses?</h4>
            <p id="confirmText" class="text-gray-500 text-sm leading-relaxed italic">Waktu akan dikunci ke dalam Saved Runtime.</p>
        </div>
        <div class="flex border-t border-gray-100 bg-gray-50/50 p-4 gap-3">
            <button onclick="closeConfirmModal()" class="flex-1 px-6 py-3 rounded-2xl bg-white border border-gray-200 text-gray-600 font-bold text-xs hover:bg-gray-100 transition-all uppercase tracking-widest">Batal</button>
            <button id="confirmBtn" class="flex-1 px-6 py-3 rounded-2xl bg-red-600 text-white font-bold text-xs hover:bg-red-700 transition-all shadow-lg shadow-red-100 uppercase tracking-widest">Ya, Selesai</button>
        </div>
    </div>
</div>

{{-- DOWNTIME MODAL --}}
<div id="downtimeModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden z-[9999] items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl border border-gray-100 overflow-hidden flex flex-col max-h-[90vh]">
        <div class="border-b border-gray-100 px-6 py-4 flex items-center justify-between bg-red-50">
            <div>
                <h3 class="font-bold text-red-800 text-lg">Laporan Downtime / Try Out</h3>
                <p class="text-sm text-red-600 mt-0.5">Lengkapi data untuk Job: <span id="dtJobNumber" class="font-bold uppercase tracking-widest">-</span></p>
            </div>
            <button onclick="closeDowntimeModal()" class="w-8 h-8 rounded-lg bg-white border border-red-200 text-red-400 hover:text-red-600 flex items-center justify-center transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        
        <div class="p-6 overflow-y-auto flex-1 space-y-6">
            {{-- ADD NEW FORM --}}
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5">
                <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4 flex items-center gap-2">
                    <span class="w-2 h-5 bg-red-500 rounded-full"></span>
                    <span id="dtFormTitle">Lengkapi Detail Masalah</span>
                </h4>
                <input type="hidden" id="dtEditId" value="">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Jenis Downtime</label>
                        <select id="dtJenis" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 outline-none transition">
                            <option value="produksi">Produksi</option>
                            <option value="mesin">Mesin</option>
                            <option value="dies">Dies (Daise)</option>
                            <option value="logistic">Logistic</option>
                            <option value="material">Material</option>
                            <option value="try out">Try Out</option>
                            <option value="break time">Break Time</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Problem</label>
                        <input type="text" id="dtProblem" placeholder="Masalah yang terjadi..." class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Penyebab</label>
                        <input type="text" id="dtPenyebab" placeholder="Penyebab masalah..." class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Action</label>
                        <input type="text" id="dtAction" placeholder="Tindakan yang diambil..." class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 outline-none transition">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">PIC</label>
                        <input type="text" id="dtPIC" placeholder="Nama penanggung jawab..." class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 outline-none transition">
                    </div>
                    <div class="flex items-end gap-2">
                        <button id="dtBtnSave" onclick="saveDowntime()" class="flex-1 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-lg shadow-blue-100 transition-all flex items-center justify-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                            <span id="dtBtnText">Simpan Laporan</span>
                        </button>
                        <button onclick="closeDowntimeModal()" class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-100 font-bold text-sm transition-all">
                            Isi Nanti
                        </button>
                    </div>
                </div>
            </div>

            {{-- LIST TABLE --}}
            <div>
                <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4">Riwayat Downtime Hari Ini</h4>
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase">Jenis</th>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase">Problem / Penyebab</th>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase">PIC</th>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase">Waktu (Start - Finish)</th>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase text-center">Durasi</th>
                                <th class="px-4 py-3 text-[10px] font-black text-gray-400 uppercase text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="downtimeListBody" class="divide-y divide-gray-100">
                            {{-- JS will render here --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="fixed top-5 right-5 z-[9999] hidden min-w-[260px] px-5 py-3 rounded-xl shadow-2xl text-white font-medium transition-all"></div>

{{-- FLOATING TOOLTIP --}}
<div id="timeline-tooltip" class="fixed hidden pointer-events-none z-[9999] bg-slate-900/95 backdrop-blur-md border border-slate-700 p-3 rounded-xl shadow-2xl transition-opacity duration-200 min-w-[150px]">
    <div class="flex flex-col gap-1">
        <div id="tooltip-type" class="text-[9px] font-black uppercase tracking-widest text-blue-400">Production</div>
        <div id="tooltip-time" class="text-xs font-black text-white font-mono">07:40 - 08:00</div>
        <div class="h-px bg-slate-700 my-1"></div>
        <div class="flex items-center justify-between gap-4">
            <span class="text-[8px] font-bold text-slate-500 uppercase">Duration</span>
            <span id="tooltip-dur" class="text-[10px] font-black text-white">20m 0s</span>
        </div>
    </div>
</div>

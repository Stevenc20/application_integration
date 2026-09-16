{{-- Modal Container --}}
<div x-cloak>
    
    {{-- 1. MODAL LEMBAR INSPEKSI --}}
    <template x-if="selectedLI">
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto" @click="closeModal()">
            <div class="bg-white w-full max-w-5xl rounded-[2.5rem] shadow-2xl mb-10 overflow-hidden mt-10" @click.stop>
                
                {{-- Header --}}
                <div class="px-8 py-5 border-b border-slate-100 flex items-center justify-between bg-white sticky top-0 z-10">
                    <div class="flex items-center gap-4">
                        <div class="w-11 h-11 bg-red-600 text-white rounded-2xl flex items-center justify-center font-black shadow-lg shadow-red-600/20 text-lg">L</div>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 tracking-tight">Review Dokumen Inspeksi</h3>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest" x-text="`${selectedLI.no_form || '—'} · ${selectedLI.part_name || '—'}`"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <a :href="`{{ url('/li') }}/${selectedLI.id}/edit`" target="_blank" 
                           class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-black rounded-xl flex items-center gap-2 transition-all uppercase tracking-wide">
                            🔍 Buka Form Lengkap
                        </a>
                        <button @click="closeModal()" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all">✕</button>
                    </div>
                </div>

                <div class="flex flex-col lg:flex-row">

                    {{-- LEFT: Full Document Info --}}
                    <div class="flex-1 p-8 border-b lg:border-b-0 lg:border-r border-slate-100 space-y-6 overflow-y-auto" style="max-height: 75vh;">
                        
                        {{-- Part Identity --}}
                        <div>
                            <p class="text-[9px] font-black text-red-500 uppercase tracking-[3px] mb-3 flex items-center gap-2">
                                <span class="w-4 h-0.5 bg-red-500 rounded"></span> Identitas Part
                            </p>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Job No.</p>
                                    <p class="text-sm font-black text-slate-800" x-text="selectedLI.job_no || '—'"></p>
                                </div>
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Part Name</p>
                                    <p class="text-sm font-black text-slate-800" x-text="selectedLI.part_name || '—'"></p>
                                </div>
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Part No.</p>
                                    <p class="text-sm font-black text-slate-800" x-text="selectedLI.part_no || '—'"></p>
                                </div>
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Type</p>
                                    <p class="text-sm font-black text-slate-800" x-text="selectedLI.type || '—'"></p>
                                </div>
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Spec Material</p>
                                    <p class="text-sm font-black text-slate-800" x-text="selectedLI.spec_material || '—'"></p>
                                </div>
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Type Pallet</p>
                                    <p class="text-sm font-black text-slate-800" x-text="selectedLI.type_pallet || '—'"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Production Info --}}
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[3px] mb-3 flex items-center gap-2">
                                <span class="w-4 h-0.5 bg-slate-300 rounded"></span> Info Produksi
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Lokasi</p>
                                    <p class="text-sm font-black text-slate-800" x-text="selectedLI.lokasi || '—'"></p>
                                </div>
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Shift</p>
                                    <p class="text-sm font-black text-slate-800" x-text="selectedLI.shift || '—'"></p>
                                </div>
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Tanggal</p>
                                    <p class="text-sm font-black text-slate-800" x-text="fmtDate(selectedLI.tgl_bulan)"></p>
                                </div>
                            </div>
                        </div>

                        {{-- Sketch --}}
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[3px] mb-3 flex items-center gap-2">
                                <span class="w-4 h-0.5 bg-slate-300 rounded"></span> Sketch Part
                            </p>
                            <div class="bg-slate-100 rounded-2xl overflow-hidden flex items-center justify-center" style="min-height:160px;">
                                <template x-if="selectedLI.sketch_url">
                                    <img :src="`${config.apiUrl}/${selectedLI.sketch_url}`" class="w-full object-contain" style="max-height:220px;" />
                                </template>
                                <template x-if="!selectedLI.sketch_url">
                                    <div class="text-slate-400 text-xs font-bold py-10">Sketch belum diupload</div>
                                </template>
                            </div>
                        </div>

                        {{-- Item Check Standards Summary --}}
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[3px] mb-3 flex items-center gap-2">
                                <span class="w-4 h-0.5 bg-slate-300 rounded"></span> Standar Item Check
                            </p>
                            <div class="space-y-2">
                                {{-- Dimensions 1-7 --}}
                                <template x-if="selectedLI.dimensi1">
                                    <div class="flex items-center gap-3 bg-slate-50 rounded-xl px-4 py-2.5 border border-slate-100">
                                        <span class="text-[9px] font-black text-slate-400 w-6">1</span>
                                        <span class="text-[11px] font-bold text-slate-700" x-text="selectedLI.dimensi1"></span>
                                    </div>
                                </template>
                                <template x-if="selectedLI.dimensi2">
                                    <div class="flex items-center gap-3 bg-slate-50 rounded-xl px-4 py-2.5 border border-slate-100">
                                        <span class="text-[9px] font-black text-slate-400 w-6">2</span>
                                        <span class="text-[11px] font-bold text-slate-700" x-text="selectedLI.dimensi2"></span>
                                    </div>
                                </template>
                                <template x-if="selectedLI.dimensi3">
                                    <div class="flex items-center gap-3 bg-slate-50 rounded-xl px-4 py-2.5 border border-slate-100">
                                        <span class="text-[9px] font-black text-slate-400 w-6">3</span>
                                        <span class="text-[11px] font-bold text-slate-700" x-text="selectedLI.dimensi3"></span>
                                    </div>
                                </template>
                                <template x-if="selectedLI.dimensi4">
                                    <div class="flex items-center gap-3 bg-slate-50 rounded-xl px-4 py-2.5 border border-slate-100">
                                        <span class="text-[9px] font-black text-slate-400 w-6">4</span>
                                        <span class="text-[11px] font-bold text-slate-700" x-text="selectedLI.dimensi4"></span>
                                    </div>
                                </template>
                                <template x-if="selectedLI.dimensi5">
                                    <div class="flex items-center gap-3 bg-slate-50 rounded-xl px-4 py-2.5 border border-slate-100">
                                        <span class="text-[9px] font-black text-slate-400 w-6">5</span>
                                        <span class="text-[11px] font-bold text-slate-700" x-text="selectedLI.dimensi5"></span>
                                    </div>
                                </template>
                                <template x-if="selectedLI.dimensi6">
                                    <div class="flex items-center gap-3 bg-slate-50 rounded-xl px-4 py-2.5 border border-slate-100">
                                        <span class="text-[9px] font-black text-slate-400 w-6">6</span>
                                        <span class="text-[11px] font-bold text-slate-700" x-text="selectedLI.dimensi6"></span>
                                    </div>
                                </template>
                                <template x-if="selectedLI.dimensi7">
                                    <div class="flex items-center gap-3 bg-slate-50 rounded-xl px-4 py-2.5 border border-slate-100">
                                        <span class="text-[9px] font-black text-slate-400 w-6">7</span>
                                        <span class="text-[11px] font-bold text-slate-700" x-text="selectedLI.dimensi7"></span>
                                    </div>
                                </template>

                                {{-- Appearance 13-14 --}}
                                <template x-if="selectedLI.appearance13">
                                    <div class="flex items-center gap-3 bg-blue-50 rounded-xl px-4 py-2.5 border border-blue-100">
                                        <span class="text-[9px] font-black text-blue-400 w-6">13</span>
                                        <span class="text-[11px] font-bold text-slate-700" x-text="selectedLI.appearance13"></span>
                                    </div>
                                </template>
                                <template x-if="selectedLI.appearance14">
                                    <div class="flex items-center gap-3 bg-blue-50 rounded-xl px-4 py-2.5 border border-blue-100">
                                        <span class="text-[9px] font-black text-blue-400 w-6">14</span>
                                        <span class="text-[11px] font-bold text-slate-700" x-text="selectedLI.appearance14"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Revision History --}}
                        <template x-if="selectedLI.revision_records && selectedLI.revision_records.filter(r => r.record).length > 0">
                            <div>
                                <p class="text-[9px] font-black text-amber-500 uppercase tracking-[3px] mb-3 flex items-center gap-2">
                                    <span class="w-4 h-0.5 bg-amber-400 rounded"></span> Riwayat Revisi
                                </p>
                                <div class="space-y-2">
                                    <template x-for="(rev, idx) in selectedLI.revision_records.filter(r => r.record).slice(-3).reverse()" :key="idx">
                                        <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
                                            <div class="flex justify-between items-center mb-1">
                                                <span class="text-[8px] font-black text-amber-600 uppercase" x-text="rev.date || '—'"></span>
                                            </div>
                                            <p class="text-[10px] font-bold text-slate-700 leading-relaxed" x-text="rev.record"></p>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- RIGHT: Signature Panel --}}
                    <div class="w-full lg:w-[340px] p-8 flex flex-col gap-6 bg-white">
                        
                        <template x-if="liAlreadySigned || done">
                            <div class="flex-1 flex flex-col items-center justify-center text-center p-8 bg-emerald-50 border-2 border-emerald-100 rounded-[2rem] text-emerald-700">
                                <div class="text-5xl mb-4">✅</div>
                                <p class="text-lg font-black">Dokumen Disetujui!</p>
                                <p class="text-sm font-bold opacity-80 mt-2">TTD <span x-text="userName"></span> telah disimpan.</p>
                            </div>
                        </template>

                        <template x-if="!liAlreadySigned && !done">
                            <div class="flex-1 flex flex-col gap-5">
                                
                                {{-- Role Label --}}
                                <div class="flex items-center gap-3">
                                    <div class="w-1 h-5 bg-red-600 rounded-full"></div>
                                    <div>
                                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-[2px]">Anda sedang meninjau sebagai</p>
                                        <p class="text-[11px] font-black text-red-600 uppercase tracking-wide" x-text="liRoleContext.label"></p>
                                    </div>
                                </div>

                                {{-- Prepared By Info --}}
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Dibuat Oleh (QA Leader)</p>
                                    <p class="text-sm font-black text-slate-800" x-text="selectedLI.qg_name || '—'"></p>
                                    <p class="text-[9px] text-slate-400 font-bold mt-0.5" x-text="fmtDate(selectedLI.tgl_bulan)"></p>
                                </div>

                                {{-- Signature Area --}}
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-3">Area Tanda Tangan</p>
                                    <div class="border-2 border-dashed border-slate-200 rounded-[1.5rem] flex flex-col items-center justify-center cursor-pointer hover:border-red-400 hover:bg-red-50/50 group transition-all relative overflow-hidden"
                                         style="min-height: 180px;"
                                         @click="handleOpenPadLi(null)">
                                        <template x-if="pendingSig">
                                            <img :src="pendingSig" class="max-h-full object-contain p-4" style="max-height:160px;" />
                                        </template>
                                        <template x-if="!pendingSig">
                                            <div class="text-center p-6">
                                                <div class="w-14 h-14 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform border border-slate-100">
                                                    <span class="text-2xl">🖋️</span>
                                                </div>
                                                <p class="text-[10px] font-black text-slate-400 group-hover:text-red-600 uppercase tracking-widest">Klik untuk Tanda Tangan</p>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Notes --}}
                                <div>
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2">Catatan / Alasan Revisi</p>
                                    <textarea x-model="catatanRevisi" 
                                              placeholder="Opsional — isi jika ada yang perlu diperbaiki..." 
                                              class="w-full p-4 bg-slate-50 border border-slate-100 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-300 transition-all resize-none placeholder:text-slate-300"
                                              rows="3"></textarea>
                                </div>

                                {{-- Action Buttons --}}
                                <div class="flex gap-3 mt-auto">
                                    <button @click="handleRejectLi()" :disabled="saving" 
                                            class="flex-1 py-3.5 bg-white border-2 border-red-100 text-red-600 rounded-2xl text-[10px] font-black hover:bg-red-50 transition-all disabled:opacity-50 tracking-widest uppercase">
                                        Revisi
                                    </button>
                                    <button @click="handleFinalSubmitLi()" :disabled="saving || !pendingSig" 
                                            class="flex-[2] py-3.5 bg-red-600 text-white rounded-2xl text-[10px] font-black hover:bg-red-700 shadow-lg shadow-red-600/20 transition-all active:scale-95 disabled:opacity-50 disabled:bg-slate-200 disabled:text-slate-400 tracking-widest uppercase">
                                        <span x-text="saving ? 'Menyimpan...' : 'Simpan TTD'"></span>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- 2. MODAL QPR --}}
    <template x-if="selected">
        <div class="fixed inset-0 z-50 flex items-start justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto" @click="closeModal()">
            <div class="bg-white w-full max-w-2xl rounded-[2.5rem] shadow-2xl mb-10 overflow-hidden mt-20" @click.stop>
                <div class="p-8">
                    {{-- Header --}}
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-red-50 text-red-600 border border-red-100 rounded-2xl flex items-center justify-center font-black text-xl">Q</div>
                        <div>
                            <h3 class="text-xl font-black text-slate-800 tracking-tight">Approval QPR</h3>
                            <p class="text-xs font-bold text-red-600" x-text="selected.qpr?.no_qpr"></p>
                        </div>
                        <a :href="`{{ url('/qpr') }}/${selected.qpr?.id}/edit`" target="_blank" 
                           class="ml-auto px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-black rounded-xl flex items-center gap-2 transition-all uppercase tracking-wide">
                            🔍 Buka Dokumen QPR
                        </a>
                    </div>

                    {{-- Role Context Badge --}}
                    <div class="mb-6 flex items-center gap-3">
                        <template x-if="selected.type === 'foreman'">
                            <div class="flex items-center gap-2 bg-red-50 border border-red-100 rounded-2xl px-4 py-2.5">
                                <div class="w-2 h-2 bg-red-600 rounded-full"></div>
                                <p class="text-[10px] font-black text-red-700 uppercase tracking-widest">Anda menandatangani sebagai GL / Foreman</p>
                            </div>
                        </template>
                        <template x-if="selected.type === 'seksi'">
                            <div class="flex items-center gap-2 bg-blue-50 border border-blue-100 rounded-2xl px-4 py-2.5">
                                <div class="w-2 h-2 bg-blue-600 rounded-full"></div>
                                <p class="text-[10px] font-black text-blue-700 uppercase tracking-widest">Anda menandatangani sebagai Seksi Terkait — <span x-text="selected.role"></span></p>
                            </div>
                        </template>
                    </div>

                    <div class="bg-slate-50 rounded-3xl p-6 mb-6 border border-slate-100">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Problem Description</p>
                        <p class="text-sm font-bold text-slate-700 leading-relaxed" x-text="selected.qpr?.defect_keterangan || 'Cek lampiran detail.'"></p>
                    </div>

                    <div x-show="!done">
                        {{-- Formulir Tanda Tangan & Tombol Action --}}
                        <div x-show="!showRevisiForm">
                            <div class="aspect-[2/1] border-2 border-dashed border-slate-200 rounded-3xl flex flex-col items-center justify-center cursor-pointer hover:border-red-400 hover:bg-red-50/50 transition-all mb-6 relative overflow-hidden"
                                 @click="showPad = true">
                                <template x-if="pendingSig">
                                    <img :src="pendingSig" class="max-h-full object-contain p-4" />
                                </template>
                                <template x-if="!pendingSig">
                                    <div class="text-center text-slate-400">
                                        <div class="text-4xl mb-2">✍️</div>
                                        <p class="text-xs font-bold uppercase tracking-widest">Sentuh untuk Tanda Tangan</p>
                                    </div>
                                </template>
                            </div>
                            
                            <div class="flex flex-col sm:flex-row gap-3">
                                <template x-if="selected.type === 'foreman'">
                                    <button @click="showRevisiForm = true" type="button"
                                            class="sm:flex-1 py-4 bg-amber-100 text-amber-700 rounded-2xl text-sm font-black hover:bg-amber-200 transition-all border-2 border-amber-200/50">
                                        ⚠️ MINTA REVISI
                                    </button>
                                </template>
                                <button @click="handleSavePadQpr(pendingSig, selected.position)" :disabled="saving || !pendingSig" 
                                        class="sm:flex-[2] py-4 bg-red-600 text-white rounded-2xl text-sm font-black hover:bg-red-700 shadow-xl shadow-red-600/20 transition-all disabled:opacity-50">
                                    <span x-text="saving ? 'Menyimpan...' : 'SUBMIT APPROVAL'"></span>
                                </button>
                            </div>
                        </div>

                        {{-- Form Revisi --}}
                        <div x-show="showRevisiForm" x-cloak class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Catatan Revisi untuk Operator</label>
                                <textarea x-model="catatanRevisi" rows="3" placeholder="Tuliskan bagian mana yang perlu diperbaiki..."
                                          class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 focus:bg-white focus:border-amber-400 focus:ring-4 focus:ring-amber-500/10 outline-none transition-all resize-none"></textarea>
                            </div>
                            <div class="flex gap-3">
                                <button @click="showRevisiForm = false; catatanRevisi = ''" type="button"
                                        class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl text-sm font-black hover:bg-slate-200 transition-all">
                                    BATAL
                                </button>
                                <button @click="submitRevisiQpr()" :disabled="saving || !catatanRevisi.trim()" type="button"
                                        class="flex-[2] py-4 bg-amber-500 text-white rounded-2xl text-sm font-black hover:bg-amber-600 shadow-xl shadow-amber-500/20 transition-all disabled:opacity-50 flex items-center justify-center gap-2">
                                    <span x-text="saving ? 'Mengirim...' : 'KIRIM PERMINTAAN REVISI'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div x-show="done" class="text-center py-10 bg-emerald-50 rounded-3xl border-2 border-emerald-100">
                        <p class="text-4xl mb-4">✅</p>
                        <p class="text-lg font-black text-emerald-800">Selesai!</p>
                        <p class="text-sm font-bold text-emerald-600">Approval QPR telah berhasil dikirim.</p>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- 3. SIGNATURE PAD OVERLAY --}}
    <template x-if="showPad">
        <div class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/80 backdrop-blur-md">
            <div class="bg-white p-8 rounded-[3rem] w-full max-w-xl shadow-2xl overflow-hidden" x-data="signaturePad()" @click.stop>
                <div class="text-center mb-6">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[4px] mb-1">E-Signature</p>
                    <h4 class="text-xl font-black text-slate-800">Goreskan Tanda Tangan</h4>
                </div>
                
                <div x-show="!confirming">
                    <div class="relative">
                        <canvas x-ref="canvas" width="600" height="250" class="w-full h-48 border-2 border-slate-100 bg-slate-50 rounded-3xl cursor-crosshair touch-none shadow-inner"
                                @mousedown="start" @mousemove="draw" @mouseup="stop" @mouseleave="stop"
                                @touchstart="start" @touchmove="draw" @touchend="stop"></canvas>
                        <div class="absolute bottom-4 right-4 opacity-30 pointer-events-none font-black text-[10px] text-slate-400 uppercase tracking-widest">Sign Area</div>
                    </div>
                    <div class="flex gap-3 mt-8">
                        <button @click="$parent.showPad = false" class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl text-sm font-bold hover:bg-slate-200 transition-all">BATAL</button>
                        <button @click="clear()" class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl text-sm font-bold hover:bg-slate-200 transition-all">HAPUS</button>
                        <button @click="save()" :disabled="isEmpty" class="flex-[2] py-4 bg-red-600 text-white rounded-2xl text-sm font-black hover:bg-red-700 shadow-xl shadow-red-600/20 disabled:opacity-50 transition-all">KONFIRMASI</button>
                    </div>
                </div>
                
                <div x-show="confirming" class="text-center">
                    <div class="bg-slate-50 border-2 border-slate-100 rounded-3xl p-6 flex items-center justify-center min-h-[150px] mb-8 shadow-inner">
                        <img :src="previewSrc" class="max-h-32 object-contain" />
                    </div>
                    <div class="flex gap-3">
                        <button @click="confirming = false" class="flex-1 py-4 bg-slate-100 text-slate-600 rounded-2xl text-sm font-bold hover:bg-slate-200 transition-all">ULANGI</button>
                        <button @click="$dispatch('signature-confirmed', previewSrc)" class="flex-[2] py-4 bg-emerald-500 text-white rounded-2xl text-sm font-black hover:bg-emerald-600 shadow-xl shadow-emerald-500/20 transition-all">✓ GUNAKAN INI</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

</div>

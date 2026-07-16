{{-- Global Breaktime Parameter Modal (PPC / LKH / Supervisor) --}}
<div id="breaktime-modal" class="fixed inset-0 z-[9998] hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" data-breaktime-close></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-5xl max-h-[92vh] flex flex-col bg-white rounded-2xl shadow-xl border border-[#E5C7C7] overflow-hidden animate-in zoom-in-95 duration-150">
            
            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#E5C7C7] bg-[#991B1B] text-white shrink-0">
                <div>
                    <p class="text-xs text-red-100/80 font-medium mb-1">Factory Timeline Parameter Management</p>
                    <h2 class="text-lg font-black uppercase tracking-tight">Manage Breaktime Parameters</h2>
                    <p class="text-xs text-white/80 mt-0.5">Timeline global — perubahan langsung me-regenerate total timeline PPC &amp; LKH</p>
                </div>
                <button type="button" class="w-9 h-9 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center transition" data-breaktime-close aria-label="Tutup">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="px-6 py-3 bg-[#FFF8F8] border-b border-[#E5C7C7] text-[#991B1B] text-xs hidden" id="breaktime-modal-alert"></div>

            <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                {{-- Preview Area --}}
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="text-[10px] font-black text-gray-500 uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        Preview Active Timeline Break
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs">
                        <span class="px-3 py-1 rounded-lg bg-gray-100 font-bold text-gray-700" id="breaktime-preview-date">—</span>
                        <span class="px-3 py-1 rounded-lg bg-gray-100 font-bold text-gray-700" id="breaktime-preview-shift">—</span>
                    </div>
                </div>
                <div id="breaktime-preview-list" class="flex flex-wrap gap-2 min-h-[2rem]"></div>

                {{-- Table Area --}}
                <div class="overflow-x-auto rounded-xl border border-[#E5C7C7] shadow-sm">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[#FFF8F8] text-[10px] font-black uppercase tracking-wider text-gray-600">
                            <tr>
                                <th class="px-3 py-3 text-left">Nama</th>
                                <th class="px-3 py-3 text-left">Hari</th>
                                <th class="px-3 py-3 text-left">Shift</th>
                                <th class="px-3 py-3 text-center">Start</th>
                                <th class="px-3 py-3 text-center">Finish</th>
                                <th class="px-3 py-3 text-center">Type</th>
                                <th class="px-3 py-3 text-center">Status</th>
                                <th class="px-3 py-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="breaktime-table-body" class="divide-y divide-[#E5C7C7]">
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400 font-medium">Memuat parameter…</td></tr>
                        </tbody>
                    </table>
                </div>

                {{-- Inline Form Panel (Glassmorphism & dashed border) --}}
                <div id="breaktime-form-panel" class="hidden rounded-xl border border-dashed border-[#991B1B]/40 bg-[#FFF8F8]/30 p-4 animate-in slide-in-from-top duration-200">
                    <h3 class="text-xs font-black text-gray-600 uppercase tracking-widest mb-3" id="breaktime-form-title">Tambah Breaktime</h3>
                    <form id="breaktime-form" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        <input type="hidden" name="id" id="breaktime-form-id" value="">
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Nama Parameter</label>
                            <input type="text" name="label" required class="w-full rounded-lg border-gray-300 text-sm focus:border-[#991B1B] focus:ring focus:ring-red-100" placeholder="ISTIRAHAT SIANG">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Hari</label>
                            <select name="hari" required class="w-full rounded-lg border-gray-300 text-sm focus:border-[#991B1B] focus:ring focus:ring-red-100">
                                <option value="semua">Semua</option>
                                <option value="senin">Senin</option>
                                <option value="selasa">Selasa</option>
                                <option value="rabu">Rabu</option>
                                <option value="kamis">Kamis</option>
                                <option value="jumat">Jumat</option>
                                <option value="sabtu">Sabtu</option>
                                <option value="minggu">Minggu</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Shift</label>
                            <input type="text" name="shift" class="w-full rounded-lg border-gray-300 text-sm focus:border-[#991B1B] focus:ring focus:ring-red-100" placeholder="Semua (kosongkan)">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Start</label>
                            <input type="time" name="waktu_mulai" required class="w-full rounded-lg border-gray-300 text-sm focus:border-[#991B1B] focus:ring focus:ring-red-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Finish</label>
                            <input type="time" name="waktu_selesai" required class="w-full rounded-lg border-gray-300 text-sm focus:border-[#991B1B] focus:ring focus:ring-red-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase mb-1">Type</label>
                            <select name="type" required class="w-full rounded-lg border-gray-300 text-sm focus:border-[#991B1B] focus:ring focus:ring-red-100">
                                <option value="istirahat">break / istirahat</option>
                                <option value="cinkorak">cinkorak</option>
                            </select>
                        </div>
                        <div class="flex items-end pb-1.5">
                            <label class="inline-flex items-center gap-2 text-sm font-bold text-gray-700 cursor-pointer">
                                <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-[#991B1B] focus:ring-[#991B1B]">
                                Aktif
                            </label>
                        </div>
                    </form>

                    {{-- Live Preview Impact Card --}}
                    <div class="mt-3 rounded-xl bg-amber-50 border border-amber-200 p-2.5 flex gap-2 text-[10px] text-amber-900">
                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div class="flex-1">
                            <span class="font-black uppercase tracking-wider flex items-center justify-between">
                                <span>Live Constraint Warning:</span>
                                <span id="breaktime-live-impact-status" class="text-[8px] bg-amber-200 text-amber-955 px-2 py-0.5 rounded-full font-black">UP TO DATE</span>
                            </span>
                            <div id="breaktime-live-impact-content" class="font-semibold mt-1 space-y-1">
                                Perubahan waktu akan memaksa timeline regenerasi pada shift terpilih.
                            </div>
                        </div>
                    </div>

                    <div class="mt-3.5 flex gap-2 border-t border-[#E5C7C7] pt-3">
                        <button type="button" id="breaktime-form-save" class="px-5 py-2 bg-[#991B1B] hover:bg-[#7F1D1D] text-white text-xs font-black rounded-xl flex items-center justify-center gap-1.5 shadow-md">
                            <span id="breaktime-save-spinner" class="hidden w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            <span id="breaktime-save-text">Simpan Baris</span>
                        </button>
                        <button type="button" id="breaktime-form-cancel" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl">Batal</button>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="shrink-0 px-6 py-4 border-t border-[#E5C7C7] bg-gray-50 flex flex-wrap items-center justify-between gap-3">
                <button type="button" id="breaktime-btn-add" class="px-4 py-2.5 bg-white border border-[#E5C7C7] hover:border-[#991B1B] text-gray-800 text-xs font-black rounded-xl shadow-sm transition">
                    + Tambah Breaktime
                </button>
                <div class="flex gap-2">
                    <button type="button" id="breaktime-btn-regenerate" class="px-4 py-2.5 bg-slate-700 hover:bg-slate-900 text-white text-xs font-black rounded-xl transition">
                        Regenerate Timeline
                    </button>
                    <button type="button" id="breaktime-btn-apply" class="px-5 py-2.5 bg-[#991B1B] hover:bg-[#7F1D1D] text-white text-xs font-black rounded-xl shadow-md transition">
                        Simpan Perubahan &amp; Muat Ulang
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Global Modal Custom Confirm Dialog --}}
<div id="bt-modal-confirm" class="fixed inset-0 z-[9999] hidden">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" id="bt-modal-confirm-backdrop"></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-2xl border border-red-100 shadow-2xl p-6 text-center animate-in zoom-in-95 duration-150">
            <div class="mx-auto w-12 h-12 rounded-full bg-red-50 flex items-center justify-center text-[#991B1B] mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h3 class="text-sm font-black text-gray-900 uppercase tracking-wider mb-2">Konfirmasi Tindakan</h3>
            <p class="text-xs text-gray-500 font-semibold leading-relaxed mb-6" id="bt-modal-confirm-message">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
            <div class="flex gap-3 justify-center">
                <button type="button" id="bt-modal-confirm-yes" class="px-5 py-2.5 bg-[#991B1B] hover:bg-[#7F1D1D] text-white text-xs font-black rounded-xl shadow-md transition-colors flex-1 uppercase tracking-wider">Ya, Lanjutkan</button>
                <button type="button" id="bt-modal-confirm-no" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-black rounded-xl transition-colors flex-1">Batal</button>
            </div>
        </div>
    </div>
</div>

@include('components.ui.toast')

<script>
window.BreaktimeModalConfig = {
    routes: {
        index: @json(route('supervisor.api.breaktime.index')),
        store: @json(url('/supervisor/api/breaktime-parameters')),
        update: @json(url('/supervisor/api/breaktime-parameters')),
        destroy: @json(url('/supervisor/api/breaktime-parameters')),
        toggle: @json(url('/supervisor/api/breaktime-parameters')),
        regenerate: @json(route('supervisor.api.breaktime.regenerate')),
        preview: @json(route('supervisor.api.breaktime.preview')),
    },
    csrf: @json(csrf_token()),
    defaultDate: @json(now()->toDateString()),
    defaultShift: 'Shift Pagi',
};
</script>
<script src="{{ asset('js/breaktime-modal.js') }}?v=3"></script>

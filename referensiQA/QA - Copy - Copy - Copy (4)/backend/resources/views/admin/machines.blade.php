<x-app-layout :pageTitle="$pageTitle">
<div class="um-wrap max-w-[1100px] mx-auto p-7 pb-[70px]" x-data="machineManagement({ apiUrl: '{{ url('/api/machines') }}' })">
    
    {{-- Styles --}}
    <style>
        .um-wrap { font-family: 'Plus Jakarta Sans', sans-serif !important; }
        .um-wrap * { font-family: 'Plus Jakarta Sans', sans-serif; box-sizing: border-box; }
        .um-row { transition: background 0.1s ease; cursor: default; }
        .um-row:hover { background: #FAFAFA !important; }
        .um-btn { transition: all 0.15s ease; cursor: pointer; }
        .um-btn:hover:not(:disabled) { filter: brightness(0.9); transform: translateY(-1px); }
        .um-new-btn:hover { background: #B91C1C !important; box-shadow: 0 4px 14px rgba(220,38,38,0.35) !important; }
        .um-input:focus { border-color: #DC2626 !important; box-shadow: 0 0 0 3px rgba(220,38,38,0.08) !important; outline: none; }
        @keyframes umToast { from { opacity:0; transform:translateY(-8px) scale(0.95); } to { opacity:1; transform:translateY(0) scale(1); } }
        .um-toast { animation: umToast 0.22s ease forwards; }
        .um-shimmer { background:linear-gradient(90deg,#f0f0f0 25%,#e8e8e8 37%,#f0f0f0 63%); background-size:400% 100%; animation:umShimmer 1.4s ease infinite; border-radius:6px; }
        @keyframes umShimmer { 0%{background-position:100% 50%;} 100%{background-position:0% 50%;} }
    </style>

    {{-- TOAST --}}
    <template x-if="toast">
        <div class="um-toast fixed top-5 right-5 z-[9999] px-5 py-3 rounded-xl shadow-2xl text-sm font-bold border flex items-center gap-2"
             :class="toast.type === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-red-50 border-red-200 text-red-800'">
            <span x-text="toast.type === 'success' ? '✅' : '❌'"></span>
            <span x-text="toast.msg"></span>
        </div>
    </template>

    {{-- HEADER --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-3 mb-6">
        <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-[.15em] mb-1">Administrasi</p>
            <h1 class="text-3xl font-black text-slate-800 tracking-tight mb-1">Master Mesin / Line</h1>
            <p class="text-sm text-slate-500 font-medium">Kelola daftar mesin press dan area produksi</p>
        </div>
        <button @click="openModal()" class="um-new-btn um-btn px-5 py-3 bg-red-600 text-white rounded-xl text-sm font-black flex items-center gap-2 shadow-lg shadow-red-600/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3"><path d="M12 5v14M5 12h14"/></svg>
            Tambah Mesin
        </button>
    </div>

    {{-- SEARCH --}}
    <div class="flex gap-3 mb-4 bg-white border-2 border-slate-100 rounded-2xl p-3 shadow-sm">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input x-model="search" class="um-input w-full pl-10 pr-4 py-2.5 bg-white border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none" placeholder="Cari kode atau nama mesin…">
        </div>
        <button @click="fetchItems()" class="um-btn px-4 py-2.5 bg-white border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-500 hover:text-red-600 hover:border-red-200 hover:bg-red-50 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            Refresh
        </button>
    </div>

    {{-- TABLE --}}
    <div class="bg-white border-2 border-slate-100 rounded-3xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[800px]">
                <thead>
                    <tr class="bg-slate-50 border-b-2 border-slate-100">
                        @foreach(['Kode Mesin', 'Nama / Deskripsi', 'Departemen', 'Status', 'Kondisi', ''] as $h)
                        <th class="px-5 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $h }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <template x-if="loading">
                        <template x-for="i in 3">
                            <tr class="border-b border-slate-50">
                                <td class="p-5" colspan="6"><div class="um-shimmer h-6 w-full"></div></td>
                            </tr>
                        </template>
                    </template>
                    <template x-if="!loading && filteredItems.length === 0">
                        <tr>
                            <td colspan="6" class="p-20 text-center">
                                <div class="text-4xl mb-4">🏭</div>
                                <p class="text-base font-black text-slate-800 mb-1">Tidak ada data mesin ditemukan</p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="item in filteredItems" :key="item.id">
                        <tr class="um-row border-b border-slate-50 last:border-b-0">
                            <td class="px-5 py-4">
                                <span class="px-3 py-1 bg-slate-100 border border-slate-200 text-slate-700 rounded-lg text-[11px] font-black font-mono" x-text="item.code"></span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-sm font-black text-slate-800" x-text="item.name"></span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-xs font-bold text-slate-600" x-text="item.department || '—'"></span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full" :class="item.is_active ? 'bg-emerald-500' : 'bg-slate-300'"></div>
                                    <span class="text-xs font-black" :class="item.is_active ? 'text-emerald-600' : 'text-slate-400'" x-text="item.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="px-2 py-1 rounded text-[10px] font-black uppercase tracking-wider"
                                      :class="item.is_stopped ? 'bg-red-100 text-red-700' : 'bg-blue-50 text-blue-600'" 
                                      x-text="item.is_stopped ? 'Berhenti (Stop)' : 'Berjalan (Run)'"></span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openModal(item)" class="um-btn px-3 py-1.5 bg-blue-50 border border-blue-200 text-blue-600 rounded-xl text-[11px] font-black">✏️ Edit</button>
                                    <button @click="deleteItem(item.id)" class="um-btn px-3 py-1.5 bg-red-50 border border-red-200 text-red-600 rounded-xl text-[11px] font-black">🗑️ Hapus</button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- MODAL --}}
    <div x-show="showModal" x-cloak class="fixed inset-0 z-[10000] bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-white rounded-[32px] w-full max-w-lg shadow-2xl overflow-hidden border-2 border-white" @click.away="showModal = false">
            <div class="p-6 bg-slate-50 border-b-2 border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-red-50 border-2 border-red-100 rounded-2xl flex items-center justify-center text-2xl">
                        <span x-text="selectedItem ? '✏️' : '🏭'"></span>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-800 leading-tight" x-text="selectedItem ? 'Edit Mesin' : 'Tambah Mesin Baru'"></h3>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-wider" x-text="selectedItem ? 'Ubah data mesin' : 'Isi form untuk mesin baru'"></p>
                    </div>
                </div>
                <button @click="showModal = false" class="w-10 h-10 bg-white border-2 border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:text-red-600 hover:border-red-200 transition-all">✕</button>
            </div>

            <form @submit.prevent="handleSubmit()" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Kode Mesin *</label>
                        <input x-model="form.code" required class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:border-red-600 transition-all" placeholder="Misal: PRESS-A">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Departemen</label>
                        <select x-model="form.department" class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:border-red-600 transition-all">
                            <option value="">-- Pilih --</option>
                            @foreach(['Stamping', 'Welding', 'Assembly', 'Machining'] as $d)
                            <option value="{{ $d }}">{{ $d }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nama / Deskripsi *</label>
                    <input x-model="form.name" required class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-sm font-bold text-slate-700 outline-none focus:border-red-600 transition-all" placeholder="Mesin Press 1500T Line A">
                </div>

                <div x-show="selectedItem" class="grid grid-cols-2 gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.is_active" class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-600">
                        <span class="text-sm font-bold text-slate-700">Aktif (Bisa Dipakai)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.is_stopped" class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-600">
                        <span class="text-sm font-bold text-slate-700">Mesin Berhenti (Stop)</span>
                    </label>
                </div>

                <div class="pt-4 border-t-2 border-slate-50 flex gap-3">
                    <button type="button" @click="showModal = false" class="flex-1 py-3 border-2 border-slate-200 rounded-2xl text-sm font-black text-slate-500 hover:bg-slate-50 transition-all">Batal</button>
                    <button type="submit" :disabled="submitting" class="flex-[2] py-3 bg-red-600 text-white rounded-2xl text-sm font-black shadow-lg shadow-red-600/20 disabled:opacity-50 flex items-center justify-center gap-2">
                        <span x-show="submitting" class="w-4 h-4 border-2 border-white/20 border-t-white rounded-full animate-spin"></span>
                        <span x-text="selectedItem ? 'Simpan Perubahan' : 'Tambah Mesin'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('machineManagement', (config) => ({
        items: [],
        search: '',
        loading: false,
        submitting: false,
        toast: null,
        showModal: false,
        selectedItem: null,
        form: { code: '', name: '', department: 'Stamping', is_active: true, is_stopped: false },

        init() {
            this.fetchItems();
        },
        
        get filteredItems() {
            if (this.search === '') return this.items;
            const q = this.search.toLowerCase();
            return this.items.filter(i => 
                (i.code && i.code.toLowerCase().includes(q)) ||
                (i.name && i.name.toLowerCase().includes(q)) ||
                (i.department && i.department.toLowerCase().includes(q))
            );
        },

        showToast(msg, type = 'success') {
            this.toast = { msg, type };
            setTimeout(() => this.toast = null, 3000);
        },

        async fetchItems() {
            this.loading = true;
            try {
                const res = await window.axios.get(config.apiUrl);
                this.items = res.data;
            } catch (err) {
                this.showToast('Gagal memuat data', 'error');
            } finally {
                this.loading = false;
            }
        },

        openModal(item = null) {
            this.selectedItem = item;
            if (item) {
                this.form = { ...item, is_active: !!item.is_active, is_stopped: !!item.is_stopped };
            } else {
                this.form = { code: '', name: '', department: 'Stamping', is_active: true, is_stopped: false };
            }
            this.showModal = true;
        },

        async handleSubmit() {
            this.submitting = true;
            try {
                if (this.selectedItem) {
                    await window.axios.put(`${config.apiUrl}/${this.selectedItem.id}`, this.form);
                    this.showToast('Data mesin diperbarui!');
                } else {
                    await window.axios.post(config.apiUrl, this.form);
                    this.showToast('Mesin baru ditambahkan!');
                }
                this.showModal = false;
                this.fetchItems();
            } catch (err) {
                const msg = err.response?.data?.message || 'Terjadi kesalahan';
                this.showToast(msg, 'error');
            } finally {
                this.submitting = false;
            }
        },

        async deleteItem(id) {
            if (!confirm('Yakin ingin menghapus mesin ini? Semua data terkait mungkin akan hilang.')) return;
            try {
                await window.axios.delete(`${config.apiUrl}/${id}`);
                this.showToast('Mesin berhasil dihapus!');
                this.fetchItems();
            } catch (err) {
                this.showToast('Gagal menghapus mesin', 'error');
            }
        }
    }));
});
</script>
@endpush
</x-app-layout>

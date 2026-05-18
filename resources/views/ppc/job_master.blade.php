@extends('layouts.ppc')
@section('title', 'Job Master')

@section('content')
<div class="space-y-6">

    {{-- Hero Header --}}
    <div class="bg-gradient-to-r from-red-900 via-red-800 to-rose-700 rounded-3xl px-8 py-6 shadow-xl relative overflow-hidden">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 300" fill="none">
                <circle cx="700" cy="30" r="180" fill="white"/>
                <circle cx="80" cy="280" r="130" fill="white"/>
            </svg>
        </div>
        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight">JOB MASTER</h1>
                    <p class="text-rose-200 text-xs font-semibold flex items-center gap-2 mt-0.5">
                        <span class="inline-block w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                        Database Master Pekerjaan &bull; {{ now()->translatedFormat('d F Y') }}
                    </p>
                </div>
            </div>
            <button onclick="openJobModal()"
                class="flex items-center gap-2 px-5 py-2.5 bg-white/15 hover:bg-white/25 text-white rounded-xl text-xs font-black transition-all ring-1 ring-white/20 backdrop-blur-sm shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
                TAMBAH JOB
            </button>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-rose-100 p-5 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 bg-gradient-to-br from-rose-500 to-red-700 rounded-xl flex items-center justify-center shadow-md shadow-rose-200/50 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-800">{{ $jobs->total() }}</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Job</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 bg-gradient-to-br from-slate-600 to-slate-800 rounded-xl flex items-center justify-center shadow-md flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-800">{{ $jobs->currentPage() }}</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Halaman</p>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm flex items-center gap-4">
            <div class="w-10 h-10 bg-gradient-to-br from-rose-400 to-red-600 rounded-xl flex items-center justify-center shadow-md shadow-rose-200/50 flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>
            </div>
            <div>
                <p class="text-2xl font-black text-slate-800">{{ $jobs->perPage() }}</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Per Halaman</p>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="bg-slate-900">
                        <th class="px-4 py-3.5 text-left text-[9px] font-black text-slate-400 uppercase tracking-widest border-r border-slate-700 w-10">#</th>
                        <th class="px-4 py-3.5 text-left text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700">Job Number</th>
                        <th class="px-4 py-3.5 text-left text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700">Job Name</th>
                        <th class="px-4 py-3.5 text-left text-[9px] font-black text-amber-400 uppercase tracking-widest border-r border-slate-700">Line</th>
                        <th class="px-4 py-3.5 text-center text-[9px] font-black text-sky-400 uppercase tracking-widest border-r border-slate-700">Capacity</th>
                        <th class="px-4 py-3.5 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest w-32">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobs as $i => $job)
                    <tr class="border-b border-slate-100 hover:bg-rose-50/30 transition-colors group">
                        <td class="px-4 py-3 text-center text-[11px] font-bold text-slate-400 border-r border-slate-100">{{ $jobs->firstItem() + $i }}</td>
                        <td class="px-4 py-3 border-r border-slate-100">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-rose-400 flex-shrink-0"></span>
                                <span class="text-sm font-black text-slate-800 tracking-tight">{{ $job->job_number }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3 border-r border-slate-100">
                            <span class="text-sm font-medium text-slate-600">{{ $job->job_name }}</span>
                        </td>
                        <td class="px-4 py-3 border-r border-slate-100">
                            <span class="inline-block px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 text-[10px] font-black uppercase tracking-wider border border-amber-100">{{ $job->line ?: '—' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center border-r border-slate-100">
                            <span class="inline-block px-3 py-1 rounded-full bg-sky-50 text-sky-700 text-xs font-black border border-sky-100">{{ number_format($job->capacity) }} <span class="font-medium opacity-70">pcs</span></span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button type="button"
                                    onclick="openEditJobModal({
                                        id: '{{ $job->id }}',
                                        number: '{{ addslashes($job->job_number) }}',
                                        name: '{{ addslashes($job->job_name) }}',
                                        line: '{{ addslashes($job->line) }}',
                                        capacity: '{{ $job->capacity }}'
                                    })"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 hover:text-rose-700 transition-all hover:scale-110 active:scale-95" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                <button type="button"
                                    data-id="{{ $job->id }}"
                                    data-name="{{ $job->job_name }}"
                                    data-number="{{ $job->job_number }}"
                                    onclick="openDeleteModal(this)"
                                    class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-100 text-slate-500 hover:bg-red-50 hover:text-red-600 transition-all hover:scale-110 active:scale-95" title="Hapus">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                                </div>
                                <p class="text-sm font-bold text-slate-400">Belum ada data job</p>
                                <p class="text-xs text-slate-300">Klik tombol "TAMBAH JOB" untuk mulai menambahkan data</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jobs->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
            {{ $jobs->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ===== MODAL ADD JOB ===== --}}
<div id="jobModal" class="fixed inset-0 z-[99999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeJobModal()"></div>
    <div class="relative w-full max-w-md mx-4">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-red-800 to-rose-700 px-8 py-6">
                <h2 class="text-lg font-black text-white tracking-tight">TAMBAH JOB</h2>
                <p class="text-rose-200 text-xs mt-0.5">Isi formulir untuk menambahkan job baru ke database</p>
            </div>
            <form action="{{ route('master.job.store') }}" method="POST" class="px-8 py-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Job Number</label>
                    <input type="text" name="job_number" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Job Name</label>
                    <input type="text" name="job_name" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Line</label>
                    <input type="text" name="line" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Capacity (pcs)</label>
                    <input type="number" name="capacity" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-transparent transition-all">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeJobModal()"
                        class="flex-1 px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">
                        BATAL
                    </button>
                    <button type="submit"
                        class="flex-1 px-5 py-3 bg-gradient-to-r from-red-800 to-rose-700 hover:from-red-900 hover:to-rose-800 text-white rounded-xl text-sm font-black shadow-lg shadow-rose-200 transition-all hover:-translate-y-0.5">
                        SIMPAN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL EDIT JOB ===== --}}
<div id="editJobModal" class="fixed inset-0 z-[99999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeEditJobModal()"></div>
    <div class="relative w-full max-w-md mx-4">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-slate-700 to-slate-900 px-8 py-6">
                <h2 class="text-lg font-black text-white tracking-tight">EDIT JOB</h2>
                <p class="text-slate-400 text-xs mt-0.5">Perbarui informasi job yang dipilih</p>
            </div>
            <form id="editForm" method="POST" class="px-8 py-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Job Number</label>
                    <input type="text" id="edit_job_number" name="job_number" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-slate-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Job Name</label>
                    <input type="text" id="edit_job_name" name="job_name" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-slate-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Line</label>
                    <input type="text" id="edit_line" name="line" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-slate-500 focus:border-transparent transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Capacity (pcs)</label>
                    <input type="number" id="edit_capacity" name="capacity" required
                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-slate-500 focus:border-transparent transition-all">
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeEditJobModal()"
                        class="flex-1 px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">
                        BATAL
                    </button>
                    <button type="submit"
                        class="flex-1 px-5 py-3 bg-gradient-to-r from-slate-700 to-slate-900 hover:from-slate-800 hover:to-black text-white rounded-xl text-sm font-black shadow-lg transition-all hover:-translate-y-0.5">
                        UPDATE
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== MODAL DELETE ===== --}}
<div id="deleteModal" class="fixed inset-0 z-[99999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeDeleteModal()"></div>
    <div class="relative w-full max-w-sm mx-4">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden text-center">
            <div class="px-8 pt-8 pb-6">
                <div class="w-16 h-16 bg-red-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <h2 class="text-lg font-black text-slate-800 mb-2">Hapus Job?</h2>
                <p class="text-xs text-red-500 font-bold mb-5">Tindakan ini tidak bisa dibatalkan.</p>
                <div class="bg-slate-50 rounded-2xl px-5 py-4 mb-6 text-left space-y-2 border border-slate-100">
                    <div class="flex gap-3 text-sm">
                        <span class="text-slate-400 font-bold w-28 shrink-0 text-[11px] uppercase tracking-wider">Job Number</span>
                        <span id="deleteJobNumber" class="font-black text-slate-800"></span>
                    </div>
                    <div class="flex gap-3 text-sm">
                        <span class="text-slate-400 font-bold w-28 shrink-0 text-[11px] uppercase tracking-wider">Job Name</span>
                        <span id="deleteJobName" class="font-bold text-slate-700"></span>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeDeleteModal()"
                        class="flex-1 px-5 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">
                        BATAL
                    </button>
                    <button type="button" onclick="confirmDeleteAction()"
                        class="flex-1 px-5 py-3 bg-gradient-to-r from-red-700 to-red-900 hover:from-red-800 hover:to-red-950 text-white rounded-xl text-sm font-black shadow-lg shadow-red-200 transition-all hover:-translate-y-0.5">
                        YA, HAPUS
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// ================= HELPERS =================
function showModal(id)  { const m = document.getElementById(id); m.classList.remove('hidden'); m.classList.add('flex'); }
function hideModal(id)  { const m = document.getElementById(id); m.classList.add('hidden'); m.classList.remove('flex'); }

// ================= ADD =================
function openJobModal()   { showModal('jobModal'); }
function closeJobModal()  { hideModal('jobModal'); }

// ================= EDIT =================
function openEditJobModal(data) {
    document.getElementById('edit_job_number').value = data.number;
    document.getElementById('edit_job_name').value   = data.name;
    document.getElementById('edit_line').value       = data.line;
    document.getElementById('edit_capacity').value   = data.capacity;
    document.getElementById('editForm').action       = '/master/job/update/' + data.id;
    showModal('editJobModal');
}
function closeEditJobModal() { hideModal('editJobModal'); }

// ================= DELETE =================
let deleteId = null;
function openDeleteModal(btn) {
    deleteId = btn.dataset.id;
    document.getElementById('deleteJobName').textContent   = btn.dataset.name;
    document.getElementById('deleteJobNumber').textContent = btn.dataset.number;
    showModal('deleteModal');
}
function closeDeleteModal()  { deleteId = null; hideModal('deleteModal'); }
function confirmDeleteAction() { if (deleteId) window.location.href = '/master/job/delete/' + deleteId; }
</script>
@endsection

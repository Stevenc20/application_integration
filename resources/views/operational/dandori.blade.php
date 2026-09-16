@extends('layouts.supervisor')

@section('title', 'Dandori Recording')
@section('header_title', 'Dandori Recording')

@section('content')

<div class="space-y-6">

    {{-- ======================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ======================================================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dandori Recording</h1>
            <p class="text-sm text-gray-500 mt-1">Production Line Changeover Tracker &amp; History</p>
        </div>
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-4 py-2.5 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-sm font-semibold text-gray-700">{{ now()->isoFormat('D MMMM YYYY') }}</span>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- PERFORMANCE STATS HEADER --}}
    {{-- ======================================================= --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Total Events --}}
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Dandori Hari Ini</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-2xl font-black text-slate-800">{{ $todayStats['total_events'] }}</h3>
                    <span class="text-xs font-bold text-slate-400 uppercase">Events</span>
                </div>
            </div>
        </div>

        {{-- Avg Duration --}}
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Rata-rata Durasi</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-2xl font-black text-slate-800">
                        @php
                            $avg = $todayStats['avg_duration'] ?? 0;
                            $m = floor($avg);
                            $s = round(($avg - $m) * 60);
                            if ($m > 0) echo $m . "m " . $s . "s";
                            else echo $s . "s";
                        @endphp
                    </h3>
                </div>
            </div>
        </div>

        {{-- Total Lost Time --}}
        <div class="bg-white rounded-3xl p-6 border border-slate-200 shadow-sm flex items-center gap-5">
            <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center shadow-inner">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Waktu Persiapan</p>
                <div class="flex items-baseline gap-2">
                    <h3 class="text-2xl font-black text-blue-600">
                        @php
                            $total = $todayStats['total_duration'] ?? 0;
                            $m = floor($total);
                            $s = round(($total - $m) * 60);
                            if ($m > 0) echo $m . "m " . $s . "s";
                            else echo $s . "s";
                        @endphp
                    </h3>
                </div>
            </div>
        </div>
    </div>


    {{-- ======================================================= --}}
    {{-- HISTORY CARD --}}
    {{-- ======================================================= --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- Card Header --}}
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-800 text-base">History Dandori</h2>
                        <p class="text-xs text-gray-500">Riwayat changeover model, setup, trial &amp; adjustment</p>
                    </div>
                </div>
                <button onclick="loadHistory()" class="flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-600 text-sm font-semibold transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- DATE --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Tanggal</label>
                    <input type="date" id="historyDate" value="{{ now()->format('Y-m-d') }}"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-white text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 transition outline-none">
                </div>

                {{-- LINE --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Line Produksi</label>
                    <select id="historyLine" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 bg-white text-sm focus:ring-2 focus:ring-blue-200 focus:border-blue-400 transition outline-none">
                        <option value="">Semua Line</option>
                        @foreach($lines as $rowLine)
                            <option value="{{ $rowLine }}">{{ $rowLine }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- BUTTONS --}}
                <div class="flex items-end gap-2">
                    <button onclick="loadHistory()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-2.5 font-semibold text-sm transition-colors shadow-sm">Filter</button>
                    <button onclick="resetFilter()" class="px-4 py-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-100 text-gray-600 text-sm font-medium transition-colors">Reset</button>
                </div>

            </div>
        </div>

        {{-- JENIS FILTER TABS --}}
        <div class="px-6 py-3 border-b border-gray-100 bg-white flex items-center gap-2">
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mr-2">Filter:</span>
            <input type="hidden" id="historyJenis" value="">
            <button onclick="setJenisFilter('')" class="jenis-filter-btn px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all border-2 border-blue-500 bg-blue-500 text-white" data-jenis="">Semua</button>
            <button onclick="setJenisFilter('dandori')" class="jenis-filter-btn px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all border-2 border-gray-200 bg-white text-gray-500 hover:border-blue-300" data-jenis="dandori">Dandori</button>
            <button onclick="setJenisFilter('1st_check')" class="jenis-filter-btn px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all border-2 border-gray-200 bg-white text-gray-500 hover:border-indigo-300" data-jenis="1st_check">1st Check</button>
        </div>

        {{-- HISTORY FEED --}}
        <div class="px-6 py-6">
            <div id="historyBody" class="space-y-4">
                {{-- Feed items via JS --}}
                <div class="flex flex-col items-center justify-center py-20 text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm font-bold uppercase tracking-widest">Memuat Riwayat...</p>
                </div>
            </div>
        </div>

        <div id="historyFooter" class="px-8 py-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
            {{-- Summary via JS --}}
        </div>

        {{-- HISTORY PAGINATION --}}
        <div id="historyPagination" class="px-6 py-4 border-t border-gray-100 flex justify-center gap-2"></div>

    </div>

</div>

{{-- ======================================================= --}}
{{-- DANDORI DETAIL MODAL --}}
{{-- ======================================================= --}}
<div id="dandoriModal" class="fixed inset-0 z-[9999] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeDandoriModal()"></div>
    
    <div class="relative bg-white w-full max-w-4xl max-h-[90vh] rounded-3xl shadow-2xl overflow-hidden flex flex-col transform transition-all scale-95 opacity-0 duration-300" id="modalContent">
        
        {{-- Modal Header --}}
        <div class="px-8 py-5 border-b border-gray-100 bg-gradient-to-r from-red-50 to-orange-50 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-primary-red text-white flex items-center justify-center shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-gray-800 text-lg" id="modalJobNumber">Dandori Detail</h2>
                    <p class="text-xs text-gray-500" id="modalJobName">Pencatatan aktivitas changeover</p>
                </div>
            </div>
            <button onclick="closeDandoriModal()" class="w-10 h-10 rounded-xl bg-white border border-gray-100 text-gray-400 flex items-center justify-center hover:text-gray-600 hover:border-gray-200 transition-all shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-8 overflow-y-auto">
            
            {{-- Job Info Summary --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Production Line</p>
                    <p class="font-bold text-gray-800 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <span id="modalLine">-</span>
                    </p>
                </div>
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100">
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Shift</p>
                    <p class="font-bold text-gray-800" id="modalShift">-</p>
                </div>
                <div class="bg-red-50 rounded-2xl p-4 border border-red-100">
                    <p class="text-[10px] font-bold text-red-400 uppercase tracking-widest mb-1">Total Durasi</p>
                    <p class="text-xl font-black text-primary-red"><span id="modalTotalDuration">0.00</span> <span class="text-[10px] font-bold text-red-300">MNT</span></p>
                </div>
            </div>

            {{-- Activity Table --}}
            <div class="rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left">
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest">Aktivitas</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Waktu</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-center">Durasi</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-gray-500 uppercase tracking-widest text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="modalActivityBody" class="divide-y divide-gray-50 bg-white">
                        {{-- Content via JS --}}
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

{{-- ======================================================= --}}
{{-- CONFIRMATION MODAL --}}
{{-- ======================================================= --}}
<div id="confirmModal" class="fixed inset-0 z-[10000] hidden items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-[2px]" onclick="closeConfirmModal()"></div>
    <div class="relative bg-white w-full max-w-sm rounded-3xl shadow-2xl p-8 text-center transform transition-all scale-95 opacity-0 duration-200" id="confirmContent">
        <div class="w-16 h-16 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center mx-auto mb-4 shadow-inner">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-800 mb-2" id="confirmTitle">Konfirmasi</h3>
        <p class="text-sm text-gray-500 mb-8" id="confirmText">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
        <div class="grid grid-cols-2 gap-3">
            <button onclick="closeConfirmModal()" class="px-5 py-3 rounded-xl border border-gray-200 text-gray-600 text-sm font-bold hover:bg-gray-50 transition-colors">Batal</button>
            <button id="confirmBtn" class="px-5 py-3 rounded-xl bg-primary-red text-white text-sm font-bold hover:bg-red-700 transition-all shadow-lg shadow-red-100">Ya, Lanjut</button>
        </div>
    </div>
</div>

{{-- TOAST --}}
<div id="toast" class="fixed top-5 right-5 z-[10001] hidden flex items-center gap-3 min-w-[280px] max-w-sm px-5 py-4 rounded-2xl shadow-2xl text-white text-sm font-medium border border-white/10 backdrop-blur-sm transform transition-all duration-300 translate-x-full">
    <div id="toastIcon" class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center bg-white/20"></div>
    <span id="toastMsg" class="flex-1"></span>
</div>

@endsection


@section('scripts')
<script>
let currentPage = 1;
let currentJobId = null;
let dandoriIntervals = {};
let confirmCallback = null;

document.addEventListener('DOMContentLoaded', function () {
    loadHistory();
    let autoJob = "{{ $jobId ?? '' }}";
    
    // Auto-load jobs if line and shift are pre-filled
    let line = document.getElementById('line').value;
    let shift = document.getElementById('shift').value;
    
    if (line && shift) {
        loadJobs(1);
    }

    if (autoJob !== '') {
        setTimeout(() => { 
            openJob(autoJob); 
        }, 1200);
    }
});

/* =========================================================
   PAGINATION LOAD JOBS
   ========================================================= */
function loadJobs(page = 1) {
    currentPage = page;
    let line = document.getElementById('line').value;
    let shift = document.getElementById('shift').value;
    let box = document.getElementById('jobList');
    let pagContainer = document.getElementById('pagination');

    if (line === '' || shift === '') {
        box.innerHTML = `<div class="flex items-center gap-3 border border-red-200 bg-red-50 text-red-600 rounded-xl p-4 text-sm"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>Pilih <strong>Line</strong> dan <strong>Shift</strong> terlebih dahulu.</div>`;
        pagContainer.innerHTML = '';
        return;
    }

    box.innerHTML = `<div class="flex items-center justify-center gap-2 py-8 text-gray-400 text-sm"><svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>Memuat item...</div>`;

    fetch(`{{ route('operational.dandori.loadJobs') }}?line=${line}&shift=${shift}&page=${page}`)
    .then(res => res.json())
    .then(data => {
        let html = '';
        if (!data.data || data.data.length === 0) {
            html = `<div class="flex flex-col items-center gap-2 py-10 text-gray-400 border border-dashed border-gray-200 rounded-xl bg-gray-50"><svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><p class="text-sm font-medium">Tidak ada item pending untuk line & shift ini</p></div>`;
            pagContainer.innerHTML = '';
        } else {
            data.data.forEach(row => {
                html += `
                <div class="group flex items-center justify-between border border-gray-200 rounded-xl px-5 py-3.5 bg-white hover:border-primary-red hover:shadow-md transition-all duration-200">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-red-50 text-primary-red flex items-center justify-center flex-shrink-0 group-hover:bg-primary-red group-hover:text-white transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        </div>
                        <div>
                            <p class="font-bold text-gray-800 text-sm">${row.job_number ?? '-'}</p>
                            <p class="text-gray-500 text-xs mt-0.5">${row.job_name ?? '-'}</p>
                        </div>
                    </div>
                    <button onclick="openJob('${row.id}')" class="flex items-center gap-2 px-4 py-2 rounded-lg bg-gray-50 border border-gray-200 text-gray-700 text-xs font-semibold hover:bg-primary-red hover:text-white hover:border-primary-red transition-all duration-200"><svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Buka</button>
                </div>`;
            });
            renderPagination(data);
        }
        box.innerHTML = html;
    });
}

function renderPagination(data) {
    let container = document.getElementById('pagination');
    let html = '';
    if (data.last_page > 1) {
        for (let i = 1; i <= data.last_page; i++) {
            let activeClass = i === data.current_page ? 'bg-primary-red text-white' : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200';
            html += `<button onclick="loadJobs(${i})" class="w-10 h-10 rounded-xl font-bold text-xs transition-all ${activeClass}">${i}</button>`;
        }
    }
    container.innerHTML = html;
}

/* =========================================================
   DANDORI MODAL LOGIC
   ========================================================= */
function openJob(id) {
    currentJobId = id;
    const modal = document.getElementById('dandoriModal');
    const content = document.getElementById('modalContent');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
    }, 10);

    fetchDandoriDetail();
}

function closeDandoriModal() {
    const modal = document.getElementById('dandoriModal');
    const content = document.getElementById('modalContent');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        clearAllTimers();
    }, 300);
}

function fetchDandoriDetail() {
    fetch(`{{ url('/operational/dandori/get-detail') }}/${currentJobId}`)
    .then(res => res.json())
    .then(data => {
        document.getElementById('modalJobNumber').innerText = `Job #${data.job.job_number}`;
        document.getElementById('modalJobName').innerText = data.job.job_name || 'N/A';
        document.getElementById('modalLine').innerText = data.job.line || '-';
        document.getElementById('modalShift').innerText = document.getElementById('shift').value || 'Shift 1';
        document.getElementById('modalTotalDuration').innerText = data.totalDuration;

        let body = document.getElementById('modalActivityBody');
        let html = '';
        
        clearAllTimers();

        data.dandoriStatus.forEach(item => {
            let statusHtml = '';
            let actionHtml = '';
            let timerHtml = '<span class="text-gray-300 font-mono">—:—:—</span>';
            let durationHtml = '<span class="text-gray-400 font-bold">0.00</span>';

            if (item.record) {
                let startStr = formatTime(item.record.start_time);
                let finishStr = item.record.finish_time ? formatTime(item.record.finish_time) : '—:—:—';
                
                timerHtml = `<div class="flex flex-col items-center"><span class="text-[10px] text-gray-400 uppercase font-bold">START: ${startStr}</span><span class="text-[10px] text-gray-400 uppercase font-bold">END: ${finishStr}</span></div>`;
                
                if (item.record.finish_time) {
                    durationHtml = `<span class="text-blue-600 font-bold">${parseFloat(item.record.duration_minutes).toFixed(2)}</span>`;
                    actionHtml = `<button onclick="confirmRestart(${item.record.id})" class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-colors" title="Restart"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></button>`;
                } else {
                    durationHtml = `<span class="text-emerald-600 font-black font-mono tracking-tighter" id="timer-${item.record.id}">00:00</span>`;
                    initRealtimeTimer(item.record.id, item.record.start_time);
                    actionHtml = `<button onclick="confirmStop(${item.record.id})" class="px-4 py-2 rounded-lg bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition-all shadow-lg shadow-red-100 flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>STOP</button>`;
                }
            } else {
                actionHtml = `<button onclick="startActivity('${item.type_code}')" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100 flex items-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>START</button>`;
            }

            html += `
            <tr class="hover:bg-gray-50/50 transition-colors">
                <td class="px-6 py-5 font-bold text-gray-800">${item.type_display}</td>
                <td class="px-6 py-5 text-center">${timerHtml}</td>
                <td class="px-6 py-5 text-center">${durationHtml}</td>
                <td class="px-6 py-5 text-right"><div class="flex justify-end gap-2">${actionHtml}</div></td>
            </tr>`;
        });
        body.innerHTML = html;
    });
}

/* =========================================================
   TIMER LOGIC
   ========================================================= */
function initRealtimeTimer(id, startTime) {
    let start = new Date(startTime).getTime();
    dandoriIntervals[id] = setInterval(() => {
        let now = new Date().getTime();
        let diff = Math.floor((now - start) / 1000);
        let m = Math.floor(diff / 60);
        let s = diff % 60;
        let timerEl = document.getElementById(`timer-${id}`);
        if (timerEl) {
            timerEl.innerText = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
        }
    }, 1000);
}

function clearAllTimers() {
    Object.values(dandoriIntervals).forEach(clearInterval);
    dandoriIntervals = {};
}

function formatTime(dateStr) {
    let d = new Date(dateStr);
    return `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}:${String(d.getSeconds()).padStart(2,'0')}`;
}

/* =========================================================
   ACTIONS
   ========================================================= */
function startActivity(type) {
    let shift = document.getElementById('shift').value;
    fetch(`{{ url('/operational/dandori/start') }}/${currentJobId}/${type}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({ shift: shift })
    }).then(() => {
        showToast('Aktivitas dimulai', 'success');
        fetchDandoriDetail();
        loadHistory();
    });
}

function confirmStop(id) {
    showConfirm('Selesaikan Aktivitas?', 'Apakah Anda yakin ingin menghentikan timer aktivitas ini?', () => {
        fetch(`{{ url('/operational/dandori/stop') }}/${id}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(() => {
            showToast('Aktivitas selesai', 'success');
            fetchDandoriDetail();
            loadHistory();
            closeConfirmModal();
        });
    });
}

function confirmRestart(id) {
    showConfirm('Restart Timer?', 'Data waktu sebelumnya akan dihapus. Lanjutkan?', () => {
        fetch(`{{ url('/operational/dandori/restart') }}/${id}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(() => {
            showToast('Timer di-reset', 'warning');
            fetchDandoriDetail();
            loadHistory();
            closeConfirmModal();
        });
    });
}

/* =========================================================
   MODAL UTILS
   ========================================================= */
function showConfirm(title, text, callback) {
    document.getElementById('confirmTitle').innerText = title;
    document.getElementById('confirmText').innerText = text;
    confirmCallback = callback;
    const modal = document.getElementById('confirmModal');
    const content = document.getElementById('confirmContent');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => { content.classList.remove('scale-95', 'opacity-0'); }, 10);
    document.getElementById('confirmBtn').onclick = confirmCallback;
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    const content = document.getElementById('confirmContent');
    content.classList.add('scale-95', 'opacity-0');
    setTimeout(() => { modal.classList.add('hidden'); modal.classList.remove('flex'); }, 200);
}

function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    const icon = document.getElementById('toastIcon');
    document.getElementById('toastMsg').innerText = msg;
    
    toast.className = 'fixed top-5 right-5 z-[10001] flex items-center gap-3 min-w-[280px] max-w-sm px-5 py-4 rounded-2xl shadow-2xl text-white text-sm font-medium border border-white/10 backdrop-blur-sm transform transition-all duration-300';
    
    if(type === 'success') {
        toast.classList.add('bg-emerald-500');
        icon.innerHTML = '<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
    } else if(type === 'warning') {
        toast.classList.add('bg-amber-500');
        icon.innerHTML = '<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
    }

    toast.classList.remove('translate-x-full');
    setTimeout(() => { toast.classList.add('translate-x-full'); }, 3000);
}

/* =========================================================
   HISTORY & OTHERS
   ========================================================= */
function setJenisFilter(jenis) {
    document.getElementById('historyJenis').value = jenis;
    document.querySelectorAll('.jenis-filter-btn').forEach(btn => {
        if (btn.dataset.jenis === jenis) {
            btn.className = 'jenis-filter-btn px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all border-2 ' +
                (jenis === '' ? 'border-blue-500 bg-blue-500 text-white' :
                 jenis === '1st_check' ? 'border-indigo-500 bg-indigo-500 text-white' :
                 'border-blue-500 bg-blue-500 text-white');
        } else {
            btn.className = 'jenis-filter-btn px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-wider transition-all border-2 border-gray-200 bg-white text-gray-500 hover:border-blue-300';
        }
    });
    loadHistory();
}

function resetFilter() {
    document.getElementById('historyDate').value = '{{ now()->format("Y-m-d") }}';
    document.getElementById('historyLine').value = '';
    setJenisFilter('');
}

function loadHistory(page = 1) {
    let date = document.getElementById('historyDate').value;
    let line = document.getElementById('historyLine').value;
    let jenis = document.getElementById('historyJenis').value;
    document.getElementById('historyBody').innerHTML = `<tr><td colspan="6" class="py-16 text-center"><div class="flex flex-col items-center gap-2 text-gray-400"><svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 animate-spin text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span class="text-sm">Memuat history...</span></div></td></tr>`;

    fetch(`{{ route('operational.dandori.history') }}?date=${date}&line=${line}&jenis=${jenis}&page=${page}`)
    .then(res => res.json())
    .then(data => {
        let body = '';
        let total = 0;
        
        if (!data.data || data.data.length === 0) {
            body = `<div class="flex flex-col items-center justify-center py-20 text-slate-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-xs font-black uppercase tracking-[0.2em]">Belum ada riwayat tercatat</p>
                    </div>`;
        } else {
            data.data.forEach((group, index) => {
                total += group.total_duration;
                let rowId = `history-group-${index}`;
                
                body += `
                <div class="bg-white border border-slate-200 rounded-[2rem] overflow-hidden shadow-sm hover:shadow-md transition-all group p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div class="flex items-center gap-5">
                            <div class="w-16 h-16 rounded-2xl bg-slate-900 text-white flex flex-col items-center justify-center shadow-lg flex-shrink-0">
                                <span class="text-[10px] font-black text-slate-400 uppercase leading-none mb-1">${group.line}</span>
                                <span class="text-lg font-black leading-none">${group.shift.replace('Shift ', 'S')}</span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 text-[9px] font-black uppercase tracking-wider">${group.job_number}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">${group.date}</span>
                                </div>
                                <h4 class="text-sm font-black text-slate-800 truncate pr-4 mb-2">${group.job_name}</h4>
                                
                                {{-- FLAT ACTIVITY LIST --}}
                                <div class="flex flex-wrap gap-2">
                                    ${group.activities.map(a => {
                                        let dotColor = a.jenis_dandori === '1st_check' ? 'bg-indigo-500' : 'bg-blue-500';
                                        let badgeClass = a.jenis_dandori === '1st_check' ? 'bg-indigo-50 border-indigo-200' : 'bg-slate-50 border-slate-100';
                                        return `
                                        <div class="flex items-center gap-2 px-3 py-1.5 ${badgeClass} border rounded-xl">
                                            <div class="w-1.5 h-1.5 rounded-full ${dotColor}"></div>
                                            <span class="text-[9px] font-black text-slate-700 uppercase tracking-tighter">${a.type}</span>
                                            <span class="text-[9px] font-bold text-slate-400 font-mono">${a.start} - ${a.finish}</span>
                                            ${a.duration ? `<span class="text-[9px] font-black text-slate-500 font-mono">(${formatDuration(a.duration)})</span>` : ''}
                                        </div>`;
                                    }).join('')}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center justify-end gap-8 pl-4 border-l border-slate-100">
                            <div class="text-right">
                                <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Durasi</p>
                                <p class="text-xl font-black text-blue-600 leading-none">${formatDuration(group.total_duration)}</p>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
        }
        document.getElementById('historyBody').innerHTML = body;
        document.getElementById('historyFooter').innerHTML = `
            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Summary Overview</span>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-500">Total Durasi Seluruhnya:</span>
                <span class="text-lg font-black text-primary-red">${formatDuration(total)}</span>
            </div>
        `;
        
        renderHistoryPagination(data);
    });
}

function toggleHistoryDetail(id) {
    let el = document.getElementById(id);
    let icon = document.getElementById(`icon-${id}`);
    if (el.classList.contains('hidden')) {
        el.classList.remove('hidden');
        icon.style.transform = 'rotate(90deg)';
    } else {
        el.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}

function renderHistoryPagination(data) {
    let container = document.getElementById('historyPagination');
    let html = '';
    if (data.last_page > 1) {
        for (let i = 1; i <= data.last_page; i++) {
            let activeClass = i === data.current_page ? 'bg-blue-600 text-white shadow-lg' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50';
            html += `<button onclick="loadHistory(${i})" class="w-8 h-8 rounded-lg font-bold text-xs transition-all ${activeClass}">${i}</button>`;
        }
    }
    container.innerHTML = html;
}

/* =========================================================
   UTILS
   ========================================================= */
function formatDuration(decimalMinutes) {
    let totalSeconds = Math.round(decimalMinutes * 60);
    let m = Math.floor(totalSeconds / 60);
    let s = totalSeconds % 60;
    
    if (m > 0) {
        return `${m}m ${s}s`;
    }
    return `${s}s`;
}

function openJob(id) {
    // Dengan modal pop-up
    currentJobId = id;
    const modal = document.getElementById('dandoriModal');
    const content = document.getElementById('modalContent');
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        content.classList.remove('scale-95', 'opacity-0');
    }, 10);

    fetchDandoriDetail();
}
</script>
@endsection

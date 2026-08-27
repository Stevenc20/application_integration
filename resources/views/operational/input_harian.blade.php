@extends('layouts.supervisor')
@section('title', 'Input Harian')
@section('header_title', 'Input Harian')



@section('content')
<style>
    .active-growing {
        position: relative;
        overflow: hidden;
    }
    
    .dandori-hazard {
        background-color: #f59e0b; 
    }
    /* ── Shift change toast ── */
    .shift-toast{
        position:fixed;top:76px;left:50%;transform:translateX(-50%);
        z-index:9999;display:flex;align-items:center;gap:10px;
        padding:10px 22px;border-radius:10px;
        background:#1e40af;color:#fff;font-size:14px;font-weight:900;
        letter-spacing:0.05em;box-shadow:0 10px 30px rgba(0,0,0,0.25);
        opacity:0;pointer-events:none;
        transition:opacity .25s ease, transform .25s ease;
    }
    .shift-toast.show{opacity:1;transform:translateX(-50%)}
    .shift-toast svg{width:20px;height:20px;flex-shrink:0;animation:pulse-dot 1.2s ease-in-out infinite}
    .shift-toast.night{background:#6d28d9}
    @keyframes pulse-dot{0%,100%{opacity:1}50%{opacity:0.3}}
</style>
<div class="space-y-6">

    @php
        $now = now();
        $isShiftPagi = $now->format('H:i') >= '07:30' && $now->format('H:i') < '21:00';
        $shiftLabel = $isShiftPagi ? 'Shift Pagi (Shift 1)' : 'Shift Malam (Shift 2)';
        $shiftColor = $isShiftPagi ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-indigo-50 text-indigo-800 border-indigo-200';
    @endphp

    {{-- SHIFT NOTIFICATION BANNER --}}
    <div class="p-4 border rounded-xl flex items-center gap-3 shadow-sm {{ $shiftColor }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <div>
            <p class="font-bold text-sm tracking-wide">STATUS SHIFT SISTEM: {{ strtoupper($shiftLabel) }}</p>
            <p class="text-xs mt-0.5">Semua data produksi yang Anda masukkan saat ini ({{ $now->format('H:i') }}) secara otomatis dicatat ke dalam buku <strong>{{ $shiftLabel }}</strong>.</p>
        </div>
    </div>


    {{-- PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Input Harian</h1>
            <p class="text-sm text-gray-500 mt-1">Pencatatan produksi harian per item &amp; line</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('supervisor.reports.daily_production', ['line' => request('line', 'Line A'), 'shift' => request('shift', 'Shift Pagi'), 'date' => request('date', $date)]) }}" 
               class="flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-sm px-5 py-2.5 rounded-xl shadow-md shadow-emerald-500/10 transition-all border border-emerald-600/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a1 1 0 01-2 2z"/>
                </svg>
                <span>Lihat Laporan LKH</span>
            </a>
            
            <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-4 py-2.5 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm font-semibold text-gray-700">{{ now()->format('d F Y') }}</span>
            </div>

            {{-- PULL AHEAD BUTTON --}}
            <button onclick="openPullAheadModal()" class="flex items-center gap-2 bg-blue-50 text-blue-700 hover:bg-blue-100 font-bold text-sm px-5 py-2.5 rounded-xl shadow-sm border border-blue-200 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
                <span>Tarik Shift Berikutnya</span>
            </button>

            {{-- Akhiri Shift Button --}}
            @if(!isset($isHistorical) || !$isHistorical)
                @if($isLocked ?? false)
                <button id="submitShiftBtnTop" disabled
                    class="flex items-center gap-2 bg-emerald-500/50 text-white/50 font-bold text-sm px-5 py-2.5 rounded-xl shadow-sm border border-emerald-600/20 cursor-not-allowed">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Shift Terkunci</span>
                </button>
                @else
                <button id="submitShiftBtnTop" onclick="submitShift()" 
                    class="flex items-center gap-2 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 shadow-md shadow-orange-500/10 text-white font-bold text-sm px-5 py-2.5 rounded-xl transition-all border border-orange-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Akhiri Shift</span>
                </button>
                @endif
            @endif
        </div>

    </div>

        {{-- 
        BAGIAN FILTER DATA
        Berfungsi untuk membatasi tampilan Job agar operator fokus pada Line-nya masing-masing.
    --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-primary-red text-white flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
            </div>
            <h2 class="font-semibold text-gray-800">Filter</h2>
        </div>
        <div class="p-5">
            <form method="GET" action="{{ route('operational.input_harian') }}"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                
                {{-- Preserve Shift and Line filters --}}
                <input type="hidden" name="shift" value="{{ request('shift') }}">
                <input type="hidden" name="line" value="{{ request('line') }}">

                <div>
                    <label for="filterStatusSelect" class="block text-xs font-semibold text-gray-600 uppercase mb-1.5 ml-1">Status</label>
                    <select id="filterStatusSelect" name="status" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 focus:border-primary-red outline-none transition bg-white cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ strtolower(request('status'))=='pending'?'selected':'' }}>Pending</option>
                        <option value="running" {{ strtolower(request('status'))=='running'?'selected':'' }}>Running</option>
                        <option value="complete" {{ strtolower(request('status'))=='complete'?'selected':'' }}>Complete</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="filterSearchInput" class="block text-xs font-semibold text-gray-600 uppercase mb-1.5 ml-1">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input id="filterSearchInput" type="text" name="search" value="{{ request('search') }}" placeholder="Cari Job # atau Nama Item..."
                            class="w-full border border-gray-300 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 focus:border-primary-red outline-none transition bg-white">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" aria-label="Jalankan Filter Data" class="flex-1 px-4 py-2.5 rounded-xl bg-primary-red hover:bg-red-700 text-white font-bold text-sm transition-all shadow-md">Filter</button>
                    <a href="{{ route('operational.input_harian') }}" aria-label="Reset Filter Data" class="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-50 font-bold text-sm transition-all flex items-center justify-center">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Tampilan Utama --}}
    
    <div class="bg-white rounded-2xl border border-rose-100 p-2 shadow-sm flex items-center gap-2 mb-6 w-fit mx-auto lg:mx-0">
        <a href="{{ route('operational.input_harian', array_merge(request()->query(), ['shift' => 'Shift Pagi'])) }}" 
           class="px-6 py-2.5 rounded-xl text-xs font-black transition-all {{ str_contains(strtoupper($currentShift), 'PAGI') ? 'bg-primary-red text-white shadow-lg shadow-red-200' : 'bg-white text-slate-400 hover:bg-rose-50 hover:text-rose-600' }}">
            SHIFT PAGI
        </a>
        <a href="{{ route('operational.input_harian', array_merge(request()->query(), ['shift' => 'Shift Malam'])) }}" 
           class="px-6 py-2.5 rounded-xl text-xs font-black transition-all {{ str_contains(strtoupper($currentShift), 'MALAM') ? 'bg-primary-red text-white shadow-lg shadow-red-200' : 'bg-white text-slate-400 hover:bg-rose-50 hover:text-rose-600' }}">
            SHIFT MALAM
        </a>
    </div>

    @if($isLocked ?? false)
    <div class="bg-red-900/20 border-2 border-red-500/30 rounded-2xl p-4 flex items-center gap-3 shadow-lg">
        <div class="w-10 h-10 rounded-xl bg-red-500/20 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </div>
        <div>
            <span class="text-red-400 font-black text-sm uppercase tracking-widest">Shift Terkunci</span>
            <p class="text-red-300/80 text-xs font-bold mt-0.5">Shift sudah disubmit. Semua data dalam mode read-only — tidak dapat diubah.</p>
        </div>
    </div>
    @endif

    @if(isset($isHistorical) && $isHistorical)
        {{-- HISTORICAL MODE: summary card read-only --}}
        <div class="bg-slate-900 rounded-3xl p-6 shadow-2xl border-2 border-slate-800 text-white relative overflow-hidden">
            <div class="absolute top-0 left-0 w-1/2 h-0.5 bg-gradient-to-r from-amber-500 via-red-500 to-transparent"></div>
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/20 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-black text-amber-400 uppercase tracking-widest">Historical Data Replay</h2>
                    <p class="text-xs text-slate-400 font-bold mt-1">Menampilkan data produksi tanggal <span class="text-white">{{ \Carbon\Carbon::parse($date)->format('d F Y') }}</span> — mode read-only</p>
                </div>
            </div>
        </div>
    @else
        @php
            $unfilledDowntimes = collect();
            if (isset($activeJob) && $activeJob) {
                $unfilledDowntimes = \App\Models\Downtime::where('job_master_id', $activeJob->id)
                    ->whereNotNull('finish_time')
                    ->where('jenis_downtime', '!=', 'dandori')
                    ->where(function($q) {
                        $q->whereNull('problem')->orWhere('problem', '')->orWhere('problem', '-')
                          ->orWhereNull('penyebab')->orWhere('penyebab', '')->orWhere('penyebab', '-');
                    })
                    ->get();
            }
        @endphp

        @if($unfilledDowntimes->isNotEmpty())
        <div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-4 mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm animate-pulse">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 text-amber-600 flex items-center justify-center font-black text-lg shrink-0">!</div>
                <div>
                    <h4 class="text-sm font-black text-amber-800 uppercase tracking-wider">Reminder Laporan Downtime</h4>
                    <p class="text-xs font-bold text-amber-700 mt-0.5">Terdapat {{ $unfilledDowntimes->count() }} kejadian Downtime yang belum diisi detailnya (Problem/Penyebab/Action). Mohon periksa &amp; lengkapi laporan.</p>
                </div>
            </div>
            <button onclick="openDowntimeReport({{ $activeJob->id }}, {{ e(json_encode($unfilledDowntimes->first())) }})" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-black text-xs rounded-xl shadow transition whitespace-nowrap">Isi Detail Downtime Sekarang</button>
        </div>
        @endif

        @include('operational.components.active-job-board')
    @endif

    {{-- SHIFT SUBMISSION BANNER --}}
    @if(!isset($isHistorical) || !$isHistorical)
    @if(!($isLocked ?? false))
    <div id="shiftSubmissionBanner" class="mb-6">
        <div class="bg-white rounded-2xl border border-orange-200 bg-orange-50/50 shadow-sm overflow-hidden">
            <div class="px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black text-orange-800 uppercase tracking-widest">Akhiri Shift</h3>
                        <p class="text-[10px] font-bold text-orange-600 mt-0.5">
                            @php
                                $completedCount = $jobPlans->filter(fn($p) => optional($p->job_data)->status === 'complete')->count();
                                $totalJobs = $jobPlans->count();
                            @endphp
                            {{ $completedCount }}/{{ $totalJobs }} item selesai. Item yang belum mencapai target akan otomatis masuk recovery. Lengkapi downtime terlebih dahulu bila ada.
                        </p>
                    </div>
                </div>
                <button id="submitShiftBtn" onclick="submitShift()" 
                    class="flex items-center gap-2 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 shadow-md shadow-orange-500/10 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all border border-orange-600/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Akhiri Shift</span>
                </button>
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- MAIN TABLE CARD --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-8">
        <div class="px-6 py-5 border-b border-gray-100">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-primary-red rounded-full"></div>
                        Antrian Produksi
                    </h2>
                    <p class="text-[10px] text-slate-500 mt-0.5 font-bold uppercase tracking-tighter">
                        {!! $scheduleContext !!} &bull; {{ is_object($jobs) && method_exists($jobs, 'total') ? $jobs->total() : $jobs->count() }} Item terdaftar
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @php
                        $userLine = '';
                        if(auth()->check()) {
                            $userRole = strtolower(auth()->user()->role);
                            if ($userRole === 'leader a') {
                                $userLine = 'Line A';
                            } elseif ($userRole === 'leader b') {
                                $userLine = 'Line B';
                            } elseif ($userRole === 'leader c') {
                                $userLine = 'Line C';
                            } elseif ($userRole === 'leader d') {
                                $userLine = 'Line D';
                            } elseif ($userRole === 'shearing') {
                                $userLine = 'Shearing';
                            } elseif ($userRole === 'handwork') {
                                $userLine = 'Handwork';
                            } else {
                                foreach($lines as $l) {
                                    if(str_contains(strtoupper(auth()->user()->name), strtoupper($l)) || str_contains(strtoupper(auth()->user()->name), strtoupper(str_replace('Line ', '', $l)))) {
                                        $userLine = $l;
                                        break;
                                    }
                                }
                            }
                        }
                        $sortedLines = $lines->sortBy(function($l) use ($userLine) {
                            return $l === $userLine ? 0 : 1;
                        });
                    @endphp

                    @foreach($sortedLines as $line)
                        @php $cleanLine = str_replace('Line ', '', $line); @endphp
                        <a href="{{ route('operational.input_harian', array_merge(request()->query(), ['line' => $line])) }}" 
                           class="px-4 py-2 rounded-xl border-2 {{ request('line') == $line ? 'bg-primary-red border-primary-red text-white shadow-lg shadow-red-200' : 'bg-white border-slate-100 text-slate-500 hover:border-red-200 hover:text-red-600' }} transition-all flex items-center justify-center min-w-[60px] relative overflow-hidden group">
                            @if($line === $userLine)
                                <div class="absolute top-0 right-0 w-2 h-2 bg-yellow-400 rounded-bl-lg shadow-sm"></div>
                            @endif
                            <span class="text-xs font-black group-hover:scale-110 transition-transform">{{ $cleanLine }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <input type="hidden" name="line" value="{{ request('line') }}">
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1300px] w-full border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-6 py-4 text-left text-[10px] font-black text-slate-400 uppercase tracking-widest">ITEM SPECIFICATION</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">QUANTITIES</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">STATUS & ACTION</th>
                        <th class="px-6 py-4 text-center text-[10px] font-black text-slate-400 uppercase tracking-widest">PRODUCTION TIMELINE (TARGET vs ACTUAL)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($jobs as $job)
                        @include('operational.components.job-row')
                    @empty
                    <tr>
                        <td colspan="4" class="py-24 text-center bg-slate-50/50">
                            <div class="flex flex-col items-center gap-2">
                                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">No production queue available</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@push('modals')
    @include('operational.components.modals')
@endpush

@endsection

@section('scripts')
<script>
/**
 * DYNAMIC CONFIGURATION FOR PRODUCTION ENGINE
 */
window.ProductionConfig = {
    csrfToken: '{{ csrf_token() }}',
    currentLine: '{{ request('line') }}',
    currentDate: '{{ $date }}',
    currentShift: '{{ request('shift', $currentShift) }}',
    isLocked: {{ ($isLocked ?? false) ? 'true' : 'false' }},
    currentActiveId: {{ $activeJob->id ?? 'null' }},
    currentStatus: '{{ $activeJob->status ?? "none" }}',
    currentIsDandori: {{ isset($activeJob) && $activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->count() > 0 ? 'true' : 'false' }},
    currentDowntimeCount: {{ isset($activeJob) ? $activeJob->downtimes->whereNull('finish_time')->count() : 0 }},
    totalDowntimeCount: {{ isset($activeJob) ? $activeJob->downtimes->count() : 0 }},
    currentDowntimeType: {!! isset($activeJob) && $activeJob->downtimes->whereNull('finish_time')->first() ? json_encode($activeJob->downtimes->whereNull('finish_time')->first()->jenis_downtime) : "null" !!},
    userName: '{{ auth()->user()->name }}',
    lastInputAt: {!! $lastInputAt ? json_encode(\Carbon\Carbon::parse($lastInputAt)->toIso8601String()) : "null" !!}
};

// Global data structures
window.runningDowntimes = {
    @foreach($jobs as $job)
        @php $jd = $job->job_data; @endphp
        @if($jd)
            @foreach($jd->downtimes->whereNull('finish_time') as $rdt)
                @php 
                    $dtTypeLower = strtolower($rdt->jenis_downtime);
                    $btnType = 'downtime';
                    if($dtTypeLower == 'try out') $btnType = 'tryout';
                    elseif($dtTypeLower == 'downtime') $btnType = 'downtime';
                    elseif(in_array($dtTypeLower, ['break time', 'manual break'])) $btnType = 'break';
                    elseif($dtTypeLower == 'dandori') $btnType = 'dandori';
                @endphp
                "{{ $jd->id }}_{{ $btnType }}": { 
                    id: {{ $rdt->id }}, 
                    start: new Date("{{ \Carbon\Carbon::parse($rdt->start_time)->toIso8601String() }}"), 
                    jobId: {{ $jd->id }}, 
                    btnType: "{{ $btnType }}",
                    dtType: "{{ $rdt->jenis_downtime }}",
                    source: "{{ $rdt->source }}",
                    problem: {!! json_encode($rdt->problem ?? '') !!},
                    pic: {!! json_encode($rdt->pic ?? '') !!}
                },
            @endforeach

            @foreach($jd->dandoris->whereNull('finish_time')->filter(fn($d) => ($d->jenis_dandori ?? '') === '1st_check') as $fc)
                "{{ $jd->id }}_firstcheck": { 
                    id: "fc_{{ $fc->id }}", 
                    start: new Date("{{ \Carbon\Carbon::parse($fc->start_time)->toIso8601String() }}"), 
                    jobId: {{ $jd->id }}, 
                    btnType: "firstcheck",
                    dtType: "1st_check"
                },
            @endforeach
        @endif
    @endforeach
    
    {{-- FORCE INCLUDE ACTIVE JOB RUNNING DOWNTIMES IF NOT IN PAGINATED LIST --}}
    @if(isset($activeJob) && !(is_object($jobs) && method_exists($jobs, 'getCollection') ? $jobs->getCollection() : $jobs)->pluck('job_data.id')->contains($activeJob->id))
        @foreach($activeJob->downtimes->whereNull('finish_time') as $rdt)
            @php 
                $dtTypeLower = strtolower($rdt->jenis_downtime);
                $btnType = 'downtime';
                if($dtTypeLower == 'try out') $btnType = 'tryout';
                elseif($dtTypeLower == 'downtime') $btnType = 'downtime';
                elseif(in_array($dtTypeLower, ['break time', 'manual break'])) $btnType = 'break';
                elseif($dtTypeLower == 'dandori') $btnType = 'dandori';
            @endphp
            "{{ $activeJob->id }}_{{ $btnType }}": { 
                id: {{ $rdt->id }}, 
                start: new Date("{{ \Carbon\Carbon::parse($rdt->start_time)->toIso8601String() }}"), 
                jobId: {{ $activeJob->id }}, 
                btnType: "{{ $btnType }}",
                dtType: "{{ $rdt->jenis_downtime }}",
                    source: "{{ $rdt->source }}",
                problem: {!! json_encode($rdt->problem ?? '') !!}
            },
            @endforeach

            @foreach($activeJob->dandoris->whereNull('finish_time')->filter(fn($d) => ($d->jenis_dandori ?? '') === '1st_check') as $fc)
                "{{ $activeJob->id }}_firstcheck": { 
                    id: "fc_{{ $fc->id }}", 
                    start: new Date("{{ \Carbon\Carbon::parse($fc->start_time)->toIso8601String() }}"), 
                    jobId: {{ $activeJob->id }}, 
                    btnType: "firstcheck",
                    dtType: "1st_check"
                },
            @endforeach
    @endif
};

window.jobMasterData = {
    @foreach($jobs as $job)
    @php $jd = $job->job_data; @endphp
    @if($jd)
    "{{ $jd->id }}": {
        id: {{ $jd->id }},
        status: "{{ $jd->status }}",
        @php $activeDate = request('date', now()->toDateString()); @endphp
        plan_start: {{ $job->start_time ? \Carbon\Carbon::parse($activeDate . ' ' . $job->start_time)->timestamp * 1000 : (\Carbon\Carbon::parse($jd->plan_start ?: $activeDate . ' 07:40')->timestamp * 1000) }},
        plan_end: {{ $job->finish_time ? \Carbon\Carbon::parse($activeDate . ' ' . $job->finish_time)->timestamp * 1000 : (\Carbon\Carbon::parse($jd->plan_end ?: $activeDate . ' 10:40')->timestamp * 1000) }},
        started_at: {{ 
            (isset($sessionMap) && $sessionMap->has($jd->id) && $sessionMap->get($jd->id)?->start_time) 
                ? \Carbon\Carbon::parse($sessionMap->get($jd->id)->start_time)->timestamp * 1000 
                : ($jd->started_at ? \Carbon\Carbon::parse($jd->started_at)->timestamp * 1000 
                    : ($job->act_start ? \Carbon\Carbon::parse($activeDate . ' ' . $job->act_start)->timestamp * 1000 : 'null'))
        }},
        act_start_ms: {{ $job->act_start ? \Carbon\Carbon::parse($activeDate . ' ' . $job->act_start)->timestamp * 1000 : 'null' }},
        finished_at: {{ 
            (isset($sessionMap) && $sessionMap->has($jd->id) && $sessionMap->get($jd->id)?->finish_time) 
                ? \Carbon\Carbon::parse($sessionMap->get($jd->id)->finish_time)->timestamp * 1000 
                : ($jd->finished_at ? \Carbon\Carbon::parse($jd->finished_at)->timestamp * 1000 : 'null') 
        }},
        base_seconds: {{ $jd->dailyProduction ? (int)$jd->dailyProduction->runtime_seconds : 0 }},
        target_qty: {{ $job->plan ?? 0 }},
        actual_ok: {{ $jd->dailyProduction?->actual_ok ?? 0 }},
        actual_repair: {{ $jd->dailyProduction?->actual_repair ?? 0 }},
        actual_reject: {{ $jd->dailyProduction?->actual_reject ?? 0 }},
        dandori_start: {{ $jd->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->first() ? \Carbon\Carbon::parse($jd->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->first()->start_time)->timestamp * 1000 : 'null' }},
        first_dandori_start: {{ $jd->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->sortBy('start_time')->first() ? \Carbon\Carbon::parse($jd->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->sortBy('start_time')->first()->start_time)->timestamp * 1000 : 'null' }},
        tpt: {{ (float)($job->tpt ?? 0) }},
        line: "{{ $job->press_name ?? '' }}",
        row_no: {{ $job->row_no ?? 0 }}
    },
    @endif
    @endforeach

    {{-- FORCE INCLUDE ACTIVE JOB IF NOT IN PAGINATED LIST --}}
    @if(isset($activeJob) && !(is_object($jobs) && method_exists($jobs, 'getCollection') ? $jobs->getCollection() : $jobs)->pluck('job_data.id')->contains($activeJob->id))
    "{{ $activeJob->id }}": {
        id: {{ $activeJob->id }},
        status: "{{ $activeJob->status }}",
        @php $activeDate = request('date', now()->toDateString()); @endphp
        @php $activeProdPlan = $activeJob->productionPlans->first(); @endphp
        plan_start: {{ $activeProdPlan?->start_time ? \Carbon\Carbon::parse($activeDate . ' ' . $activeProdPlan->start_time)->timestamp * 1000 : (\Carbon\Carbon::parse($activeJob->plan_start ?: $activeDate . ' 07:40')->timestamp * 1000) }},
        plan_end: {{ $activeProdPlan?->finish_time ? \Carbon\Carbon::parse($activeDate . ' ' . $activeProdPlan->finish_time)->timestamp * 1000 : (\Carbon\Carbon::parse($activeJob->plan_end ?: $activeDate . ' 10:40')->timestamp * 1000) }},
        started_at: {{ 
            (isset($sessionMap) && $sessionMap->has($activeJob->id) && $sessionMap->get($activeJob->id)?->start_time) 
                ? \Carbon\Carbon::parse($sessionMap->get($activeJob->id)->start_time)->timestamp * 1000 
                : ($activeJob->started_at ? \Carbon\Carbon::parse($activeJob->started_at)->timestamp * 1000 
                    : ($activeProdPlan && $activeProdPlan->act_start ? \Carbon\Carbon::parse($activeDate . ' ' . $activeProdPlan->act_start)->timestamp * 1000 : 'null'))
        }},
        act_start_ms: {{ $activeProdPlan && $activeProdPlan->act_start ? \Carbon\Carbon::parse($activeDate . ' ' . $activeProdPlan->act_start)->timestamp * 1000 : 'null' }},
        finished_at: {{ 
            (isset($sessionMap) && $sessionMap->has($activeJob->id) && $sessionMap->get($activeJob->id)?->finish_time) 
                ? \Carbon\Carbon::parse($sessionMap->get($activeJob->id)->finish_time)->timestamp * 1000 
                : ($activeJob->finished_at ? \Carbon\Carbon::parse($activeJob->finished_at)->timestamp * 1000 : 'null') 
        }},
        base_seconds: {{ $activeJob->dailyProduction ? (int)$activeJob->dailyProduction->runtime_seconds : 0 }},
        target_qty: {{ $activeJob->target_qty ?? 0 }},
        actual_ok: {{ $activeJob->dailyProduction?->actual_ok ?? 0 }},
        actual_repair: {{ $activeJob->dailyProduction?->actual_repair ?? 0 }},
        actual_reject: {{ $activeJob->dailyProduction?->actual_reject ?? 0 }},
        dandori_start: {{ $activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->first() ? \Carbon\Carbon::parse($activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->first()->start_time)->timestamp * 1000 : 'null' }},
        first_dandori_start: {{ $activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->sortBy('start_time')->first() ? \Carbon\Carbon::parse($activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->sortBy('start_time')->first()->start_time)->timestamp * 1000 : 'null' }},
        tpt: {{ (float)($activeJob->productionPlans->first()?->tpt ?? 0) }},
        line: "{{ $activeJob->line ?? '' }}",
        row_no: {{ $activeProdPlan?->row_no ?? 0 }}
    },
    @endif
};

window.jobDowntimeHistory = {
    @foreach($jobs as $job)
    @php $jd = $job->job_data; @endphp
    @if($jd)
    "{{ $jd->id }}": {!! json_encode(array_merge(
        $jd->downtimes->map(function($dt){ 
            return [
                'id' => $dt->id,
                'start' => \Carbon\Carbon::parse($dt->start_time)->timestamp * 1000,
                'end' => $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time)->timestamp * 1000 : null,
                'type' => $dt->jenis_downtime,
                'source' => $dt->source,
                'problem' => $dt->problem,
                'pic' => $dt->pic
            ];
        })->toArray(),
        $jd->dandoris->filter(fn($d) => $d->finish_time)->map(function($d){
            return [
                'id' => 'fc_'.$d->id,
                'start' => \Carbon\Carbon::parse($d->start_time)->timestamp * 1000,
                'end' => \Carbon\Carbon::parse($d->finish_time)->timestamp * 1000,
                'type' => (strtolower($d->jenis_dandori ?? '') === '1st_check' || strtolower($d->activity ?? '') === '1st check') ? '1st_check' : 'dandori',
                'problem' => null
            ];
        })->toArray()
    )) !!},
    @endif
    @endforeach

    @if(isset($activeJob) && !(is_object($jobs) && method_exists($jobs, 'getCollection') ? $jobs->getCollection() : $jobs)->pluck('job_data.id')->contains($activeJob->id))
    "{{ $activeJob->id }}": {!! json_encode(array_merge(
        $activeJob->downtimes->map(function($dt){ 
            return [
                'id' => $dt->id,
                'start' => \Carbon\Carbon::parse($dt->start_time)->timestamp * 1000,
                'end' => $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time)->timestamp * 1000 : null,
                'type' => $dt->jenis_downtime,
                'source' => $dt->source,
                'problem' => $dt->problem,
                'pic' => $dt->pic
            ];
        })->toArray(),
        $activeJob->dandoris->filter(fn($d) => $d->finish_time)->map(function($d){
            return [
                'id' => 'fc_'.$d->id,
                'start' => \Carbon\Carbon::parse($d->start_time)->timestamp * 1000,
                'end' => \Carbon\Carbon::parse($d->finish_time)->timestamp * 1000,
                'type' => (strtolower($d->jenis_dandori ?? '') === '1st_check' || strtolower($d->activity ?? '') === '1st check') ? '1st_check' : 'dandori',
                'problem' => null
            ];
        })->toArray()
    )) !!},
    @endif
};

// ——— AUTO BREAK TIME SCHEDULE ———
@php
    $bDayIndo = \App\Models\MasterBreakTime::getIndonesianDayName(now());
    $bDayEn = strtolower(now()->format('l'));
    $bShift = ((int) now()->format('H') >= 7 && (int) now()->format('H') < 19) ? 'Shift Pagi' : 'Shift Malam';
    $breakScheduleData = \App\Models\MasterBreakTime::where('is_active', true)
        ->where(function($q) use ($bDayIndo, $bDayEn) { $q->whereIn('hari', [$bDayIndo, $bDayEn])->orWhere('hari', 'semua'); })
        ->where(function($q) use ($bShift) { $q->where('shift', $bShift)->orWhereNull('shift'); })
        ->orderBy('sort_order')
        ->get()
        ->map(fn($b) => [
            'label' => $b->label,
            'type' => $b->type,
            'start' => substr($b->waktu_mulai, 0, 5),
            'end' => substr($b->waktu_selesai, 0, 5),
            'startMin' => \App\Models\MasterBreakTime::timeToMinutes(substr($b->waktu_mulai, 0, 5)),
            'endMin' => \App\Models\MasterBreakTime::timeToMinutes(substr($b->waktu_selesai, 0, 5)),
        ]);
@endphp
window._breakSchedule = {!! $breakScheduleData->toJson() !!};

// Auto-inject shift/date/line headers into all operational API requests
(function() {
    const _origFetch = window.fetch;
    window.fetch = function(url, opts) {
        const cfg = window.ProductionConfig || {};
        if (cfg.currentShift && typeof url === 'string' && url.includes('/operational/')) {
            opts = opts || {};
            opts.headers = new Headers(opts.headers || {});
            if (!opts.headers.has('X-Shift')) opts.headers.set('X-Shift', cfg.currentShift);
            if (!opts.headers.has('X-Date')) opts.headers.set('X-Date', cfg.currentDate);
            if (!opts.headers.has('X-Line')) opts.headers.set('X-Line', cfg.currentLine || '');
        }
        return _origFetch.call(window, url, opts);
    };
})();

function submitShift() {
    const activeJobId = window.ProductionConfig?.currentActiveId;
    if (activeJobId) {
        const activeJob = window.jobMasterData?.[activeJobId];
        const hasDowntime = (window.jobDowntimeHistory?.[activeJobId]?.length > 0) || (Object.keys(window.runningDowntimes || {}).length > 0);
        if (activeJob && activeJob.status === 'running' && !hasDowntime) {
            showToast('Item yang sedang berjalan harus mengisi downtime terlebih dahulu sebelum shift bisa difinalisasi.', 'error');
            return;
        }
    }
    showConfirm('Akhiri Shift?', 'Semua data akan difinalisasi.', function () {
        closeConfirmModal();
        const btn = document.getElementById('submitShiftBtn') || document.getElementById('submitShiftBtnTop');
        btn.disabled = true;
        btn.innerHTML = '<svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Memvalidasi...';

        const params = new URLSearchParams(window.location.search);
        fetch('{{ route('operational.shift.submit', ['lineId' => '__LINE__']) }}'.replace('__LINE__', params.get('line') || 'Line A'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                date: params.get('date') || '{{ $date }}',
                shift: params.get('shift') || 'Shift Pagi',
            }),
        })
        .then(r => r.json().then(data => ({ status: r.status, data })))
        .then(({ status, data }) => {
            if (status === 200 && data.success) {
                const recovered = parseInt(data.recovered || 0, 10);
                showToast(recovered > 0 ? 'Shift berhasil disubmit! ' + recovered + ' item tidak tercapai masuk recovery.' : 'Shift berhasil disubmit!', 'success');
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Shift Disubmit';
                btn.classList.remove('from-orange-500', 'to-red-600', 'hover:from-orange-600', 'hover:to-red-700');
                btn.classList.add('from-emerald-500', 'to-teal-600', 'cursor-default');
                btn.onclick = null;
                setTimeout(function () { window.location.reload(); }, 1500);
            } else if (data.has_issues) {
                openShiftValidationModal(data.issues);
                btn.disabled = false;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Akhiri Shift';
            } else {
                alert('Gagal: ' + (data.message || 'Unknown error'));
                btn.disabled = false;
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Akhiri Shift';
            }
        })
        .catch(err => {
            alert('Error: ' + err.message);
            btn.disabled = false;
            btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Akhiri Shift';
        });
    });
}

// ── Shift Validation Modal Functions ──
function openShiftValidationModal(issues) {
    const body = document.getElementById('shiftValidationBody');
    if (!body) return;
    body.innerHTML = '';
    const sections = {
        dt: { label: 'Downtime', color: 'red', icon: '⏱' },
    };
    const keys = Object.keys(sections).filter(k => (issues[k] || []).length > 0);
    if (keys.length === 0) {
        body.innerHTML = '<p class="text-xs text-slate-500">Tidak ada masalah yang perlu diperbaiki.</p>';
    } else {
        keys.forEach(k => {
            const s = sections[k];
            const items = issues[k] || [];
            const listHtml = '<ul class="mt-2 space-y-1">' + items.map(item =>
                '<li class="flex items-center justify-between text-xs text-slate-700">' +
                    '<span>&bull; ' + item.item + ': ' + item.issue + '</span>' +
                    '<button onclick="goToIssue(\'' + k + '\',' + item.plan_id + ',' + item.job_master_id + ',' + (item.dt_id || 'null') + ')" class="ml-2 px-2 py-0.5 bg-' + s.color + '-50 hover:bg-' + s.color + '-100 text-' + s.color + '-700 rounded-lg text-[10px] font-bold transition-all whitespace-nowrap">' +
                        '&rarr; Buka' +
                    '</button>' +
                '</li>'
            ).join('') + '</ul>';
            body.innerHTML +=
                '<div class="bg-' + s.color + '-50 border border-' + s.color + '-200 rounded-xl p-4">' +
                    '<h4 class="text-sm font-black text-' + s.color + '-700 uppercase tracking-wider">' + s.icon + ' ' + s.label + ' (' + items.length + ' item)</h4>' +
                    listHtml +
                '</div>';
        });
    }
    const modal = document.getElementById('shiftValidationModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeShiftValidationModal() {
    const modal = document.getElementById('shiftValidationModal');
    modal.classList.remove('flex');
    modal.classList.add('hidden');
}

function goToIssue(type, planId, jobMasterId, dtId) {
    closeShiftValidationModal();
    const row = document.getElementById('row-' + planId);
    if (row) {
        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        row.classList.add('ring-2', 'ring-blue-500', 'rounded-xl');
        setTimeout(() => row.classList.remove('ring-2', 'ring-blue-500', 'rounded-xl'), 2000);
    } else {
        showToast('Baris item tidak ditemukan.', 'info');
    }
    setTimeout(function () {
        if (type === 'dt') {
            window.openDowntimeReport ? window.openDowntimeReport(jobMasterId, null) : null;
        }
    }, 500);
}

    const _pageDate = '{{ $date }}';
    const _initShift = {{ $isShiftPagi ? 1 : 2 }};
    let _shifting = false;
    
    function currentShiftFromClock(){
        const n = new Date(), h = n.getHours(), m = n.getMinutes();
        return (h >= 21 || h < 7 || (h === 7 && m < 30)) ? 2 : 1;
    }

    setInterval(function () {
        if (_shifting) return;
        
        const now = new Date();
        const currentShift = currentShiftFromClock();
        
        // Calculate logical date (if Shift 2 and before 07:30, it belongs to previous day)
        let logicalDate = new Date(now);
        if (now.getHours() < 7 || (now.getHours() === 7 && now.getMinutes() < 30)) {
            logicalDate.setDate(logicalDate.getDate() - 1);
        }
        const logicalToday = logicalDate.getFullYear() + '-' +
            String(logicalDate.getMonth() + 1).padStart(2, '0') + '-' +
            String(logicalDate.getDate()).padStart(2, '0');
            
        // Check if shift changed OR logical date changed
        if (currentShift !== _initShift || logicalToday !== _pageDate) {
            _shifting = true;
            
            // Show toast
            const el = document.getElementById('shiftToast');
            const msg = document.getElementById('shiftToastMsg');
            if (el && msg) {
                msg.textContent = currentShift === 1 ? '⏰ SHIFT BERUBAH → SHIFT PAGI' : '⏰ SHIFT BERUBAH → SHIFT MALAM';
                el.classList.toggle('night', currentShift === 2);
                el.classList.add('show');
            }
            
            setTimeout(() => { window.location.reload(); }, 5000);
        }
    }, 10000);
</script>

@vite(['resources/js/operational/production-engine.js'])
{{-- MODAL PULL AHEAD --}}
<div id="pullAheadModal" class="fixed inset-0 z-[100] flex justify-end bg-black/40 backdrop-blur-sm hidden" onclick="if(event.target === this) closePullAheadModal()">
    <div class="bg-gray-50 w-full max-w-2xl h-full shadow-2xl flex flex-col transform translate-x-full transition-transform duration-300" id="pullAheadSidebar">
        
        <div class="bg-white px-6 py-5 border-b border-gray-200 flex justify-between items-center shrink-0">
            <div>
                <h3 class="text-xl font-bold text-gray-800">Jadwal Shift Berikutnya</h3>
                <p class="text-sm text-blue-600 font-semibold mt-1" id="nextShiftLabel">Loading...</p>
            </div>
            <button onclick="closePullAheadModal()" class="text-gray-400 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-4" id="nextShiftContainer">
            <div class="flex items-center justify-center h-40">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
        </div>

    </div>
</div>

{{-- MODAL FORM REQUEST PULL AHEAD --}}
<div id="pullAheadFormModal" class="fixed inset-0 z-[110] flex items-center justify-center bg-gray-900/40 backdrop-blur-sm hidden transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 transition-all duration-300">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 tracking-tight">Request Pull Ahead</h3>
            </div>
            <button onclick="closePullAheadFormModal()" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-full transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        
        <!-- Body -->
        <div class="p-6">
            <!-- Item Info Card -->
            <div class="mb-6 bg-gradient-to-br from-blue-50 to-blue-50/20 p-5 rounded-2xl border border-blue-100/50 shadow-inner">
                <p class="text-xs font-semibold tracking-wider text-blue-500 uppercase mb-1">Item yang akan ditarik</p>
                <div class="font-black text-2xl text-blue-900 tracking-tight" id="reqItemName">-</div>
                <div class="flex items-center gap-2 mt-2">
                    <div class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-md">
                        Max Qty: <span id="reqAvailableQty" class="text-sm">0</span> PCS
                    </div>
                </div>
            </div>

            <form id="pullAheadRequestForm" onsubmit="submitPullAheadRequest(event)">
                <input type="hidden" id="reqPlanId">
                
                <div class="mb-5 relative">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Qty yang diminta (PCS)</label>
                    <div class="relative">
                        <input type="number" id="reqQty" min="1" class="w-full pl-4 pr-12 py-3 rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all font-semibold text-gray-800 text-lg" placeholder="Misal: 50" required>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-medium text-sm">PCS</span>
                    </div>
                </div>

                <div class="mb-8">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sisipkan Setelah Job (Opsional)</label>
                    <select id="reqSequenceAfter" class="w-full px-4 py-3 rounded-xl border-gray-200 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all text-gray-700 font-medium appearance-none bg-white">
                        <option value="">-- Letakkan di Antrean Paling Bawah --</option>
                    </select>
                </div>

                <!-- Footer / Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closePullAheadFormModal()" class="text-gray-500 hover:text-gray-700 bg-white hover:bg-gray-50 border border-gray-200 font-semibold px-5 py-2.5 rounded-xl transition-all shadow-sm">Batal</button>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl shadow-lg shadow-blue-600/20 hover:shadow-blue-600/40 transition-all flex items-center gap-2" id="btnSubmitReq">
                        <span>Kirim Request</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="shiftToast" class="shift-toast" role="alert">
    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
    <span id="shiftToastMsg"></span>
</div>

<script>
    let currentNextShift = '';
    let currentLine = '{{ request('line', 'Line A') }}';
    let currentShiftName = '{{ request('shift', 'Shift Pagi') }}';
    let currentDate = '{{ request('date', now()->toDateString()) }}';

    function openPullAheadModal() {
        const modal = document.getElementById('pullAheadModal');
        const sidebar = document.getElementById('pullAheadSidebar');
        modal.classList.remove('hidden');
        setTimeout(() => { sidebar.classList.remove('translate-x-full'); }, 10);
        
        // Fetch data
        fetch(`/operational/next-shift?line=${currentLine}&shift=${currentShiftName}&date=${currentDate}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('nextShiftLabel').innerText = data.next_shift_name;
                currentNextShift = data.next_shift_name;
                
                // Populate options for sequence
                const select = document.getElementById('reqSequenceAfter');
                select.innerHTML = '<option value="">-- Paling Bawah (Default) --</option>';
                data.current_shift_plans.forEach(plan => {
                    select.innerHTML += `<option value="${plan.id}">${plan.job_master} (No: ${plan.row_no})</option>`;
                });

                // Render cards
                const container = document.getElementById('nextShiftContainer');
                container.innerHTML = '';
                
                if (data.next_shift_plans.length === 0) {
                    container.innerHTML = '<div class="text-center text-gray-500 py-10">Jadwal shift berikutnya kosong.</div>';
                    return;
                }

                data.next_shift_plans.forEach(plan => {
                    const available = plan.available_qty;
                    const card = `
                        <div class="bg-white border ${available > 0 ? 'border-gray-200 hover:border-blue-300' : 'border-gray-200 opacity-60'} rounded-xl p-4 shadow-sm transition-colors">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-gray-800">${plan.job_master}</h4>
                                    <p class="text-xs text-gray-500 mt-1">${plan.job_no}</p>
                                </div>
                                <span class="bg-gray-100 text-gray-600 text-xs font-bold px-2 py-1 rounded">Seq: ${plan.row_no}</span>
                            </div>
                            <div class="mt-4 flex justify-between items-end">
                                <div>
                                    <div class="text-xs text-gray-500 mb-1">Available Qty</div>
                                    <div class="font-bold text-lg ${available > 0 ? 'text-blue-600' : 'text-red-500'}">${available} PCS</div>
                                </div>
                                ${available > 0 
                                    ? `<button onclick="openPullAheadFormModal(${plan.id}, '${plan.job_master}', ${available})" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 rounded-lg shadow-sm">Tarik Item</button>`
                                    : `<span class="text-xs font-bold text-red-500 bg-red-50 px-3 py-1.5 rounded-lg">Habis / Pending</span>`
                                }
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', card);
                });
            })
            .catch(err => {
                document.getElementById('nextShiftContainer').innerHTML = '<div class="text-center text-red-500 py-10">Gagal mengambil data jadwal.</div>';
            });
    }

    function closePullAheadModal() {
        const sidebar = document.getElementById('pullAheadSidebar');
        sidebar.classList.add('translate-x-full');
        setTimeout(() => { document.getElementById('pullAheadModal').classList.add('hidden'); }, 300);
    }

    function openPullAheadFormModal(planId, itemName, availableQty) {
        document.getElementById('reqPlanId').value = planId;
        document.getElementById('reqItemName').innerText = itemName;
        document.getElementById('reqAvailableQty').innerText = availableQty;
        document.getElementById('reqQty').max = availableQty;
        document.getElementById('reqQty').value = availableQty;
        
        document.getElementById('pullAheadFormModal').classList.remove('hidden');
    }

    function closePullAheadFormModal() {
        document.getElementById('pullAheadFormModal').classList.add('hidden');
    }

    function submitPullAheadRequest(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSubmitReq');
        btn.innerText = 'Mengirim...';
        btn.disabled = true;

        const payload = {
            original_plan_id: document.getElementById('reqPlanId').value,
            qty_requested: document.getElementById('reqQty').value,
            proposed_sequence_after: document.getElementById('reqSequenceAfter').value,
            target_shift: currentShiftName,
            source_shift: currentNextShift
        };

        fetch('/operational/pull-ahead', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            btn.innerHTML = '<span>Kirim Request</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>';
            btn.disabled = false;
            
            if(data.success) {
                showToast(data.message, 'success');
                closePullAheadFormModal();
                // Refresh modal content to update available qty
                document.getElementById('nextShiftContainer').innerHTML = '<div class="flex items-center justify-center h-40"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div></div>';
                openPullAheadModal(); 
            } else {
                showToast(data.message, 'error');
            }
        })
        .catch(err => {
            btn.innerHTML = '<span>Kirim Request</span><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>';
            btn.disabled = false;
            showToast('Terjadi kesalahan sistem.', 'error');
        });
    }

    // Auto Refresh pada pergantian shift pagi (07:30:02)
    setInterval(() => {
        const now = new Date();
        if (now.getHours() === 7 && now.getMinutes() === 30 && now.getSeconds() === 2) {
            const url = new URL(window.location.href);
            url.searchParams.delete('shift'); // Hapus parameter shift lama agar auto-detect
            window.location.href = url.toString();
        }
    }, 1000);
</script>

@endsection

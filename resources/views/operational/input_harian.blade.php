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

</style>
<div class="space-y-6">

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
                    class="flex items-center gap-2 {{ ($allJobsDone ?? false) ? 'bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 shadow-md shadow-orange-500/10' : 'bg-gradient-to-r from-slate-400 to-slate-500 hover:from-slate-500 hover:to-slate-600 shadow-sm' }} text-white font-bold text-sm px-5 py-2.5 rounded-xl transition-all border border-orange-600/20">
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
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5 ml-1">Status</label>
                    <select name="status" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 focus:border-primary-red outline-none transition bg-white cursor-pointer">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ strtolower(request('status'))=='pending'?'selected':'' }}>Pending</option>
                        <option value="running" {{ strtolower(request('status'))=='running'?'selected':'' }}>Running</option>
                        <option value="complete" {{ strtolower(request('status'))=='complete'?'selected':'' }}>Complete</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5 ml-1">Pencarian</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Job # atau Nama Item..."
                            class="w-full border border-gray-300 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 focus:border-primary-red outline-none transition bg-white">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-primary-red hover:bg-red-700 text-white font-bold text-sm transition-all shadow-md">Filter</button>
                    <a href="{{ route('operational.input_harian') }}" class="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-500 hover:bg-gray-50 font-bold text-sm transition-all flex items-center justify-center">Reset</a>
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
        @include('operational.components.active-job-board')
    @endif

    {{-- SHIFT SUBMISSION BANNER --}}
    @if(!isset($isHistorical) || !$isHistorical)
    @if(!($isLocked ?? false))
    <div id="shiftSubmissionBanner" class="mb-6">
        <div class="bg-white rounded-2xl border {{ ($allJobsDone ?? false) ? 'border-orange-200 bg-orange-50/50' : 'border-gray-200' }} shadow-sm overflow-hidden">
            <div class="px-5 py-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl {{ ($allJobsDone ?? false) ? 'bg-orange-100' : 'bg-slate-100' }} flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ ($allJobsDone ?? false) ? 'text-orange-600' : 'text-slate-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black {{ ($allJobsDone ?? false) ? 'text-orange-800' : 'text-slate-600' }} uppercase tracking-widest">Akhiri Shift</h3>
                        <p class="text-[10px] font-bold {{ ($allJobsDone ?? false) ? 'text-orange-600' : 'text-slate-400' }} mt-0.5">
                            @if($allJobsDone ?? false)
                                Semua item sudah selesai. Klik untuk finalisasi shift.
                            @else
                                @php
                                    $completedCount = $jobPlans->filter(fn($p) => optional($p->job_data)->status === 'complete')->count();
                                    $totalJobs = $jobPlans->count();
                                @endphp
                                {{ $completedCount }}/{{ $totalJobs }} item selesai. Semua item harus selesai sebelum shift bisa difinalisasi.
                            @endif
                        </p>
                    </div>
                </div>
                <button id="submitShiftBtn" onclick="submitShift()" 
                    class="flex items-center gap-2 {{ ($allJobsDone ?? false) ? 'bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 shadow-md shadow-orange-500/10' : 'bg-slate-200 text-slate-400 cursor-not-allowed' }} text-white font-bold text-xs px-5 py-2.5 rounded-xl transition-all border {{ ($allJobsDone ?? false) ? 'border-orange-600/20' : 'border-slate-300' }}"
                    {{ ($allJobsDone ?? false) ? '' : 'disabled' }}>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ ($allJobsDone ?? false) ? 'Akhiri Shift' : 'Menunggu...' }}</span>
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
                    elseif($dtTypeLower == 'break time') $btnType = 'break';
                    elseif($dtTypeLower == 'dandori') $btnType = 'dandori';
                @endphp
                "{{ $jd->id }}_{{ $btnType }}": { 
                    id: {{ $rdt->id }}, 
                    start: new Date("{{ \Carbon\Carbon::parse($rdt->start_time)->toIso8601String() }}"), 
                    jobId: {{ $jd->id }}, 
                    btnType: "{{ $btnType }}",
                    dtType: "{{ $rdt->jenis_downtime }}",
                    problem: {!! json_encode($rdt->problem ?? '') !!}
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
                elseif($dtTypeLower == 'break time') $btnType = 'break';
                elseif($dtTypeLower == 'dandori') $btnType = 'dandori';
            @endphp
            "{{ $activeJob->id }}_{{ $btnType }}": { 
                id: {{ $rdt->id }}, 
                start: new Date("{{ \Carbon\Carbon::parse($rdt->start_time)->toIso8601String() }}"), 
                jobId: {{ $activeJob->id }}, 
                btnType: "{{ $btnType }}",
                dtType: "{{ $rdt->jenis_downtime }}",
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
        line: "{{ $job->press_name ?? '' }}"
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
        line: "{{ $activeJob->line ?? '' }}"
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
                'problem' => $dt->problem
            ];
        })->toArray(),
        $jd->dandoris->filter(fn($d) => ($d->jenis_dandori ?? '') === '1st_check' && $d->finish_time)->map(function($d){
            return [
                'id' => 'fc_'.$d->id,
                'start' => \Carbon\Carbon::parse($d->start_time)->timestamp * 1000,
                'end' => \Carbon\Carbon::parse($d->finish_time)->timestamp * 1000,
                'type' => '1st_check',
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
                'problem' => $dt->problem
            ];
        })->toArray(),
        $activeJob->dandoris->filter(fn($d) => ($d->jenis_dandori ?? '') === '1st_check' && $d->finish_time)->map(function($d){
            return [
                'id' => 'fc_'.$d->id,
                'start' => \Carbon\Carbon::parse($d->start_time)->timestamp * 1000,
                'end' => \Carbon\Carbon::parse($d->finish_time)->timestamp * 1000,
                'type' => '1st_check',
                'problem' => null
            ];
        })->toArray()
    )) !!},
    @endif
};

// ——— AUTO BREAK TIME SCHEDULE ———
@php
    $dayMap = ['Monday'=>'senin','Tuesday'=>'selasa','Wednesday'=>'rabu','Thursday'=>'kamis','Friday'=>'jumat','Saturday'=>'sabtu','Sunday'=>'minggu'];
    $bDay = $dayMap[now()->format('l')] ?? strtolower(now()->format('l'));
    $bShift = ((int) now()->format('H') >= 7 && (int) now()->format('H') < 19) ? 'Shift Pagi' : 'Shift Malam';
    $breakScheduleData = \App\Models\MasterBreakTime::where('is_active', true)
        ->where(function($q) use ($bDay) { $q->where('hari', $bDay)->orWhere('hari', 'semua'); })
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
                showToast('Shift berhasil disubmit!', 'success');
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Shift Disubmit';
                btn.classList.remove('from-orange-500', 'to-red-600', 'hover:from-orange-600', 'hover:to-red-700');
                btn.classList.add('from-emerald-500', 'to-teal-600', 'cursor-default');
                btn.onclick = null;
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
    const sections = [
        { key: 'dt', label: 'Downtime', color: 'red', icon: '⏱' },
        { key: 'repair', label: 'Repair', color: 'orange', icon: '🔧' },
        { key: 'reject', label: 'Reject', color: 'rose', icon: '🗑' },
        { key: 'remain', label: 'Remain', color: 'yellow', icon: '📦' },
    ];
    sections.forEach(s => {
        const items = issues[s.key] || [];
        let listHtml = items.length === 0
            ? '<p class="mt-1 text-xs text-slate-500">Tidak ada masalah.</p>'
            : '<ul class="mt-2 space-y-1">' + items.map(item =>
                '<li class="flex items-center justify-between text-xs text-slate-300">' +
                    '<span>&bull; ' + item.item + ': ' + item.issue + '</span>' +
                    '<button onclick="goToIssue(\'' + s.key + '\',' + item.plan_id + ',' + item.job_master_id + ',' + (item.dt_id || 'null') + ')" class="ml-2 px-2 py-0.5 bg-' + s.color + '-500/20 hover:bg-' + s.color + '-500/30 text-' + s.color + '-300 rounded-lg text-[10px] font-bold transition-all whitespace-nowrap">' +
                        '&rarr; Buka' +
                    '</button>' +
                '</li>'
            ).join('') + '</ul>';
        body.innerHTML +=
            '<div class="bg-' + s.color + '-500/10 border border-' + s.color + '-500/20 rounded-xl p-4">' +
                '<h4 class="text-sm font-black text-' + s.color + '-400 uppercase tracking-wider">' + s.icon + ' ' + s.label + ' (' + items.length + ' item)</h4>' +
                listHtml +
            '</div>';
    });
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
    if (type === 'remain') {
        showToast('Item masih berjalan — selesaikan dulu.', 'info');
        return;
    }
    setTimeout(function () {
        if (type === 'dt') {
            window.openDowntimeReport ? window.openDowntimeReport(jobMasterId, null) : null;
        } else if (type === 'repair') {
            window.openRRInputModal ? window.openRRInputModal(jobMasterId, 'repair', 0) : null;
        } else if (type === 'reject') {
            window.openRRInputModal ? window.openRRInputModal(jobMasterId, 'reject', 0) : null;
        }
    }, 500);
}

    const _pageDate = '{{ $date }}';
    setInterval(function () {
        const now = new Date();
        const today = now.getFullYear() + '-' +
            String(now.getMonth() + 1).padStart(2, '0') + '-' +
            String(now.getDate()).padStart(2, '0');
        if (today !== _pageDate) {
            window.location.reload();
        }
    }, 30000);
</script>

@php
    $peLegacyFiles = glob(public_path('build/assets/production-engine-legacy-*.js'));
    if (!empty($peLegacyFiles)) {
        usort($peLegacyFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        echo '<script src="' . asset('build/assets/' . basename($peLegacyFiles[0])) . '"></script>';
    }
@endphp
@endsection

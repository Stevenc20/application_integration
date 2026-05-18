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
        background-color: #f59e0b; /* Solid color instead of animated gradient */
    }

    /* IDLE ALERT BAR */
    #idle-alert-bar {
        position: sticky;
        top: 1rem;
        z-index: 100;
        margin-bottom: 1.5rem;
        display: none;
        animation: slideInDown 0.5s ease-out;
    }
    @keyframes slideInDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .idle-card {
        background: #ef4444; /* Solid red */
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 1.25rem;
        padding: 0.75rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        color: white;
        transition: all 0.3s ease;
    }
    
    /* STYLE SAAT SCROLL (Sederhana) */
    .idle-card.scrolled {
        background: rgba(239, 68, 68, 0.8);
        padding: 0.5rem 1.5rem;
        transform: scale(0.98);
    }
</style>
<div class="space-y-6">

    {{-- IDLE INPUT ALERT BAR --}}
    <div id="idle-alert-bar">
        <div class="idle-card">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-xs font-black text-white uppercase tracking-[0.2em] leading-none mb-1">PERINGATAN INAKTIVITAS PRODUKSI</h4>
                    <p class="text-[13px] text-red-50 font-bold">Sistem mendeteksi Anda belum melakukan input data selama <span id="idle-time-display" class="bg-white text-red-600 px-2 py-0.5 rounded-lg mx-1 shadow-sm font-black">--:--</span> menit.</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <p class="hidden md:block text-[10px] font-black text-red-100 italic uppercase tracking-widest opacity-80">Segera update hasil produksi Anda!</p>
                <button onclick="document.getElementById('idle-alert-bar').style.display='none'" class="w-10 h-10 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-all flex items-center justify-center border border-white/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Input Harian</h1>
            <p class="text-sm text-gray-500 mt-1">Pencatatan produksi harian per item &amp; line</p>
        </div>
        <div class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-4 py-2.5 shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary-red" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span class="text-sm font-semibold text-gray-700">{{ now()->format('d F Y') }}</span>
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
            <h2 class="font-semibold text-gray-800">Filter Produksi</h2>
        </div>
        <div class="p-5">
            <form method="GET" action="{{ route('operational.input_harian') }}"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                
                {{-- Preserve Shift and Line filters --}}
                <input type="hidden" name="shift" value="{{ request('shift') }}">
                <input type="hidden" name="line" value="{{ request('line') }}">

                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5 ml-1">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date', $date) }}"
                        class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 focus:border-primary-red outline-none transition bg-white">
                </div>
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
                    <a href="{{ route('operational.input_harian', ['date' => $date]) }}" class="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-500 hover:bg-gray-50 font-bold text-sm transition-all flex items-center justify-center">Reset</a>
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
        
    @include('operational.components.active-job-board')

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

@include('operational.components.modals')

@endsection

@section('scripts')
<script>
/**
 * DYNAMIC CONFIGURATION FOR PRODUCTION ENGINE
 */
window.ProductionConfig = {
    csrfToken: '{{ csrf_token() }}',
    currentActiveId: {{ $activeJob->id ?? 'null' }},
    currentStatus: '{{ $activeJob->status ?? "none" }}',
    currentIsDandori: {{ isset($activeJob) && $activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->count() > 0 ? 'true' : 'false' }},
    currentDowntimeCount: {{ isset($activeJob) ? $activeJob->downtimes->whereNull('finish_time')->count() : 0 }},
    totalDowntimeCount: {{ isset($activeJob) ? $activeJob->downtimes->count() : 0 }},
    currentDowntimeType: {!! isset($activeJob) && $activeJob->downtimes->whereNull('finish_time')->first() ? "'".$activeJob->downtimes->whereNull('finish_time')->first()->jenis_downtime."'" : "null" !!},
    userName: '{{ auth()->user()->name }}',
    lastInputAt: {!! $lastInputAt ? "'".\Carbon\Carbon::parse($lastInputAt)->toIso8601String()."'" : "null" !!}
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
                    elseif($dtTypeLower == 'idle time') $btnType = 'idle';
                    elseif($dtTypeLower == 'break time') $btnType = 'break';
                    elseif($dtTypeLower == 'dandori') $btnType = 'dandori';
                @endphp
                "{{ $jd->id }}_{{ $btnType }}": { 
                    id: {{ $rdt->id }}, 
                    start: new Date("{{ \Carbon\Carbon::parse($rdt->start_time)->toIso8601String() }}"), 
                    jobId: {{ $jd->id }}, 
                    btnType: "{{ $btnType }}",
                    dtType: "{{ $rdt->jenis_downtime }}"
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
                elseif($dtTypeLower == 'idle time') $btnType = 'idle';
                elseif($dtTypeLower == 'break time') $btnType = 'break';
                elseif($dtTypeLower == 'dandori') $btnType = 'dandori';
            @endphp
            "{{ $activeJob->id }}_{{ $btnType }}": { 
                id: {{ $rdt->id }}, 
                start: new Date("{{ \Carbon\Carbon::parse($rdt->start_time)->toIso8601String() }}"), 
                jobId: {{ $activeJob->id }}, 
                btnType: "{{ $btnType }}",
                dtType: "{{ $rdt->jenis_downtime }}"
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
        plan_start: {{ \Carbon\Carbon::parse($jd->plan_start ?: $activeDate . ' 07:40')->timestamp * 1000 }},
        plan_end: {{ \Carbon\Carbon::parse($jd->plan_end ?: $activeDate . ' 10:40')->timestamp * 1000 }},
        started_at: {{ $jd->started_at ? \Carbon\Carbon::parse($jd->started_at)->timestamp * 1000 : 'null' }},
        finished_at: {{ $jd->finished_at ? \Carbon\Carbon::parse($jd->finished_at)->timestamp * 1000 : 'null' }},
        base_seconds: {{ $jd->dailyProduction ? (int)$jd->dailyProduction->runtime_seconds : 0 }},
        target_qty: {{ $job->plan ?? 0 }},
        actual_ok: {{ $jd->dailyProduction->actual_ok ?? 0 }},
        actual_repair: {{ $jd->dailyProduction->actual_repair ?? 0 }},
        actual_reject: {{ $jd->dailyProduction->actual_reject ?? 0 }},
        dandori_start: {{ $jd->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->first() ? \Carbon\Carbon::parse($jd->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->first()->start_time)->timestamp * 1000 : 'null' }},
        first_dandori_start: {{ $jd->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->sortBy('start_time')->first() ? \Carbon\Carbon::parse($jd->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->sortBy('start_time')->first()->start_time)->timestamp * 1000 : 'null' }}
    },
    @endif
    @endforeach

    {{-- FORCE INCLUDE ACTIVE JOB IF NOT IN PAGINATED LIST --}}
    @if(isset($activeJob) && !(is_object($jobs) && method_exists($jobs, 'getCollection') ? $jobs->getCollection() : $jobs)->pluck('job_data.id')->contains($activeJob->id))
    "{{ $activeJob->id }}": {
        id: {{ $activeJob->id }},
        status: "{{ $activeJob->status }}",
        @php $activeDate = request('date', now()->toDateString()); @endphp
        plan_start: {{ \Carbon\Carbon::parse($activeJob->plan_start ?: $activeDate . ' 07:40')->timestamp * 1000 }},
        plan_end: {{ \Carbon\Carbon::parse($activeJob->plan_end ?: $activeDate . ' 10:40')->timestamp * 1000 }},
        started_at: {{ $activeJob->started_at ? \Carbon\Carbon::parse($activeJob->started_at)->timestamp * 1000 : 'null' }},
        finished_at: {{ $activeJob->finished_at ? \Carbon\Carbon::parse($activeJob->finished_at)->timestamp * 1000 : 'null' }},
        base_seconds: {{ $activeJob->dailyProduction ? (int)$activeJob->dailyProduction->runtime_seconds : 0 }},
        target_qty: {{ $activeJob->plan ?? 0 }},
        actual_ok: {{ $activeJob->dailyProduction->actual_ok ?? 0 }},
        actual_repair: {{ $activeJob->dailyProduction->actual_repair ?? 0 }},
        actual_reject: {{ $activeJob->dailyProduction->actual_reject ?? 0 }},
        dandori_start: {{ $activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->first() ? \Carbon\Carbon::parse($activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->whereNull('finish_time')->first()->start_time)->timestamp * 1000 : 'null' }},
        first_dandori_start: {{ $activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->sortBy('start_time')->first() ? \Carbon\Carbon::parse($activeJob->downtimes->filter(fn($d) => strtolower($d->jenis_downtime) === 'dandori')->sortBy('start_time')->first()->start_time)->timestamp * 1000 : 'null' }}
    },
    @endif
};

window.jobDowntimeHistory = {
    @foreach($jobs as $job)
    @php $jd = $job->job_data; @endphp
    @if($jd)
    "{{ $jd->id }}": {!! json_encode($jd->downtimes->map(function($dt){ 
        return [
            'start' => \Carbon\Carbon::parse($dt->start_time)->timestamp * 1000,
            'end' => $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time)->timestamp * 1000 : null,
            'type' => $dt->jenis_downtime
        ];
    })) !!},
    @endif
    @endforeach

    @if(isset($activeJob) && !(is_object($jobs) && method_exists($jobs, 'getCollection') ? $jobs->getCollection() : $jobs)->pluck('job_data.id')->contains($activeJob->id))
    "{{ $activeJob->id }}": {!! json_encode($activeJob->downtimes->map(function($dt){ 
        return [
            'start' => \Carbon\Carbon::parse($dt->start_time)->timestamp * 1000,
            'end' => $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time)->timestamp * 1000 : null,
            'type' => $dt->jenis_downtime
        ];
    })) !!},
    @endif
};
</script>

@vite(['resources/js/operational/production-engine.js'])
@endsection

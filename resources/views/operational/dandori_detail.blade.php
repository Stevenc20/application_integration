@extends('layouts.supervisor')

@section('title', 'Dandori Detail - ' . $job->job_number)

@section('content')
<div class="space-y-6">
    {{-- ======================================================= --}}
    {{-- PAGE HEADER --}}
    {{-- ======================================================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dandori Detail</h1>
            <p class="text-sm text-gray-500 mt-1">Pencatatan Changeover untuk Job #{{ $job->job_number }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('operational.dandori') }}" class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-gray-200 text-gray-700 text-sm font-semibold hover:bg-gray-50 transition-colors shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- JOB INFO CARD --}}
    {{-- ======================================================= --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Item / Part Number</label>
                    <p class="font-bold text-gray-800">{{ $job->job_number }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Part Name</label>
                    <p class="font-bold text-gray-800">{{ $job->job_name ?? '-' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Line Produksi</label>
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span>
                        <p class="font-bold text-gray-800">{{ $job->line }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Shift</label>
                    <p class="font-bold text-gray-800" id="displayShift">Shift 1</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Durasi Dandori</label>
                    <p class="text-xl font-black text-primary-red">{{ number_format($totalDuration, 2) }} <span class="text-xs font-bold text-gray-400 uppercase">Menit</span></p>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- ACTIVITY LIST CARD --}}
    {{-- ======================================================= --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        {{-- Card Header --}}
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-primary-red text-white flex items-center justify-center shadow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-gray-800 text-base">Pencatatan Aktivitas</h2>
                    <p class="text-xs text-gray-500">Catat waktu start & finish setiap tahapan changeover</p>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-white text-left">
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100">Jenis Aktivitas</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 text-center">Mulai</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 text-center">Selesai</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 text-center">Durasi (Mnt)</th>
                        <th class="px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-100 text-right">Kontrol</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($dandoriStatus as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-gray-800">{{ $item['type_display'] }}</p>
                        </td>
                        
                        @if ($item['record'])
                            <td class="px-6 py-4 text-center">
                                <span class="px-2.5 py-1 rounded-lg bg-gray-100 font-mono text-xs text-gray-600 border border-gray-200">
                                    {{ \Carbon\Carbon::parse($item['record']->start_time)->format('H:i:s') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($item['record']->finish_time)
                                    <span class="px-2.5 py-1 rounded-lg bg-green-50 font-mono text-xs text-green-700 border border-green-100">
                                        {{ \Carbon\Carbon::parse($item['record']->finish_time)->format('H:i:s') }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—:—:—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center font-bold {{ $item['record']->duration_minutes > 0 ? 'text-blue-600' : 'text-gray-400' }}">
                                {{ number_format($item['record']->duration_minutes, 2) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    @if ($item['record']->finish_time)
                                        <form action="{{ route('operational.dandori.restart', $item['record']->id) }}" method="POST" onsubmit="return confirm('Reset timer untuk aktivitas ini?')">
                                            @csrf
                                            <button type="submit" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 text-amber-600 text-xs font-bold hover:bg-amber-100 transition-colors">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                </svg>
                                                Restart
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('operational.dandori.stop', $item['record']->id) }}" method="POST" onsubmit="return confirm('Selesaikan aktivitas ini?')">
                                            @csrf
                                            <button type="submit" class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-red-600 text-white text-xs font-bold hover:bg-red-700 transition-colors shadow-sm shadow-red-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/>
                                                </svg>
                                                Stop
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        @else
                            <td class="px-6 py-4 text-center text-gray-300">—:—:—</td>
                            <td class="px-6 py-4 text-center text-gray-300">—:—:—</td>
                            <td class="px-6 py-4 text-center text-gray-400">0.00</td>
                            <td class="px-6 py-4 text-right">
                                <form action="{{ route('operational.dandori.start', ['id' => $job->id, 'type' => $item['type_code']]) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="shift" id="hiddenShift" value="Shift 1">
                                    <button type="submit" class="flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition-colors shadow-sm shadow-emerald-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Start
                                    </button>
                                </form>
                            </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Toast Notification Script --}}
@if(session('success'))
<div id="alert-success" class="fixed top-5 right-5 z-[9999] flex items-center gap-3 min-w-[280px] max-w-sm px-4 py-3.5 rounded-2xl shadow-2xl text-white text-sm font-medium border border-white/10 backdrop-blur-sm bg-emerald-500 transform transition-all duration-300 translate-x-0" role="alert">
    <div class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center bg-white/20">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
    </div>
    <span class="flex-1">{{ session('success') }}</span>
    <button onclick="this.closest('#alert-success').classList.add('hidden')" class="flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
<script>setTimeout(() => document.getElementById('alert-success')?.classList.add('translate-x-[120%]'), 3000);</script>
@endif

@if(session('warning'))
<div id="alert-warning" class="fixed top-5 right-5 z-[9999] flex items-center gap-3 min-w-[280px] max-w-sm px-4 py-3.5 rounded-2xl shadow-2xl text-white text-sm font-medium border border-white/10 backdrop-blur-sm bg-amber-500 transform transition-all duration-300 translate-x-0" role="alert">
    <div class="flex-shrink-0 w-8 h-8 rounded-xl flex items-center justify-center bg-white/20">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    </div>
    <span class="flex-1">{{ session('warning') }}</span>
    <button onclick="this.closest('#alert-warning').classList.add('hidden')" class="flex-shrink-0 opacity-60 hover:opacity-100 transition-opacity">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>
<script>setTimeout(() => document.getElementById('alert-warning')?.classList.add('translate-x-[120%]'), 3000);</script>
@endif

@endsection

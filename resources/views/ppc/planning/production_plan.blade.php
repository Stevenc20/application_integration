@extends('layouts.ppc')
@section('title', 'Production Planning')

@section('content')
<div class="space-y-6">
    @if(session('success'))
    <script>document.addEventListener('DOMContentLoaded',function(){showToast({!! json_encode(session('success')) !!},'success');});</script>
    @endif
    @if(session('error'))
    <script>document.addEventListener('DOMContentLoaded',function(){showToast({!! json_encode(session('error')) !!},'error');});</script>
    @endif
    @if($errors->any())
    @foreach($errors->all() as $err)
    <script>document.addEventListener('DOMContentLoaded',function(){showToast({!! json_encode($err) !!},'error');});</script>
    @endforeach
    @endif
    @php
        $importPreview = session('import_preview');
    @endphp
    @if($importPreview && isset($importPreview['counts']))
    <div class="mx-auto max-w-screen-2xl px-4">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-black text-slate-800 uppercase tracking-tight">Preview Import (Merge)</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Revisi PPC hanya menambah/mengubah jadwal. Item yang tidak tercantum <b>tidak dihapus</b> dan aktual produksi tetap utuh.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2 text-[11px] font-bold">
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200">Added: {{ $importPreview['counts']['added'] ?? 0 }}</span>
                    <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-200">Updated: {{ $importPreview['counts']['updated'] ?? 0 }}</span>
                    <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-600 ring-1 ring-slate-200">Unchanged: {{ $importPreview['counts']['unchanged'] ?? 0 }}</span>
                    <span class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 ring-1 ring-rose-200">Not in revision: {{ $importPreview['counts']['not_in_revision'] ?? 0 }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 divide-y lg:divide-y-0 lg:divide-x divide-slate-100">
                <div class="p-4">
                    <details open>
                        <summary class="cursor-pointer text-xs font-black text-slate-700">Added ({{ $importPreview['counts']['added'] ?? 0 }})</summary>
                        <div class="mt-2 max-h-48 overflow-auto space-y-1">
                            @forelse(($importPreview['added'] ?? []) as $item)
                            <div class="text-[11px] text-slate-600 flex items-center justify-between gap-2 border-b border-slate-50 pb-1">
                                <span class="truncate">{{ $item['shift'] ?? '-' }} &bull; {{ $item['press'] ?? '-' }} &bull; <b>{{ $item['job_no'] ?? '-' }}</b> {{ $item['job_master'] ?? '' }}</span>
                                <span class="shrink-0 text-slate-400">{{ $item['start_time'] ?? '-' }}–{{ $item['finish_time'] ?? '-' }}</span>
                            </div>
                            @empty
                            <p class="text-[11px] text-slate-400">Tidak ada item baru.</p>
                            @endforelse
                        </div>
                    </details>
                </div>

                <div class="p-4">
                    <details open>
                        <summary class="cursor-pointer text-xs font-black text-slate-700">Updated ({{ $importPreview['counts']['updated'] ?? 0 }})</summary>
                        <div class="mt-2 max-h-48 overflow-auto space-y-1">
                            @forelse(($importPreview['updated'] ?? []) as $item)
                            <div class="text-[11px] text-slate-600 border-b border-slate-50 pb-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate">{{ $item['shift'] ?? '-' }} &bull; {{ $item['press'] ?? '-' }} &bull; <b>{{ $item['job_no'] ?? '-' }}</b> {{ $item['job_master'] ?? '' }}</span>
                                    <span class="shrink-0 text-amber-600">{{ count($item['changed_fields'] ?? []) }} field</span>
                                </div>
                                <div class="text-[10px] text-slate-400 truncate">Field: {{ implode(', ', $item['changed_fields'] ?? []) ?: '-' }}</div>
                            </div>
                            @empty
                            <p class="text-[11px] text-slate-400">Tidak ada item berubah.</p>
                            @endforelse
                        </div>
                    </details>
                </div>

                <div class="p-4">
                    <details>
                        <summary class="cursor-pointer text-xs font-black text-slate-700">Unchanged ({{ $importPreview['counts']['unchanged'] ?? 0 }})</summary>
                        <div class="mt-2 max-h-48 overflow-auto space-y-1">
                            @forelse(($importPreview['unchanged'] ?? []) as $item)
                            <div class="text-[11px] text-slate-500 flex items-center justify-between gap-2 border-b border-slate-50 pb-1">
                                <span class="truncate">{{ $item['shift'] ?? '-' }} &bull; {{ $item['press'] ?? '-' }} &bull; <b>{{ $item['job_no'] ?? '-' }}</b> {{ $item['job_master'] ?? '' }}</span>
                                <span class="shrink-0 text-slate-400">{{ $item['start_time'] ?? '-' }}–{{ $item['finish_time'] ?? '-' }}</span>
                            </div>
                            @empty
                            <p class="text-[11px] text-slate-400">Tidak ada item identik.</p>
                            @endforelse
                        </div>
                    </details>
                </div>

                <div class="p-4">
                    <details>
                        <summary class="cursor-pointer text-xs font-black text-slate-700">Tidak Tercantum di Revisi ({{ $importPreview['counts']['not_in_revision'] ?? 0 }})</summary>
                        <div class="mt-2 max-h-48 overflow-auto space-y-1">
                            @forelse(($importPreview['not_in_revision'] ?? []) as $item)
                            <div class="text-[11px] text-slate-500 flex items-center justify-between gap-2 border-b border-slate-50 pb-1">
                                <span class="truncate">{{ $item['shift'] ?? '-' }} &bull; {{ $item['press'] ?? '-' }} &bull; <b>{{ $item['job_no'] ?? '-' }}</b> {{ $item['job_master'] ?? '' }}</span>
                                @if($item['has_activity'] ?? false)
                                <span class="shrink-0 px-1.5 py-0.5 rounded bg-rose-50 text-rose-600 text-[10px] font-bold">AKTIVITAS</span>
                                @else
                                <span class="shrink-0 text-slate-400">dipertahankan</span>
                                @endif
                            </div>
                            @empty
                            <p class="text-[11px] text-slate-400">Semua item lama masih tercantum di revisi.</p>
                            @endforelse
                        </div>
                    </details>
                </div>
            </div>
        </div>
    </div>
    @endif
    {{-- Header Section --}}
    <div class="bg-red-700 border-b border-red-800 px-6 py-5 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mx-auto max-w-screen-2xl">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-800 rounded-2xl flex items-center justify-center text-white border border-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m11 0h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-xl font-black text-white tracking-tight">PRODUCTION PLANNING</h1>
                    <p class="text-red-100 text-[10px] font-semibold flex flex-wrap items-center gap-2 mt-0.5">
                        <span class="inline-block w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                        {{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }} &bull; {{ $currentPress }} &bull; 
                        <span class="text-white font-black px-1.5 py-0.5 bg-red-800 rounded">{{ $totalJobs }} ITEMS</span>
                        <span class="mx-1 text-white/30">|</span>
                        <span class="text-emerald-300 font-bold uppercase tracking-tighter">Last Import: {{ $activeFilters['last_import'] }}</span>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button onclick="openImportModal()" class="px-4 py-2 bg-red-800 hover:bg-red-900 text-white rounded-xl text-xs font-black transition-all flex items-center gap-2 border border-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    IMPORT EXCEL
                </button>

                <button onclick="openAddJobModal()" class="px-4 py-2 bg-red-800 hover:bg-red-900 text-white rounded-xl text-xs font-black transition-all flex items-center gap-2 border border-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    TAMBAH MANUAL
                </button>

                <a href="{{ route('ppc.planning.recovery.index') }}" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-black transition-all flex items-center gap-2 shadow-sm border border-amber-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    RECOVERY
                    @if(isset($pendingRecoveryItems) && $pendingRecoveryItems->count() > 0)
                        <span class="px-1.5 py-0.5 text-[10px] font-black bg-white text-amber-700 rounded-full leading-none">{{ $pendingRecoveryItems->count() }}</span>
                    @endif
                </a>

                <a href="{{ route('ppc.planning.backups.index', ['date' => $date]) }}" class="px-4 py-2 bg-red-800 hover:bg-red-900 text-white rounded-xl text-xs font-black transition-all flex items-center gap-2 border border-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    BACKUP &amp; RESTORE
                </a>

                <button type="button" onclick="openDuplicateModal()" class="px-4 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-black transition-all flex items-center gap-2 border border-white/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                    CEK DUPLIKAT
                </button>

                <div class="w-px h-6 bg-white/20"></div>

                <form id="filterForm" class="flex items-center gap-2">
                    <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                           class="bg-white/10 border-white/20 text-white text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-white/30 placeholder-slate-300 transition-all">
                    <select name="status" onchange="this.form.submit()"
                            class="bg-white/10 border-white/20 text-white text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-white/30 transition-all">
                        <option value="" class="text-slate-800">SEMUA STATUS</option>
                        <option value="pending" class="text-slate-800" {{ request('status') === 'pending' ? 'selected' : '' }}>PENDING</option>
                        <option value="approved" class="text-slate-800" {{ request('status') === 'approved' ? 'selected' : '' }}>APPROVED</option>
                    </select>
                </form>
            </div>
        </div>

        {{-- Press & Shift Selector --}}
        <div class="mt-4 flex flex-wrap items-center gap-3 mx-auto max-w-screen-2xl">
            <div class="flex items-center gap-1 bg-white/10 p-1 rounded-xl ring-1 ring-white/20">
                @foreach(['PRESS A', 'PRESS B', 'PRESS C', 'PRESS D', 'ALL'] as $p)
                <a href="{{ route('ppc.planning.production_plan', ['press' => $p, 'date' => $date, 'shift' => $currentShift, 'status' => request('status')]) }}" 
                   class="px-4 py-1.5 rounded-lg text-[11px] font-black transition-all {{ ($currentPress ?? 'PRESS A') === $p ? 'bg-white text-red-700 shadow' : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                    {{ $p }}
                </a>
                @endforeach
            </div>

            <div class="w-px h-6 bg-white/20"></div>

            <div class="flex items-center gap-1 bg-white/10 p-1 rounded-xl ring-1 ring-white/20">
                @php
                    $shiftIsPagi = stripos($currentShift, 'pagi') !== false || stripos($currentShift, '1') !== false;
                    $shiftIsMalam = stripos($currentShift, 'malam') !== false || stripos($currentShift, '2') !== false;
                @endphp
                <a href="{{ route('ppc.planning.production_plan', ['press' => $currentPress, 'date' => $date, 'shift' => 'Pagi', 'status' => request('status')]) }}" 
                   class="px-4 py-1.5 rounded-lg text-[11px] font-black transition-all {{ $shiftIsPagi ? 'bg-white text-red-700 shadow' : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                    SHIFT PAGI
                </a>
                <a href="{{ route('ppc.planning.production_plan', ['press' => $currentPress, 'date' => $date, 'shift' => 'Malam', 'status' => request('status')]) }}" 
                   class="px-4 py-1.5 rounded-lg text-[11px] font-black transition-all {{ $shiftIsMalam ? 'bg-white text-red-700 shadow' : 'text-white/70 hover:text-white hover:bg-white/10' }}">
                    SHIFT MALAM
                </a>
            </div>
        </div>
    </div>

    {{-- Shift Malam + PRESS C Alert --}}
    @php
        $isMalam = str_contains(strtoupper($currentShift ?? ''), 'MALAM') && !str_contains(strtoupper($currentShift ?? ''), 'PAGI');
        $isPressC = strtoupper($currentPress ?? '') === 'PRESS C';
    @endphp
    @if($isMalam && $isPressC)
    <div class="mx-auto max-w-screen-2xl mt-4 flex items-center gap-3 px-5 py-3 bg-rose-50 border border-rose-200 rounded-2xl shadow-sm">
        <div class="w-8 h-8 bg-rose-100 rounded-xl flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
        </div>
        <div>
            <p class="text-sm font-black text-rose-800 uppercase tracking-wide">Informasi</p>
            <p class="text-xs font-semibold text-rose-600">LINE C / PRESS C tidak beroperasi pada <span class="font-black uppercase">{{ $currentShift }}</span>. Data tidak tersedia untuk kombinasi ini.</p>
        </div>
    </div>
    @endif

    {{-- RECOVERY NOTIFICATION BANNER --}}
    @if($pendingRecoveryItems->count())
    <div class="bg-amber-50 border-l-4 border-amber-400 rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div>
                    <span class="font-bold text-amber-800 text-sm">Recovery Pending ({{ $pendingRecoveryItems->count() }} item)</span>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Pilih item yang mau di-recovery. Item terpilih otomatis masuk ke jadwal berikutnya paling atas.
                    </p>
                </div>
            </div>
            <button onclick="showRecoveryModal()"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-black transition-all shadow-lg shadow-amber-200 shrink-0">
                REVIEW
            </button>
        </div>
    </div>
    @endif

    @php
        $shiftEndDefault = config("shift.{$currentShift}.end", '21:00');
        $rawEnd = $pressMeta && $pressMeta->production_end ? substr($pressMeta->production_end, 0, 5) : '';
        $effectiveEnd = $rawEnd ?: $shiftEndDefault;
        $isExtendedShift = $rawEnd && $rawEnd > '21:00';
    @endphp

    {{-- EXTENDED SHIFT BANNER — Press dengan production_end > 21:00 (e.g. PRESS C → 22:00) --}}
    @if($isExtendedShift && str_contains(strtoupper($currentShift ?? ''), 'PAGI'))
    <div class="mx-auto max-w-screen-2xl flex items-center gap-3 px-5 py-3 bg-purple-50 border border-purple-200 rounded-2xl shadow-sm">
        <div class="w-8 h-8 bg-purple-100 rounded-xl flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <div>
            <p class="text-sm font-black text-purple-800 uppercase tracking-wide">EXTENDED SHIFT — {{ strtoupper($currentPress) }}</p>
            <p class="text-xs font-semibold text-purple-700">Produksi diizinkan hingga <span class="font-black">{{ $rawEnd }}</span>.@if($overflowCount > 0) Jika masih overflow, gunakan tombol <strong>OVERRIDE</strong> di banner cut off.@endif</p>
        </div>
    </div>
    @endif

    {{-- SCHEDULER OVERFLOW ALERT — §11 SRS: Detail item cut off --}}
    @if($overflowCount > 0)
    @foreach($overflowByPress as $press => $items)
    <div class="bg-red-50 border-l-4 border-red-400 rounded-xl p-4 shadow-sm" data-press="{{ $press }}">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01" />
                    </svg>
                </div>
                <div>
                    <span class="font-bold text-red-800 text-sm">⚠️ CUT OFF DETECTED — {{ $press }} ({{ $currentShift }})</span>
                    <p class="text-xs text-red-700 mt-0.5">
                        {{ $items->count() }} item tidak selesai — melewati batas shift {{ $effectiveEnd }}.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                @if($isExtendedShift)
                <button onclick="showOverrideModal(this)"
                        class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold transition-all shrink-0 shadow-sm">
                    ⚡ OVERRIDE
                </button>
                @else
                <button onclick="prosesCutOff('{{ $press }}')"
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-all shrink-0 shadow-sm">
                    🔄 PROSES CUT OFF
                </button>
                @endif
                <button onclick="this.closest('[style]')?.remove() ?? this.parentElement.parentElement.remove()"
                        class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-xs font-bold transition-all shrink-0">
                    TUTUP
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-collapse">
                <thead>
                    <tr class="bg-red-100/60 text-red-800">
                        <th class="px-2 py-1.5 text-center w-8 font-bold">
                            <input type="checkbox" onchange="toggleAllOverflow(this, '{{ $press }}')" class="rounded border-red-300">
                        </th>
                        <th class="px-2 py-1.5 text-left font-bold">Job No</th>
                        <th class="px-2 py-1.5 text-left font-bold">Job Master</th>
                        <th class="px-2 py-1.5 text-center font-bold">Qty Plan</th>
                        <th class="px-2 py-1.5 text-center font-bold">Qty OK</th>
                        <th class="px-2 py-1.5 text-center font-bold">Repair</th>
                        <th class="px-2 py-1.5 text-center font-bold">Reject</th>
                        <th class="px-2 py-1.5 text-center font-bold">Balance</th>
                        <th class="px-2 py-1.5 text-center font-bold">Est. Durasi</th>
                        <th class="px-2 py-1.5 text-center font-bold">Posisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-100">
                    @foreach($items as $item)
                    @php
                        $planQty = (float)($item->plan ?? 0);
                        $okQty = (float)($item->ok ?? 0);
                        $repairQty = (float)($item->repair ?? 0);
                        $rejectQty = (float)($item->reject ?? 0);
                        $balance = max(0, $planQty - $okQty - $repairQty - $rejectQty);
                        $recoveryQty = max(0, $planQty - $okQty);
                        $ct = (float)($item->ct_detik ?? 0);
                        $estDurasi = $ct > 0 ? (int)ceil(($ct * $recoveryQty) / 60.0) . ' mnt' : '—';
                    @endphp
                    <tr class="hover:bg-red-50/50">
                        <td class="px-2 py-1.5 text-center">
                            <input type="checkbox" class="overflow-checkbox overflow-checkbox-{{ Str::slug($press) }}"
                                   value="{{ $item->id }}" data-plan-id="{{ $item->id }}">
                        </td>
                        <td class="px-2 py-1.5 text-left font-medium text-red-900">{{ $item->job_no }}</td>
                        <td class="px-2 py-1.5 text-left text-red-800">{{ $item->job_master }}</td>
                        <td class="px-2 py-1.5 text-center text-red-800">{{ number_format($planQty) }}</td>
                        <td class="px-2 py-1.5 text-center text-emerald-700 font-medium">{{ number_format($okQty) }}</td>
                        <td class="px-2 py-1.5 text-center text-yellow-700">{{ number_format($repairQty) }}</td>
                        <td class="px-2 py-1.5 text-center text-rose-700">{{ number_format($rejectQty) }}</td>
                        <td class="px-2 py-1.5 text-center font-bold text-red-700">{{ number_format($balance) }}</td>
                        <td class="px-2 py-1.5 text-center text-red-700">{{ $estDurasi }}</td>
                        <td class="px-2 py-1.5 text-center text-red-700">#{{ $item->row_no }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-2 text-xs text-red-600 font-medium">
            @if($isExtendedShift)
            Tekan <strong>OVERRIDE</strong> untuk memilih tindakan: tetap di timeline (shift diperpanjang) atau pindah ke recovery queue.
            @else
            Total {{ $items->count() }} item cut off.
            <button onclick="prosesCutOff('{{ $press }}')"
                    class="ml-2 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition-all shrink-0 shadow-sm">
                🔄 PROSES CUT OFF
            </button>
            @endif
        </div>
    </div>
    @endforeach
    @endif

    {{-- Main Content Table --}}
    <div class="overflow-x-auto rounded-3xl border border-slate-200 bg-white custom-scrollbar shadow-sm">
        <table id="planTable" class="w-full table-auto border-collapse text-left">
            <thead>
                <tr class="bg-slate-900 whitespace-nowrap">
                    {{-- Group: Drag Handle --}}
                    <th class="px-2 py-4 text-[9px] font-black text-slate-500 uppercase tracking-widest text-center border-r border-slate-700 w-[60px] min-w-[60px]">≣</th>
                    {{-- Group: Identity --}}
                    <th class="px-3 py-4 text-[9px] font-black text-slate-500 uppercase tracking-widest text-center border-r border-slate-700 min-w-[40px] w-[40px]">#</th>
                    <th class="px-4 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700 min-w-[250px]">Job Master</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Type</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Qty/Plt</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Tot. Plt</th>
                    <th class="px-4 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700 min-w-[180px]">Job No.</th>
                    {{-- Group: Output Monitoring --}}
                    <th class="px-3 py-4 text-[9px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Plan</th>
                    <th class="px-3 py-4 text-[9px] font-black text-emerald-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">OK</th>
                    <th class="px-3 py-4 text-[9px] font-black text-yellow-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Repair</th>
                    <th class="px-3 py-4 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Reject</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Balance</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Mesin</th>
                    {{-- Group: Time Metrics --}}
                    <th class="px-3 py-4 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">CT"</th>
                    <th class="px-3 py-4 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Proc.Time</th>
                    <th class="px-3 py-4 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Reg Act</th>
                    <th class="px-3 py-4 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">DCT</th>
                    <th class="px-3 py-4 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">MCT</th>
                    <th class="px-3 py-4 text-[9px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Plan DCT</th>
                    <th class="px-3 py-4 text-[9px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">TPT</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[100px]">GSPH</th>
                    {{-- Group: Schedule --}}
                    <th class="px-3 py-4 text-[9px] font-black text-violet-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Start</th>
                    <th class="px-3 py-4 text-[9px] font-black text-violet-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Finish</th>
                    <th class="px-4 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700 min-w-[250px]">Keterangan</th>
                    {{-- Group: Reject --}}
                    <th class="px-3 py-4 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">A-1</th>
                    <th class="px-3 py-4 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">A-2</th>
                    <th class="px-3 py-4 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">A-3</th>
                    <th class="px-3 py-4 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">A-4</th>
                    <th class="px-3 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">DT</th>
                    <th class="px-4 py-4 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center min-w-[120px]">Status</th>
                </tr>
            </thead>
            <tbody id="planTableBody" class="divide-y divide-slate-100">
                @php
                    $jobNo = 1;
                    $nextPlan = null;
                    $recoveryShown = false;
                    $recoveryDone = false;
                    $hasRegularJobs = $plans->contains(fn($p) => is_null($p->recovery_id) && $p->row_type !== 'break' && $p->row_type !== 'total_finish' && $p->row_type !== 'note');
                @endphp
                @forelse($plans as $plan)
                    @php 
                        $nextPlan = $plans->get($loop->index + 1);
                        $jobMaster = trim($plan->job_master ?? '');
                        $combined = strtoupper(($plan->job_no ?? '') . ' ' . $jobMaster . ' ' . ($plan->keterangan ?? ''));
                        $isRecovery = !is_null($plan->recovery_id);
                        
                        $isBreak = ($plan->row_type === 'break') || 
                                   (is_null($plan->row_no) || $plan->row_no === '' || $plan->row_no === '—') ||
                                   str_contains($combined, 'ISTIRAHAT') || 
                                   str_contains($combined, 'CINGKORAK') || 
                                   str_contains($combined, 'BREAK') ||
                                   str_contains($combined, 'JUMAT') ||
                                   str_contains($combined, 'SORE') ||
                                   str_contains($combined, 'MALAM');

                        // 1. SKIP GHOST ROWS (UI Shield) - but do NOT skip break rows!
                        if (!$isBreak && empty($plan->job_no) && (empty($jobMaster) || in_array($jobMaster, ['—', '0']))) {
                            continue;
                        }
                        // 1b. SKIP NOTE/SUMMARY ROWS (TOTAL STROKE, TARGET GSPH, etc.)
                        if ($plan->row_type === 'note') {
                            continue;
                        }
                        // 1c. SKIP BOUNDARY OVERFLOW — shown only in overflow banner, not in timeline
                        if (!empty($boundaryOverflowIds) && in_array($plan->id, $boundaryOverflowIds)) {
                            continue;
                        }
                    @endphp
                              @if($isRecovery && !$recoveryShown)
                                 @php $recoveryShown = true; @endphp
                                 <tr class="border-l-4 border-l-blue-500 border-y-2 border-blue-300/60 bg-blue-100/30 transition-none">
                                      <td class="px-2 py-3 text-center text-slate-400 border-r border-blue-200/60 w-[60px]">
                                          <span class="drag-handle cursor-grab active:cursor-grabbing text-slate-400 hover:text-slate-600 transition-colors inline-block mr-1.5" title="Geser baris ini">≡</span>
                                          <span class="drag-handle-block cursor-grab active:cursor-grabbing text-blue-500 hover:text-blue-700 transition-colors inline-block" title="Geser seluruh block recovery">⣿</span>
                                      </td>
                                     <td class="px-3 py-3 text-center text-[10px] font-bold text-blue-700/60 bg-blue-100/50 border-r border-blue-200/60">—</td>
                                     <td colspan="19" class="px-6 py-3 bg-blue-100/50 border-r border-blue-200/60">
                                         <div class="flex items-center gap-6">
                                             <div class="flex items-center gap-2">
                                                 <span class="w-3 h-3 rounded-full bg-blue-500 animate-pulse border-2 border-white shadow-sm"></span>
                                                 <span class="text-xs font-black text-blue-900 uppercase tracking-widest">RECOVERY</span>
                                             </div>
                                             <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-white/60 border border-blue-300 shadow-sm">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                 </svg>
                                                 <span class="text-[10px] font-black text-blue-700 uppercase tracking-tighter">BACKUP</span>
                                             </div>
                                         </div>
                                     </td>
                                     <!-- Start -->
                                     <td class="bg-blue-100/50 border-r border-blue-200/60"></td>
                                     <!-- Finish -->
                                     <td class="bg-blue-100/50 border-r border-blue-200/60"></td>
                                     <!-- Keterangan -->
                                     <td class="bg-blue-100/50 border-r border-blue-200/60"></td>
                                     <!-- A1 -->
                                     <td class="bg-blue-100/50 border-r border-blue-200/60"></td>
                                     <!-- A2 -->
                                     <td class="bg-blue-100/50 border-r border-blue-200/60"></td>
                                     <!-- A3 -->
                                     <td class="bg-blue-100/50 border-r border-blue-200/60"></td>
                                     <!-- A4 -->
                                     <td class="bg-blue-100/50 border-r border-blue-200/60"></td>
                                     <!-- DT -->
                                     <td class="bg-blue-100/50 border-r border-blue-200/60"></td>
                                     <!-- Status -->
                                     <td class="bg-blue-100/50"></td>
                                 </tr>
                              @endif
                               @if($hasRegularJobs && $recoveryShown && !$recoveryDone && !$isRecovery && $plan->row_type !== 'break' && !empty($plan->start_time) && !empty($plan->finish_time))
                                 @php
                                     $recoveryDone = true;
                                 @endphp
                                 <tr data-no-drag="true" class="recovery-end border-l-4 border-l-blue-500 border-y-2 border-blue-300/60 bg-blue-100/30 transition-none">
                                     <td colspan="2" class="py-3 text-center text-[10px] font-bold text-blue-700/60 bg-blue-100/50 border-r border-blue-200/60">—</td>
                                     <td colspan="19" class="px-6 py-3 bg-blue-100/50 border-r border-blue-200/60">
                                         <div class="flex items-center gap-6">
                                             <div class="flex items-center gap-2">
                                                 <span class="w-3 h-3 rounded-full bg-blue-500 border-2 border-white shadow-sm"></span>
                                                 <span class="text-xs font-black text-blue-900 uppercase tracking-widest">RECOVERY SELESAI</span>
                                             </div>
                                             <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-white/60 border border-blue-300 shadow-sm">
                                                 <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                 </svg>
                                                 <span class="text-[10px] font-black text-blue-700 uppercase tracking-tighter">BACKUP</span>
                                             </div>
                                         </div>
                                     </td>
                                      <td colspan="9" class="bg-blue-100/50"></td>
                                  </tr>
                                @endif
                              @if(!$isRecovery && empty($plan->start_time) && empty($plan->finish_time))
                                 @continue
                             @endif
                             @if($isBreak && $plan->row_type !== 'total_finish')
                                @php
                                    $breakDesc = 'OPERATIONAL BREAK';
                                    $start = $plan->start_time;
                                    $finish = $plan->finish_time;
                                    $dct = (int) $plan->dct;

                                    if ($dct >= 45) {
                                        $isFriday = str_contains(strtoupper($plan->hari ?? ''), 'JUMAT');
                                        $breakDesc = $isFriday ? 'ISTIRAHAT JUMAT' : 'ISTIRAHAT SIANG';
                                    } elseif ($dct == 15) {
                                        $breakDesc = ($start >= '15:00' && $finish <= '16:00') ? 'BREAKTIME' : 'CINGKORAK';
                                    } elseif ($dct == 30) {
                                        $breakDesc = 'ISTIRAHAT SORE';
                                    } elseif ($dct == 10) {
                                        $breakDesc = 'CLEANING / 5S';
                                    }

                                    if (!empty($plan->job_no) && !in_array($plan->job_no, ['0', '—', '', 'None'])) {
                                        $breakDesc = $plan->job_no;
                                    }

                                    // Helper for solid break row cells
                                    $breakCell = 'bg-amber-50/40 border-r border-amber-100/50';

                                    $showBreak = $nextPlan && !empty(trim($nextPlan->job_no ?? '')) && !in_array($nextPlan->job_no, ['0', '—']);
                                @endphp

                                <tr data-no-drag="true" class="border-l-4 border-l-amber-400 border-y border-amber-200/50 transition-none">
                                    {{-- 0: Drag --}}
                                    <td class="px-2 py-3 text-center text-[10px] font-bold text-amber-600/50 {{ $breakCell }}">—</td>
                                    {{-- 1: No --}}
                                    <td class="px-3 py-3 text-center text-[10px] font-bold text-amber-600/50 {{ $breakCell }}">—</td>

                                    {{-- 2-20: Label Istirahat (19 Columns) --}}
                                    <td colspan="19" class="px-6 py-3 {{ $breakCell }}">
                                        <div class="flex items-center gap-6">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse border-2 border-white shadow-sm"></span>
                                                <span class="text-[10px] font-black text-amber-900 uppercase tracking-widest">{{ $breakDesc }}</span>
                                            </div>

                                            {{-- THE CLEAN BADGE (Simplified) --}}
                                            <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-white/50 border border-amber-200 shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-[10px] font-black text-amber-700 uppercase tracking-tighter">{{ (int)$plan->dct ?: '0' }} MINS</span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 21: Start Time (Amber Pill Style) --}}
                                    <td class="px-2 py-3 text-center bg-amber-50/40 border-r border-amber-100/50">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/40 border border-amber-200/50 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-[10px] font-black text-amber-700 tracking-tight">{{ $plan->start_time ?: '—' }}</span>
                                        </div>
                                    </td>

                                    {{-- 22: Finish Time (Amber Pill Style) --}}
                                    <td class="px-2 py-3 text-center bg-amber-50/40 border-r border-amber-100/50">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/40 border border-amber-200/50 shadow-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <span class="text-[10px] font-black text-amber-700 tracking-tight">{{ $plan->finish_time ?: '—' }}</span>
                                        </div>
                                    </td>

                                    {{-- 23: Keterangan --}}
                                    <td class="bg-amber-50/40 border-r border-amber-100/50"></td>

                                    {{-- 24-27: A1-A4 --}}
                                    <td class="bg-amber-50/40 border-r border-amber-100/50"></td>
                                    <td class="bg-amber-50/40 border-r border-amber-100/50"></td>
                                    <td class="bg-amber-50/40 border-r border-amber-100/50"></td>
                                    <td class="bg-amber-50/40 border-r border-amber-100/50"></td>

                                    {{-- 28: DT (Aligned & Styled) --}}
                                    <td class="px-3 py-3 text-center bg-amber-50/40 border-r border-amber-100/50">
                                        <span class="font-black text-amber-800 text-[11px]">{{ (int)$plan->dct ?: '0' }}</span>
                                    </td>

                                    {{-- 29: Status --}}
                                    <td class="bg-amber-50/40"></td>
                                </tr>
                                @continue
                            @elseif($plan->row_type === 'total_finish')
                                    <td class="px-3 py-3 text-center font-bold text-slate-600 border-r border-slate-100">{{ $plan->dct ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-black text-amber-700 border-r border-slate-100">{{ $plan->tpt ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-bold text-slate-600 border-r border-slate-100">{{ $plan->process_time ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-bold text-slate-600 border-r border-slate-100">{{ $plan->reg_active ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-bold text-slate-600 border-r border-slate-100">{{ $plan->mct ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-bold text-slate-600 border-r border-slate-100">{{ $plan->gsph_item ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center font-mono text-xs font-black text-indigo-700 border-r border-slate-100 bg-indigo-50/30">—</td>
                                    <td class="px-3 py-3 text-center font-mono text-xs font-black text-indigo-700 border-r border-slate-100 bg-indigo-50/30">—</td>
                                    <td colspan="6" class="px-4 py-3 border-r border-slate-100 text-slate-400 text-[10px] text-center italic">{{ $plan->keterangan ?: '—' }}</td>
                                    <td class="px-4 py-3 text-center border-l border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->dt_menit ?: '—' }}</td>
                                </tr>
                              @else
                                 <tr data-id="{{ $plan->id }}" @if(in_array(strtolower($plan->status ?? ''), ['approved'])) data-no-drag="true" @endif @if($isRecovery) data-recovery-member @endif class="hover:bg-blue-50/30 transition-colors border-b border-slate-100 group cursor-default @if($isRecovery) border-l-4 border-l-blue-400 @endif">
                                    {{-- 0: Drag Handle --}}
                                    <td class="px-2 py-3 text-center text-slate-400 border-r border-slate-100 w-[36px]">
                                        <span class="drag-handle cursor-grab active:cursor-grabbing text-slate-300 hover:text-slate-500 transition-colors inline-block" title="Drag to reorder">≡</span>
                                    </td>
                                    {{-- 1: No --}}
                                    <td class="px-3 py-3 text-center text-[11px] font-bold text-slate-400 border-r border-slate-100 min-w-[40px]">
                                        <span class="row-number">{{ $jobNo++ }}</span>
                                    </td>
                                    <td class="px-4 py-3 border-r border-slate-100">
                                        <div class="flex items-center justify-between gap-2 w-full">
                                            <input type="text" value="{{ $plan->job_master }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'job_master', this.value)" class="flex-grow bg-transparent border-none focus:ring-0 text-sm font-black text-slate-800 p-0 min-w-0">
                                            @if(!empty($plan->session_no))
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9px] font-black bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm shrink-0" title="Split Session {{ $plan->session_no }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                    </svg>
                                                    SES {{ chr(64 + (int)$plan->session_no) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100"><span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-indigo-100 text-indigo-700 uppercase tracking-tighter">{{ $plan->type_plt }}</span></td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100"><input type="number" value="{{ $plan->qty_plt }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'qty_plt', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0"></td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-500 text-sm">{{ round($plan->total_plt) }}</td>
                                    <td class="px-4 py-3 border-r border-slate-100"><input type="text" value="{{ $plan->job_no }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'job_no', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-medium text-slate-600 p-0"></td>
                                    {{-- Group: Output Monitoring (Interactive) --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 bg-amber-50/20"><input type="number" value="{{ $plan->plan }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'plan', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-black text-amber-600 text-center p-0"></td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 bg-emerald-50/20">
                                        <span class="text-sm font-black text-emerald-600 tracking-tight">{{ number_format($plan->ok ?? 0) }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 bg-yellow-50/20">
                                        <span class="text-sm font-black text-yellow-600 tracking-tight">{{ number_format($plan->repair ?? 0) }}</span>
                                    </td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 bg-rose-50/20">
                                        <span class="text-sm font-black text-rose-600 tracking-tight">{{ number_format($plan->reject ?? 0) }}</span>
                                    </td>
                                    
                                    {{-- Calculation: Balance (Plan - OK - Reject) --}}
                                    @php 
                                        $balance = ($plan->plan ?? 0) - ($plan->ok ?? 0) - ($plan->reject ?? 0);
                                        $balanceColor = $balance > 0 ? 'text-slate-600' : ($balance < 0 ? 'text-indigo-600' : 'text-slate-300');
                                    @endphp
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-black text-sm {{ $balanceColor }}">{{ number_format($balance) }}</td>

                                    {{-- 9: Mesin --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100"><input type="number" value="{{ $plan->total_mesin }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'total_mesin', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0"></td>
                                    
                                    {{-- 10: CT --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100">
                                        <span class="text-sm font-bold text-slate-700">
                                            {{ number_format((float) $plan->ct_detik, 1, '.', '') }}
                                        </span>
                                    </td>
                                    
                                    {{-- 11: Process Time --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-500 text-sm">{{ (int) ceil((float) $plan->process_time) }}</td>
                                    
                                    {{-- 12: Reg Active --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100"><input type="number" value="{{ $plan->reg_active }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'reg_active', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-sm font-bold text-slate-600 text-center p-0"></td>
                                    
                                    {{-- 13: DCT --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-sm">{{ $plan->dct ?: '0' }}</td>
                                    
                                    {{-- 14: MCT --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-sm">{{ $plan->mct ?: '0' }}</td>
                                    
                                    {{-- 15: Plan DCT --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-amber-600 text-sm bg-amber-50/30">{{ $plan->plan_dct ?: '0' }}</td>
                                    
                                    {{-- 16: TPT --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-black text-amber-700 text-sm">{{ $plan->tpt ?: '0' }}</td>
                                    
                                    {{-- 17: GSPH --}}
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-500 text-sm">{{ number_format($plan->gsph_item) }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-mono text-xs font-bold text-indigo-600">{{ $plan->start_time }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-mono text-xs font-bold text-indigo-600">{{ $plan->finish_time }}</td>
                                    <td class="px-4 py-3 border-r border-slate-100">@if($isRecovery)<span class="inline-flex items-center gap-2"><span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-black bg-teal-100 text-teal-700 uppercase tracking-wider">RECOVERY</span><button onclick="showMoveModal({{ $plan->id }}, '{{ $plan->job_no }}', '{{ $plan->job_master }}')" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors" title="Pindahkan ke shift/hari lain">↗</button></span>@elseif($plan->keterangan)<input type="text" value="{{ $plan->keterangan }}" onchange="PlanningEngine.updateInline({{ $plan->id }}, 'keterangan', this.value)" class="w-full bg-transparent border-none focus:ring-0 text-xs font-medium text-slate-500 p-0">@else<span class="text-slate-300 text-xs">—</span>@endif</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->a1 ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->a2 ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->a3 ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->a4 ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ $plan->dt_menit ?: '—' }}</td>
                                    <td class="px-3 py-3 text-center">
                                        @php
                                            $totalOutput = ($plan->ok ?? 0) + ($plan->repair ?? 0) + ($plan->reject ?? 0);
                                            $planQty = $plan->plan ?? 0;
                                            
                                            if ($totalOutput == 0) {
                                                $statusLabel = 'PENDING';
                                                $statusColor = 'bg-amber-100 text-amber-700 border-amber-200';
                                                $dotColor = 'bg-amber-500';
                                            } elseif ($totalOutput < $planQty) {
                                                $statusLabel = 'RUNNING';
                                                $statusColor = 'bg-blue-100 text-blue-700 border-blue-200';
                                                $dotColor = 'bg-blue-500 animate-pulse';
                                            } else {
                                                $statusLabel = 'DONE';
                                                $statusColor = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                                                $dotColor = 'bg-emerald-500';
                                            }
                                        @endphp
                                        <div class="flex flex-col items-center gap-1.5">
                                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border {{ $statusColor }} shadow-sm">
                                                <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                                <span class="text-[9px] font-black tracking-widest">{{ $statusLabel }}</span>
                                            </div>
                                            @if(!$isRecovery)
                                                <button type="button" onclick="openRowDeleteModal({{ $plan->id }})" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-[9px] font-black uppercase tracking-wide transition-colors" title="Hapus item duplikat">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5h6v2m-8 0h10l-1 13H8L7 7z" />
                                                    </svg>
                                                    Hapus
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                     @empty
                        <tr>
                            <td colspan="27" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <button onclick="openImportModal()" class="mt-6 px-6 py-2.5 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-700 transition-all">
                                        Mulai Import
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse

                        {{-- IN-TABLE TOTAL FINISH ROW --}}
                        @if($totalFinishRow)
                        <tr data-no-drag="true" class="bg-emerald-50 border-t-2 border-emerald-200">
                            {{-- 0: Drag --}}
                            <td class="px-2 py-4 text-center text-emerald-600/50 font-black text-xs">—</td>
                            {{-- 1: No --}}
                            <td class="px-3 py-4 text-center text-emerald-600 font-black">—</td>
                            
                            {{-- 2-6: Label TOTAL FINISH --}}
                            <td colspan="5" class="px-4 py-4 font-black text-emerald-700 text-sm uppercase tracking-wider text-center border-r border-emerald-100">TOTAL FINISH</td>
                            
                            {{-- 7: Total Plan --}}
                            <td class="px-3 py-4 text-center font-black text-emerald-700 border-r border-emerald-100 bg-amber-50/30">{{ number_format($totalFinishRow->plan) }}</td>
                            
                            {{-- 8: Total OK --}}
                            <td class="px-3 py-4 text-center font-black text-emerald-700 border-r border-emerald-100 bg-emerald-50/30">{{ number_format($totalFinishRow->ok) }}</td>

                            {{-- 9: Total Repair --}}
                            <td class="px-3 py-4 text-center font-black text-yellow-700 border-r border-emerald-100 bg-yellow-50/30">{{ number_format($totalFinishRow->repair) }}</td>

                            {{-- 10: Total Reject --}}
                            <td class="px-3 py-4 text-center font-black text-rose-700 border-r border-emerald-100 bg-rose-50/30">{{ number_format($totalFinishRow->reject) }}</td>

                            {{-- 11: Total Balance --}}
                            @php 
                                $totalBalance = ($totalFinishRow->plan ?? 0) - ($totalFinishRow->ok ?? 0);
                            @endphp
                            <td class="px-3 py-4 text-center font-black text-slate-700 border-r border-emerald-100 bg-slate-50">{{ number_format($totalBalance) }}</td>

                            {{-- 12: Total Mesin --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->total_mesin ?: '—' }}</td>

                            {{-- 10: Average CT --}}
                            <td class="px-3 py-4 text-center font-black text-emerald-700 border-r border-emerald-100 text-xs bg-emerald-100/50">{{ number_format((float) $totalFinishRow->ct_detik, 1, '.', '') }}</td>

                            {{-- 11: Total Proc Time --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ (int) ceil((float) $totalFinishRow->process_time) }}</td>
                            
                            {{-- 12: Total Reg Act --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->reg_active ?: '0' }}</td>
                            
                            {{-- 13: Total DCT --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->dct ?: '0' }}</td>
                            
                            {{-- 14: Total MCT --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->mct ?: '0' }}</td>
                            
                            {{-- 15: Total Plan DCT --}}
                            <td class="px-3 py-4 text-center font-black text-emerald-700 border-r border-emerald-100 text-xs bg-emerald-50">{{ number_format($totalFinishRow->plan_dct) }}</td>
                            
                            {{-- 16: Total TPT --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ number_format($totalFinishRow->tpt) }}</td>
                            
                            {{-- 17: Total GSPH --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs bg-emerald-50">{{ number_format($totalFinishRow->gsph_item) }}</td>
                            
                            {{-- 18-19: Start/Finish (kosong untuk total row) --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-[10px]">—</td>
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-[10px]">—</td>
                            
                            {{-- 20: Keterangan --}}
                            <td class="px-4 py-4 border-r border-emerald-100 min-w-[260px]"></td>
                            
                            {{-- 20-23: A1-A4 --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->a1 ?: '0' }}</td>
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->a2 ?: '0' }}</td>
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->a3 ?: '0' }}</td>
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs">{{ $totalFinishRow->a4 ?: '0' }}</td>
                            
                            {{-- 24: DT --}}
                            <td class="px-3 py-4 text-center font-bold text-emerald-700 border-r border-emerald-100 text-xs bg-emerald-50">{{ $totalFinishRow->dt_menit ?: '0' }}</td>
                            
                            {{-- 26: Status --}}
                            <td class="px-3 py-4 text-center">
                                <span class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase">Summary</span>
                            </td>
                        </tr>
                        @endif
                    </tbody>
                </table>
    </div>
        
    {{-- Drag & Drop Hint --}}
    <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50 rounded-b-3xl">
        <div class="flex items-center justify-center gap-2 text-[10px] font-semibold text-slate-400">
            <span class="text-slate-300 text-xs">≡</span>
            <span>Drag & drop items to reorder. Break rows are fixed automatically.</span>
            <span class="mx-2 text-slate-200">|</span>
            <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-400"></span>
            <span>Items with status APPROVED are frozen (running in production).</span>
        </div>
    </div>

            {{-- Summary Cards — Reorganized into PLAN vs ACTUAL with REPAIR --}}
            @if($totalFinishRow)
            @php 
                $totalReject = ($totalFinishRow->a1 ?? 0) + ($totalFinishRow->a2 ?? 0) + ($totalFinishRow->a3 ?? 0) + ($totalFinishRow->a4 ?? 0);
                $totalBalance = ($totalFinishRow->plan ?? 0) - ($totalFinishRow->ok ?? 0);
                $targetGsph = ($totalFinishRow->tpt ?? 0) > 0 ? ($totalFinishRow->plan / ($totalFinishRow->tpt / 60)) : 0;
                $achievement = ($totalFinishRow->plan ?? 0) > 0 ? (($totalFinishRow->ok ?? 0) / $totalFinishRow->plan) * 100 : 0;
                $actualGsph = ($totalFinishRow->tpt ?? 0) > 0 ? (($totalFinishRow->ok ?? 0) / (($totalFinishRow->tpt ?? 0) / 60)) : 0;
                $totalRepair = $totalFinishRow->repair ?? 0;
            @endphp

            <div class="mt-8 px-8 py-10 bg-slate-50/50 border-t border-slate-100">
                <div class="flex flex-col xl:flex-row gap-10 items-start">
                    
                    {{-- SECTION 1: PLAN OVERVIEW --}}
                    <div class="flex-none xl:w-[350px] space-y-5 min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-6 bg-amber-500 rounded-full shadow-[0_0_8px_rgba(245,158,11,0.4)]"></div>
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Plan Overview</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-3">
                            {{-- Total Plan --}}
                            <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group min-w-0">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 group-hover:text-amber-500 transition-colors">Total Plan</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-black text-slate-800">{{ number_format($totalFinishRow->plan) }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Pcs</span>
                                </div>
                            </div>

                            {{-- Total Balance --}}
                            <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group min-w-0">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 group-hover:text-slate-600 transition-colors">Total Balance</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-black text-slate-600">{{ number_format($totalBalance) }}</span>
                                    <span class="text-[10px] font-bold text-slate-400 uppercase">Pcs</span>
                                </div>
                            </div>

                            {{-- Target GSPH --}}
                            <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group border-b-4 border-b-amber-500 min-w-0">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 group-hover:text-amber-600 transition-colors">Target GSPH</p>
                                <div class="flex items-baseline gap-2">
                                    <span class="text-2xl font-black text-amber-600">{{ number_format($targetGsph, 1) }}</span>
                                    <span class="text-[10px] font-bold text-amber-400 uppercase">U/H</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Vertical Separator for Desktop --}}
                    <div class="hidden xl:block w-px bg-slate-200 self-stretch my-2"></div>

                    {{-- SECTION 2: ACTUAL PRODUCTION --}}
                    <div class="flex-1 space-y-6 min-w-0">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-6 bg-emerald-500 rounded-full shadow-[0_0_8px_rgba(16,185,129,0.4)]"></div>
                            <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Actual Production</h3>
                        </div>

                        <div class="flex flex-col gap-4">
                            {{-- Row 1: Small KPIs (OK, REPAIR, REJECT) --}}
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="bg-white px-4 py-3 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group border-b-2 border-b-emerald-500 min-w-0">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5 group-hover:text-emerald-500 transition-colors">Total OK</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xl font-black text-emerald-600">{{ number_format($totalFinishRow->ok) }}</span>
                                        <span class="text-[9px] font-bold text-emerald-400 uppercase">Pcs</span>
                                    </div>
                                </div>

                                <div class="bg-white px-4 py-3 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group border-b-2 border-b-indigo-400 min-w-0">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5 group-hover:text-indigo-500 transition-colors">Total Repair</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xl font-black text-indigo-600">{{ number_format($totalRepair) }}</span>
                                        <span class="text-[9px] font-bold text-indigo-400 uppercase">Pcs</span>
                                    </div>
                                </div>

                                <div class="bg-white px-4 py-3 rounded-2xl border border-slate-200/60 shadow-sm hover:shadow-md transition-all group border-b-2 border-b-rose-500 min-w-0">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5 group-hover:text-rose-500 transition-colors">Total Reject</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-xl font-black text-rose-600">{{ number_format($totalReject) }}</span>
                                        <span class="text-[9px] font-bold text-rose-400 uppercase">Pcs</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Row 2: Hero KPIs (ACHIEVEMENT, GSPH) --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm hover:shadow-md transition-all group border-b-4 border-b-sky-500 min-w-0">
                                    <div class="flex justify-between items-start mb-3">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] group-hover:text-sky-500 transition-colors">Overall Achievement</p>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div class="flex items-baseline gap-2">
                                        @php $achColor = $achievement >= 100 ? 'text-indigo-600' : ($achievement >= 90 ? 'text-emerald-600' : 'text-amber-600'); @endphp
                                        <span class="text-4xl font-black {{ $achColor }} tracking-tight">{{ number_format($achievement, 1) }}%</span>
                                    </div>
                                    <div class="mt-4 w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                                        <div class="h-full {{ str_replace('text', 'bg', $achColor) }} transition-all duration-1000" style="width: {{ min(100, $achievement) }}%"></div>
                                    </div>
                                </div>

                                <div class="bg-slate-900 p-6 rounded-3xl shadow-xl shadow-slate-200 group relative overflow-hidden min-w-0">
                                    <div class="absolute top-0 right-0 p-4 opacity-10">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <p class="text-[10px] font-black text-indigo-300 uppercase tracking-[0.2em] mb-3">Actual GSPH Rate</p>
                                    <div class="flex items-baseline gap-2">
                                        <span class="text-4xl font-black text-white tracking-tight">{{ number_format($actualGsph) }}</span>
                                        <span class="text-xs font-bold text-indigo-400 uppercase">Unit/Hour</span>
                                    </div>
                                    <p class="text-[9px] font-bold text-indigo-300/50 mt-4 italic">Real-time production throughput</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

</div>

@push('modals')
{{-- MODAL AREA (Teleported outside all wrappers to prevent layout shifting) --}}
<div id="importModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeImportModal()"></div>
    <div class="relative w-full max-w-md p-6 animate-in fade-in zoom-in duration-300">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="px-8 py-8">
                <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center mb-6 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 00-4-4H5m11 0h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-800">IMPORT JADWAL</h3>
                <p class="text-slate-500 text-sm mt-1">Upload file Excel (.xlsx, .xlsm) untuk sinkronisasi jadwal produksi.</p>

                <form id="importForm" action="{{ route('ppc.planning.production_plan.import') }}" method="POST" enctype="multipart/form-data" class="mt-8" onsubmit="return handleImportSubmit(event)">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Pilih File Excel</label>
                            <input type="file" name="excel_file" required accept=".xlsx,.xls,.xlsm"
                                   class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-indigo-50 file:text-indigo-600 hover:file:bg-indigo-100 transition-all border border-slate-200 rounded-xl p-1">
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Filter Shift</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="shift_filter" value="all" checked
                                           class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                    <span class="text-sm font-bold text-slate-600">Semua Shift</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="shift_filter" value="pagi"
                                           class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                    <span class="text-sm font-bold text-slate-600">Shift Pagi</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="shift_filter" value="malam"
                                           class="w-4 h-4 text-indigo-600 border-slate-300 focus:ring-indigo-500">
                                    <span class="text-sm font-bold text-slate-600">Shift Malam</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Tanggal Rencana</label>
                            <input type="date" name="date" value="{{ date('Y-m-d') }}"
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-indigo-500 transition-all">
                        </div>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button type="button" onclick="closeImportModal()" class="flex-1 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">
                            BATAL
                        </button>
                        <button type="submit" class="flex-1 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-black shadow-lg shadow-indigo-100 transition-all">
                            IMPORT
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush

{{-- ADD JOB MODAL --}}
@push('modals')
<div id="addJobModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeAddJobModal()"></div>
    <div class="relative w-full max-w-md mx-4 animate-in fade-in zoom-in duration-300">
        <div class="bg-white rounded-3xl shadow-2xl">
            <div class="px-8 py-8 max-h-[80vh] overflow-y-auto">
                <div class="w-16 h-16 bg-emerald-50 rounded-2xl flex items-center justify-center mb-6 text-emerald-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-800">TAMBAH JOB MANUAL</h3>
                <p class="text-slate-500 text-sm mt-1">Tambah job baru ke antrian produksi secara manual.</p>

                <form id="addJobForm" action="{{ route('ppc.planning.production_plan.add_job') }}" method="POST" class="mt-8" onsubmit="return handleAddJobSubmit(event)">
                    @csrf
                    <input type="hidden" name="plan_date"  value="{{ $date }}">
                    <input type="hidden" name="shift_name" value="{{ $currentShift }}">
                    <input type="hidden" name="press_name" value="{{ $currentPress }}">

                    <div class="space-y-4">
                        <div class="relative">
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Job No. <span class="text-red-500">*</span></label>
                            <input type="text" name="job_no" id="addJobNoInput" required autocomplete="off" placeholder="Ketik atau cari Job No dari master..."
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 transition-all">
                            <div id="addJobSuggestions" class="hidden absolute top-full left-0 w-full bg-white border border-slate-200 rounded-xl shadow-lg mt-1 max-h-48 overflow-y-auto z-50"></div>
                            <div id="addJobMasterPreview" class="hidden mt-2 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-semibold leading-relaxed"></div>
                            <input type="hidden" name="job_master" id="addJobMasterVal">
                            <input type="hidden" name="type_plt"   id="addJobTypePltVal">
                            <input type="hidden" name="qty_plt"    id="addJobQtyPltVal">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Plan (pcs)</label>
                                <input type="number" name="plan" id="addJobPlan" placeholder="0" min="0"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">CT (detik)</label>
                                <input type="number" name="ct_detik" id="addJobCt" placeholder="0" min="0" step="0.1"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">DCT (menit)</label>
                                <input type="number" name="dct" id="addJobDct" placeholder="0" min="0"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Reg. Active (menit)</label>
                                <input type="number" name="reg_active" id="addJobRegActive" placeholder="0" min="0"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 transition-all">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Pilihan Mesin</label>
                            <div class="flex gap-4">
                                @foreach([1,2,3,4] as $m)
                                <label class="flex items-center gap-2 px-3 py-2 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-all has-[:checked]:bg-emerald-50 has-[:checked]:border-emerald-300">
                                    <input type="checkbox" name="machines[]" value="{{ $m }}" checked class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                                    <span class="text-xs font-bold text-slate-600">P{{ $m }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Keterangan</label>
                            <input type="text" name="keterangan" placeholder="Opsional..."
                                class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-emerald-500 transition-all">
                        </div>

                        <div class="bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3 text-xs font-semibold text-emerald-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline mr-1 mb-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Job akan ditempatkan setelah job terakhir pada jadwal ini.
                        </div>
                    </div>

                    <div class="flex gap-3 mt-8">
                        <button type="button" onclick="closeAddJobModal()" class="flex-1 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">
                            BATAL
                        </button>
                        <button type="submit" class="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-black shadow-lg shadow-emerald-100 transition-all">
                            TAMBAH JOB
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush

@push('modals')
{{-- DUPLICATE DETECTION MODAL --}}
<div id="duplicateModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeDuplicateModal()"></div>
    <div class="relative w-full max-w-6xl mx-4 animate-in fade-in zoom-in duration-300">
        <div class="bg-white rounded-3xl shadow-2xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-4 shrink-0">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4M16 17H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-lg font-black text-slate-800 tracking-tight">CEK ITEM DUPLIKAT</h3>
                            <p class="text-[11px] text-slate-500">Hanya item dengan slot jadwal identik (tanggal, shift, press, job, jam mulai) yang ditampilkan. Tidak ada penghapusan otomatis.</p>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="closeDuplicateModal()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 font-black transition-all shrink-0">&times;</button>
            </div>

            <div class="px-6 py-3 bg-slate-50 border-b border-slate-100 flex flex-wrap items-center gap-4 shrink-0">
                <label class="flex items-center gap-2 text-[11px] font-bold text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" id="dupAllPress" class="w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    SEMUA PRESS
                </label>
                <label class="flex items-center gap-2 text-[11px] font-bold text-slate-600 cursor-pointer select-none">
                    <input type="checkbox" id="dupAllShift" class="w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                    SEMUA SHIFT
                </label>
                <button type="button" onclick="clearDuplicateResult(); loadDuplicateScan()" class="ml-auto px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-[11px] font-black transition-all">
                    PINDAI ULANG
                </button>
            </div>

            <div id="duplicateResult" role="status" aria-live="polite" class="hidden"></div>

            <div id="duplicateLoading" class="p-12 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">
                Memindai jadwal...
            </div>

            <div id="duplicateEmpty" class="hidden p-12 text-center">
                <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <p class="text-sm font-black text-slate-700">Tidak ada item duplikat</p>
                <p class="text-xs text-slate-400 mt-1">Tidak ditemukan dua item dengan slot jadwal yang identik pada filter ini.</p>
            </div>

            <div id="duplicateContent" class="hidden flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/50"></div>

            <div class="px-6 py-4 border-t border-slate-100 bg-white flex items-center justify-between gap-4 shrink-0">
                <p class="text-[10px] text-slate-400 font-semibold leading-relaxed">
                    Item dengan aktivitas produksi, status selain pending, atau relasi Recovery/LKH tidak dapat dihapus. Penghapusan selalu dicatat pada audit trail.
                </p>
                <button type="button" onclick="closeDuplicateModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-black transition-all shrink-0">
                    TUTUP
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ROW DELETE MODAL --}}
<div id="rowDeleteModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeRowDeleteModal()"></div>
    <div class="relative w-full max-w-2xl mx-4 animate-in fade-in zoom-in duration-300">
        <div class="bg-white rounded-3xl shadow-2xl max-h-[90vh] flex flex-col overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-start justify-between gap-4 shrink-0">
                <div class="flex items-center gap-2">
                    <span class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5h6v2m-8 0h10l-1 13H8L7 7z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-lg font-black text-slate-800 tracking-tight">HAPUS ITEM PRODUCTION PLAN</h3>
                        <p class="text-[11px] text-slate-500">Hanya satu record yang dipilih yang akan dihapus. Pasangan duplikat tetap disimpan.</p>
                    </div>
                </div>
                <button type="button" onclick="closeRowDeleteModal()" class="w-9 h-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 font-black transition-all shrink-0">&times;</button>
            </div>

            <div id="rowDeleteLoading" class="p-12 text-center text-slate-400 text-xs font-bold uppercase tracking-widest">
                Memeriksa item...
            </div>

            <div id="rowDeleteBody" class="hidden flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/50">
                <div id="rowDeleteWarning" class="hidden p-3 rounded-xl border text-[11px] font-semibold leading-relaxed"></div>
                <div id="rowDeleteSummary" class="grid grid-cols-2 md:grid-cols-4 gap-2"></div>
                <div id="rowDeletePeer"></div>
                <div id="rowDeleteBlockers" class="hidden"></div>
                <div id="rowDeleteForm" class="hidden space-y-3">
                    <div>
                        <label class="block text-[10px] font-black text-slate-600 uppercase">Alasan penghapusan <span class="text-rose-600">*</span></label>
                        <textarea id="rowDeleteReason" rows="2" maxlength="500" placeholder="Contoh: hasil import ganda pada revisi PPC" class="mt-1 w-full rounded-xl border-slate-300 text-xs focus:ring-rose-500 focus:border-rose-500"></textarea>
                    </div>
                    <label class="flex items-start gap-2 text-[10px] font-bold text-slate-600 cursor-pointer select-none">
                        <input type="checkbox" id="rowDeleteIdentity" class="mt-0.5 w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                        <span>Saya sudah memastikan record yang dihapus BUKAN satu-satunya jadwal job tersebut dan pasangan duplikat tetap disimpan.</span>
                    </label>
                    <label id="rowDeleteReviewWrap" class="hidden flex items-start gap-2 text-[10px] font-bold text-amber-700 cursor-pointer select-none">
                        <input type="checkbox" id="rowDeleteReview" class="mt-0.5 w-4 h-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                        <span>Saya sudah memeriksa perbedaan detail jadwal (finish time / split) dan tetap yakin ini duplikat.</span>
                    </label>
                </div>
                <div id="rowDeleteError" class="hidden p-3 bg-rose-50 border border-rose-200 rounded-xl text-[11px] font-semibold text-rose-700"></div>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-white flex flex-wrap items-center justify-end gap-2 shrink-0">
                <button type="button" onclick="closeRowDeleteModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-black transition-all">
                    TUTUP
                </button>
                <button type="button" id="rowDeleteCheckDuplicate" onclick="closeRowDeleteModal(); openDuplicateModal()" class="hidden px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-black transition-all">
                    BUKA CEK DUPLIKAT
                </button>
                <button type="button" id="rowDeleteSubmit" onclick="submitRowDelete()" disabled class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white rounded-xl text-xs font-black transition-all">
                    HAPUS RECORD
                </button>
            </div>
        </div>
    </div>
</div>
@endpush

<style>
    .custom-scrollbar::-webkit-scrollbar { height: 8px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    /* Status Picker Dropdown */
    .status-dropdown { animation: statusFadeIn 0.15s ease; transform-origin: top right; }
    @keyframes statusFadeIn {
        from { opacity: 0; transform: scale(0.92) translateY(-4px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* SortableJS Drag & Drop Styles */
    #planTableBody .sortable-ghost {
        opacity: 0.3;
        background: #dbeafe !important;
        outline: 2px dashed #3b82f6 !important;
        outline-offset: -2px;
        border-top: 3px solid #2563eb !important;
    }
    #planTableBody .sortable-drag {
        background: #ffffff !important;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06) !important;
        opacity: 0.95 !important;
        transform: scale(1.02);
        z-index: 9999 !important;
    }

    .drag-handle,
    .drag-handle-block {
        font-size: 14px;
        line-height: 1;
        user-select: none;
        -webkit-user-select: none;
        display: inline-block;
        vertical-align: middle;
    }

    #planTableBody tr[data-no-drag] .drag-handle {
        display: none;
    }

    #planTableBody tr[data-no-drag] {
        opacity: 0.85;
        border-left: 3px solid #f59e0b !important;
    }

    /* Drag column hover cursor */
    #planTableBody tr:not([data-no-drag]) td:first-child {
        cursor: grab;
    }
    #planTableBody tr:not([data-no-drag]) td:first-child:active {
        cursor: grabbing;
    }

</style>

<script>
function openImportModal() {
    if (typeof PlanningEngine !== 'undefined' && PlanningEngine.openImportModal) {
        PlanningEngine.openImportModal();
    } else {
        var modal = document.getElementById('importModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }
}
function openAddJobModal() {
    if (typeof PlanningEngine !== 'undefined' && PlanningEngine.openAddJobModal) {
        PlanningEngine.openAddJobModal();
    } else {
        var modal = document.getElementById('addJobModal');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            var input = document.getElementById('addJobNoInput');
            if (input) { input.value = ''; input.focus(); }
        }
    }
}
function closeImportModal() {
    if (typeof PlanningEngine !== 'undefined' && PlanningEngine.closeImportModal) {
        PlanningEngine.closeImportModal();
    } else {
        var modal = document.getElementById('importModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
}
function closeAddJobModal() {
    if (typeof PlanningEngine !== 'undefined' && PlanningEngine.closeAddJobModal) {
        PlanningEngine.closeAddJobModal();
    } else {
        var modal = document.getElementById('addJobModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
}
function handleAddJobSubmit(e) {
    if (typeof PlanningEngine !== 'undefined' && PlanningEngine.setupAddJobAutocomplete) {
        return true;
    }
    var btn = e.target.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    return true;
}
function handleImportSubmit(e) {
    var btn = e.target.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'IMPORTING...'; }
    return true;
}
window.StatusPicker = {
    toggle: function(btn) {
        const picker = btn.closest('.status-picker');
        const dropdown = picker.querySelector('.status-dropdown');
        const isOpen = !dropdown.classList.contains('hidden');
        // Close all other open dropdowns first
        document.querySelectorAll('.status-dropdown').forEach(d => d.classList.add('hidden'));
        if (!isOpen) dropdown.classList.remove('hidden');
    },

    select: function(btn, status, bgColor, textColor, dotColor) {
        const picker = btn.closest('.status-picker');
        const planId = picker.dataset.planId;
        const badge = picker.querySelector('.status-badge');
        const dot = badge.querySelector('span.rounded-full');
        const label = badge.childNodes[2]; // text node between dot and chevron

        // Update badge style
        badge.style.backgroundColor = bgColor;
        badge.style.color = textColor;
        badge.style.ringColor = dotColor;
        dot.style.backgroundColor = dotColor;

        // Update label text
        const labels = { pending: 'Pending', approved: 'Approved', completed: 'Done' };
        badge.querySelector('span.rounded-full').nextSibling.textContent = ' ' + labels[status] + ' ';

        // Close dropdown
        picker.querySelector('.status-dropdown').classList.add('hidden');
        picker.dataset.current = status;

        // Save via API
        PlanningEngine.updateInline(planId, 'status', status);
    }
};

// Close on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.status-picker')) {
        document.querySelectorAll('.status-dropdown').forEach(d => d.classList.add('hidden'));
    }
});
</script>

@push('scripts')
@vite(['resources/js/ppc/planning.js'])
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof PlanningEngine !== 'undefined' && PlanningEngine.init) {
            PlanningEngine.init({
                csrfToken: '{{ csrf_token() }}',
                inlineUrl: '{{ route('ppc.planning.production_plan.inline') }}',
                indexUrl: '{{ route('ppc.planning.production_plan') }}',
                currentPress: '{{ $currentPress }}'
            });
        }
    });
</script>
@endpush

{{-- RECOVERY APPROVAL MODAL (Per-Item Checkboxes) --}}
<div id="recoveryModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeRecoveryModal()"></div>
    <div class="relative w-full max-w-4xl mx-4 animate-in fade-in zoom-in duration-300">
        <div class="bg-white rounded-3xl shadow-2xl max-h-[85vh] flex flex-col">
            <div class="px-8 py-8 overflow-y-auto">
                <div class="flex flex-col items-center text-center">
                    <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mb-4 text-amber-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                    <h3 class="text-xl font-black text-slate-800">RECOVERY APPROVAL</h3>
                    <p class="text-slate-500 text-sm mt-1">Item yang belum selesai &mdash; centang yang mau di-recovery ke jadwal berikutnya.</p>
                </div>

                <div class="mt-6">
                    @if($pendingRecoveryItems->count())
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="checklistAll" onchange="toggleAllCheckboxes()"
                                   class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500 cursor-pointer">
                            <label for="checklistAll" class="text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer select-none">CHECKLIST ALL</label>
                        </div>
                        <span class="text-xs text-slate-400">{{ $pendingRecoveryItems->count() }} item pending</span>
                    </div>

                    <div class="border border-slate-200 rounded-xl overflow-hidden">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider">
                                    <th class="text-center py-2.5 px-2 w-10">#</th>
                                    <th class="text-left py-2.5 px-3">Job No</th>
                                    <th class="text-left py-2.5 px-3">Job Master</th>
                                    <th class="text-left py-2.5 px-3">Press</th>
                                    <th class="text-left py-2.5 px-3">Tgl Asal</th>
                                    <th class="text-left py-2.5 px-3">Shift</th>
                                    <th class="text-right py-2.5 px-3">Plan</th>
                                    <th class="text-right py-2.5 px-3">OK</th>
                                    <th class="text-right py-2.5 px-3">Sisa</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($pendingRecoveryItems as $item)
                                <tr class="hover:bg-amber-50/30 transition-colors">
                                    <td class="text-center py-2.5 px-2">
                                        <input type="checkbox" name="recovery_item" value="{{ $item->id }}"
                                               class="w-4 h-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500 cursor-pointer item-checkbox">
                                    </td>
                                    <td class="py-2.5 px-3 font-semibold text-slate-700">{{ $item->job_no }}</td>
                                    <td class="py-2.5 px-3 text-slate-500">{{ $item->job_master }}</td>
                                    <td class="py-2.5 px-3 font-medium text-slate-600">{{ $item->press_name }}</td>
                                    <td class="py-2.5 px-3 text-slate-500">{{ $item->original_date ? \Carbon\Carbon::parse($item->original_date)->format('d M Y') : ($item->schedule?->plan_date?->format('d M Y') ?? '-') }}</td>
                                    <td class="py-2.5 px-3 text-slate-500">{{ $item->original_shift_name ?? ($item->schedule?->shift_name ?? '-') }}</td>
                                    <td class="py-2.5 px-3 text-right font-semibold text-slate-700">{{ number_format($item->plan_qty + $item->ok) }}</td>
                                    <td class="py-2.5 px-3 text-right text-emerald-600 font-semibold">{{ number_format($item->ok) }}</td>
                                    <td class="py-2.5 px-3 text-right text-rose-600 font-bold">{{ number_format($item->plan_qty) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex gap-3 mt-6">
                        <button type="button" onclick="approveSelectedItems()"
                                class="flex-1 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-black transition-all shadow-lg shadow-emerald-200">
                            APPROVE SELECTED
                        </button>
                        <button type="button" onclick="closeRecoveryModal()"
                                class="flex-1 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">
                            TUTUP
                        </button>
                    </div>
                    @else
                    <div class="text-center py-8 text-slate-400 text-sm font-semibold">Tidak ada recovery pending.</div>
                    <div class="flex mt-6">
                        <button type="button" onclick="closeRecoveryModal()"
                                class="flex-1 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-sm font-black transition-all">
                            TUTUP
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MOVE ITEM MODAL (Cross-shift/date) --}}
<div id="moveModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeMoveModal()"></div>
    <div class="relative w-full max-w-md mx-4 animate-in fade-in zoom-in duration-300">
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <div class="flex flex-col items-center text-center">
                <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center mb-4 text-indigo-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-800">PINDAHKAN ITEM</h3>
                <p class="text-slate-500 text-sm mt-1" id="moveItemLabel">—</p>
            </div>

            <form id="moveForm" class="mt-6 space-y-4" onsubmit="submitMove(event)">
                
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Target Shift</label>
                    <select id="moveTargetShift" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        <option value="Shift Pagi">Shift Pagi</option>
                        <option value="Shift Malam">Shift Malam</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Target Tanggal</label>
                    <input type="date" id="moveTargetDate" value="{{ $date }}" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 ml-1">Target Press</label>
                    <select id="moveTargetPress" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        @foreach(['PRESS A', 'PRESS B', 'PRESS C', 'PRESS D'] as $p)
                        <option value="{{ $p }}" @if($p === $currentPress) selected @endif>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="closeMoveModal()" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">BATAL</button>
                    <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold transition-all shadow-lg shadow-indigo-200">PINDAHKAN</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- CONFIRM MODAL (Tailwind, gantikan confirm() native) --}}
<div id="confirmModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeConfirm()"></div>
    <div class="relative w-full max-w-sm mx-4 animate-in fade-in zoom-in duration-300">
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <div class="flex flex-col items-center text-center">
                <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center mb-4 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-800">KONFIRMASI</h3>
                <p class="text-slate-500 text-sm mt-2" id="confirmMessage">—</p>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="closeConfirm()" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">BATAL</button>
                <button type="button" onclick="executeConfirm()" class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold transition-all shadow-lg shadow-emerald-200">LANJUTKAN</button>
            </div>
        </div>
    </div>
</div>

{{-- OVERRIDE MODAL — Extended shift: Tetap di Timeline / Masukin ke Recovery --}}
<div id="overrideModal" class="fixed inset-0 z-[99999] hidden flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" onclick="closeOverrideModal()"></div>
    <div class="relative w-full max-w-lg mx-4 animate-in fade-in zoom-in duration-300">
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
            <div class="px-8 py-8">
                <div class="w-16 h-16 bg-amber-50 rounded-2xl flex items-center justify-center mb-6 text-amber-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01" />
                    </svg>
                </div>
                <h3 class="text-xl font-black text-slate-800">Override — Atur Overflow</h3>
                <p class="text-slate-500 text-sm mt-1"><span id="overrideCount">{{ $overflowCount }}</span> item dipilih di <strong class="text-slate-700">{{ strtoupper($currentPress) }}</strong> {{ $currentShift }}. Pilih tindakan:</p>

                <div class="mt-6 space-y-3">
                    <button onclick="forceTimeline()" class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-emerald-200 hover:border-emerald-400 bg-emerald-50 hover:bg-emerald-100 transition-all text-left group">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 group-hover:scale-105 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-emerald-800">Tetap di Timeline</p>
                            <p class="text-xs text-emerald-600 mt-0.5">Jadwal diperpanjang — item di-schedule ulang mengikuti timeline yang diperluas.</p>
                        </div>
                    </button>

                    <button onclick="toRecovery()" class="w-full flex items-center gap-4 p-4 rounded-2xl border-2 border-amber-200 hover:border-amber-400 bg-amber-50 hover:bg-amber-100 transition-all text-left group">
                        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 group-hover:scale-105 transition-transform">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-black text-amber-800">Masukin ke Recovery</p>
                            <p class="text-xs text-amber-600 mt-0.5">Item dipindah ke recovery queue — tidak di-schedule di timeline utama.</p>
                        </div>
                    </button>

                    <button onclick="closeOverrideModal()" class="w-full p-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-500 text-sm font-bold transition-all">
                        BATAL
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- TOAST NOTIFICATION (Tailwind, gantikan alert() native) --}}
<div id="toast" class="fixed top-6 right-6 z-[99999] hidden items-center gap-3 transition-all duration-500">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 px-6 py-4 flex items-center gap-3">
        <span id="toastIcon" class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
        </span>
        <span id="toastMessage" class="text-sm font-semibold text-slate-700">—</span>
    </div>
</div>

@endsection
@push('scripts')
<script>
    let movePlanIds = [];

    function showMoveModal(planId, jobNo, jobMaster) {
        movePlanIds = [planId];
        document.getElementById('moveItemLabel').textContent = jobNo + ' — ' + jobMaster;
        document.getElementById('moveModal').classList.remove('hidden');
        document.getElementById('moveModal').classList.add('flex');
    }

    function closeMoveModal() {
        document.getElementById('moveModal').classList.remove('flex');
        document.getElementById('moveModal').classList.add('hidden');
        movePlanIds = [];
    }

    function submitMove(event) {
        event.preventDefault();

        if (movePlanIds.length === 0) {
            showToast('Tidak ada item dipilih.', 'error');
            return;
        }

        const targetShift = document.getElementById('moveTargetShift').value;
        const targetDate = document.getElementById('moveTargetDate').value;
        const targetPress = document.getElementById('moveTargetPress').value;
        const count = movePlanIds.length;
        const label = count === 1 ? 'item ini' : count + ' item';

        showConfirm('Pindahkan ' + label + ' ke ' + targetShift + ', ' + targetDate + ', ' + targetPress + '?', function() {
            fetch('{{ route("ppc.planning.production_plan.move") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    plan_ids: movePlanIds,
                    target_date: targetDate,
                    target_shift: targetShift,
                    target_press: targetPress
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showToast(data.message || 'Gagal memindahkan item.', 'error');
                }
            })
            .catch(() => showToast('Error saat memindahkan item.', 'error'));
        });
    }

    function toggleAllOverflow(checkbox, press) {
        const slug = press.toLowerCase().replace(/\s+/g, '-');
        document.querySelectorAll('.overflow-checkbox-' + slug).forEach(cb => cb.checked = checkbox.checked);
    }

    function showOverrideModal(btn) {
        const section = btn.closest('[data-press]');
        const checked = section.querySelectorAll('.overflow-checkbox:checked');
        const ids = Array.from(checked).map(cb => parseInt(cb.value));
        if (ids.length === 0) {
            showToast('Pilih item yang ingin di-override.', 'error');
            return;
        }
        window._overridePlanIds = ids;
        document.getElementById('overrideCount').textContent = ids.length;
        document.getElementById('overrideModal').classList.remove('hidden');
        document.getElementById('overrideModal').classList.add('flex');
    }

    function closeOverrideModal() {
        document.getElementById('overrideModal').classList.remove('flex');
        document.getElementById('overrideModal').classList.add('hidden');
    }

    function forceTimeline() {
        closeOverrideModal();
        const ids = window._overridePlanIds || [];
        if (ids.length === 0) { showToast('Tidak ada item dipilih.', 'error'); return; }
        showConfirm('Tetap di timeline untuk ' + ids.length + ' item? Item akan di-schedule ulang melewati batas shift.', function() {
            fetch('{{ route("ppc.planning.production_plan.force_overflow") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ plan_ids: ids, action: 'force_timeline' })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { location.reload(); }
                else { showToast(data.message || 'Gagal.', 'error'); }
            })
            .catch(() => showToast('Error saat force timeline.', 'error'));
        });
    }

    function toRecovery() {
        closeOverrideModal();
        const ids = window._overridePlanIds || [];
        if (ids.length === 0) { showToast('Tidak ada item dipilih.', 'error'); return; }
        showConfirm('Pindahkan ' + ids.length + ' item ke recovery queue? Item akan dihapus dari timeline utama.', function() {
            fetch('{{ route("ppc.planning.production_plan.force_overflow") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ plan_ids: ids, action: 'to_recovery' })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { location.reload(); }
                else { showToast(data.message || 'Gagal.', 'error'); }
            })
            .catch(() => showToast('Error saat pindah ke recovery.', 'error'));
        });
    }

    function showRecoveryModal() {
        document.getElementById('recoveryModal').classList.remove('hidden');
        document.getElementById('recoveryModal').classList.add('flex');
    }

    function closeRecoveryModal() {
        document.getElementById('recoveryModal').classList.remove('flex');
        document.getElementById('recoveryModal').classList.add('hidden');
    }

    function prosesCutOff(press) {
        const shift = '{{ $currentShift }}';
        if (!confirm(`Proses cut off untuk ${press} ${shift}? Item yang belum selesai akan masuk recovery queue.`)) return;
        fetch('{{ route("ppc.planning.recovery.run_cutoff") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
            body: JSON.stringify({ date: '{{ $date }}', shift: shift })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('Cut-off selesai!', 'success');
                setTimeout(() => { window.location.href = '{{ route("ppc.planning.recovery.index") }}'; }, 800);
            } else {
                showToast(data.message || 'Gagal.', 'error');
            }
        })
        .catch(() => showToast('Error saat proses cut off.', 'error'));
    }

    function toggleAllCheckboxes() {
        const checked = document.getElementById('checklistAll').checked;
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = checked);
    }

    function approveSelectedItems() {
        const checked = document.querySelectorAll('.item-checkbox:checked');
        if (checked.length === 0) {
            showToast('Pilih minimal satu item yang akan di-approve.', 'warning');
            return;
        }

        showConfirm('Approve ' + checked.length + ' item recovery yang dipilih?', function() {
            const itemIds = Array.from(checked).map(cb => parseInt(cb.value));

            const targetDate = document.querySelector('input[name="plan_date"]').value;
            const targetShift = document.querySelector('input[name="shift_name"]').value;
            const targetPress = document.querySelector('input[name="press_name"]').value;

            fetch('{{ route("ppc.planning.recovery.approve_items") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    item_ids: itemIds,
                    target_date: targetDate,
                    target_shift: targetShift,
                    target_press: targetPress
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showToast(data.message || 'Gagal approve recovery.', 'error');
                }
            })
            .catch(() => showToast('Error saat approve recovery.', 'error'));
        });
    }

    // Confirm Modal helpers
    let confirmCallback = null;

    function showConfirm(message, onConfirm) {
        document.getElementById('confirmMessage').textContent = message;
        confirmCallback = onConfirm;
        document.getElementById('confirmModal').classList.remove('hidden');
        document.getElementById('confirmModal').classList.add('flex');
    }

    function closeConfirm() {
        document.getElementById('confirmModal').classList.remove('flex');
        document.getElementById('confirmModal').classList.add('hidden');
        confirmCallback = null;
    }

    function executeConfirm() {
        const cb = confirmCallback;
        closeConfirm();
        if (cb) cb();
    }

    // Toast notification helpers
    function showToast(message, type) {
        const el = document.getElementById('toast');
        const msgEl = document.getElementById('toastMessage');
        const iconEl = document.getElementById('toastIcon');
        msgEl.textContent = message;

        const icons = {
            success: { bg: 'bg-emerald-100', text: 'text-emerald-600', path: 'M5 13l4 4L19 7' },
            error: { bg: 'bg-rose-100', text: 'text-rose-600', path: 'M6 18L18 6M6 6l12 12' },
            warning: { bg: 'bg-amber-100', text: 'text-amber-600', path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z' },
        };
        const cfg = icons[type] || icons.success;
        iconEl.className = 'w-6 h-6 rounded-full ' + cfg.bg + ' ' + cfg.text + ' flex items-center justify-center shrink-0';
        iconEl.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + cfg.path + '" /></svg>';

        el.classList.remove('hidden');
        el.classList.add('flex');
        setTimeout(function() {
            el.classList.remove('flex');
            el.classList.add('hidden');
        }, 4000);
    }

    // 1. Auto Refresh setiap 5 menit (300.000 ms)
    setInterval(() => {
        console.log('Syncing data with server...');
        if (!$('#importModal').is(':visible') && !$('#duplicateModal').is(':visible')) {
            location.reload();
        }
    }, 300000);

    // 2. Planning Engine Initialization
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Production Planning Dashboard Active | Date: {{ $activeFilters['date'] }}');
        
        @if(request('open_recovery') || ($pendingRecoveryItems->count() > 0 && !request('date')))
            setTimeout(function() {
                if (typeof showRecoveryModal === 'function') {
                    showRecoveryModal();
                }
            }, 400);
        @endif
    });

    // 3. Duplicate Detection (read-only scan + guarded single delete)
    const DUP_CONFIG = {
        scanUrl: @json(route('ppc.planning.production_plan.duplicates')),
        deleteUrl: @json(url('/ppc/planning/production-plan/duplicates')),
        csrf: @json(csrf_token()),
        date: @json($date),
        press: @json($currentPress),
        shift: @json($currentShift),
    };

    let DUP_STATE = { groups: [] };

    function dupEsc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function dupNum(value) {
        return Number(value || 0).toLocaleString('id-ID');
    }

    function clearDuplicateResult() {
        const result = document.getElementById('duplicateResult');
        if (!result) return;
        result.className = 'hidden';
        result.textContent = '';
    }

    function showDuplicateResult(type, message) {
        const result = document.getElementById('duplicateResult');
        if (!result) return;
        const styles = {
            success: 'mx-6 mt-4 p-3 rounded-xl text-xs font-bold border bg-emerald-50 text-emerald-700 border-emerald-200',
            warning: 'mx-6 mt-4 p-3 rounded-xl text-xs font-bold border bg-amber-50 text-amber-700 border-amber-200',
            error: 'mx-6 mt-4 p-3 rounded-xl text-xs font-bold border bg-rose-50 text-rose-700 border-rose-200',
        };
        result.className = styles[type] || styles.error;
        result.textContent = message || '';
    }

    function openDuplicateModal() {
        const modal = document.getElementById('duplicateModal');
        if (!modal) return;
        clearDuplicateResult();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        loadDuplicateScan();
    }

    function closeDuplicateModal() {
        const modal = document.getElementById('duplicateModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    async function loadDuplicateScan() {
        const loading = document.getElementById('duplicateLoading');
        const empty = document.getElementById('duplicateEmpty');
        const content = document.getElementById('duplicateContent');
        if (!loading || !empty || !content) return;

        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        content.classList.add('hidden');
        content.innerHTML = '';

        const params = new URLSearchParams();
        if (DUP_CONFIG.date) params.set('date', DUP_CONFIG.date);
        if (!document.getElementById('dupAllPress').checked) params.set('press', DUP_CONFIG.press);
        if (!document.getElementById('dupAllShift').checked) params.set('shift', DUP_CONFIG.shift);

        try {
            const response = await fetch(DUP_CONFIG.scanUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Gagal memindai item duplikat.');
            }

            DUP_STATE.groups = payload.data || [];
            loading.classList.add('hidden');

            if (DUP_STATE.groups.length === 0) {
                empty.classList.remove('hidden');
                return;
            }

            content.innerHTML = DUP_STATE.groups.map(renderDuplicateGroup).join('');
            content.classList.remove('hidden');
        } catch (error) {
            loading.classList.add('hidden');
            content.classList.remove('hidden');
            content.innerHTML = '<div class="p-6 bg-rose-50 border border-rose-200 rounded-2xl text-sm font-semibold text-rose-700">'
                + dupEsc(error.message) + '</div>';
        }
    }

    function renderDuplicateGroup(group, index) {
        const confidenceBadge = group.confidence === 'exact'
            ? '<span class="px-2 py-0.5 rounded-lg bg-rose-50 text-rose-700 ring-1 ring-rose-200 text-[10px] font-black uppercase">Slot identik</span>'
            : '<span class="px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-200 text-[10px] font-black uppercase">Perlu review</span>';

        const timeWindow = dupEsc(group.start_time) + (group.finish_times.length ? ' &ndash; ' + group.finish_times.map(dupEsc).join(' / ') : '');

        const members = group.members.map(function (member) {
            const badges = [
                member.has_activity ? '<span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[9px] font-black uppercase">Aktivitas</span>' : '',
                member.is_recovery ? '<span class="px-1.5 py-0.5 rounded bg-teal-100 text-teal-700 text-[9px] font-black uppercase">Recovery</span>' : '',
                member.status ? '<span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[9px] font-black uppercase">' + dupEsc(member.status) + '</span>' : '',
                member.requires_review ? '<span class="px-1.5 py-0.5 rounded bg-amber-100 text-amber-700 text-[9px] font-black uppercase">Review</span>' : '',
            ].join(' ');

            const blockers = member.blockers.length
                ? '<ul class="mt-2 space-y-1">' + member.blockers.map(function (item) {
                    return '<li class="flex items-start gap-1.5 text-[10px] text-rose-600 font-semibold"><span class="mt-0.5">&#10005;</span><span>' + dupEsc(item) + '</span></li>';
                }).join('') + '</ul>'
                : '';

            const warnings = member.warnings.length
                ? '<ul class="mt-2 space-y-1">' + member.warnings.map(function (item) {
                    return '<li class="flex items-start gap-1.5 text-[10px] text-amber-600 font-semibold"><span class="mt-0.5">!</span><span>' + dupEsc(item) + '</span></li>';
                }).join('') + '</ul>'
                : '';

            const disabledClass = member.deletable ? 'cursor-pointer hover:border-rose-300 hover:bg-rose-50/40' : 'cursor-not-allowed opacity-80 bg-slate-50';

            return '<label class="flex items-start gap-3 p-3 rounded-2xl border border-slate-200 transition-all ' + disabledClass + '">'
                + '<input type="radio" name="dup_group_' + index + '" value="' + member.id + '" '
                + (member.deletable ? '' : 'disabled ')
                + 'onchange="onDuplicateSelect(' + index + ')" '
                + 'class="mt-1 w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500 shrink-0">'
                + '<div class="flex-1 min-w-0">'
                + '<div class="flex flex-wrap items-center gap-2">'
                + '<span class="font-black text-sm text-slate-800">#' + member.id + '</span>'
                + badges
                + '</div>'
                + '<div class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-1 mt-2 text-[10px]">'
                + '<div><span class="text-slate-400 font-bold uppercase">Qty</span><div class="font-black text-slate-700">' + dupNum(member.plan_qty) + '</div></div>'
                + '<div><span class="text-slate-400 font-bold uppercase">OK</span><div class="font-black text-emerald-600">' + dupNum(member.ok) + '</div></div>'
                + '<div><span class="text-slate-400 font-bold uppercase">Repair</span><div class="font-black text-amber-600">' + dupNum(member.repair) + '</div></div>'
                + '<div><span class="text-slate-400 font-bold uppercase">Reject</span><div class="font-black text-rose-600">' + dupNum(member.reject) + '</div></div>'
                + '</div>'
                + '<div class="mt-1 text-[10px] text-slate-500 font-semibold">Job No: ' + dupEsc(member.job_no || '-')
                + ' &bull; ' + dupEsc(member.plan_date || '-')
                + ' &bull; ' + dupEsc(member.shift_name || '-')
                + ' &bull; ' + dupEsc(member.press_name || '-') + '</div>'
                + '<div class="mt-1 text-[10px] font-black uppercase tracking-wide ' + (member.has_activity ? 'text-rose-600' : 'text-emerald-600') + '">'
                + 'Status aktivitas: ' + (member.has_activity ? 'ADA' : 'TIDAK ADA') + '</div>'
                + '<div class="mt-1 text-[10px] text-slate-400 font-semibold">Jam: ' + dupEsc(member.start_time || '-') + ' &ndash; ' + dupEsc(member.finish_time || '-')
                + ' &bull; Row ' + dupEsc(member.row_no === null ? '-' : member.row_no)
                + ' &bull; Sumber ' + dupEsc(member.source_type || 'ppc') + '</div>'
                + blockers
                + warnings
                + '</div>'
                + '</label>';
        }).join('');

        const deleteDisabled = group.deletable_count > 0 ? '' : 'disabled';

        return '<div class="bg-white border border-slate-200 rounded-3xl shadow-sm overflow-hidden" data-group="' + index + '">'
            + '<div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">'
            + '<div class="min-w-0">'
            + '<div class="flex flex-wrap items-center gap-2">'
            + '<span class="text-sm font-black text-slate-800 tracking-tight">' + dupEsc(group.job_no || group.job_master || '-') + '</span>'
            + '<span class="text-[11px] font-semibold text-slate-400">' + dupEsc(group.job_master || '') + '</span>'
            + confidenceBadge
            + '</div>'
            + '<p class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-wide">'
            + dupEsc(group.plan_date) + ' &bull; ' + dupEsc(group.shift) + ' &bull; ' + dupEsc(group.press)
            + ' &bull; ' + timeWindow + ' &bull; ' + group.count + ' item'
            + '</p>'
            + '</div>'
            + '<span class="px-2.5 py-1 rounded-xl bg-slate-100 text-slate-600 text-[10px] font-black uppercase">' + group.deletable_count + ' dapat dihapus</span>'
            + '</div>'
            + '<div class="p-4 space-y-2">'
            + '<p class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">Pilih item yang akan dihapus (pasangan duplikat tetap disimpan):</p>'
            + members
            + '<div class="flex flex-wrap items-center justify-between gap-2 pt-2">'
            + '<span id="dup-note-' + index + '" class="text-[10px] font-semibold text-slate-400">Belum ada item dipilih.</span>'
            + '<button type="button" id="dup-delete-btn-' + index + '" onclick="openDuplicateConfirm(' + index + ')" ' + deleteDisabled
            + ' class="px-4 py-2 bg-rose-600 hover:bg-rose-700 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white rounded-xl text-[11px] font-black transition-all">'
            + 'HAPUS ITEM TERPILIH</button>'
            + '</div>'
            + '<div id="dup-confirm-' + index + '" class="hidden mt-2 p-4 bg-rose-50 border border-rose-200 rounded-2xl">'
            + '<p class="text-xs font-black text-rose-800 uppercase tracking-wide">Konfirmasi penghapusan</p>'
            + '<p class="text-[11px] text-rose-600 font-semibold mt-1">Hanya item terpilih yang dihapus. Tindakan ini dicatat pada audit trail dan tidak dapat dibatalkan.</p>'
            + '<label class="block mt-3 text-[10px] font-black text-slate-600 uppercase">Alasan penghapusan <span class="text-rose-600">*</span></label>'
            + '<textarea id="dup-reason-' + index + '" rows="2" maxlength="500" placeholder="Contoh: hasil import ganda pada revisi PPC" class="mt-1 w-full rounded-xl border-slate-300 text-xs focus:ring-rose-500 focus:border-rose-500"></textarea>'
            + '<label class="flex items-start gap-2 mt-3 text-[10px] font-bold text-slate-600 cursor-pointer select-none">'
            + '<input type="checkbox" id="dup-confirm-identity-' + index + '" class="mt-0.5 w-4 h-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">'
            + '<span>Saya sudah memastikan kedua item benar-benar duplikat dan item yang dihapus BUKAN satu-satunya jadwal job tersebut.</span>'
            + '</label>'
            + '<label id="dup-review-wrap-' + index + '" class="hidden flex items-start gap-2 mt-2 text-[10px] font-bold text-amber-700 cursor-pointer select-none">'
            + '<input type="checkbox" id="dup-confirm-review-' + index + '" class="mt-0.5 w-4 h-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500">'
            + '<span>Saya sudah memeriksa perbedaan detail jadwal (finish time / split) dan tetap yakin ini duplikat.</span>'
            + '</label>'
            + '<div id="dup-error-' + index + '" class="hidden mt-3 p-2.5 bg-white border border-rose-200 rounded-xl text-[10px] font-semibold text-rose-700"></div>'
            + '<div class="flex items-center justify-end gap-2 mt-3">'
            + '<button type="button" onclick="closeDuplicateConfirm(' + index + ')" class="px-4 py-2 bg-white border border-slate-200 hover:bg-slate-100 text-slate-600 rounded-xl text-[11px] font-black transition-all">BATAL</button>'
            + '<button type="button" id="dup-submit-' + index + '" onclick="submitDuplicateDelete(' + index + ')" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-[11px] font-black transition-all">HAPUS SEKARANG</button>'
            + '</div>'
            + '</div>'
            + '</div>'
            + '</div>';
    }

    function onDuplicateSelect(index) {
        const group = DUP_STATE.groups[index];
        if (!group) return;

        const selected = document.querySelector('input[name="dup_group_' + index + '"]:checked');
        const button = document.getElementById('dup-delete-btn-' + index);
        const note = document.getElementById('dup-note-' + index);
        const reviewWrap = document.getElementById('dup-review-wrap-' + index);
        const confirmPanel = document.getElementById('dup-confirm-' + index);

        if (confirmPanel) confirmPanel.classList.add('hidden');

        if (!selected) {
            if (button) button.disabled = true;
            if (note) note.textContent = 'Belum ada item dipilih.';
            if (reviewWrap) reviewWrap.classList.add('hidden');
            return;
        }

        const member = group.members.find(function (item) { return String(item.id) === String(selected.value); });
        if (button) button.disabled = false;
        if (note && member) {
            note.textContent = 'Dipilih #' + member.id + ' — ' + (member.job_no || member.job_master || '-')
                + (member.has_activity ? ' (memiliki aktivitas)' : '');
        }
        if (reviewWrap) reviewWrap.classList.toggle('hidden', !(member && member.requires_review));
    }

    function openDuplicateConfirm(index) {
        const selected = document.querySelector('input[name="dup_group_' + index + '"]:checked');
        if (!selected) {
            showToast('Pilih item yang akan dihapus terlebih dahulu.', 'warning');
            return;
        }
        const panel = document.getElementById('dup-confirm-' + index);
        if (panel) {
            panel.classList.remove('hidden');
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function closeDuplicateConfirm(index) {
        const panel = document.getElementById('dup-confirm-' + index);
        if (panel) panel.classList.add('hidden');
    }

    async function submitDuplicateDelete(index) {
        const group = DUP_STATE.groups[index];
        if (!group) return;

        const selected = document.querySelector('input[name="dup_group_' + index + '"]:checked');
        if (!selected) {
            showToast('Pilih item yang akan dihapus terlebih dahulu.', 'warning');
            return;
        }

        const selectedId = Number(selected.value);
        const member = group.members.find(function (item) { return Number(item.id) === selectedId; });
        const peer = group.members.find(function (item) { return Number(item.id) !== selectedId; });
        const reasonEl = document.getElementById('dup-reason-' + index);
        const identityEl = document.getElementById('dup-confirm-identity-' + index);
        const reviewEl = document.getElementById('dup-confirm-review-' + index);
        const errorEl = document.getElementById('dup-error-' + index);
        const submitBtn = document.getElementById('dup-submit-' + index);

        const reason = (reasonEl ? reasonEl.value : '').trim();
        const needsReview = !!(member && member.requires_review);

        if (errorEl) {
            errorEl.classList.add('hidden');
            errorEl.textContent = '';
        }

        if (!peer) {
            showToast('Pasangan duplikat tidak ditemukan.', 'error');
            return;
        }
        if (reason.length < 5) {
            showToast('Alasan penghapusan minimal 5 karakter.', 'warning');
            if (reasonEl) reasonEl.focus();
            return;
        }
        if (!identityEl || !identityEl.checked) {
            showToast('Centang konfirmasi identitas item terlebih dahulu.', 'warning');
            return;
        }
        if (needsReview && (!reviewEl || !reviewEl.checked)) {
            showToast('Centang konfirmasi pemeriksaan detail jadwal terlebih dahulu.', 'warning');
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'MENGHAPUS...';
        }

        try {
            const response = await fetch(DUP_CONFIG.deleteUrl + '/' + selectedId, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': DUP_CONFIG.csrf,
                },
                body: JSON.stringify({
                    duplicate_of_id: peer.id,
                    reason: reason,
                    confirm_identity: true,
                    confirm_review: !!(reviewEl && reviewEl.checked),
                }),
            });

            const payload = await response.json().catch(function () { return {}; });

            if (!response.ok || !payload.success) {
                let message = payload.message || 'Gagal menghapus item duplikat.';
                if (payload.errors) {
                    const firstKey = Object.keys(payload.errors)[0];
                    if (firstKey) message = payload.errors[firstKey][0];
                }
                if (payload.blockers && payload.blockers.length) {
                    message += ' ' + payload.blockers.join(' ');
                }
                const failure = new Error(message);
                failure.code = payload.code || '';
                throw failure;
            }

            const deletedId = Number(payload.deleted_id || selectedId);
            showToast(payload.message || 'Item duplikat berhasil dihapus.', 'success');
            showDuplicateResult('success', payload.message || 'Item duplikat berhasil dihapus.');

            const planningRow = document.querySelector('#planTableBody tr[data-id="' + deletedId + '"]');
            if (planningRow) planningRow.remove();

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'HAPUS SEKARANG';
            }

            await loadDuplicateScan();
        } catch (error) {
            const warningCodes = ['blocked', 'needs_review', 'both_active', 'recovery_item', 'recovery_peer'];
            const resultType = warningCodes.indexOf(error.code) !== -1 ? 'warning' : 'error';

            if (errorEl) {
                errorEl.textContent = error.message;
                errorEl.classList.remove('hidden');
            }
            showDuplicateResult(resultType, error.message);
            showToast(error.message, 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'HAPUS SEKARANG';
            }
        }
    }

    let ROW_DELETE_STATE = { plan: null, peers: [], peer: null, requiresReview: false, deletable: false };

    function rowDeleteSummaryCard(label, value, colorClass) {
        return '<div class="p-2.5 rounded-xl bg-white border border-slate-200">'
            + '<div class="text-[9px] font-black uppercase tracking-wide text-slate-400">' + dupEsc(label) + '</div>'
            + '<div class="mt-0.5 text-[11px] font-black ' + (colorClass || 'text-slate-700') + '">' + dupEsc(value) + '</div>'
            + '</div>';
    }

    function closeRowDeleteModal() {
        const modal = document.getElementById('rowDeleteModal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        ROW_DELETE_STATE = { plan: null, peers: [], peer: null, requiresReview: false, deletable: false };
    }

    async function openRowDeleteModal(planId) {
        const modal = document.getElementById('rowDeleteModal');
        const loading = document.getElementById('rowDeleteLoading');
        const body = document.getElementById('rowDeleteBody');
        const warning = document.getElementById('rowDeleteWarning');
        const summary = document.getElementById('rowDeleteSummary');
        const peer = document.getElementById('rowDeletePeer');
        const blockers = document.getElementById('rowDeleteBlockers');
        const form = document.getElementById('rowDeleteForm');
        const error = document.getElementById('rowDeleteError');
        const submit = document.getElementById('rowDeleteSubmit');
        const check = document.getElementById('rowDeleteCheckDuplicate');
        const reviewWrap = document.getElementById('rowDeleteReviewWrap');
        const reason = document.getElementById('rowDeleteReason');
        const identity = document.getElementById('rowDeleteIdentity');
        const review = document.getElementById('rowDeleteReview');

        if (!modal) return;

        ROW_DELETE_STATE = { plan: null, peers: [], peer: null, requiresReview: false, deletable: false };
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        loading.classList.remove('hidden');
        body.classList.add('hidden');
        warning.classList.add('hidden');
        warning.textContent = '';
        summary.innerHTML = '';
        peer.innerHTML = '';
        blockers.classList.add('hidden');
        blockers.innerHTML = '';
        form.classList.add('hidden');
        error.classList.add('hidden');
        error.textContent = '';
        submit.disabled = true;
        submit.textContent = 'HAPUS RECORD';
        submit.classList.remove('hidden');
        check.classList.add('hidden');
        reviewWrap.classList.add('hidden');
        if (reason) reason.value = '';
        if (identity) identity.checked = false;
        if (review) review.checked = false;

        try {
            const response = await fetch(DUP_CONFIG.deleteUrl + '/' + planId, {
                headers: { 'Accept': 'application/json' },
            });
            const payload = await response.json().catch(function () { return {}; });

            if (!response.ok || !payload.success || !payload.data) {
                throw new Error(payload.message || 'Gagal memeriksa item.');
            }

            loading.classList.add('hidden');
            body.classList.remove('hidden');
            renderRowDeleteDetail(payload.data);
        } catch (fetchError) {
            loading.classList.add('hidden');
            body.classList.remove('hidden');
            warning.textContent = fetchError.message;
            warning.className = 'p-3 rounded-xl border bg-rose-50 text-rose-700 border-rose-200 text-[11px] font-semibold leading-relaxed';
            warning.classList.remove('hidden');
            submit.disabled = true;
            submit.classList.add('hidden');
        }
    }

    function renderRowDeleteDetail(detail) {
        const plan = detail.plan || {};
        const peers = Array.isArray(detail.peers) ? detail.peers : [];
        const analysis = detail.analysis || null;
        const summary = document.getElementById('rowDeleteSummary');
        const peerEl = document.getElementById('rowDeletePeer');
        const warning = document.getElementById('rowDeleteWarning');
        const blockersEl = document.getElementById('rowDeleteBlockers');
        const form = document.getElementById('rowDeleteForm');
        const submit = document.getElementById('rowDeleteSubmit');
        const check = document.getElementById('rowDeleteCheckDuplicate');
        const reviewWrap = document.getElementById('rowDeleteReviewWrap');
        const review = document.getElementById('rowDeleteReview');
        const error = document.getElementById('rowDeleteError');

        const candidates = peers.filter(function (item) {
            return item.analysis && !item.analysis.is_recovery;
        });
        const exact = candidates.find(function (item) {
            return !item.analysis.requires_review;
        });
        const chosen = exact || candidates[0] || null;
        const requiresReview = !!(chosen && chosen.analysis.requires_review);
        const rawBlockers = analysis && Array.isArray(analysis.blockers) ? analysis.blockers.slice() : [];

        if (analysis && analysis.is_recovery) {
            rawBlockers.push('Item berasal dari jadwal Recovery.');
        }

        const uniqueBlockers = rawBlockers.filter(function (item, index) {
            return rawBlockers.indexOf(item) === index;
        });

        ROW_DELETE_STATE = {
            plan: plan,
            peers: peers,
            peer: chosen ? chosen.plan : null,
            requiresReview: requiresReview,
            deletable: !!(analysis && analysis.deletable && chosen && uniqueBlockers.length === 0),
        };

        summary.innerHTML = [
            rowDeleteSummaryCard('ID Record', '#' + (plan.id || '-')),
            rowDeleteSummaryCard('Job No', plan.job_no || '-'),
            rowDeleteSummaryCard('JobMaster', plan.job_master || '-'),
            rowDeleteSummaryCard('Tanggal', plan.plan_date || '-'),
            rowDeleteSummaryCard('Shift', plan.shift_name || '-'),
            rowDeleteSummaryCard('Press', plan.press_name || '-'),
            rowDeleteSummaryCard('Qty Plan', dupNum(plan.plan_qty), 'text-amber-600'),
            rowDeleteSummaryCard('Status Produksi', plan.status || '-'),
            rowDeleteSummaryCard('OK / Repair / Reject', dupNum(plan.ok) + ' / ' + dupNum(plan.repair) + ' / ' + dupNum(plan.reject)),
            rowDeleteSummaryCard('Jam', (plan.start_time || '-') + ' - ' + (plan.finish_time || '-')),
        ].join('');

        if (chosen) {
            const peerPlan = chosen.plan || {};
            peerEl.innerHTML = '<div class="p-3 rounded-xl border border-emerald-200 bg-emerald-50 text-[11px] text-emerald-800 font-semibold leading-relaxed">'
                + '<div class="font-black uppercase tracking-wide text-[10px] text-emerald-700">Pasangan duplikat yang dipertahankan</div>'
                + '<div class="mt-1">#' + dupEsc(peerPlan.id || '-') + ' &bull; ' + dupEsc(peerPlan.job_no || peerPlan.job_master || '-')
                + ' &bull; ' + dupEsc(peerPlan.start_time || '-') + ' &ndash; ' + dupEsc(peerPlan.finish_time || '-')
                + (peers.length > 1 ? ' &bull; ' + peers.length + ' pasangan ditemukan, semuanya tetap disimpan' : '')
                + '</div></div>';
        } else {
            peerEl.innerHTML = '';
        }

        if (!analysis) {
            warning.textContent = 'Item ini belum terbukti sebagai duplikat. Periksa melalui CEK DUPLIKAT sebelum menghapus.';
            warning.className = 'p-3 rounded-xl border bg-amber-50 text-amber-800 border-amber-200 text-[11px] font-semibold leading-relaxed';
            warning.classList.remove('hidden');
            check.classList.remove('hidden');
            form.classList.add('hidden');
            submit.disabled = true;
            submit.classList.add('hidden');
            return;
        }

        if (!chosen) {
            warning.textContent = peers.length > 0
                ? 'Pasangan duplikat berasal dari jadwal Recovery. Periksa kedua item melalui menu Recovery.'
                : 'Item ini belum terbukti sebagai duplikat. Periksa melalui CEK DUPLIKAT sebelum menghapus.';
            warning.className = 'p-3 rounded-xl border bg-amber-50 text-amber-800 border-amber-200 text-[11px] font-semibold leading-relaxed';
            warning.classList.remove('hidden');
            check.classList.toggle('hidden', peers.length > 0);
            form.classList.add('hidden');
            submit.disabled = true;
            submit.classList.add('hidden');
            return;
        }

        if (uniqueBlockers.length > 0 || !analysis.deletable) {
            warning.textContent = 'Item ini tidak dapat dihapus karena masih terhubung dengan data produksi atau relasi yang dilindungi.';
            warning.className = 'p-3 rounded-xl border bg-rose-50 text-rose-700 border-rose-200 text-[11px] font-semibold leading-relaxed';
            warning.classList.remove('hidden');
            blockersEl.innerHTML = uniqueBlockers.map(function (item) {
                return '<div class="flex items-start gap-2 text-[10px] text-rose-700 font-semibold py-0.5"><span>&#10005;</span><span>' + dupEsc(item) + '</span></div>';
            }).join('');
            blockersEl.classList.remove('hidden');
            form.classList.add('hidden');
            submit.disabled = true;
            submit.classList.add('hidden');
            return;
        }

        warning.classList.add('hidden');
        warning.textContent = '';
        form.classList.remove('hidden');
        submit.classList.remove('hidden');
        submit.disabled = false;
        check.classList.add('hidden');
        error.classList.add('hidden');
        error.textContent = '';

        if (requiresReview) {
            reviewWrap.classList.remove('hidden');
        } else {
            reviewWrap.classList.add('hidden');
            if (review) review.checked = false;
        }
    }

    async function submitRowDelete() {
        const state = ROW_DELETE_STATE;
        if (!state.plan || !state.peer) {
            showToast('Item atau pasangan duplikat tidak ditemukan.', 'error');
            return;
        }
        if (!state.deletable) {
            showToast('Item ini belum dapat dihapus. Periksa kembali status dan relasi produksinya.', 'error');
            return;
        }

        const reasonEl = document.getElementById('rowDeleteReason');
        const identityEl = document.getElementById('rowDeleteIdentity');
        const reviewEl = document.getElementById('rowDeleteReview');
        const errorEl = document.getElementById('rowDeleteError');
        const submit = document.getElementById('rowDeleteSubmit');
        const reason = (reasonEl ? reasonEl.value : '').trim();

        if (errorEl) {
            errorEl.classList.add('hidden');
            errorEl.textContent = '';
        }

        if (reason.length < 5) {
            showToast('Alasan penghapusan minimal 5 karakter.', 'warning');
            if (reasonEl) reasonEl.focus();
            return;
        }
        if (!identityEl || !identityEl.checked) {
            showToast('Centang konfirmasi identitas record terlebih dahulu.', 'warning');
            return;
        }
        if (state.requiresReview && (!reviewEl || !reviewEl.checked)) {
            showToast('Centang konfirmasi pemeriksaan detail jadwal terlebih dahulu.', 'warning');
            return;
        }

        if (submit) {
            submit.disabled = true;
            submit.textContent = 'MENGHAPUS...';
        }

        try {
            const response = await fetch(DUP_CONFIG.deleteUrl + '/' + state.plan.id, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': DUP_CONFIG.csrf,
                },
                body: JSON.stringify({
                    duplicate_of_id: state.peer.id,
                    reason: reason,
                    confirm_identity: true,
                    confirm_review: !!(state.requiresReview && reviewEl && reviewEl.checked),
                }),
            });

            const payload = await response.json().catch(function () { return {}; });

            if (!response.ok || !payload.success) {
                let message = payload.message || 'Gagal menghapus item duplikat.';
                if (payload.errors) {
                    const firstKey = Object.keys(payload.errors)[0];
                    if (firstKey) message = payload.errors[firstKey][0];
                }
                if (payload.blockers && payload.blockers.length) {
                    message += ' ' + payload.blockers.join(' ');
                }
                throw new Error(message);
            }

            const deletedId = Number(payload.deleted_id || state.plan.id);
            showToast(payload.message || 'Item duplikat berhasil dihapus.', 'success');

            const row = document.querySelector('#planTableBody tr[data-id="' + deletedId + '"]');
            if (row) row.remove();

            closeRowDeleteModal();

            const duplicateModal = document.getElementById('duplicateModal');
            if (duplicateModal && !duplicateModal.classList.contains('hidden')) {
                loadDuplicateScan();
            }
        } catch (error) {
            if (errorEl) {
                errorEl.textContent = error.message;
                errorEl.classList.remove('hidden');
            }
            showToast(error.message, 'error');
            if (submit) {
                submit.disabled = false;
                submit.textContent = 'HAPUS RECORD';
            }
        }
    }
</script>
@endpush

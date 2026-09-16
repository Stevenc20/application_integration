@php
    $tableMode = $tableMode ?? 'schedule';
    $colCount = 36;
    $timeCell = fn ($dt) => $dt ? $dt->format('H:i') : '-';
@endphp

<div class="lkh-table-shell">
    <div class="lkh-scroll-x">
        <div class="lkh-sticky-shadow">
            @if (count($jobsData) === 0)
                <table class="lkh-grid-table">
                    <colgroup>
                        <col style="width:60px">
                        <col style="width:220px">
                        <col style="width:70px">
                        <col style="width:70px">
                        <col style="width:80px">
                        <col style="width:70px">
                        <col style="width:160px">
                        <col style="width:80px">
                        <col style="width:70px">
                        <col style="width:70px">
                        <col style="width:70px">
                        <col style="width:80px">
                        <col style="width:80px">
                        <col style="width:90px">
                        <col style="width:70px">
                        <col style="width:65px">
                        <col style="width:65px">
                        <col style="width:85px">
                        <col style="width:75px">
                        <col style="width:85px">
                        <col style="width:80px">
                        <col style="width:80px">
                        <col style="width:90px">
                        <col style="width:90px">
    <col style="width:220px">
    <col style="width:55px">
    <col style="width:55px">
    <col style="width:55px">
    <col style="width:55px">
    <col style="width:65px">
    <col style="width:65px">
    <col style="width:65px">
    <col style="width:65px">
    <col style="width:75px">
    <col style="width:65px">
    <col style="width:110px">
</colgroup>
                    <thead>
                        <tr class="text-white border-b border-gray-700 font-black uppercase tracking-wide">
                            <th colspan="7" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Identity</th>
                            <th colspan="4" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Output</th>
                            <th colspan="1" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Machine</th>
                            <th colspan="8" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Time Engine</th>
                            <th colspan="4" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Schedule</th>
                            <th colspan="1" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Info</th>
                            <th colspan="4" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Quality</th>
                            <th colspan="6" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Down Time</th>
                            <th colspan="1" class="lkh-cell py-2 px-2 text-center">Status</th>
                        </tr>
                        <tr class="text-white border-b-2 border-gray-800 font-bold">
                            <th class="lkh-cell lkh-sticky-no py-2.5 px-2 border-r border-gray-700 text-center">NO</th>
                            <th class="lkh-cell lkh-sticky-jm py-2.5 px-2 border-r border-gray-700 text-center">JOB MASTER</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">TYPE PLT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">QTY/PLT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">KEB MTL</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">TOT PLT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">JOB NO</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">PLAN</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">OK</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">REPAIR</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">REJECT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">TOT MESIN</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">CT (")</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">PROC TIME</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">REG ACT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">DCT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">MCT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">PLAN DCT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">TPT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">GSPH</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">START</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">FINISH</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">ACT START</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">ACT FINISH</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">KETERANGAN</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">A-1</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">A-2</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">A-3</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">A-4</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Dies</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Machine</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Material</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Log</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Prod Hndl</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Total</th>
                            <th class="lkh-cell py-2.5 px-2 text-center">STATUS</th>
                        </tr>
                    </thead>
                </table>

                <div class="lkh-empty-state">
                    <div class="flex flex-col items-center justify-center text-center max-w-lg mx-auto px-6">
                        <div class="text-[72px] leading-none mb-2">📄</div>
                        <p class="text-lg font-black text-gray-700">Tidak ada jadwal produksi</p>
                        <p class="text-sm text-gray-500 mt-2">Belum ada data PPC untuk:</p>
                        <p class="text-sm font-bold text-gray-600">
                            {{ $selectedLineName ?? 'Line' }} / {{ $selectedShift ?? 'Shift' }} / {{ $date ?? '-' }}
                        </p>
                    </div>
                </div>
            @else
                <table class="lkh-grid-table">
                    <colgroup>
                        <col style="width:60px">
                        <col style="width:220px">
                        <col style="width:70px">
                        <col style="width:70px">
                        <col style="width:80px">
                        <col style="width:70px">
                        <col style="width:160px">
                        <col style="width:80px">
                        <col style="width:70px">
                        <col style="width:70px">
                        <col style="width:70px">
                        <col style="width:80px">
                        <col style="width:80px">
                        <col style="width:90px">
                        <col style="width:70px">
                        <col style="width:65px">
                        <col style="width:65px">
                        <col style="width:85px">
                        <col style="width:75px">
                        <col style="width:85px">
                        <col style="width:80px">
                        <col style="width:80px">
                        <col style="width:90px">
                        <col style="width:90px">
    <col style="width:220px">
    <col style="width:55px">
    <col style="width:55px">
    <col style="width:55px">
    <col style="width:55px">
    <col style="width:65px">
    <col style="width:65px">
    <col style="width:65px">
    <col style="width:65px">
    <col style="width:75px">
    <col style="width:65px">
    <col style="width:110px">
</colgroup>
                    <thead>
                        <tr class="text-white border-b border-gray-700 font-black uppercase tracking-wide">
                            <th colspan="7" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Identity</th>
                            <th colspan="4" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Output</th>
                            <th colspan="1" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Machine</th>
                            <th colspan="8" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Time Engine</th>
                            <th colspan="4" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Schedule</th>
                            <th colspan="1" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Info</th>
                            <th colspan="4" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Quality</th>
                            <th colspan="6" class="lkh-cell py-2 px-2 border-r border-gray-700 text-center">Down Time</th>
                            <th colspan="1" class="lkh-cell py-2 px-2 text-center">Status</th>
                        </tr>
                        <tr class="text-white border-b-2 border-gray-800 font-bold">
                            <th class="lkh-cell lkh-sticky-no py-2.5 px-2 border-r border-gray-700 text-center">NO</th>
                            <th class="lkh-cell lkh-sticky-jm py-2.5 px-2 border-r border-gray-700 text-center">JOB MASTER</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">TYPE PLT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">QTY/PLT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">KEB MTL</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">TOT PLT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">JOB NO</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">PLAN</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">OK</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">REPAIR</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">REJECT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">TOT MESIN</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">CT (")</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">PROC TIME</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">REG ACT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">DCT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">MCT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">PLAN DCT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">TPT</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">GSPH</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">START</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">FINISH</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">ACT START</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">ACT FINISH</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">KETERANGAN</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">A-1</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">A-2</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">A-3</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">A-4</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Dies</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Machine</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Material</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Log</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Prod Hndl</th>
                            <th class="lkh-cell py-2.5 px-2 border-r border-gray-700 text-center">Total</th>
                            <th class="lkh-cell py-2.5 px-2 text-center">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($jobsData as $index => $job)
                            @if (($job['row_type'] ?? 'job') === 'break')
                                @php
                                    $breakDesc = $job['break_label'] ?? $job['job_master'] ?? 'ISTIRAHAT';
                                    $startStr = $timeCell($job['schedule_start'] ?? null);
                                    $finishStr = $timeCell($job['schedule_finish'] ?? null);
                                    
                                    $duration = 0;
                                    if (($job['schedule_start'] ?? null) && ($job['schedule_finish'] ?? null)) {
                                        $duration = abs($job['schedule_finish']->diffInMinutes($job['schedule_start']));
                                    }
                                @endphp
                                <tr class="lkh-row-break border-l-4 border-l-amber-500 border-y border-amber-200/50 bg-amber-50/10">
                                    {{-- 1: NO --}}
                                    <td class="lkh-cell py-2 px-2 border-r border-amber-100/50 text-center font-bold text-amber-600/50 bg-amber-50/20">—</td>
                                    
                                    {{-- 2-20: Label Istirahat --}}
                                    <td colspan="19" class="lkh-cell py-2 px-4 border-r border-amber-100/50 text-center bg-amber-50/20">
                                        <div class="flex items-center gap-6">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse border-2 border-white shadow-sm"></span>
                                                <span class="text-xs font-black text-amber-900 uppercase tracking-widest">{{ $breakDesc }}</span>
                                            </div>
                                            <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-white/75 border border-amber-200 shadow-sm">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-[10px] font-black text-amber-700 uppercase tracking-tighter">{{ $duration }} MINS</span>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 21: START --}}
                                    <td class="lkh-cell py-2 px-2 border-r border-amber-100/50 text-center bg-amber-50/30">
                                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white border border-amber-200 shadow-sm">
                                            <span class="text-xs font-bold text-amber-700 font-mono">{{ $startStr }}</span>
                                        </div>
                                    </td>

                                    {{-- 22: FINISH --}}
                                    <td class="lkh-cell py-2 px-2 border-r border-amber-100/50 text-center bg-amber-50/30">
                                        <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-white border border-amber-200 shadow-sm">
                                            <span class="text-xs font-bold text-amber-700 font-mono">{{ $finishStr }}</span>
                                        </div>
                                    </td>

                                    {{-- 23-36: Remaining empty columns (14 Columns) --}}
                                    <td colspan="14" class="lkh-cell py-2 px-2 bg-amber-50/10 border-r border-gray-200"></td>
                                </tr>
                            @endif
                            @if (($job['row_type'] ?? 'job') !== 'break')
                                @php
                                    $rowNo = $job['display_no'] ?? ($index + 1);
                                    $isAlt = $rowNo % 2 !== 0;
                                    $gsphVal = $tableMode === 'actual' ? ($job['actual_gsph'] ?? 0) : ($job['plan_gsph'] ?? 0);
                                    $ctVal = $job['plan_ct'] ?? 0;
                                    $tptVal = $tableMode === 'actual' ? ($job['tpt_act'] ?? 0) : ($job['tpt_plan'] ?? 0);
                                    $dtDiesVal = $job['dt_breakdown']['dies_t'] ?? 0;
                                    $dtMachVal = $job['dt_breakdown']['mach_t'] ?? 0;
                                    $dtMatlVal = $job['dt_breakdown']['mat_t'] ?? 0;
                                    $dtLogVal = $job['dt_breakdown']['log_t'] ?? 0;
                                    $dtProdVal = $job['dt_breakdown']['prod_t'] ?? 0;
                                    $dtTotalVal = $job['dt_total'] ?? 0;
                                    $processTimeVal = is_array($job)
                                        ? ($job['process_time'] ?? 0)
                                        : ($job->process_time ?? 0);
                                    $status = strtoupper($job['status'] ?? 'PENDING');
                                    $statusClass = match ($status) {
                                        'DONE' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'RUNNING' => 'bg-blue-100 text-blue-800 border-blue-200',
                                        'DOWNTIME' => 'bg-red-100 text-red-800 border-red-200',
                                        'DANDORI' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'BREAK' => 'bg-gray-200 text-gray-700 border-gray-300',
                                        default => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                    };
                                @endphp
                                <tr class="text-center {{ $isAlt ? 'lkh-row-alt' : 'bg-white' }} hover:bg-slate-50/80 transition-colors">
                                    <td class="lkh-cell lkh-sticky-no py-2 px-2 border-r border-gray-200 font-bold text-gray-500">{{ $rowNo }}</td>
                                    <td class="lkh-cell lkh-sticky-jm py-2 px-2 border-r border-gray-200 text-center font-black text-gray-900" title="{{ $job['job_master'] ?? '' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="truncate">{{ $job['job_master'] ?? '-' }}</span>
                                            @if(!empty($job['session_no']))
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9px] font-black bg-indigo-50 text-indigo-600 border border-indigo-100 shadow-sm shrink-0" title="Split Session {{ $job['session_no'] }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                                    </svg>
                                                    SES {{ chr(64 + (int)$job['session_no']) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 text-indigo-700 font-bold">{{ $job['type_plt'] ?? '-' }}</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtQty($job['qty_plt'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-semibold text-slate-800">@fmtQty($job['keb_mtl'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtQty($job['total_plt'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 text-center font-semibold text-gray-700" title="{{ $job['job_no'] ?? '' }}">{{ $job['job_no'] ?? '-' }}</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 bg-amber-50/40 font-black text-amber-800">@fmtQty($job['plan_qty'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 bg-emerald-50/40 font-black text-emerald-800">@fmtQty($job['actual_good'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 text-amber-700 font-bold">@fmtQty($job['actual_repair'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 text-red-600 font-bold">@fmtQty($job['actual_reject'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtQty($job['total_mesin'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-black">{{ (float) $ctVal > 0 ? number_format((float) $ctVal, 1, '.', '') : '-' }}</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">{{ $processTimeVal !== null && $processTimeVal !== '' ? (int) ceil((float) $processTimeVal) : '-' }}</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtQty($job['reg_active'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtMin($job['dct'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtMin($job['mct'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtMin($job['plan_dct'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-bold">@fmtMin($tptVal)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-black text-red-900">@fmtGsph($gsphVal)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-semibold text-violet-800">{{ $timeCell($job['schedule_start'] ?? null) }}</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-semibold text-violet-800">{{ $timeCell($job['schedule_finish'] ?? null) }}</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-bold text-emerald-800 bg-emerald-50/30">{{ $timeCell($job['actual_start'] ?? null) }}</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-bold text-emerald-800 bg-emerald-50/30">{{ $timeCell($job['actual_finish'] ?? null) }}</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 text-center text-gray-600 italic" title="{{ $job['keterangan'] ?? '' }}">{{ $job['keterangan'] ?? '-' }}</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtQty($job['a1'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtQty($job['a2'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtQty($job['a3'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200">@fmtQty($job['a4'] ?? 0)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-bold text-red-800">@fmtMin($dtDiesVal)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-bold text-red-800">@fmtMin($dtMachVal)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-bold text-red-800">@fmtMin($dtMatlVal)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-bold text-red-800">@fmtMin($dtLogVal)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-bold text-red-800">@fmtMin($dtProdVal)</td>
                                    <td class="lkh-cell py-2 px-2 border-r border-gray-200 font-bold text-red-900">@fmtMin($dtTotalVal)</td>
                                    <td class="lkh-cell py-2 px-2">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-black border {{ $statusClass }}">{{ $status }}</span>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-black border-t-2 border-gray-800 text-red-950 text-[11px]">
                            <td class="lkh-cell lkh-sticky-no py-3 px-2 border-r border-gray-300"></td>
                            <td colspan="6" class="lkh-cell lkh-sticky-jm py-3 px-2 border-r border-gray-300 text-center">TOTAL SHIFT</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtQty($totals['plan_qty'] ?? 0)</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtQty($totals['actual_good'] ?? 0)</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtQty($totals['actual_repair'] ?? 0)</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtQty($totals['actual_reject'] ?? 0)</td>
                            <td colspan="7" class="lkh-cell py-3 px-2 border-r border-gray-300"></td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtMin($totals['tpt_plan'] ?? 0)</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtGsph($tableMode === 'actual' ? ($totals['gsph'] ?? 0) : ($totals['plan_gsph'] ?? 0))</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">{{ isset($totals['total_schedule_start']) && $totals['total_schedule_start'] ? $totals['total_schedule_start']->format('H:i') : '-' }}</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">{{ isset($totals['total_schedule_finish']) && $totals['total_schedule_finish'] ? $totals['total_schedule_finish']->format('H:i') : '-' }}</td>
                            <td colspan="2" class="lkh-cell py-3 px-2 border-r border-gray-300"></td>
                            <td colspan="5" class="lkh-cell py-3 px-2 border-r border-gray-300"></td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtMin($totals['downtime_dies'] ?? 0)</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtMin($totals['downtime_mach'] ?? 0)</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtMin($totals['downtime_matl'] ?? 0)</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtMin($totals['downtime_log'] ?? 0)</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300">@fmtMin($totals['downtime_prod'] ?? 0)</td>
                            <td class="lkh-cell py-3 px-2 border-r border-gray-300 font-bold">@fmtMin($totals['downtime_total'] ?? 0)</td>
                            <td class="lkh-cell py-3 px-2"></td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>
    </div>
</div>

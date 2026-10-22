{{--
    Reusable schedule table partial matching the exact Production Plan column layout, typography, badges, and break row format.
    Parameters:
    - $rows: array of normalized ProductionPlan rows (with optional 'diff_state' and 'changed_fields')
    - $tableMode: 'snapshot' | 'live'
--}}
@php
    $mode = $tableMode ?? 'snapshot';
@endphp
<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white custom-scrollbar shadow-sm">
    <table class="w-full table-auto border-collapse text-left">
        <thead>
            <tr class="bg-slate-900 whitespace-nowrap">
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[90px]">Perbandingan</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[85px]">Press</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[95px]">Shift</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[50px]">#</th>
                <th class="px-4 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700 min-w-[220px]">Job Master</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">Type</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">Qty/Plt</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">Tot. Plt</th>
                <th class="px-4 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700 min-w-[150px]">Job No.</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[85px]">Plan</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-emerald-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">OK</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-yellow-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Repair</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Reject</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center border-r border-slate-700 min-w-[85px]">Balance</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[70px]">Mesin</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[65px]">CT"</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Proc.Time</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[75px]">Reg Act</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[65px]">DCT</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-sky-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[65px]">MCT</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">Plan DCT</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-amber-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[80px]">TPT</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[85px]">GSPH</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-violet-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[75px]">Start</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-violet-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[75px]">Finish</th>
                <th class="px-4 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest border-r border-slate-700 min-w-[180px]">Keterangan</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[60px]">A-1</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[60px]">A-2</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[60px]">A-3</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-rose-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[60px]">A-4</th>
                <th class="px-3 py-3.5 text-[9px] font-black text-slate-400 uppercase tracking-widest text-center border-r border-slate-700 min-w-[65px]">DT</th>
                <th class="px-4 py-3.5 text-[9px] font-black text-slate-300 uppercase tracking-widest text-center min-w-[110px]">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($rows as $row)
                @php
                    $pressVal = $row['press_name'] ?? $row['press'] ?? '—';
                    $shiftVal = $row['shift_name'] ?? $row['shift'] ?? '—';
                    $rowNoVal = $row['row_no'] ?? $row['urutan_plan'] ?? null;
                    $rowType = strtolower((string) ($row['row_type'] ?? 'job'));
                    $jobMaster = trim((string) ($row['job_master'] ?? ''));
                    $jobNo = trim((string) ($row['job_no'] ?? ''));
                    $combined = strtoupper($jobNo . ' ' . $jobMaster . ' ' . ($row['keterangan'] ?? ''));
                    $diffState = $row['diff_state'] ?? 'identical';
                    $changedFields = $row['changed_fields'] ?? [];

                    $isBreak = ($rowType === 'break') ||
                               str_contains($combined, 'ISTIRAHAT') ||
                               str_contains($combined, 'CINGKORAK') ||
                               str_contains($combined, 'BREAKTIME');

                    // Diff badge & row border
                    if ($diffState === 'missing_in_live') {
                        $diffBadge = 'HILANG DI LIVE';
                        $diffBadgeClass = 'bg-red-600 text-white border-red-700';
                        $rowHighlight = 'bg-red-50/40 border-l-4 border-l-red-600';
                    } elseif ($diffState === 'added_after_backup') {
                        $diffBadge = 'ITEM BARU';
                        $diffBadgeClass = 'bg-emerald-600 text-white border-emerald-700';
                        $rowHighlight = 'bg-emerald-50/40 border-l-4 border-l-emerald-600';
                    } elseif ($diffState === 'changed') {
                        $diffBadge = 'BERUBAH';
                        $diffBadgeClass = 'bg-amber-500 text-white border-amber-600';
                        $rowHighlight = 'bg-amber-50/30 border-l-4 border-l-amber-500';
                    } else {
                        $diffBadge = 'IDENTIK';
                        $diffBadgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
                        $rowHighlight = 'hover:bg-slate-50';
                    }
                @endphp

                @if($isBreak && $rowType !== 'total_finish')
                    @php
                        $breakDesc = 'OPERATIONAL BREAK';
                        $start = $row['start_time'] ?? null;
                        $finish = $row['finish_time'] ?? null;
                        $dct = (int) ($row['dct'] ?? 0);

                        if ($dct >= 45) {
                            $isFriday = str_contains(strtoupper((string) ($row['hari'] ?? '')), 'JUMAT');
                            $breakDesc = $isFriday ? 'ISTIRAHAT JUMAT' : 'ISTIRAHAT SIANG';
                        } elseif ($dct == 15) {
                            $breakDesc = ($start >= '15:00' && $finish <= '16:00') ? 'BREAKTIME' : 'CINGKORAK';
                        } elseif ($dct == 30) {
                            $breakDesc = 'ISTIRAHAT SORE';
                        } elseif ($dct == 10) {
                            $breakDesc = 'CLEANING / 5S';
                        }
                        if (!empty($jobNo) && !in_array($jobNo, ['0', '—', '', 'None'])) {
                            $breakDesc = $jobNo;
                        }
                        $breakCell = 'bg-amber-50/40 border-r border-amber-100/50';
                    @endphp
                    <tr class="border-l-4 border-l-amber-400 border-y border-amber-200/50">
                        <td class="px-2 py-2.5 text-center {{ $breakCell }}">
                            <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase border {{ $diffBadgeClass }}">{{ $diffBadge }}</span>
                        </td>
                        <td class="px-3 py-2.5 text-center text-[11px] font-black text-amber-900 {{ $breakCell }}">{{ $pressVal ?: '—' }}</td>
                        <td class="px-3 py-2.5 text-center text-[11px] font-bold text-amber-800 {{ $breakCell }}">{{ $shiftVal ?: '—' }}</td>
                        <td class="px-3 py-2.5 text-center text-[10px] font-bold text-amber-700 {{ $breakCell }}">—</td>
                        <td colspan="19" class="px-6 py-2.5 {{ $breakCell }}">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 border-2 border-white shadow-sm"></span>
                                    <span class="text-[10px] font-black text-amber-900 uppercase tracking-widest">{{ $breakDesc }}</span>
                                </div>
                                <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white border border-amber-200 shadow-sm">
                                    <span class="text-[10px] font-black text-amber-700 uppercase">{{ $dct }} MINS</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-2 py-2.5 text-center {{ $breakCell }} font-mono text-[10px] font-black text-amber-800">{{ $start ?: '—' }}</td>
                        <td class="px-2 py-2.5 text-center {{ $breakCell }} font-mono text-[10px] font-black text-amber-800">{{ $finish ?: '—' }}</td>
                        <td class="{{ $breakCell }}"></td>
                        <td class="{{ $breakCell }}"></td>
                        <td class="{{ $breakCell }}"></td>
                        <td class="{{ $breakCell }}"></td>
                        <td class="{{ $breakCell }}"></td>
                        <td class="px-3 py-2.5 text-center {{ $breakCell }} font-black text-amber-800 text-[11px]">{{ $dct }}</td>
                        <td class="bg-amber-50/40 text-center text-[10px] font-black text-amber-700">BREAK</td>
                    </tr>
                @else
                    @php
                        $planQty = (float) ($row['plan'] ?? $row['qty_plan'] ?? 0);
                        $okQty = array_key_exists('ok', $row) && $row['ok'] !== null ? (float) $row['ok'] : null;
                        $repairQty = array_key_exists('repair', $row) && $row['repair'] !== null ? (float) $row['repair'] : null;
                        $rejectQty = array_key_exists('reject', $row) && $row['reject'] !== null ? (float) $row['reject'] : null;
                        $balance = $planQty - ($okQty ?? 0) - ($rejectQty ?? 0);
                        $balanceColor = $balance > 0 ? 'text-slate-600' : ($balance < 0 ? 'text-indigo-600' : 'text-slate-300');

                        $totalOutput = ($okQty ?? 0) + ($repairQty ?? 0) + ($rejectQty ?? 0);
                        $rawStatus = strtolower((string) ($row['status'] ?? 'pending'));
                        if ($rawStatus === 'completed' || $rawStatus === 'done' || ($planQty > 0 && $totalOutput >= $planQty)) {
                            $statusLabel = 'DONE';
                            $statusColor = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                            $dotColor = 'bg-emerald-500';
                        } elseif ($rawStatus === 'running' || $totalOutput > 0) {
                            $statusLabel = 'RUNNING';
                            $statusColor = 'bg-blue-100 text-blue-700 border-blue-200';
                            $dotColor = 'bg-blue-500';
                        } else {
                            $statusLabel = 'PENDING';
                            $statusColor = 'bg-amber-100 text-amber-700 border-amber-200';
                            $dotColor = 'bg-amber-500';
                        }

                        $isFieldChanged = fn (string $f) => isset($changedFields[$f]);
                        $otherVal = function (string $f) use ($changedFields, $mode) {
                            if (!isset($changedFields[$f]) || !is_array($changedFields[$f])) {
                                return null;
                            }
                            return $mode === 'snapshot' ? ($changedFields[$f]['live'] ?? null) : ($changedFields[$f]['backup'] ?? null);
                        };
                    @endphp
                    <tr class="transition-colors border-b border-slate-100 {{ $rowHighlight }}">
                        {{-- Perbandingan Badge --}}
                        <td class="px-2.5 py-2.5 text-center border-r border-slate-100 whitespace-nowrap">
                            <span class="inline-block px-2 py-0.5 rounded text-[9px] font-black uppercase border {{ $diffBadgeClass }}">
                                {{ $diffBadge }}
                            </span>
                        </td>

                        {{-- Press --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 whitespace-nowrap {{ $isFieldChanged('press_name') ? 'bg-amber-100/70 ring-1 ring-inset ring-amber-400' : '' }}">
                            <div class="text-xs font-black text-slate-800">{{ $pressVal ?: '—' }}</div>
                            @if($isFieldChanged('press_name'))
                                <div class="text-[9px] font-bold text-amber-800">vs {{ $otherVal('press_name') ?? '—' }}</div>
                            @endif
                        </td>

                        {{-- Shift --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 whitespace-nowrap {{ $isFieldChanged('shift_name') ? 'bg-amber-100/70 ring-1 ring-inset ring-amber-400' : '' }}">
                            <div class="text-xs font-bold text-slate-700">{{ $shiftVal ?: '—' }}</div>
                            @if($isFieldChanged('shift_name'))
                                <div class="text-[9px] font-bold text-amber-800">vs {{ $otherVal('shift_name') ?? '—' }}</div>
                            @endif
                        </td>

                        {{-- # Urutan --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 {{ $isFieldChanged('row_no') ? 'bg-amber-100/70 ring-1 ring-inset ring-amber-400' : '' }}">
                            <div class="text-xs font-black text-slate-700">{{ ($rowNoVal !== null && $rowNoVal !== '') ? $rowNoVal : '—' }}</div>
                            @if($isFieldChanged('row_no'))
                                <div class="text-[9px] font-bold text-amber-800">vs #{{ $otherVal('row_no') ?? '—' }}</div>
                            @endif
                        </td>

                        {{-- Job Master --}}
                        <td class="px-4 py-2.5 border-r border-slate-100 {{ $isFieldChanged('job_master') ? 'bg-amber-100/70 ring-1 ring-inset ring-amber-400' : '' }}">
                            <div class="text-xs font-black text-slate-800">{{ $jobMaster ?: '—' }}</div>
                            @if($isFieldChanged('job_master'))
                                <div class="text-[9px] font-bold text-amber-800">vs {{ $otherVal('job_master') ?? '—' }}</div>
                            @endif
                        </td>

                        {{-- Type Plt --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100">
                            @if(!empty($row['type_plt']))
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black bg-indigo-100 text-indigo-700 uppercase tracking-tighter">{{ $row['type_plt'] }}</span>
                            @else
                                <span class="text-slate-300 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Qty/Plt --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 text-xs font-bold text-slate-600">
                            {{ isset($row['qty_plt']) && $row['qty_plt'] !== null ? number_format((float) $row['qty_plt']) : '—' }}
                        </td>

                        {{-- Tot. Plt --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 text-xs font-bold text-slate-500">
                            {{ isset($row['total_plt']) && $row['total_plt'] !== null ? round((float) $row['total_plt']) : '—' }}
                        </td>

                        {{-- Job No --}}
                        <td class="px-4 py-2.5 border-r border-slate-100 {{ $isFieldChanged('job_no') ? 'bg-amber-100/70 ring-1 ring-inset ring-amber-400' : '' }}">
                            <div class="text-xs font-bold text-slate-700">{{ $jobNo ?: '—' }}</div>
                            @if($isFieldChanged('job_no'))
                                <div class="text-[9px] font-bold text-amber-800">vs {{ $otherVal('job_no') ?? '—' }}</div>
                            @endif
                        </td>

                        {{-- Plan --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 bg-amber-50/20 {{ $isFieldChanged('plan') ? 'bg-amber-100 ring-1 ring-inset ring-amber-500' : '' }}">
                            <div class="text-xs font-black text-amber-700">{{ number_format($planQty) }}</div>
                            @if($isFieldChanged('plan'))
                                <div class="text-[9px] font-bold text-amber-900">vs {{ number_format((float) ($otherVal('plan') ?? 0)) }}</div>
                            @endif
                        </td>

                        {{-- OK --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 bg-emerald-50/20">
                            <span class="text-xs font-black text-emerald-600 tracking-tight">{{ $okQty !== null ? number_format($okQty) : '—' }}</span>
                        </td>

                        {{-- Repair --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 bg-yellow-50/20">
                            <span class="text-xs font-black text-yellow-600 tracking-tight">{{ $repairQty !== null ? number_format($repairQty) : '—' }}</span>
                        </td>

                        {{-- Reject --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 bg-rose-50/20">
                            <span class="text-xs font-black text-rose-600 tracking-tight">{{ $rejectQty !== null ? number_format($rejectQty) : '—' }}</span>
                        </td>

                        {{-- Balance --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-black text-xs {{ $balanceColor }}">
                            {{ number_format($balance) }}
                        </td>

                        {{-- Mesin --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 text-xs font-bold text-slate-600">
                            {{ isset($row['total_mesin']) && $row['total_mesin'] !== null ? $row['total_mesin'] : '—' }}
                        </td>

                        {{-- CT" --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 {{ $isFieldChanged('ct_detik') ? 'bg-amber-100/70' : '' }}">
                            <span class="text-xs font-bold text-slate-700">
                                {{ isset($row['ct_detik']) && $row['ct_detik'] !== null ? number_format((float) $row['ct_detik'], 1, '.', '') : '—' }}
                            </span>
                        </td>

                        {{-- Proc.Time --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-slate-500 text-xs {{ $isFieldChanged('process_time') ? 'bg-amber-100/70' : '' }}">
                            {{ isset($row['process_time']) && $row['process_time'] !== null ? (int) ceil((float) $row['process_time']) : '—' }}
                        </td>

                        {{-- Reg Act --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">
                            {{ isset($row['reg_active']) && $row['reg_active'] !== null ? $row['reg_active'] : '—' }}
                        </td>

                        {{-- DCT (Dandori) --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">
                            {{ isset($row['dct']) && $row['dct'] !== null ? $row['dct'] : '—' }}
                        </td>

                        {{-- MCT --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">
                            {{ isset($row['mct']) && $row['mct'] !== null ? $row['mct'] : '—' }}
                        </td>

                        {{-- Plan DCT --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-amber-600 text-xs bg-amber-50/30">
                            {{ isset($row['plan_dct']) && $row['plan_dct'] !== null ? $row['plan_dct'] : '—' }}
                        </td>

                        {{-- TPT --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-black text-amber-700 text-xs {{ $isFieldChanged('tpt') ? 'bg-amber-100/70' : '' }}">
                            {{ isset($row['tpt']) && $row['tpt'] !== null ? $row['tpt'] : '—' }}
                        </td>

                        {{-- GSPH --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-slate-500 text-xs {{ $isFieldChanged('gsph_item') ? 'bg-amber-100/70' : '' }}">
                            {{ isset($row['gsph_item']) && $row['gsph_item'] !== null ? number_format((float) $row['gsph_item']) : '—' }}
                        </td>

                        {{-- Start --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-mono text-xs font-bold text-indigo-600 {{ $isFieldChanged('start_time') ? 'bg-amber-100 ring-1 ring-inset ring-amber-400' : '' }}">
                            <div>{{ $row['start_time'] ?? '—' }}</div>
                            @if($isFieldChanged('start_time'))
                                <div class="text-[9px] font-bold text-amber-800">vs {{ $otherVal('start_time') ?: '—' }}</div>
                            @endif
                        </td>

                        {{-- Finish --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-mono text-xs font-bold text-indigo-600 {{ $isFieldChanged('finish_time') ? 'bg-amber-100 ring-1 ring-inset ring-amber-400' : '' }}">
                            <div>{{ $row['finish_time'] ?? '—' }}</div>
                            @if($isFieldChanged('finish_time'))
                                <div class="text-[9px] font-bold text-amber-800">vs {{ $otherVal('finish_time') ?: '—' }}</div>
                            @endif
                        </td>

                        {{-- Keterangan --}}
                        <td class="px-4 py-2.5 border-r border-slate-100 text-xs text-slate-600 {{ $isFieldChanged('keterangan') ? 'bg-amber-100/70' : '' }}">
                            {{ !empty($row['keterangan']) ? $row['keterangan'] : '—' }}
                        </td>

                        {{-- A1 - A4 --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ !empty($row['a1']) ? $row['a1'] : '—' }}</td>
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ !empty($row['a2']) ? $row['a2'] : '—' }}</td>
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ !empty($row['a3']) ? $row['a3'] : '—' }}</td>
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">{{ !empty($row['a4']) ? $row['a4'] : '—' }}</td>

                        {{-- DT (Downtime Menit) --}}
                        <td class="px-3 py-2.5 text-center border-r border-slate-100 font-bold text-slate-600 text-xs">
                            {{ isset($row['dt_menit']) && $row['dt_menit'] !== null && $row['dt_menit'] !== '' ? $row['dt_menit'] : '—' }}
                        </td>

                        {{-- Status Badge (Identical to Production Plan) --}}
                        <td class="px-3 py-2.5 text-center whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border {{ $statusColor }} shadow-sm">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                <span class="text-[9px] font-black tracking-widest">{{ $statusLabel }}</span>
                            </div>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="30" class="py-10 text-center text-slate-400 text-xs font-semibold">
                        Tidak ada baris jadwal untuk ditampilkan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

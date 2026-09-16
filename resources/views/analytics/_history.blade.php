@if($records->isNotEmpty())
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-700">History Produksi</h3>
        <span class="text-[10px] font-bold text-slate-400">{{ $records->total() }} records</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                    <th class="text-left px-5 py-3">Tanggal</th>
                    <th class="text-left px-5 py-3">Item</th>
                    <th class="text-left px-5 py-3">Line</th>
                    <th class="text-left px-5 py-3">Shift</th>
                    <th class="text-right px-5 py-3">Target</th>
                    <th class="text-center px-5 py-3">Progress</th>
                    <th class="text-right px-5 py-3">OK</th>
                    <th class="text-right px-5 py-3">Repair</th>
                    <th class="text-right px-5 py-3">Reject</th>
                    <th class="text-right px-5 py-3">Runtime</th>
                    <th class="text-right px-5 py-3">Achievement</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($records as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-3 font-medium text-slate-700">{{ \Carbon\Carbon::parse($r->work_date)->format('d M Y') }}</td>
                    <td class="px-5 py-3">
                        @if($r->jobMaster)
                            @php
                                $parts = explode('-', $r->jobMaster->job_number);
                                if (count($parts) > 1 && is_numeric(end($parts))) {
                                    array_pop($parts);
                                    $jobNo = implode('-', $parts);
                                } else {
                                    $jobNo = $r->jobMaster->job_number;
                                }
                            @endphp
                            <div class="font-bold text-slate-700 text-xs">{{ $jobNo }}</div>
                            <div class="text-[10px] text-slate-400 font-medium">{{ $r->jobMaster->job_name }}</div>
                        @else
                            <span class="text-slate-400 text-xs">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-slate-600">{{ $r->line ?? '-' }}</td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $r->shift === 'Pagi' ? 'bg-blue-100 text-blue-600' : 'bg-indigo-100 text-indigo-600' }}">{{ $r->shift ?? '-' }}</span>
                    </td>
                    @php
                        $targetQty = $r->target_qty ?: ($r->jobMaster?->target_qty ?? 0);
                    @endphp
                    <td class="px-5 py-3 text-right text-slate-600">{{ number_format($targetQty) }}</td>
                    @php
                        $shiftHourStart = $r->shift === 'Pagi' ? 6 : 15;
                        $shiftHourEnd = $r->shift === 'Pagi' ? 15 : 23;
                        $downtimes = $r->jobMaster?->downtimes?->filter(function($dt) use ($r, $shiftHourStart, $shiftHourEnd) {
                            $h = (int) \Carbon\Carbon::parse($dt->start_time)->format('G');
                            return $h >= $shiftHourStart && $h < $shiftHourEnd;
                        }) ?? collect();

                        $repairSec = $downtimes->where('jenis_downtime', 'repair')->sum('duration_seconds');
                        $prodSec = $r->runtime_seconds ?? 0;
                        $totalTimeline = max($repairSec + $prodSec, 1);
                        $repairPct = ($repairSec / $totalTimeline) * 100;
                        $prodPct = ($prodSec / $totalTimeline) * 100;
                        $finishPct = max(0, 100 - $repairPct - $prodPct);
                    @endphp
                    <td class="px-5 py-3 min-w-[180px]">
                        @if($targetQty > 0)
                        <div class="flex flex-col gap-1">
                            <div class="h-5 w-full bg-slate-100 rounded-full border border-slate-200 shadow-inner overflow-hidden flex">
                                @if($repairPct > 0)<div class="h-full bg-amber-500 transition-all" style="width: {{ $repairPct }}%" title="Repair {{ gmdate('H:i', $repairSec) }}"></div>@endif
                                @if($prodPct > 0)<div class="h-full bg-emerald-500 transition-all" style="width: {{ $prodPct }}%" title="Production {{ gmdate('H:i', $prodSec) }}"></div>@endif
                                @if($finishPct > 0)<div class="h-full bg-blue-500 transition-all" style="width: {{ $finishPct }}%" title="Finish"></div>@endif
                            </div>
                            <div class="flex flex-wrap gap-x-3 gap-y-0.5 text-[9px] text-slate-400">
                                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Repair {{ gmdate('H:i', $repairSec) }}</span>
                                <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Prod {{ gmdate('H:i', $prodSec) }}</span>
                            </div>
                            <div class="text-[10px] text-slate-500 font-medium">
                                OK {{ number_format($r->actual_ok ?? 0) }} · Repair {{ number_format($r->actual_repair ?? 0) }} · Reject {{ number_format($r->actual_reject ?? 0) }} · Target {{ number_format($targetQty) }}
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-slate-300">-</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right text-emerald-600 font-semibold">{{ number_format($r->actual_ok ?? 0) }}</td>
                    <td class="px-5 py-3 text-right text-amber-600 font-semibold">{{ number_format($r->actual_repair ?? 0) }}</td>
                    <td class="px-5 py-3 text-right text-red-600 font-semibold">{{ number_format($r->actual_reject ?? 0) }}</td>
                    <td class="px-5 py-3 text-right font-mono text-slate-600">{{ $r->runtime_seconds ? gmdate('H:i', $r->runtime_seconds) : '-' }}</td>
                    <td class="px-5 py-3 text-right font-bold {{ ($targetQty > 0 && ($r->actual_ok / $targetQty) * 100 >= 80) ? 'text-emerald-600' : 'text-slate-600' }}">
                        {{ $targetQty > 0 ? round(($r->actual_ok / $targetQty) * 100, 1) : 0 }}%
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
    <div class="px-5 py-4 border-t border-slate-100">
        {{ $records->links() }}
    </div>
    @endif
</div>
@else
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
    <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
    <p class="text-slate-500 font-bold">Tidak ada data produksi untuk periode ini.</p>
    <p class="text-xs text-slate-400 mt-1">Coba ubah rentang tanggal, line, atau shift yang dipilih.</p>
</div>
@endif

<div class="space-y-4">
    {{-- LEGEND --}}
    <div class="flex items-center gap-4 text-xs font-bold flex-wrap">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-blue-600"></span> Production</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-400"></span> Dandori</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-purple-500"></span> 1st Check</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-orange-500"></span> Try Out</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-slate-500"></span> Break Time</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500"></span> Downtime</span>
    </div>

    {{-- DANDORI & 1ST CHECK EVENTS --}}
    @if(isset($dandoris) && $dandoris->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-700">Dandori & 1st Check Events</h3>
            <span class="text-[10px] font-bold text-slate-400">{{ $dandoris->count() }} events</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <th class="text-left px-5 py-3">Waktu</th>
                        <th class="text-left px-5 py-3">Line</th>
                        <th class="text-left px-5 py-3">Job / Item</th>
                        <th class="text-left px-5 py-3">Kategori</th>
                        <th class="text-left px-5 py-3">Aktivitas</th>
                        <th class="text-right px-5 py-3">Durasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($dandoris as $d)
                    @php
                        $actNorm = \App\Services\ProductionTimelineService::normalizeActivityType($d->jenis_dandori ?: $d->activity);
                        $badgeColor = $actNorm === '1st_check' ? 'bg-purple-100 text-purple-700' : 'bg-amber-100 text-amber-700';
                        $dotColor = $actNorm === '1st_check' ? 'bg-purple-500' : 'bg-amber-400';
                        $lbl = $actNorm === '1st_check' ? '1ST CHECK' : 'DANDORI';
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ $d->start_time ? \Carbon\Carbon::parse($d->start_time)->format('d M H:i') : '-' }}</td>
                        <td class="px-5 py-3 font-medium text-slate-700">{{ $d->line ?: ($d->nextJob?->line ?? '-') }}</td>
                        <td class="px-5 py-3 text-slate-700 font-semibold">{{ $d->nextJob?->job_name ?: ($d->nextJob?->job_number ?? '-') }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badgeColor }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotColor }}"></span>
                                {{ $lbl }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-600 max-w-xs truncate">{{ $d->activity ?: '-' }}</td>
                        <td class="px-5 py-3 text-right font-mono text-slate-700">{{ round($d->duration_minutes, 1) }}m</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- DOWNTIME, BREAKTIME & TRYOUT EVENTS --}}
    @if($downtimes->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-700">Downtime, Break Time & Tryout Events</h3>
            <span class="text-[10px] font-bold text-slate-400">{{ $downtimes->count() }} events</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <th class="text-left px-5 py-3">Waktu</th>
                        <th class="text-left px-5 py-3">Line</th>
                        <th class="text-left px-5 py-3">Job / Item</th>
                        <th class="text-left px-5 py-3">Jenis</th>
                        <th class="text-left px-5 py-3">Problem / Catatan</th>
                        <th class="text-right px-5 py-3">Durasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($downtimes as $dt)
                    @php
                        $normType = \App\Services\ProductionTimelineService::normalizeActivityType($dt->jenis_downtime);
                        $badgeBg = match($normType) {
                            'tryout' => 'bg-orange-100 text-orange-700',
                            'break'  => 'bg-slate-100 text-slate-700',
                            'dandori'=> 'bg-amber-100 text-amber-700',
                            '1st_check'=> 'bg-purple-100 text-purple-700',
                            default  => 'bg-red-100 text-red-700',
                        };
                        $dotBg = match($normType) {
                            'tryout' => 'bg-orange-500',
                            'break'  => 'bg-slate-500',
                            'dandori'=> 'bg-amber-400',
                            '1st_check'=> 'bg-purple-500',
                            default  => 'bg-red-500',
                        };
                        $lblText = \App\Services\ProductionTimelineService::getActivityLabel($normType, $dt->jenis_downtime);
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ $dt->start_time ? \Carbon\Carbon::parse($dt->start_time)->format('d M H:i') : '-' }}</td>
                        <td class="px-5 py-3 font-medium text-slate-700">{{ $dt->jobMaster?->line ?? '-' }}</td>
                        <td class="px-5 py-3 text-slate-700 font-semibold">{{ $dt->jobMaster?->job_name ?: ($dt->jobMaster?->job_number ?? '-') }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badgeBg }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotBg }}"></span>
                                {{ strtoupper($lblText) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-600 max-w-xs truncate">{{ $dt->problem ?: ($dt->penyebab ?: '-') }}</td>
                        <td class="px-5 py-3 text-right font-mono text-slate-700">{{ $dt->duration_seconds ? gmdate('H:i:s', $dt->duration_seconds) : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- PRODUCTION LOGS --}}
    @if($logs->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-700">Production Logs</h3>
            <span class="text-[10px] font-bold text-slate-400">{{ $logs->count() }} entries</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <th class="text-left px-5 py-3">Waktu</th>
                        <th class="text-left px-5 py-3">Line</th>
                        <th class="text-right px-5 py-3">OK</th>
                        <th class="text-right px-5 py-3">Repair</th>
                        <th class="text-right px-5 py-3">Reject</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ $log->created_at ? $log->created_at->format('d M H:i:s') : '-' }}</td>
                        <td class="px-5 py-3 font-medium text-slate-700">{{ $log->jobMaster?->line ?? '-' }}</td>
                        <td class="px-5 py-3 text-right text-emerald-600 font-semibold">{{ number_format($log->ok_qty ?? 0) }}</td>
                        <td class="px-5 py-3 text-right text-amber-600 font-semibold">{{ number_format($log->repair_qty ?? 0) }}</td>
                        <td class="px-5 py-3 text-right text-red-600 font-semibold">{{ number_format($log->reject_qty ?? 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if((!isset($dandoris) || $dandoris->isEmpty()) && $downtimes->isEmpty() && $logs->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
        <p class="text-slate-500 font-bold">Tidak ada event untuk periode ini.</p>
        <p class="text-xs text-slate-400 mt-1">Coba ubah rentang tanggal atau line yang dipilih.</p>
    </div>
    @endif
</div>

<div class="space-y-4">
    {{-- LEGEND --}}
    <div class="flex items-center gap-4 text-xs font-bold">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500"></span> Repair</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500"></span> Reject</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span> Finish</span>
    </div>

    {{-- DOWNTIME EVENTS --}}
    @if($downtimes->isNotEmpty())
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-700">Downtime Events</h3>
            <span class="text-[10px] font-bold text-slate-400">{{ $downtimes->count() }} events</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        <th class="text-left px-5 py-3">Waktu</th>
                        <th class="text-left px-5 py-3">Line</th>
                        <th class="text-left px-5 py-3">Jenis</th>
                        <th class="text-left px-5 py-3">Problem</th>
                        <th class="text-right px-5 py-3">Durasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($downtimes as $dt)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-xs text-slate-500 font-mono">{{ $dt->start_time ? \Carbon\Carbon::parse($dt->start_time)->format('d M H:i') : '-' }}</td>
                        <td class="px-5 py-3 font-medium text-slate-700">{{ $dt->jobMaster?->line ?? '-' }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $dt->jenis_downtime === 'repair' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dt->jenis_downtime === 'repair' ? 'bg-amber-500' : 'bg-slate-400' }}"></span>
                                {{ strtoupper($dt->jenis_downtime ?? 'DOWN') }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-600 max-w-xs truncate">{{ $dt->problem ?? '-' }}</td>
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

    @if($downtimes->isEmpty() && $logs->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-10 text-center">
        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
        <p class="text-slate-500 font-bold">Tidak ada event untuk periode ini.</p>
        <p class="text-xs text-slate-400 mt-1">Coba ubah rentang tanggal atau line yang dipilih.</p>
    </div>
    @endif
</div>

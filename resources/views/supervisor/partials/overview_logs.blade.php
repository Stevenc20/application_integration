@forelse($latestProductions as $p)
<tr class="hover:bg-gray-50/50 transition-colors">
    <td class="px-6 py-4">
        <span class="text-xs font-bold text-gray-400">{{ $p->created_at->format('H:i') }}</span>
    </td>
    <td class="px-6 py-4">
        <div class="text-sm font-black text-gray-800">{{ $p->jobMaster->job_name ?? '-' }}</div>
        <div class="text-[10px] font-bold text-gray-400">{{ $p->jobMaster->job_number ?? '-' }}</div>
    </td>
    <td class="px-6 py-4">
        <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-[10px] font-bold uppercase tracking-widest">{{ $p->line ?? $p->jobMaster->line ?? '-' }}</span>
    </td>
    <td class="px-6 py-4 text-center">
        <span class="text-xs font-bold text-gray-600">{{ $p->shift ?: ($shift === 1 ? 'Shift Pagi' : 'Shift Malam') }}</span>
    </td>
    <td class="px-6 py-4 text-center">
        <span class="text-sm font-black text-green-600">{{ number_format($p->actual_ok) }}</span>
    </td>
    <td class="px-6 py-4 text-center">
        <span class="text-sm font-black text-red-600">{{ number_format($p->actual_reject) }}</span>
    </td>
    <td class="px-6 py-4 text-center">
        @php
            $ok = $p->actual_ok ?? 0;
            $repair = $p->actual_repair ?? 0;
            $reject = $p->actual_reject ?? 0;
            $total = $ok + $repair + $reject;
        @endphp
        @if($total > 0 && $reject > 0 && $ok == 0)
            <span class="px-2 py-0.5 rounded-full bg-red-100 text-red-600 text-[9px] font-black uppercase tracking-widest">REJECT</span>
        @elseif($total > 0 && $repair > 0 && $ok == 0)
            <span class="px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-600 text-[9px] font-black uppercase tracking-widest">REPAIR</span>
        @elseif($ok > 0)
            <span class="px-2 py-0.5 rounded-full bg-green-100 text-green-600 text-[9px] font-black uppercase tracking-widest">OK</span>
        @else
            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-400 text-[9px] font-black uppercase tracking-widest">PENDING</span>
        @endif
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm font-medium italic">No production logs recorded for this period</td>
</tr>
@endforelse

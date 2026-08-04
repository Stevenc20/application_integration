<div id="lkh-oee-cards" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
    @php
        $oeeVal = $totals['weighted_oee'] ?? 0;
        $oeeClass = 'from-red-50 to-red-100/50 border-red-200 text-red-700 bg-red-50 text-red-600';
        if ($oeeVal >= 85) $oeeClass = 'from-emerald-50 to-emerald-100/50 border-emerald-200 text-emerald-700 bg-emerald-50 text-emerald-600';
        elseif ($oeeVal >= 65) $oeeClass = 'from-amber-50 to-amber-100/50 border-amber-200 text-amber-700 bg-amber-50 text-amber-600';
    @endphp
    @foreach ([
        ['TOTAL STROKE', number_format($totals['total_stroke'] ?? 0), 'good+repair+reject'],
        ['SHIFT CYCLE TIME', number_format($totals['weighted_ct'] ?? 0,1).' <span class="text-xs font-semibold">sec</span>', 'Standard: '.number_format($totals['avg_plan_ct'] ?? 0,1).' s'],
        ['SHIFT GSPH', number_format($totals['weighted_gsph'] ?? 0,0), 'Target: '.number_format($summary['gsph_plan'] ?? 0,0)],
        ['AVAILABILITY', number_format($summary['availability'] ?? 0,1).'%', 'operating vs loading'],
        ['PERFORMANCE', number_format($summary['performance'] ?? 0,1).'%', 'ideal vs operating'],
    ] as $card)
    <div class="bg-gradient-to-br from-slate-50 to-slate-100/50 rounded-2xl border border-slate-200 p-4 shadow-sm relative overflow-hidden transition-all hover:shadow-md hover:border-slate-350">
        <div class="flex justify-between items-start">
            <div><span class="block text-[10px] font-black text-slate-500 uppercase tracking-wider">{{ $card[0] }}</span><span class="block text-xl font-black text-slate-900 mt-2">{!! $card[1] !!}</span></div>
        </div>
        <div class="mt-2 text-[10px] text-slate-400 font-bold uppercase tracking-wide">{{ $card[2] }}</div>
    </div>
    @endforeach
    <div class="bg-gradient-to-br {{ $oeeClass }} rounded-2xl border p-4 shadow-sm relative overflow-hidden transition-all hover:shadow-md">
        <div class="flex justify-between items-start">
            <div><span class="block text-[10px] font-black uppercase tracking-wider">SHIFT OEE RATING</span><span class="block text-xl font-black mt-2">{{ number_format($oeeVal,1) }}%</span></div>
        </div>
        <div class="mt-2 text-[10px] font-bold uppercase tracking-wide">
            @if ($oeeVal >= 85) WORLD CLASS OEE
            @elseif ($oeeVal >= 65) OK PERFORMANCE
            @else LOW EFFICIENCY
            @endif
        </div>
    </div>
</div>

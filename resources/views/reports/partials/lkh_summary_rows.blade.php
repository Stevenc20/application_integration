<tbody id="lkh-summary-tbody">
    @php
        $sumItemPlan = $summary['item_plan'] ?? 0;
        $sumItemAct = $summary['item_act'] ?? 0;
        $sumQtyPlan = $summary['qty_plan'] ?? 0;
        $sumQtyAct = $summary['qty_act'] ?? 0;
        $sumTptPlan = $summary['tpt_plan'] ?? 0;
        $sumTptAct = $summary['tpt_act'] ?? 0;
        $sumGsphPlan = $summary['gsph_plan'] ?? 0;
        $sumGsphAct = $summary['gsph_act'] ?? 0;
        $sumPassPlan = $summary['pass_rate_plan'] ?? 100;
        $sumPassAct = $summary['pass_rate_act'] ?? 0;
        $sumRejPlan = $summary['reject_rate_plan'] ?? 2;
        $sumRejAct = $summary['reject_rate_act'] ?? 0;
        $sumRepPlan = $summary['repair_rate_plan'] ?? 1;
        $sumRepAct = $summary['repair_rate_act'] ?? 0;
        $sumOee = $totals['weighted_oee'] ?? 0;
        $achievementPct = $sumQtyPlan > 0 ? ($sumQtyAct / $sumQtyPlan * 100) : 0;

        $pctClass = fn($v) => $v >= 100 ? 'good' : ($v >= 80 ? 'warn' : 'bad');
    @endphp
    <tr><td class="bg-gray-50 font-bold text-gray-700">ITEM PROCESS</td>
        <td class="val-plan">{{ number_format($sumItemPlan) }} Items</td>
        <td class="val-actual">{{ number_format($sumItemAct) }} Items</td>
        @php $pct = $sumItemPlan > 0 ? ($sumItemAct / $sumItemPlan * 100) : 0; @endphp
        <td class="val-pct {{ $pctClass($pct) }}">{{ number_format($pct,1) }}%</td>
    </tr>
    <tr><td class="bg-gray-50 font-bold text-gray-700">QTY PROCESS (PCS)</td>
        <td class="val-plan">{{ number_format($sumQtyPlan) }} Pcs</td>
        <td class="val-actual">{{ number_format($sumQtyAct) }} Pcs</td>
        @php $pct = $sumQtyPlan > 0 ? ($sumQtyAct / $sumQtyPlan * 100) : 0; @endphp
        <td class="val-pct {{ $pctClass($pct) }}">{{ number_format($pct,1) }}%</td>
    </tr>
    <tr><td class="bg-gray-50 font-bold text-gray-700">TPT PROCESS (MIN)</td>
        <td class="val-plan">{{ number_format($sumTptPlan,1) }} Min</td>
        <td class="val-actual">{{ number_format($sumTptAct,1) }} Min</td>
        @php $pct = $sumTptPlan > 0 ? ($sumTptAct / $sumTptPlan * 100) : 0; @endphp
        <td class="val-pct {{ $pctClass($pct) }}">{{ number_format($pct,1) }}%</td>
    </tr>
    <tr><td class="bg-gray-50 font-bold text-gray-700">GSPH</td>
        <td class="val-plan">{{ number_format($sumGsphPlan,0) }} Pcs/Hour</td>
        <td class="val-actual">{{ number_format($sumGsphAct,0) }} Pcs/Hour</td>
        @php $pct = $sumGsphPlan > 0 ? ($sumGsphAct / $sumGsphPlan * 100) : 0; @endphp
        <td class="val-pct {{ $pctClass($pct) }}">{{ number_format($pct,1) }}%</td>
    </tr>
    <tr><td class="bg-gray-50 font-bold text-gray-700">PASS RATE (%)</td>
        <td class="val-plan">{{ number_format($sumPassPlan,1) }}%</td>
        <td class="val-actual">{{ number_format($sumPassAct,1) }}%</td>
        @php $pct = $sumPassPlan > 0 ? ($sumPassAct / $sumPassPlan * 100) : 0; @endphp
        <td class="val-pct {{ $pctClass($pct) }}">{{ number_format($pct,1) }}%</td>
    </tr>
    <tr><td class="bg-gray-50 font-bold text-gray-700">REJECT RATE (%)</td>
        <td class="val-plan">{{ number_format($sumRejPlan,2) }}%</td>
        <td class="val-actual">{{ number_format($sumRejAct,2) }}%</td>
        @php $pct = $sumRejPlan > 0 ? ($sumRejAct / $sumRejPlan * 100) : 0; @endphp
        <td class="val-pct {{ $pct <= 80 ? 'good' : ($pct <= 100 ? 'warn' : 'bad') }}">{{ number_format($pct,1) }}%</td>
    </tr>
    <tr><td class="bg-gray-50 font-bold text-gray-700">REPAIR RATE (%)</td>
        <td class="val-plan">{{ number_format($sumRepPlan,2) }}%</td>
        <td class="val-actual">{{ number_format($sumRepAct,2) }}%</td>
        @php $pct = $sumRepPlan > 0 ? ($sumRepAct / $sumRepPlan * 100) : 0; @endphp
        <td class="val-pct {{ $pct <= 80 ? 'good' : ($pct <= 100 ? 'warn' : 'bad') }}">{{ number_format($pct,1) }}%</td>
    </tr>
    <tr><td class="bg-gray-50 font-bold text-gray-700">OEE (%)</td>
        <td class="val-plan">100.0%</td>
        <td class="val-actual">{{ number_format($sumOee,1) }}%</td>
        <td class="val-pct {{ $sumOee >= 85 ? 'good' : ($sumOee >= 65 ? 'warn' : 'bad') }}">{{ number_format($sumOee,1) }}%</td>
    </tr>
    <tr class="bg-gray-100 font-black text-lg">
        <td class="bg-gray-100 font-black text-gray-800">ACHIEVEMENT</td>
        <td class="bg-gray-100 text-gray-600">{{ number_format($sumQtyPlan,0) }}</td>
        <td class="bg-gray-100 val-actual">{{ number_format($sumQtyAct,0) }}</td>
        <td class="bg-gray-100 val-pct {{ $pctClass($achievementPct) }}">{{ number_format($achievementPct,1) }}%</td>
    </tr>
</tbody>

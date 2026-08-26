<div class="table-container">

    <!-- SECTION 1: SAFETY -->
    <div class="section-title">1. SAFETY</div>
    <table class="excel-table" style="width: 50%;">
        <thead>
            <tr>
                <th>ITEM</th>
                <th>TARGET</th>
                <th>ACTUAL</th>
                <th>DIFF</th>
                <th>HIGHLIGHT ISSUE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shiftData['safety'] ?? [] as $saf)
                <tr>
                    <td>{{ $saf['item'] }}</td>
                    <td class="center">{{ $saf['target'] }}</td>
                    <td class="center">{{ $saf['actual'] }}</td>
                    <td class="center {{ $saf['diff'] < 0 ? 'diff-negative' : '' }}">{{ $saf['diff'] }}</td>
                    <td>{{ $saf['issue'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- SECTION 2: REPAIR, REJECT, GSPH -->
    <div class="section-title">2. REPAIR, REJECT, GSPH</div>
    
    <div style="display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap;">
        <!-- REPAIR LINE -->
        <table class="excel-table" style="flex: 1; min-width: 400px;">
            <thead>
                <tr>
                    <th>REPAIR LINE</th>
                    <th>TARGET</th>
                    <th>ACTUAL</th>
                    <th>ACCUM</th>
                    <th>HIGHLIGHT ISSUE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shiftData['repair'] ?? [] as $rep)
                    <tr>
                        <td>{{ $rep['line_name'] }}</td>
                        <td class="center">{{ number_format($rep['target'], 1, ',', '.') }}%</td>
                        <td class="center">{{ number_format($rep['actual'], 2, ',', '.') }}%</td>
                        <td class="center">{{ number_format($rep['accum'], 2, ',', '.') }}%</td>
                        <td>{{ $rep['issue'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- GSPH LINE -->
        <table class="excel-table" style="flex: 1; min-width: 300px;">
            <thead>
                <tr>
                    <th>GSPH LINE</th>
                    <th>TARGET</th>
                    <th>PLAN</th>
                    <th>ACTUAL</th>
                    <th>DIFF</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shiftData['gsph'] ?? [] as $g)
                    <tr>
                        <td>{{ $g['line_name'] }}</td>
                        <td class="num">{{ $g['target'] }}</td>
                        <td class="num">{{ number_format($g['plan'], 0, '', '') }}</td>
                        <td class="num">{{ number_format($g['actual'], 0, '', '') }}</td>
                        <td class="num {{ $g['diff'] < 0 ? 'diff-negative' : '' }}">{{ number_format($g['diff'], 0, '', '') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- REJECT LINE -->
        <table class="excel-table" style="flex: 1; min-width: 500px;">
            <thead>
                <tr>
                    <th>REJECT LINE</th>
                    <th>TARGET</th>
                    <th>ACTUAL</th>
                    <th>COST</th>
                    <th>ACCUM</th>
                    <th>ACCUM COST</th>
                    <th>HIGHLIGHT ISSUE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shiftData['reject'] ?? [] as $rej)
                    <tr>
                        <td>{{ $rej['line_name'] }}</td>
                        <td class="center">{{ number_format($rej['target'], 2, ',', '.') }}%</td>
                        <td class="center">{{ number_format($rej['actual'], 2, ',', '.') }}%</td>
                        <td class="num">Rp {{ number_format($rej['cost'], 2, ',', '.') }}</td>
                        <td class="center">{{ number_format($rej['accum'], 2, ',', '.') }}%</td>
                        <td class="num">Rp {{ number_format($rej['accum_cost'], 2, ',', '.') }}</td>
                        <td>{{ $rej['issue'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- SECTION 3 / 4: PRODUCTIVITY -->
    <div class="section-title">{{ $shiftTitle }}</div>
    
    <table class="excel-table">
        <thead>
            <tr>
                <th style="width: 15%;">LINE</th>
                <th colspan="2" style="width: 12%;">PLAN</th>
                <th colspan="2" style="width: 12%;">ACTUAL OK</th>
                <th style="width: 8%;">DIFF</th>
                <th style="width: 10%;">FACTOR</th>
                <th style="width: 15%;">PROBLEM</th>
                <th style="width: 15%;">PENYEBAB</th>
                <th style="width: 15%;">COUNTER MEASURE</th>
                <th style="width: 6%;">DT</th>
            </tr>
        </thead>
        @foreach($shiftData['lines'] ?? [] as $line)
            <tbody>
                <!-- LINE SUMMARY -->
                <tr class="header-gray">
                    <td>LINE {{ $line['line_name'] }}</td>
                    <td class="num" style="border-right: none;">{{ $line['total_plan'] }}</td>
                    <td class="center" style="border-left: none; width: 4%;">{{ $line['count_plan'] }}</td>
                    <td class="num" style="border-right: none;">{{ $line['total_actual'] }}</td>
                    <td class="center" style="border-left: none; width: 4%;">{{ $line['count_actual'] }}</td>
                    @php
                        $lDiffClass = '';
                        if ($line['total_diff'] < 0) $lDiffClass = 'diff-negative';
                        elseif ($line['total_diff'] > 0) $lDiffClass = 'diff-positive';
                    @endphp
                    <td class="num {{ $lDiffClass }}">{{ $line['total_diff'] }}</td>
                    <td colspan="5"></td>
                </tr>
                
                <!-- LINE ITEMS -->
                @foreach($line['items'] as $item)
                    @php
                        $iDiffClass = '';
                        if ($item['diff'] < 0) $iDiffClass = 'diff-negative';
                        elseif ($item['diff'] > 0) $iDiffClass = 'diff-positive';
                        $rowspan = max(1, count($item['downtimes']));
                    @endphp
                    <tr>
                        <td class="indent" rowspan="{{ $rowspan }}">{{ $item['item_name'] }}</td>
                        <td class="num" colspan="2" rowspan="{{ $rowspan }}">{{ $item['plan'] }}</td>
                        <td class="num" colspan="2" rowspan="{{ $rowspan }}">{{ $item['actual'] }}</td>
                        <td class="num {{ $iDiffClass }}" rowspan="{{ $rowspan }}">{{ $item['diff'] }}</td>
                        
                        @if(count($item['downtimes']) > 0)
                            <td>{{ $item['downtimes'][0]['factor'] }}</td>
                            <td>{{ $item['downtimes'][0]['problem'] }}</td>
                            <td>{{ $item['downtimes'][0]['penyebab'] }}</td>
                            <td>{{ $item['downtimes'][0]['action'] }}</td>
                            <td class="num">{{ $item['downtimes'][0]['minutes'] }}</td>
                        @else
                            <td></td><td></td><td></td><td></td><td class="num"></td>
                        @endif
                    </tr>
                    
                    @if(count($item['downtimes']) > 1)
                        @for($i = 1; $i < count($item['downtimes']); $i++)
                        <tr>
                            <td>{{ $item['downtimes'][$i]['factor'] }}</td>
                            <td>{{ $item['downtimes'][$i]['problem'] }}</td>
                            <td>{{ $item['downtimes'][$i]['penyebab'] }}</td>
                            <td>{{ $item['downtimes'][$i]['action'] }}</td>
                            <td class="num">{{ $item['downtimes'][$i]['minutes'] }}</td>
                        </tr>
                        @endfor
                    @endif
                @endforeach
                
                <!-- UNACHIEVED ITEMS -->
                @if(count($line['unachieved_items']) > 0)
                    <tr class="header-gray" style="background-color: #fff2cc;">
                        <td colspan="11">Item tidak tercapai</td>
                    </tr>
                    @foreach($line['unachieved_items'] as $index => $uItem)
                        <tr>
                            <td class="indent">{{ $index + 1 }}. {{ $uItem['item_name'] }}</td>
                            <td class="num" colspan="2">{{ $uItem['plan'] }}</td>
                            <td class="num" colspan="2">{{ $uItem['actual'] }}</td>
                            <td class="num diff-negative">{{ $uItem['diff'] }}</td>
                            <td colspan="5"></td>
                        </tr>
                    @endforeach
                @endif
                
                <!-- TOTAL DOWNTIME -->
                <tr class="header-gray">
                    <td colspan="10" style="text-align: right; padding-right: 1rem;">TOTAL DOWNTIME</td>
                    <td class="num">{{ $line['total_downtime'] }}</td>
                </tr>
            </tbody>
        @endforeach
        
        @if(count($shiftData['lines'] ?? []) == 0)
            <tbody>
                <tr>
                    <td colspan="11" class="center" style="padding: 2rem; color: #666; font-style: italic;">
                        Belum ada data plan yang diupload dari PPC untuk shift ini pada tanggal ini.
                    </td>
                </tr>
            </tbody>
        @endif
    </table>
</div>

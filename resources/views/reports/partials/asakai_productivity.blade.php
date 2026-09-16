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
                        <td class="editable" contenteditable="true">{{ $item['downtimes'][0]['factor'] }}</td>
                        <td class="editable" contenteditable="true">{{ $item['downtimes'][0]['problem'] }}</td>
                        <td class="editable" contenteditable="true">{{ $item['downtimes'][0]['penyebab'] }}</td>
                        <td class="editable" contenteditable="true">{{ $item['downtimes'][0]['action'] }}</td>
                        <td class="num">{{ $item['downtimes'][0]['minutes'] }}</td>
                    @else
                        <td class="editable" contenteditable="true"></td>
                        <td class="editable" contenteditable="true"></td>
                        <td class="editable" contenteditable="true"></td>
                        <td class="editable" contenteditable="true"></td>
                        <td class="num"></td>
                    @endif
                </tr>
                
                @if(count($item['downtimes']) > 1)
                    @for($i = 1; $i < count($item['downtimes']); $i++)
                    <tr>
                        <td class="editable" contenteditable="true">{{ $item['downtimes'][$i]['factor'] }}</td>
                        <td class="editable" contenteditable="true">{{ $item['downtimes'][$i]['problem'] }}</td>
                        <td class="editable" contenteditable="true">{{ $item['downtimes'][$i]['penyebab'] }}</td>
                        <td class="editable" contenteditable="true">{{ $item['downtimes'][$i]['action'] }}</td>
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

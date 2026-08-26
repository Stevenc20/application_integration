<div class="line-section">
    <div class="line-header">
        <div class="line-name">LINE {{ $line['line_name'] }}</div>
        <div class="line-summary">
            <div class="summary-item">
                <span class="summary-label">PLAN</span>
                <span class="summary-val">{{ number_format($line['total_plan']) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">ACTUAL OK</span>
                <span class="summary-val">{{ number_format($line['total_actual']) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">DIFF</span>
                @php
                    $diffClass = 'neutral';
                    if ($line['total_diff'] > 0) $diffClass = 'positive';
                    elseif ($line['total_diff'] < 0) $diffClass = 'negative';
                @endphp
                <span class="summary-val val-diff {{ $diffClass }}">
                    {{ $line['total_diff'] > 0 ? '+' : '' }}{{ number_format($line['total_diff']) }}
                </span>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="asakai-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Item / Job</th>
                    <th class="num" style="width: 10%;">Plan</th>
                    <th class="num" style="width: 10%;">Actual</th>
                    <th class="num" style="width: 10%;">Diff</th>
                    <th style="width: 12%;">Factor</th>
                    <th style="width: 15%;">Problem</th>
                    <th style="width: 15%;">Counter Measure</th>
                    <th class="num" style="width: 8%;">DT (Mnt)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($line['items'] as $item)
                    @php
                        $iDiffClass = 'neutral';
                        if ($item['diff'] > 0) $iDiffClass = 'positive';
                        elseif ($item['diff'] < 0) $iDiffClass = 'negative';
                        
                        $rowspan = max(1, count($item['downtimes']));
                    @endphp
                    
                    <tr>
                        <td rowspan="{{ $rowspan }}"><strong>{{ $item['item_name'] }}</strong></td>
                        <td class="num" rowspan="{{ $rowspan }}">{{ number_format($item['plan']) }}</td>
                        <td class="num" rowspan="{{ $rowspan }}">{{ number_format($item['actual']) }}</td>
                        <td class="num val-diff {{ $iDiffClass }}" rowspan="{{ $rowspan }}">
                            {{ $item['diff'] > 0 ? '+' : '' }}{{ number_format($item['diff']) }}
                        </td>
                        
                        @if(count($item['downtimes']) > 0)
                            <td>{{ $item['downtimes'][0]['factor'] }}</td>
                            <td>
                                <div>{{ $item['downtimes'][0]['problem'] }}</div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">{{ $item['downtimes'][0]['penyebab'] }}</div>
                            </td>
                            <td>{{ $item['downtimes'][0]['action'] }}</td>
                            <td class="num val-diff negative">{{ $item['downtimes'][0]['minutes'] }}</td>
                        @else
                            <td>-</td>
                            <td>-</td>
                            <td>-</td>
                            <td class="num">-</td>
                        @endif
                    </tr>
                    
                    @if(count($item['downtimes']) > 1)
                        @for($i = 1; $i < count($item['downtimes']); $i++)
                        <tr>
                            <td>{{ $item['downtimes'][$i]['factor'] }}</td>
                            <td>
                                <div>{{ $item['downtimes'][$i]['problem'] }}</div>
                                <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">{{ $item['downtimes'][$i]['penyebab'] }}</div>
                            </td>
                            <td>{{ $item['downtimes'][$i]['action'] }}</td>
                            <td class="num val-diff negative">{{ $item['downtimes'][$i]['minutes'] }}</td>
                        </tr>
                        @endfor
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>
    
    @if(count($line['unachieved_items']) > 0)
    <div class="unachieved-section">
        <div class="unachieved-title">Item Tidak Tercapai</div>
        <table class="unachieved-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Item / Job</th>
                    <th class="num" style="width: 15%;">Plan</th>
                    <th class="num" style="width: 15%;">Actual</th>
                    <th class="num" style="width: 20%;">Diff</th>
                </tr>
            </thead>
            <tbody>
                @foreach($line['unachieved_items'] as $uItem)
                <tr>
                    <td><strong>{{ $uItem['item_name'] }}</strong></td>
                    <td class="num">{{ number_format($uItem['plan']) }}</td>
                    <td class="num">{{ number_format($uItem['actual']) }}</td>
                    <td class="num val-diff negative">{{ number_format($uItem['diff']) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="total-dt">
        <span class="dt-label">TOTAL DOWNTIME (MENIT)</span>
        <span class="dt-val">{{ number_format($line['total_downtime']) }}</span>
    </div>
</div>

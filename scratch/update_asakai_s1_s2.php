<?php

$file = 'resources/views/reports/asakai.blade.php';
$content = file_get_contents($file);

$search = '/<!-- SECTION 1: SAFETY -->(.*?)<!-- SECTION 3: PRODUCTIVITY SHIFT 1 -->/s';

$replacement = <<< 'HTML'
<!-- SECTION 1: SAFETY -->
        <div class="section-title">1. SAFETY</div>
        <table class="excel-table" style="width: 50%; min-width: 500px;">
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
                @foreach($shift1['safety'] ?? [] as $saf)
                    @php
                        $diffClass = '';
                        if ($saf['diff'] < 0) $diffClass = 'diff-negative';
                        elseif ($saf['diff'] > 0) $diffClass = 'diff-positive';
                    @endphp
                    <tr>
                        <td>{{ $saf['item'] }}</td>
                        <td class="center editable" contenteditable="true">{{ $saf['target'] }}</td>
                        <td class="center editable" contenteditable="true">{{ $saf['actual'] }}</td>
                        <td class="center editable {{ $diffClass }} dynamic-diff" contenteditable="true">{{ $saf['diff'] }}</td>
                        <td class="editable" contenteditable="true">{{ $saf['issue'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- SECTION 2: REPAIR, REJECT, GSPH -->
        <div class="section-title">2. REPAIR, REJECT, GSPH</div>
        
        <div class="flex-row">
            <!-- REPAIR LINE -->
            <table class="excel-table flex-1" style="min-width: 350px;">
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
                    @foreach($shift1['repair'] ?? [] as $rep)
                        <tr>
                            <td>{{ $rep['line_name'] }}</td>
                            <td class="center editable" contenteditable="true">{{ number_format($rep['target'], 1, ',', '.') }}%</td>
                            <td class="center editable" contenteditable="true">{{ number_format($rep['actual'], 2, ',', '.') }}%</td>
                            <td class="center editable" contenteditable="true">{{ number_format($rep['accum'], 2, ',', '.') }}%</td>
                            <td class="editable" contenteditable="true">{{ $rep['issue'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- GSPH LINE -->
            <table class="excel-table flex-1" style="min-width: 300px;">
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
                    @foreach($shift1['gsph'] ?? [] as $g)
                        @php
                            $diffClass = '';
                            if ($g['diff'] < 0) $diffClass = 'diff-negative';
                            elseif ($g['diff'] > 0) $diffClass = 'diff-positive';
                        @endphp
                        <tr>
                            <td>{{ $g['line_name'] }}</td>
                            <td class="num editable" contenteditable="true">{{ $g['target'] }}</td>
                            <td class="num editable" contenteditable="true">{{ number_format($g['plan'], 0, '', '') }}</td>
                            <td class="num editable" contenteditable="true">{{ number_format($g['actual'], 0, '', '') }}</td>
                            <td class="num editable {{ $diffClass }} dynamic-diff" contenteditable="true">{{ number_format($g['diff'], 0, '', '') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- REJECT LINE -->
            <table class="excel-table flex-1" style="min-width: 450px;">
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
                    @foreach($shift1['reject'] ?? [] as $rej)
                        <tr>
                            <td>{{ $rej['line_name'] }}</td>
                            <td class="center editable" contenteditable="true">{{ number_format($rej['target'], 2, ',', '.') }}%</td>
                            <td class="center editable" contenteditable="true">{{ number_format($rej['actual'], 2, ',', '.') }}%</td>
                            <td class="num editable" contenteditable="true">Rp {{ number_format($rej['cost'], 2, ',', '.') }}</td>
                            <td class="center editable" contenteditable="true">{{ number_format($rej['accum'], 2, ',', '.') }}%</td>
                            <td class="num editable" contenteditable="true">Rp {{ number_format($rej['accum_cost'], 2, ',', '.') }}</td>
                            <td class="editable" contenteditable="true">{{ $rej['issue'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- SECTION 3: PRODUCTIVITY SHIFT 1 -->
HTML;

$newContent = preg_replace($search, $replacement, $content);
file_put_contents($file, $newContent);

echo "Updated asakai.blade.php successfully!";

@extends('layouts.supervisor')

@section('title', 'ASAKAI Productivity Report')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #000; }
    
    .asakai-header {
        margin-bottom: 1.5rem;
    }
    
    .asakai-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #000;
        margin-bottom: 0.25rem;
    }
    
    .asakai-subtitle {
        font-size: 0.9rem;
        color: #333;
    }

    .asakai-meta {
        display: flex;
        gap: 1.5rem;
        margin-top: 1rem;
        padding: 0.75rem 1rem;
        background: #fff;
        border: 1px solid #ccc;
        align-items: center;
    }

    .date-filter-form {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .date-input {
        border: 1px solid #999;
        padding: 0.25rem 0.5rem;
        font-size: 0.85rem;
    }

    .btn-filter {
        background-color: #e5e7eb;
        color: #000;
        border: 1px solid #999;
        padding: 0.25rem 0.75rem;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.85rem;
    }
    .btn-filter:hover { background-color: #d1d5db; }

    /* Tabs */
    .asakai-tabs {
        display: flex;
        gap: 0.25rem;
        margin-bottom: 1rem;
        border-bottom: 2px solid #000;
    }

    .tab-btn {
        padding: 0.5rem 1rem;
        background: #f3f4f6;
        border: 1px solid #ccc;
        border-bottom: none;
        font-weight: 600;
        font-size: 0.9rem;
        color: #333;
        cursor: pointer;
    }

    .tab-btn.active {
        background: #fff;
        color: #000;
        border: 2px solid #000;
        border-bottom: 2px solid #fff;
        margin-bottom: -2px;
    }

    .tab-content { display: none; }
    .tab-content.active { display: block; }

    /* Excel Table Styling */
    .table-container {
        width: 100%;
        overflow-x: auto;
        background: #fff;
        padding-bottom: 2rem;
    }

    .section-title {
        font-size: 1rem;
        font-weight: bold;
        margin: 1rem 0 0.5rem 0;
        text-transform: uppercase;
    }

    .excel-table {
        width: 100%;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        font-size: 0.8rem;
        color: #000;
        min-width: 1000px; /* Ensure it doesn't squish too much */
    }

    .excel-table th, .excel-table td {
        border: 1px solid #888;
        padding: 4px 6px;
        vertical-align: middle;
    }

    .excel-table th {
        background-color: #FFC000; /* Excel yellow/orange */
        font-weight: bold;
        text-align: center;
        text-transform: uppercase;
    }

    .excel-table td.num {
        text-align: center;
    }
    
    .excel-table td.indent {
        padding-left: 1rem;
    }

    .line-header-row td {
        font-weight: bold;
        background-color: #f2f2f2;
    }

    .unachieved-header td {
        font-weight: bold;
    }
    
    .diff-negative { color: red; font-weight: bold; }
    .diff-positive { color: #16a34a; }
    .diff-zero { color: #000; }
    
    .dt-row td {
        font-weight: bold;
        background-color: #f2f2f2;
    }

    .empty-state {
        padding: 2rem;
        text-align: center;
        color: #666;
        border: 1px solid #ccc;
        background: #fff;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper" style="padding: 1.5rem;">

    <div class="asakai-header">
        <h1 class="asakai-title">ASAKAI Productivity Stamping</h1>
        <div class="asakai-subtitle">Reporting System - Produksi Aktual vs Plan</div>
        
        <div class="asakai-meta">
            <form action="{{ route('supervisor.reports.asakai') }}" method="GET" class="date-filter-form">
                <div>
                    <label for="date" style="font-size: 0.85rem; font-weight: bold;">Tanggal Report (Cut-off):</label>
                    <input type="date" name="date" id="date" value="{{ $reportDate }}" class="date-input">
                </div>
                <button type="submit" class="btn-filter">Update Data</button>
            </form>
            <div style="margin-left: auto; font-size: 0.85rem;">
                <strong>Shift 1:</strong> {{ \Carbon\Carbon::parse($shift1Date)->format('d M Y') }} &nbsp;|&nbsp; 
                <strong>Shift 2:</strong> {{ \Carbon\Carbon::parse($shift2Date)->format('d M Y') }}
            </div>
        </div>
    </div>

    <!-- TABS -->
    <div class="asakai-tabs">
        <button class="tab-btn active" onclick="switchTab('shift1')">SHIFT 1</button>
        <button class="tab-btn" onclick="switchTab('shift2')">SHIFT 2</button>
    </div>

    <!-- SHIFT 1 CONTENT -->
    <div id="shift1" class="tab-content active">
        @if(count($shift1Data) == 0)
            <div class="empty-state">Tidak ada data plan/aktual untuk Shift 1 pada tanggal ini.</div>
        @else
            <div class="table-container">
                <div class="section-title">3. PRODUCTIVITY STAMPING SHIFT 1</div>
                <table class="excel-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">LINE</th>
                            <th style="width: 8%;">PLAN</th>
                            <th style="width: 8%;">ACTUAL OK</th>
                            <th style="width: 8%;">DIFF</th>
                            <th style="width: 10%;">FACTOR</th>
                            <th style="width: 15%;">PROBLEM</th>
                            <th style="width: 15%;">PENYEBAB</th>
                            <th style="width: 15%;">COUNTER MEASURE</th>
                            <th style="width: 6%;">DT</th>
                        </tr>
                    </thead>
                    @foreach($shift1Data as $line)
                        <tbody>
                            <!-- LINE SUMMARY -->
                            <tr class="line-header-row">
                                <td>LINE {{ $line['line_name'] }}</td>
                                <td class="num">{{ $line['total_plan'] }}</td>
                                <td class="num">{{ $line['total_actual'] }}</td>
                                @php
                                    $lDiffClass = 'diff-zero';
                                    if ($line['total_diff'] < 0) $lDiffClass = 'diff-negative';
                                    elseif ($line['total_diff'] > 0) $lDiffClass = 'diff-positive';
                                @endphp
                                <td class="num {{ $lDiffClass }}">{{ $line['total_diff'] }}</td>
                                <td colspan="5"></td>
                            </tr>
                            
                            <!-- LINE ITEMS -->
                            @foreach($line['items'] as $item)
                                @php
                                    $iDiffClass = 'diff-zero';
                                    if ($item['diff'] < 0) $iDiffClass = 'diff-negative';
                                    elseif ($item['diff'] > 0) $iDiffClass = 'diff-positive';
                                    
                                    $rowspan = max(1, count($item['downtimes']));
                                @endphp
                                <tr>
                                    <td class="indent" rowspan="{{ $rowspan }}">{{ $item['item_name'] }}</td>
                                    <td class="num" rowspan="{{ $rowspan }}">{{ $item['plan'] }}</td>
                                    <td class="num" rowspan="{{ $rowspan }}">{{ $item['actual'] }}</td>
                                    <td class="num {{ $iDiffClass }}" rowspan="{{ $rowspan }}">{{ $item['diff'] }}</td>
                                    
                                    @if(count($item['downtimes']) > 0)
                                        <td>{{ $item['downtimes'][0]['factor'] }}</td>
                                        <td>{{ $item['downtimes'][0]['problem'] }}</td>
                                        <td>{{ $item['downtimes'][0]['penyebab'] }}</td>
                                        <td>{{ $item['downtimes'][0]['action'] }}</td>
                                        <td class="num">{{ $item['downtimes'][0]['minutes'] }}</td>
                                    @else
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="num"></td>
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
                                <tr class="unachieved-header">
                                    <td colspan="9">Item tidak tercapai</td>
                                </tr>
                                @foreach($line['unachieved_items'] as $index => $uItem)
                                    <tr>
                                        <td class="indent">{{ $index + 1 }}. {{ $uItem['item_name'] }}</td>
                                        <td class="num">{{ $uItem['plan'] }}</td>
                                        <td class="num">{{ $uItem['actual'] }}</td>
                                        <td class="num diff-negative">{{ $uItem['diff'] }}</td>
                                        <td colspan="5"></td>
                                    </tr>
                                @endforeach
                            @endif
                            
                            <!-- TOTAL DOWNTIME -->
                            <tr class="dt-row">
                                <td colspan="8" style="text-align: right; padding-right: 1rem;">TOTAL DOWNTIME</td>
                                <td class="num">{{ $line['total_downtime'] }}</td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </div>

    <!-- SHIFT 2 CONTENT -->
    <div id="shift2" class="tab-content">
        @if(count($shift2Data) == 0)
            <div class="empty-state">Tidak ada data plan/aktual untuk Shift 2 pada tanggal ini.</div>
        @else
            <div class="table-container">
                <div class="section-title">4. PRODUCTIVITY STAMPING SHIFT 2</div>
                <table class="excel-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">LINE</th>
                            <th style="width: 8%;">PLAN</th>
                            <th style="width: 8%;">ACTUAL OK</th>
                            <th style="width: 8%;">DIFF</th>
                            <th style="width: 10%;">FACTOR</th>
                            <th style="width: 15%;">PROBLEM</th>
                            <th style="width: 15%;">PENYEBAB</th>
                            <th style="width: 15%;">COUNTER MEASURE</th>
                            <th style="width: 6%;">DT</th>
                        </tr>
                    </thead>
                    @foreach($shift2Data as $line)
                        <tbody>
                            <!-- LINE SUMMARY -->
                            <tr class="line-header-row">
                                <td>LINE {{ $line['line_name'] }}</td>
                                <td class="num">{{ $line['total_plan'] }}</td>
                                <td class="num">{{ $line['total_actual'] }}</td>
                                @php
                                    $lDiffClass = 'diff-zero';
                                    if ($line['total_diff'] < 0) $lDiffClass = 'diff-negative';
                                    elseif ($line['total_diff'] > 0) $lDiffClass = 'diff-positive';
                                @endphp
                                <td class="num {{ $lDiffClass }}">{{ $line['total_diff'] }}</td>
                                <td colspan="5"></td>
                            </tr>
                            
                            <!-- LINE ITEMS -->
                            @foreach($line['items'] as $item)
                                @php
                                    $iDiffClass = 'diff-zero';
                                    if ($item['diff'] < 0) $iDiffClass = 'diff-negative';
                                    elseif ($item['diff'] > 0) $iDiffClass = 'diff-positive';
                                    
                                    $rowspan = max(1, count($item['downtimes']));
                                @endphp
                                <tr>
                                    <td class="indent" rowspan="{{ $rowspan }}">{{ $item['item_name'] }}</td>
                                    <td class="num" rowspan="{{ $rowspan }}">{{ $item['plan'] }}</td>
                                    <td class="num" rowspan="{{ $rowspan }}">{{ $item['actual'] }}</td>
                                    <td class="num {{ $iDiffClass }}" rowspan="{{ $rowspan }}">{{ $item['diff'] }}</td>
                                    
                                    @if(count($item['downtimes']) > 0)
                                        <td>{{ $item['downtimes'][0]['factor'] }}</td>
                                        <td>{{ $item['downtimes'][0]['problem'] }}</td>
                                        <td>{{ $item['downtimes'][0]['penyebab'] }}</td>
                                        <td>{{ $item['downtimes'][0]['action'] }}</td>
                                        <td class="num">{{ $item['downtimes'][0]['minutes'] }}</td>
                                    @else
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="num"></td>
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
                                <tr class="unachieved-header">
                                    <td colspan="9">Item tidak tercapai</td>
                                </tr>
                                @foreach($line['unachieved_items'] as $index => $uItem)
                                    <tr>
                                        <td class="indent">{{ $index + 1 }}. {{ $uItem['item_name'] }}</td>
                                        <td class="num">{{ $uItem['plan'] }}</td>
                                        <td class="num">{{ $uItem['actual'] }}</td>
                                        <td class="num diff-negative">{{ $uItem['diff'] }}</td>
                                        <td colspan="5"></td>
                                    </tr>
                                @endforeach
                            @endif
                            
                            <!-- TOTAL DOWNTIME -->
                            <tr class="dt-row">
                                <td colspan="8" style="text-align: right; padding-right: 1rem;">TOTAL DOWNTIME</td>
                                <td class="num">{{ $line['total_downtime'] }}</td>
                            </tr>
                        </tbody>
                    @endforeach
                </table>
            </div>
        @endif
    </div>

</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
    
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>
@endsection

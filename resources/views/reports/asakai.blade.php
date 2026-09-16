@extends('layouts.supervisor')

@section('title', 'ASAKAI Productivity Report')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Arial', 'Inter', sans-serif; background-color: #f8fafc; color: #000; }
    
    .asakai-header { margin-bottom: 1.5rem; }
    .asakai-title { font-size: 1.25rem; font-weight: bold; color: #000; margin-bottom: 0.25rem; }
    .asakai-subtitle { font-size: 0.9rem; color: #333; }
    .asakai-meta {
        display: flex; gap: 1.5rem; margin-top: 1rem; padding: 0.75rem 1rem;
        background: #fff; border: 1px solid #ccc; align-items: center;
    }
    .date-filter-form { display: flex; align-items: center; gap: 1rem; }
    .date-input { border: 1px solid #999; padding: 0.25rem 0.5rem; font-size: 0.85rem; }
    .btn-filter {
        background-color: #e5e7eb; color: #000; border: 1px solid #999;
        padding: 0.25rem 0.75rem; font-weight: bold; cursor: pointer; font-size: 0.85rem;
    }

    .table-container { width: 100%; overflow-x: auto; background: #fff; padding-bottom: 2rem; margin-top: 1rem; }
    .section-title { font-size: 1rem; font-weight: bold; margin: 1.5rem 0 0.5rem 0; text-transform: uppercase; }

    .excel-table {
        width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;
        font-size: 0.8rem; color: #000; margin-bottom: 1rem;
    }
    .excel-table th, .excel-table td { border: 1px solid #888; padding: 4px 6px; vertical-align: middle; }
    .excel-table th { background-color: #FFC000; font-weight: bold; text-align: center; text-transform: uppercase; }
    .excel-table td.num { text-align: center; } /* Rata tengah sesuai request */
    .excel-table td.center { text-align: center; }
    .excel-table td.indent { padding-left: 1rem; }
    .header-gray { background-color: #e2efda; font-weight: bold; }
    .diff-negative { color: red; }
    .diff-positive { color: #16a34a; }
    
    .editable {
        cursor: text;
        transition: background 0.2s;
    }
    .editable:hover {
        background-color: #fef9c3; /* highlight kuning tipis saat di-hover */
    }
    .editable:focus {
        background-color: #fff;
        outline: 2px solid #3b82f6;
        outline-offset: -2px;
    }

    .flex-row { display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap; }
    .flex-1 { flex: 1; }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dynamic diff coloring for elements with dynamic-diff class
        const diffCells = document.querySelectorAll('.dynamic-diff');
        
        function updateColor(cell) {
            const val = parseFloat(cell.innerText.replace(/[^0-9.-]/g, ''));
            if (!isNaN(val)) {
                if (val < 0) {
                    cell.classList.add('diff-negative');
                    cell.classList.remove('diff-positive');
                } else if (val > 0) {
                    cell.classList.add('diff-positive');
                    cell.classList.remove('diff-negative');
                } else {
                    cell.classList.remove('diff-negative', 'diff-positive');
                }
            }
        }
        
        diffCells.forEach(cell => {
            // Apply initial color
            updateColor(cell);
            
            // Listen for changes
            cell.addEventListener('input', function() {
                updateColor(cell);
            });
        });
    });
</script>
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

    <div class="table-container">

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
        @include('reports.partials.asakai_productivity', ['shiftData' => $shift1, 'shiftTitle' => '3. PRODUCTIVITY STAMPING SHIFT 1'])

        <!-- SECTION 4: PRODUCTIVITY SHIFT 2 -->
        @include('reports.partials.asakai_productivity', ['shiftData' => $shift2, 'shiftTitle' => '4. PRODUCTIVITY STAMPING SHIFT 2'])

        <!-- SECTION 5: PENCAPAIAN SUB-ASSY -->
        <div class="section-title">5. PENCAPAIAN SUB-ASSY</div>
        <table class="excel-table" style="width: 100%;">
            <thead>
                <tr>
                    <th style="width: 15%;">LINE</th>
                    <th style="width: 10%;">PLAN SHIFT 1</th>
                    <th style="width: 10%;">PLAN SHIFT 2</th>
                    <th style="width: 10%;">PLAN TOTAL</th>
                    <th style="width: 10%;">ACTUAL</th>
                    <th style="width: 10%;">DIFF</th>
                    <th style="width: 25%;">HIGHLIGHT ISSUE</th>
                    <th style="width: 10%;">D/T</th>
                </tr>
            </thead>
            <tbody>
                @foreach($subAssy ?? [] as $sa)
                    <tr>
                        <td>{{ $sa['line_name'] }}</td>
                        <td class="num">{{ $sa['plan_shift_1'] }}</td>
                        <td class="num">{{ $sa['plan_shift_2'] }}</td>
                        <td class="num">{{ $sa['plan_total'] }}</td>
                        <td class="num">{{ $sa['actual'] }}</td>
                        <td class="num {{ $sa['diff'] < 0 ? 'diff-negative' : '' }}">{{ $sa['diff'] }}</td>
                        <td>{{ $sa['issue'] }}</td>
                        <td class="num">{{ $sa['dt'] }}</td>
                    </tr>
                @endforeach
                <tr class="header-gray">
                    <td colspan="5" class="num">TOTAL DIFF</td>
                    <td class="num">0</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="header-gray">
                    <td colspan="7" class="num">TOTAL DOWNTIME</td>
                    <td class="num">0</td>
                </tr>
            </tbody>
        </table>

        <!-- SECTION 6: SPOT / MAN HOURS -->
        <div class="section-title">SPOT / MAN HOURS</div>
        <table class="excel-table" style="width: 60%; min-width: 600px;">
            <thead>
                <tr>
                    <th>SPOT / MAN HOURS</th>
                    <th>TARGET</th>
                    <th>PLAN</th>
                    <th>ACTUAL</th>
                    <th>DIFF</th>
                    <th>ACCUM</th>
                    <th>HIGHLIGHT ISSUE</th>
                </tr>
            </thead>
            <tbody>
                @foreach($spot ?? [] as $sp)
                    <tr class="{{ $sp['item'] == 'TOTAL' ? 'header-gray' : '' }}">
                        <td>{{ $sp['item'] }}</td>
                        <td class="center">{{ $sp['target'] }}</td>
                        <td class="num">{{ $sp['plan'] }}</td>
                        <td class="num">{{ $sp['actual'] }}</td>
                        <td class="num {{ $sp['diff'] < 0 ? 'diff-negative' : '' }}">{{ $sp['diff'] }}</td>
                        <td class="num">{{ $sp['accum'] }}</td>
                        <td>{{ $sp['issue'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

    </div>

</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Dynamic diff coloring for elements with dynamic-diff class
        const diffCells = document.querySelectorAll('.dynamic-diff');
        
        function updateColor(cell) {
            const val = parseFloat(cell.innerText.replace(/[^0-9.-]/g, ''));
            if (!isNaN(val)) {
                if (val < 0) {
                    cell.classList.add('diff-negative');
                    cell.classList.remove('diff-positive');
                } else if (val > 0) {
                    cell.classList.add('diff-positive');
                    cell.classList.remove('diff-negative');
                } else {
                    cell.classList.remove('diff-negative', 'diff-positive');
                }
            }
        }
        
        diffCells.forEach(cell => {
            // Apply initial color
            updateColor(cell);
            
            // Listen for changes
            cell.addEventListener('input', function() {
                updateColor(cell);
            });
        });
    });
</script>
@endsection
@extends('layouts.supervisor')

@section('title', 'ASAKAI Productivity Report')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Arial', 'Inter', sans-serif; background-color: #f8fafc; color: #000; }
    
    .asakai-header {
        margin-bottom: 1.5rem;
    }
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
    .btn-filter:hover { background-color: #d1d5db; }

    /* Tabs */
    .asakai-tabs { display: flex; gap: 0.25rem; margin-bottom: 1rem; border-bottom: 2px solid #000; }
    .tab-btn {
        padding: 0.5rem 1rem; background: #f3f4f6; border: 1px solid #ccc;
        border-bottom: none; font-weight: bold; font-size: 0.9rem; color: #333; cursor: pointer;
    }
    .tab-btn.active {
        background: #fff; color: #000; border: 2px solid #000; border-bottom: 2px solid #fff; margin-bottom: -2px;
    }
    .tab-content { display: none; }
    .tab-content.active { display: block; }

    /* Excel Table Styling */
    .table-container { width: 100%; overflow-x: auto; background: #fff; padding-bottom: 2rem; }
    .section-title { font-size: 1rem; font-weight: bold; margin: 1.5rem 0 0.5rem 0; text-transform: uppercase; }

    .excel-table {
        width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;
        font-size: 0.8rem; color: #000; min-width: 800px; margin-bottom: 1rem;
    }
    .excel-table th, .excel-table td {
        border: 1px solid #888; padding: 4px 6px; vertical-align: middle;
    }
    .excel-table th {
        background-color: #FFC000; font-weight: bold; text-align: center; text-transform: uppercase;
    }
    .excel-table td.num { text-align: right; }
    .excel-table td.center { text-align: center; }
    .excel-table td.indent { padding-left: 1rem; }

    .header-gray { background-color: #e2efda; font-weight: bold; }
    .diff-negative { color: red; }
    .diff-positive { color: #16a34a; }
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

    <div class="asakai-tabs">
        <button class="tab-btn active" onclick="switchTab('shift1')">SHIFT 1</button>
        <button class="tab-btn" onclick="switchTab('shift2')">SHIFT 2</button>
    </div>

    <!-- SHIFT 1 CONTENT -->
    <div id="shift1" class="tab-content active">
        @include('reports.partials.asakai_content', ['shiftData' => $shift1, 'shiftTitle' => '3. PRODUCTIVITY STAMPING SHIFT 1'])
    </div>

    <!-- SHIFT 2 CONTENT -->
    <div id="shift2" class="tab-content">
        @include('reports.partials.asakai_content', ['shiftData' => $shift2, 'shiftTitle' => '4. PRODUCTIVITY STAMPING SHIFT 2'])
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

@extends('layouts.supervisor')

@section('title', 'ASAKAI Productivity Report')

@section('head')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #334155; }
    
    .asakai-header {
        margin-bottom: 2rem;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 1.5rem;
    }
    
    .asakai-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.5rem;
    }
    
    .asakai-subtitle {
        font-size: 0.95rem;
        color: #64748b;
    }

    .asakai-meta {
        display: flex;
        gap: 1.5rem;
        margin-top: 1rem;
        padding: 1rem;
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        align-items: center;
    }

    .date-filter-form {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .date-input {
        border: 1px solid #cbd5e1;
        border-radius: 0.375rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }

    .btn-filter {
        background-color: #2563eb;
        color: white;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-weight: 600;
        cursor: pointer;
        font-size: 0.9rem;
    }

    .btn-filter:hover { background-color: #1d4ed8; }

    /* Tabs */
    .asakai-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid #cbd5e1;
    }

    .tab-btn {
        padding: 0.75rem 1.5rem;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        font-weight: 600;
        font-size: 1rem;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s;
    }

    .tab-btn:hover { color: #0f172a; }
    .tab-btn.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
    }

    .tab-content { display: none; }
    .tab-content.active { display: block; }

    /* Line Section */
    .line-section {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
        overflow: hidden;
    }

    .line-header {
        background: #f1f5f9;
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .line-name {
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f172a;
    }

    .line-summary {
        display: flex;
        gap: 2rem;
    }

    .summary-item {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
    }

    .summary-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.25rem;
    }

    .summary-val {
        font-size: 1.1rem;
        font-weight: 700;
    }
    
    .val-diff.positive { color: #16a34a; }
    .val-diff.negative { color: #dc2626; }
    .val-diff.neutral { color: #64748b; }

    /* Tables */
    .asakai-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    .asakai-table th, .asakai-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        text-align: left;
    }

    .asakai-table th {
        background: #f8fafc;
        font-weight: 600;
        color: #475569;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.02em;
    }

    .asakai-table tbody tr:hover { background: #f8fafc; }

    .asakai-table td.num { text-align: right; font-weight: 600; }

    .unachieved-section {
        padding: 1.5rem;
        background: #fff1f2;
        border-top: 1px solid #fecdd3;
    }

    .unachieved-title {
        font-weight: 700;
        color: #9f1239;
        margin-bottom: 1rem;
        font-size: 0.95rem;
    }

    .unachieved-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
        background: white;
        border: 1px solid #fecdd3;
        border-radius: 0.375rem;
    }
    
    .unachieved-table th, .unachieved-table td {
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid #ffe4e6;
        text-align: left;
    }

    .unachieved-table th {
        background: #fff1f2;
        color: #9f1239;
        font-weight: 600;
    }
    
    .unachieved-table td.num { text-align: right; font-weight: 600; }

    .total-dt {
        padding: 1rem 1.5rem;
        background: #f8fafc;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1rem;
        border-top: 1px solid #e2e8f0;
    }
    .dt-label { font-weight: 600; color: #475569; }
    .dt-val { font-size: 1.1rem; font-weight: 700; color: #b91c1c; }

    .empty-state {
        padding: 3rem;
        text-align: center;
        color: #64748b;
    }
</style>
@endsection

@section('content')
<div class="page-wrapper" style="padding: 2rem;">

    <div class="asakai-header">
        <h1 class="asakai-title">ASAKAI Productivity Stamping</h1>
        <div class="asakai-subtitle">Reporting System - Produksi Aktual vs Plan</div>
        
        <div class="asakai-meta">
            <form action="{{ route('supervisor.reports.asakai') }}" method="GET" class="date-filter-form">
                <div>
                    <label for="date" style="font-size: 0.85rem; font-weight: 600; color: #475569; margin-right: 0.5rem;">Tanggal Report (Cut-off):</label>
                    <input type="date" name="date" id="date" value="{{ $reportDate }}" class="date-input">
                </div>
                <button type="submit" class="btn-filter">Update Data</button>
            </form>
            <div style="margin-left: auto; font-size: 0.85rem; color: #64748b;">
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
            <div class="line-section empty-state">Tidak ada data plan/aktual untuk Shift 1 pada tanggal ini.</div>
        @else
            @foreach($shift1Data as $line)
                @include('reports.partials.asakai_line', ['line' => $line])
            @endforeach
        @endif
    </div>

    <!-- SHIFT 2 CONTENT -->
    <div id="shift2" class="tab-content">
        @if(count($shift2Data) == 0)
            <div class="line-section empty-state">Tidak ada data plan/aktual untuk Shift 2 pada tanggal ini.</div>
        @else
            @foreach($shift2Data as $line)
                @include('reports.partials.asakai_line', ['line' => $line])
            @endforeach
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

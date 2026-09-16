@extends('layouts.app')

@section('title', 'Business Event Logs')

@push('styles')
<style>
    /* ===== HERO ===== */
    .hero {
        background: var(--red-main);
        padding: 24px 32px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }
    .hero-title-block h2 {
        font-size: 28px;
        font-weight: 900;
        color: white;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: 0;
    }
    .hero-title-block h2 .material-icons { font-size: 32px; opacity: 0.8; }
    .hero-meta {
        color: rgba(255,255,255,0.75);
        font-size: 12px;
        font-weight: 500;
        margin-top: 6px;
    }
    
    .content-body {
        padding: 24px 28px;
        background: #f8fafc;
        min-height: calc(100vh - 70px);
    }

    .card {
        background: #fff;
        border-radius: 12px; 
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        padding: 24px;
        overflow: hidden;
    }
    
    .card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .card-title {
        font-size: 16px;
        font-weight: 800;
        color: var(--red-main);
    }
    
    .btn-export {
        background: #10b981;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-export:hover {
        background: #059669;
    }

    /* FILTERS */
    .filter-row {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .filter-input {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 12px;
        background: #fff;
        outline: none;
        width: 180px;
    }
    .filter-input:focus {
        border-color: #94a3b8;
    }
    .btn-filter {
        background: #475569;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-filter:hover {
        background: #334155;
    }
    .btn-reset {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }
    .btn-reset:hover {
        background: #e2e8f0;
    }

    /* TABLE */
    .table-responsive {
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    thead th {
        background: var(--red-main);
        color: white;
        padding: 12px 20px;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
    }
    tbody td {
        padding: 14px 20px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: top;
    }
    tbody tr:last-child td {
        border-bottom: none;
    }
    .empty-state {
        text-align: center;
        padding: 30px;
        color: #94a3b8;
    }
    
    .payload-content {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-family: monospace;
        color: #64748b;
        background: #f8fafc;
        padding: 4px 8px;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
    }

    .badge-event {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        background: #f1f5f9;
        color: #475569;
    }

    .pagination-wrap {
        margin-top: 20px;
    }

    @media (max-width: 768px) {
        .hero {
            flex-direction: column;
            align-items: flex-start;
            padding: 20px;
        }
        .filter-row {
            flex-direction: column;
            align-items: stretch;
        }
        .filter-input {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">history_edu</span> Business Event Logs</h2>
            <div class="hero-meta">Catatan log aktivitas bisnis dan perubahan entitas di seluruh sistem</div>
        </div>
        <div style="color: rgba(255,255,255,0.9); font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <span class="material-icons" style="font-size:18px;">account_circle</span>
            Administrator &bull; {{ now()->format('d M Y') }}
        </div>
    </div>

    <div class="content-body">
        
        <div class="card">
            <div class="card-top">
                <div class="card-title">Business Event Logs</div>
                <a href="{{ route('business_logs.export', ['event_type' => $eventType, 'entity_type' => $entityType, 'entity_id' => $entityId]) }}" class="btn-export">
                    <span class="material-icons" style="font-size: 16px;">file_download</span> Export Excel
                </a>
            </div>

            <form action="{{ route('business_logs.index') }}" method="GET" class="filter-row">
                <input type="text" name="event_type" value="{{ $eventType }}" class="filter-input" placeholder="event_type...">
                <input type="text" name="entity_type" value="{{ $entityType }}" class="filter-input" placeholder="entity_type...">
                <input type="text" name="entity_id" value="{{ $entityId }}" class="filter-input" placeholder="entity_id">
                
                <button type="submit" class="btn-filter"><span class="material-icons">search</span>Cari</button>
                @if($eventType || $entityType || $entityId)
                    <a href="{{ route('business_logs.index') }}" class="btn-reset">Kembali</a>
                @endif
            </form>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Event</th>
                            <th>Entity</th>
                            <th>Entity ID</th>
                            <th>User</th>
                            <th>Payload</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td style="white-space: nowrap;">{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '-' }}</td>
                            <td><span class="badge-event">{{ $log->event_type }}</span></td>
                            <td style="font-weight: 600;">{{ $log->entity_type }}</td>
                            <td style="font-weight: 700;">{{ $log->entity_id }}</td>
                            <td>{{ $log->user_name }}</td>
                            <td>
                                <pre style="font-family: monospace; font-size: 11px; white-space: pre-wrap; word-break: break-all; color: #475569; background: #f8fafc; padding: 6px; border-radius: 4px; border: 1px solid #e2e8f0; margin: 0; max-width: 500px;">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="empty-state">Belum ada event log.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
            <div class="pagination-wrap">
                {{ $logs->links() }}
            </div>
            @endif
        </div>

    </div>

@endsection

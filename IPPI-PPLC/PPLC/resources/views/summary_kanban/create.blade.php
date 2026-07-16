@extends('layouts.app')

@section('title', 'Buat SKM - Summary Kanban Material')

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 28px;
        background: #fff;
        border-bottom: 1px solid #f0f0f0;
    }
    .page-title { font-size: 20px; font-weight: 800; color: var(--navy-dark); }
    .content-body { padding: 24px 28px; background: #f8fafc; min-height: calc(100vh - 70px); }
    .card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,.02);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .card-header-basic {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 15px;
        font-weight: 800;
        color: var(--navy-dark);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .form-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        padding: 20px;
        align-items: flex-end;
    }
    .form-group { display: flex; flex-direction: column; gap: 4px; }
    .form-group label { font-size: 12px; font-weight: 600; color: #64748b; }
    .form-group input, .form-group select {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 7px 10px;
        font-size: 13px;
        color: #1e293b;
    }
    .form-group input:focus, .form-group select:focus {
        outline: none;
        border-color: var(--navy-dark);
    }
    .table-wrap { overflow-x: auto; padding: 0 0 16px; }
    table { width: 100%; border-collapse: collapse; font-size: 12px; }
    thead th {
        background: var(--navy-dark);
        color: #fff;
        padding: 10px 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    tbody td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
    tbody tr:last-child td { border-bottom: none; }
    tbody tr.opacity-40 { opacity: .4; }
    tbody tr:hover td { background: #f8fafc; }
    .mat-code { font-family: monospace; font-weight: 700; color: #1e3a5f; font-size: 12px; }
    .mat-name { font-size: 11px; color: #64748b; }
    input[type=number].num-input {
        width: 80px;
        text-align: right;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 5px 8px;
        font-size: 13px;
    }
    input[type=text].note-input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 5px 8px;
        font-size: 12px;
        color: #64748b;
    }
    .form-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-top: 1px solid #f1f5f9;
    }
    .btn { display:inline-flex;align-items:center;gap:6px;border:none;border-radius:6px;padding:9px 20px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:opacity .15s; }
    .btn:hover { opacity:.85; }
    .btn-blue { background:#2563eb;color:#fff; }
    .btn-gray  { background:#e2e8f0;color:#334155; }
</style>
@endpush

@section('content')

<div class="page-header">
    <div class="page-title">Buat Summary Kanban Material</div>
    <a href="{{ route('summary_kanban.index') }}" class="btn btn-gray">Batal</a>
</div>

<div class="content-body">

    @if(session('error'))
    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;margin-bottom:20px;color:#dc2626;font-weight:600;font-size:13px;">
        ✗ {{ session('error') }}
    </div>
    @endif

    <div class="card">
        <div class="card-header-basic">
            Detail SKM
            <span style="font-size:12px;font-weight:500;color:#94a3b8">
                Sistem mendeteksi {{ count($pending) }} item perlu dipesan berdasarkan kalkulasi kanban beredar.
            </span>
        </div>

        <form method="POST" action="{{ route('summary_kanban.store') }}" id="skm-form">
            @csrf

            <div class="form-grid">
                <div class="form-group">
                    <label>Tanggal Order *</label>
                    <input type="date" name="order_date" value="{{ user_now()->format('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label>Est. Pengiriman</label>
                    <input type="date" name="expected_delivery_date">
                </div>
                <div class="form-group">
                    <label>Lokasi Gudang Tujuan</label>
                    <select name="storage_location_id" style="min-width:200px">
                        <option value="">— Pilih Lokasi —</option>
                        @foreach($storageLocations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->code }} - {{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:200px">
                    <label>Catatan SKM (Opsional)</label>
                    <input type="text" name="notes" placeholder="Catatan...">
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th style="width:36px">
                                <input type="checkbox" id="check-all" checked style="width:16px;height:16px">
                            </th>
                            <th>Material</th>
                            <th>Vendor</th>
                            <th style="text-align:right">Stok Saat Ini</th>
                            <th style="text-align:right" title="Kanban per hari × (LT+SS+Proses)">Total Kanban Beredar</th>
                            <th style="text-align:right" title="floor(stok ÷ qty/kartu)">Stok (kanban)</th>
                            <th style="text-align:right">Outstanding</th>
                            <th style="text-align:right">Qty/Kartu</th>
                            <th style="text-align:right;width:90px">Jml Kartu *</th>
                            <th style="text-align:right;width:90px">Total Order</th>
                            <th style="width:160px">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pending as $idx => $p)
                        <tr id="row-{{ $idx }}">
                            <td style="text-align:center">
                                <input type="checkbox" name="items[{{ $idx }}][selected]" value="1"
                                       class="row-check" checked style="width:16px;height:16px"
                                       onchange="toggleRow({{ $idx }}, this.checked)">
                                <input type="hidden" name="items[{{ $idx }}][material_id]" value="{{ $p['material']->id }}">
                            </td>
                            <td>
                                <div class="mat-code">{{ $p['material']->code }}</div>
                                <div class="mat-name">{{ $p['material']->name }}</div>
                                <div style="font-size:10px;color:#94a3b8">{{ $p['material']->unit_of_measure ?? '' }}</div>
                                @if($p['rm_sheet_demand'] > 0)
                                <div style="font-size:10px;color:#6366f1">Demand: {{ number_format($p['rm_sheet_demand'], 0) }} sht</div>
                                @endif
                            </td>
                            <td style="font-size:11px;color:#64748b">
                                @if($p['material']->vendor)
                                    {{ $p['material']->vendor->name }}
                                @else
                                    <span style="color:#ef4444;font-weight:600">Belum ada vendor</span>
                                @endif
                            </td>
                            <td style="text-align:right;color:#ef4444;font-weight:700">{{ number_format($p['current_stock'], 0) }}</td>
                            <td style="text-align:right;font-weight:700;color:#1e3a5f">
                                {{ $p['total_kanban'] }}
                                @if($p['kanban_per_day'] > 0)
                                <div style="font-size:10px;color:#94a3b8;font-weight:400">{{ $p['kanban_per_day'] }}/hr × 6hr</div>
                                @endif
                            </td>
                            <td style="text-align:right;color:#64748b">{{ $p['stock_kanban'] }}</td>
                            <td style="text-align:right;color:#f97316;font-weight:700">
                                {{ $p['outstanding_kanban'] }}
                                @if($p['outstanding_qty'] > 0)
                                <div style="font-size:10px;color:#94a3b8;font-weight:400">{{ number_format($p['outstanding_qty'], 0) }} sht</div>
                                @endif
                            </td>
                            <td style="text-align:right">{{ number_format($p['kanban_qty'], 0) }}</td>
                            <td>
                                <input type="number" name="items[{{ $idx }}][num_cards]"
                                       value="{{ $p['num_cards_suggest'] }}" min="1" required
                                       class="num-input num-cards-input"
                                       data-kanban="{{ $p['kanban_qty'] }}"
                                       data-row="{{ $idx }}"
                                       oninput="calcTotal({{ $idx }})">
                            </td>
                            <td style="text-align:right;font-weight:800;color:#1e3a5f" id="total-{{ $idx }}">
                                {{ number_format($p['order_qty_suggest'], 0) }}
                            </td>
                            <td>
                                <input type="text" name="items[{{ $idx }}][notes]"
                                       class="note-input" placeholder="Opsional...">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="form-footer">
                <span style="font-size:12px;color:#64748b">
                    <span id="selected-count">{{ count($pending) }}</span> dari {{ count($pending) }} item dipilih
                </span>
                <button type="submit" id="submit-btn" class="btn btn-blue">Generate SKM</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('check-all').addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach((cb, idx) => {
        cb.checked = this.checked;
        toggleRow(idx, this.checked);
    });
    updateCount();
});

function toggleRow(idx, checked) {
    const row = document.getElementById('row-' + idx);
    const inputs = row.querySelectorAll('input:not([type=checkbox])');
    inputs.forEach(i => { i.disabled = !checked; });
    row.classList.toggle('opacity-40', !checked);
    updateCount();
}

function calcTotal(idx) {
    const input    = document.querySelector('[data-row="' + idx + '"]');
    const kanbanQty = parseFloat(input.dataset.kanban) || 0;
    const numCards  = parseInt(input.value) || 0;
    document.getElementById('total-' + idx).textContent = (kanbanQty * numCards).toLocaleString('id-ID');
}

function updateCount() {
    const selected = document.querySelectorAll('.row-check:checked').length;
    document.getElementById('selected-count').textContent = selected;
    document.getElementById('submit-btn').disabled = selected === 0;
}

document.getElementById('skm-form').addEventListener('submit', function () {
    document.querySelectorAll('.row-check').forEach((cb, idx) => {
        if (!cb.checked) {
            const row = document.getElementById('row-' + idx);
            row.querySelectorAll('input').forEach(i => i.name = '');
        }
    });
});
</script>

@endsection

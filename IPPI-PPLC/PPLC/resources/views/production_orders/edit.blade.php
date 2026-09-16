@extends('layouts.app')

@section('title', 'Edit Production Order')

@push('styles')
<style>
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
        max-width: 600px;
    }

    .card-title {
        font-size: 15px;
        font-weight: 800;
        color: #1e293b;
        padding: 16px 24px;
        border-bottom: 1px solid #f1f5f9;
    }

    .card-body {
        padding: 24px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        margin-bottom: 16px;
    }
    .form-label {
        font-size: 12px;
        color: #475569;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .form-input {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 10px 14px;
        font-size: 13px;
        color: #334155;
        outline: none;
        width: 100%;
        box-sizing: border-box;
    }
    .form-input:focus { border-color: var(--navy-dark); }

    .form-row {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
    }
    .form-row .form-group {
        flex: 1;
        min-width: 200px;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }
    .btn-submit {
        background: var(--red-main);
        color: white;
        border: none;
        border-radius: 6px;
        padding: 10px 24px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
    }
    .btn-submit:hover { opacity: 0.9; }
    
    .btn-cancel {
        background: #e2e8f0;
        color: #475569;
        text-decoration: none;
        border-radius: 6px;
        padding: 10px 24px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
    }
    .btn-cancel:hover { background: #cbd5e1; }
</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">precision_manufacturing</span> Edit Production Order</h2>
        </div>
    </div>

    <div class="content-body">
        
        @if ($errors->any())
        <div class="card" style="border-color: #fbd5d5; background-color: #fef2f2; padding: 16px; margin-bottom: 20px; max-width: 600px;">
            <ul style="color: #b91c1c; font-size: 12px; margin: 0; padding-left: 20px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="card">
            <div class="card-title">Edit Production Order: {{ $productionOrder->order_number }}</div>
            <div class="card-body">
                <form action="{{ route('production_orders.update', $productionOrder->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="form-label">Material *</label>
                        <select name="material_id" class="form-input" required>
                            @foreach($materials as $m)
                            <option value="{{ $m->id }}" {{ old('material_id', $productionOrder->material_id) == $m->id ? 'selected' : '' }}>
                                {{ $m->kode }} - {{ $m->nama }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">BOM *</label>
                        <select name="bom_id" class="form-input" required>
                            @foreach($boms as $bom)
                            <option value="{{ $bom->id }}" {{ old('bom_id', $productionOrder->bom_id) == $bom->id ? 'selected' : '' }}>
                                {{ $bom->bom_number }} ({{ $bom->material->nama ?? '' }})
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Qty Planned *</label>
                        <input type="number" name="quantity_planned" value="{{ old('quantity_planned', $productionOrder->quantity_planned) }}" class="form-input" min="0.001" step="0.001" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Tgl Mulai Rencana</label>
                            <input type="date" name="planned_start_date" value="{{ old('planned_start_date', $productionOrder->planned_start_date ? $productionOrder->planned_start_date->format('Y-m-d') : '') }}" class="form-input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tgl Selesai Rencana</label>
                            <input type="date" name="planned_end_date" value="{{ old('planned_end_date', $productionOrder->planned_end_date ? $productionOrder->planned_end_date->format('Y-m-d') : '') }}" class="form-input">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" rows="2" class="form-input">{{ old('notes', $productionOrder->notes) }}</textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">Perbarui</button>
                        <a href="{{ route('production_orders.show', $productionOrder->id) }}" class="btn-cancel">Batal</a>
                    </div>
                </form>
            </div>
        </div>

    </div>

@endsection

@extends('layouts.app')

@section('title', 'Detail Production Order')

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
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.02);
        padding: 24px;
    }

    /* CARD 1: INFO PRO */
    .pro-header-wrapper {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 20px;
    }
    .pro-label {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 600;
        margin-bottom: 4px;
    }
    .pro-number {
        font-size: 24px;
        font-weight: 800;
        color: #3b82f6; 
        margin-bottom: 12px;
        font-family: monospace;
    }
    .pro-material {
        font-size: 14px;
        color: #64748b;
        font-weight: 500;
    }
    
    .pro-actions {
        display: flex;
        flex-direction: row;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .badge-status {
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .btn-action {
        color: white;
        text-decoration: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 13px;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .btn-action-green { background: #10b981; }
    .btn-action-green:hover { background: #059669; }
    .btn-action-blue { background: #2563eb; }
    .btn-action-blue:hover { background: #1d4ed8; }
    .btn-action-yellow { background: #eab308; }
    .btn-action-yellow:hover { background: #ca8a04; }
    .btn-action-red { background: #ef4444; }
    .btn-action-red:hover { background: #dc2626; }
    
    .btn-back {
        background: #e2e8f0;
        color: #475569;
        text-decoration: none;
        padding: 8px 24px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
    }
    .btn-back:hover { background: #cbd5e1; }

    .pro-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    .detail-item .label {
        font-size: 11px;
        color: #64748b;
        margin-bottom: 4px;
        font-weight: 500;
    }
    .detail-item .val {
        font-size: 13px;
        color: #0f172a;
        font-weight: 700;
    }
    .val.green { color: #16a34a; }
    .val.red { color: #dc2626; }
    .val.link { color: #3b82f6; text-decoration: none; font-family: monospace; }
    .val.link:hover { text-decoration: underline; }

    /* CARD 2: KOMPONEN PRODUKSI */
    .card-title {
        font-size: 16px;
        font-weight: 800;
        color: #334155;
        margin-bottom: 20px;
    }
    .table-responsive {
        overflow-x: auto;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    thead th {
        background: #f8fafc;
        color: #334155;
        padding: 14px 20px;
        text-align: left;
        font-weight: 700;
        white-space: nowrap;
        border-bottom: 2px solid #e2e8f0;
    }
    tbody td {
        padding: 14px 20px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #475569;
        font-weight: 500;
    }
    .col-kode {
        color: #3b82f6;
        font-family: monospace;
        display: block;
    }
    .col-nama {
        color: #1e293b;
        font-weight: 700;
    }
    .col-center {
        text-align: center;
    }
    .text-green { color: #16a34a; font-weight: 700; }
    
    .badge-status-issued {
        background: #dcfce7;
        color: #16a34a;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 700;
    }

    /* Modal Form Group */
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-bottom: 16px;
    }
    .form-label {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
    }
    .form-input {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 13px;
    }
</style>
@endpush

@section('content')

    <div class="hero">
        <div class="hero-title-block">
            <h2><span class="material-icons">precision_manufacturing</span> Detail Production Order: {{ $order->order_number }}</h2>
        </div>
    </div>

    <div class="content-body">
        
        @if(session('success'))
        <div class="card" style="border-color: #bbf7d0; background-color: #f0fdf4; padding: 16px; color: #15803d; font-size: 13px; font-weight: 600;">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="card" style="border-color: #fbd5d5; background-color: #fef2f2; padding: 16px; color: #b91c1c; font-size: 13px; font-weight: 600;">
            {{ session('error') }}
        </div>
        @endif

        @if($errors->any() && !$errors->has('quantities'))
        <div class="card" style="border-color: #fbd5d5; background-color: #fef2f2; padding: 16px; color: #b91c1c; font-size: 13px; font-weight: 600;">
            <ul style="margin: 0; padding-left: 20px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- CARD INFO PRO -->
        <div class="card">
            <div class="pro-header-wrapper">
                <div>
                    <div class="pro-label">Nomor Production Order</div>
                    <div class="pro-number">{{ $order->order_number }}</div>
                    <div class="pro-material">
                        <span style="font-family: monospace; font-weight: bold; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-size: 12px; margin-right: 6px;">{{ $order->material->kode ?? '' }}</span>
                        <strong>{{ $order->material->nama ?? '-' }}</strong>
                    </div>
                </div>
                <div class="pro-actions" @if(in_array(strtolower($order->status), ['released', 'in_progress'])) style="display: flex; flex-direction: column; align-items: flex-end; gap: 12px;" @endif>
                    @php
                        $statusColors = [
                            'draft' => 'background: #f1f5f9; color: #64748b;',
                            'created' => 'background: #f1f5f9; color: #64748b;',
                            'released' => 'background: #e0f2fe; color: #0284c7;',
                            'in_progress' => 'background: #fef9c3; color: #a16207;',
                            'goods_issued' => 'background: #fef9c3; color: #a16207;',
                            'confirmed' => 'background: #dcfce7; color: #16a34a;',
                            'completed' => 'background: #dcfce7; color: #15803d; border: 1px solid #16a34a;',
                            'cancelled' => 'background: #fee2e2; color: #dc2626;'
                        ];
                        $style = $statusColors[strtolower($order->status)] ?? 'background: #f1f5f9; color: #64748b;';
                    @endphp
                    <span class="badge-status" style="{{ $style }}">{{ str_replace('_', ' ', $order->status) }}</span>
                    
                    @if(in_array(strtolower($order->status), ['draft', 'created']))
                        <form method="POST" action="{{ route('production_orders.release', $order->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn-action btn-action-blue"><span class="material-icons" style="font-size:16px;">play_arrow</span> Release</button>
                        </form>
                        <a href="{{ route('production_orders.edit', $order->id) }}" class="btn-action btn-action-yellow"><span class="material-icons" style="font-size:16px;">edit</span> Edit</a>
                        <form method="POST" action="{{ route('production_orders.cancel', $order->id) }}" style="display:inline;" onsubmit="return confirm('Batalkan PO ini?')">
                            @csrf
                            <button type="submit" class="btn-action btn-action-red"><span class="material-icons" style="font-size:16px;">close</span> Cancel</button>
                        </form>
                        <a href="{{ route('production_orders.index') }}" class="btn-back">Kembali</a>
                    @endif

                    @if(in_array(strtolower($order->status), ['released', 'in_progress']))
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <form action="{{ route('production_orders.confirm', $order->id) }}" method="POST" style="display: inline-block; margin: 0;">
                                @csrf
                                <input type="hidden" name="actual_start_date" value="{{ $order->actual_start_date ? $order->actual_start_date->format('Y-m-d') : date('Y-m-d') }}">
                                <input type="hidden" name="actual_end_date" value="{{ date('Y-m-d') }}">
                                <input type="hidden" name="notes" value="">

                                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 16px; display: flex; flex-direction: column; gap: 4px; text-align: left;">
                                    <div style="font-size: 11px; font-weight: 700; color: #1e293b;">Konfirmasi Selesai</div>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <span style="font-size: 9px; color: #64748b; font-weight: 600;">Qty OK:</span>
                                            <input type="number" id="confirm-qty-ok" name="quantity_ok" value="{{ old('quantity_ok', $maxConfirmQty) }}" step="0.001" min="0" style="width: 80px; height: 32px; padding: 4px 8px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-align: center;" required>
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <span style="font-size: 9px; color: #64748b; font-weight: 600;">Qty NG:</span>
                                            <input type="number" name="quantity_ng" value="{{ old('quantity_ng', 0) }}" step="0.001" min="0" style="width: 80px; height: 32px; padding: 4px 8px; font-size: 12px; border: 1px solid #cbd5e1; border-radius: 4px; text-align: center;" required>
                                        </div>
                                        <div style="display: flex; flex-direction: column; gap: 2px;">
                                            <span style="font-size: 9px; color: #64748b; font-weight: 600;">Lokasi Tujuan:</span>
                                            <select name="storage_location_id" style="width: 190px; height: 32px; padding: 4px 8px; font-size: 11px; border: 1px solid #cbd5e1; border-radius: 4px;" required>
                                                @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}" {{ old('storage_location_id', $defaultFgLocation ? $defaultFgLocation->id : '') == $loc->id ? 'selected' : '' }}>{{ $loc->kode }} — {{ $loc->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="btn-action btn-action-green" style="height: 32px; padding: 0 16px; font-size: 11px; margin-top: 13px; font-weight: 700; border-radius: 4px; background: #16a34a; color: white;">
                                            Konfirmasi Selesai
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                            <form method="POST" action="{{ route('production_orders.cancel', $order->id) }}" style="display:inline;" onsubmit="return confirm('Batalkan PO ini?')">
                                @csrf
                                <button type="submit" class="btn-action btn-action-red" style="height: 32px; display: inline-flex; align-items: center; gap: 4px;"><span class="material-icons" style="font-size:16px;">close</span> Cancel</button>
                            </form>
                            
                            <a href="{{ route('production_orders.index') }}" class="btn-back" style="height: 32px; display: inline-flex; align-items: center; justify-content: center; padding: 0 24px;">Kembali</a>
                        </div>
                    @endif

                    @if(!in_array(strtolower($order->status), ['draft', 'created', 'released', 'in_progress']))
                        <a href="{{ route('production_orders.index') }}" class="btn-back">Kembali</a>
                    @endif
                </div>
            </div>

            <div class="pro-details-grid">
                <div class="detail-item">
                    <div class="label">Qty Planned:</div>
                    <div class="val">{{ rtrim(rtrim(number_format($order->quantity_planned, 3, ',', '.'), '0'), ',') }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Qty Produced:</div>
                    <div class="val">{{ rtrim(rtrim(number_format($order->quantity_produced, 3, ',', '.'), '0'), ',') }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Qty OK:</div>
                    <div class="val green">{{ rtrim(rtrim(number_format($order->quantity_ok, 3, ',', '.'), '0'), ',') }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Qty NG:</div>
                    <div class="val red">{{ rtrim(rtrim(number_format($order->quantity_ng, 3, ',', '.'), '0'), ',') }}</div>
                </div>
            </div>

            <div class="pro-details-grid">
                <div class="detail-item">
                    <div class="label">BOM:</div>
                    @if($order->bom)
                        <a href="{{ route('boms.show', $order->bom_id) }}" class="val link">{{ $order->bom->bom_number }}</a>
                    @else
                        <div class="val">-</div>
                    @endif
                </div>
                <div class="detail-item">
                    <div class="label">Routing:</div>
                    <div class="val">{{ $order->routing_id ?: '-' }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Tgl Mulai Rencana:</div>
                    <div class="val">{{ $order->planned_start_date ? $order->planned_start_date->format('d/m/Y') : '-' }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Tgl Selesai Rencana:</div>
                    <div class="val">{{ $order->planned_end_date ? $order->planned_end_date->format('d/m/Y') : '-' }}</div>
                </div>
            </div>

            <div class="pro-details-grid" style="margin-bottom:0;">
                <div class="detail-item">
                    <div class="label">Dibuat Oleh:</div>
                    <div class="val">{{ $order->createdBy->name ?? '-' }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Dibuat Pada:</div>
                    <div class="val">{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Tgl Mulai Aktual:</div>
                    <div class="val">{{ $order->actual_start_date ? $order->actual_start_date->format('d/m/Y') : '-' }}</div>
                </div>
                <div class="detail-item">
                    <div class="label">Tgl Selesai Aktual:</div>
                    <div class="val">{{ $order->actual_end_date ? $order->actual_end_date->format('d/m/Y') : '-' }}</div>
                </div>
            </div>
        </div>

        <!-- CARD KOMPONEN PRODUKSI -->
        <div class="card">
            @if(in_array(strtolower($order->status), ['released', 'in_progress']) && $order->components->isNotEmpty())
            <form method="POST" action="{{ route('production_orders.issue', $order->id) }}"
                  onsubmit="return confirm('Post Goods Issue dengan qty yang diinput? Stok gudang akan dikurangi sesuai qty.')">
            @csrf
            @endif
            
            @if($errors->hasBag('default') && $errors->has('quantities'))
            <div style="margin-bottom:16px; padding:12px; background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; border-radius:6px; font-size:13px;">
                @foreach((array) $errors->get('quantities') as $e)
                    <div style="margin-bottom:4px;">• {{ $e }}</div>
                @endforeach
            </div>
            @endif

            <div class="card-title" style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                <span>Komponen Produksi ({{ $order->components->count() }})</span>
                @if(in_array(strtolower($order->status), ['released', 'in_progress']) && $order->components->isNotEmpty())
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="font-size:12px; color:#64748b; font-weight:normal;">Sumber: RM → WH-01 &nbsp;|&nbsp; WIP → WH-02</span>
                    <button type="submit" class="btn-action btn-action-orange" style="background:#ea580c;color:white;border:none;">
                        <span class="material-icons" style="font-size:16px;">output</span>
                        {{ strtolower($order->status) === 'in_progress' ? 'Top-up GI' : 'Post GI ke Produksi' }}
                    </button>
                </div>
                @endif
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th class="col-center">Qty Required</th>
                            <th class="col-center">Qty Issued</th>
                            @if(in_array(strtolower($order->status), ['released', 'in_progress']))
                            <th class="col-center">Stok Tersedia</th>
                            <th class="col-center" style="width:130px;">Qty GI (input)</th>
                            @endif
                            <th>Lokasi Sumber</th>
                            <th class="col-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($order->components as $comp)
                        @php
                            $remaining   = round((float) $comp->quantity_required - (float) ($comp->quantity_issued ?? 0), 3);
                            $isPending   = $remaining > 0.001;
                            $stockInfo   = $componentStocks[$comp->id] ?? ['location_code' => '-', 'available' => 0];
                        @endphp
                        <tr>
                            <td>
                                <span class="col-kode">{{ $comp->material->kode ?? '' }}</span>
                                <span class="col-nama">{{ $comp->material->nama ?? '-' }}</span>
                            </td>
                            <td class="col-center" style="font-weight:600;">{{ rtrim(rtrim(number_format($comp->quantity_required, 3, ',', '.'), '0'), ',') }}</td>
                            <td class="col-center text-green">{{ rtrim(rtrim(number_format($comp->quantity_issued, 3, ',', '.'), '0'), ',') }}</td>
                            @if(in_array(strtolower($order->status), ['released', 'in_progress']))
                            <td class="col-center {{ $stockInfo['available'] < $remaining ? 'text-red' : 'text-gray' }}">
                                {{ rtrim(rtrim(number_format($stockInfo['available'], 3, ',', '.'), '0'), ',') }}
                                <div style="font-size:10px;color:#94a3b8;">{{ $stockInfo['location_code'] }}</div>
                            </td>
                            <td class="col-center">
                                @if($isPending)
                                <input type="number"
                                       name="quantities[{{ $comp->id }}]"
                                       value="{{ old('quantities.' . $comp->id, round($remaining, 3) + 0) }}"
                                       min="0"
                                       max="{{ $remaining }}"
                                       step="0.001"
                                       class="form-input gi-qty-input"
                                       style="width:110px;text-align:right;padding:4px 8px;margin:0;"
                                       data-required="{{ $comp->quantity_required }}"
                                       data-issued="{{ $comp->quantity_issued ?? 0 }}"
                                       data-planned="{{ $order->quantity_planned }}">
                                @else
                                <span style="font-size:12px;color:#16a34a;font-weight:600;">Selesai</span>
                                <input type="hidden" name="quantities[{{ $comp->id }}]" value="0">
                                @endif
                            </td>
                            @endif
                            <td>
                                @if($comp->storageLocation)
                                <span style="display:block;color:#64748b;">{{ $comp->storageLocation->kode }}</span>
                                <span style="font-weight:600;">{{ $comp->storageLocation->nama }}</span>
                                @else
                                <span style="display:block;color:#64748b;">{{ $stockInfo['location_code'] }}</span>
                                @endif
                            </td>
                            <td class="col-center">
                                <span class="badge-status" style="{{ !$isPending ? 'background: #dcfce7; color: #16a34a;' : 'background: #fee2e2; color: #dc2626;' }}">
                                    {{ !$isPending ? 'Issued' : 'Pending' }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ in_array(strtolower($order->status), ['released', 'in_progress']) ? 7 : 5 }}" style="text-align:center;padding:30px;color:#94a3b8;">Tidak ada komponen untuk order ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if(in_array(strtolower($order->status), ['released', 'in_progress']) && $order->components->isNotEmpty())
            </form>
            @endif
        </div>

    </div>



@endsection

@push('scripts')
<script>
    function openModal(id) {
        document.getElementById(id).style.display = 'flex';
    }
    
    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    }

    // Automatically suggest confirm qty ok based on component GI ratios
    const qtyOkInput = document.getElementById('confirm-qty-ok');
    const planned = {{ (float) $order->quantity_planned }};

    function recomputeConfirmQty() {
        let minRatio = 1;
        let count = 0;
        document.querySelectorAll('.gi-qty-input').forEach(input => {
            const required = parseFloat(input.dataset.required) || 0;
            if (required <= 0) return;
            count++;
            const issued  = parseFloat(input.dataset.issued) || 0;
            const adding  = parseFloat(input.value) || 0;
            const totalIssued = issued + adding;
            const ratio = totalIssued / required;
            if (ratio < minRatio) minRatio = ratio;
        });
        if (qtyOkInput) {
            qtyOkInput.value = Math.round(Math.min(planned, minRatio * planned) * 1000) / 1000;
        }
    }

    document.querySelectorAll('.gi-qty-input').forEach(input => {
        input.addEventListener('input', recomputeConfirmQty);
    });
</script>
@endpush

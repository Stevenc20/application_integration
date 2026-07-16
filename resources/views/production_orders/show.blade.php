@extends('layouts.app')

@section('title', 'Detail Production Order')

@section('content')
<div class="space-y-6">

    {{-- Hero --}}
    <div class="bg-gradient-to-r from-red-800 via-rose-700 to-red-600 rounded-3xl px-8 py-8 shadow-xl relative overflow-hidden flex items-center justify-between gap-6">
        <div class="absolute inset-0 opacity-10">
            <svg class="w-full h-full" viewBox="0 0 800 400" fill="none"><circle cx="700" cy="50" r="200" fill="white"/><circle cx="100" cy="350" r="150" fill="white"/></svg>
        </div>
        <div class="relative flex items-center gap-5">
            <div class="w-14 h-14 bg-white/15 backdrop-blur-sm rounded-2xl flex items-center justify-center text-white ring-1 ring-white/20 shadow-lg">
                <span class="material-icons text-3xl">precision_manufacturing</span>
            </div>
            <div>
                <h1 class="text-2xl font-black text-white tracking-tight">Detail Production Order: {{ $order->order_number }}</h1>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-emerald-50 text-emerald-700 p-4 rounded-xl flex items-center gap-3 border border-emerald-100 shadow-sm text-sm font-semibold">{{ session('success') }}</div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl flex items-center gap-3 border border-red-100 shadow-sm text-sm font-semibold">{{ session('error') }}</div>
    @endif

    @if($errors->any() && !$errors->has('quantities'))
    <div class="bg-red-50 text-red-600 p-4 rounded-xl border border-red-100 shadow-sm text-sm font-semibold">
        <ul class="m-0 ps-5">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Card Info PRO --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6 space-y-5">
        <div class="flex justify-between items-start flex-wrap gap-5">
            <div>
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Nomor Production Order</div>
                <div class="text-2xl font-black text-blue-600 font-mono mb-3">{{ $order->order_number }}</div>
                <div class="text-sm text-slate-500 font-medium">
                    <span class="font-mono font-bold bg-sky-50 text-sky-700 px-1.5 py-0.5 rounded text-xs mr-1.5">{{ $order->material->kode ?? '' }}</span>
                    <strong>{{ $order->material->nama ?? '-' }}</strong>
                </div>
            </div>
            <div class="flex flex-col items-end gap-3">
                @php
                    $statusColors = [
                        'draft' => 'bg-slate-100 text-slate-500',
                        'created' => 'bg-slate-100 text-slate-500',
                        'released' => 'bg-sky-100 text-sky-700',
                        'in_progress' => 'bg-amber-100 text-amber-700',
                        'goods_issued' => 'bg-amber-100 text-amber-700',
                        'confirmed' => 'bg-emerald-100 text-emerald-700',
                        'completed' => 'bg-emerald-100 text-emerald-700 border border-emerald-500',
                        'cancelled' => 'bg-red-100 text-red-700'
                    ];
                    $statusCls = $statusColors[strtolower($order->status)] ?? 'bg-slate-100 text-slate-500';
                @endphp
                <span class="inline-block px-4 py-1.5 rounded-full text-xs font-bold uppercase {{ $statusCls }}">{{ str_replace('_', ' ', $order->status) }}</span>

                <div class="flex items-center gap-2 flex-wrap">
                    @if(in_array(strtolower($order->status), ['draft', 'created']))
                        <form method="POST" action="{{ route('production_orders.release', $order->id) }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg px-4 py-2 text-sm inline-flex items-center gap-1"><span class="material-icons text-base">play_arrow</span> Release</button>
                        </form>
                        <a href="{{ route('production_orders.edit', $order->id) }}" class="bg-amber-400 hover:bg-amber-500 text-white font-bold rounded-lg px-4 py-2 text-sm inline-flex items-center gap-1"><span class="material-icons text-base">edit</span> Edit</a>
                        <form method="POST" action="{{ route('production_orders.cancel', $order->id) }}" class="inline" onsubmit="return confirm('Batalkan PO ini?')">
                            @csrf
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold rounded-lg px-4 py-2 text-sm inline-flex items-center gap-1"><span class="material-icons text-base">close</span> Cancel</button>
                        </form>
                        <a href="{{ route('production_orders.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg px-5 py-2 text-sm">Kembali</a>
                    @endif

                    @if(in_array(strtolower($order->status), ['released', 'in_progress']))
                        <div class="flex items-center gap-2 flex-wrap">
                            <form action="{{ route('production_orders.confirm', $order->id) }}" method="POST" class="inline-block m-0">
                                @csrf
                                <input type="hidden" name="actual_start_date" value="{{ $order->actual_start_date ? $order->actual_start_date->format('Y-m-d') : date('Y-m-d') }}">
                                <input type="hidden" name="actual_end_date" value="{{ date('Y-m-d') }}">
                                <input type="hidden" name="notes" value="">
                                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex flex-col gap-1.5 text-left">
                                    <div class="text-[11px] font-bold text-slate-800">Konfirmasi Selesai</div>
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[9px] text-slate-500 font-bold">Qty OK:</span>
                                            <input type="number" id="confirm-qty-ok" name="quantity_ok" value="{{ old('quantity_ok', $maxConfirmQty) }}" step="0.001" min="0" class="w-20 h-8 px-2 text-xs text-center border border-slate-300 rounded" required>
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[9px] text-slate-500 font-bold">Qty NG:</span>
                                            <input type="number" name="quantity_ng" value="{{ old('quantity_ng', 0) }}" step="0.001" min="0" class="w-20 h-8 px-2 text-xs text-center border border-slate-300 rounded" required>
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-[9px] text-slate-500 font-bold">Lokasi Tujuan:</span>
                                            <select name="storage_location_id" class="w-[190px] h-8 px-2 text-[11px] border border-slate-300 rounded" required>
                                                @foreach($locations as $loc)
                                                <option value="{{ $loc->id }}" {{ old('storage_location_id', $defaultFgLocation ? $defaultFgLocation->id : '') == $loc->id ? 'selected' : '' }}>{{ $loc->kode }} — {{ $loc->nama }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 h-8 text-[11px] rounded mt-[13px]">Konfirmasi Selesai</button>
                                    </div>
                                </div>
                            </form>
                            <form method="POST" action="{{ route('production_orders.cancel', $order->id) }}" class="inline" onsubmit="return confirm('Batalkan PO ini?')">
                                @csrf
                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-bold rounded-lg px-3 h-8 text-sm inline-flex items-center gap-1"><span class="material-icons text-base">close</span> Cancel</button>
                            </form>
                            <a href="{{ route('production_orders.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg px-6 h-8 inline-flex items-center text-sm">Kembali</a>
                        </div>
                    @endif

                    @if(!in_array(strtolower($order->status), ['draft', 'created', 'released', 'in_progress']))
                        <a href="{{ route('production_orders.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg px-6 py-2 text-sm">Kembali</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-5">
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Qty Planned:</div>
                <div class="text-sm font-bold text-slate-800">{{ rtrim(rtrim(number_format($order->quantity_planned, 3, ',', '.'), '0'), ',') }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Qty Produced:</div>
                <div class="text-sm font-bold text-slate-800">{{ rtrim(rtrim(number_format($order->quantity_produced, 3, ',', '.'), '0'), ',') }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Qty OK:</div>
                <div class="text-sm font-bold text-emerald-600">{{ rtrim(rtrim(number_format($order->quantity_ok, 3, ',', '.'), '0'), ',') }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Qty NG:</div>
                <div class="text-sm font-bold text-red-500">{{ rtrim(rtrim(number_format($order->quantity_ng, 3, ',', '.'), '0'), ',') }}</div>
            </div>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-5">
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">BOM:</div>
                @if($order->bom)
                    <a href="{{ route('boms.show', $order->bom_id) }}" class="text-sm font-bold text-blue-600 font-mono hover:underline">{{ $order->bom->bom_number }}</a>
                @else
                    <div class="text-sm font-bold text-slate-800">-</div>
                @endif
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Routing:</div>
                <div class="text-sm font-bold text-slate-800">{{ $order->routing_id ?: '-' }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Tgl Mulai Rencana:</div>
                <div class="text-sm font-bold text-slate-800">{{ $order->planned_start_date ? $order->planned_start_date->format('d/m/Y') : '-' }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Tgl Selesai Rencana:</div>
                <div class="text-sm font-bold text-slate-800">{{ $order->planned_end_date ? $order->planned_end_date->format('d/m/Y') : '-' }}</div>
            </div>
        </div>

        <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-5">
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Dibuat Oleh:</div>
                <div class="text-sm font-bold text-slate-800">{{ $order->createdBy->name ?? '-' }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Dibuat Pada:</div>
                <div class="text-sm font-bold text-slate-800">{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '-' }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Tgl Mulai Aktual:</div>
                <div class="text-sm font-bold text-slate-800">{{ $order->actual_start_date ? $order->actual_start_date->format('d/m/Y') : '-' }}</div>
            </div>
            <div class="flex flex-col gap-1">
                <div class="text-[11px] font-semibold text-slate-500">Tgl Selesai Aktual:</div>
                <div class="text-sm font-bold text-slate-800">{{ $order->actual_end_date ? $order->actual_end_date->format('d/m/Y') : '-' }}</div>
            </div>
        </div>
    </div>

    {{-- Card Komponen Produksi --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden p-6">
        @if(in_array(strtolower($order->status), ['released', 'in_progress']) && $order->components->isNotEmpty())
        <form method="POST" action="{{ route('production_orders.issue', $order->id) }}"
              onsubmit="return confirm('Post Goods Issue dengan qty yang diinput? Stok gudang akan dikurangi sesuai qty.')">
        @csrf
        @endif

        @if($errors->hasBag('default') && $errors->has('quantities'))
        <div class="mb-4 p-3 bg-red-50 border border-red-300 text-red-700 rounded-lg text-sm">
            @foreach((array) $errors->get('quantities') as $e)
            <div class="mb-1">• {{ $e }}</div>
            @endforeach
        </div>
        @endif

        <div class="flex items-center justify-between w-full mb-5">
            <span class="text-base font-black text-slate-700">Komponen Produksi ({{ $order->components->count() }})</span>
            @if(in_array(strtolower($order->status), ['released', 'in_progress']) && $order->components->isNotEmpty())
            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500 font-normal">Sumber: RM → WH-01 &nbsp;|&nbsp; WIP → WH-02</span>
                <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-lg px-4 py-2 text-sm inline-flex items-center gap-1">
                    <span class="material-icons text-base">output</span>
                    {{ strtolower($order->status) === 'in_progress' ? 'Top-up GI' : 'Post GI ke Produksi' }}
                </button>
            </div>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b-2 border-slate-200">
                        <th class="py-3.5 px-5 font-bold text-slate-700 whitespace-nowrap">Material</th>
                        <th class="py-3.5 px-5 font-bold text-slate-700 whitespace-nowrap text-center">Qty Required</th>
                        <th class="py-3.5 px-5 font-bold text-slate-700 whitespace-nowrap text-center">Qty Issued</th>
                        @if(in_array(strtolower($order->status), ['released', 'in_progress']))
                        <th class="py-3.5 px-5 font-bold text-slate-700 whitespace-nowrap text-center">Stok Tersedia</th>
                        <th class="py-3.5 px-5 font-bold text-slate-700 whitespace-nowrap text-center w-[130px]">Qty GI (input)</th>
                        @endif
                        <th class="py-3.5 px-5 font-bold text-slate-700 whitespace-nowrap">Lokasi Sumber</th>
                        <th class="py-3.5 px-5 font-bold text-slate-700 whitespace-nowrap text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($order->components as $comp)
                    @php
                        $remaining   = round((float) $comp->quantity_required - (float) ($comp->quantity_issued ?? 0), 3);
                        $isPending   = $remaining > 0.001;
                        $stockInfo   = $componentStocks[$comp->id] ?? ['location_code' => '-', 'available' => 0];
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3.5 px-5">
                            <span class="text-blue-600 font-mono font-bold block">{{ $comp->material->kode ?? '' }}</span>
                            <span class="text-slate-800 font-bold">{{ $comp->material->nama ?? '-' }}</span>
                        </td>
                        <td class="py-3.5 px-5 text-center font-semibold">{{ rtrim(rtrim(number_format($comp->quantity_required, 3, ',', '.'), '0'), ',') }}</td>
                        <td class="py-3.5 px-5 text-center font-bold text-emerald-600">{{ rtrim(rtrim(number_format($comp->quantity_issued, 3, ',', '.'), '0'), ',') }}</td>
                        @if(in_array(strtolower($order->status), ['released', 'in_progress']))
                        <td class="py-3.5 px-5 text-center {{ $stockInfo['available'] < $remaining ? 'text-red-500' : 'text-slate-600' }}">
                            {{ rtrim(rtrim(number_format($stockInfo['available'], 3, ',', '.'), '0'), ',') }}
                            <div class="text-[10px] text-slate-400">{{ $stockInfo['location_code'] }}</div>
                        </td>
                        <td class="py-3.5 px-5 text-center">
                            @if($isPending)
                            <input type="number"
                                   name="quantities[{{ $comp->id }}]"
                                   value="{{ old('quantities.' . $comp->id, round($remaining, 3) + 0) }}"
                                   min="0"
                                   max="{{ $remaining }}"
                                   step="0.001"
                                   class="gi-qty-input border border-slate-300 rounded px-2 py-1 text-right text-sm w-[110px] outline-none focus:border-rose-400"
                                   data-required="{{ $comp->quantity_required }}"
                                   data-issued="{{ $comp->quantity_issued ?? 0 }}"
                                   data-planned="{{ $order->quantity_planned }}">
                            @else
                            <span class="text-emerald-600 font-bold text-xs">Selesai</span>
                            <input type="hidden" name="quantities[{{ $comp->id }}]" value="0">
                            @endif
                        </td>
                        @endif
                        <td class="py-3.5 px-5">
                            @if($comp->storageLocation)
                            <span class="block text-slate-500">{{ $comp->storageLocation->kode }}</span>
                            <span class="font-bold">{{ $comp->storageLocation->nama }}</span>
                            @else
                            <span class="block text-slate-500">{{ $stockInfo['location_code'] }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-5 text-center">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold {{ !$isPending ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ !$isPending ? 'Issued' : 'Pending' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ in_array(strtolower($order->status), ['released', 'in_progress']) ? 7 : 5 }}" class="text-center py-8 text-slate-400">Tidak ada komponen untuk order ini.</td>
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

    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    });

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

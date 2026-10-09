@extends('layouts.supervisor')
@section('title', 'History Repair & Reject')
@section('header_title', 'History Repair & Reject')

@section('content')
<div class="space-y-6">

    {{-- PAGE HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-lg shadow-orange-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                History Repair & Reject
            </h1>
            <p class="text-sm text-gray-500 mt-1 ml-13">Rekap catatan insiden kualitas produksi</p>
        </div>
        <a href="{{ route('operational.input_harian') }}" class="flex items-center gap-2 bg-slate-700 hover:bg-slate-800 text-white font-bold text-sm px-5 py-2.5 rounded-xl shadow-md transition-all">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
            <span>Kembali ke Input Harian</span>
        </a>
    </div>

    {{-- STAT CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-orange-100 shadow-sm p-5">
            <p class="text-[10px] font-black text-orange-400 uppercase tracking-widest">Total Repair (All)</p>
            <p class="text-3xl font-black text-orange-600 mt-1">{{ number_format($totalRepair) }}</p>
            <p class="text-xs text-slate-400 mt-1">PCS</p>
        </div>
        <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-5">
            <p class="text-[10px] font-black text-red-400 uppercase tracking-widest">Total Reject (All)</p>
            <p class="text-3xl font-black text-red-600 mt-1">{{ number_format($totalReject) }}</p>
            <p class="text-xs text-slate-400 mt-1">PCS</p>
        </div>
        <div class="bg-white rounded-2xl border border-amber-100 shadow-sm p-5">
            <p class="text-[10px] font-black text-amber-500 uppercase tracking-widest">Repair Hari Ini</p>
            <p class="text-3xl font-black text-amber-600 mt-1">{{ number_format($todayRepair) }}</p>
            <p class="text-xs text-slate-400 mt-1">PCS</p>
        </div>
        <div class="bg-white rounded-2xl border border-rose-100 shadow-sm p-5">
            <p class="text-[10px] font-black text-rose-500 uppercase tracking-widest">Reject Hari Ini</p>
            <p class="text-3xl font-black text-rose-600 mt-1">{{ number_format($todayReject) }}</p>
            <p class="text-xs text-slate-400 mt-1">PCS</p>
        </div>
    </div>

    {{-- LINE BUTTONS FILTER --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-primary-red text-white flex items-center justify-center shadow-md">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <span class="text-sm font-bold text-gray-800">Filter Area Produksi (Line)</span>
        </div>
        <div class="flex flex-wrap gap-2">
            @php
                $activeLine = request('line');
                $normalizedActive = '';
                if ($activeLine) {
                    $clean = strtoupper(trim(str_replace(['Line ', 'LINE ', 'Press ', 'PRESS '], '', $activeLine)));
                    if (in_array($clean, ['A', 'B', 'C', 'D'])) {
                        $normalizedActive = 'Line ' . $clean;
                    } else {
                        if ($clean === 'SHEARING') {
                            $normalizedActive = 'Shearing';
                        } elseif ($clean === 'HANDWORK') {
                            $normalizedActive = 'Handwork';
                        } else {
                            $normalizedActive = $activeLine;
                        }
                    }
                }
            @endphp
            <a href="{{ route('operational.repair_reject.index', array_merge(request()->query(), ['line' => '', 'job_id' => '', 'page' => 1])) }}" 
               class="px-4 py-2 rounded-xl text-xs font-black transition-all border-2 {{ !$normalizedActive ? 'bg-primary-red border-primary-red text-white shadow-lg shadow-red-200' : 'bg-white border-slate-100 text-slate-500 hover:border-red-200 hover:text-red-600' }} uppercase">
                SEMUA LINE
            </a>
            @foreach(['Line A', 'Line B', 'Line C', 'Line D', 'Shearing', 'Handwork'] as $ln)
                @php
                    $cleanName = str_replace('Line ', '', $ln);
                @endphp
                <a href="{{ route('operational.repair_reject.index', array_merge(request()->query(), ['line' => $ln, 'job_id' => '', 'page' => 1])) }}" 
                   class="px-4 py-2 rounded-xl text-xs font-black transition-all border-2 {{ $normalizedActive == $ln ? 'bg-primary-red border-primary-red text-white shadow-lg shadow-red-200' : 'bg-white border-slate-100 text-slate-500 hover:border-red-200 hover:text-red-600' }} uppercase">
                    {{ $cleanName }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-primary-red text-white flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
            </div>
            <h2 class="font-semibold text-gray-800">Filter Data Lainnya</h2>
        </div>
        <div class="p-5">
            <form method="GET" action="{{ route('operational.repair_reject.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <input type="hidden" name="line" value="{{ request('line') }}">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Tanggal</label>
                    <input type="date" name="date" value="{{ request('date') }}" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 focus:border-primary-red outline-none transition bg-white">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Tipe</label>
                    <select name="type" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 focus:border-primary-red outline-none transition bg-white cursor-pointer">
                        <option value="">Semua Tipe</option>
                        <option value="repair" {{ request('type') == 'repair' ? 'selected' : '' }}>Repair</option>
                        <option value="reject" {{ request('type') == 'reject' ? 'selected' : '' }}>Reject</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-1.5">Nama Defect</label>
                    <input type="text" name="defect" value="{{ request('defect') }}" placeholder="Cari defect..." class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-red-200 focus:border-primary-red outline-none transition bg-white">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-primary-red text-white font-bold py-2.5 rounded-xl hover:bg-red-700 transition text-sm shadow-md">Tampilkan</button>
                    <a href="{{ route('operational.repair_reject.index') }}" class="flex-1 text-center bg-slate-100 text-slate-600 font-bold py-2.5 rounded-xl hover:bg-slate-200 transition text-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- DATA TABLE --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Daftar Insiden Kualitas</h2>
            <span class="text-xs text-slate-400 font-bold">{{ $logs->total() }} data ditemukan</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">Waktu</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">Job</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">Tipe</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">Defect</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest">Qty A</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest">Qty B</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest">Pcs Ke-</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">Area</th>
                        <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">Operator</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest">Foto</th>
                        <th class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($logs as $log)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-mono text-xs font-bold text-slate-700">{{ $log->created_at->format('H:i') }}</div>
                            <div class="text-[10px] text-slate-400">{{ $log->created_at->format('d M Y') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-xs text-slate-800">{{ $log->jobMaster?->job_number ?? '-' }}</div>
                            <div class="text-[10px] text-slate-400 truncate max-w-[120px]">{{ $log->jobMaster?->job_name ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if($log->type === 'repair')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-orange-50 text-orange-600 font-black text-[10px] uppercase border border-orange-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    Repair
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-50 text-red-600 font-black text-[10px] uppercase border border-red-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Reject
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-bold text-xs text-slate-800">{{ $log->defect_name }}</div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-black text-sm {{ $log->type === 'repair' ? 'text-orange-600' : 'text-red-600' }}">{{ number_format($log->qty_a) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs text-slate-500">{{ $log->qty_b ? number_format($log->qty_b) : '-' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-mono text-xs font-bold text-slate-700">{{ $log->pcs_number ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs text-slate-600">{{ $log->area_problem ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-xs text-slate-600">{{ $log->creator?->name ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($log->images->count() > 0)
                                <div class="flex items-center justify-center -space-x-2 overflow-hidden">
                                    @foreach($log->images->take(3) as $img)
                                        <img src="{{ asset('uploads/' . $img->image_path) }}" 
                                             onerror="this.src='/images/no-image.png'"
                                             onclick='openImagePreview(@json($log->images->pluck('image_path')->map(fn($p) => asset("storage/".$p))->toArray()))'
                                             class="inline-block h-8 w-8 rounded-lg ring-2 ring-white object-cover cursor-pointer hover:scale-110 hover:z-10 transition duration-200" 
                                             alt="Defect image">
                                    @endforeach
                                    @if($log->images->count() > 3)
                                        <div onclick='openImagePreview(@json($log->images->pluck("image_path")->map(fn($p) => asset("storage/".$p))->toArray()))'
                                             class="flex items-center justify-center h-8 w-8 rounded-lg ring-2 ring-white bg-slate-100 text-[10px] font-black text-slate-500 cursor-pointer hover:bg-slate-200 transition">
                                             +{{ $log->images->count() - 3 }}
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="text-[10px] text-slate-300">-</span>
                            @endif
                        </td>
                        @php
                            $detailImages = $log->images->pluck('image_path')->map(fn($p) => asset('uploads/'.$p))->toArray();
                            $detailJson = json_encode($detailImages);
                        @endphp
                        <td class="px-4 py-3 text-center">
                            <button onclick='showDetailModal({{ $log->id }}, @json($log->defect_name), @json($log->type), {{ $log->qty_a }}, @json($log->pcs_number ?? ''), @json($log->root_cause ?? ''), @json($log->countermeasure ?? ''), @json($log->area_problem ?? ''), {!! $detailJson !!})'
                                class="p-1.5 rounded-lg bg-slate-50 text-slate-400 hover:bg-slate-600 hover:text-white transition-all inline-flex items-center justify-center" title="Lihat Detail">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <button onclick='showHistoryEditModal({{ $log->id }}, @json($log->defect_name), {{ $log->qty_a }}, {{ $log->qty_b ?? 'null' }}, @json($log->pcs_number ?? ''), @json($log->area_problem ?? ''), @json($log->root_cause ?? ''), @json($log->countermeasure ?? ''), {!! $detailJson !!})'
                                class="p-1.5 rounded-lg bg-slate-50 text-slate-500 hover:bg-blue-600 hover:text-white transition-all inline-flex items-center justify-center" title="Lengkapi Catatan / Masalah">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center gap-3">
                                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p class="font-bold text-slate-400">Tidak ada data</p>
                                <p class="text-xs text-slate-300">Belum ada insiden repair/reject yang dicatat</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
            {{ $logs->links() }}
        </div>
        @endif
    </div>

</div>

{{-- IMAGE PREVIEW MODAL --}}
<div id="imgPreviewModal" onclick="closeImagePreview()" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="relative max-w-3xl w-full mx-4 flex flex-col items-center justify-center" onclick="event.stopPropagation()">
        <button onclick="closeImagePreview()" class="absolute -top-12 right-4 md:right-0 w-10 h-10 rounded-xl bg-white/10 text-white hover:bg-white/20 transition flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div id="imgPreviewContent" class="flex gap-3 justify-center items-center overflow-x-auto pb-2 w-full"></div>
    </div>
</div>

{{-- DETAIL MODAL --}}
<div id="rrDetailModal" onclick="closeDetailModal()" class="fixed inset-0 z-[9997] hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4 sm:p-6">
    <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[85vh]" id="rrDetailContent">
        <div id="rrDetailHeader" class="px-6 py-4 flex items-center justify-between shrink-0">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-white/70">Detail Insiden</p>
                <h3 id="rrDetailTitle" class="text-lg font-black text-white"></h3>
            </div>
            <button onclick="closeDetailModal()" class="w-8 h-8 rounded-lg bg-white/10 text-white hover:bg-white/20 transition flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4 overflow-y-auto flex-1">
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-slate-50 rounded-xl p-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Tipe</p>
                    <p id="rrDetailType" class="text-sm font-black text-slate-800 uppercase mt-1"></p>
                </div>
                <div class="bg-slate-50 rounded-xl p-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Qty</p>
                    <p id="rrDetailQty" class="text-xl font-black text-slate-800 mt-1"></p>
                </div>
                <div class="bg-slate-50 rounded-xl p-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Pcs Ke-</p>
                    <p id="rrDetailPcs" class="text-sm font-bold text-slate-800 mt-1"></p>
                    <p class="text-[8px] text-slate-400 mt-1 leading-tight">Nomor urut pcs yang di-repair / reject. Contoh: 5, 5-8, 3,5,7</p>
                </div>
            </div>
            <div class="bg-slate-50 rounded-xl p-3">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Area / Problem</p>
                <p id="rrDetailArea" class="text-xs font-bold text-slate-800 mt-1"></p>
            </div>
            <div class="bg-orange-50 rounded-xl p-4 border border-orange-100">
                <p class="text-[10px] font-black text-orange-400 uppercase tracking-widest mb-1">Root Cause</p>
                <p id="rrDetailRoot" class="text-sm text-slate-700 leading-relaxed"></p>
            </div>
            <div class="bg-emerald-50 rounded-xl p-4 border border-emerald-100">
                <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-1">Countermeasure</p>
                <p id="rrDetailCm" class="text-sm text-slate-700 leading-relaxed"></p>
            </div>
            
            {{-- Foto Bukti Split --}}
            <div id="rrDetailImagesContainer" class="hidden space-y-3">
                <div id="rrDetailPartImagesContainer" class="hidden space-y-1">
                    <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Foto Evidence Part</p>
                    <div id="rrDetailPartImages" class="flex gap-2 overflow-x-auto py-1"></div>
                </div>
                <div id="rrDetailToolingImagesContainer" class="hidden space-y-1">
                    <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest">Foto Evidence Tooling</p>
                    <div id="rrDetailToolingImages" class="flex gap-2 overflow-x-auto py-1"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- EDIT MODAL --}}
<div id="rrEditModal" onclick="closeHistoryEditModal()" class="fixed inset-0 z-[9998] hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 sm:p-6">
    <div onclick="event.stopPropagation()" class="bg-white rounded-3xl shadow-2xl w-full max-w-xl overflow-hidden border border-slate-100 transform scale-95 opacity-0 transition-all duration-300 flex flex-col max-h-[85vh]" id="rrEditContent">
        <div class="px-6 sm:px-8 py-4 sm:py-5 bg-gradient-to-r from-slate-800 to-slate-950 text-white flex items-center justify-between shadow-lg shrink-0">
            <div>
                <h3 class="text-sm sm:text-base font-black uppercase tracking-wider" id="rrEditTitle">Ubah Catatan Kualitas</h3>
                <p class="text-[10px] sm:text-xs text-white/80 font-bold uppercase tracking-tight">Edit data insiden kualitas</p>
            </div>
            <button type="button" onclick="closeHistoryEditModal()" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/40 text-white flex items-center justify-center transition focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="rrEditForm" onsubmit="submitEditForm(event)" class="flex flex-col flex-1 overflow-hidden" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="editLogId">
            
            <div class="p-5 sm:p-8 space-y-4 sm:space-y-5 overflow-y-auto flex-1 bg-slate-50/30">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Nama Defect / Kerusakan <span class="text-red-500">*</span></label>
                        <input type="text" id="editDefectName" name="defect_name" required class="w-full border border-gray-300 rounded-xl px-4 py-2 sm:py-2.5 text-sm focus:border-red-500 focus:ring focus:ring-red-200/50 outline-none transition duration-200 bg-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Qty A (Utama) <span class="text-red-500">*</span></label>
                        <input type="number" id="editQtyA" name="qty_a" required min="0" class="w-full border border-gray-300 rounded-xl px-4 py-2 sm:py-2.5 text-sm font-black focus:border-red-500 focus:ring focus:ring-red-200/50 outline-none transition duration-200 bg-white">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Qty B (Opsional)</label>
                        <input type="number" id="editQtyB" name="qty_b" min="0" class="w-full border border-gray-300 rounded-xl px-4 py-2 sm:py-2.5 text-sm focus:border-red-500 focus:ring focus:ring-red-200/50 outline-none transition duration-200 bg-white">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Nomor Pcs (Opsional)</label>
                        <input type="text" id="editPcsNumber" name="pcs_number" class="w-full border border-gray-300 rounded-xl px-4 py-2 sm:py-2.5 text-sm focus:border-red-500 focus:ring focus:ring-red-200/50 outline-none transition duration-200 bg-white" placeholder="Contoh: 5, 5-8, 3,5,7">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Area Problem / Problem</label>
                        <input type="text" id="editAreaProblem" name="area_problem" class="w-full border border-gray-300 rounded-xl px-4 py-2 sm:py-2.5 text-sm focus:border-red-500 focus:ring focus:ring-red-200/50 outline-none transition duration-200 bg-white">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Penyebab Utama (Root Cause)</label>
                        <textarea id="editRootCause" name="root_cause" rows="2" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:border-red-500 focus:ring focus:ring-red-200/50 outline-none resize-none transition duration-200 bg-white"></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5 ml-1">Tindakan Pencegahan (Countermeasure)</label>
                        <textarea id="editCountermeasure" name="countermeasure" rows="2" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:border-red-500 focus:ring focus:ring-red-200/50 outline-none resize-none transition duration-200 bg-white"></textarea>
                    </div>
                </div>
                
                <!-- Upload Section Split -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">
                    <!-- Part Upload -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Tambah Foto Evidence Part</label>
                        <div id="editDragZonePart" class="relative group mt-1 flex flex-col justify-center px-4 py-4 border-2 border-gray-300 border-dashed rounded-xl hover:border-red-400 hover:bg-slate-50 transition cursor-pointer bg-white">
                            <div class="space-y-1 text-center" onclick="document.getElementById('editImagesPart').click()">
                                <svg class="mx-auto h-7 w-7 text-slate-400 group-hover:text-red-400 transition" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20a4 4 0 004 4h20a4 4 0 004-4V20m-6-12l-6-6m0 0L12 14M24 2l6 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-[11px] text-slate-600 font-bold justify-center">
                                    <span class="text-red-600 hover:text-red-500">Pilih file gambar</span>
                                    <p class="pl-1 text-slate-500 font-medium">atau drag & drop</p>
                                </div>
                                <p class="text-[8px] text-slate-400 font-bold uppercase tracking-tight">PNG, JPG, WEBP hingga 5MB</p>
                            </div>
                            <input id="editImagesPart" type="file" class="hidden" multiple accept="image/*" onchange="previewHistoryEditImages(event, 'part')">
                            
                            <button type="button" onclick="document.getElementById('editCameraPart').click(); event.stopPropagation();" class="mt-2.5 w-full flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg bg-orange-50 border border-orange-200 text-orange-600 hover:bg-orange-100 hover:text-orange-700 transition font-black text-[9px] uppercase tracking-wider shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Ambil Kamera Part
                            </button>
                            <input id="editCameraPart" type="file" class="hidden" accept="image/*" capture="environment" onchange="previewHistoryEditImages(event, 'part')">
                        </div>
                        <div id="editImagePreviewPart" class="flex flex-wrap gap-2 mt-2"></div>
                    </div>

                    <!-- Tooling Upload -->
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Tambah Foto Evidence Tooling</label>
                        <div id="editDragZoneTooling" class="relative group mt-1 flex flex-col justify-center px-4 py-4 border-2 border-gray-300 border-dashed rounded-xl hover:border-red-400 hover:bg-slate-50 transition cursor-pointer bg-white">
                            <div class="space-y-1 text-center" onclick="document.getElementById('editImagesTooling').click()">
                                <svg class="mx-auto h-7 w-7 text-slate-400 group-hover:text-red-400 transition" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                    <path d="M28 8H12a4 4 0 00-4 4v20a4 4 0 004 4h20a4 4 0 004-4V20m-6-12l-6-6m0 0L12 14M24 2l6 6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-[11px] text-slate-600 font-bold justify-center">
                                    <span class="text-red-600 hover:text-red-500">Pilih file gambar</span>
                                    <p class="pl-1 text-slate-500 font-medium">atau drag & drop</p>
                                </div>
                                <p class="text-[8px] text-slate-400 font-bold uppercase tracking-tight">PNG, JPG, WEBP hingga 5MB</p>
                            </div>
                            <input id="editImagesTooling" type="file" class="hidden" multiple accept="image/*" onchange="previewHistoryEditImages(event, 'tooling')">
                            
                            <button type="button" onclick="document.getElementById('editCameraTooling').click(); event.stopPropagation();" class="mt-2.5 w-full flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-lg bg-orange-50 border border-orange-200 text-orange-600 hover:bg-orange-100 hover:text-orange-700 transition font-black text-[9px] uppercase tracking-wider shadow-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Ambil Kamera Tooling
                            </button>
                            <input id="editCameraTooling" type="file" class="hidden" accept="image/*" capture="environment" onchange="previewHistoryEditImages(event, 'tooling')">
                        </div>
                        <div id="editImagePreviewTooling" class="flex flex-wrap gap-2 mt-2"></div>
                    </div>
                </div>

                <!-- Existing Photos Split Display -->
                <div id="editImagesPreviewContainer" class="hidden space-y-3 border-t border-slate-150 pt-3">
                    <div id="editPartImagesContainer" class="hidden space-y-1">
                        <p class="text-[10px] font-black text-orange-500 uppercase tracking-widest">Foto Evidence Part Saat Ini</p>
                        <div id="editPartImagesPreview" class="flex gap-2 overflow-x-auto py-1"></div>
                    </div>
                    <div id="editToolingImagesContainer" class="hidden space-y-1">
                        <p class="text-[10px] font-black text-blue-500 uppercase tracking-widest">Foto Evidence Tooling Saat Ini</p>
                        <div id="editToolingImagesPreview" class="flex gap-2 overflow-x-auto py-1"></div>
                    </div>
                </div>
            </div>

            <!-- Sticky Footer -->
            <div class="px-6 sm:px-8 py-3.5 bg-white border-t border-slate-150 flex gap-3 justify-end shrink-0">
                <button type="button" onclick="closeHistoryEditModal()" class="px-5 py-2 sm:py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs sm:text-sm transition-all focus:outline-none">Batal</button>
                <button type="submit" class="px-5 py-2 sm:py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs sm:text-sm transition-all shadow-md focus:outline-none">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
window.editSelectedPartFiles = [];
window.editSelectedToolingFiles = [];

// Client-side WebP Conversion and Compression for Edit Form
window.compressAndConvertToWebP = function (file, prefix) {
    return new Promise((resolve, reject) => {
        if (!file.type.startsWith('image/')) {
            reject(new Error("File is not an image"));
            return;
        }

        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (event) => {
            const img = new Image();
            img.src = event.target.result;
            img.onload = () => {
                const canvas = document.createElement('canvas');
                
                // Limit maximum dimension to 1280px to prevent heavy uploads
                let width = img.width;
                let height = img.height;
                const maxDim = 1280;
                if (width > maxDim || height > maxDim) {
                    if (width > height) {
                        height = Math.round((height * maxDim) / width);
                        width = maxDim;
                    } else {
                        width = Math.round((width * maxDim) / height);
                        height = maxDim;
                    }
                }

                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    if (!blob) {
                        reject(new Error("Canvas conversion to Blob failed"));
                        return;
                    }
                    const baseName = file.name.substring(0, file.name.lastIndexOf('.')) || file.name;
                    const newFileName = `${prefix}_${baseName.replace(/[^a-zA-Z0-9_.-]/g, '_')}_${Date.now()}.webp`;
                    const convertedFile = new File([blob], newFileName, {
                        type: 'image/webp',
                        lastModified: Date.now()
                    });
                    resolve(convertedFile);
                }, 'image/webp', 0.75); // 0.75 quality is optimal for compression
            };
            img.onerror = (err) => reject(err);
        };
        reader.onerror = (err) => reject(err);
    });
};

window.setupEditDragAndDrop = function () {
    ['part', 'tooling'].forEach(cat => {
        const zoneId = cat === 'part' ? 'editDragZonePart' : 'editDragZoneTooling';
        const zone = document.getElementById(zoneId);
        if (!zone) return;

        // Prevent default drag behaviors
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, e => {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        // Add/remove hover styling
        ['dragenter', 'dragover'].forEach(eventName => {
            zone.addEventListener(eventName, () => {
                zone.classList.remove('border-slate-200', 'bg-slate-50/50');
                zone.classList.add('border-red-400', 'bg-slate-100/80');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            zone.addEventListener(eventName, () => {
                zone.classList.remove('border-red-400', 'bg-slate-100/80');
                zone.classList.add('border-slate-200', 'bg-slate-50/50');
            }, false);
        });

        // Handle dropped files
        zone.addEventListener('drop', async (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                const targetArray = cat === 'part' ? window.editSelectedPartFiles : window.editSelectedToolingFiles;
                
                // Show processing indicator
                const containerId = cat === 'part' ? 'editImagePreviewPart' : 'editImagePreviewTooling';
                const container = document.getElementById(containerId);
                if (container) {
                    container.innerHTML = '<span class="text-[10px] text-orange-500 font-bold uppercase animate-pulse">Memproses gambar...</span>';
                }

                for (let i = 0; i < files.length; i++) {
                    try {
                        const webpFile = await window.compressAndConvertToWebP(files[i], cat);
                        targetArray.push(webpFile);
                    } catch (err) {
                        console.error("Failed to convert image: ", err);
                        targetArray.push(files[i]);
                    }
                }
                window.renderEditPreviews(cat);
            }
        }, false);
    });
};

function openImagePreview(images) {
    const modal = document.getElementById('imgPreviewModal');
    const content = document.getElementById('imgPreviewContent');
    content.innerHTML = images.map(src => `
        <img src="${src}" class="max-h-[70vh] rounded-xl object-contain shadow-2xl flex-shrink-0" onerror="this.src='/images/no-image.png'">
    `).join('');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeImagePreview() {
    const modal = document.getElementById('imgPreviewModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function showDetailModal(id, defect, type, qty, pcs, root, cm, area, images = []) {
    document.getElementById('rrDetailTitle').innerText = defect;
    document.getElementById('rrDetailType').innerText = type;
    document.getElementById('rrDetailQty').innerText = qty + ' PCS';
    document.getElementById('rrDetailPcs').innerText = pcs || '-';
    document.getElementById('rrDetailRoot').innerText = root || '-';
    document.getElementById('rrDetailCm').innerText = cm || '-';
    document.getElementById('rrDetailArea').innerText = area || '-';
    
    // Header Color Gradient based on Type
    const header = document.getElementById('rrDetailHeader');
    header.className = 'px-6 py-4 flex items-center justify-between shrink-0 ' + (type === 'repair' ? 'bg-gradient-to-r from-orange-500 to-amber-500' : 'bg-gradient-to-r from-red-600 to-rose-600');
    
    // Handle Images rendering (Split dynamically based on filename)
    const imgsContainer = document.getElementById('rrDetailImagesContainer');
    const partContainer = document.getElementById('rrDetailPartImagesContainer');
    const partDiv = document.getElementById('rrDetailPartImages');
    const toolingContainer = document.getElementById('rrDetailToolingImagesContainer');
    const toolingDiv = document.getElementById('rrDetailToolingImages');

    partContainer.classList.add('hidden');
    toolingContainer.classList.add('hidden');
    partDiv.innerHTML = '';
    toolingDiv.innerHTML = '';

    if (images && images.length > 0) {
        let hasPart = false;
        let hasTooling = false;

        images.forEach(src => {
            const filename = src.split('/').pop().toLowerCase();
            const isTooling = filename.includes('tooling_');
            
            const html = `
                <a href="${src}" target="_blank" class="block w-20 h-20 rounded-xl overflow-hidden border border-slate-200 shadow-sm flex-shrink-0 hover:scale-105 transition-all">
                    <img src="${src}" class="w-full h-full object-cover" onerror="this.src='/images/no-image.png';this.onerror='';">
                </a>
            `;

            if (isTooling) {
                toolingDiv.innerHTML += html;
                hasTooling = true;
            } else {
                partDiv.innerHTML += html;
                hasPart = true;
            }
        });

        if (hasPart) partContainer.classList.remove('hidden');
        if (hasTooling) toolingContainer.classList.remove('hidden');
        imgsContainer.classList.remove('hidden');
    } else {
        imgsContainer.classList.add('hidden');
    }

    const modal = document.getElementById('rrDetailModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    setTimeout(() => {
        const content = document.getElementById('rrDetailContent');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 50);
}

function closeDetailModal() {
    const content = document.getElementById('rrDetailContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        const modal = document.getElementById('rrDetailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 150);
}

function showHistoryEditModal(id, defect, qtyA, qtyB, pcs, area, root, cm, images = []) {
    document.getElementById('editLogId').value = id;
    document.getElementById('editDefectName').value = defect;
    document.getElementById('editQtyA').value = qtyA;
    document.getElementById('editQtyB').value = qtyB || '';
    document.getElementById('editPcsNumber').value = pcs || '';
    document.getElementById('editAreaProblem').value = area || '';
    document.getElementById('editRootCause').value = root || '';
    document.getElementById('editCountermeasure').value = cm || '';

    // Reset selectors, preview containers, and state arrays
    if (document.getElementById('editImagesPart')) document.getElementById('editImagesPart').value = '';
    if (document.getElementById('editCameraPart')) document.getElementById('editCameraPart').value = '';
    if (document.getElementById('editImagePreviewPart')) document.getElementById('editImagePreviewPart').innerHTML = '';
    window.editSelectedPartFiles = [];

    if (document.getElementById('editImagesTooling')) document.getElementById('editImagesTooling').value = '';
    if (document.getElementById('editCameraTooling')) document.getElementById('editCameraTooling').value = '';
    if (document.getElementById('editImagePreviewTooling')) document.getElementById('editImagePreviewTooling').innerHTML = '';
    window.editSelectedToolingFiles = [];

    // Handle current existing images display (Split dynamically)
    const imgsContainer = document.getElementById('editImagesPreviewContainer');
    const partContainer = document.getElementById('editPartImagesContainer');
    const partDiv = document.getElementById('editPartImagesPreview');
    const toolingContainer = document.getElementById('editToolingImagesContainer');
    const toolingDiv = document.getElementById('editToolingImagesPreview');

    partContainer.classList.add('hidden');
    toolingContainer.classList.add('hidden');
    partDiv.innerHTML = '';
    toolingDiv.innerHTML = '';

    if (images && images.length > 0) {
        let hasPart = false;
        let hasTooling = false;

        images.forEach(src => {
            const filename = src.split('/').pop().toLowerCase();
            const isTooling = filename.includes('tooling_');

            const html = `
                <div class="relative w-16 h-16 rounded-xl overflow-hidden border border-slate-200 shadow-sm flex-shrink-0">
                    <img src="${src}" class="w-full h-full object-cover" onerror="this.src='/images/no-image.png'">
                </div>
            `;

            if (isTooling) {
                toolingDiv.innerHTML += html;
                hasTooling = true;
            } else {
                partDiv.innerHTML += html;
                hasPart = true;
            }
        });

        if (hasPart) partContainer.classList.remove('hidden');
        if (hasTooling) toolingContainer.classList.remove('hidden');
        imgsContainer.classList.remove('hidden');
    } else {
        imgsContainer.classList.add('hidden');
    }

    // Set up drag and drop
    setTimeout(() => {
        window.setupEditDragAndDrop();
    }, 50);

    const modal = document.getElementById('rrEditModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(() => {
        const content = document.getElementById('rrEditContent');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');
    }, 50);
}

function closeHistoryEditModal() {
    const content = document.getElementById('rrEditContent');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        const modal = document.getElementById('rrEditModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 150);
}

window.previewHistoryEditImages = async function (event, category) {
    const files = event.target.files;
    if (!files || files.length === 0) return;
    
    event.target.disabled = true;

    // Show processing indicator
    const containerId = category === 'part' ? 'editImagePreviewPart' : 'editImagePreviewTooling';
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = '<span class="text-[10px] text-orange-500 font-bold uppercase animate-pulse">Memproses gambar...</span>';
    }

    const targetArray = category === 'part' ? window.editSelectedPartFiles : window.editSelectedToolingFiles;
    for (let i = 0; i < files.length; i++) {
        try {
            const webpFile = await window.compressAndConvertToWebP(files[i], category);
            targetArray.push(webpFile);
        } catch (e) {
            console.error("Failed to convert image to WebP: ", e);
            targetArray.push(files[i]);
        }
    }

    window.renderEditPreviews(category);
    
    event.target.disabled = false;
    event.target.value = '';
};

window.renderEditPreviews = function (category) {
    const containerId = category === 'part' ? 'editImagePreviewPart' : 'editImagePreviewTooling';
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '';
    const filesArray = category === 'part' ? window.editSelectedPartFiles : window.editSelectedToolingFiles;

    filesArray.forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = function (e) {
            const div = document.createElement('div');
            div.className = 'relative w-16 h-16 rounded-xl overflow-hidden border border-slate-200 shadow-sm flex-shrink-0 group hover:scale-105 transition-all';
            div.innerHTML = `
                <img src="${e.target.result}" class="w-full h-full object-cover">
                <span class="absolute top-0.5 left-0.5 bg-blue-500 text-white rounded-full px-1.5 py-0.5 text-[8px] font-black uppercase tracking-tight">Baru</span>
                <button type="button" onclick="window.removeEditImage('${category}', ${index})" class="absolute top-0.5 right-0.5 w-4 h-4 rounded-full bg-red-600/90 text-white flex items-center justify-center hover:bg-red-700 transition shadow focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            `;
            container.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
};

window.removeEditImage = function (category, index) {
    if (category === 'part') {
        window.editSelectedPartFiles.splice(index, 1);
    } else {
        window.editSelectedToolingFiles.splice(index, 1);
    }
    window.renderEditPreviews(category);
};

function submitEditForm(event) {
    event.preventDefault();
    const id = document.getElementById('editLogId').value;
    const form = document.getElementById('rrEditForm');
    const formData = new FormData(form);

    // Remove any standard images[] entries from fields
    formData.delete('images[]');

    // Append newly chosen & WebP-compressed images
    const allNewFiles = [...(window.editSelectedPartFiles || []), ...(window.editSelectedToolingFiles || [])];
    allNewFiles.forEach(file => {
        formData.append('images[]', file);
    });

    fetch(`/operational/repair-reject/${id}/update`, {
        method: 'POST', // Use POST for multipart form uploads in Laravel
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            closeHistoryEditModal();
            if (window.showToast) {
                window.showToast(res.message, 'success');
            } else {
                alert(res.message);
            }
            setTimeout(() => location.reload(), 800);
        } else {
            alert(res.message || 'Gagal memperbarui data.');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Terjadi kesalahan saat menyimpan data.');
    });
}

// Auto setup on load
document.addEventListener('DOMContentLoaded', () => {
    window.setupEditDragAndDrop();
});
</script>
@endsection

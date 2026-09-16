@extends('layouts.app')
@section('content')
    @section('title', 'Defect Monitoring')
    <div class="max-w-7xl mx-auto pb-12">

        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800">Defect Monitoring</h1>
                <p class="text-sm font-semibold text-slate-400 mt-1">Monitoring defect hasil inspeksi — {{ $bulanLabel }} {{ $tahun }}</p>
            </div>
            <form method="GET" action="{{ route('quality.defect_monitoring') }}" class="flex items-center gap-2">
                @php
                    $months = [
                        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                        '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
                    ];
                @endphp
                <select name="bulan" class="border border-slate-300 text-sm rounded-xl px-3 py-2.5 font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}" {{ $bulan == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="tahun" class="border border-slate-300 text-sm rounded-xl px-3 py-2.5 font-semibold text-slate-700 bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @for($y = date('Y'); $y >= date('Y')-3; $y--)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                <button type="submit" class="bg-red-700 hover:bg-red-600 text-white px-4 py-2.5 rounded-xl text-sm font-bold transition-colors">Tampilkan</button>
            </form>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Inspeksi</p>
                <p class="text-3xl font-black text-slate-800">{{ number_format($totalLi, 0, ',', '.') }}</p>
            </div>
            <div class="bg-rose-50 border border-rose-100 p-5 rounded-2xl">
                <p class="text-[11px] font-black text-rose-600 uppercase tracking-widest mb-1">Total NG</p>
                <p class="text-3xl font-black text-rose-700">{{ number_format($totalNg, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-rose-500 mt-1">{{ $ngRate }}% NG rate</p>
            </div>
            <div class="bg-amber-50 border border-amber-100 p-5 rounded-2xl">
                <p class="text-[11px] font-black text-amber-600 uppercase tracking-widest mb-1">Total Kasus Defect</p>
                <p class="text-3xl font-black text-amber-700">{{ number_format($totalCases, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-amber-500 mt-1">terdeteksi dari NG</p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <h3 class="text-base font-black text-slate-800 mb-6">Frekuensi Defect / Problem</h3>
            @if(count($defectRows) > 0)
                <div class="space-y-4">
                    @foreach($defectRows as $i => $row)
                        <div class="flex items-center gap-4 p-4 bg-white hover:bg-slate-50 rounded-2xl border border-slate-100 transition-colors">
                            <div class="w-9 h-9 rounded-full {{ $i === 0 ? 'bg-rose-500 text-white' : ($i === 1 ? 'bg-amber-500 text-white' : ($i === 2 ? 'bg-emerald-500 text-white' : 'bg-slate-100 text-slate-500')) }} flex items-center justify-center text-xs font-black shrink-0">#{{ $i + 1 }}</div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-4">
                                    <p class="text-sm font-black text-slate-700 truncate">{{ $row['name'] }}</p>
                                    <span class="text-xs font-black text-slate-400 shrink-0">{{ $row['percentage'] }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full mt-2 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-rose-500 to-rose-400 rounded-full transition-all duration-1000" style="width: {{ $row['percentage'] }}%"></div>
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="text-xl font-black text-slate-800">{{ $row['count'] }}</span>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Kasus</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-10 text-center text-emerald-600 font-bold text-sm bg-emerald-50/50 rounded-2xl border border-dashed border-emerald-200 flex flex-col items-center gap-3">
                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center text-2xl mb-1">🎉</div>
                    Luar biasa! Tidak ada data defect pada periode ini.
                </div>
            @endif
        </div>
    </div>
@endsection

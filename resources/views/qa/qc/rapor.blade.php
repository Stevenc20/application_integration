@extends('layouts.app')
@section('content')
    <div class="max-w-6xl mx-auto pb-12">
        
        {{-- Header Section: Professional & High Contrast --}}
        <div class="bg-slate-900 text-white p-8 border-b-4 border-rose-700 shadow-sm mb-8 flex flex-col md:flex-row justify-between md:items-end gap-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Rapor Kinerja Quality Control</p>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight text-white mb-2">{{ $user->name }}</h1>
                <div class="flex items-center gap-4 text-sm font-semibold text-slate-300">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                        NIP: {{ $user->employee_id ?? '—' }}
                    </span>
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-rose-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                        Line: {{ $user->assigned_line ?? 'Semua Line' }}
                    </span>
                </div>
            </div>
            <div class="text-left md:text-right">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Periode Pantauan</p>
                <form method="GET" action="{{ route('qc.rapor') }}" class="flex items-center gap-2">
                    @php
                        $selectedMonth = request('month', date('m'));
                        $selectedYear = request('year', date('Y'));
                        $months = [
                            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                            '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                        ];
                    @endphp
                    <select name="month" class="bg-slate-800 border-slate-700 text-white text-sm rounded-lg focus:ring-rose-500 focus:border-rose-500 block py-2 px-3 font-semibold">
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <select name="year" class="bg-slate-800 border-slate-700 text-white text-sm rounded-lg focus:ring-rose-500 focus:border-rose-500 block py-2 px-3 font-semibold">
                        @for($y = date('Y'); $y >= date('Y')-3; $y--)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <button type="submit" class="bg-rose-700 hover:bg-rose-600 text-white p-2 rounded-lg transition-colors" title="Terapkan Filter">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    </button>
                    @if(request()->has('month'))
                        <a href="{{ route('qc.rapor') }}" class="bg-slate-700 hover:bg-slate-600 text-white p-2 rounded-lg transition-colors" title="Reset Filter">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Key Performance Indicators (KPIs) --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            {{-- KPI 1: Total Inspeksi --}}
            <div class="bg-white border border-slate-200 border-t-4 border-t-slate-800 shadow-sm p-6 group hover:border-slate-400 transition-colors flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-slate-100 rounded-lg">
                            <svg class="w-6 h-6 text-slate-700" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path></svg>
                        </div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-slate-700">Total Inspeksi</h3>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-5xl font-black text-slate-900">{{ number_format($totalInspeksi, 0, ',', '.') }}</span>
                        <span class="text-xs font-bold text-slate-500 uppercase">Dokumen</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-xs font-bold text-slate-500">Volume pengecekan keseluruhan</p>
                </div>
            </div>

            {{-- KPI 2: Temuan NG --}}
            <div class="bg-white border border-slate-200 border-t-4 border-t-rose-700 shadow-sm p-6 group hover:border-rose-300 transition-colors flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-rose-50 rounded-lg">
                            <svg class="w-6 h-6 text-rose-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        </div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-rose-800">Produk Not Good</h3>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-5xl font-black text-rose-700">{{ number_format($totalNgFound, 0, ',', '.') }}</span>
                        <span class="text-xs font-bold text-rose-600 uppercase">Kasus</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <p class="text-xs font-bold text-slate-500">Frekuensi anomali terdeteksi</p>
                </div>
            </div>

            {{-- KPI 3: QPR Terbit --}}
            <div class="bg-white border border-slate-200 border-t-4 border-t-emerald-600 shadow-sm p-6 group hover:border-emerald-300 transition-colors flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-emerald-50 rounded-lg">
                            <svg class="w-6 h-6 text-emerald-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        </div>
                        <h3 class="text-sm font-black uppercase tracking-wider text-emerald-800">QPR Diajukan</h3>
                    </div>
                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-5xl font-black text-emerald-700">{{ number_format($totalQpr, 0, ',', '.') }}</span>
                        <span class="text-xs font-bold text-emerald-600 uppercase">Laporan</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 flex justify-between items-center">
                    <p class="text-xs font-bold text-slate-500">Tindak lanjut penyimpangan</p>
                    <p class="text-xs font-black text-white bg-emerald-600 px-2.5 py-1 rounded-md">{{ $approvedQpr }} Selesai</p>
                </div>
            </div>

        </div>

        {{-- Main Layout: Chart & Tables --}}
        <div class="space-y-8">
            
            {{-- Trend Chart Full Width --}}
            <div class="bg-white border border-slate-200 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-6 border-b-2 border-slate-100 pb-3">
                    <h2 class="text-lg font-black text-slate-900 uppercase tracking-wide">Tren Inspeksi (6 Bulan Terakhir)</h2>
                </div>
                
                {{-- Background Grid & Chart --}}
                <div class="relative h-72 mt-4">
                    {{-- Grid Lines --}}
                    <div class="absolute inset-0 flex flex-col justify-between pointer-events-none border-b-2 border-slate-200 pb-10 z-0">
                        <div class="w-full border-t border-dashed border-slate-200 h-0"></div>
                        <div class="w-full border-t border-dashed border-slate-200 h-0"></div>
                        <div class="w-full border-t border-dashed border-slate-200 h-0"></div>
                        <div class="w-full border-t border-dashed border-slate-200 h-0"></div>
                    </div>
                    
                    {{-- Bars Container --}}
                    <div class="relative h-full flex items-end justify-between gap-4 px-4 md:px-12 z-10">
                        @foreach(array_reverse($monthlyTrend) as $trend)
                        <div class="flex flex-col justify-end items-center flex-1 h-full group relative">
                            
                            {{-- The Bar Area (Takes up remaining space) --}}
                            <div class="w-full flex-1 flex items-end justify-center relative pb-10">
                                {{-- Tooltip --}}
                                <div class="absolute -top-8 bg-slate-900 text-white text-sm font-black px-3 py-1.5 rounded opacity-0 group-hover:opacity-100 transition-opacity z-20 whitespace-nowrap shadow-lg pointer-events-none">
                                    {{ $trend['count'] }}
                                </div>
                                {{-- Bar Element --}}
                                <div class="w-12 md:w-16 bg-slate-800 group-hover:bg-rose-700 transition-all duration-300 rounded-t-sm" 
                                     style="height: {{ max($trend['percentage'], 2) }}%;"></div>
                            </div>
                            
                            {{-- The Text Area (Positioned naturally at the bottom) --}}
                            <div class="absolute bottom-0 h-10 flex items-center justify-center w-full">
                                <span class="text-[10px] md:text-[11px] font-black uppercase text-slate-500 tracking-wider text-center">{{ $trend['month'] }}</span>
                            </div>
                            
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 2 Tables Side by Side --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                {{-- Table 1: Recent Inspections --}}
                <div class="bg-white border border-slate-200 shadow-sm">
                    <div class="p-5 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                        <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider">Riwayat Inspeksi Terakhir</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white text-slate-400 text-[10px] uppercase font-black tracking-widest border-b-2 border-slate-100">
                                    <th class="p-4">Tanggal</th>
                                    <th class="p-4">Part / Job No</th>
                                    <th class="p-4 text-right">Judgement</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm font-semibold text-slate-700">
                                @forelse($recentInspeksi as $ic)
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                    <td class="p-4 align-top">
                                        <div class="font-bold text-slate-900">{{ $ic->tanggal ? $ic->tanggal->translatedFormat('d M') : '—' }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $ic->tanggal ? $ic->tanggal->format('Y') : '' }}</div>
                                    </td>
                                    <td class="p-4 align-top">
                                        <div class="font-black text-slate-900 leading-tight mb-1">{{ $ic->masterTemplate->part_name ?? '—' }}</div>
                                        <div class="text-xs font-bold text-slate-500">{{ $ic->schedule->job_no ?? $ic->masterTemplate->job_no ?? '—' }}</div>
                                        <div class="mt-2 inline-block px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-sm {{ 
                                            in_array($ic->status, ['finished', 'approved', 'locked']) ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' 
                                        }}">
                                            {{ in_array($ic->status, ['finished', 'approved', 'locked']) ? 'Selesai' : 'Diproses' }}
                                        </div>
                                    </td>
                                    <td class="p-4 text-right align-top">
                                        @if($ic->hasNg())
                                            <span class="text-rose-700 font-black text-lg">NG</span>
                                        @else
                                            <span class="text-emerald-700 font-black text-lg">OK</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="p-8 text-center text-slate-400 font-bold">Belum ada riwayat inspeksi.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Table 2: Recent QPRs --}}
                <div class="bg-white border border-slate-200 shadow-sm">
                    <div class="p-5 bg-slate-900 border-b-4 border-rose-700 flex justify-between items-center">
                        <h2 class="text-sm font-black text-white uppercase tracking-wider">Log Pengajuan QPR</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-white text-slate-400 text-[10px] uppercase font-black tracking-widest border-b-2 border-slate-100">
                                    <th class="p-4">Tanggal / QPR</th>
                                    <th class="p-4">Terkait Part</th>
                                    <th class="p-4 text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm font-semibold text-slate-700">
                                @forelse($recentQprs as $qpr)
                                <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                    <td class="p-4 align-top">
                                        <div class="font-bold text-slate-900">{{ $qpr->tanggal ? \Carbon\Carbon::parse($qpr->tanggal)->translatedFormat('d M') : '—' }}</div>
                                        <div class="text-[10px] text-slate-400 mb-1">{{ $qpr->tanggal ? \Carbon\Carbon::parse($qpr->tanggal)->format('Y') : '' }}</div>
                                        <div class="text-[10px] font-black text-slate-800">{{ $qpr->no_qpr ?? 'DRAFT' }}</div>
                                    </td>
                                    <td class="p-4 align-top">
                                        <div class="font-black text-slate-900 leading-tight mb-1">{{ $qpr->nama_part }}</div>
                                        <div class="text-xs font-bold text-slate-500">{{ $qpr->no_job }}</div>
                                    </td>
                                    <td class="p-4 text-right align-top">
                                        <span class="inline-block px-2 py-1 text-[10px] font-black uppercase tracking-wider rounded-sm {{ 
                                            in_array(strtoupper($qpr->status), ['CLOSE', 'FINISHED']) ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-slate-700 bg-slate-100 border border-slate-200' 
                                        }}">
                                            {{ $qpr->status ?? 'Draft' }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="p-8 text-center text-slate-400 font-bold">Belum pernah mengajukan QPR.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection

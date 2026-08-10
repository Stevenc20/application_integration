@extends('layouts.app')
@section('content')
    <div class="max-w-6xl mx-auto pb-12">
        
        {{-- Header Section --}}
        <div class="bg-slate-900 text-white p-8 border-b-4 border-emerald-600 shadow-sm mb-8 flex flex-col md:flex-row justify-between md:items-end gap-6 rounded-t-3xl">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Monitoring Kinerja Quality Control</p>
                <h1 class="text-3xl md:text-4xl font-black tracking-tight text-white mb-2">Leaderboard Operator</h1>
                <div class="flex items-center gap-4 text-sm font-semibold text-slate-300">
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
                        Pemantau: {{ $user->name }} ({{ $user->role }})
                    </span>
                </div>
            </div>
            <div class="text-left md:text-right">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Periode Pantauan</p>
                <form method="GET" action="{{ route('qc.rapor') }}" class="flex items-center gap-2">
                    @php
                        $months = [
                            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
                            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
                            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
                            '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
                        ];
                    @endphp
                    <select name="month" class="bg-slate-800 border-slate-700 text-white text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block py-2 px-3 font-semibold">
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}" {{ $selectedMonth == $num ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <select name="year" class="bg-slate-800 border-slate-700 text-white text-sm rounded-lg focus:ring-emerald-500 focus:border-emerald-500 block py-2 px-3 font-semibold">
                        @for($y = date('Y'); $y >= date('Y')-3; $y--)
                            <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white p-2 rounded-lg transition-colors" title="Terapkan Filter">
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

        {{-- Leaderboard Section --}}
        <div class="bg-white border-2 border-slate-100 rounded-3xl shadow-sm p-2 md:p-6">
            <div class="px-4 py-3 border-b-2 border-slate-100 mb-4 flex justify-between items-center">
                <h2 class="text-lg font-black text-slate-800 uppercase tracking-widest">Peringkat Keaktifan QC ({{ $months[$selectedMonth] }} {{ $selectedYear }})</h2>
                <span class="text-xs font-bold text-slate-400 bg-slate-100 px-3 py-1 rounded-full">{{ count($leaderboard) }} Operator Aktif</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white text-slate-400 text-[10px] uppercase font-black tracking-widest border-b-2 border-slate-100">
                            <th class="p-4 w-16 text-center">Rank</th>
                            <th class="p-4">Operator</th>
                            <th class="p-4 text-center">Total Inspeksi</th>
                            <th class="p-4 text-center">Temuan NG</th>
                            <th class="p-4 text-center">Pengajuan QPR</th>
                            <th class="p-4 text-right">Skor Total</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm font-semibold text-slate-700">
                        @forelse($leaderboard as $index => $row)
                        <tr class="border-b border-slate-50 hover:bg-slate-50 transition-colors group">
                            <td class="p-4 text-center align-middle">
                                @if($index == 0)
                                    <div class="w-10 h-10 mx-auto rounded-full bg-amber-100 text-amber-600 flex items-center justify-center font-black text-lg border-2 border-amber-300 shadow-sm shadow-amber-200">1</div>
                                @elseif($index == 1)
                                    <div class="w-10 h-10 mx-auto rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-black text-lg border-2 border-slate-300 shadow-sm">2</div>
                                @elseif($index == 2)
                                    <div class="w-10 h-10 mx-auto rounded-full bg-orange-50 text-orange-600 flex items-center justify-center font-black text-lg border-2 border-orange-200 shadow-sm">3</div>
                                @else
                                    <div class="w-10 h-10 mx-auto rounded-full bg-slate-50 text-slate-400 flex items-center justify-center font-bold text-base">{{ $index + 1 }}</div>
                                @endif
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-black border-2 border-white shadow-sm">
                                        {{ substr($row['operator']->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-black text-slate-800 text-base leading-tight">{{ $row['operator']->name }}</div>
                                        <div class="text-[10px] font-bold uppercase text-slate-400 tracking-wider">{{ $row['operator']->employee_id ?? '—' }} | {{ $row['operator']->assigned_line ?? 'Semua Line' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 text-center align-middle">
                                <span class="inline-block bg-blue-50 text-blue-700 px-3 py-1 rounded-lg font-black text-lg border border-blue-100 min-w-[3rem]">
                                    {{ $row['totalInspeksi'] }}
                                </span>
                            </td>
                            <td class="p-4 text-center align-middle">
                                <span class="inline-block bg-rose-50 text-rose-700 px-3 py-1 rounded-lg font-black text-lg border border-rose-100 min-w-[3rem]">
                                    {{ $row['totalNg'] }}
                                </span>
                            </td>
                            <td class="p-4 text-center align-middle">
                                <span class="inline-block bg-emerald-50 text-emerald-700 px-3 py-1 rounded-lg font-black text-lg border border-emerald-100 min-w-[3rem]">
                                    {{ $row['totalQpr'] }}
                                </span>
                            </td>
                            <td class="p-4 text-right align-middle">
                                <div class="text-2xl font-black {{ $index < 3 ? 'text-emerald-600' : 'text-slate-700' }}">
                                    {{ number_format($row['score']) }}
                                </div>
                                <div class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Points</div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center">
                                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                                    <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 9.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <p class="text-slate-400 font-bold">Belum ada data aktivitas QC di bulan ini.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 p-4 bg-slate-50 rounded-2xl border border-slate-100 text-xs font-semibold text-slate-500">
                <span class="font-black text-slate-700">ℹ️ Info Penilaian:</span> Skor dihitung berdasarkan gabungan dari Total Inspeksi yang diselesaikan (1x), penemuan produk NG (2x lipat), dan penerbitan QPR (5x lipat). Tujuan pemeringkatan ini adalah untuk memotivasi keaktifan dan ketelitian pengecekan.
            </div>
        </div>

    </div>
@endsection

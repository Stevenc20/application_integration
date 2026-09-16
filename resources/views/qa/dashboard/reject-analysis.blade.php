@extends('layouts.app')
@section('content')
    @section('title', 'Reject Analysis')
    <div class="max-w-7xl mx-auto pb-12">

        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800">Reject Analysis</h1>
                <p class="text-sm font-semibold text-slate-400 mt-1">Analisa reject & repair hasil inspeksi — {{ $bulanLabel }} {{ $tahun }}</p>
            </div>
            <form method="GET" action="{{ route('quality.reject_analysis') }}" class="flex items-center gap-2">
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

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Produksi</p>
                <p class="text-3xl font-black text-slate-800">{{ number_format($totalProduksi, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-slate-400 mt-1">pcs</p>
            </div>
            <div class="bg-rose-50 border border-rose-100 p-5 rounded-2xl">
                <p class="text-[11px] font-black text-rose-600 uppercase tracking-widest mb-1">Total Reject</p>
                <p class="text-3xl font-black text-rose-700">{{ number_format($totalReject, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-rose-500 mt-1">{{ $rejectRate }}% rate</p>
            </div>
            <div class="bg-amber-50 border border-amber-100 p-5 rounded-2xl">
                <p class="text-[11px] font-black text-amber-600 uppercase tracking-widest mb-1">Total Repair</p>
                <p class="text-3xl font-black text-amber-700">{{ number_format($totalRepair, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-amber-500 mt-1">{{ $repairRate }}% rate</p>
            </div>
            <div class="bg-indigo-50 border border-indigo-100 p-5 rounded-2xl">
                <p class="text-[11px] font-black text-indigo-600 uppercase tracking-widest mb-1">Reject + Repair</p>
                <p class="text-3xl font-black text-indigo-700">{{ number_format($totalReject + $totalRepair, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-indigo-500 mt-1">pcs tidak OK</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                <h3 class="text-base font-black text-slate-800 mb-4">Top Part Reject / Repair</h3>
                @if(count($partReject) > 0)
                    <div class="space-y-3">
                        @foreach($partReject as $i => $row)
                            <div class="flex items-center gap-4 p-4 bg-white hover:bg-slate-50 rounded-2xl border border-slate-100 transition-colors">
                                <div class="w-8 h-8 rounded-full {{ $i < 3 ? 'bg-rose-500 text-white' : 'bg-slate-100 text-slate-500' }} flex items-center justify-center text-xs font-black shrink-0">#{{ $i + 1 }}</div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-black text-slate-700 truncate">{{ $row['part'] }}</p>
                                    <p class="text-xs font-semibold text-slate-400 mt-0.5">{{ $row['repair'] }} repair · {{ $row['reject'] }} reject</p>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="text-lg font-black text-slate-800">{{ $row['reject'] + $row['repair'] }}</span>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">pcs</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm font-bold text-emerald-600 bg-emerald-50 rounded-xl p-6 text-center">Tidak ada reject/repair pada periode ini.</p>
                @endif
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                <h3 class="text-base font-black text-slate-800 mb-4">Trend Reject & Repair Mingguan</h3>
                <div class="relative min-h-[260px] w-full"><canvas id="rejectTrendChart"></canvas></div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <h3 class="text-base font-black text-slate-800 mb-4">Rincian Reject & Repair</h3>
            @if(count($partReject) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                                <th class="py-3 pr-4">#</th>
                                <th class="py-3 pr-4">Part</th>
                                <th class="py-3 pr-4 text-right">Reject (pcs)</th>
                                <th class="py-3 pr-4 text-right">Repair (pcs)</th>
                                <th class="py-3 pr-4 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($partReject as $i => $row)
                                <tr class="border-b border-slate-50">
                                    <td class="py-3 pr-4 text-slate-400 font-bold">{{ $i + 1 }}</td>
                                    <td class="py-3 pr-4 font-bold text-slate-700">{{ $row['part'] }}</td>
                                    <td class="py-3 pr-4 text-right font-bold text-rose-600">{{ number_format($row['reject'], 0, ',', '.') }}</td>
                                    <td class="py-3 pr-4 text-right font-bold text-amber-600">{{ number_format($row['repair'], 0, ',', '.') }}</td>
                                    <td class="py-3 pr-4 text-right font-black text-slate-800">{{ number_format($row['reject'] + $row['repair'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm font-bold text-slate-400 text-center py-8">Tidak ada data reject/repair pada periode ini.</p>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    (function () {
        const weeks = @json(array_keys($trend));
        const rejectData = @json(array_column($trend, 'reject'));
        const repairData = @json(array_column($trend, 'repair'));

        const ctx = document.getElementById('rejectTrendChart');
        if (!ctx) return;
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: weeks,
                datasets: [
                    { label: 'Reject', data: rejectData, backgroundColor: '#f43f5e', borderRadius: 6 },
                    { label: 'Repair', data: repairData, backgroundColor: '#f59e0b', borderRadius: 6 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { font: { weight: 'bold' } } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();
    </script>
@endsection

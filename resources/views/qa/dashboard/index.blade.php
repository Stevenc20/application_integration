@extends('layouts.app')
@section('content')
    @section('title', 'Quality Dashboard')
    <div class="max-w-7xl mx-auto pb-12">

        {{-- Header --}}
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800">Quality Dashboard</h1>
                <p class="text-sm font-semibold text-slate-400 mt-1">Monitoring kualitas inspeksi — {{ $bulanLabel }} {{ $tahun }}</p>
            </div>
            <form method="GET" action="{{ route('quality.dashboard') }}" class="flex items-center gap-2">
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

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Inspeksi</p>
                <p class="text-3xl font-black text-slate-800">{{ number_format($totalLi, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-slate-400 mt-1">{{ number_format($totalProduksi, 0, ',', '.') }} pcs diproduksi</p>
            </div>
            <div class="bg-emerald-50 border border-emerald-100 p-5 rounded-2xl">
                <p class="text-[11px] font-black text-emerald-600 uppercase tracking-widest mb-1">Total OK</p>
                <p class="text-3xl font-black text-emerald-700">{{ number_format($totalOk, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-emerald-500 mt-1">{{ $totalLi ? round(($totalOk / $totalLi) * 100) : 0 }}% dari total</p>
            </div>
            <div class="bg-rose-50 border border-rose-100 p-5 rounded-2xl">
                <p class="text-[11px] font-black text-rose-600 uppercase tracking-widest mb-1">Total NG</p>
                <p class="text-3xl font-black text-rose-700">{{ number_format($totalNg, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-rose-500 mt-1">{{ $ngRate }}% NG rate</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">Repair</p>
                <p class="text-3xl font-black text-amber-500">{{ number_format($totalRepair, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-slate-400 mt-1">pcs diperbaiki</p>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
                <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">Reject</p>
                <p class="text-3xl font-black text-slate-800">{{ number_format($totalReject, 0, ',', '.') }}</p>
                <p class="text-xs font-bold text-slate-400 mt-1">pcs di-reject</p>
            </div>
            <div class="bg-indigo-50 border border-indigo-100 p-5 rounded-2xl">
                <p class="text-[11px] font-black text-indigo-600 uppercase tracking-widest mb-1">QPR</p>
                <p class="text-3xl font-black text-indigo-700">{{ $qprs->count() }}</p>
                <p class="text-xs font-bold text-indigo-500 mt-1">{{ $qprOpen->count() }} open</p>
            </div>
        </div>

        {{-- NG Rate Progress --}}
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm mb-8">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-black text-slate-800">Rasio Hasil Inspeksi</h3>
                <span class="text-sm font-black px-4 py-1.5 rounded-xl border {{ $ngRate > 20 ? 'bg-rose-50 text-rose-600 border-rose-100' : ($ngRate > 10 ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-emerald-50 text-emerald-600 border-emerald-100') }}">NG Rate: {{ $ngRate }}%</span>
            </div>
            <div class="flex h-5 rounded-2xl overflow-hidden bg-slate-100 gap-1 p-1">
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-400 h-full rounded-xl transition-all duration-1000" :style="''" style="width: {{ $totalLi ? round(($totalOk / $totalLi) * 100) : 0 }}%"></div>
                <div class="bg-gradient-to-r from-rose-500 to-rose-400 h-full rounded-xl" style="width: {{ $totalLi ? round(($totalNg / $totalLi) * 100) : 0 }}%"></div>
            </div>
            <div class="flex justify-between mt-3 text-sm font-bold text-slate-500 px-1">
                <span class="flex items-center gap-2 text-emerald-700"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>OK ({{ $totalOk }})</span>
                <span class="flex items-center gap-2 text-rose-700"><span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>NG ({{ $totalNg }})</span>
            </div>
        </div>

        {{-- Status & QPR --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                <h3 class="text-base font-black text-slate-800 mb-4">Status Inspeksi</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-4 bg-emerald-50 rounded-xl">
                        <span class="text-sm font-bold text-emerald-800">Selesai / Approved / Locked</span>
                        <span class="text-xl font-black text-emerald-700">{{ $statusBreakdown['finished'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-amber-50 rounded-xl">
                        <span class="text-sm font-bold text-amber-800">Dalam Proses / Menunggu Approve</span>
                        <span class="text-xl font-black text-amber-700">{{ $statusBreakdown['progress'] }}</span>
                    </div>
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                        <span class="text-sm font-bold text-slate-700">Status Lainnya</span>
                        <span class="text-xl font-black text-slate-600">{{ $statusBreakdown['pending'] }}</span>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <div class="flex items-center justify-between text-sm font-bold text-slate-500">
                        <span>Shift 1 (Pagi)</span><span class="text-lg font-black text-slate-700">{{ $perShift['Shift 1 (Pagi)'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm font-bold text-slate-500 mt-2">
                        <span>Shift 2 (Malam)</span><span class="text-lg font-black text-slate-700">{{ $perShift['Shift 2 (Malam)'] }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-black text-slate-800">Monitoring QPR</h3>
                    <a href="{{ route('qa.qpr.index') }}" class="text-xs font-black text-red-700 hover:text-red-600 uppercase">Lihat semua →</a>
                </div>
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="p-4 bg-rose-50 rounded-xl text-center">
                        <p class="text-2xl font-black text-rose-700">{{ $qprOpen->count() }}</p>
                        <p class="text-[11px] font-black text-rose-500 uppercase tracking-wide">Open QPR</p>
                    </div>
                    <div class="p-4 bg-emerald-50 rounded-xl text-center">
                        <p class="text-2xl font-black text-emerald-700">{{ $qprClosed }}</p>
                        <p class="text-[11px] font-black text-emerald-500 uppercase tracking-wide">Closed</p>
                    </div>
                    <div class="p-4 bg-amber-50 rounded-xl text-center">
                        <p class="text-2xl font-black text-amber-700">{{ $qprWaitingAction }}</p>
                        <p class="text-[11px] font-black text-amber-500 uppercase tracking-wide">Menunggu Tindakan</p>
                    </div>
                    <div class="p-4 bg-indigo-50 rounded-xl text-center">
                        <p class="text-2xl font-black text-indigo-700">{{ $qprWaitingVerif }}</p>
                        <p class="text-[11px] font-black text-indigo-500 uppercase tracking-wide">Menunggu Verifikasi</p>
                    </div>
                </div>
                <div class="space-y-2">
                    @forelse($recentQprs as $q)
                        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
                            <div class="min-w-0">
                                <p class="text-sm font-black text-slate-700 truncate">{{ $q->no_qpr ?: $q->no_job }}</p>
                                <p class="text-xs font-semibold text-slate-400 truncate">{{ $q->nama_part }} — {{ $q->defect }}</p>
                            </div>
                            <span class="text-[10px] font-black uppercase px-2.5 py-1 rounded-lg {{ $q->status == 'Close' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">{{ $q->status }}</span>
                        </div>
                    @empty
                        <p class="text-sm font-bold text-slate-400 text-center py-6">Belum ada QPR pada periode ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Top Defect & Top Part --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                <h3 class="text-base font-black text-slate-800 mb-4">Top Defect / Problem</h3>
                <div class="space-y-3">
                    @forelse($topDefects as $name => $count)
                        <div class="flex items-center gap-4 p-4 bg-white hover:bg-slate-50 rounded-2xl border border-slate-100 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-rose-500 text-white flex items-center justify-center text-xs font-black shrink-0">#{{ $loop->iteration }}</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-black text-slate-700 truncate">{{ $name }}</p>
                                <div class="w-full bg-slate-100 h-1.5 rounded-full mt-2 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-rose-500 to-rose-400 rounded-full" style="width: {{ max($topDefects) ? round(($count / max($topDefects)) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            <span class="text-lg font-black text-slate-800">{{ $count }} <span class="text-[10px] font-bold text-slate-400 uppercase">kasus</span></span>
                        </div>
                    @empty
                        <p class="text-sm font-bold text-emerald-600 bg-emerald-50 rounded-xl p-6 text-center">Luar biasa! Tidak ada data defect pada periode ini.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                <h3 class="text-base font-black text-slate-800 mb-4">Top Part Diinspeksi</h3>
                <div class="space-y-3">
                    @forelse($topParts as $name => $count)
                        <div class="flex items-center gap-4 p-4 bg-white hover:bg-slate-50 rounded-2xl border border-slate-100 transition-colors">
                            <div class="w-8 h-8 rounded-full bg-indigo-500 text-white flex items-center justify-center text-xs font-black shrink-0">#{{ $loop->iteration }}</div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-black text-slate-700 truncate">{{ $name }}</p>
                                <div class="w-full bg-slate-100 h-1.5 rounded-full mt-2 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 to-sky-400 rounded-full" style="width: {{ $topParts->max() ? round(($count / $topParts->max()) * 100) : 0 }}%"></div>
                                </div>
                            </div>
                            <span class="text-lg font-black text-slate-800">{{ $count }} <span class="text-[10px] font-bold text-slate-400 uppercase">form</span></span>
                        </div>
                    @empty
                        <p class="text-sm font-bold text-slate-400 bg-slate-50 rounded-xl p-6 text-center">Tidak ada data part.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Trend Chart --}}
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm mb-8">
            <h3 class="text-base font-black text-slate-800 mb-4">Trend Inspeksi Mingguan</h3>
            <div class="relative min-h-[260px] w-full"><canvas id="trendChart"></canvas></div>
        </div>

        {{-- Recent Activity --}}
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
            <h3 class="text-base font-black text-slate-800 mb-4">Inspeksi Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-black text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 pr-4">Part</th>
                            <th class="py-3 pr-4">Operator</th>
                            <th class="py-3 pr-4">Tanggal</th>
                            <th class="py-3 pr-4">Shift</th>
                            <th class="py-3 pr-4">Judgement</th>
                            <th class="py-3 pr-4">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentItemChecks as $ic)
                            <tr class="border-b border-slate-50">
                                <td class="py-3 pr-4 font-bold text-slate-700">{{ $ic->masterTemplate?->part_name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-500">{{ $ic->operator?->name ?? '—' }}</td>
                                <td class="py-3 pr-4 text-slate-500">{{ $ic->tanggal ? \Carbon\Carbon::parse($ic->tanggal)->format('d/m/Y') : '—' }}</td>
                                <td class="py-3 pr-4 text-slate-500">{{ $ic->shift }}</td>
                                <td class="py-3 pr-4">
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase {{ $ic->judgement == 'NG' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $ic->judgement ?? '—' }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-500 capitalize">{{ str_replace('_', ' ', $ic->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-sm font-bold text-slate-400">Belum ada inspeksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    (function () {
        const weeks = @json(array_keys($trendMingguan));
        const okData = weeks.map(w => {{ json_encode(array_column($trendMingguan, 'OK')) }}[weeks.indexOf(w)]);
        const ngData = weeks.map(w => {{ json_encode(array_column($trendMingguan, 'NG')) }}[weeks.indexOf(w)]);

        const ctx = document.getElementById('trendChart');
        if (!ctx) return;
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: weeks,
                datasets: [
                    { label: 'OK', data: okData, backgroundColor: '#34d399', borderRadius: 6 },
                    { label: 'NG', data: ngData, backgroundColor: '#fb7185', borderRadius: 6 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { font: { weight: 'bold' } } } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });
    })();
    </script>
@endsection

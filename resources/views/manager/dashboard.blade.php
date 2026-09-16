@extends('layouts.supervisor')

@section('title', 'Manager Dashboard')

@section('content')
<div class="p-4 sm:p-6 bg-gray-50 min-h-screen">

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Manager Dashboard</h1>
            <p class="text-gray-500 text-sm">{{ now()->format('d F Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Produksi</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalProduction) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Target</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($targetQty) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Achievement</p>
            <p class="text-2xl font-bold {{ $achievementPercent >= 100 ? 'text-green-600' : ($achievementPercent >= 80 ? 'text-yellow-600' : 'text-red-600') }} mt-1">{{ $achievementPercent }}%</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Reject Rate</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $rejectRate }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-green-50 border border-green-100 rounded-xl p-4 text-center">
            <p class="text-xs text-green-600">OK</p>
            <p class="text-lg font-bold text-green-700">{{ number_format($totalOk) }}</p>
        </div>
        <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4 text-center">
            <p class="text-xs text-yellow-600">Repair</p>
            <p class="text-lg font-bold text-yellow-700">{{ number_format($totalRepair) }}</p>
        </div>
        <div class="bg-red-50 border border-red-100 rounded-xl p-4 text-center">
            <p class="text-xs text-red-600">Reject</p>
            <p class="text-lg font-bold text-red-700">{{ number_format($totalReject) }}</p>
        </div>
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-center">
            <p class="text-xs text-blue-600">Downtime (min)</p>
            <p class="text-lg font-bold text-blue-700">{{ number_format($downtimeToday) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-4">Achievement per Line</h2>
            <div class="space-y-3">
                @foreach($lineStatuses as $line => $status)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-gray-700">{{ $line }}</span>
                        <span class="text-gray-500">{{ number_format($status['ok']) }} / {{ number_format($status['target']) }} ({{ $status['achievement'] }}%)</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $status['achievement'] >= 100 ? 'bg-green-500' : ($status['achievement'] >= 80 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ min($status['achievement'], 100) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-4">Ringkasan Target vs Aktual</h2>
            <div class="flex items-center justify-center h-40">
                <div class="text-center">
                    <p class="text-5xl font-black {{ $achievementPercent >= 100 ? 'text-green-500' : ($achievementPercent >= 80 ? 'text-yellow-500' : 'text-red-500') }}">{{ $achievementPercent }}%</p>
                    <p class="text-sm text-gray-500 mt-2">Achievement Hari Ini</p>
                    <p class="text-xs text-gray-400 mt-1">Target: {{ number_format($targetQty) }} | OK: {{ number_format($totalOk) }}</p>
                </div>
            </div>
        </div>
    </div>

    @include('components.grafik-gsph')

    <div class="mt-6 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-700">Produksi Terbaru</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Line</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">OK</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Repair</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Reject</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentProduction as $prod)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $prod->line }}</td>
                        <td class="px-4 py-3 text-right text-green-600 font-medium">{{ number_format($prod->actual_ok) }}</td>
                        <td class="px-4 py-3 text-right text-yellow-600 font-medium">{{ number_format($prod->actual_repair) }}</td>
                        <td class="px-4 py-3 text-right text-red-600 font-medium">{{ number_format($prod->actual_reject) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-400">Belum ada data produksi hari ini</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-100">
            {{ $recentProduction->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js"></script>
@endsection

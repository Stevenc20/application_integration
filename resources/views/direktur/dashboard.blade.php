@extends('layouts.supervisor')

@section('title', 'Direktur Dashboard')

@section('content')
<div class="p-4 sm:p-6 bg-gray-50 min-h-screen">

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Direktur Dashboard</h1>
            <p class="text-gray-500 text-sm">{{ now()->format('d F Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Produksi</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalProduction) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Achievement</p>
            <p class="text-2xl font-bold {{ $achievementPercent >= 100 ? 'text-green-600' : ($achievementPercent >= 80 ? 'text-yellow-600' : 'text-red-600') }} mt-1">{{ $achievementPercent }}%</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Reject Rate</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $rejectRate }}%</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Weekly Achievement</p>
            <p class="text-2xl font-bold {{ $weeklyAchievement >= 100 ? 'text-green-600' : ($weeklyAchievement >= 80 ? 'text-yellow-600' : 'text-red-600') }} mt-1">{{ $weeklyAchievement }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-4">Line Performance</h2>
            <div class="space-y-4">
                @foreach($lineSummaries as $line => $data)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-semibold text-gray-700">{{ $line }}</span>
                        <span class="text-sm {{ $data['achievement'] >= 100 ? 'text-green-600' : ($data['achievement'] >= 80 ? 'text-yellow-600' : 'text-red-600') }}">{{ $data['achievement'] }}%</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $data['achievement'] >= 100 ? 'bg-green-500' : ($data['achievement'] >= 80 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ min($data['achievement'], 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">{{ number_format($data['ok']) }} / {{ number_format($data['target']) }} pcs</p>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-4">Top Downtime Issues</h2>
            @if($topDowntime->count() > 0)
            <div class="space-y-2">
                @foreach($topDowntime as $dt)
                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                    <span class="text-sm text-gray-700 truncate max-w-[70%]">{{ $dt->problem }}</span>
                    <span class="text-sm font-semibold {{ $dt->total > 60 ? 'text-red-600' : 'text-yellow-600' }}">{{ number_format($dt->total) }} min</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-400 text-center py-8">Tidak ada downtime hari ini</p>
            @endif
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <h2 class="font-semibold text-gray-700 mb-4">Ringkasan Kinerja Harian</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-green-50 rounded-xl">
                <p class="text-sm text-green-600">OK</p>
                <p class="text-xl font-bold text-green-700">{{ number_format($totalOk) }}</p>
            </div>
            <div class="text-center p-4 bg-yellow-50 rounded-xl">
                <p class="text-sm text-yellow-600">Repair</p>
                <p class="text-xl font-bold text-yellow-700">{{ number_format($totalRepair) }}</p>
            </div>
            <div class="text-center p-4 bg-red-50 rounded-xl">
                <p class="text-sm text-red-600">Reject</p>
                <p class="text-xl font-bold text-red-700">{{ number_format($totalReject) }}</p>
            </div>
        </div>
    </div>

    @include('components.grafik-gsph')
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js"></script>
@endsection
    
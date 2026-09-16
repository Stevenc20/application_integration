@extends('layouts.supervisor')

@section('title', 'Kadiv Dashboard')

@section('content')
<div class="p-4 sm:p-6 bg-gray-50 min-h-screen">

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Dashboard Kepala Divisi</h1>
            <p class="text-gray-500 text-sm">{{ now()->format('d F Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Produksi</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($totalProduction) }}</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Achievement</p>
            <p class="text-2xl font-bold {{ $achievementPercent >= 100 ? 'text-green-600' : ($achievementPercent >= 80 ? 'text-yellow-600' : 'text-red-600') }} mt-1">{{ $achievementPercent }}%</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">OK Rate</p>
            <p class="text-2xl font-bold text-green-600 mt-1">{{ $okRate }}%</p>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Reject Rate</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $rejectRate }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-4">Achievement per Line</h2>
            <div class="space-y-4">
                @foreach($lineAchievement as $line => $data)
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-semibold text-gray-700">{{ $line }}</span>
                        <span class="text-gray-500">{{ number_format($data['ok']) }} / {{ number_format($data['target']) }}</span>
                    </div>
                    <div class="h-3 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full {{ $data['achievement'] >= 100 ? 'bg-green-500' : ($data['achievement'] >= 80 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ min($data['achievement'], 100) }}%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1 text-right">{{ $data['achievement'] }}%</p>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-4">Kualitas Produksi</h2>
            <div class="flex items-center justify-center h-48">
                <div class="text-center space-y-4">
                    <div>
                        <p class="text-4xl font-black text-green-500">{{ $okRate }}%</p>
                        <p class="text-sm text-gray-500">OK Rate</p>
                    </div>
                    <div class="flex gap-6 text-sm">
                        <div><span class="text-yellow-600 font-bold">{{ $repairRate }}%</span> Repair</div>
                        <div><span class="text-red-600 font-bold">{{ $rejectRate }}%</span> Reject</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-4">Downtime by Type</h2>
            @if(count($downtimeByType) > 0)
            <div class="space-y-2">
                @foreach($downtimeByType as $type => $total)
                <div class="flex justify-between items-center py-2 border-b border-gray-50">
                    <span class="text-sm text-gray-700 capitalize">{{ str_replace('_', ' ', $type) }}</span>
                    <span class="text-sm font-semibold text-gray-800">{{ number_format($total) }} min</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-gray-400 text-center py-8">Belum ada downtime hari ini</p>
            @endif
        </div>

    </div>

    @include('components.grafik-gsph')
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js"></script>
@endsection

@extends('layouts.supervisor')

@section('title', 'Presdir Dashboard')

@section('content')
<div class="p-4 sm:p-6 bg-gray-50 min-h-screen">

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">President Director Dashboard</h1>
            <p class="text-gray-500 text-sm">{{ now()->format('d F Y') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl p-6 text-white shadow-lg">
            <p class="text-xs opacity-80 uppercase tracking-wide">Total Produksi</p>
            <p class="text-3xl font-black mt-1">{{ number_format($totalProduction) }}</p>
        </div>
        <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-xl p-6 text-white shadow-lg">
            <p class="text-xs opacity-80 uppercase tracking-wide">Achievement</p>
            <p class="text-3xl font-black mt-1">{{ $achievementPercent }}%</p>
        </div>
        <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl p-6 text-white shadow-lg">
            <p class="text-xs opacity-80 uppercase tracking-wide">OK Rate</p>
            <p class="text-3xl font-black mt-1">{{ $okRate }}%</p>
        </div>
        <div class="bg-gradient-to-br from-red-500 to-red-700 rounded-xl p-6 text-white shadow-lg">
            <p class="text-xs opacity-80 uppercase tracking-wide">Reject Rate</p>
            <p class="text-3xl font-black mt-1">{{ $rejectRate }}%</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-4">Monthly Achievement</h2>
            <div class="text-center py-6">
                <p class="text-6xl font-black {{ $monthlyAchievement >= 100 ? 'text-green-500' : ($monthlyAchievement >= 80 ? 'text-yellow-500' : 'text-red-500') }}">{{ $monthlyAchievement }}%</p>
                <p class="text-sm text-gray-500 mt-3">Achievement Bulan Ini</p>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
            <h2 class="font-semibold text-gray-700 mb-4">Line Status Overview</h2>
            <div class="space-y-3">
                @foreach($lineSnapshots as $line => $snap)
                <div class="flex items-center justify-between p-3 rounded-lg {{ $snap['status'] == 'on_track' ? 'bg-green-50' : ($snap['status'] == 'behind' ? 'bg-red-50' : 'bg-gray-50') }}">
                    <div class="flex items-center gap-3">
                        <span class="w-3 h-3 rounded-full {{ $snap['status'] == 'on_track' ? 'bg-green-500' : ($snap['status'] == 'behind' ? 'bg-red-500' : 'bg-gray-400') }}"></span>
                        <span class="font-semibold text-gray-700">{{ $line }}</span>
                    </div>
                    <span class="text-sm font-bold {{ $snap['achievement'] >= 100 ? 'text-green-600' : ($snap['achievement'] >= 80 ? 'text-yellow-600' : 'text-red-600') }}">{{ $snap['achievement'] }}%</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
        <h2 class="font-semibold text-gray-700 mb-4">Ringkasan Eksekutif</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="text-center p-4">
                <p class="text-sm text-gray-500">Total Target</p>
                <p class="text-xl font-bold text-gray-800">{{ number_format($targetQty) }} pcs</p>
            </div>
            <div class="text-center p-4">
                <p class="text-sm text-gray-500">OK Produksi</p>
                <p class="text-xl font-bold text-green-600">{{ number_format($totalOk) }} pcs</p>
            </div>
            <div class="text-center p-4">
                <p class="text-sm text-gray-500">Repair</p>
                <p class="text-xl font-bold text-yellow-600">{{ number_format($totalRepair) }} pcs</p>
            </div>
            <div class="text-center p-4">
                <p class="text-sm text-gray-500">Reject</p>
                <p class="text-xl font-bold text-red-600">{{ number_format($totalReject) }} pcs</p>
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

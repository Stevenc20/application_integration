@extends('layouts.supervisor')

@section('title', 'Foreman Dashboard')

@section('content')
<div class="p-4 sm:p-6 bg-gray-50 min-h-screen">

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Dashboard FOREMAN</h1>
            <p class="text-gray-500 text-sm">{{ now()->format('d F Y') }}</p>
        </div>
    </div>

    @include('components.grafik-gsph')

</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js"></script>
@endsection

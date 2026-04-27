@extends('layouts.supervisor')

@section('title', 'Downtime Dashboard')
@section('header_title', 'Downtime Dashboard')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h5 class="text-lg font-bold text-gray-800">Downtime Dashboard</h5>
    </div>
    <div class="p-12 text-center">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 text-red-600 mb-4">
            <i class="bx bx-error text-4xl"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Modul Sedang Dalam Pengembangan</h3>
        <p class="text-gray-500 max-w-md mx-auto">Fitur Downtime Dashboard untuk Supervisor masih dalam tahap pengembangan. Silakan kembali lagi nanti.</p>
        <div class="mt-6">
            <a href="{{ route('supervisor.dashboard') ?? '#' }}" class="inline-flex items-center px-4 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors">
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>
@endsection

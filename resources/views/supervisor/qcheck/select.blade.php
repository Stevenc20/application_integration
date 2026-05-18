@extends('layouts.supervisor')

@section('title', 'Pilih Item Q-Check')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg flex justify-between items-center">
        <div>
            <h5 class="text-xl font-bold text-gray-800">Daftar Item untuk Q-Check</h5>
            <p class="text-sm text-gray-500 mt-1">Line: {{ $selected_line->namaline }} | Shift: {{ $selected_shift }}</p>
        </div>
        <a href="{{ route('supervisor.qcheck.index') }}" class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium py-2 px-4 rounded-md shadow-sm transition-colors text-sm flex items-center">
            <i class="bx bx-arrow-back mr-1"></i> Kembali
        </a>
    </div>

    <div class="p-5">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Mock Items -->
            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow bg-white flex flex-col h-full">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-blue-100 text-blue-800 mb-2">Job Aktif</span>
                        <h6 class="font-bold text-gray-800 text-lg">JOB-101</h6>
                    </div>
                </div>
                <div class="text-sm text-gray-600 mb-4 flex-grow">
                    <p><span class="font-medium">Item:</span> PN-12345</p>
                    <p><span class="font-medium">Plan:</span> 5,000 pcs</p>
                    <p class="mt-2 text-xs text-green-600 font-medium"><i class="bx bx-check-circle"></i> 1st Piece OK</p>
                </div>
                <a href="{{ route('supervisor.qcheck.list', 1) }}" class="block w-full bg-primary-red hover-bg-primary-red text-white text-center font-medium py-2 rounded-md shadow-sm transition-colors text-sm mt-auto">
                    Pilih Item Ini
                </a>
            </div>

            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow bg-white flex flex-col h-full">
                <div class="flex justify-between items-start mb-3">
                    <div>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800 mb-2">Pending Q-Check</span>
                        <h6 class="font-bold text-gray-800 text-lg">JOB-102</h6>
                    </div>
                </div>
                <div class="text-sm text-gray-600 mb-4 flex-grow">
                    <p><span class="font-medium">Item:</span> PN-67890</p>
                    <p><span class="font-medium">Plan:</span> 2,500 pcs</p>
                    <p class="mt-2 text-xs text-red-600 font-medium"><i class="bx bx-error-circle"></i> Butuh 1st Piece</p>
                </div>
                <a href="{{ route('supervisor.qcheck.list', 2) }}" class="block w-full bg-primary-red hover-bg-primary-red text-white text-center font-medium py-2 rounded-md shadow-sm transition-colors text-sm mt-auto">
                    Pilih Item Ini
                </a>
            </div>
            
            @forelse ($items as $item)
                <!-- Real items loop -->
            @empty
            @endforelse
        </div>
    </div>
</div>
@endsection

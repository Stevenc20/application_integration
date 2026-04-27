@extends('layouts.supervisor')
@section('title', 'Rekap Break')
@section('header_title', 'Rekap Break')

@section('content')
<!-- Card Ringkasan -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4 border-b border-gray-200 bg-red-50 rounded-t-lg">
        <h4 class="text-lg font-bold text-red-900">BREAKTIME - Job {{ $datadetailjob->id_detailjob ?? 'JOB-123' }}</h4>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex justify-between items-center p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <span class="font-semibold text-gray-700">Total Break</span>
                <span class="text-lg font-bold text-primary-red">{{ number_format($total_break ?? 0, 2) }} menit</span>
            </div>
            <div class="flex justify-between items-center p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <span class="font-semibold text-gray-700">Jumlah Break</span>
                <span class="text-lg font-bold text-blue-600">{{ $count_break ?? 0 }} kali</span>
            </div>
        </div>
    </div>
</div>

<!-- Card Detail -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <h5 class="text-lg font-bold text-gray-800">Data Break Time</h5>
        <button id="toggleBreakForm" class="px-4 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors flex items-center justify-center">
            <i class="bx bx-plus mr-1"></i> Tambah Break
        </button>
    </div>

    <div class="p-6">
        <!-- Collapsible form -->
        <div id="addBreakForm" class="hidden mb-6 bg-gray-50 p-4 border border-gray-200 rounded-lg">
            <form action="#" method="post" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                @csrf
                <div class="md:col-span-4">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Jenis Break</label>
                    <input type="text" name="break_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="contoh: Makan Siang">
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Start</label>
                    <div class="flex">
                        <input type="text" name="start_break" id="start_break" class="w-full rounded-l-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Waktu Start">
                        <button type="button" class="px-3 bg-green-600 hover:bg-green-700 text-white rounded-r-md transition-colors" onclick="document.getElementById('start_break').value=new Date().toISOString()">
                            <i class="bx bx-play"></i>
                        </button>
                    </div>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Finish</label>
                    <div class="flex">
                        <input type="text" name="finish_break" id="finish_break" class="w-full rounded-l-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" placeholder="Waktu Finish">
                        <button type="button" class="px-3 bg-red-600 hover:bg-red-700 text-white rounded-r-md transition-colors" onclick="document.getElementById('finish_break').value=new Date().toISOString()">
                            <i class="bx bx-stop"></i>
                        </button>
                    </div>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors">Simpan</button>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-red-50 border-b border-red-100 text-red-900">
                        <th class="py-3 px-4 text-center font-semibold text-sm">Jenis Break</th>
                        <th class="py-3 px-4 text-center font-semibold text-sm">Start</th>
                        <th class="py-3 px-4 text-center font-semibold text-sm">Finish</th>
                        <th class="py-3 px-4 text-center font-semibold text-sm">Durasi (menit)</th>
                        <th class="py-3 px-4 text-center font-semibold text-sm">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($filterbreak ?? [] as $br)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-3 px-4 text-center text-sm font-medium text-gray-900">{{ $br->break_type }}</td>
                        <td class="py-3 px-4 text-center text-sm text-gray-600">{{ $br->start_break }}</td>
                        <td class="py-3 px-4 text-center text-sm text-gray-600">{{ $br->finish_break ?? '-' }}</td>
                        <td class="py-3 px-4 text-center text-sm text-gray-600">{{ number_format($br->duration_break ?? 0, 2) }}</td>
                        <td class="py-3 px-4">
                            <div class="flex flex-col gap-2">
                                @if(!$br->finish_break)
                                    <a href="#" class="px-3 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded transition-colors text-center shadow-sm">Stop</a>
                                @else
                                    <a href="#" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition-colors text-center shadow-sm">Update</a>
                                    <a href="#" class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded transition-colors text-center shadow-sm">Delete</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-8 px-4 text-center text-gray-500">
                            <div class="flex flex-col items-center justify-center">
                                <i class="bx bx-coffee-togo text-4xl text-gray-300 mb-2"></i>
                                <p>Belum ada data break.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.getElementById('toggleBreakForm').addEventListener('click', function() {
        const form = document.getElementById('addBreakForm');
        form.classList.toggle('hidden');
    });
</script>
@endsection

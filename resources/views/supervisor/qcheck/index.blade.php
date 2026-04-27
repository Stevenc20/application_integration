@extends('layouts.supervisor')

@section('title', 'Q-Check Main')
@section('header_title', 'Quality Check')

@section('content')
<div class="grid grid-cols-1 gap-6">

    <!-- Card: Mulai Pencatatan -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-bold text-gray-800">Mulai Pencatatan Q-Check</h5>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 mb-4">Pilih Line dan Shift untuk menampilkan daftar item yang akan di-Q-Check.</p>
            
            <form method="GET" action="{{ route('supervisor.qcheck.select') ?? '#' }}">
                <div class="flex flex-col md:flex-row md:items-end gap-4">
                    <div class="w-full md:w-5/12">
                        <label for="entry_line" class="block text-sm font-medium text-gray-700 mb-1">Line Produksi</label>
                        <select id="entry_line" name="line" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                            <option value="">Pilih Line</option>
                            @foreach($top_dropdown_lines ?? [] as $line)
                                <option value="{{ $line->id }}">{{ $line->namaline }}</option>
                            @endforeach
                            <!-- Template fallback -->
                            @if(empty($top_dropdown_lines))
                                <option value="1">Line A</option>
                                <option value="2">Line B</option>
                            @endif
                        </select>
                    </div>
                    <div class="w-full md:w-5/12">
                        <label for="entry_shift" class="block text-sm font-medium text-gray-700 mb-1">Shift</label>
                        <select id="entry_shift" name="shift" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" required>
                            <option value="">Pilih Shift</option>
                            <option value="1">Shift 1</option>
                            <option value="2">Shift 2</option>
                        </select>
                    </div>
                    <div class="w-full md:w-2/12">
                        <button type="submit" class="w-full px-4 py-2 bg-primary-red hover:bg-red-800 text-white text-sm font-medium rounded-md shadow-sm transition-colors">Tampilkan Item</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Card: History -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h5 class="text-lg font-bold text-gray-800">History Q-Check</h5>
        </div>
        <div class="p-6">
            <form method="GET" action="{{ route('supervisor.qcheck.index') ?? '#' }}">
                <div class="flex flex-col md:flex-row md:items-end gap-4">
                    <div class="w-full md:w-1/3">
                        <label for="history_date" class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                        <input type="date" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50" id="history_date" name="history_date" value="{{ $selected_history_date ?? date('Y-m-d') }}">
                    </div>
                    <div class="w-full md:w-1/3">
                        <label for="history_line" class="block text-sm font-medium text-gray-700 mb-1">Line Produksi</label>
                        <select id="history_line" name="history_line" class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                            <option value="">Semua Line</option>
                            @foreach($history_dropdown_lines ?? [] as $line)
                            <option value="{{ $line->id }}" {{ isset($selected_history_line) && $selected_history_line == $line->id ? 'selected' : '' }}>
                                {{ $line->namaline }} - Shift {{ $line->shift }}
                            </option>
                            @endforeach
                            <!-- Template fallback -->
                            @if(empty($history_dropdown_lines))
                                <option value="1">Line A - Shift 1</option>
                            @endif
                        </select>
                    </div>
                    <div class="w-full md:w-1/3">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md shadow-sm transition-colors">Filter History</button>
                    </div>
                </div>
            </form>

            <hr class="my-6 border-gray-200">

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-red-50 border-b border-red-100 text-red-900">
                            <th class="py-3 px-4 font-semibold text-sm">Item/Part Number</th>
                            <th class="py-3 px-4 font-semibold text-sm text-center">Jenis</th>
                            <th class="py-3 px-4 font-semibold text-sm text-center">Hasil</th>
                            <th class="py-3 px-4 font-semibold text-sm text-center">Waktu Mulai</th>
                            <th class="py-3 px-4 font-semibold text-sm text-center">Waktu Selesai</th>
                            <th class="py-3 px-4 font-semibold text-sm text-right">Durasi (Menit)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($qcheck_history ?? [] as $qc)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="py-3 px-4 text-sm font-medium text-gray-900">{{ $qc->id_detailjob->id_itemproduksi->job_number ?? 'JOB-XXX' }}</td>
                            <td class="py-3 px-4 text-center text-sm text-gray-600">{{ $qc->get_jenis_qcheck_display ?? 'First Check' }}</td>
                            <td class="py-3 px-4 text-center text-sm font-semibold {{ $qc->hasil_qcheck == 'OK' ? 'text-green-600' : ($qc->hasil_qcheck == 'NG' ? 'text-red-600' : 'text-gray-600') }}">{{ $qc->hasil_qcheck ?? 'OK' }}</td>
                            <td class="py-3 px-4 text-center text-sm text-gray-600">{{ \Carbon\Carbon::parse($qc->start_time)->format('H:i:s') }}</td>
                            <td class="py-3 px-4 text-center text-sm text-gray-600">{{ $qc->finish_time ? \Carbon\Carbon::parse($qc->finish_time)->format('H:i:s') : '-' }}</td>
                            <td class="py-3 px-4 text-right text-sm font-medium text-gray-900">{{ number_format($qc->duration ?? 0, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="py-8 px-4 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="bx bx-check-shield text-4xl text-gray-300 mb-2"></i>
                                    <p>Belum ada history Q-Check untuk filter ini.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        <tr>
                            <th colspan="5" class="py-3 px-4 text-right font-bold text-gray-800">Total Durasi:</th>
                            <th class="py-3 px-4 text-right font-bold text-primary-red">{{ number_format($total_duration_history ?? 0, 2) }} Menit</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

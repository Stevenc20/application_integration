@extends('layouts.supervisor')

@section('title', 'Daftar Q-Check')
@section('header_title', 'Pencatatan Q-Check')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h5 class="text-lg font-bold text-gray-800">Pencatatan Q-Check untuk Job: <span class="text-primary-red">{{ $detail_job->id_itemproduksi->job_number ?? 'PART-XYZ' }}</span></h5>
        <a href="{{ route('supervisor.input_harian') ?? '#' }}" class="px-4 py-2 bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 text-sm font-medium rounded-md shadow-sm transition-colors flex items-center justify-center w-full md:w-auto">
            <i class="bx bx-spreadsheet mr-2"></i> Kembali ke Input Harian
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b border-gray-200 text-gray-700">
                    <th class="py-3 px-6 font-semibold text-sm">Jenis Aktivitas Q-Check</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Start</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Finish</th>
                    <th class="py-3 px-4 text-center font-semibold text-sm">Durasi (Menit)</th>
                    <th class="py-3 px-6 text-center font-semibold text-sm">Kontrol</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($qcheck_status ?? [] as $item)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-6 text-sm font-medium text-gray-900">{{ $item->type_display ?? 'Check Type' }}</td>
                    
                    @if(isset($item->record) && $item->record)
                        <td class="py-3 px-4 text-center text-sm text-gray-600 font-mono">{{ \Carbon\Carbon::parse($item->record->start_time)->format('H:i:s') }}</td>
                        <td class="py-3 px-4 text-center text-sm text-gray-600 font-mono">{{ $item->record->finish_time ? \Carbon\Carbon::parse($item->record->finish_time)->format('H:i:s') : '-' }}</td>
                        <td class="py-3 px-4 text-center text-sm font-medium text-gray-900">{{ number_format($item->record->duration ?? 0, 2) }}</td>
                        
                        <td class="py-3 px-6 text-center">
                            @if($item->record->finish_time)
                                <a href="#" class="px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-medium rounded transition-colors shadow-sm inline-block">Restart</a>
                            @else
                                <a href="#" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded transition-colors shadow-sm inline-block">Stop</a>
                            @endif
                        </td>
                    @else
                        <td class="py-3 px-4 text-center text-gray-400">-</td>
                        <td class="py-3 px-4 text-center text-gray-400">-</td>
                        <td class="py-3 px-4 text-center text-sm font-medium text-gray-500">0.00</td>
                        <td class="py-3 px-6 text-center">
                            <a href="#" class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition-colors shadow-sm inline-block">Start</a>
                        </td>
                    @endif
                </tr>
                @empty
                <!-- Template fallback -->
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-6 text-sm font-medium text-gray-900">First Check</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600 font-mono">08:00:00</td>
                    <td class="py-3 px-4 text-center text-sm text-gray-600 font-mono">-</td>
                    <td class="py-3 px-4 text-center text-sm font-medium text-gray-900">15.50</td>
                    <td class="py-3 px-6 text-center">
                        <a href="#" class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded transition-colors shadow-sm inline-block">Stop</a>
                    </td>
                </tr>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-6 text-sm font-medium text-gray-900">Middle Check</td>
                    <td class="py-3 px-4 text-center text-gray-400">-</td>
                    <td class="py-3 px-4 text-center text-gray-400">-</td>
                    <td class="py-3 px-4 text-center text-sm font-medium text-gray-500">0.00</td>
                    <td class="py-3 px-6 text-center">
                        <a href="#" class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition-colors shadow-sm inline-block">Start</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-gray-100 border-t-2 border-gray-200">
                <tr>
                    <th colspan="3" class="py-3 px-6 text-right font-bold text-gray-800">Total Durasi:</th>
                    <th class="py-3 px-4 text-center font-bold text-primary-red">{{ number_format($total_duration ?? 15.50, 2) }} menit</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

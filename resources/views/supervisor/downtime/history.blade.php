@extends('layouts.supervisor')

@section('title', 'Trouble History')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Trouble / Downtime History</h5>
    </div>
    
    <div class="p-5">
        <form class="flex items-center w-full md:w-auto mb-6">
            <input type="date" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m-d') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Filter Tanggal</button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line/Mesin</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kategori</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi/Alasan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Mulai</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu Selesai</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total (Mnt)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">Line A / M12</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Machine Error</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">Sensor Limit Switch Rusak</td>
                        <td class="px-4 py-3 text-sm text-gray-700">09:15</td>
                        <td class="px-4 py-3 text-sm text-gray-700">10:00</td>
                        <td class="px-4 py-3 text-center text-sm font-bold text-gray-900">45</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">Line B / M05</td>
                        <td class="px-4 py-3 text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Setup Dies</span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">Ganti Model PN-123 ke PN-456</td>
                        <td class="px-4 py-3 text-sm text-gray-700">13:30</td>
                        <td class="px-4 py-3 text-sm text-gray-700">14:00</td>
                        <td class="px-4 py-3 text-center text-sm font-bold text-gray-900">30</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

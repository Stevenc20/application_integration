@extends('layouts.supervisor')

@section('title', 'Downtime Monitoring')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
    <div class="flex justify-between items-center mb-6">
        <h4 class="text-xl font-bold text-gray-800">Downtime Monitoring Dashboard</h4>
        <div class="flex gap-2">
            <span class="bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded flex items-center">
                <span class="w-2 h-2 bg-red-500 rounded-full mr-1 animate-pulse"></span> Live
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-red-50 border border-red-200 rounded-lg p-5 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-sm font-medium text-red-600 uppercase tracking-wider">Active Downtime</p>
                <p class="text-3xl font-bold text-red-800 mt-1">3 Mesin</p>
            </div>
            <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xl">
                <i class="bx bx-error"></i>
            </div>
        </div>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-5 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-sm font-medium text-yellow-600 uppercase tracking-wider">Total Durasi Hari Ini</p>
                <p class="text-3xl font-bold text-yellow-800 mt-1">245 Mnt</p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 text-yellow-600 rounded-full flex items-center justify-center text-xl">
                <i class="bx bx-time-five"></i>
            </div>
        </div>

        <div class="bg-green-50 border border-green-200 rounded-lg p-5 flex items-center justify-between shadow-sm">
            <div>
                <p class="text-sm font-medium text-green-600 uppercase tracking-wider">Mesin Normal</p>
                <p class="text-3xl font-bold text-green-800 mt-1">12 Mesin</p>
            </div>
            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-xl">
                <i class="bx bx-check-circle"></i>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line/Mesin</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alasan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mulai</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Durasi (Mnt)</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Line A / M12</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Stop (Error)</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">Sensor Limit Switch Rusak</td>
                    <td class="px-4 py-3 text-sm text-gray-700">09:15</td>
                    <td class="px-4 py-3 text-center text-sm font-bold text-red-600">45</td>
                </tr>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">Line B / M05</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Setup Dies</span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-700">Ganti Model PN-123 ke PN-456</td>
                    <td class="px-4 py-3 text-sm text-gray-700">09:30</td>
                    <td class="px-4 py-3 text-center text-sm font-bold text-yellow-600">30</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

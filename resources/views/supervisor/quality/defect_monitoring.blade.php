@extends('layouts.supervisor')

@section('title', 'Defect Monitoring')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <h4 class="text-xl font-bold text-gray-800">Defect Monitoring Dashboard</h4>
        <form class="flex items-center w-full md:w-auto">
            <input type="date" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m-d') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Filter</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-red-50 border border-red-100 rounded-lg p-4 shadow-sm text-center">
            <p class="text-sm font-medium text-red-600 uppercase tracking-wider mb-1">Total Defect Hari Ini</p>
            <h3 class="text-3xl font-bold text-red-800">24</h3>
        </div>
        <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-4 shadow-sm text-center">
            <p class="text-sm font-medium text-yellow-600 uppercase tracking-wider mb-1">Menunggu Action</p>
            <h3 class="text-3xl font-bold text-yellow-800">5</h3>
        </div>
        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 shadow-sm text-center">
            <p class="text-sm font-medium text-blue-600 uppercase tracking-wider mb-1">Sedang Repair</p>
            <h3 class="text-3xl font-bold text-blue-800">12</h3>
        </div>
        <div class="bg-green-50 border border-green-100 rounded-lg p-4 shadow-sm text-center">
            <p class="text-sm font-medium text-green-600 uppercase tracking-wider mb-1">Selesai (OK)</p>
            <h3 class="text-3xl font-bold text-green-800">7</h3>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line/Mesin</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Number</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jenis Defect</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-sm text-gray-700">08:15</td>
                    <td class="px-4 py-3 text-sm text-gray-700">Line A / M12</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">PN-12345</td>
                    <td class="px-4 py-3 text-sm text-gray-700">Penyok / Baret</td>
                    <td class="px-4 py-3 text-sm text-center font-bold text-gray-900">5</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Waiting Action</span>
                    </td>
                </tr>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-sm text-gray-700">09:30</td>
                    <td class="px-4 py-3 text-sm text-gray-700">Line B / M08</td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">PN-67890</td>
                    <td class="px-4 py-3 text-sm text-gray-700">Dimensi Out</td>
                    <td class="px-4 py-3 text-sm text-center font-bold text-gray-900">2</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">In Repair</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection

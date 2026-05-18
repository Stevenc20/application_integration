@extends('layouts.supervisor')

@section('title', 'Daily Production Report')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Laporan Harian Produksi</h5>
    </div>
    
    <div class="p-5">
        <form class="flex items-center w-full md:w-auto mb-6">
            <input type="date" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m-d') }}">
            <button type="submit" class="ml-2 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">Update Report</button>
            <button type="button" class="ml-2 bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm">
                <i class="bx bx-export mr-1"></i> Export Excel
            </button>
        </form>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line/Shift</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Plan Qty</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actual Qty</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pencapaian (%)</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Total Reject</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Downtime (Menit)</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">Line A / Shift 1</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">5,000</td>
                        <td class="px-4 py-3 text-center text-sm font-bold text-gray-900">4,500</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">90.0%</span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-red-600 font-medium">45</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">120.0</td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">Line B / Shift 1</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">3,000</td>
                        <td class="px-4 py-3 text-center text-sm font-bold text-gray-900">3,100</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">103.3%</span>
                        </td>
                        <td class="px-4 py-3 text-center text-sm text-red-600 font-medium">10</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-700">15.0</td>
                    </tr>
                </tbody>
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-right text-sm font-bold text-gray-700">Total Produksi Hari Ini:</th>
                        <th class="px-4 py-3 text-center text-sm font-bold text-gray-900">8,000</th>
                        <th class="px-4 py-3 text-center text-sm font-bold text-primary-red">7,600</th>
                        <th class="px-4 py-3 text-center text-sm font-bold text-gray-900">95.0%</th>
                        <th class="px-4 py-3 text-center text-sm font-bold text-red-600">55</th>
                        <th class="px-4 py-3 text-center text-sm font-bold text-gray-900">135.0</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

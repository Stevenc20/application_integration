@extends('layouts.supervisor')

@section('title', 'Production Approval')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Production Approval Dashboard</h5>
    </div>
    
    <div class="p-5">
        <p class="text-sm text-gray-600 mb-4">Daftar laporan produksi harian yang menunggu persetujuan Supervisor.</p>
        
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line/Shift</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Output</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status Foreman</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Mock Data -->
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-700">{{ date('d-M-Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">Line A / Shift 1</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">4,500 pcs</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button class="bg-primary-red hover-bg-primary-red text-white py-1 px-3 rounded text-xs shadow-sm transition-colors">Review & Approve</button>
                        </td>
                    </tr>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-700">{{ date('d-M-Y', strtotime('-1 day')) }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">Line B / Shift 2</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">3,200 pcs</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button class="bg-gray-300 text-gray-500 py-1 px-3 rounded text-xs cursor-not-allowed" disabled>Menunggu Foreman</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@extends('layouts.supervisor')

@section('title', 'Quality Approval')

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg">
        <h5 class="text-xl font-bold text-gray-800">Quality Approval Dashboard</h5>
    </div>
    
    <div class="p-5">
        <p class="text-sm text-gray-600 mb-4">Daftar laporan Quality (Q-Check / Reject Analysis) yang membutuhkan persetujuan.</p>
        
        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Line/Mesin</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laporan</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status QC Leader</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Mock Data -->
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-sm text-gray-700">{{ date('d-M-Y') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700">Line A / M12</td>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">CAPA - Baret / Scratch tinggi</td>
                        <td class="px-4 py-3 text-sm text-center">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Approved</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button class="bg-primary-red hover-bg-primary-red text-white py-1 px-3 rounded text-xs shadow-sm transition-colors">Review & Approve</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

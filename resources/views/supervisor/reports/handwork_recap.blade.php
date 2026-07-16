@extends('layouts.supervisor')

@section('title', 'Handwork / Repair Recap')

@section('content')
<div class="p-4 md:p-6">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 gap-3">
        <div>
            <h1 class="text-2xl font-bold">Handwork / Repair Recap</h1>
            <p class="text-gray-500 text-sm">
                Job: {{ $plan->job_no ?? $plan->job_master }} — {{ $plan->plan_date->format('d M Y') }}
            </p>
        </div>
        <a href="{{ route('supervisor.reports.daily_production', ['date' => $plan->plan_date->toDateString()]) }}"
           class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 text-sm">
            ← Kembali ke LKH
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border mb-6">
        <div class="p-4 border-b">
            <h5 class="font-bold text-gray-800">Ringkasan Repair / Reject</h5>
        </div>
        <div class="p-4 grid grid-cols-2 gap-4">
            <div class="bg-green-50 p-4 rounded-lg text-center">
                <p class="text-sm text-gray-500">Total Repair</p>
                <h2 class="text-2xl font-bold text-amber-600">{{ number_format($totalRepair) }}</h2>
            </div>
            <div class="bg-red-50 p-4 rounded-lg text-center">
                <p class="text-sm text-gray-500">Total Reject</p>
                <h2 class="text-2xl font-bold text-red-600">{{ number_format($totalReject) }}</h2>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border">
        <div class="p-4 border-b">
            <h5 class="font-bold text-gray-800">Data Handwork</h5>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3 text-center">Tipe</th>
                        <th class="p-3 text-center">Defect</th>
                        <th class="p-3 text-center">Sketch No</th>
                        <th class="p-3 text-center">Kategori Repair</th>
                        <th class="p-3 text-center">Qty</th>
                        <th class="p-3 text-center">Area Problem</th>
                        <th class="p-3 text-center">Root Cause</th>
                        <th class="p-3 text-center">Countermeasure</th>
                        <th class="p-3 text-center">Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($repairRejectLogs as $log)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3 text-center">
                            <span class="px-2 py-1 rounded text-xs font-bold {{ $log->type === 'repair' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($log->type) }}
                            </span>
                        </td>
                        <td class="p-3">{{ $log->defect_name ?? '-' }}</td>
                        <td class="p-3 text-center">{{ $log->sketch_no ?? '-' }}</td>
                        <td class="p-3">{{ $log->repair_category ?? '-' }}</td>
                        <td class="p-3 text-center">{{ (int) $log->qty_a }}</td>
                        <td class="p-3">{{ $log->area_problem ?? '-' }}</td>
                        <td class="p-3">{{ $log->root_cause ?? '-' }}</td>
                        <td class="p-3">{{ $log->countermeasure ?? '-' }}</td>
                        <td class="p-3 text-center">{{ $log->creator?->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="p-4 text-center text-gray-400">Belum ada data handwork</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

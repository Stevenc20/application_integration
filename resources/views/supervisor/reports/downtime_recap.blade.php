@extends('layouts.supervisor')

@section('title', 'Downtime Recap')

@section('content')
<div class="p-4 md:p-6">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 gap-3">
        <div>
            <h1 class="text-2xl font-bold">Downtime Recap</h1>
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
            <h5 class="font-bold text-gray-800">Akumulasi Downtime</h5>
        </div>
        <div class="p-4">
            <table class="min-w-full text-sm border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3 text-left">Jenis Downtime</th>
                        <th class="p-3 text-center">Jumlah</th>
                        <th class="p-3 text-center">Total Menit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($classified as $jenis => $data)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3">{{ $jenis }}</td>
                        <td class="p-3 text-center">{{ $data['count'] }}</td>
                        <td class="p-3 text-center">{{ number_format($data['total_minutes'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="p-4 text-center text-gray-400">Belum ada data downtime</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-gray-50 font-bold">
                        <th class="p-3 text-left">Total</th>
                        <th class="p-3 text-center">{{ $totalCount }}</th>
                        <th class="p-3 text-center">{{ number_format($totalMinutes, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border">
        <div class="p-4 border-b">
            <h5 class="font-bold text-gray-800">Data Downtime</h5>
        </div>
        <div class="p-4 overflow-x-auto">
            <table class="min-w-full text-sm border">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3 text-center">Jenis</th>
                        <th class="p-3 text-center">Problem</th>
                        <th class="p-3 text-center">Penyebab</th>
                        <th class="p-3 text-center">Aksi</th>
                        <th class="p-3 text-center">PIC</th>
                        <th class="p-3 text-center">Start</th>
                        <th class="p-3 text-center">Finish</th>
                        <th class="p-3 text-center">Durasi (menit)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($downtimes as $dt)
                    @php
                        $dur = 0;
                        if (!empty($dt->duration_seconds)) {
                            $dur = round($dt->duration_seconds / 60, 2);
                        } elseif ($dt->start_time && $dt->finish_time) {
                            $dur = round(abs(\Carbon\Carbon::parse($dt->finish_time)->diffInMinutes(\Carbon\Carbon::parse($dt->start_time))), 2);
                        }
                    @endphp
                    <tr class="border-t hover:bg-gray-50">
                        <td class="p-3 text-center">{{ $dt->jenis_downtime ?? '-' }}</td>
                        <td class="p-3">{{ $dt->problem ?? '-' }}</td>
                        <td class="p-3">{{ $dt->penyebab ?? '-' }}</td>
                        <td class="p-3">{{ $dt->action ?? '-' }}</td>
                        <td class="p-3 text-center">{{ $dt->pic ?? '-' }}</td>
                        <td class="p-3 text-center">{{ $dt->start_time ? \Carbon\Carbon::parse($dt->start_time)->format('H:i') : '-' }}</td>
                        <td class="p-3 text-center">{{ $dt->finish_time ? \Carbon\Carbon::parse($dt->finish_time)->format('H:i') : '-' }}</td>
                        <td class="p-3 text-center">{{ number_format($dur, 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="p-4 text-center text-gray-400">Belum ada data downtime</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

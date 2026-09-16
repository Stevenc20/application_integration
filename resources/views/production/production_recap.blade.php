@extends('layouts.supervisor')

@section('content')
<div class="p-4 md:p-6">

<!-- HEADER -->
<div class="flex flex-col md:flex-row md:justify-between md:items-center mb-6 gap-3">

    <div>
        <h1 class="text-2xl font-bold">Production Recap</h1>
        <p class="text-gray-500 text-sm">{{ $dateLabel }}</p>
    </div>

    <div class="flex flex-wrap gap-2">

        <form method="GET" class="flex gap-2 flex-wrap">

            <!-- TYPE -->
            <select name="type" class="border px-2 py-2 rounded" onchange="this.form.submit()">
                <option value="daily" {{ ($type ?? '')=='daily' ? 'selected' : '' }}>Daily</option>
                <option value="weekly" {{ ($type ?? '')=='weekly' ? 'selected' : '' }}>Weekly</option>
                <option value="monthly" {{ ($type ?? '')=='monthly' ? 'selected' : '' }}>Monthly</option>
            </select>

            <!-- FILTER -->
            @if(($type ?? 'daily') == 'daily')
                <input type="date" name="date" class="border px-2 py-2 rounded"
                    value="{{ request('date', now()->toDateString()) }}"
                    onchange="this.form.submit()">
            @elseif($type == 'weekly')
                <input type="date" name="start" class="border px-2 py-2 rounded"
                    value="{{ request('start') }}">
                <input type="date" name="end" class="border px-2 py-2 rounded"
                    value="{{ request('end', now()->toDateString()) }}"
                    onchange="this.form.submit()">
            @else
                <input type="month" name="month" class="border px-2 py-2 rounded"
                    value="{{ request('month', now()->format('Y-m')) }}"
                    onchange="this.form.submit()">
            @endif

        </form>

        <!-- EXPORT BUTTON -->
        <a href="{{ route('production_recap.export', request()->all()) }}"
           class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
           Export Excel
        </a>

    </div>

</div>

<!-- SUMMARY -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">OK</p>
        <h2 class="text-xl font-bold text-green-600">
            {{ number_format($summary['ok']) }}
        </h2>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Repair</p>
        <h2 class="text-xl font-bold text-yellow-500">
            {{ number_format($summary['repair']) }}
        </h2>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Reject</p>
        <h2 class="text-xl font-bold text-red-600">
            {{ number_format($summary['reject']) }}
        </h2>
    </div>

    <div class="bg-white p-4 rounded-xl shadow">
        <p class="text-sm text-gray-500">Achievement</p>
        <h2 class="text-xl font-bold text-blue-600">
            {{ $summary['achievement'] }}%
        </h2>
    </div>

</div>

<!-- TABLE -->
<div class="bg-white rounded-xl shadow overflow-x-auto">

<table class="min-w-full text-sm text-center">

<thead class="bg-gray-100 text-gray-700">
<tr>
    <th class="p-3">Date</th>
    <th class="p-3">Job</th>
    <th class="p-3">Line</th>
    <th class="p-3">Shift</th>
    <th class="p-3">Target</th>
    <th class="p-3 text-green-600">OK</th>
    <th class="p-3 text-yellow-500">Repair</th>
    <th class="p-3 text-red-600">Reject</th>
    <th class="p-3">Total</th>
    <th class="p-3">Runtime</th>
    <th class="p-3">Downtime</th>
    <th class="p-3">Efisiensi</th>
</tr>
</thead>

<tbody>

@forelse($productions as $p)

<tr class="border-t hover:bg-gray-50 transition">

<td class="p-3">
    {{ \Carbon\Carbon::parse($p->work_date)->format('d/m') }}
</td>

<td class="p-3">
    {{ $p->jobMaster->job_name ?? $p->jobMaster->job_number ?? '-' }}
</td>

<td class="p-3">
    {{ $p->line ?? $p->jobMaster->line ?? '-' }}
</td>

<td class="p-3">
    {{ $p->shift ?? '-' }}
</td>

<td class="p-3">
    {{ number_format($p->target_qty) }}
</td>

<td class="p-3 text-green-600 font-medium">
    {{ number_format($p->actual_ok) }}
</td>

<td class="p-3 text-yellow-500 font-medium">
    {{ number_format($p->actual_repair) }}
</td>

<td class="p-3 text-red-600 font-medium">
    {{ number_format($p->actual_reject) }}
</td>

<td class="p-3 font-bold">
    {{ number_format($p->actual_ok + $p->actual_repair + $p->actual_reject) }}
</td>

<td class="p-3">
    {{ gmdate('H:i', $p->runtime_seconds) }}
</td>

<td class="p-3">
    {{ gmdate('H:i', $p->downtime_seconds) }}
</td>

<td class="p-3">
    {{ number_format($p->efficiency, 1) }}%
</td>

</tr>

@empty

<tr>
    <td colspan="12" class="p-4 text-gray-400">
        No data available
    </td>
</tr>

@endforelse

</tbody>

</table>

</div>

</div>
@endsection

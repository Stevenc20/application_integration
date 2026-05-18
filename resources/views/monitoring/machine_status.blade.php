@extends('layouts.supervisor')

@section('content')
<div class="p-6">

    <!-- HEADER -->
    <div class="flex justify-between items-center   mb-6">
        <div>
            <h1 class="text-2xl font-bold">Machine Status</h1>
            <p class="text-gray-500 text-sm">
                {{ now()->format('d F Y') }}
            </p>
        </div>
    </div>

    <!-- SUMMARY -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        <div class="bg-white p-4 rounded-xl shadow">
            <p class="text-sm text-gray-500">Running</p>
            <h2 class="text-2xl font-bold text-green-600">
                {{ $machines->where('status','running')->count() }}
            </h2>
        </div>

        <div class="bg-white p-4 rounded-xl shadow">
            <p class="text-sm text-gray-500">Downtime</p>
            <h2 class="text-2xl font-bold text-red-600">
                {{ $machines->where('status','downtime')->count() }}
            </h2>
        </div>

        <div class="bg-white p-4 rounded-xl shadow">
            <p class="text-sm text-gray-500">Maintenance</p>
            <h2 class="text-2xl font-bold text-yellow-500">
                {{ $machines->where('status','maintenance')->count() }}
            </h2>
        </div>

    </div>

    <!-- FILTER -->
    <div class="bg-white p-4 rounded-xl shadow mb-6 flex flex-wrap gap-3 items-center">

        <input type="date"
               class="border px-3 py-2 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500">

        <select class="border px-3 py-2 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-red-500">
            <option>All Line</option>
            <option>Line A</option>
            <option>Line B</option>
            <option>Line C</option>
        </select>

    </div>

    <!-- MACHINE GRID -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6">

        @foreach($machines as $machine)

        @php
            $color = match($machine->status) {
                'running' => 'bg-green-500',
                'downtime' => 'bg-red-500',
                'maintenance' => 'bg-yellow-400',
                default => 'bg-gray-400'
            };
        @endphp

        <div class="bg-white p-4 rounded-xl shadow border hover:shadow-md transition">

            <div class="flex justify-between items-center mb-2">
                <h3 class="font-semibold text-gray-800">
                    {{ $machine->name }}
                </h3>
                <span class="w-3 h-3 rounded-full {{ $color }}"></span>
            </div>

            <p class="text-sm text-gray-500 mb-1">
                Line: {{ $machine->line ?? '-' }}
            </p>

            <p class="text-sm">
                Status:
                <span class="font-medium capitalize">
                    {{ $machine->status }}
                </span>
            </p>

            <p class="text-xs text-gray-400 mt-2">
                Last Update: {{ now()->format('H:i') }}
            </p>

        </div>

        @endforeach

    </div>

    <!-- TABLE DETAIL -->
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <table class="w-full text-sm">

            <thead class="bg-blue-100 text-gray-600">
                <tr>
                    <th class="p-3 text-left">Machine</th>
                    <th class="p-3 text-left">Line</th>
                    <th class="p-3 text-left">Status</th>
                    <th class="p-3 text-left">Downtime Duration</th>
                    <th class="p-3 text-left">Last Update</th>
                </tr>
            </thead>

            <tbody>

                @foreach($machines as $machine)

                @php
                    $statusColor = match($machine->status) {
                        'running' => 'text-green-600',
                        'downtime' => 'text-red-600',
                        'maintenance' => 'text-yellow-500',
                        default => 'text-gray-500'
                    };
                @endphp

                <tr class="border-t hover:bg-gray-50">

                    <td class="p-3 font-medium">
                        {{ $machine->name }}
                    </td>

                    <td class="p-3">
                        {{ $machine->line ?? '-' }}
                    </td>

                    <td class="p-3 {{ $statusColor }}">
                        {{ ucfirst($machine->status) }}
                    </td>

                    <td class="p-3">
                        {{ $machine->downtime ?? '-' }}
                    </td>

                    <td class="p-3 text-gray-500">
                        {{ now()->format('H:i') }}
                    </td>

                </tr>

                @endforeach

            </tbody>

        </table>

    </div>

</div>
@endsection

@extends('layouts.layouts')

@vite(['resources/css/app.css', 'resources/js/app.js'])

@section('content')
<div class="p-4 sm:p-6 space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-2">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">Dashboard Operator</h1>
            <p class="text-gray-500 text-sm">
                {{ now()->format('d F Y') }}
            </p>
        </div>
    </div>

    {{-- SHIFT STATUS --}}
    <div class="bg-white p-4 rounded-xl shadow flex justify-between items-center">
        <div>
            <p class="text-sm text-gray-500">Shift Aktif</p>
            <p class="font-semibold text-green-600">Shift 1</p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500">Jam</p>
            <p id="liveClock" class="font-bold text-lg"></p>
        </div>
    </div>

    {{-- SUMMARY --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

        <div class="bg-green-100 p-4 rounded-xl text-center">
            <p class="text-sm text-gray-600">OK</p>
            <p class="text-xl font-bold text-green-600">{{ $totalOk ?? 0 }}</p>
        </div>

        <div class="bg-yellow-100 p-4 rounded-xl text-center">
            <p class="text-sm text-gray-600">Repair</p>
            <p class="text-xl font-bold text-yellow-600">{{ $totalRepair ?? 0 }}</p>
        </div>

        <div class="bg-red-100 p-4 rounded-xl text-center">
            <p class="text-sm text-gray-600">Reject</p>
            <p class="text-xl font-bold text-red-600">{{ $totalReject ?? 0 }}</p>
        </div>

        <div class="bg-blue-100 p-4 rounded-xl text-center">
            <p class="text-sm text-gray-600">Total Input</p>
            <p class="text-xl font-bold text-blue-600">{{ $totalProduction ?? 0 }}</p>
        </div>

    </div>

    {{-- QUICK ACTION --}}
    <div class="bg-white p-4 rounded-xl shadow">
        <h2 class="font-semibold mb-3">Production Input</h2>

        <a href="{{ route('production_entry') }}"
           class="block w-full text-center bg-red-600 text-white py-3 rounded-lg hover:bg-red-700">
            Input Produksi
        </a>
    </div>

    {{-- LINE STATUS --}}
    <div class="bg-white p-4 rounded-xl shadow">
        <h2 class="font-semibold mb-4">Line Status</h2>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

            <div class="bg-green-500 text-white p-4 rounded-lg text-center">
                Line A <br><span class="font-bold">Running</span>
            </div>

            <div class="bg-red-500 text-white p-4 rounded-lg text-center">
                Line B <br><span class="font-bold">Stop</span>
            </div>

            <div class="bg-yellow-500 text-white p-4 rounded-lg text-center">
                Line C <br><span class="font-bold">Setup</span>
            </div>

            <div class="bg-green-500 text-white p-4 rounded-lg text-center">
                Line D <br><span class="font-bold">Running</span>
            </div>

        </div>
    </div>

    {{-- RECENT INPUT --}}
    <div class="bg-white p-4 rounded-xl shadow">
        <h2 class="font-semibold mb-4">Recent Production</h2>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">

                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left">Order</th>
                        <th class="px-3 py-2 text-left">Line</th>
                        <th class="px-3 py-2 text-left">OK</th>
                        <th class="px-3 py-2 text-left">Reject</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($recentProductions as $item)
                    <tr class="border-b">
                        <td class="px-3 py-2">{{ $item->production_order_number }}</td>
                        <td class="px-3 py-2">{{ $item->line }}</td>
                        <td class="px-3 py-2 text-green-600">{{ $item->qty_ok }}</td>
                        <td class="px-3 py-2 text-red-600">{{ $item->qty_reject }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-gray-500">
                            No data
                        </td>
                    </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    </div>

</div>

@endsection
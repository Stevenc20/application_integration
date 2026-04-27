@extends('layouts.layouts')

@section('content')
<div class="p-3 sm:p-4 md:p-6 bg-gray-50 min-h-screen">

    <!-- HEADER -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-6">
        <div>
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800">Admin Dashboard</h1>
            <p class="text-gray-500 text-xs sm:text-sm">
                {{ now()->format('d F Y') }}
            </p>
        </div>
    </div>

    <!-- KPI GRID -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-6 gap-3 mb-6">

        <div class="bg-blue-50 border border-blue-100 shadow-sm rounded-xl p-4 text-center hover:shadow-md transition">
            <p class="text-xs text-blue-600">Total Users</p>
            <p class="text-lg font-bold text-blue-700">{{ $totalUsers }}</p>
        </div>

        <div class="bg-green-50 border border-green-100 shadow-sm rounded-xl p-4 text-center hover:shadow-md transition">
            <p class="text-xs text-green-600">Operators</p>
            <p class="text-lg font-bold text-green-700">{{ $totalOperators }}</p>
        </div>

        <div class="bg-purple-50 border border-purple-100 shadow-sm rounded-xl p-4 text-center hover:shadow-md transition">
            <p class="text-xs text-purple-600">Supervisors</p>
            <p class="text-lg font-bold text-purple-700">{{ $totalSupervisors }}</p>
        </div>

        <div class="bg-indigo-50 border border-indigo-100 shadow-sm rounded-xl p-4 text-center hover:shadow-md transition">
            <p class="text-xs text-indigo-600">Production</p>
            <p class="text-lg font-bold text-indigo-700">{{ $totalProduction }}</p>
        </div>

        <div class="bg-green-50 border border-green-100 shadow-sm rounded-xl p-4 text-center hover:shadow-md transition">
            <p class="text-xs text-green-600">Total OK</p>
            <p class="text-lg font-bold text-green-600">{{ $totalOk }}</p>
        </div>

        <div class="bg-red-50 border border-red-100 shadow-sm rounded-xl p-4 text-center hover:shadow-md transition">
            <p class="text-xs text-red-600">Reject</p>
            <p class="text-lg font-bold text-red-600">{{ $totalReject }}</p>
        </div>

    </div>

    <!-- KPI SECOND ROW -->
    <div class="grid grid-cols-2 md:grid-cols-2 gap-4 mb-6">

        <!-- REJECT RATE -->
        <div class="bg-red-50 border border-red-100 shadow-sm rounded-xl p-5 text-center">
            <p class="text-xs text-red-600 mb-1">Reject Rate</p>
            <p class="text-2xl font-bold text-red-700">
                {{ number_format($rejectRate,2) }}%
            </p>
            <div class="mt-2 h-2 bg-red-100 rounded">
                <div class="h-2 bg-red-500 rounded" style="width: {{ $rejectRate }}%"></div>
            </div>
        </div>

        <!-- OK RATE (GANTI DARI YIELD) -->
        <div class="bg-green-50 border border-green-100 shadow-sm rounded-xl p-5 text-center">
            <p class="text-xs text-green-600 mb-1">OK Rate</p>
            <p class="text-2xl font-bold text-green-700">
                {{ number_format($yield,2) }}%
            </p>
            <div class="mt-2 h-2 bg-green-100 rounded">
                <div class="h-2 bg-green-500 rounded" style="width: {{ $yield }}%"></div>
            </div>
        </div>

    </div>

    <!-- RECENT PRODUCTION -->
    <div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5">

        <div class="flex justify-between items-center mb-4">
            <h2 class="font-semibold text-sm md:text-base text-gray-700">
                Recent Production
            </h2>
        </div>

       <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">

    <table class="min-w-full text-xs md:text-sm">

        <!-- HEADER -->
        <thead class="bg-gray-100 sticky top-0 z-10">
            <tr class="text-left">
                <th class="px-4 py-3 font-semibold text-gray-600">Order</th>
                <th class="px-4 py-3 font-semibold text-gray-600">Line</th>
                <th class="px-4 py-3 font-semibold text-gray-600 text-center">OK</th>
                <th class="px-4 py-3 font-semibold text-gray-600 text-center">Reject</th>
            </tr>
        </thead>

        <!-- BODY -->
        <tbody class="divide-y divide-gray-100">

            @forelse($recentProduction as $p)
            <tr class="hover:bg-gray-50 transition">

                <!-- ORDER -->
                <td class="px-4 py-3 font-medium text-gray-700">
                    {{ $p->production_order_number }}
                </td>

                <!-- LINE -->
                <td class="px-4 py-3 text-gray-600">
                    {{ $p->line }}
                </td>

                <!-- OK -->
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                        {{ $p->qty_ok }}
                    </span>
                </td>

                <!-- REJECT -->
                <td class="px-4 py-3 text-center">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                        {{ $p->qty_reject }}
                    </span>
                </td>

            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-center py-6 text-gray-400">
                    No production data available
                </td>
            </tr>
            @endforelse

        </tbody>
    </table>

</div>

<!-- PAGINATION -->
<div class="mt-4 flex justify-end">
    {{ $recentProduction->links() }}
</div>


</div>
@endsection
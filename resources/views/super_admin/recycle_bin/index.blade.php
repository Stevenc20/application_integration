@extends('layouts.super_admin')

@section('title', 'Recycle Bin - Data Produksi')

@section('content')
<div class="p-3 sm:p-4 md:p-6">

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center gap-3 shadow-sm">
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-center gap-3 shadow-sm">
        <span class="text-sm font-medium">{{ session('error') }}</span>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Recycle Bin</h1>
            <p class="text-sm text-gray-500">Data produksi yang dihapus sementara</p>
        </div>
        <div class="flex gap-2">
            @if($stats['total_active'] > 0)
            <form action="{{ route('super-admin.recycle-bin.restore-all') }}" method="POST" onsubmit="return confirm('Restore semua data di recycle bin?')">
                @csrf
                <button class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-xl transition-colors">
                    <i class="bx bx-refresh mr-1"></i> Restore All
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total di Trash</p>
            <p class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($stats['total_active']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Aktif (Bisa Direstore)</p>
            <p class="text-3xl font-bold text-emerald-600 mt-1">{{ number_format($stats['active_count']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Expired (Akan Dihapus)</p>
            <p class="text-3xl font-bold text-red-600 mt-1">{{ number_format($stats['expired_count']) }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Expired dalam 7 Hari</p>
            <p class="text-3xl font-bold text-amber-600 mt-1">{{ number_format($stats['expiring_soon']) }}</p>
        </div>
    </div>

    {{-- CHARTS ROW --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-gray-700 mb-3">Data per Tabel</h3>
            <div class="h-64"><canvas id="perTableChart"></canvas></div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-gray-700 mb-3">Status</h3>
            <div class="h-64"><canvas id="statusChart"></canvas></div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <h3 class="text-sm font-bold text-gray-700 mb-3">Trend per Bulan</h3>
            <div class="h-64"><canvas id="trendChart"></canvas></div>
        </div>
    </div>

    {{-- TABLE --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-800">Daftar Data di Trash</h3>
            <span class="text-xs text-gray-400">{{ $trash->total() }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tabel Asal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Data</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Di-trash</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Expired</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($trash as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-gray-700">{{ $stats['table_labels'][$item->original_table] ?? $item->original_table }}</td>
                        <td class="px-4 py-3 text-gray-500">{{ $item->original_id }}</td>
                        <td class="px-4 py-3 max-w-xs">
                            <button onclick="toggleDetail({{ $item->id }})" class="text-primary-red hover:text-red-700 text-xs font-medium">Lihat</button>
                            <pre id="detail-{{ $item->id }}" class="hidden text-xs text-gray-500 bg-gray-50 p-2 rounded mt-1 overflow-x-auto max-h-32">@json($item->data, JSON_PRETTY_PRINT)</pre>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $item->trashed_at ? $item->trashed_at->diffForHumans() : '-' }}</td>
                        <td class="px-4 py-3">
                            @if($item->expires_at && $item->expires_at->isPast())
                                <span class="text-xs font-semibold text-red-600 bg-red-50 px-2 py-1 rounded-full">Expired</span>
                            @elseif($item->expires_at && $item->expires_at->diffInDays(now()) <= 7)
                                <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-1 rounded-full">{{ $item->expires_at->diffForHumans() }}</span>
                            @elseif($item->expires_at)
                                <span class="text-xs text-gray-500">{{ $item->expires_at->diffForHumans() }}</span>
                            @else
                                <span class="text-xs text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('super-admin.recycle-bin.restore', $item->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 px-3 py-1.5 rounded-lg transition-colors">
                                        Restore
                                    </button>
                                </form>
                                <form action="{{ route('super-admin.recycle-bin.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Hapus permanen data ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-400">Tidak ada data di recycle bin</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($trash->hasPages())
        <div class="px-5 py-4 border-t border-gray-100">
            {{ $trash->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const stats = @json($stats);

    // Horizontal bar: data per table
    const perTableCtx = document.getElementById('perTableChart')?.getContext('2d');
    if (perTableCtx && stats.chart_labels.length > 0) {
        new Chart(perTableCtx, {
            type: 'bar',
            data: {
                labels: stats.chart_labels,
                datasets: [{
                    data: stats.chart_values,
                    backgroundColor: '#C0392B',
                    borderRadius: 4,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }

    // Pie: active vs expired
    const statusCtx = document.getElementById('statusChart')?.getContext('2d');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Aktif (Bisa Direstore)', 'Expired (Akan Dihapus)'],
                datasets: [{
                    data: [stats.active_count, stats.expired_count],
                    backgroundColor: ['#059669', '#DC2626'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
                }
            }
        });
    }

    // Line: monthly trend
    const trendCtx = document.getElementById('trendChart')?.getContext('2d');
    if (trendCtx && stats.monthly_labels.length > 0) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: stats.monthly_labels,
                datasets: [{
                    label: 'Data di-trash',
                    data: stats.monthly_values,
                    borderColor: '#C0392B',
                    backgroundColor: 'rgba(192, 57, 43, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#C0392B',
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    }
});

function toggleDetail(id) {
    const el = document.getElementById('detail-' + id);
    if (el) el.classList.toggle('hidden');
}
</script>
@endsection

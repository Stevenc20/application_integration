@extends('layouts.supervisor')

@section('title', 'Analisis Pareto Downtime per Tipe')

@section('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="p-5 border-b border-gray-200 bg-gray-50 rounded-t-lg flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h5 class="text-xl font-bold text-gray-800">Analisis Pareto Downtime per Tipe</h5>
            <p class="text-xs text-gray-500 mt-1">Prinsip Pareto 80/20 untuk mengidentifikasi kategori downtime dominan (vital few) berdasarkan durasi operasional.</p>
        </div>
        <form class="flex items-center gap-2" onsubmit="event.preventDefault(); loadData();">
            <label for="monthFilter" class="text-xs font-semibold text-gray-600 whitespace-nowrap">Periode:</label>
            <input type="month" id="monthFilter" class="form-input rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 text-sm" value="{{ date('Y-m') }}">
            <button type="submit" class="bg-gray-700 hover:bg-gray-800 text-white font-medium py-2 px-4 rounded-md transition-colors shadow-sm text-sm whitespace-nowrap">Update</button>
        </form>
    </div>
    
    <div class="p-5">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm text-center">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Downtime</p>
                <p class="text-2xl font-bold text-gray-800 mt-1" id="totalMinutes">0</p>
                <p class="text-xs text-gray-400">menit</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm text-center">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Total Kejadian</p>
                <p class="text-2xl font-bold text-gray-800 mt-1" id="totalEvents">0</p>
                <p class="text-xs text-gray-400">kali</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm text-center">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Kategori Dominan</p>
                <p class="text-2xl font-bold text-red-600 mt-1" id="topCategory">-</p>
                <p class="text-xs text-gray-400" id="topCategoryPct">&nbsp;</p>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 bg-white shadow-sm text-center">
                <p class="text-xs font-medium uppercase tracking-wider text-gray-500">Fokus Vital Few (≤80%)</p>
                <p class="text-base font-bold text-slate-800 mt-2 truncate" id="topCategoriesText" title="-">-</p>
                <p class="text-xs text-gray-400" id="topCategoriesCount">0 kategori</p>
            </div>
        </div>

        <!-- Pareto Chart & Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Pareto Combo Chart (Bar + Line) -->
            <div class="lg:col-span-2 border border-gray-200 rounded-lg p-5 bg-white shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h6 class="font-bold text-gray-800 text-sm">Pareto Chart (Durasi & Kumulatif %)</h6>
                        <p class="text-xs text-gray-400 mt-0.5">Batang menunjukkan total menit, garis hitam menunjukkan % kumulatif.</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs">
                        <span class="inline-flex items-center gap-1 text-slate-600">
                            <span class="w-3 h-3 rounded-sm bg-red-600 inline-block"></span> ≥20%
                        </span>
                        <span class="inline-flex items-center gap-1 text-slate-600">
                            <span class="w-3 h-3 rounded-sm bg-amber-500 inline-block"></span> ≥10%
                        </span>
                        <span class="inline-flex items-center gap-1 text-slate-600">
                            <span class="w-3 h-3 rounded-sm bg-blue-600 inline-block"></span> &lt;10%
                        </span>
                    </div>
                </div>
                <div class="relative flex-1" style="min-height: 280px;" id="chartWrapper">
                    <canvas id="downtimeTypeChart"></canvas>
                    <div id="noDataMessage" class="absolute inset-0 flex items-center justify-center text-gray-400 text-sm font-medium hidden">
                        Tidak ada data downtime untuk periode ini
                    </div>
                </div>
            </div>
            
            <!-- Pareto Breakdown Progress Bars -->
            <div class="border border-gray-200 rounded-lg p-5 bg-white shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h6 class="font-bold text-gray-800 text-sm">Distribusi Kontribusi</h6>
                    <span class="text-xs font-semibold px-2 py-0.5 bg-slate-100 text-slate-600 rounded" id="categoryCountBadge">0 Kategori</span>
                </div>
                <div class="space-y-4 overflow-y-auto max-h-[300px] pr-1" id="percentageBars"></div>
            </div>
        </div>

        <!-- Detail Table -->
        <div class="border border-gray-200 rounded-lg overflow-hidden bg-white shadow-sm">
            <div class="p-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                <h6 class="font-bold text-gray-800 text-sm">Tabel Rincian Analisis Pareto</h6>
                <span class="text-xs text-gray-500">Diurutkan berdasarkan total durasi tertinggi</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 text-xs uppercase tracking-wider border-b border-gray-200">
                            <th class="text-center py-2.5 px-3 w-12">#</th>
                            <th class="text-left py-2.5 px-3">Kategori Downtime</th>
                            <th class="text-right py-2.5 px-3">Total (Menit)</th>
                            <th class="text-right py-2.5 px-3">Kontribusi (%)</th>
                            <th class="text-right py-2.5 px-3">Kumulatif (%)</th>
                            <th class="text-right py-2.5 px-3">Jumlah Kejadian</th>
                            <th class="text-center py-2.5 px-3">Klasifikasi Pareto</th>
                        </tr>
                    </thead>
                    <tbody id="detailTableBody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let chartInstance = null;

// Chart.js plugin to draw Pareto 80% cutoff line
const pareto80LinePlugin = {
    id: 'pareto80Line',
    afterDraw(chart) {
        const y1 = chart.scales.y1;
        if (!y1) return;
        const yCoor = y1.getPixelForValue(80);
        const ctx = chart.ctx;
        ctx.save();
        ctx.strokeStyle = '#ef4444';
        ctx.lineWidth = 1.5;
        ctx.setLineDash([5, 4]);
        ctx.beginPath();
        ctx.moveTo(chart.chartArea.left, yCoor);
        ctx.lineTo(chart.chartArea.right, yCoor);
        ctx.stroke();

        ctx.fillStyle = '#ef4444';
        ctx.font = 'bold 11px Inter, system-ui, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText('Batas 80% Pareto', chart.chartArea.right - 6, yCoor - 5);
        ctx.restore();
    }
};

async function loadData() {
    const month = document.getElementById('monthFilter').value;
    const params = new URLSearchParams({ month });

    const totalMinutesEl = document.getElementById('totalMinutes');
    const totalEventsEl = document.getElementById('totalEvents');
    const topCategoryEl = document.getElementById('topCategory');
    const topCategoryPctEl = document.getElementById('topCategoryPct');
    const topCategoriesTextEl = document.getElementById('topCategoriesText');
    const topCategoriesCountEl = document.getElementById('topCategoriesCount');
    const categoryCountBadgeEl = document.getElementById('categoryCountBadge');

    const canvas = document.getElementById('downtimeTypeChart');
    const noDataMsg = document.getElementById('noDataMessage');
    const barsContainer = document.getElementById('percentageBars');
    const tbody = document.getElementById('detailTableBody');

    // Loading state
    barsContainer.innerHTML = '<div class="flex items-center justify-center h-32 text-gray-400 text-sm font-medium animate-pulse">Memuat data...</div>';
    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-6 text-gray-400 text-sm font-medium animate-pulse">Memuat data analisis Pareto...</td></tr>';

    try {
        const res = await fetch("{{ route('supervisor.api.grafik.downtime_type') }}?" + params);
        if (!res.ok) {
            const errText = await res.text().catch(() => '');
            throw new Error(`HTTP ${res.status}: ${res.statusText || 'Server Error'}`);
        }
        const data = await res.json();

        const items = data.items || [];
        const hasData = items.length > 0;

        totalMinutesEl.textContent = hasData ? (Number(data.totalMinutes) || 0).toLocaleString('id-ID', { maximumFractionDigits: 1 }) : '0';
        totalEventsEl.textContent = hasData ? (data.totalEvents || 0).toLocaleString('id-ID') : '0';
        topCategoryEl.textContent = hasData ? data.topCategory : '-';
        topCategoryPctEl.textContent = hasData && data.percentages.length > 0 ? `${data.percentages[0]}% dari total durasi` : '&nbsp;';

        const topCats = data.top_categories || [];
        topCategoriesTextEl.textContent = topCats.length > 0 ? topCats.join(', ') : '-';
        topCategoriesTextEl.title = topCats.join(', ');
        topCategoriesCountEl.textContent = `${topCats.length} kategori kunci`;
        categoryCountBadgeEl.textContent = `${items.length} Kategori`;

        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }

        if (!hasData) {
            canvas.classList.add('hidden');
            noDataMsg.classList.remove('hidden');
            barsContainer.innerHTML = '<div class="flex items-center justify-center h-48 text-gray-400 text-sm font-medium">Tidak ada data downtime untuk periode ini</div>';
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-6 text-gray-400 text-sm">Tidak ada data downtime untuk periode ini</td></tr>';
            return;
        }

        canvas.classList.remove('hidden');
        noDataMsg.classList.add('hidden');

        // Pareto Chart (Bar + Line Combo)
        const ctx = canvas.getContext('2d');
        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Durasi (Menit)',
                        data: data.data,
                        backgroundColor: data.percentages.map(p => p >= 20 ? '#dc2626' : (p >= 10 ? '#f59e0b' : '#2563eb')),
                        borderRadius: 4,
                        order: 2,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Kumulatif (%)',
                        data: data.cumulative,
                        type: 'line',
                        borderColor: '#1e293b',
                        backgroundColor: '#1e293b',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointBackgroundColor: '#1e293b',
                        tension: 0.25,
                        order: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.dataset.yAxisID === 'y1') {
                                    return `Kumulatif: ${ctx.parsed.y}%`;
                                }
                                const idx = ctx.dataIndex;
                                const pct = data.percentages[idx] || 0;
                                return `Durasi: ${ctx.parsed.y} menit (${pct}%)`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11, weight: '500' } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        title: {
                            display: true,
                            text: 'Durasi (Menit)',
                            font: { size: 11, weight: 'bold' }
                        }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        max: 100,
                        grid: { display: false },
                        ticks: {
                            callback: val => val + '%'
                        },
                        title: {
                            display: true,
                            text: 'Kumulatif (%)',
                            font: { size: 11, weight: 'bold' }
                        }
                    }
                }
            },
            plugins: [pareto80LinePlugin]
        });

        // Breakdown Progress Bars
        barsContainer.innerHTML = '';
        items.forEach((item, idx) => {
            const barColor = item.percentage >= 20 ? 'bg-red-600' : (item.percentage >= 10 ? 'bg-amber-500' : 'bg-blue-600');
            const badgeClass = item.in_pareto_80 ? 'bg-red-100 text-red-700 font-semibold' : 'bg-slate-100 text-slate-600';
            const badgeLabel = item.in_pareto_80 ? 'Vital Few' : 'Useful Many';

            barsContainer.innerHTML += `
                <div class="border-b border-gray-100 pb-3 last:border-b-0 last:pb-0">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <div class="flex items-center gap-1.5">
                            <span class="font-bold text-gray-500">#${idx + 1}</span>
                            <span class="font-semibold text-gray-800">${item.category}</span>
                            <span class="text-[10px] px-1.5 py-0.2 rounded ${badgeClass}">${badgeLabel}</span>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-gray-900">${item.percentage}%</span>
                            <span class="text-gray-400 text-[10px]"> (Kum: ${item.cumulative}%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full ${barColor}" style="width: ${Math.min(item.percentage, 100)}%"></div>
                    </div>
                    <div class="flex justify-between items-center text-[11px] text-gray-500 mt-1">
                        <span>${item.minutes.toLocaleString('id-ID')} menit</span>
                        <span>${item.count.toLocaleString('id-ID')} kejadian</span>
                    </div>
                </div>
            `;
        });

        // Detail Table Rows
        tbody.innerHTML = '';
        items.forEach((item, idx) => {
            const isVital = item.in_pareto_80;
            const badgeClass = isVital
                ? 'bg-red-50 text-red-700 border border-red-200 font-semibold'
                : 'bg-slate-50 text-slate-600 border border-slate-200';
            const badgeLabel = isVital ? 'Vital Few (Prioritas Utama)' : 'Useful Many';

            tbody.innerHTML += `
                <tr class="hover:bg-gray-50 transition-colors ${isVital ? 'bg-red-50/20' : ''}">
                    <td class="py-2.5 px-3 text-center font-bold text-gray-400 text-xs">${idx + 1}</td>
                    <td class="py-2.5 px-3 font-semibold text-gray-800">${item.category}</td>
                    <td class="py-2.5 px-3 text-right font-bold text-gray-900">${item.minutes.toLocaleString('id-ID')}</td>
                    <td class="py-2.5 px-3 text-right font-medium text-gray-700">${item.percentage}%</td>
                    <td class="py-2.5 px-3 text-right font-semibold ${item.cumulative <= 80 ? 'text-red-600' : 'text-slate-600'}">${item.cumulative}%</td>
                    <td class="py-2.5 px-3 text-right text-gray-700">${item.count.toLocaleString('id-ID')}</td>
                    <td class="py-2.5 px-3 text-center">
                        <span class="inline-block px-2.5 py-1 text-xs rounded-full ${badgeClass}">
                            ${badgeLabel}
                        </span>
                    </td>
                </tr>
            `;
        });
    } catch (err) {
        console.error('Failed to load downtime Pareto data:', err);
        if (chartInstance) {
            chartInstance.destroy();
            chartInstance = null;
        }
        canvas.classList.add('hidden');
        noDataMsg.classList.add('hidden');

        totalMinutesEl.textContent = '-';
        totalEventsEl.textContent = '-';
        topCategoryEl.textContent = 'Error';
        topCategoryPctEl.innerHTML = '<span class="text-red-500 font-semibold">Gagal memuat API</span>';
        topCategoriesTextEl.textContent = 'Gagal memuat';
        topCategoriesCountEl.textContent = 'Periksa server';
        categoryCountBadgeEl.textContent = 'Error';

        barsContainer.innerHTML = '<div class="p-4 text-center text-red-500 text-sm font-medium">Gagal memuat data downtime dari server.<br><span class="text-xs text-gray-400 mt-1 block">Silakan coba lagi atau periksa koneksi backend.</span></div>';
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-6 text-red-500 text-sm font-medium">Terjadi kesalahan saat memuat data dari server. Silakan refresh halaman.</td></tr>';
    }
}

document.addEventListener('DOMContentLoaded', loadData);
</script>
@endsection

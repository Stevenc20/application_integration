<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col">
    <div class="px-5 py-3 border-b border-gray-100 font-bold text-gray-700 flex items-center gap-2 text-sm">
        <span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span>Pencapaian GSPH
        <button onclick="window.gsphFitChart()" class="ml-auto text-xs text-gray-400 hover:text-blue-500 transition-colors px-2 py-1 rounded-lg hover:bg-blue-50" title="Sesuaikan skala ke data">↺ Fit</button>
        <button onclick="window.gsphResetChart()" class="text-xs text-gray-400 hover:text-red-500 transition-colors px-2 py-1 rounded-lg hover:bg-red-50" title="Reset zoom">↺ Reset</button>
    </div>
    <div class="p-4 flex-1 min-h-[350px] 2xl:min-h-[500px]"><canvas id="gsphChart"></canvas></div>
</div>

<script>
(function() {
    if (window.__gsphInited) return;
    window.__gsphInited = true;

    function loadChartJs(cb) {
        if (typeof Chart !== 'undefined') { cb(); return; }
        var s1 = document.createElement('script');
        s1.src = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';
        s1.onload = function() {
            var s2 = document.createElement('script');
            s2.src = 'https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.2.0/dist/chartjs-plugin-zoom.min.js';
            s2.onload = function() {
                var s3 = document.createElement('script');
                s3.src = 'https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js';
                s3.onload = cb;
                document.head.appendChild(s3);
            };
            document.head.appendChild(s2);
        };
        document.head.appendChild(s1);
    }

    loadChartJs(function() {
        Chart.register(ChartZoom);
        if (typeof ChartDataLabels !== 'undefined') {
            Chart.register(ChartDataLabels);
        }
        fetchGsphData();
    });

    var gsphChart = null;
    var gsphMeta = null;

    async function fetchGsphData() {
        try {
            var res = await fetch('/api/gsph');
            var data = await res.json();
            renderGsphChart(data.labels, data.plan, data.actual);
        } catch(e) {
            console.error('GSPH data fetch error:', e);
        }
    }

    function renderGsphChart(labels, plan, actual) {
        var canvas = document.getElementById('gsphChart');
        if (!canvas) return;
        if (gsphChart) { gsphChart.destroy(); }

        var posActual = actual.filter(function(v) { return v > 0; });
        var minVal = posActual.length > 0 ? Math.min.apply(null, posActual) : 0;
        var maxPlan = Math.max.apply(null, plan);
        var isSmall = minVal > 0 && maxPlan > 0 && (maxPlan / minVal) > 5;

        gsphMeta = { labels: labels, plan: plan, actual: actual, minVal: minVal, maxPlan: maxPlan, isSmall: isSmall };

        gsphChart = new Chart(canvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Plan', data: plan, backgroundColor: '#e5e7eb', borderRadius: 4, minBarLength: 10 },
                    { label: 'Actual', data: actual, backgroundColor: '#3b82f6', borderRadius: 4, minBarLength: 10 }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#6b7280', font: { size: 13, family: "'Inter', sans-serif" } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                if (ctx.datasetIndex === 0) return 'Plan: ' + ctx.parsed.x.toLocaleString();
                                var planVal = ctx.chart.data.datasets[0].data[ctx.dataIndex];
                                var pct = planVal > 0 ? ((ctx.parsed.x / planVal) * 100).toFixed(2) + '%' : '-';
                                return 'Actual: ' + ctx.parsed.x.toLocaleString() + ' (' + pct + ')';
                            }
                        }
                    },
                    datalabels: {
                        display: function(ctx) { return ctx.datasetIndex === 1; },
                        anchor: 'end',
                        align: 'end',
                        color: '#374151',
                        font: { weight: 'bold', size: 11 },
                        formatter: function(val) {
                            if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                            if (val >= 1000) return (val / 1000).toFixed(1) + 'K';
                            return val.toLocaleString();
                        }
                    },
                    zoom: {
                        pan: { enabled: true, mode: 'x', modifierKey: 'shift' },
                        zoom: { wheel: { enabled: false }, pinch: { enabled: true }, drag: { enabled: true, backgroundColor: 'rgba(59,130,246,0.08)', borderColor: '#3b82f6', borderWidth: 1 } },
                        limits: { x: { minRange: 0.5 } }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: !isSmall,
                        min: isSmall ? Math.max(0, minVal * 0.8) : undefined,
                        max: isSmall ? maxPlan * 1.1 : undefined,
                        ticks: { color: '#6b7280', font: { size: 13 } },
                        grid: { color: '#f3f4f6' },
                        title: { display: true, text: 'GSPH', color: '#9ca3af', font: { size: 13, weight: 'bold' } }
                    },
                    y: {
                        ticks: { color: '#6b7280', font: { size: 13 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    window.gsphFitChart = function() {
        if (!gsphChart || !gsphMeta) return;
        var m = gsphMeta;
        var pa = m.actual.filter(function(v) { return v > 0; });
        if (pa.length === 0) return;
        var mv = Math.min.apply(null, pa);
        var mx = Math.max.apply(null, m.plan);
        if (mx <= 0) return;
        gsphChart.options.scales.x.beginAtZero = false;
        gsphChart.options.scales.x.min = Math.max(0, mv * 0.8);
        gsphChart.options.scales.x.max = mx * 1.1;
        gsphChart.resetZoom();
        gsphChart.update();
    };

    window.gsphResetChart = function() {
        if (!gsphChart) return;
        gsphChart.options.scales.x.beginAtZero = true;
        gsphChart.options.scales.x.min = undefined;
        gsphChart.options.scales.x.max = undefined;
        gsphChart.resetZoom();
        gsphChart.update();
    };
})();
</script>

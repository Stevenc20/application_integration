@extends('layouts.supervisor')

@section('content')

<div class="p-4 md:p-6">

{{-- HEADER --}}
<div class="flex justify-between items-center mb-6">

<div>
<h1 class="text-xl md:text-2xl font-bold">Production Dashboard</h1>
<p class="text-gray-500 text-xs md:text-sm">{{ now()->format('d F Y') }}</p>
</div>

<div class="text-right">
<p class="text-xs text-gray-500">Live Time</p>
<p id="liveClock" class="text-xl md:text-2xl font-bold text-blue-600"></p>
</div>

</div>

{{-- PRODUCTION ALERT --}}
<div class="w-full bg-white shadow rounded-xl p-4 md:p-6 mb-6">

<h2 class="font-semibold text-sm md:text-lg mb-4">Production Alert</h2>

<div class="space-y-2 text-xs md:text-sm">

@if($achievementPercent < 80)
<div class="bg-yellow-400 text-black px-3 py-2 rounded flex items-center gap-2">
⚠ Production behind target
</div>
@endif

@if($rejectRate > 5)
<div class="bg-yellow-400 text-black px-3 py-2 rounded flex items-center gap-2">
⚠ High reject rate
</div>
@endif

@if($activeDowntime > 0)
<div class="bg-yellow-400 text-black px-3 py-2 rounded flex items-center gap-2">
⚠ Machine downtime detected
</div>
@endif

@if($openAbnormality > 0)
<div class="bg-yellow-400 text-black px-3 py-2 rounded flex items-center gap-2">
⚠ Open abnormality report
</div>
@endif

@if(
$achievementPercent >= 80 &&
$rejectRate <= 5 &&
$activeDowntime == 0 &&
$openAbnormality == 0
)
<div class="bg-green-500 text-white px-3 py-2 rounded flex items-center gap-2">
✔ Production running normally
</div>
@endif

</div>

</div>

{{-- ================= FILTER ================= --}}
<div class="bg-white p-6 rounded-xl shadow mb-6">

<form method="GET" action="{{ route('dashboard') }}"
class="grid grid-cols-1 md:grid-cols-6 gap-4">

<div>
<label class="text-sm font-medium">Date From</label>
<input type="date"
name="date_from"
value="{{ request('date_from', now()->toDateString()) }}"
class="w-full border rounded-lg px-3 py-2 text-sm">
</div>

<div>
<label class="text-sm font-medium">Date To</label>
<input type="date"
name="date_to"
value="{{ request('date_to', now()->toDateString()) }}"
class="w-full border rounded-lg px-3 py-2 text-sm">
</div>

<div>
<label class="text-sm font-medium">Process</label>

<select name="process_type"
class="w-full border rounded-lg px-3 py-2 text-sm">

<option value="">All</option>

<option value="Stamping"
{{ request('process_type')=='Stamping'?'selected':'' }}>
Stamping
</option>

<option value="Sub Assy"
{{ request('process_type')=='Sub Assy'?'selected':'' }}>
Sub Assy
</option>

<option value="Shearing"
{{ request('process_type')=='Shearing'?'selected':'' }}>
Shearing
</option>

<option value="Metal Finish"
{{ request('process_type')=='Metal Finish'?'selected':'' }}>
Metal Finish
</option>

</select>
</div>

<div>
<label class="text-sm font-medium">Shift</label>

<select name="shift"
class="w-full border rounded-lg px-3 py-2 text-sm">

<option value="">All</option>

<option value="Shift 1"
{{ request('shift')=='Shift 1'?'selected':'' }}>
Shift 1
</option>

<option value="Shift 2"
{{ request('shift')=='Shift 2'?'selected':'' }}>
Shift 2
</option>

<option value="Shift 3"
{{ request('shift')=='Shift 3'?'selected':'' }}>
Shift 3
</option>

</select>
</div>

<div>
<label class="text-sm font-medium">Order</label>

<input type="text"
name="order"
value="{{ request('order') }}"
placeholder="Search Order"
class="w-full border rounded-lg px-3 py-2 text-sm">

</div>

<div class="flex items-end gap-2">

<button
class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm">
Apply
</button>

<a href="{{ route('dashboard') }}"
class="bg-gray-400 text-white px-4 py-2 rounded-lg text-sm">
Reset
</a>

</div>

</form>

</div>

{{-- LINE STATUS --}}
<div class="bg-white shadow rounded-xl p-6 mb-6">

<h2 class="font-semibold text-lg mb-4">
Line Monitoring
</h2>

<div class="grid md:grid-cols-4 gap-4">

<div class="bg-green-500 text-white p-4 rounded text-center">
<p class="text-sm">Line A</p>
<p class="text-xl font-bold">Running</p>
</div>

<div class="bg-red-500 text-white p-4 rounded text-center">
<p class="text-sm">Line B</p>
<p class="text-xl font-bold">Stop</p>
</div>

<div class="bg-yellow-500 text-white p-4 rounded text-center">
<p class="text-sm">Line C</p>
<p class="text-xl font-bold">Setup</p>
</div>

<div class="bg-green-500 text-white p-4 rounded text-center">
<p class="text-sm">Line D</p>
<p class="text-xl font-bold">Running</p>
</div>

</div>

</div>

{{-- KPI CARDS --}}
<div class="grid md:grid-cols-2 gap-6 mb-6">

{{-- PRODUCTION PERFORMANCE --}}
<div class="bg-white shadow rounded-xl p-4 md:p-6">

<h2 class="font-semibold text-sm md:text-lg mb-4">
Production Performance
</h2>

<div class="grid grid-cols-3 gap-4">

{{-- TARGET --}}
<div class="bg-blue-600 text-white rounded-xl h-28 flex flex-col justify-center items-center">

<p class="text-xs opacity-80">Target</p>

<p class="text-2xl font-bold">
{{ number_format($targetQty ?? 0) }}
</p>

<p class="text-xs opacity-70">
/pcs
</p>

</div>


{{-- ACHIEVEMENT --}}
<div class="{{ $performanceColor ?? 'bg-gray-400' }} text-white rounded-xl h-28 flex flex-col justify-center items-center">

<p class="text-xs opacity-80">Achievement</p>

<p class="text-2xl font-bold">
{{ $achievementPercent ?? 0 }}%
</p>

<p class="text-xs opacity-70">
Production Rate
</p>

</div>


{{-- GAP --}}
<div class="bg-gray-700 text-white rounded-xl h-28 flex flex-col justify-center items-center">

<p class="text-xs opacity-80">Gap</p>

<p class="text-2xl font-bold">
{{ number_format($gap ?? 0) }}
</p>

<p class="text-xs opacity-70">
/pcs remaining
</p>

</div>
    
</div>

</div>



{{-- PRODUCTION OUTPUT --}}
<div class="bg-white shadow rounded-xl p-5 md:p-6">

<h2 class="font-semibold text-sm md:text-lg mb-5">
Production Output
</h2>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4">

{{-- OK --}}
<div class="bg-green-50 border border-green-200 rounded-xl p-4">

<div class="flex justify-between items-center mb-2">
<p class="text-xs text-gray-600">OK</p>
<span class="text-green-600 text-sm">✔</span>
</div>

<p class="text-2xl font-bold text-green-600">
{{ number_format($totalOk) }}
</p>

<p class="text-xs text-gray-500 mb-2">
{{ $okPercent }}% of production
</p>

<div class="w-full bg-green-100 rounded-full h-2">
<div class="bg-green-500 h-2 rounded-full"
style="width: {{ $okPercent }}%"></div>
</div>

</div>


{{-- REPAIR --}}
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">

<div class="flex justify-between items-center mb-2">
<p class="text-xs text-gray-600">Repair</p>
<span class="text-yellow-500 text-sm">🛠</span>
</div>

<p class="text-2xl font-bold text-yellow-600">
{{ number_format($totalRepair) }}
</p>

<p class="text-xs text-gray-500 mb-2">
{{ $repairPercent }}% of production
</p>

<div class="w-full bg-yellow-100 rounded-full h-2">
<div class="bg-yellow-500 h-2 rounded-full"
style="width: {{ $repairPercent }}%"></div>
</div>

</div>


{{-- REJECT --}}
<div class="bg-red-50 border border-red-200 rounded-xl p-4">

<div class="flex justify-between items-center mb-2">
<p class="text-xs text-gray-600">Reject</p>
<span class="text-red-500 text-sm">✖</span>
</div>

<p class="text-2xl font-bold text-red-600">
{{ number_format($totalReject) }}
</p>

<p class="text-xs text-gray-500 mb-2">
{{ $rejectPercent }}% of production
</p>

<div class="w-full bg-red-100 rounded-full h-2">
<div class="bg-red-500 h-2 rounded-full"
style="width: {{ $rejectPercent }}%"></div>
</div>

</div>


{{-- REJECT RATE --}}
<div class="bg-pink-50 border border-pink-200 rounded-xl p-4">

<div class="flex justify-between items-center mb-2">
<p class="text-xs text-gray-600">Reject Rate</p>
<span class="text-pink-500 text-sm">📊</span>
</div>

<p class="text-2xl font-bold text-pink-600">
{{ $rejectRate ?? 0 }}%
</p>

<p class="text-xs text-gray-500 mb-2">
Quality KPI
</p>

<div class="w-full bg-pink-100 rounded-full h-2">
<div class="bg-pink-500 h-2 rounded-full"
style="width: {{ $rejectRate ?? 0 }}%"></div>
</div>

</div>

</div>

</div>


{{-- PRODUCTION CONTROL --}}
<div class="bg-white shadow rounded-xl p-4 md:p-6">

<div class="flex justify-between items-center mb-4">
<h2 class="font-semibold text-sm md:text-lg">Production Control</h2>

<button onclick="openControlModal()"
class="text-xs text-white bg-red-600 hover:bg-red-500 px-3 py-1 rounded">
Detail
</button>
</div>

<div class="flex flex-wrap gap-3 text-center">

<div class="flex-1 min-w-[90px] bg-indigo-600 text-white p-3 rounded">
<p class="text-xs">Remaining</p>
<p class="text-lg font-bold">{{ $remainingHours ?? 0 }}h</p>
</div>

<div class="flex-1 min-w-[90px] bg-teal-600 text-white p-3 rounded">
<p class="text-xs">Current</p>
<p class="text-lg font-bold">{{ $currentSpeed ?? 0 }}/h</p>
</div>

<div class="flex-1 min-w-[90px] bg-orange-600 text-white p-3 rounded">
<p class="text-xs">Required</p>
<p class="text-lg font-bold">{{ $requiredSpeed ?? 0 }}/h</p>
</div>

</div>

</div>


{{-- MONITORING --}}
<div class="bg-white shadow rounded-xl p-4 md:p-6">

<h2 class="font-semibold text-sm md:text-lg mb-4">Monitoring</h2>

<div class="flex flex-wrap gap-3 text-center">

<div class="flex-1 min-w-[100px] bg-purple-600 text-white p-3 rounded">
<p class="text-xs">Abnormality</p>
<p class="text-lg font-bold">{{ $openAbnormality ?? 0 }}</p>
</div>

<div class="flex-1 min-w-[100px] bg-gray-800 text-white p-3 rounded">
<p class="text-xs">Downtime</p>
<p class="text-lg font-bold">{{ $activeDowntime ?? 0 }}</p>
</div>

</div>

</div>

</div>


{{-- ================= PROGRESS ================= --}}
@php
$progress = $targetQty > 0 ? ($totalOk / $targetQty) * 100 : 0;

$progressComment = "Behind target";

if($progress >= 100){
$progressComment = "Production target achieved";
}elseif($progress >= 80){
$progressComment = "Production on track";
}elseif($progress >= 50){
$progressComment = "Production progressing";
}
@endphp

<div class="w-full bg-white shadow rounded-xl p-6 mb-6">

<div class="flex justify-between items-center mb-3">

<h2 class="text-lg font-bold">Production Progress</h2>

<span class="text-sm font-semibold text-gray-600">
{{ round($progress,1) }}%
</span>

</div>

<div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">

<div
class="bg-green-500 h-6 flex items-center justify-center text-white text-xs font-semibold transition-all duration-500"
style="width: {{ $progress }}%">

{{ round($progress,1) }}%

</div>

</div>

<div class="flex justify-between text-xs text-gray-500 mt-2">

<span>0</span>

<span>Target : {{ number_format($targetQty ?? 0) }}</span>

<span>Actual : {{ number_format($totalOk ?? 0) }}</span>

</div>

<p class="mt-3 text-sm font-medium text-gray-600">
{{ $progressComment }}
</p>

</div>



{{-- CHART --}}
<div class="bg-white shadow rounded-xl p-4 md:p-6 mb-6">

<h2 class="font-semibold mb-4 text-sm md:text-base">Production Trend</h2>

<div style="height:320px">
<canvas id="productionChart"></canvas>
</div>

</div>



{{-- TABLE --}}
<div class="bg-white shadow rounded-xl p-4 md:p-6">

<h2 class="font-semibold mb-4 text-sm md:text-base">
Recent Production
</h2>

<div class="overflow-x-auto">

<table class="min-w-full text-xs md:text-sm">

<thead class="bg-gray-100">
<tr>
<th class="p-2">Time</th>
<th class="p-2">Pro Number</th>
<th class="p-2">Job Number</th>
<th class="p-2">Part Name</th>
<th class="p-2">Process</th>
<th class="p-2">Shift</th>
<th class="p-2">OK</th>
<th class="p-2">Repair</th>
<th class="p-2">Reject</th>
<th class="p-2">Status</th>
</tr>
</thead>

<tbody>

@forelse($latestProductions as $p)

<tr class="text-center border-t hover:bg-gray-50">

<td class="p-2">
{{ $p->created_at->format('H:i') }}
</td>

<td class="p-2">
{{ $p->production_order_number }}
</td>

<td class="p-2">
{{ $p->job->job_number ?? '-' }}
</td>

<td class="p-2 font-semibold">
{{ $p->job->job_name ?? '-' }}
</td>

<td class="p-2">
{{ $p->process_type }}
</td>

<td class="p-2">
{{ $p->shift }}
</td>

<td class="p-2 text-green-600 font-semibold">
{{ $p->qty_ok }}
</td>

<td class="p-2 text-yellow-600 font-semibold">
{{ $p->qty_repair }}
</td>

<td class="p-2 text-red-600 font-semibold">
{{ $p->qty_reject }}
</td>

<td class="p-2">

@if($p->status=='approved')
<span class="bg-green-500 text-white px-2 py-1 rounded text-xs">
Approved
</span>

@elseif($p->status=='rejected')
<span class="bg-red-500 text-white px-2 py-1 rounded text-xs">
Rejected
</span>

@else
<span class="bg-yellow-500 text-white px-2 py-1 rounded text-xs">
Pending
</span>
@endif

</td>

</tr>

@empty

<tr>
<td colspan="9" class="text-center py-6 text-gray-500">
No production data available
</td>
</tr>

@endforelse

</tbody>

</table>

</div>

{{-- PAGINATION --}}
@if($latestProductions->hasPages())
<div class="mt-4 flex justify-center">
{{ $latestProductions->links() }}
</div>
@endif

</div>


{{-- PRODUCTION CONTROL MODAL --}}
<div id="controlModal"
class="fixed inset-0 bg-black/30 backdrop-blur-sm hidden items-center justify-center z-50">

<div class="bg-white rounded-2xl shadow-2xl w-[480px] max-w-full p-6">

{{-- HEADER --}}
<div class="flex justify-between items-center mb-5">

<h3 class="text-lg font-bold text-gray-800">
Production Control Detail
</h3>

<button onclick="closeControlModal()"
class="text-gray-400 hover:text-red-500 text-xl leading-none">
✕
</button>

</div>


{{-- SPEED STATUS --}}
<div class="mb-5 p-4 rounded-xl

@if($status=='on_track')
bg-green-50 border border-green-200
@elseif($status=='behind')
bg-red-50 border border-red-200
@else
bg-gray-50 border border-gray-200
@endif

">

<div class="flex items-center justify-between">

<div>

<p class="text-xs text-gray-500">Production Status</p>

@if($status=='on_track')
<p class="text-green-600 font-semibold">
🟢 On Track
</p>

@elseif($status=='behind')
<p class="text-red-600 font-semibold">
🔴 Behind Required Speed
</p>

@else
<p class="text-gray-500 font-semibold">
⚪ Waiting Production Data
</p>
@endif

</div>

<div class="text-right">

<p class="text-xs text-gray-500">Current Speed</p>

<p class="text-lg font-bold text-gray-800">
{{ $currentSpeed ?? '-' }} /h
</p>

</div>

</div>

</div>


{{-- PRODUCTION SUMMARY --}}
<div class="grid grid-cols-3 gap-3 mb-5 text-center">

<div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
<p class="text-xs text-gray-500">Target</p>
<p class="text-lg font-bold text-blue-600">
{{ number_format($targetQty ?? 0) }}
</p>
</div>

<div class="bg-green-50 border border-green-100 rounded-lg p-3">
<p class="text-xs text-gray-500">Actual</p>
<p class="text-lg font-bold text-green-600">
{{ number_format($totalOk ?? 0) }}
</p>
</div>

<div class="bg-orange-50 border border-orange-100 rounded-lg p-3">
<p class="text-xs text-gray-500">Remaining</p>
<p class="text-lg font-bold text-orange-600">
{{ max(($targetQty ?? 0) - ($totalOk ?? 0),0) }}
</p>
</div>

</div>


{{-- SPEED ANALYSIS --}}
<div class="grid grid-cols-3 gap-3 text-center">

<div class="bg-indigo-50 border border-indigo-100 rounded-lg p-3">
<p class="text-xs text-gray-500">Remaining Time</p>
<p class="text-lg font-bold text-indigo-600">
{{ $remainingHours ?? 0 }} h
</p>
</div>

<div class="bg-teal-50 border border-teal-100 rounded-lg p-3">
<p class="text-xs text-gray-500">Current Speed</p>
<p class="text-lg font-bold text-teal-600">
{{ $currentSpeed ?? 0 }}/h
</p>
</div>

<div class="bg-red-50 border border-red-100 rounded-lg p-3">
<p class="text-xs text-gray-500">Required Speed</p>
<p class="text-lg font-bold text-red-600">
{{ $requiredSpeed ?? 0 }}/h
</p>
</div>

</div>

</div>

</div>


{{-- SCRIPTS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

document.addEventListener("DOMContentLoaded", function(){

function updateClock(){
const now = new Date()
document.getElementById("liveClock").innerText =
now.toLocaleTimeString()
}

setInterval(updateClock,1000)
updateClock()

const ctx = document.getElementById('productionChart')

if(ctx){

new Chart(ctx,{
type:'line',
data:{
labels:@json($chartLabels),
datasets:[
{
label:'Expected Production',
data:@json($expectedProduction),
borderColor:'#2563eb',
borderWidth:2,
tension:0.3
},
{
label:'Actual Production',
data:@json($actualProduction),
borderColor:'#16a34a',
borderWidth:2,
tension:0.3
}
]
},
options:{
responsive:true,
maintainAspectRatio:false
}
})

}

})

//logic modal production control
function openControlModal(){
document.getElementById('controlModal')
.classList.remove('hidden')
document.getElementById('controlModal')
.classList.add('flex')
}

function closeControlModal(){
document.getElementById('controlModal')
.classList.add('hidden')
}

function openControlModal(){

const modal = document.getElementById('controlModal')

modal.classList.remove('hidden')
modal.classList.add('flex')

document.body.classList.add('overflow-hidden')

}

function closeControlModal(){

const modal = document.getElementById('controlModal')

modal.classList.add('hidden')

document.body.classList.remove('overflow-hidden')

}

</script>

@endsection

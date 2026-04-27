@extends('layouts.layouts')

@section('content')

<div class="w-full max-w-[1900px] mx-auto p-6">

{{-- HEADER --}}
<div class="flex justify-between items-center mb-10">

<div>
<h1 class="text-4xl font-bold tracking-wide">
Factory Monitoring
</h1>

<p class="text-gray-500 text-lg">
{{ now()->format('d F Y') }}
</p>
</div>

<div class="text-right">

<p class="text-gray-500 text-sm">
LIVE TIME
</p>

<p id="liveClock"
class="text-5xl font-bold text-blue-600 tracking-wider">
</p>

</div>

</div>


{{-- ALERT PANEL --}}
<div class="bg-red-50 border border-red-200 rounded-xl shadow p-6 mb-10">

<div class="flex justify-between items-center mb-4">

<h2 class="text-xl font-bold text-red-600">
⚠ Production Alerts
</h2>

<button onclick="openIssueModal()"
class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
Add Issue
</button>

</div>

<ul class="space-y-2 text-lg">

@foreach($alerts as $alert)

<li>
Line {{ $alert->line }} – {{ $alert->message }}
</li>

@endforeach

</ul>

</div>


{{-- KPI --}}
<div class="grid grid-cols-2 lg:grid-cols-3 gap-6 mb-12 text-center">

<div class="bg-white rounded-xl shadow p-8">

<p class="text-gray-500 text-lg mb-2">
Total Production
</p>

<p class="text-6xl font-bold text-blue-600">
{{ number_format($totalProduction) }}
</p>

</div>


<div class="bg-white rounded-xl shadow p-8">

<p class="text-gray-500 text-lg mb-2">
Average Speed
</p>

<p class="text-6xl font-bold text-green-600">
{{ $averageSpeed }}
</p>

<p class="text-gray-400 text-lg">
pcs/hour
</p>

</div>


<div class="bg-white rounded-xl shadow p-8">

<p class="text-gray-500 text-lg mb-2">
Active Lines
</p>

<p class="text-6xl font-bold text-gray-800">
{{ $activeLines }} / 4
</p>

</div>

</div>



{{-- LINE STATUS --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">

@foreach($lines as $line)

@php
$statusColor = match($line->status) {
'downtime' => 'bg-red-600',
'slow' => 'bg-yellow-500',
default => 'bg-green-600'
};
@endphp

<div class="rounded-xl shadow-lg p-8 text-center text-white {{ $statusColor }}">

<h2 class="text-3xl font-bold mb-2">
LINE {{ $line->name }}
</h2>

<p class="text-xl tracking-wide">
{{ strtoupper($line->status) }}
</p>

</div>

@endforeach

</div>



{{-- LINE MONITORING TABLE --}}
<div class="bg-white shadow rounded-xl p-6">

<h2 class="text-xl font-bold mb-4">
Line Monitoring Summary
</h2>

<div class="overflow-x-auto">

<table class="w-full text-lg text-center">

<thead class="bg-gray-100">

<tr>
<th class="p-3">Line</th>
<th class="p-3">Status</th>
<th class="p-3">Target</th>
<th class="p-3">Actual</th>
<th class="p-3">Speed</th>
<th class="p-3">Downtime</th>
<th class="p-3">Reject</th>
</tr>

</thead>

<tbody>

@foreach($lines as $line)

<tr class="border-t">

<td class="p-3 font-semibold">
Line {{ $line->name }}
</td>

<td class="p-3">

@if($line->status == 'running')
<span class="text-green-600 font-semibold">Running</span>
@elseif($line->status == 'downtime')
<span class="text-red-600 font-semibold">Stop</span>
@else
<span class="text-yellow-600 font-semibold">Slow</span>
@endif

</td>

<td class="p-3">10000</td>
<td class="p-3">{{ rand(3000,9000) }}</td>
<td class="p-3">{{ rand(200,500) }}/h</td>
<td class="p-3">{{ rand(0,20) }} min</td>
<td class="p-3 text-red-600">{{ rand(5,40) }}</td>

</tr>

@endforeach

</tbody>

</table>

</div>

</div>

</div>




{{-- QUALITY ISSUE MODAL --}}
<div id="issueModal"
class="fixed inset-0 hidden items-center justify-center bg-black/40 z-50">

<div class="bg-white rounded-xl shadow p-6 w-full max-w-lg">

<div class="flex justify-between items-center mb-4">

<h2 class="text-lg font-bold">
Add Quality Issue
</h2>

<button onclick="closeIssueModal()" class="text-gray-500 text-xl">
✕
</button>

</div>

<form method="POST" action="{{ route('quality.store') }}">
@csrf

<div class="grid grid-cols-1 md:grid-cols-2 gap-3">

<input name="problem"
placeholder="Problem"
class="border p-2 rounded w-full">

<input name="qty"
type="number"
placeholder="Qty"
class="border p-2 rounded w-full">

<input name="cause"
placeholder="Cause"
class="border p-2 rounded w-full">

<input name="countermeasure"
placeholder="Countermeasure"
class="border p-2 rounded w-full">

</div>

<div class="flex justify-end gap-2 mt-4">

<button type="button"
onclick="closeIssueModal()"
class="px-4 py-2 border rounded">
Cancel
</button>

<button type="submit"
class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
Save
</button>

</div>

</form>

</div>

</div>



@endsection


@section('scripts')

<script>

function updateClock(){
const now = new Date()
document.getElementById("liveClock").innerText =
now.toLocaleTimeString()
}

setInterval(updateClock,1000)
updateClock()

setInterval(function(){
location.reload()
},60000)

function openIssueModal(){
document.getElementById('issueModal').classList.remove('hidden')
document.getElementById('issueModal').classList.add('flex')
}

function closeIssueModal(){
document.getElementById('issueModal').classList.remove('flex')
document.getElementById('issueModal').classList.add('hidden')
}

</script>

@endsection
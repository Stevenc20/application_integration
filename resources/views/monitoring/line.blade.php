@extends('layouts.supervisor')

@section('content')

<div class="p-4 md:p-6">
{{-- HEADER --}}
<div class="flex justify-between items-center mb-6">

<div>
<h1 class="text-xl md:text-2xl font-bold">Line Monitoring</h1>
<p class="text-gray-500 text-xs md:text-sm">{{ now()->format('d F Y') }}</p>
</div>

<div class="text-right">
<p class="text-xs text-gray-500">Live Time</p>
<p id="liveClock" class="text-xl md:text-2xl font-bold text-blue-600"></p>
</div>

</div>



{{-- FACTORY STATUS --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 ">

<div class="bg-white shadow rounded-xl p-4">
<p class="text-xs text-gray-500">Total Production Today</p>
<p class="text-2xl font-bold text-blue-600">24,850 pcs</p>
</div>

<div class="bg-white shadow rounded-xl p-4">
<p class="text-xs text-gray-500">Average Speed</p>
<p class="text-2xl font-bold text-green-600">480 pcs/h</p>
</div>

<div class="bg-white shadow rounded-xl p-4">
<p class="text-xs text-gray-500">Active Lines</p>
<p class="text-2xl font-bold text-gray-800">4 / 4</p>
</div>

</div>



{{-- LINE MONITORING GRID --}}
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 pt-4">

@foreach(['A','B','C','D'] as $line)

<div class="bg-white rounded-xl shadow p-4 border border-gray-100">

{{-- LINE HEADER --}}
<div class="flex justify-between items-center mb-3">
<h3 class="font-bold text-gray-800">Line {{ $line }}</h3>

<span class="text-xs px-2 py-1 rounded bg-green-100 text-green-700">
Running
</span>
</div>

{{-- PRODUCTION PROGRESS --}}
<div class="mb-3">

<p class="text-xs text-gray-500">Production Progress</p>

<div class="w-full bg-gray-200 h-2 rounded mt-1">
<div class="bg-blue-500 h-2 rounded"
style="width: {{ rand(40,90) }}%"></div>
</div>

</div>


{{-- KPI GRID --}}
<div class="grid grid-cols-2 gap-2 text-center">

<div class="bg-blue-50 rounded p-2">
<p class="text-xs text-gray-500">Target</p>
<p class="font-bold text-blue-600">{{ rand(8000,12000) }}</p>
</div>

<div class="bg-green-50 rounded p-2">
<p class="text-xs text-gray-500">Actual</p>
<p class="font-bold text-green-600">{{ rand(3000,9000) }}</p>
</div>

<div class="bg-orange-50 rounded p-2">
<p class="text-xs text-gray-500">Current Speed</p>
<p class="font-bold text-orange-600">{{ rand(300,600) }}/h</p>
</div>

<div class="bg-red-50 rounded p-2">
<p class="text-xs text-gray-500">Required</p>
<p class="font-bold text-red-600">{{ rand(350,650) }}/h</p>
</div>

</div>


{{-- QUALITY --}}
<div class="mt-3 grid grid-cols-3 gap-2 text-center">

<div class="bg-green-100 rounded p-2">
<p class="text-xs">OK</p>
<p class="font-bold text-green-700">{{ rand(2000,7000) }}</p>
</div>

<div class="bg-yellow-100 rounded p-2">
<p class="text-xs">Repair</p>
<p class="font-bold text-yellow-700">{{ rand(10,100) }}</p>
</div>

<div class="bg-red-100 rounded p-2">
<p class="text-xs">Reject</p>
<p class="font-bold text-red-700">{{ rand(5,80) }}</p>
</div>

</div>


{{-- DOWNTIME --}}
<div class="mt-4 border-t pt-3">

<p class="text-xs text-gray-500 mb-2">Downtime</p>

<div class="grid grid-cols-2 gap-2 text-center">

<div class="bg-red-50 rounded p-2">
<p class="text-xs text-gray-500">Duration</p>
<p class="font-bold text-red-600">{{ rand(0,45) }} min</p>
</div>

<div class="bg-gray-100 rounded p-2">
<p class="text-xs text-gray-500">Machine</p>
<p class="font-bold text-gray-700">Press {{ rand(1,3) }}</p>
</div>

</div>

<div class="mt-2 bg-orange-50 rounded p-2 text-center">

<p class="text-xs text-gray-500">Reason</p>

<p class="font-bold text-orange-600 text-sm">

@php
$reasons = ['Material Jam','Setup Change','Sensor Error','Maintenance','No Issue'];
echo $reasons[array_rand($reasons)];
@endphp

</p>

</div>

</div>

</div>

@endforeach

</div>



{{-- PRODUCTION TREND --}}
<div class="bg-white shadow rounded-xl p-4 md:p-6 mt-6" data-line="{{ $line }}">

    {{-- LINE FILTER --}}
<div class="flex flex-wrap gap-2 mb-4">

<button class="line-filter px-3 py-1 rounded bg-red-600 text-white text-sm"
data-line="all">
All Lines
</button>

@foreach(['A','B','C','D'] as $line)

<button class="line-filter px-3 py-1 rounded bg-gray-200 hover:bg-gray-300 text-sm"
data-line="{{ $line }}">
Line {{ $line }}
</button>

@endforeach

</div>

<h2 class="font-semibold mb-4">Hourly Production Trend</h2>

<canvas id="lineChart" height="90"></canvas>

</div>



@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>

function updateClock(){
const now = new Date()
document.getElementById("liveClock").innerText =
now.toLocaleTimeString()
}

setInterval(updateClock,1000)
updateClock()

const ctx = document.getElementById('lineChart');

const chartData = {

all: [120,300,550,820,1200,1500,1900,2300,2600,2900],
A: [80,200,350,500,700,900,1100,1400,1600,1800],
B: [40,100,200,320,500,600,800,900,1000,1100],
C: [20,50,100,200,350,500,650,800,900,1000],
D: [10,30,80,150,200,300,400,500,600,700]

};

const targetData = [200,400,600,800,1000,1200,1400,1600,1800,2000];

const productionChart = new Chart(ctx, {

type:'line',

data:{
labels:['07','08','09','10','11','12','13','14','15','16'],
datasets:[
{
label:'Actual Production',
data:chartData.all,
borderWidth:2,
tension:0.3
},
{
label:'Target',
data:targetData,
borderDash:[5,5],
borderWidth:2
}
]
},

options:{
responsive:true,
plugins:{
legend:{position:'top'}
}
}

});

document.querySelectorAll('.line-filter').forEach(btn => {

btn.addEventListener('click', function(){

const selected = this.dataset.line

// button style
document.querySelectorAll('.line-filter').forEach(b=>{
b.classList.remove('bg-red-600','text-white')
b.classList.add('bg-gray-200')
})

this.classList.remove('bg-gray-200')
this.classList.add('bg-red-600','text-white')

// update chart data
productionChart.data.datasets[0].data = chartData[selected]

productionChart.update()

})

})
</script>
@endsection

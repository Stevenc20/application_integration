@php
$role = strtolower(auth()->user()->role ?? '');

$title = 'Production';

if($role == 'operator'){
    $title = 'Production Entry';
}elseif($role == 'supervisor'){
    $title = 'Production Data';
}elseif($role == 'admin'){
    $title = 'Production Overview';
}
@endphp

@extends('layouts.layouts')


@section('content')

<div class="p-6">

<div class="flex justify-between items-center mb-6">
    

<div>
<h1 class="text-2xl font-bold">{{ $title }}</h1>
<p class="text-gray-500 text-sm">{{ now()->format('d F Y') }}</p>
</div>

@if(auth()->user()->role == 'operator')
<button onclick="openProductionModal()"
class="bg-red-600 text-white px-4 py-2 rounded">
+ Add Production
</button>
@endif

</div>

@if ($errors->any())
<div class="bg-red-100 text-red-700 p-3 mb-4 rounded">
<ul>
@foreach ($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul>
</div>
@endif


@if(auth()->user()->role == 'operator')

<div id="productionModal"
class="fixed inset-0 hidden items-center justify-center bg-black/40 z-[9999]"
onclick="closeProductionModal()">

    <div class="bg-white w-full max-w-3xl p-6 rounded-xl shadow-xl"
         onclick="event.stopPropagation()">

        <!-- HEADER -->
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-xl font-bold">Add Production</h2>

            <button onclick="closeProductionModal()" class="text-gray-500 hover:text-black">
                ✕
            </button>
        </div>

        <!-- FORM -->
        <form method="POST" action="{{ route('production_entry.store') }}">
            @csrf

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label>Order Number</label>
                    <input type="text" name="production_order_number"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label>Job</label>
                    <select id="job_id" name="job_id"
                        class="w-full border rounded px-3 py-2"
                        onchange="fillJobData()">

                        <option value="">Select Job</option>

                        @foreach($jobs as $job)
                        <option value="{{ $job->id }}"
                            data-number="{{ $job->job_number }}"
                            data-name="{{ $job->job_name }}"
                            data-line="{{ $job->line }}"
                            data-capacity="{{ $job->capacity }}">
                            {{ $job->job_number }} - {{ $job->job_name }}
                        </option>
                        @endforeach

                    </select>
                </div>

                <div>
                    <label>Job Name</label>
                    <input id="job_name" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
                </div>

                <div>
                    <label>Job Number</label>
                    <input id="job_number" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
                </div>

                <div>
                    <label>Line</label>
                    <input id="line" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
                </div>

                <div>
                    <label>Capacity</label>
                    <input id="capacity" class="w-full border rounded px-3 py-2 bg-gray-100" readonly>
                </div>

                <div>
                    <label>Process</label>
                    <select name="process_type" class="w-full border rounded px-3 py-2">
                        <option>Stamping</option>
                        <option>Sub Assy</option>
                        <option>Shearing</option>
                        <option>Metal Finish</option>
                    </select>
                </div>

                <div>
                    <label>Shift</label>
                    <select name="shift" class="w-full border rounded px-3 py-2">
                        <option>Shift 1</option>
                        <option>Shift 2</option>
                        <option>Shift 3</option>
                    </select>
                </div>

                <div>
                    <label>Qty OK</label>
                    <input type="number" name="qty_ok"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label>Qty Repair</label>
                    <input type="number" name="qty_repair"
                        class="w-full border rounded px-3 py-2">
                </div>

                <div>
                    <label>Qty Reject</label>
                    <input type="number" name="qty_reject"
                        class="w-full border rounded px-3 py-2">
                </div>

            </div>

            <!-- BUTTON -->
            <div class="flex justify-end gap-2 mt-6">
                <button type="button"
                    onclick="closeProductionModal()"
                    class="bg-gray-400 text-white px-4 py-2 rounded">
                    Cancel
                </button>

                <button type="submit"
                    class="bg-red-600 text-white px-4 py-2 rounded">
                    Save
                </button>
            </div>

        </form>

    </div>
</div>

@endif



{{-- ================= TABLE ================= --}}
<div class="bg-white p-6 rounded shadow">

<div class="flex justify-between items-center mb-4">

<h2 class="font-bold">Production Data</h2>

<form method="GET" action="{{ route('production_entry') }}">
<input type="date"
name="date"
value="{{ request('date') ?? date('Y-m-d') }}"
class="border rounded px-3 py-2"
onchange="this.form.submit()">
</form>

</div>
<div class="overflow-x-auto">

<table class="min-w-full border">

<thead class="bg-gray-100 text-center">

<tr>

<th class="p-3">Order</th>
<th class="p-3">Job Number</th>
<th class="p-3">Job Name</th>
<th class="p-3">Line</th>
<th class="p-3">Process</th>
<th class="p-3">Shift</th>
<th class="p-3">Capacity</th>
<th class="p-3">OK</th>
<th class="p-3">Repair</th>
<th class="p-3">Reject</th>
<th class="p-3">Status</th>

</tr>

</thead>

<tbody>

@foreach($productions as $p)

<tr class="border-t text-center">

<td class="p-3">{{ $p->production_order_number }}</td>
<td class="p-3">{{ $p->job->job_number ?? '-' }}</td>
<td class="p-3">{{ $p->job->job_name ?? '-' }}</td>
<td class="p-3">{{ $p->job->line ?? '-' }}</td>
<td class="p-3">{{ $p->process_type }}</td>
<td class="p-3">{{ $p->shift }}</td>
<td class="p-3">{{ $p->job->capacity ?? '-' }}</td>

<td class="p-3 text-green-600">{{ $p->qty_ok }}</td>
<td class="p-3 text-yellow-600">{{ $p->qty_repair }}</td>
<td class="p-3 text-red-600">{{ $p->qty_reject }}</td>

<td class="p-3">{{ $p->status }}</td>

</tr>

@endforeach

</tbody>

</table>

</div>

</div>

</div>

@endsection

@section('scripts')
<script>

    function openProductionModal() {
        const modal = document.getElementById('productionModal');
        if (!modal) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeProductionModal() {
        const modal = document.getElementById('productionModal');
        if (!modal) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    // =======================
// FILL JOB DATA
// =======================

function fillJobData(){
    const select = document.getElementById('job_id');
    if (!select) return;

    const option = select.options[select.selectedIndex];

    document.getElementById('job_number').value =
        option.getAttribute('data-number') || '';

    document.getElementById('job_name').value =
        option.getAttribute('data-name') || '';

    document.getElementById('line').value =
        option.getAttribute('data-line') || '';

    document.getElementById('capacity').value =
        option.getAttribute('data-capacity') || '';
}
</script>
@endsection
   

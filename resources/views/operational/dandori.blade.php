@extends('layouts.layouts')

@section('content')

<div class="p-6 min-h-screen bg-gray-100 space-y-6">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

        <div>
            <h1 class="text-3xl font-bold text-gray-800">
                Dandori Recording
            </h1>

            <p class="text-sm text-gray-500 mt-1">
                Production Line Changeover Tracker
            </p>
        </div>

        <div class="px-4 py-2 rounded-xl bg-white border shadow text-sm">
            <span class="text-gray-500">Today :</span>
            <span class="font-bold text-blue-600">
                {{ now()->format('d F Y') }}
            </span>
        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- START RECORDING CARD --}}
    {{-- ========================================================= --}}
    <div class="bg-white rounded-2xl border shadow overflow-hidden">

        <div class="px-6 py-4 border-b bg-gray-50">

            <div class="flex items-center justify-between">

                <h2 class="font-bold text-gray-800 text-lg">
                    ▶ Mulai Pencatatan Dandori
                </h2>

                <span class="text-xs px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 font-semibold">
                    LIVE MODULE
                </span>

            </div>

        </div>



        <div class="p-6">

            <p class="text-sm text-gray-500 mb-5">
                Pilih line dan shift untuk menampilkan item pending yang akan dilakukan changeover.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- LINE --}}
                <div>

                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">
                        Line Produksi
                    </label>

                    <select id="line" class="w-full border rounded-xl px-4 py-3 bg-white">

                    <option value="">Pilih Line</option>

                    @foreach($lines as $rowLine)
                    <option value="{{ $rowLine }}"
                    {{ $line == $rowLine ? 'selected' : '' }}>
                    {{ $rowLine }}
                    </option>
                    @endforeach

                    </select>
                </div>



                {{-- SHIFT --}}
                <div>

                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">
                        Shift
                    </label>

                    <select id="shift" class="w-full border rounded-xl px-4 py-3 bg-white">

                    <option value="">Pilih Shift</option>

                    <option value="Shift 1"
                    {{ $shift == 'Shift 1' ? 'selected' : '' }}>
                    Shift 1
                    </option>

                    <option value="Shift 2"
                    {{ $shift == 'Shift 2' ? 'selected' : '' }}>
                    Shift 2
                    </option>

                    </select>
                </div>



                {{-- BUTTON --}}
                <div class="flex items-end">

                    <button onclick="loadJobs()"
                    class="w-full bg-yellow-400 hover:bg-yellow-500 rounded-xl py-3 font-bold text-black transition">
                        Tampilkan Item
                    </button>

                </div>

            </div>



            {{-- JOB LIST --}}
            <div id="jobList" class="mt-6 space-y-3"></div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- HISTORY CARD --}}
    {{-- ========================================================= --}}
    <div class="bg-white rounded-2xl border shadow overflow-hidden">

        <div class="px-6 py-4 border-b bg-gray-50">

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

                <div>
                    <h2 class="font-bold text-gray-800 text-lg">
                        ▶ History Dandori
                    </h2>

                    <p class="text-xs text-gray-500 mt-1">
                        Riwayat pergantian model, setup line, trial, adjustment.
                    </p>
                </div>

                <button onclick="loadHistory()"
                class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold">
                    Refresh
                </button>

            </div>

        </div>



        {{-- FILTER --}}
        <div class="p-6 border-b bg-gray-50">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- DATE --}}
                <div>

                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">
                        Tanggal
                    </label>

                    <input type="date"
                    id="historyDate"
                    value="{{ now()->format('Y-m-d') }}"
                    class="w-full border rounded-xl px-4 py-3 bg-white">

                </div>



                {{-- LINE --}}
                <div>

                    <label class="block text-xs font-semibold text-gray-500 uppercase mb-2">
                        Line Produksi
                    </label>

                    <select id="historyLine"
                    class="w-full border rounded-xl px-4 py-3 bg-white">

                        <option value="">Semua Line</option>

                        @foreach($lines as $rowLine)
                            <option value="{{ $rowLine }}">
                                {{ $rowLine }}
                            </option>
                        @endforeach

                    </select>

                </div>



                {{-- BUTTONS --}}
                <div class="flex items-end gap-2">

                    <button onclick="loadHistory()"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white rounded-xl py-3 font-bold">
                        Filter
                    </button>

                    <button onclick="resetFilter()"
                    class="px-5 py-3 rounded-xl border bg-white hover:bg-gray-100">
                        Reset
                    </button>

                </div>

            </div>

        </div>



        {{-- TABLE --}}
        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead class="bg-gray-100 uppercase text-xs text-gray-600">

                    <tr>
                        <th class="px-4 py-3 text-left">Item / Part Number</th>
                        <th class="px-4 py-3 text-left">Jenis Dandori</th>
                        <th class="px-4 py-3 text-center">Mulai</th>
                        <th class="px-4 py-3 text-center">Selesai</th>
                        <th class="px-4 py-3 text-right">Durasi</th>
                    </tr>

                </thead>

                <tbody id="historyBody">

                    <tr>
                        <td colspan="5" class="py-10 text-center text-gray-400">
                            Loading...
                        </td>
                    </tr>

                </tbody>

                <tfoot id="historyFooter"></tfoot>

            </table>

        </div>

    </div>

</div>

@endsection


@section('scripts')
<script>

/* =========================================================
AUTO LOAD
========================================================= */
document.addEventListener('DOMContentLoaded', function () {

    loadHistory();

    let autoJob = "{{ $jobId ?? '' }}";

    if (autoJob !== '') {

        setTimeout(function () {
            loadJobs();

            setTimeout(function () {
                openJob(autoJob);
            }, 700);

        }, 300);
    }

});


/* =========================================================
RESET FILTER
========================================================= */
function resetFilter() {

    document.getElementById('historyDate').value = '{{ now()->format("Y-m-d") }}';
    document.getElementById('historyLine').value = '';

    loadHistory();
}


/* =========================================================
LOAD JOBS
========================================================= */
function loadJobs() {

    let line = document.getElementById('line').value;
    let shift = document.getElementById('shift').value;

    let box = document.getElementById('jobList');

    if (line === '' || shift === '') {

        box.innerHTML = `
        <div class="border rounded-xl bg-red-50 border-red-200 text-red-600 p-4 text-sm">
            Pilih line dan shift terlebih dahulu.
        </div>
        `;
        return;
    }

    box.innerHTML = `
    <div class="border rounded-xl bg-gray-50 py-8 text-center text-gray-400">
        Loading...
    </div>
    `;

    fetch(`{{ route('operational.dandori.loadJobs') }}?line=${line}&shift=${shift}`)
    .then(res => res.json())
    .then(rows => {

        let html = '';

        if (!rows || rows.length === 0) {

            html = `
            <div class="border rounded-xl bg-gray-50 py-8 text-center text-gray-500">
                Data item belum tersedia
            </div>
            `;

        } else {

            rows.forEach(row => {

                html += `
                <div class="border rounded-xl px-4 py-3 bg-white hover:bg-gray-50 transition flex justify-between items-center">

                    <div>
                        <div class="font-bold text-gray-800 text-sm">
                            ${row.job_number ?? '-'}
                        </div>

                        <div class="text-gray-500 text-xs mt-1">
                            ${row.job_name ?? '-'}
                        </div>

                        <div class="text-gray-400 text-xs mt-1">
                            ${row.line ?? '-'} | ${shift}
                        </div>
                    </div>

                    <button
                    onclick="openJob('${row.id}')"
                    class="px-4 py-2 rounded-lg border text-gray-700 text-xs hover:bg-gray-100 transition">
                        Open
                    </button>

                </div>
                `;

            });

        }

        box.innerHTML = html;

    })
    .catch(error => {

        box.innerHTML = `
        <div class="border rounded-xl bg-red-50 py-8 text-center text-red-500">
            Gagal mengambil data item
        </div>
        `;

        console.log(error);

    });

}


/* =========================================================
LOAD HISTORY
========================================================= */
function loadHistory() {

    let date = document.getElementById('historyDate').value;
    let line = document.getElementById('historyLine').value;

    fetch(`{{ route('operational.dandori.history') }}?date=${date}&line=${line}`)
    .then(res => res.json())
    .then(rows => {

        let body = '';
        let footer = '';
        let total = 0;

        if (!rows || rows.length === 0) {

            body = `
            <tr>
                <td colspan="5" class="py-12 text-center text-gray-400">
                    Data history belum ada
                </td>
            </tr>
            `;

            footer = `
            <tr class="bg-yellow-50 border-t">
                <td colspan="4" class="px-4 py-4 text-right font-bold text-yellow-700">
                    TOTAL DURASI :
                </td>
                <td class="px-4 py-4 text-right font-bold text-yellow-700">
                    0 MENIT
                </td>
            </tr>
            `;

        } else {

            rows.forEach(row => {

                let dur = parseFloat(row.duration_minutes ?? 0);
                total += dur;

                body += `
                <tr class="border-t hover:bg-gray-50 transition">

                    <td class="px-4 py-3 font-semibold text-gray-800">
                        ${row.job_number ?? '-'}
                    </td>

                    <td class="px-4 py-3 text-gray-600">
                        ${row.activity ?? '-'}
                    </td>

                    <td class="px-4 py-3 text-center text-gray-600">
                        ${row.start_time ?? '-'}
                    </td>

                    <td class="px-4 py-3 text-center text-gray-600">
                        ${row.finish_time ?? '-'}
                    </td>

                    <td class="px-4 py-3 text-right font-bold text-blue-600">
                        ${dur.toFixed(2)}
                    </td>

                </tr>
                `;

            });

            footer = `
            <tr class="bg-yellow-50 border-t">

                <td colspan="4"
                class="px-4 py-4 text-right font-bold text-yellow-700 uppercase">
                    TOTAL DURASI :
                </td>

                <td class="px-4 py-4 text-right font-bold text-yellow-700">
                    ${total.toFixed(2)} MENIT
                </td>

            </tr>
            `;

        }

        document.getElementById('historyBody').innerHTML = body;
        document.getElementById('historyFooter').innerHTML = footer;

    })
    .catch(error => {

        document.getElementById('historyBody').innerHTML = `
        <tr>
            <td colspan="5" class="py-12 text-center text-red-500">
                Gagal mengambil history
            </td>
        </tr>
        `;

        console.log(error);

    });

}


/* =========================================================
OPEN JOB
========================================================= */
function openJob(id) {

    if (!confirm('Mulai pencatatan Dandori untuk item ini?')) {
        return;
    }

    fetch(`/operational/dandori/open/${id}`)
    .then(res => res.json())
    .then(row => {

        let jenis = prompt(
            'Jenis Dandori:\n1.Setup Mesin\n2.Ganti Model\n3.Trial\n4.Adjustment',
            'Setup Mesin'
        );

        if (jenis === null || jenis === '') {
            return;
        }

        fetch(`{{ route('operational.dandori.start') }}`, {

            method: 'POST',

            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },

            body: JSON.stringify({
                job_id: row.id,
                line: row.line,
                shift: document.getElementById('shift').value,
                activity: jenis
            })

        })
        .then(res => res.json())
        .then(r => {

            alert('Dandori dimulai');

            loadHistory();
            loadJobs();

        });

    })
    .catch(error => {
        alert('Gagal membuka item');
        console.log(error);
    });

}

</script>
@endsection
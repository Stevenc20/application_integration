@extends('layouts.layouts')

@section('content')
<div class="p-4 sm:p-6">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">Master Job</h1>
            <p class="text-gray-500 text-sm">{{ now()->format('d F Y') }}</p>
        </div>
        <button onclick="openJobModal()"
            class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 transition w-full sm:w-auto">
            + Add Job
        </button>
    </div>

    {{-- CARD --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Job Number</th>
                        <th class="px-4 py-3 text-left">Job Name</th>
                        <th class="px-4 py-3 text-left">Line</th>
                        <th class="px-4 py-3 text-left">Capacity</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobs as $job)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-4 py-3 whitespace-nowrap font-medium">{{ $job->job_number }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $job->job_name }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $job->line }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $job->capacity }} pcs</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col sm:flex-row gap-2 justify-center">

                                <button
                                    type="button"
                                    onclick="openEditJobModal({
                                        id: '{{ $job->id }}',
                                        number: '{{ addslashes($job->job_number) }}',
                                        name: '{{ addslashes($job->job_name) }}',
                                        line: '{{ addslashes($job->line) }}',
                                        capacity: '{{ $job->capacity }}'
                                    })"
                                    class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">
                                    Edit
                                </button>

                              <button
                                type="button"
                                data-id="{{ $job->id }}"
                                data-name="{{ $job->job_name }}"
                                data-number="{{ $job->job_number }}"
                                onclick="openDeleteModal(this)"
                                class="px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600">
                                Delete
                            </button>

                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-10 text-gray-500">No job data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($jobs->hasPages())
        <div class="p-4 border-t">
            {{ $jobs->links() }}
        </div>
        @endif
    </div>

</div>

{{-- ================= MODAL ADD JOB ================= --}}
<div id="jobModal"
    class="fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm"
    style="display:none;">
    <div class="bg-white w-full max-w-lg mx-4 rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Add Job</h2>
            <button type="button" onclick="closeJobModal()" class="text-gray-500 hover:text-gray-700 text-xl font-bold">&times;</button>
        </div>
        <form action="{{ route('master.job.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium">Job Number</label>
                <input type="text" name="job_number"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-red-200" required>
            </div>
            <div>
                <label class="text-sm font-medium">Job Name</label>
                <input type="text" name="job_name"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-red-200" required>
            </div>
            <div>
                <label class="text-sm font-medium">Line</label>
                <input type="text" name="line"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-red-200" required>
            </div>
            <div>
                <label class="text-sm font-medium">Capacity</label>
                <input type="number" name="capacity"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-red-200" required>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeJobModal()"
                    class="px-4 py-2 text-sm bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ================= MODAL EDIT JOB ================= --}}
<div id="editJobModal"
    class="fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm"
    style="display:none;">
    <div class="bg-white w-full max-w-lg mx-4 rounded-xl shadow-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Edit Job</h2>
            <button type="button" onclick="closeEditJobModal()" class="text-gray-500 hover:text-gray-700 text-xl font-bold">&times;</button>
        </div>
        <form id="editForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="text-sm font-medium">Job Number</label>
                <input type="text" id="edit_job_number" name="job_number"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-blue-200" required>
            </div>
            <div>
                <label class="text-sm font-medium">Job Name</label>
                <input type="text" id="edit_job_name" name="job_name"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-blue-200" required>
            </div>
            <div>
                <label class="text-sm font-medium">Line</label>
                <input type="text" id="edit_line" name="line"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-blue-200" required>
            </div>
            <div>
                <label class="text-sm font-medium">Capacity</label>
                <input type="number" id="edit_capacity" name="capacity"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-blue-200" required>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeEditJobModal()"
                    class="px-4 py-2 text-sm bg-gray-200 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ================= MODAL DELETE ================= --}}
<div id="deleteModal"
    class="fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm"
    style="display:none;">
    <div class="bg-white w-full max-w-md mx-4 rounded-xl shadow-lg p-6 text-center">

        {{-- Icon Warning --}}
        <div class="flex justify-center mb-3">
            <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
        </div>

        <h2 class="text-lg font-semibold mb-3">Delete Job?</h2>

        {{-- Info job yang akan dihapus --}}
        <div class="bg-gray-50 rounded-lg px-4 py-3 mb-4 text-left space-y-1">
            <div class="flex gap-2 text-sm">
                <span class="text-gray-500 w-28 shrink-0">Job Number</span>
                <span class="text-gray-400">:</span>
                <span id="deleteJobNumber" class="font-semibold text-gray-800"></span>
            </div>
            <div class="flex gap-2 text-sm">
                <span class="text-gray-500 w-28 shrink-0">Job Name</span>
                <span class="text-gray-400">:</span>
                <span id="deleteJobName" class="font-semibold text-gray-800"></span>
            </div>
        </div>

        <p class="text-red-500 text-xs mb-5">This action cannot be undone.</p>

        <div class="flex justify-center gap-3">
            <button type="button" onclick="closeDeleteModal()"
                class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 text-sm">
                Cancel
            </button>
            <button type="button" onclick="confirmDeleteAction()"
                class="px-5 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm">
                Yes, Delete
            </button>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>

// ================= ADD =================
function openJobModal() {
    document.getElementById('jobModal').style.display = 'flex';
}
function closeJobModal() {
    document.getElementById('jobModal').style.display = 'none';
}

// ================= EDIT =================
function openEditJobModal(data) {
    document.getElementById('edit_job_number').value = data.number;
    document.getElementById('edit_job_name').value   = data.name;
    document.getElementById('edit_line').value       = data.line;
    document.getElementById('edit_capacity').value   = data.capacity;
    document.getElementById('editForm').action       = '/master/job/update/' + data.id;
    document.getElementById('editJobModal').style.display = 'flex';
}
function closeEditJobModal() {
    document.getElementById('editJobModal').style.display = 'none';
}

// ================= DELETE =================
let deleteId = null;

function openDeleteModal(btn) {
    deleteId = btn.dataset.id;
    document.getElementById('deleteJobName').textContent   = btn.dataset.name;
    document.getElementById('deleteJobNumber').textContent = btn.dataset.number;
    document.getElementById('deleteModal').style.display   = 'flex';
}

function closeDeleteModal() {
    deleteId = null;
    document.getElementById('deleteModal').style.display = 'none';
}

function confirmDeleteAction() {
    if (deleteId) {
        window.location.href = '/master/job/delete/' + deleteId;
    }
}

// ================= KLIK LUAR MODAL =================
document.addEventListener('DOMContentLoaded', function () {
    ['jobModal', 'editJobModal', 'deleteModal'].forEach(function (id) {
        const modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    if (id === 'deleteModal') deleteId = null;
                }
            });
        }
    });
});

</script>
@endsection
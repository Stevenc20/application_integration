@extends('layouts.super_admin')

@section('title', 'Manage Line Assignments')

@section('content')
<div class="p-3 sm:p-4 md:p-6">

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl flex items-start gap-3 shadow-sm">
        <ul class="text-sm space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

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
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800">Line & Shift Leader Assignments</h1>
            <p class="text-gray-500 text-xs sm:text-sm">Assign Leaders, Foremen, and Supervisors for Shift Pagi and Shift Malam on each Line</p>
        </div>
        <button onclick="openAddAssignmentModal()"
            class="inline-flex items-center gap-2 bg-red-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition shadow-sm w-full sm:w-auto justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add/Edit Assignment
        </button>
    </div>

    <!-- Line Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($lines as $line)
        <div class="bg-white border border-gray-200 shadow-sm rounded-2xl overflow-hidden flex flex-col">
            <!-- Line Header -->
            <div class="bg-gray-50/50 px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    <h2 class="font-bold text-gray-800 text-sm md:text-base uppercase">{{ $line }}</h2>
                </div>
            </div>

            <!-- Shifts Section -->
            <div class="p-5 flex-1 grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($shifts as $shift)
                @php
                    $assign = $assignments->where('line_name', $line)->where('shift_name', $shift)->first();
                @endphp
                <div class="p-4 rounded-xl border border-gray-100 bg-gray-50/30 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3 border-b border-gray-100 pb-2">
                            <span class="text-xs font-bold text-gray-600 uppercase tracking-wide">{{ $shift }}</span>
                            @if($assign)
                            <div class="flex gap-1.5">
                                <button type="button" 
                                        onclick="openEditAssignmentModal('{{ $line }}', '{{ $shift }}', '{{ $assign->leader_user_id }}', '{{ $assign->foreman_user_id }}', '{{ $assign->supervisor_user_id }}')"
                                        class="text-xs text-red-600 hover:text-red-800 font-semibold transition" title="Edit Assignment">
                                    Edit
                                </button>
                                <span class="text-gray-300 text-[10px]">&bull;</span>
                                <button type="button" 
                                        onclick="openDeleteAssignmentModal('{{ $assign->id }}', '{{ $line }}', '{{ $shift }}')"
                                        class="text-xs text-gray-400 hover:text-red-500 font-semibold transition" title="Delete Assignment">
                                    Hapus
                                </button>
                            </div>
                            @else
                            <button type="button" 
                                    onclick="openEditAssignmentModal('{{ $line }}', '{{ $shift }}', '', '', '')"
                                    class="text-xs text-red-500 hover:text-red-700 font-semibold transition">
                                + Assign
                            </button>
                            @endif
                        </div>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-start justify-between">
                                <span class="text-gray-400">Team Leader</span>
                                <span class="font-bold text-gray-700 text-right">
                                    {{ $assign && $assign->leaderUser ? $assign->leaderUser->name : '-' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between">
                                <span class="text-gray-400">Foreman</span>
                                <span class="font-semibold text-gray-700 text-right">
                                    {{ $assign && $assign->foremanUser ? $assign->foremanUser->name : '-' }}
                                </span>
                            </div>
                            <div class="flex items-start justify-between">
                                <span class="text-gray-400">Supervisor</span>
                                <span class="font-semibold text-gray-700 text-right">
                                    {{ $assign && $assign->supervisorUser ? $assign->supervisorUser->name : '-' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- ADD / EDIT ASSIGNMENT MODAL --}}
<div id="assignmentModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAssignmentModal()"></div>
    <div class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-5">
            <h2 id="modalTitle" class="text-lg font-bold text-gray-800">Add/Edit Assignment</h2>
            <button onclick="closeAssignmentModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="assignmentForm" action="{{ route('super-admin.assignments.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-700">Production Line</label>
                <select name="line_name" id="form_line_name" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition bg-white">
                    @foreach($lines as $line)
                        <option value="{{ $line }}">{{ $line }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Shift</label>
                <select name="shift_name" id="form_shift_name" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition bg-white">
                    @foreach($shifts as $shift)
                        <option value="{{ $shift }}">{{ $shift }}</option>
                    @endforeach
                </select>
            </div>
            <div class="border-t border-gray-100 pt-3">
                <label class="text-sm font-medium text-gray-700">Team Leader</label>
                <select name="leader_user_id" id="form_leader_user_id" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition bg-white">
                    <option value="">-- Select Team Leader (Optional) --</option>
                    @foreach($leaders as $leader)
                        <option value="{{ $leader->id }}">{{ $leader->name }} (Role: {{ $leader->role }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Foreman</label>
                <select name="foreman_user_id" id="form_foreman_user_id" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition bg-white">
                    <option value="">-- Select Foreman (Optional) --</option>
                    @foreach($foremen as $foreman)
                        <option value="{{ $foreman->id }}">{{ $foreman->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Supervisor</label>
                <select name="supervisor_user_id" id="form_supervisor_user_id" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition bg-white">
                    <option value="">-- Select Supervisor (Optional) --</option>
                    @foreach($supervisors as $supervisor)
                        <option value="{{ $supervisor->id }}">{{ $supervisor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeAssignmentModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-red-600 text-white rounded-xl hover:bg-red-700 transition shadow-sm">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

{{-- DELETE CONFIRMATION MODAL --}}
<div id="deleteAssignmentModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeDeleteAssignmentModal()"></div>
    <div class="relative bg-white w-full max-w-sm mx-4 rounded-2xl shadow-2xl p-6 text-center">
        <h2 class="text-lg font-bold text-gray-800 mb-2">Delete Assignment?</h2>
        <p class="text-sm text-gray-500 mb-2" id="deleteAssignmentDetails"></p>
        <p class="text-sm text-red-500 mb-6">This will remove the assigned personnel for this shift.</p>
        <form id="deleteAssignmentForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex gap-3 justify-center">
                <button type="button" onclick="closeDeleteAssignmentModal()" class="flex-1 px-4 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 text-sm font-medium bg-red-600 text-white rounded-xl hover:bg-red-700 transition shadow-sm">Delete</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.openAddAssignmentModal = function() {
    document.getElementById('modalTitle').textContent = 'Add Assignment';
    document.getElementById('form_line_name').value = document.getElementById('form_line_name').options[0].value;
    document.getElementById('form_shift_name').value = document.getElementById('form_shift_name').options[0].value;
    document.getElementById('form_leader_user_id').value = '';
    document.getElementById('form_foreman_user_id').value = '';
    document.getElementById('form_supervisor_user_id').value = '';
    
    document.getElementById('assignmentModal').classList.remove('hidden');
    document.getElementById('assignmentModal').classList.add('flex');
};

window.openEditAssignmentModal = function(line, shift, leaderId, foremanId, supervisorId) {
    document.getElementById('modalTitle').textContent = 'Edit Assignment';
    document.getElementById('form_line_name').value = line;
    document.getElementById('form_shift_name').value = shift;
    document.getElementById('form_leader_user_id').value = leaderId || '';
    document.getElementById('form_foreman_user_id').value = foremanId || '';
    document.getElementById('form_supervisor_user_id').value = supervisorId || '';
    
    document.getElementById('assignmentModal').classList.remove('hidden');
    document.getElementById('assignmentModal').classList.add('flex');
};

window.closeAssignmentModal = function() {
    document.getElementById('assignmentModal').classList.add('hidden');
    document.getElementById('assignmentModal').classList.remove('flex');
};

window.openDeleteAssignmentModal = function(id, line, shift) {
    document.getElementById('deleteAssignmentDetails').textContent = line + ' (' + shift + ')';
    document.getElementById('deleteAssignmentForm').action = '/super-admin/assignments/' + id;
    
    document.getElementById('deleteAssignmentModal').classList.remove('hidden');
    document.getElementById('deleteAssignmentModal').classList.add('flex');
};

window.closeDeleteAssignmentModal = function() {
    document.getElementById('deleteAssignmentModal').classList.add('hidden');
    document.getElementById('deleteAssignmentModal').classList.remove('flex');
};
</script>
@endpush

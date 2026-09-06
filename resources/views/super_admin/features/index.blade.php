@extends('layouts.super_admin')

@section('title', 'Feature Permissions')

@section('content')
<div class="p-3 sm:p-4 md:p-6">

    @if(session('success'))
    <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center gap-3 shadow-sm">
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
    @endif

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
        <div>
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800">Permission Matrix</h1>
            <p class="text-gray-500 text-xs sm:text-sm">Manage fine-grained access to features by Jabatan and Section.</p>
        </div>
        <button type="button" onclick="openAddModal()" class="inline-flex items-center gap-2 bg-yellow-500 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-yellow-600 transition shadow-sm w-full sm:w-auto justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add Permission
        </button>
    </div>

    <form method="POST" action="{{ route('access-management.features.update') }}">
        @csrf
        
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs md:text-sm">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr class="text-left border-b border-gray-200">
                            <th class="px-5 py-3 font-semibold text-gray-600">Feature</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Jabatan</th>
                            <th class="px-5 py-3 font-semibold text-gray-600">Section</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">View</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Create</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Edit</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Delete</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Approve</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Export</th>
                            <th class="px-5 py-3 font-semibold text-gray-600 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($matrices as $featureId => $group)
                            @foreach($group as $matrix)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-5 py-3 font-medium text-gray-800">{{ $matrix->feature->feature_name }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $matrix->position ? $matrix->position->position_name : 'All' }}</td>
                                <td class="px-5 py-3 text-gray-600">{{ $matrix->section ? $matrix->section->section_name : 'All' }}</td>
                                
                                <td class="px-5 py-3 text-center">
                                    <input type="checkbox" name="matrices[{{ $matrix->id }}][can_view]" value="1" {{ $matrix->can_view ? 'checked' : '' }} class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <input type="checkbox" name="matrices[{{ $matrix->id }}][can_create]" value="1" {{ $matrix->can_create ? 'checked' : '' }} class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <input type="checkbox" name="matrices[{{ $matrix->id }}][can_edit]" value="1" {{ $matrix->can_edit ? 'checked' : '' }} class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <input type="checkbox" name="matrices[{{ $matrix->id }}][can_delete]" value="1" {{ $matrix->can_delete ? 'checked' : '' }} class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <input type="checkbox" name="matrices[{{ $matrix->id }}][can_approve]" value="1" {{ $matrix->can_approve ? 'checked' : '' }} class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <input type="checkbox" name="matrices[{{ $matrix->id }}][can_export]" value="1" {{ $matrix->can_export ? 'checked' : '' }} class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
                                </td>
                                
                                <td class="px-5 py-3 text-center">
                                    <button type="button" onclick="deleteMatrix({{ $matrix->id }})" class="text-red-500 hover:text-red-700 transition">
                                        <svg class="w-5 h-5 inline-block" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="10" class="px-5 py-12 text-center text-gray-400">No permission matrix records found. Add one above.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="p-4 border-t border-gray-100 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition shadow-sm">
                    Save Changes
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Add Modal --}}
<div id="addModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeAddModal()"></div>
    <div class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-2xl p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4">Add Permission</h2>
        <form method="POST" action="{{ route('access-management.features.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-700">Feature</label>
                <select name="new_feature_id" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200">
                    <option value="">- Select Feature -</option>
                    @foreach($features as $f)
                        <option value="{{ $f->id }}">{{ $f->feature_name }} ({{ $f->group_name }})</option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex gap-4">
                <div class="flex-1">
                    <label class="text-sm font-medium text-gray-700">Jabatan (Optional)</label>
                    <select name="new_position_id" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200">
                        <option value="">- All -</option>
                        @foreach($positions as $p)
                            <option value="{{ $p->id }}">{{ $p->position_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex-1">
                    <label class="text-sm font-medium text-gray-700">Section (Optional)</label>
                    <select name="new_section_id" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200">
                        <option value="">- All -</option>
                        @foreach($sections as $s)
                            <option value="{{ $s->id }}">{{ $s->section_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700 block mb-2">Permissions</label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="new_can_view" value="1" class="rounded text-red-600" checked> View</label>
                    <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="new_can_create" value="1" class="rounded text-red-600"> Create</label>
                    <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="new_can_edit" value="1" class="rounded text-red-600"> Edit</label>
                    <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="new_can_delete" value="1" class="rounded text-red-600"> Delete</label>
                    <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="new_can_approve" value="1" class="rounded text-red-600"> Approve</label>
                    <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="new_can_export" value="1" class="rounded text-red-600"> Export</label>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <button type="button" onclick="closeAddModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-red-600 text-white rounded-xl hover:bg-red-700">Add Assignment</button>
            </div>
        </form>
    </div>
</div>

{{-- Hidden Delete Form --}}
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
    function openAddModal() {
        document.getElementById('addModal').classList.remove('hidden');
        document.getElementById('addModal').classList.add('flex');
    }
    function closeAddModal() {
        document.getElementById('addModal').classList.add('hidden');
        document.getElementById('addModal').classList.remove('flex');
    }
    function deleteMatrix(id) {
        if (confirm('Delete this permission assignment?')) {
            const form = document.getElementById('deleteForm');
            form.action = '/access-management/features/matrix/' + id;
            form.submit();
        }
    }
</script>
@endpush
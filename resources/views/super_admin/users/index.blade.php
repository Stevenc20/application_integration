@extends('layouts.super_admin')

@section('title', 'Manage Users')

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
            <h1 class="text-lg sm:text-xl md:text-2xl font-bold text-gray-800">User Management</h1>
            <p class="text-gray-500 text-xs sm:text-sm">Manage all system users including super admins</p>
        </div>
        <button onclick="openSuperAdminAddModal()"
            class="inline-flex items-center gap-2 bg-yellow-500 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-yellow-600 transition shadow-sm w-full sm:w-auto justify-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Add User
        </button>
    </div>

    <div class="bg-white border border-gray-200 shadow-sm rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs md:text-sm">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr class="text-left">
                        <th class="px-5 py-3 font-semibold text-gray-600">#</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Name</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">NRP</th>
                        <th class="px-5 py-3 font-semibold text-gray-600">Role</th>
                        <th class="px-5 py-3 font-semibold text-gray-600 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $index => $user)
                    <tr class="hover:bg-gray-50/60 transition">
                        <td class="px-5 py-3 text-gray-400 font-mono text-xs">{{ $users->firstItem() + $index }}</td>
                        <td class="px-5 py-3 font-medium text-gray-800 whitespace-nowrap">{{ $user->name }}</td>
                        <td class="px-5 py-3 text-gray-600 font-mono">{{ $user->nrp ?? '-' }}</td>
                        <td class="px-5 py-3 capitalize">
                            @if($user->role === 'superadmin')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-800">Super Admin</span>
                            @else
                                {{ $user->role }}
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex gap-2 justify-center">
                                <button type="button"
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}"
                                    data-user-nrp="{{ $user->nrp }}"
                                    data-user-role="{{ $user->role }}"
                                    onclick="openSuperAdminEditModal(this); return false;"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">Edit</button>
                                <button type="button"
                                    data-user-id="{{ $user->id }}"
                                    data-user-name="{{ $user->name }}"
                                    onclick="openSuperAdminDeleteModal(this)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition">Delete</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-gray-400">No users found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
        <div class="p-4 border-t border-gray-100">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

{{-- ADD MODAL --}}
<div id="userModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSuperAdminAddModal()"></div>
    <div class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-2xl p-6 transform scale-100">
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-lg font-bold text-gray-800">Add New User</h2>
            <button onclick="closeSuperAdminAddModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('super-admin.users.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">NRP</label>
                <input type="text" name="nrp" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Role</label>
                <select name="role" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
                    <option value="">Select role</option>
                    <option value="superadmin">Super Admin</option>
                    <option value="admin">Admin</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="ppc">PPC</option>
                    <option value="foreman">Foreman</option>
                    <option value="operator">Operator</option>
                    <option value="leader">Leader</option>
                    <option value="leader a">Leader A</option>
                    <option value="leader b">Leader B</option>
                    <option value="leader c">Leader C</option>
                    <option value="leader d">Leader D</option>
                    <option value="shearing">Shearing</option>
                    <option value="handwork">Handwork</option>
                    <option value="quality">Quality</option>
                    <option value="production">Production</option>
                    <option value="manager">Manager</option>
                    <option value="kadiv">Kadiv</option>
                    <option value="direktur">Direktur</option>
                    <option value="presdir">Presdir</option>
                    <option value="hambatan">Hambatan</option>
                    <option value="dies_shop">Dies Shop</option>
                    <option value="plant_service">Plant Service</option>
                    <option value="irm">IRM</option>
                    <option value="logistik">Logistik</option>
                    <option value="produksi">Produksi</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeSuperAdminAddModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-yellow-500 text-white rounded-xl hover:bg-yellow-600 transition shadow-sm">Save User</button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT MODAL --}}
<div id="editModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSuperAdminEditModal()"></div>
    <div class="relative bg-white w-full max-w-lg mx-4 rounded-2xl shadow-2xl p-6">
        <div class="flex justify-between items-center mb-5">
            <h2 class="text-lg font-bold text-gray-800">Edit User</h2>
            <button onclick="closeSuperAdminEditModal()" class="w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-400 hover:text-gray-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="editForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" id="edit_name" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">NRP</label>
                <input type="text" name="nrp" id="edit_nrp" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Password <span class="text-gray-400 font-normal">(optional)</span></label>
                <input type="password" name="password" class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition" placeholder="Leave blank to keep current">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-700">Role</label>
                <select name="role" id="edit_role" required class="w-full mt-1.5 px-4 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-red-200 focus:border-red-400 outline-none transition">
                    <option value="superadmin">Super Admin</option>
                    <option value="admin">Admin</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="ppc">PPC</option>
                    <option value="foreman">Foreman</option>
                    <option value="operator">Operator</option>
                    <option value="leader">Leader</option>
                    <option value="leader a">Leader A</option>
                    <option value="leader b">Leader B</option>
                    <option value="leader c">Leader C</option>
                    <option value="leader d">Leader D</option>
                    <option value="shearing">Shearing</option>
                    <option value="handwork">Handwork</option>
                    <option value="quality">Quality</option>
                    <option value="production">Production</option>
                    <option value="manager">Manager</option>
                    <option value="kadiv">Kadiv</option>
                    <option value="direktur">Direktur</option>
                    <option value="presdir">Presdir</option>
                    <option value="hambatan">Hambatan</option>
                    <option value="dies_shop">Dies Shop</option>
                    <option value="plant_service">Plant Service</option>
                    <option value="irm">IRM</option>
                    <option value="logistik">Logistik</option>
                    <option value="produksi">Produksi</option>
                </select>
            </div>
            <div class="flex justify-end gap-2 pt-3 border-t border-gray-100">
                <button type="button" onclick="closeSuperAdminEditModal()" class="px-5 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-5 py-2.5 text-sm font-medium bg-yellow-500 text-white rounded-xl hover:bg-yellow-600 transition shadow-sm">Update User</button>
            </div>
        </form>
    </div>
</div>

{{-- DELETE MODAL --}}
<div id="deleteModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSuperAdminDeleteModal()"></div>
    <div class="relative bg-white w-full max-w-sm mx-4 rounded-2xl shadow-2xl p-6 text-center">
        <h2 class="text-lg font-bold text-gray-800 mb-2">Delete User?</h2>
        <p class="text-sm text-gray-500 mb-2" id="deleteUserName"></p>
        <p class="text-sm text-red-500 mb-6">This action cannot be undone.</p>
        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')
            <div class="flex gap-3 justify-center">
                <button type="button" onclick="closeSuperAdminDeleteModal()" class="flex-1 px-4 py-2.5 text-sm font-medium bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="flex-1 px-4 py-2.5 text-sm font-medium bg-red-600 text-white rounded-xl hover:bg-red-700 transition shadow-sm">Delete</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    console.log('SuperAdmin users page loaded');
});

window.openSuperAdminAddModal = function () {
    document.getElementById('userModal').classList.remove('hidden');
    document.getElementById('userModal').classList.add('flex');
};
window.closeSuperAdminAddModal = function () {
    document.getElementById('userModal').classList.add('hidden');
    document.getElementById('userModal').classList.remove('flex');
};

window.openSuperAdminEditModal = function (btn) {
    var name = btn.getAttribute('data-user-name');
    var nrp = btn.getAttribute('data-user-nrp');
    var role = btn.getAttribute('data-user-role');
    var id = btn.getAttribute('data-user-id');

    document.getElementById('edit_name').value = name;
    document.getElementById('edit_nrp').value = nrp || '';
    document.getElementById('edit_role').value = role;

    var form = document.getElementById('editForm');
    form.action = '/super-admin/users/' + id;

    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
};
window.closeSuperAdminEditModal = function () {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
};

window.openSuperAdminDeleteModal = function (btn) {
    var name = btn.getAttribute('data-user-name');
    var id = btn.getAttribute('data-user-id');
    document.getElementById('deleteUserName').textContent = 'Hapus user "' + name + '"?';
    var form = document.getElementById('deleteForm');
    form.action = '/super-admin/users/' + id;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
};
window.closeSuperAdminDeleteModal = function () {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
};
</script>
@endpush

@extends('layouts.layouts')

 @vite(['resources/css/app.css', 'resources/js/app.js'])

@section('content')
<div class="p-4 sm:p-6">

     @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg">
            <ul>
                @foreach($errors->all() as $error)
                    <li>- {{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">

        <div>
            <h1 class="text-xl sm:text-2xl font-bold">User List</h1>
            <p class="text-gray-500 text-sm">
                {{ now()->format('d F Y') }}
            </p>
        </div>

     <button onclick="openModal()"
        class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 transition w-full sm:w-auto">
        + Add User
    </button>

    </div>

    @if(session('success'))
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    {{-- CARD --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">

                {{-- HEADER --}}
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">NIP</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-center">Action</th>
                    </tr>
                </thead>

                {{-- BODY --}}
                <tbody>

                    @forelse ($users as $user)
                    <tr class="border-b hover:bg-gray-50 transition">

                        {{-- NAME --}}
                        <td class="px-4 py-3 font-medium whitespace-nowrap">
                            {{ $user->name }}
                        </td>

                        {{-- NIP --}}
                        <td class="px-4 py-3 whitespace-nowrap">
                            {{ $user->nip ?? '-' }}
                        </td>

                        {{-- ROLE --}}
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded text-xs
                                @if($user->role == 'admin') bg-red-100 text-red-600
                                @elseif($user->role == 'supervisor') bg-blue-100 text-blue-600
                                @else bg-green-100 text-green-600
                                @endif">
                                {{ ucfirst($user->role) }}
                            </span>
                        </td>

                        {{-- ACTION --}}
                        <td class="px-4 py-3">
                            <div class="flex flex-col sm:flex-row gap-2 justify-center">

                                {{-- EDIT --}}
                               <button onclick="openEditModal(
                                '{{ $user->id }}',
                                '{{ $user->name }}',
                                '{{ $user->nip }}',
                                '{{ $user->role }}'
                                    )"
                                    class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">
                                    Edit
                             </button>

                                {{-- DELETE --}}
                                    @csrf
                                    @method('DELETE')

                                   <button onclick="openDeleteModal('{{ $user->id }}')"
                                        class="px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600">
                                        Delete
                                    </button>
                               

                            </div>
                        </td>

                    </tr>
                    @empty

                    {{-- NO DATA --}}
                    <tr>
                        <td colspan="4" class="text-center py-8 text-gray-500">
                            No data available
                        </td>
                    </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

        {{-- PAGINATION --}}
        @if($users->hasPages())
        <div class="p-4 border-t">
            {{ $users->links() }}
        </div>
        @endif

    </div>

</div>
@endsection

{{-- MODAL ADD USER --}}
<div id="userModal"
class="fixed inset-0 z-50 hidden items-center justify-center">

    {{-- BACKDROP --}}
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm opacity-0 transition-opacity duration-300"
         id="modalBackdrop"></div>

    {{-- MODAL BOX --}}
    <div id="modalBox"
         class="relative bg-white w-full max-w-lg mx-4 rounded-xl shadow-lg p-6
         transform scale-95 opacity-0 transition-all duration-300">

        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold">Add User</h2>
            <button onclick="closeModal()" class="text-gray-500 text-xl">&times;</button>
        </div>

        <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="text-sm font-medium">Name</label>
                <input type="text" name="name"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-red-200"
                    required>
            </div>

            <div>
                <label class="text-sm font-medium">NIP</label>
                <input type="text" name="nip"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-red-200"
                    required>
            </div>

            <div>
                <label class="text-sm font-medium">Password</label>
                <input type="password" name="password"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-red-200"
                    required>
            </div>

            <div>
                <label class="text-sm font-medium">Role</label>
                <select name="role"
                    class="w-full mt-1 px-3 py-2 border rounded-lg focus:ring focus:ring-red-200"
                    required>
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="operator">Operator</option>
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal()"
                    class="px-4 py-2 text-sm bg-gray-200 rounded-lg">
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

<div id="editModal" class="fixed inset-0 z-50 hidden items-center justify-center">

    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeEditModal()"></div>

    <div class="bg-white w-full max-w-lg mx-4 rounded-xl shadow-lg p-6 relative">

        <h2 class="text-lg font-semibold mb-4">Edit User</h2>

        <form id="editForm" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <input type="hidden" id="edit_id">

            <div>
                <label>Name</label>
                <input type="text" name="name" id="edit_name"
                    class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label>NIP</label>
                <input type="text" name="nip" id="edit_nip"
                    class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label>Password (optional)</label>
                <input type="password" name="password"
                    class="w-full border rounded px-3 py-2">
            </div>

            <div>
                <label>Role</label>
                <select name="role" id="edit_role"
                    class="w-full border rounded px-3 py-2">
                    <option value="admin">Admin</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="operator">Operator</option>
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">
                    Update
                </button>
            </div>

        </form>

    </div>
</div>

<div id="deleteModal" class="fixed inset-0 z-[9999] hidden items-center justify-center">

    {{-- BACKDROP --}}
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"
         onclick="closeDeleteModal()"></div>

    {{-- BOX --}}
    <div class="relative bg-white w-full max-w-md mx-4 rounded-xl shadow-lg p-6 text-center">

        <h2 class="text-lg font-semibold mb-3">Konfirmasi Hapus</h2>

        <p class="text-gray-600 mb-6">
            Apakah anda yakin ingin menghapus user ini?
        </p>

        <form id="deleteForm" method="POST">
            @csrf
            @method('DELETE')

            <div class="flex justify-center gap-2">
                <button type="button"
                        onclick="closeDeleteModal()"
                        class="px-4 py-2 bg-gray-200 rounded">
                    Cancel
                </button>

                <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Delete
                </button>
            </div>
        </form>

    </div>
</div>
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Document Type Permissions</h1>
        <p class="text-sm text-gray-600 mt-1">Manage which roles can access each document type</p>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Create Document Type</h2>
        <form method="POST" action="{{ route('admin.document-types.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Code</label>
                    <input type="text" name="code" value="{{ old('code') }}" required class="mt-1 w-full rounded border-gray-300">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <select name="category" required class="mt-1 w-full rounded border-gray-300">
                        <option value="case_creation" @selected(old('category') === 'case_creation')>Case Creation</option>
                        <option value="party_upload" @selected(old('category') === 'party_upload')>Party Upload</option>
                        <option value="system" @selected(old('category') === 'system')>System</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', 0) }}" class="mt-1 w-full rounded border-gray-300">
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-2">
                <label class="flex items-center">
                    <input type="checkbox" name="is_required" value="1" class="mr-2" @checked(old('is_required'))>
                    <span class="text-sm text-gray-700">Required</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="is_pleading" value="1" class="mr-2" @checked(old('is_pleading'))>
                    <span class="text-sm text-gray-700">Pleading</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="allows_multiple" value="1" class="mr-2" @checked(old('allows_multiple'))>
                    <span class="text-sm text-gray-700">Allows Multiple</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" class="mr-2" @checked(old('is_active', true))>
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>

            <div class="mt-4">
                <div class="text-sm font-medium text-gray-700 mb-2">Allowed Roles</div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    @foreach($roles as $role)
                        <label class="flex items-center p-2 rounded border border-gray-200">
                            <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="mr-2">
                            <span class="text-sm text-gray-700">{{ $role->display_name }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Create
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Document Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Allowed Roles</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($documentTypes as $docType)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $docType->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $docType->code }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                            {{ $docType->category }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @forelse($docType->roles as $role)
                                <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">
                                    {{ $role->display_name }}
                                </span>
                            @empty
                                <span class="text-xs text-gray-400">No roles assigned</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button onclick="editRoles({{ $docType->id }})" class="text-blue-600 hover:text-blue-800">
                            Edit Roles
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Roles Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="w-full max-w-lg p-5 border shadow-lg rounded-md bg-white">
        <div class="mb-4">
            <h3 class="text-lg font-medium text-gray-900">Edit Allowed Roles</h3>
            <p class="text-sm text-gray-500 mt-1" id="modalDocTypeName"></p>
        </div>
        
        <form id="rolesForm" onsubmit="saveRoles(event)">
            <input type="hidden" id="docTypeId">
            <div class="space-y-2 mb-4">
                @foreach($roles as $role)
                <label class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer">
                    <input type="checkbox" name="role_ids[]" value="{{ $role->id }}" class="mr-3 h-4 w-4 text-blue-600 rounded">
                    <div>
                        <div class="text-sm font-medium text-gray-900">{{ $role->display_name }}</div>
                        <div class="text-xs text-gray-500">{{ $role->name }} @if($role->group)({{ $role->group }})@endif</div>
                    </div>
                </label>
                @endforeach
            </div>
            
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const documentTypes = @json($documentTypes);

function editRoles(docTypeId) {
    const docType = documentTypes.find(dt => dt.id === docTypeId);
    document.getElementById('docTypeId').value = docTypeId;
    document.getElementById('modalDocTypeName').textContent = docType.name;
    
    // Uncheck all checkboxes first
    document.querySelectorAll('input[name="role_ids[]"]').forEach(cb => cb.checked = false);
    
    // Check the roles assigned to this document type
    docType.roles.forEach(role => {
        const checkbox = document.querySelector(`input[name="role_ids[]"][value="${role.id}"]`);
        if (checkbox) checkbox.checked = true;
    });
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
}

async function saveRoles(event) {
    event.preventDefault();
    
    const docTypeId = document.getElementById('docTypeId').value;
    const formData = new FormData(event.target);
    const roleIds = formData.getAll('role_ids[]');
    
    try {
        const response = await fetch(`/admin/document-types/${docTypeId}/roles`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ role_ids: roleIds })
        });
        
        if (response.ok) {
            window.location.reload();
        } else {
            alert('Failed to update roles');
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}
</script>
@endsection

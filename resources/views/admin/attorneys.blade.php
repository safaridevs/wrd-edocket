@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Manage Attorneys</h1>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bar Number</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($attorneys as $attorney)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $attorney->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $attorney->email }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $attorney->phone }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $attorney->bar_number }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button onclick="editAttorney({{ $attorney->id }})" class="text-blue-600 hover:text-blue-800 mr-3">Edit</button>
                        <form action="{{ route('admin.attorneys.destroy', $attorney) }}" method="POST" class="inline" onsubmit="return confirm('Delete this attorney?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $attorneys->links() }}
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <h3 class="text-lg font-medium mb-4">Edit Attorney</h3>
        
        <form id="editForm" onsubmit="saveAttorney(event)">
            <input type="hidden" id="attorneyId">
            
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input type="text" id="name" required class="w-full border-gray-300 rounded-md">
            </div>
            
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" id="email" required class="w-full border-gray-300 rounded-md">
            </div>
            
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" id="phone" class="w-full border-gray-300 rounded-md">
            </div>
            
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Bar Number</label>
                <input type="text" id="bar_number" class="w-full border-gray-300 rounded-md">
            </div>
            
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 1</label>
                <input type="text" id="address_line1" class="w-full border-gray-300 rounded-md">
            </div>
            
            <div class="mb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                <input type="text" id="address_line2" class="w-full border-gray-300 rounded-md">
            </div>
            
            <div class="grid grid-cols-3 gap-2 mb-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" id="city" class="w-full border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <input type="text" id="state" class="w-full border-gray-300 rounded-md">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Zip</label>
                    <input type="text" id="zip" class="w-full border-gray-300 rounded-md">
                </div>
            </div>
            
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
async function editAttorney(id) {
    const response = await fetch(`/admin/attorneys/${id}/edit`);
    const attorney = await response.json();
    
    document.getElementById('attorneyId').value = attorney.id;
    document.getElementById('name').value = attorney.name;
    document.getElementById('email').value = attorney.email;
    document.getElementById('phone').value = attorney.phone || '';
    document.getElementById('bar_number').value = attorney.bar_number || '';
    document.getElementById('address_line1').value = attorney.address_line1 || '';
    document.getElementById('address_line2').value = attorney.address_line2 || '';
    document.getElementById('city').value = attorney.city || '';
    document.getElementById('state').value = attorney.state || '';
    document.getElementById('zip').value = attorney.zip || '';
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
}

async function saveAttorney(event) {
    event.preventDefault();
    
    const id = document.getElementById('attorneyId').value;
    const data = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        phone: document.getElementById('phone').value,
        bar_number: document.getElementById('bar_number').value,
        address_line1: document.getElementById('address_line1').value,
        address_line2: document.getElementById('address_line2').value,
        city: document.getElementById('city').value,
        state: document.getElementById('state').value,
        zip: document.getElementById('zip').value
    };
    
    const response = await fetch(`/admin/attorneys/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    });
    
    if (response.ok) {
        window.location.reload();
    } else {
        alert('Failed to update attorney');
    }
}
</script>
@endsection

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            User Management
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if(session('success'))
                        <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-6 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Accounts</h3>
                            <p class="mt-1 text-sm text-gray-600">Create staff and contractor accounts before they are assigned to a case.</p>
                        </div>
                        <button type="button" onclick="showCreateUserModal()" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                            Create User
                        </button>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $user->getDisplayName() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ ucwords(str_replace('_', ' ', $user->getCurrentRole())) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($user->is_active)
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Pending</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="editUser({{ $user->id }})" class="text-blue-600 hover:text-blue-900">Edit</button>

                                        @if(!$user->is_active)
                                            <form method="POST" action="{{ route('admin.users.approve', $user) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-900">Approve</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.users.deactivate', $user) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900" onclick="return confirm('Deactivate this user?')">Deactivate</button>
                                            </form>
                                        @endif

                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete this user? This action cannot be undone.')">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>

                    <!-- User count -->
                    <div class="mt-2 text-sm text-gray-600">
                        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} users
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="createUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Create User</h3>
                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" name="name" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" name="title" value="{{ old('title') }}" class="mt-1 block w-full border-gray-300 rounded-md" placeholder="e.g., ALU Attorney">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Role</label>
                                <select name="role" id="createUserRole" class="mt-1 block w-full border-gray-300 rounded-md" required onchange="toggleCreateUserProfileFields()">
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}" @selected(old('role') === $role->name)>{{ $role->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Sign-in Method</label>
                                <select name="account_type" id="createAccountType" class="mt-1 block w-full border-gray-300 rounded-md" required onchange="toggleCreateUserProfileFields()">
                                    <option value="ldap" @selected(old('account_type', 'ldap') === 'ldap')>OSE Attorney</option>
                                    <option value="local" @selected(old('account_type') === 'local')>Private Attorney</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">OSE Attorneys sign in with network credentials. Private Attorneys sign in with the password set here.</p>
                            </div>
                            <div id="createPasswordFields" class="space-y-4 hidden">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Temporary Password</label>
                                    <input type="password" name="password" id="createPassword" class="mt-1 block w-full border-gray-300 rounded-md" autocomplete="new-password">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                    <input type="password" name="password_confirmation" id="createPasswordConfirmation" class="mt-1 block w-full border-gray-300 rounded-md" autocomplete="new-password">
                                </div>
                            </div>
                            <div id="privateAttorneyProfileFields" class="hidden rounded-lg border border-indigo-100 bg-indigo-50 p-4">
                                <div class="mb-4">
                                    <h4 class="text-sm font-semibold text-indigo-950">Private Attorney Profile</h4>
                                    <p class="mt-1 text-xs text-indigo-800">This creates the matching person/contact record used later for counsel, clients, and service lists.</p>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">First Name</label>
                                        <input type="text" name="first_name" value="{{ old('first_name') }}" class="mt-1 block w-full border-gray-300 rounded-md" data-private-attorney-required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Middle Name</label>
                                        <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                        <input type="text" name="last_name" value="{{ old('last_name') }}" class="mt-1 block w-full border-gray-300 rounded-md" data-private-attorney-required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Organization / Firm</label>
                                        <input type="text" name="organization" value="{{ old('organization') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Office Phone</label>
                                        <input type="text" name="phone_office" value="{{ old('phone_office') }}" class="mt-1 block w-full border-gray-300 rounded-md" data-private-attorney-phone>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Mobile Phone</label>
                                        <input type="text" name="phone_mobile" value="{{ old('phone_mobile') }}" class="mt-1 block w-full border-gray-300 rounded-md" data-private-attorney-phone>
                                        <p class="mt-1 text-xs text-gray-500">Enter at least one phone number.</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Address Line 1</label>
                                        <input type="text" name="address_line1" value="{{ old('address_line1') }}" class="mt-1 block w-full border-gray-300 rounded-md" data-private-attorney-required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Address Line 2</label>
                                        <input type="text" name="address_line2" value="{{ old('address_line2') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">City</label>
                                        <input type="text" name="city" value="{{ old('city') }}" class="mt-1 block w-full border-gray-300 rounded-md" data-private-attorney-required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">State</label>
                                        <input type="text" name="state" value="{{ old('state') }}" class="mt-1 block w-full border-gray-300 rounded-md" data-private-attorney-required>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">ZIP</label>
                                        <input type="text" name="zip" value="{{ old('zip') }}" class="mt-1 block w-full border-gray-300 rounded-md" data-private-attorney-required>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                                        <textarea name="notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md">{{ old('notes') }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-blue-600" @checked(old('is_active', true))>
                                Active account
                            </label>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="hideCreateUserModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Edit User</h3>
                    <form id="editUserForm" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" name="name" id="editName" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Title</label>
                                <input type="text" name="title" id="editTitle" class="mt-1 block w-full border-gray-300 rounded-md" placeholder="e.g., WRAP Director">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" id="editEmail" class="mt-1 block w-full border-gray-300 rounded-md" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Role</label>
                                <select name="role" id="editRole" class="mt-1 block w-full border-gray-300 rounded-md" required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->name }}">{{ $role->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="hideEditModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Update User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showCreateUserModal() {
            document.getElementById('createUserModal').classList.remove('hidden');
            toggleCreateUserProfileFields();
        }

        function hideCreateUserModal() {
            document.getElementById('createUserModal').classList.add('hidden');
        }

        function toggleCreateUserProfileFields() {
            const accountType = document.getElementById('createAccountType').value;
            const role = document.getElementById('createUserRole').value;
            const passwordFields = document.getElementById('createPasswordFields');
            const password = document.getElementById('createPassword');
            const confirmation = document.getElementById('createPasswordConfirmation');
            const privateAttorneyFields = document.getElementById('privateAttorneyProfileFields');
            const isLocal = accountType === 'local';
            const needsPrivateAttorneyProfile = isLocal || role === 'external_attorney';

            passwordFields.classList.toggle('hidden', !isLocal);
            password.required = isLocal;
            confirmation.required = isLocal;
            privateAttorneyFields.classList.toggle('hidden', !needsPrivateAttorneyProfile);

            document.querySelectorAll('[data-private-attorney-required]').forEach((input) => {
                input.required = needsPrivateAttorneyProfile;
            });

            document.querySelectorAll('[data-private-attorney-phone]').forEach((input) => {
                input.required = false;
            });
        }

        function editUser(userId) {
            fetch(`/admin/users/${userId}/edit`)
                .then(response => response.json())
                .then(user => {
                    document.getElementById('editName').value = user.name;
                    document.getElementById('editTitle').value = user.title || '';
                    document.getElementById('editEmail').value = user.email;
                    document.getElementById('editRole').value = user.role;
                    document.getElementById('editUserForm').action = `/admin/users/${userId}`;
                    document.getElementById('editUserModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load user data');
                });
        }

        function hideEditModal() {
            document.getElementById('editUserModal').classList.add('hidden');
        }

        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to update user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update user');
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            toggleCreateUserProfileFields();
            @if($errors->any())
                showCreateUserModal();
            @endif
        });
    </script>
</x-app-layout>

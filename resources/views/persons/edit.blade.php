<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Person</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">
                @if (session('success'))
                    <div class="mb-4 bg-green-50 border border-green-200 rounded-md p-4">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('cases.persons.update', [$case, $person]) }}">
                    @csrf
                    @method('PUT')
                    
                    <!-- Person Type -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="type" value="individual" {{ $person->type === 'individual' ? 'checked' : '' }} class="mr-2">
                                Individual
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="type" value="company" {{ $person->type === 'company' ? 'checked' : '' }} class="mr-2">
                                Company
                            </label>
                        </div>
                    </div>

                    <!-- Individual Fields -->
                    <div id="individual-fields" class="{{ $person->type === 'individual' ? '' : 'hidden' }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name</label>
                                <input type="text" name="first_name" value="{{ $person->first_name }}" class="mt-1 block w-full border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name</label>
                                <input type="text" name="last_name" value="{{ $person->last_name }}" class="mt-1 block w-full border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>

                    <!-- Company Fields -->
                    <div id="company-fields" class="{{ $person->type === 'company' ? '' : 'hidden' }}">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Organization</label>
                            <input type="text" name="organization" value="{{ $person->organization }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Common Fields -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" name="title" value="{{ $person->title }}" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700">Email *</label>
                        <input type="email" name="email" value="{{ $person->email }}" required class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mobile Phone</label>
                            <input type="text" name="phone_mobile" value="{{ $person->phone_mobile }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Office Phone</label>
                            <input type="text" name="phone_office" value="{{ $person->phone_office }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Address -->
                    <h3 class="text-lg font-medium mb-4">Address</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Address Line 1</label>
                        <input type="text" name="address_line1" value="{{ $person->address_line1 }}" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Address Line 2</label>
                        <input type="text" name="address_line2" value="{{ $person->address_line2 }}" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">City</label>
                            <input type="text" name="city" value="{{ $person->city }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">State</label>
                            <input type="text" name="state" value="{{ $person->state }}" maxlength="2" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">ZIP Code</label>
                            <input type="text" name="zip" value="{{ $person->zip }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-4">
                        <a href="javascript:history.back()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</a>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Update Person</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('input[name="type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const individualFields = document.getElementById('individual-fields');
                const companyFields = document.getElementById('company-fields');
                
                if (this.value === 'individual') {
                    individualFields.classList.remove('hidden');
                    companyFields.classList.add('hidden');
                } else {
                    individualFields.classList.add('hidden');
                    companyFields.classList.remove('hidden');
                }
            });
        });
    </script>
</x-app-layout>
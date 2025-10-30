<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Contact Information') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('party.contact.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($person->type === 'individual')
                                <div>
                                    <label for="first_name" class="block text-sm font-medium text-gray-700">First Name</label>
                                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $person->first_name) }}" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>

                                <div>
                                    <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name</label>
                                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $person->last_name) }}" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            @else
                                <div class="md:col-span-2">
                                    <label for="organization" class="block text-sm font-medium text-gray-700">Organization</label>
                                    <input type="text" name="organization" id="organization" value="{{ old('organization', $person->organization) }}" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            @endif

                            <div>
                                <label for="phone_mobile" class="block text-sm font-medium text-gray-700">Mobile Phone</label>
                                <input type="text" name="phone_mobile" id="phone_mobile" value="{{ old('phone_mobile', $person->phone_mobile) }}" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div>
                                <label for="phone_office" class="block text-sm font-medium text-gray-700">Office Phone</label>
                                <input type="text" name="phone_office" id="phone_office" value="{{ old('phone_office', $person->phone_office) }}" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div class="md:col-span-2">
                                <label for="address_line1" class="block text-sm font-medium text-gray-700">Address Line 1</label>
                                <input type="text" name="address_line1" id="address_line1" value="{{ old('address_line1', $person->address_line1) }}" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div class="md:col-span-2">
                                <label for="address_line2" class="block text-sm font-medium text-gray-700">Address Line 2</label>
                                <input type="text" name="address_line2" id="address_line2" value="{{ old('address_line2', $person->address_line2) }}" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                                <input type="text" name="city" id="city" value="{{ old('city', $person->city) }}" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700">State</label>
                                <input type="text" name="state" id="state" value="{{ old('state', $person->state) }}" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" maxlength="2">
                            </div>

                            <div>
                                <label for="zip" class="block text-sm font-medium text-gray-700">ZIP Code</label>
                                <input type="text" name="zip" id="zip" value="{{ old('zip', $person->zip) }}" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" value="{{ $person->email }}" disabled
                                       class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                                <p class="mt-1 text-sm text-gray-500">Email cannot be changed</p>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <a href="{{ route('dashboard') }}" 
                               class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Update Contact Information
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
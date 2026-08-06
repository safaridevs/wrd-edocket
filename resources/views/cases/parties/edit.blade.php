@php
    $stateOptions = [
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    ];

    $hasAttorneyRepresentation = $party->attorneys->isNotEmpty();
@endphp

<form id="editPartyForm" onsubmit="updateParty(event, {{ $party->id }})">
    @csrf
    @method('PUT')
    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Role *</label>
                <select name="role" required class="mt-1 block w-full border-gray-300 rounded-md">
                    <option value="applicant" {{ $party->role === 'applicant' ? 'selected' : '' }} class="regular-role">Applicant</option>
                    <option value="protestant" {{ $party->role === 'protestant' ? 'selected' : '' }}>Protestant</option>
                    <option value="intervenor" {{ $party->role === 'intervenor' ? 'selected' : '' }}>Intervenor</option>
                    <option value="respondent" {{ $party->role === 'respondent' ? 'selected' : '' }} class="compliance-role" style="display: none;">Respondent</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Type *</label>
                <select name="type" required class="mt-1 block w-full border-gray-300 rounded-md bg-gray-100" onchange="toggleEditPartyType(this)" disabled>
                    <option value="individual" {{ $party->person->type === 'individual' ? 'selected' : '' }}>Individual</option>
                    <option value="company" {{ $party->person->type === 'company' ? 'selected' : '' }}>Entity (Non-Person)</option>
                </select>
            </div>
        </div>

        <div id="editIndividualFields" class="{{ $party->person->type === 'company' ? 'hidden' : '' }}">
            <div class="grid grid-cols-4 gap-2">
                <input type="text" name="prefix" placeholder="Prefix" value="{{ $party->person->prefix }}" class="border-gray-300 rounded-md text-sm">
                <input type="text" name="first_name" placeholder="First Name" value="{{ $party->person->first_name }}" class="border-gray-300 rounded-md text-sm">
                <input type="text" name="middle_name" placeholder="Middle" value="{{ $party->person->middle_name }}" class="border-gray-300 rounded-md text-sm">
                <input type="text" name="last_name" placeholder="Last Name" value="{{ $party->person->last_name }}" class="border-gray-300 rounded-md text-sm">
            </div>
            <div class="grid grid-cols-2 gap-2 mt-2">
                <input type="text" name="suffix" placeholder="Suffix" value="{{ $party->person->suffix }}" class="border-gray-300 rounded-md text-sm">
                <input type="text" name="title" placeholder="Title" value="{{ $party->person->title }}" class="border-gray-300 rounded-md text-sm">
            </div>
        </div>

        <div id="editCompanyFields" class="{{ $party->person->type === 'individual' ? 'hidden' : '' }}">
            <div class="grid grid-cols-2 gap-2">
                <input type="text" name="organization" placeholder="Organization" value="{{ $party->person->organization }}" class="border-gray-300 rounded-md">
                <input type="text" name="title" placeholder="Title" value="{{ $party->person->title }}" class="border-gray-300 rounded-md">
            </div>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <input type="email" name="email" placeholder="Email *" value="{{ $party->person->email }}" required class="border-gray-300 rounded-md">
            <input type="text" name="phone_mobile" placeholder="555-555-5555" value="{{ $party->person->phone_mobile }}" inputmode="tel" pattern="\d{3}-\d{3}-\d{4}" oninput="formatPhoneInput(this)" class="border-gray-300 rounded-md">
            <input type="text" name="phone_office" placeholder="555-555-5555" value="{{ $party->person->phone_office }}" inputmode="tel" pattern="\d{3}-\d{3}-\d{4}" oninput="formatPhoneInput(this)" class="border-gray-300 rounded-md">
        </div>

        <div>
            <h4 class="text-sm font-medium text-gray-700 mb-2">Address</h4>
            <div class="space-y-2">
                <input type="text" name="address_line1" placeholder="Address Line 1" value="{{ $party->person->address_line1 }}" class="block w-full border-gray-300 rounded-md">
                <input type="text" name="address_line2" placeholder="Address Line 2" value="{{ $party->person->address_line2 }}" class="block w-full border-gray-300 rounded-md">
                <div class="grid grid-cols-3 gap-2">
                    <input type="text" name="city" placeholder="City" value="{{ $party->person->city }}" class="border-gray-300 rounded-md">
                    <select name="state" class="border-gray-300 rounded-md">
                        @foreach($stateOptions as $code => $label)
                            <option value="{{ $code }}" {{ old('state', $party->person->state ?: 'NM') === $code ? 'selected' : '' }}>{{ $code }} - {{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="zip" placeholder="ZIP" value="{{ $party->person->zip }}" class="border-gray-300 rounded-md">
                </div>
            </div>
        </div>

        <!-- Representation -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Representation</label>
            <div class="space-y-2 rounded-md border border-gray-200 bg-gray-50 p-3 opacity-75">
                <label class="flex items-center text-sm text-gray-700">
                    <input type="radio" value="self" {{ !$hasAttorneyRepresentation ? 'checked' : '' }} class="mr-2" disabled>
                    Self-Represented
                </label>
                <label class="flex items-center text-sm text-gray-700">
                    <input type="radio" value="attorney" {{ $hasAttorneyRepresentation ? 'checked' : '' }} class="mr-2" disabled>
                    Attorney Representation
                </label>
            </div>
        </div>
    </div>
    <div class="flex justify-end space-x-3 mt-6">
        <button type="button" onclick="hideEditPartyModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Update Party</button>
    </div>
    <!-- Hidden field to ensure type is submitted -->
    <input type="hidden" name="type" value="{{ $party->person->type }}">
</form>

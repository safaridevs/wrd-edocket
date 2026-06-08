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
@endphp

<div class="space-y-4">
    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-medium text-gray-900">{{ $party->person->full_name }}</h4>
        <p class="text-sm text-gray-600">{{ ucfirst($party->role) }} • {{ ucfirst($party->person->type) }}</p>
        
        @php
            $hasAttorney = $party->attorneys->count() > 0;
            $attorneyCount = $party->attorneys->count();
            $isEntityParty = $party->person->type === 'company';
        @endphp
        @if($hasAttorney)
            <div class="mt-3 p-3 bg-blue-50 rounded border">
                <p class="font-medium text-blue-900 mb-2">Currently Represented By:</p>
                @if($attorneyCount === 1 && $isEntityParty)
                    <p class="mb-2 rounded border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                        This entity must keep at least one attorney. The last attorney cannot be removed here.
                    </p>
                @elseif($attorneyCount === 1)
                    <p class="mb-2 rounded border border-yellow-200 bg-yellow-50 px-3 py-2 text-xs text-yellow-800">
                        Removing this attorney will make the party self-represented.
                    </p>
                @endif
                <div class="space-y-2">
                    @foreach($party->attorneys as $attorneyParty)
                        <div class="flex items-start justify-between gap-3 rounded bg-white/70 p-3">
                            <div>
                                <p class="text-sm">{{ $attorneyParty->person->full_name }}</p>
                                <p class="text-xs text-gray-600">{{ $attorneyParty->person->email }}</p>
                                @if($attorneyParty->person->phone_office)
                                    <p class="text-xs text-gray-600">{{ $attorneyParty->person->phone_office }}</p>
                                @endif
                            </div>
                            <button onclick="removeAttorney({{ $party->id }}, {{ $attorneyParty->id }}, {{ $attorneyCount }}, @js($party->person->type), @js($party->person->full_name))" type="button" class="shrink-0 text-red-600 hover:text-red-800 text-sm">
                                Remove
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-3 p-3 bg-gray-100 rounded">
                <p class="text-sm text-gray-600">
                    @if($party->person->type === 'company')
                        Company requires attorney representation
                    @else
                        Currently self-represented
                    @endif
                </p>
            </div>
        @endif
    </div>

    <form onsubmit="handleAttorneyForm(event, {{ $party->id }})" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                {{ $hasAttorney ? 'Add Another Attorney' : 'Assign Attorney' }}
            </label>
            
            <div class="space-y-3">
                <div>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="attorney_option" value="existing" class="mr-2" onchange="toggleAttorneyFields()" checked>
                        Select Existing Attorney
                    </label>
                    <select name="attorney_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                        @if($attorneys->count() > 0)
                            <option value="">Choose an attorney...</option>
                            @foreach($attorneys as $attorney)
                                <option value="{{ $attorney->id }}">
                                    {{ $attorney->full_name }} - {{ $attorney->email }}
                                </option>
                            @endforeach
                        @else
                            <option value="">No unassigned attorneys available</option>
                        @endif
                    </select>
                </div>
                
                <div>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="attorney_option" value="new" class="mr-2" onchange="toggleAttorneyFields()">
                        Add New Attorney
                    </label>
                    <div id="newAttorneyFields" class="mt-2 space-y-2">
                        <div class="grid grid-cols-1 sm:grid-cols-6 gap-2">
                            <input type="text" name="attorney_prefix" placeholder="Prefix" class="border-gray-300 rounded-md text-sm">
                            <input type="text" name="attorney_first_name" placeholder="First Name *" class="border-gray-300 rounded-md text-sm sm:col-span-2">
                            <input type="text" name="attorney_middle_name" placeholder="Middle" class="border-gray-300 rounded-md text-sm">
                            <input type="text" name="attorney_last_name" placeholder="Last Name *" class="border-gray-300 rounded-md text-sm sm:col-span-2">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <input type="text" name="attorney_suffix" placeholder="Suffix" class="border-gray-300 rounded-md text-sm">
                            <input type="text" name="attorney_title" placeholder="Title" class="border-gray-300 rounded-md text-sm">
                            <input type="email" name="attorney_email" placeholder="Attorney Email *" class="border-gray-300 rounded-md text-sm">
                        </div>
                        <div>
                            <input type="text" name="attorney_phone" placeholder="555-555-5555" inputmode="tel" pattern="\d{3}-\d{3}-\d{4}" oninput="formatPhoneInput(this)" class="border-gray-300 rounded-md text-sm">
                        </div>
                        <div class="space-y-2">
                            <input type="text" name="address_line1" placeholder="Address Line 1" class="block w-full border-gray-300 rounded-md text-sm">
                            <input type="text" name="address_line2" placeholder="Address Line 2" class="block w-full border-gray-300 rounded-md text-sm">
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="text" name="city" placeholder="City" class="border-gray-300 rounded-md text-sm">
                            <select name="state" class="border-gray-300 rounded-md text-sm">
                                @foreach($stateOptions as $code => $label)
                                    <option value="{{ $code }}" {{ old('state', 'NM') === $code ? 'selected' : '' }}>{{ $code }} - {{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="zip" placeholder="ZIP" class="border-gray-300 rounded-md text-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <button type="button" onclick="hideAttorneyModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                Cancel
            </button>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                {{ $hasAttorney ? 'Add Attorney' : 'Assign Attorney' }}
            </button>
        </div>
    </form>
</div>

<script>
window.toggleAttorneyFields = function() {
    const option = document.querySelector('#attorneyModal input[name="attorney_option"]:checked')?.value;
    const existingSelect = document.querySelector('#attorneyModal select[name="attorney_id"]');
    const newFields = document.getElementById('newAttorneyFields');
    const newInputs = newFields?.querySelectorAll('input, select') || [];
    
    if (option === 'existing') {
        existingSelect.disabled = false;
        existingSelect.classList.remove('opacity-50', 'bg-gray-100');
        newFields?.classList.add('opacity-50');
        newInputs.forEach(input => {
            input.disabled = true;
            input.required = false;
            input.classList.add('bg-gray-100');
        });
    } else if (option === 'new') {
        existingSelect.disabled = true;
        existingSelect.value = '';
        existingSelect.classList.add('opacity-50', 'bg-gray-100');
        newFields?.classList.remove('opacity-50');
        newInputs.forEach(input => {
            input.disabled = false;
            input.required = ['attorney_first_name', 'attorney_last_name', 'attorney_email', 'attorney_phone'].includes(input.name);
            input.classList.remove('bg-gray-100');
        });
    }
};

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('attorneyModal');
    if (modal) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (!modal.classList.contains('hidden')) {
                    // Modal was opened, trigger initial state
                    setTimeout(() => toggleAttorneyFields(), 100);
                }
            });
        });
        observer.observe(modal, { attributes: true, attributeFilter: ['class'] });
    }
});

window.handleAttorneyForm = function(event, partyId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    assignAttorney(partyId, formData);
};
</script>

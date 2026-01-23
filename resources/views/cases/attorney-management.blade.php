<div class="space-y-4">
    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-medium text-gray-900">{{ $party->person->full_name }}</h4>
        <p class="text-sm text-gray-600">{{ ucfirst($party->role) }} • {{ ucfirst($party->person->type) }}</p>
        
        @php
            $hasAttorney = $party->attorneys->count() > 0;
        @endphp
        @if($hasAttorney)
            <div class="mt-3 p-3 bg-blue-50 rounded border">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-medium text-blue-900">Currently Represented By:</p>
                        @foreach($party->attorneys as $attorneyParty)
                            @php
                                $attorney = \App\Models\Attorney::where('email', $attorneyParty->person->email)->first();
                            @endphp
                            <div class="mb-2 last:mb-0">
                                <p class="text-sm">{{ $attorneyParty->person->full_name }}</p>
                                <p class="text-xs text-gray-600">{{ $attorneyParty->person->email }}</p>
                                @if($attorneyParty->person->phone_office)
                                    <p class="text-xs text-gray-600">{{ $attorneyParty->person->phone_office }}</p>
                                @endif
                                @if($attorney && $attorney->bar_number)
                                    <p class="text-xs text-gray-600">Bar: {{ $attorney->bar_number }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    @if($party->person->type === 'individual')
                        <button onclick="removeAttorney({{ $party->id }})" class="text-red-600 hover:text-red-800 text-sm">
                            Remove
                        </button>
                    @endif
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
                {{ $hasAttorney ? 'Change Attorney' : 'Assign Attorney' }}
            </label>
            
            <div class="space-y-3">
                <div>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="attorney_option" value="existing" class="mr-2" onchange="toggleAttorneyFields()" checked>
                        Select Existing Attorney
                    </label>
                    <select name="attorney_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm">
                        <option value="">Choose an attorney...</option>
                        @foreach($attorneys as $attorney)
                            <option value="{{ $attorney->id }}">
                                {{ $attorney->name }} - {{ $attorney->email }}
                                @if($attorney->bar_number) (Bar: {{ $attorney->bar_number }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="attorney_option" value="new" class="mr-2" onchange="toggleAttorneyFields()">
                        Add New Attorney
                    </label>
                    <div id="newAttorneyFields" class="mt-2 space-y-2">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="attorney_name" placeholder="Attorney Name *" class="border-gray-300 rounded-md text-sm">
                            <input type="email" name="attorney_email" placeholder="Attorney Email *" class="border-gray-300 rounded-md text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="attorney_phone" placeholder="Phone" class="border-gray-300 rounded-md text-sm">
                            <input type="text" name="bar_number" placeholder="Bar Number" class="border-gray-300 rounded-md text-sm">
                        </div>
                        <div class="space-y-2">
                            <input type="text" name="address_line1" placeholder="Address Line 1" class="block w-full border-gray-300 rounded-md text-sm">
                            <input type="text" name="address_line2" placeholder="Address Line 2" class="block w-full border-gray-300 rounded-md text-sm">
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <input type="text" name="city" placeholder="City" class="border-gray-300 rounded-md text-sm">
                            <input type="text" name="state" placeholder="State" maxlength="2" class="border-gray-300 rounded-md text-sm">
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
                {{ $hasAttorney ? 'Update Attorney' : 'Assign Attorney' }}
            </button>
        </div>
    </form>
</div>

<script>
window.toggleAttorneyFields = function() {
    const option = document.querySelector('#attorneyModal input[name="attorney_option"]:checked')?.value;
    const existingSelect = document.querySelector('#attorneyModal select[name="attorney_id"]');
    const newFields = document.getElementById('newAttorneyFields');
    const newInputs = newFields?.querySelectorAll('input') || [];
    
    if (option === 'existing') {
        existingSelect.disabled = false;
        existingSelect.classList.remove('opacity-50', 'bg-gray-100');
        newFields?.classList.add('opacity-50');
        newInputs.forEach(input => {
            input.disabled = true;
            input.classList.add('bg-gray-100');
        });
    } else if (option === 'new') {
        existingSelect.disabled = true;
        existingSelect.value = '';
        existingSelect.classList.add('opacity-50', 'bg-gray-100');
        newFields?.classList.remove('opacity-50');
        newInputs.forEach(input => {
            input.disabled = false;
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
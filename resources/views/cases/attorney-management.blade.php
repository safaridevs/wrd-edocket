<div class="space-y-4">
    <div class="bg-gray-50 p-4 rounded-lg">
        <h4 class="font-medium text-gray-900">{{ $party->person->full_name }}</h4>
        <p class="text-sm text-gray-600">{{ ucfirst($party->role) }} • {{ ucfirst($party->person->type) }}</p>
        
        @if($party->attorney)
            <div class="mt-3 p-3 bg-blue-50 rounded border">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="font-medium text-blue-900">Currently Represented By:</p>
                        <p class="text-sm">{{ $party->attorney->name }}</p>
                        <p class="text-xs text-gray-600">{{ $party->attorney->email }}</p>
                        @if($party->attorney->phone)
                            <p class="text-xs text-gray-600">{{ $party->attorney->phone }}</p>
                        @endif
                        @if($party->attorney->bar_number)
                            <p class="text-xs text-gray-600">Bar: {{ $party->attorney->bar_number }}</p>
                        @endif
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
                {{ $party->attorney ? 'Change Attorney' : 'Assign Attorney' }}
            </label>
            
            <div class="space-y-3">
                <div>
                    <label class="flex items-center">
                        <input type="radio" name="attorney_option" value="existing" class="mr-2" onchange="toggleAttorneyFields()">
                        Select Existing Attorney
                    </label>
                    <select name="attorney_id" class="mt-1 block w-full border-gray-300 rounded-md text-sm" disabled>
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
                    <label class="flex items-center">
                        <input type="radio" name="attorney_option" value="new" class="mr-2" onchange="toggleAttorneyFields()">
                        Add New Attorney
                    </label>
                    <div id="newAttorneyFields" class="mt-2 space-y-2 opacity-50">
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="attorney_name" placeholder="Attorney Name *" class="border-gray-300 rounded-md text-sm" disabled>
                            <input type="email" name="attorney_email" placeholder="Attorney Email *" class="border-gray-300 rounded-md text-sm" disabled>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" name="attorney_phone" placeholder="Phone" class="border-gray-300 rounded-md text-sm" disabled>
                            <input type="text" name="bar_number" placeholder="Bar Number" class="border-gray-300 rounded-md text-sm" disabled>
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
                {{ $party->attorney ? 'Update Attorney' : 'Assign Attorney' }}
            </button>
        </div>
    </form>
</div>

<script>
function toggleAttorneyFields() {
    const option = document.querySelector('input[name="attorney_option"]:checked')?.value;
    const existingSelect = document.querySelector('select[name="attorney_id"]');
    const newFields = document.getElementById('newAttorneyFields');
    const newInputs = newFields.querySelectorAll('input');
    
    if (option === 'existing') {
        existingSelect.disabled = false;
        newFields.classList.add('opacity-50');
        newInputs.forEach(input => input.disabled = true);
    } else if (option === 'new') {
        existingSelect.disabled = true;
        existingSelect.value = '';
        newFields.classList.remove('opacity-50');
        newInputs.forEach(input => input.disabled = false);
    }
}

function handleAttorneyForm(event, partyId) {
    event.preventDefault();
    const formData = new FormData(event.target);
    assignAttorney(partyId, formData);
}
</script>
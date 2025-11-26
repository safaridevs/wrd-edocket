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
                    <option value="respondent" {{ $party->role === 'respondent' ? 'selected' : '' }} class="compliance-role" style="display: none;">Respondent</option>
                    <option value="violator" {{ $party->role === 'violator' ? 'selected' : '' }} class="compliance-role" style="display: none;">Violator</option>
                    <option value="alleged_violator" {{ $party->role === 'alleged_violator' ? 'selected' : '' }} class="compliance-role" style="display: none;">Alleged Violator</option>
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
            <input type="text" name="phone_mobile" placeholder="Mobile Phone" value="{{ $party->person->phone_mobile }}" class="border-gray-300 rounded-md">
            <input type="text" name="phone_office" placeholder="Office Phone" value="{{ $party->person->phone_office }}" class="border-gray-300 rounded-md">
        </div>

        <div>
            <h4 class="text-sm font-medium text-gray-700 mb-2">Address</h4>
            <div class="space-y-2">
                <input type="text" name="address_line1" placeholder="Address Line 1" value="{{ $party->person->address_line1 }}" class="block w-full border-gray-300 rounded-md">
                <input type="text" name="address_line2" placeholder="Address Line 2" value="{{ $party->person->address_line2 }}" class="block w-full border-gray-300 rounded-md">
                <div class="grid grid-cols-3 gap-2">
                    <input type="text" name="city" placeholder="City" value="{{ $party->person->city }}" class="border-gray-300 rounded-md">
                    <input type="text" name="state" placeholder="State" value="{{ $party->person->state }}" maxlength="2" class="border-gray-300 rounded-md">
                    <input type="text" name="zip" placeholder="ZIP" value="{{ $party->person->zip }}" class="border-gray-300 rounded-md">
                </div>
            </div>
        </div>

        <!-- Representation -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Representation</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="radio" name="representation" value="self" {{ $party->representation === 'self' ? 'checked' : '' }} class="mr-2" onchange="toggleEditRepresentation()">
                    Self-Represented
                </label>
                <label class="flex items-center">
                    <input type="radio" name="representation" value="attorney" {{ $party->representation === 'attorney' ? 'checked' : '' }} class="mr-2" onchange="toggleEditRepresentation()">
                    Attorney Representation
                </label>
            </div>
        </div>

        <!-- Attorney Fields -->
        <div id="editAttorneyFields" class="{{ $party->representation !== 'attorney' ? 'hidden' : '' }} space-y-4">
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="radio" name="attorney_option" value="existing" {{ $party->attorney_id ? 'checked' : '' }} class="mr-2" onchange="toggleEditAttorneyOption()">
                    Select Existing Attorney
                </label>
                <select name="attorney_id" class="ml-6 block w-full border-gray-300 rounded-md" {{ !$party->attorney_id ? 'disabled' : '' }}>
                    <option value="">Choose an attorney...</option>
                    @foreach($attorneys as $attorney)
                        <option value="{{ $attorney->id }}" {{ $party->attorney_id == $attorney->id ? 'selected' : '' }}>
                            {{ $attorney->name }} ({{ $attorney->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="radio" name="attorney_option" value="new" {{ !$party->attorney_id ? 'checked' : '' }} class="mr-2" onchange="toggleEditAttorneyOption()">
                    Add New Attorney
                </label>
                <div id="editNewAttorneyFields" class="ml-6 space-y-2 {{ $party->attorney_id ? 'opacity-50' : '' }}">
                    <input type="text" name="attorney_name" placeholder="Attorney Name" class="block w-full border-gray-300 rounded-md" {{ $party->attorney_id ? 'disabled' : '' }}>
                    <input type="email" name="attorney_email" placeholder="Attorney Email" class="block w-full border-gray-300 rounded-md" {{ $party->attorney_id ? 'disabled' : '' }}>
                    <input type="text" name="attorney_phone" placeholder="Attorney Phone" class="block w-full border-gray-300 rounded-md" {{ $party->attorney_id ? 'disabled' : '' }}>
                    <input type="text" name="bar_number" placeholder="Bar Number" class="block w-full border-gray-300 rounded-md" {{ $party->attorney_id ? 'disabled' : '' }}>
                </div>
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

<script>
// Initialize role filtering on page load
document.addEventListener('DOMContentLoaded', function() {
    updateEditRoleOptions();
});

function updateEditRoleOptions() {
    const caseType = '{{ $case->case_type }}';
    const complianceRoles = document.querySelectorAll('.compliance-role');
    const regularRoles = document.querySelectorAll('.regular-role');

    if (caseType === 'compliance') {
        complianceRoles.forEach(option => option.style.display = 'block');
        regularRoles.forEach(option => option.style.display = 'none');
    } else {
        complianceRoles.forEach(option => option.style.display = 'none');
        regularRoles.forEach(option => option.style.display = 'block');
    }
}

function toggleEditPartyType(select) {
    const individualFields = document.getElementById('editIndividualFields');
    const companyFields = document.getElementById('editCompanyFields');

    if (select.value === 'individual') {
        individualFields.classList.remove('hidden');
        companyFields.classList.add('hidden');
    } else {
        individualFields.classList.add('hidden');
        companyFields.classList.remove('hidden');
    }
}

function toggleEditRepresentation() {
    const attorneyFields = document.getElementById('editAttorneyFields');
    const attorneySelected = document.querySelector('input[name="representation"][value="attorney"]:checked');

    if (attorneySelected) {
        attorneyFields.classList.remove('hidden');
    } else {
        attorneyFields.classList.add('hidden');
    }
}

function toggleEditAttorneyOption() {
    const option = document.querySelector('input[name="attorney_option"]:checked')?.value;
    const existingSelect = document.querySelector('select[name="attorney_id"]');
    const newFields = document.getElementById('editNewAttorneyFields');
    const newInputs = newFields?.querySelectorAll('input');

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

function updateParty(event, partyId) {
    event.preventDefault();

    const formData = new FormData(event.target);

    fetch(`/cases/{{ $case->id }}/parties/${partyId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update party: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update party');
    });
}
</script>

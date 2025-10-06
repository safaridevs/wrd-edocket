<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Manage Parties - Case {{ $case->case_no }}
            </h2>
            <a href="{{ route('cases.show', $case) }}" class="bg-gray-500 text-white px-4 py-2 rounded-md text-sm hover:bg-gray-600">
                Back to Case
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Case Info -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-2">{{ $case->case_no }}</h3>
                <p class="text-sm text-gray-600">{{ $case->caption }}</p>
            </div>

            <!-- Current Parties -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Current Parties ({{ $case->parties->count() }})</h3>
                    <div class="flex space-x-2">
                        @if($case->status === 'approved' && auth()->user()->isHearingUnit())
                        <button onclick="showNotifyModal()" class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-600">
                            📧 Notify Parties
                        </button>
                        @endif
                        @if(auth()->user()->isHearingUnit() || (auth()->user()->canCreateCase() && in_array($case->status, ['draft', 'rejected'])))
                        <button onclick="showAddPartyModal()" class="bg-green-500 text-white px-4 py-2 rounded-md text-sm hover:bg-green-600">
                            + Add Party
                        </button>
                        @endif
                    </div>
                </div>

                @if($case->parties->count() > 0)
                    <div class="space-y-4">
                        @foreach($case->parties as $party)
                        <div class="border rounded-lg p-4 bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3 mb-2">
                                        <h4 class="font-medium">{{ $party->person->full_name }}</h4>
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{{ ucfirst($party->role) }}</span>
                                        @if($party->representation === 'attorney' && $party->attorney)
                                            <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">Represented by {{ $party->attorney->name }}</span>
                                        @elseif($party->representation === 'self')
                                            <span class="bg-gray-100 text-gray-800 text-xs px-2 py-1 rounded">Self-Represented</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <div>{{ $party->person->email }}</div>
                                        @if($party->person->phone_mobile || $party->person->phone_office)
                                            <div>
                                                @if($party->person->phone_mobile)Mobile: {{ $party->person->phone_mobile }}@endif
                                                @if($party->person->phone_mobile && $party->person->phone_office) • @endif
                                                @if($party->person->phone_office)Office: {{ $party->person->phone_office }}@endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @if(auth()->user()->isHearingUnit() || (auth()->user()->canCreateCase() && in_array($case->status, ['draft', 'rejected'])))
                                <div class="flex space-x-2">
                                    <button onclick="editParty({{ $party->id }})" class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
                                    @if($party->representation === 'attorney')
                                        <button onclick="manageAttorney({{ $party->id }})" class="text-green-600 hover:text-green-800 text-sm">Attorney</button>
                                    @endif
                                    <button onclick="removeParty({{ $party->id }})" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-8">No parties added to this case yet.</p>
                @endif
            </div>

            <!-- Service List -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Service List ({{ $case->serviceList->count() }})</h3>
                @if($case->serviceList->count() > 0)
                    <div class="space-y-2">
                        @foreach($case->serviceList as $service)
                        <div class="flex justify-between items-center py-2 border-b">
                            <div>
                                <div class="font-medium">{{ $service->person->full_name }}</div>
                                <div class="text-sm text-gray-600">{{ $service->email }} • {{ ucfirst($service->service_method) }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">Service list is automatically generated from case parties.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Add Party Modal -->
    <div id="addPartyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Add Party to Case</h3>
                    <form id="addPartyForm" action="{{ route('cases.parties.store', $case) }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Role *</label>
                                    <select name="role" required class="mt-1 block w-full border-gray-300 rounded-md">
                                        <option value="applicant">Applicant</option>
                                        <option value="protestant">Protestant</option>
                                        <option value="intervenor">Intervenor</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Type *</label>
                                    <select name="type" required class="mt-1 block w-full border-gray-300 rounded-md" onchange="togglePartyType(this)">
                                        <option value="individual">Individual</option>
                                        <option value="company">Company</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div id="individualFields">
                                <div class="grid grid-cols-4 gap-2">
                                    <input type="text" name="prefix" placeholder="Prefix" class="border-gray-300 rounded-md text-sm">
                                    <input type="text" name="first_name" placeholder="First Name" class="border-gray-300 rounded-md text-sm">
                                    <input type="text" name="middle_name" placeholder="Middle" class="border-gray-300 rounded-md text-sm">
                                    <input type="text" name="last_name" placeholder="Last Name" class="border-gray-300 rounded-md text-sm">
                                </div>
                                <div class="grid grid-cols-2 gap-2 mt-2">
                                    <input type="text" name="suffix" placeholder="Suffix" class="border-gray-300 rounded-md text-sm">
                                    <input type="text" name="title" placeholder="Title" class="border-gray-300 rounded-md text-sm">
                                </div>
                            </div>
                            
                            <div id="companyFields" class="hidden">
                                <div class="grid grid-cols-2 gap-2">
                                    <input type="text" name="organization" placeholder="Organization" class="border-gray-300 rounded-md">
                                    <input type="text" name="title" placeholder="Title" class="border-gray-300 rounded-md">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-2">
                                <input type="email" name="email" placeholder="Email *" required class="border-gray-300 rounded-md">
                                <input type="text" name="phone_mobile" placeholder="Mobile Phone" class="border-gray-300 rounded-md">
                                <input type="text" name="phone_office" placeholder="Office Phone" class="border-gray-300 rounded-md">
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Address</h4>
                                <div class="space-y-2">
                                    <input type="text" name="address_line1" placeholder="Address Line 1" class="block w-full border-gray-300 rounded-md">
                                    <input type="text" name="address_line2" placeholder="Address Line 2" class="block w-full border-gray-300 rounded-md">
                                    <div class="grid grid-cols-3 gap-2">
                                        <input type="text" name="city" placeholder="City" class="border-gray-300 rounded-md">
                                        <input type="text" name="state" placeholder="State" maxlength="2" class="border-gray-300 rounded-md">
                                        <input type="text" name="zip" placeholder="ZIP" class="border-gray-300 rounded-md">
                                    </div>
                                </div>
                            </div>

                            <!-- Representation -->
                            <div id="representationSection">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Representation</label>
                                <div id="individualRepresentation" class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="radio" name="representation" value="self" checked class="mr-2" onchange="toggleRepresentation()">
                                        Self-Represented
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="representation" value="attorney" class="mr-2" onchange="toggleRepresentation()">
                                        Attorney Representation
                                    </label>
                                </div>
                                <div id="companyRepresentation" class="hidden">
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 mb-3">
                                        <p class="text-sm text-yellow-800">
                                            <strong>Note:</strong> Companies must be represented by an attorney.
                                        </p>
                                    </div>
                                    <input type="hidden" name="representation" value="attorney">
                                </div>
                            </div>

                            <!-- Attorney Fields -->
                            <div id="attorneyFields" class="hidden space-y-4">
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="radio" name="attorney_option" value="existing" class="mr-2" onchange="toggleAttorneyOption()">
                                        Select Existing Attorney
                                    </label>
                                    <select name="attorney_id" class="ml-6 block w-full border-gray-300 rounded-md" disabled>
                                        <option value="">Choose an attorney...</option>
                                        @foreach($attorneys as $attorney)
                                            <option value="{{ $attorney->id }}">{{ $attorney->name }} ({{ $attorney->email }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="radio" name="attorney_option" value="new" class="mr-2" onchange="toggleAttorneyOption()">
                                        Add New Attorney
                                    </label>
                                    <div id="newAttorneyFields" class="ml-6 space-y-2 opacity-50">
                                        <input type="text" name="attorney_name" placeholder="Attorney Name" class="block w-full border-gray-300 rounded-md" disabled>
                                        <input type="email" name="attorney_email" placeholder="Attorney Email" class="block w-full border-gray-300 rounded-md" disabled>
                                        <input type="text" name="attorney_phone" placeholder="Attorney Phone" class="block w-full border-gray-300 rounded-md" disabled>
                                        <input type="text" name="bar_number" placeholder="Bar Number" class="block w-full border-gray-300 rounded-md" disabled>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="hideAddPartyModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Add Party</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Party Modal -->
    <div id="editPartyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Edit Party</h3>
                    <div id="editPartyContent">
                        <!-- Content loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Attorney Management Modal -->
    <div id="attorneyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Manage Attorney Representation</h3>
                    <div id="attorneyContent">
                        <!-- Content loaded dynamically -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAddPartyModal() {
            document.getElementById('addPartyModal').classList.remove('hidden');
        }

        function hideAddPartyModal() {
            document.getElementById('addPartyModal').classList.add('hidden');
            document.getElementById('addPartyForm').reset();
            togglePartyType(document.querySelector('select[name="type"]'));
            toggleRepresentation();
        }

        function togglePartyType(select) {
            const individualFields = document.getElementById('individualFields');
            const companyFields = document.getElementById('companyFields');
            const individualRep = document.getElementById('individualRepresentation');
            const companyRep = document.getElementById('companyRepresentation');
            const attorneyFields = document.getElementById('attorneyFields');
            
            if (select.value === 'individual') {
                individualFields.classList.remove('hidden');
                companyFields.classList.add('hidden');
                individualRep.classList.remove('hidden');
                companyRep.classList.add('hidden');
                // Reset to self-representation for individuals
                document.querySelector('input[name="representation"][value="self"]').checked = true;
                toggleRepresentation();
            } else if (select.value === 'company') {
                individualFields.classList.add('hidden');
                companyFields.classList.remove('hidden');
                individualRep.classList.add('hidden');
                companyRep.classList.remove('hidden');
                // Always show attorney fields for companies
                attorneyFields.classList.remove('hidden');
            }
        }

        function toggleRepresentation() {
            const attorneyFields = document.getElementById('attorneyFields');
            const attorneySelected = document.querySelector('input[name="representation"][value="attorney"]:checked');
            
            if (attorneySelected) {
                attorneyFields.classList.remove('hidden');
            } else {
                attorneyFields.classList.add('hidden');
            }
        }

        function toggleAttorneyOption() {
            const option = document.querySelector('input[name="attorney_option"]:checked')?.value;
            const existingSelect = document.querySelector('select[name="attorney_id"]');
            const newFields = document.getElementById('newAttorneyFields');
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

        function editParty(partyId) {
            fetch(`/cases/{{ $case->id }}/parties/${partyId}/edit`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('editPartyContent').innerHTML = html;
                    document.getElementById('editPartyModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load party edit form');
                });
        }

        function hideEditPartyModal() {
            document.getElementById('editPartyModal').classList.add('hidden');
        }

        function removeParty(partyId) {
            if (confirm('Are you sure you want to remove this party from the case?')) {
                fetch(`/cases/{{ $case->id }}/parties/${partyId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to remove party');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to remove party');
                });
            }
        }

        function manageAttorney(partyId) {
            fetch(`/cases/{{ $case->id }}/parties/${partyId}/attorney`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('attorneyContent').innerHTML = html;
                    document.getElementById('attorneyModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load attorney management');
                });
        }

        function hideAttorneyModal() {
            document.getElementById('attorneyModal').classList.add('hidden');
        }

        function showNotifyModal() {
            document.getElementById('notifyModal').classList.remove('hidden');
        }

        function hideNotifyModal() {
            document.getElementById('notifyModal').classList.add('hidden');
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="notify_recipients[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }
    </script>

    <!-- Notify Parties Modal -->
    @if($case->status === 'approved' && auth()->user()->isHearingUnit())
    <div id="notifyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Notify Parties - Case {{ $case->case_no }} Approved</h3>
                    <p class="text-sm text-gray-600 mb-4">Select the parties and attorneys to notify about the case approval:</p>
                    
                    <form action="{{ route('cases.notify-parties', $case) }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <!-- Select All -->
                            <div class="border-b pb-3">
                                <label class="flex items-center font-medium">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="mr-3">
                                    Select All
                                </label>
                            </div>
                            
                            <!-- Case Parties -->
                            <div>
                                <h4 class="font-medium text-sm mb-3">Case Parties:</h4>
                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    @foreach($case->parties as $party)
                                    <label class="flex items-center p-2 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="notify_recipients[]" value="party_{{ $party->id }}" class="mr-3">
                                        <div class="flex-1">
                                            <div class="font-medium text-sm">{{ $party->person->full_name }}</div>
                                            <div class="text-xs text-gray-500">{{ ucfirst($party->role) }} • {{ $party->person->email }}</div>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Attorneys -->
                            @php
                                $attorneys = $case->parties->where('representation', 'attorney')->whereNotNull('attorney_id')->pluck('attorney')->unique('id');
                            @endphp
                            @if($attorneys->count() > 0)
                            <div>
                                <h4 class="font-medium text-sm mb-3">Associated Attorneys:</h4>
                                <div class="space-y-2 max-h-40 overflow-y-auto">
                                    @foreach($attorneys as $attorney)
                                    <label class="flex items-center p-2 hover:bg-gray-50 rounded">
                                        <input type="checkbox" name="notify_recipients[]" value="attorney_{{ $attorney->id }}" class="mr-3">
                                        <div class="flex-1">
                                            <div class="font-medium text-sm">{{ $attorney->name }}</div>
                                            <div class="text-xs text-gray-500">Attorney • {{ $attorney->email }}</div>
                                        </div>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                            
                            <!-- Custom Message -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Additional Message (Optional)</label>
                                <textarea name="custom_message" rows="3" class="block w-full border-gray-300 rounded-md" placeholder="Add any additional information about the case approval..."></textarea>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="hideNotifyModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Send Notifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-app-layout>
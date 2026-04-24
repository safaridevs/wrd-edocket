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

<div class="mb-6">
    <h3 class="text-lg font-medium mb-4">Parties & Contact Information</h3>

    <div id="parties-list" class="space-y-6">
        <div class="border rounded-lg p-4 bg-gray-50">
            <div class="flex justify-between items-center mb-4">
                <h4 class="font-medium text-gray-900" id="party-0-title">Primary Party 1 *</h4>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Party Role *</label>
                <select name="parties[0][role]" id="party-0-role" required class="mt-1 block w-full border-gray-300 rounded-md">
                    <option value="">Select Role</option>
                    <option value="applicant" {{ old('parties.0.role') == 'applicant' ? 'selected' : '' }}>Applicant</option>
                    <option value="respondent" {{ old('parties.0.role') == 'respondent' ? 'selected' : '' }} class="compliance-role" style="display: none;">Respondent</option>
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        Type *
                        <span class="ml-2 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-blue-700 bg-blue-100 rounded-full cursor-help align-middle"
                              title="19.25.2.11 (B) and (C) NMAC:&#10;B. An individual may appear as a pro se party. Parties appearing pro se shall be responsible for familiarizing themselves with this rule, the rules of civil procedure for the district courts of New Mexico, the rules of evidence governing non-jury trials for the district courts of New Mexico, the instructions for parties in administrative proceedings, and all other rules of the OSE.&#10;&#10;C. A party that is not an individual shall be represented by an attorney or an authorized agent until counsel appears.">i</span>
                    </label>
                    <select name="parties[0][type]" required class="mt-1 block w-full border-gray-300 rounded-md" onchange="togglePersonFields(0)">
                        <option value="">Select Type</option>
                        <option value="individual" {{ old('parties.0.type') == 'individual' ? 'selected' : '' }}>Individual</option>
                        <option value="company" {{ old('parties.0.type') == 'company' ? 'selected' : '' }}>Entity (Non-Person)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Service Method</label>
                    <select name="parties[0][service_method]" class="mt-1 block w-full border-gray-300 rounded-md">
                        <option value="email" {{ old('parties.0.service_method') == 'email' ? 'selected' : '' }}>Email</option>
                        <option value="mail" {{ old('parties.0.service_method') == 'mail' ? 'selected' : '' }}>Mail</option>
                    </select>
                </div>
            </div>

            <div id="individual-fields-0" class="{{ old('parties.0.type') == 'individual' ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">First Name *</label>
                        <input type="text" name="parties[0][first_name]" value="{{ old('parties.0.first_name') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                        <input type="text" name="parties[0][last_name]" value="{{ old('parties.0.last_name') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>
                </div>
            </div>

            <div id="company-fields-0" class="{{ old('parties.0.type') == 'company' ? '' : 'hidden' }}">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Organization Name *</label>
                    <input type="text" name="parties[0][organization]" value="{{ old('parties.0.organization') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Principal Contact First Name *</label>
                        <input type="text" name="parties[0][first_name]" value="{{ old('parties.0.first_name') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Principal Contact Last Name *</label>
                        <input type="text" name="parties[0][last_name]" value="{{ old('parties.0.last_name') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Representation</label>
                <div id="representation-0" class="mt-2">
                    <div class="individual-representation {{ old('parties.0.type') == 'individual' ? '' : 'hidden' }}">
                        <label class="flex items-center mb-2">
                            <input type="radio" name="parties[0][representation]" value="self" {{ old('parties.0.representation', 'self') == 'self' ? 'checked' : '' }} class="mr-2" onchange="toggleAttorneyFields(0)">
                            Self-Represented
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="parties[0][representation]" value="attorney" {{ old('parties.0.representation') == 'attorney' ? 'checked' : '' }} class="mr-2" onchange="toggleAttorneyFields(0)">
                            Represented by Attorney
                        </label>
                    </div>
                    <div class="company-representation {{ old('parties.0.type') == 'company' ? '' : 'hidden' }}">
                        @php
                            $companyRepresentationMode = old('parties.0.representation_mode');
                            if (!$companyRepresentationMode && old('parties.0.attorney_option') === 'no_attorney_yet') {
                                $companyRepresentationMode = 'none';
                            }
                        @endphp
                        <label class="flex items-center mb-2">
                            <input type="radio" name="parties[0][representation_mode]" value="attorney" {{ $companyRepresentationMode === 'attorney' ? 'checked' : '' }} class="mr-2" onchange="toggleCompanyRepresentation(0)">
                            Represented by Attorney
                        </label>
                        <label class="flex items-center mb-2">
                            <input type="radio" name="parties[0][representation_mode]" value="agent" {{ $companyRepresentationMode === 'agent' ? 'checked' : '' }} class="mr-2" onchange="toggleCompanyRepresentation(0)">
                            Represented by Agent
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="parties[0][representation_mode]" value="none" {{ $companyRepresentationMode === 'none' ? 'checked' : '' }} class="mr-2" onchange="toggleCompanyRepresentation(0)">
                            No representative yet
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">If you choose agent representation, the Agent Information section opens below.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email *</label>
                    <input type="email" name="parties[0][email]" value="{{ old('parties.0.email') }}" required class="mt-1 block w-full border-gray-300 rounded-md">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mobile Phone *</label>
                        <input type="text" name="parties[0][phone_mobile]" value="{{ old('parties.0.phone_mobile') }}" required inputmode="tel" pattern="\d{3}-\d{3}-\d{4}" placeholder="555-555-5555" oninput="formatPhoneInput(this)" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Office Phone</label>
                        <input type="text" name="parties[0][phone_office]" value="{{ old('parties.0.phone_office') }}" inputmode="tel" pattern="\d{3}-\d{3}-\d{4}" placeholder="555-555-5555" oninput="formatPhoneInput(this)" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Address</label>
                <input type="text" name="parties[0][address_line1]" value="{{ old('parties.0.address_line1') }}" placeholder="Address Line 1" class="mt-1 block w-full border-gray-300 rounded-md">
                <input type="text" name="parties[0][address_line2]" value="{{ old('parties.0.address_line2') }}" placeholder="Address Line 2 (Optional)" class="mt-2 block w-full border-gray-300 rounded-md">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" name="parties[0][city]" value="{{ old('parties.0.city') }}" placeholder="City" class="border-gray-300 rounded-md">
                <select name="parties[0][state]" class="border-gray-300 rounded-md">
                    @foreach($stateOptions as $code => $label)
                        <option value="{{ $code }}" {{ old('parties.0.state', 'NM') === $code ? 'selected' : '' }}>{{ $code }} - {{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="parties[0][zip]" value="{{ old('parties.0.zip') }}" placeholder="ZIP" class="border-gray-300 rounded-md">
            </div>

            <div id="attorney-fields-0" class="{{ old('parties.0.representation') == 'attorney' || old('parties.0.representation_mode') == 'attorney' ? '' : 'hidden' }} mt-4">
                <div class="bg-indigo-50 border-2 border-indigo-200 rounded-lg p-4">
                    <h5 class="font-semibold text-indigo-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        Attorney Information
                    </h5>

                    <div class="mb-4">
                        <label class="flex items-center mb-2">
                            <input type="radio" name="parties[0][attorney_option]" value="existing" class="mr-2" onchange="toggleAttorneyOption(0)" {{ old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id') ? 'checked' : '' }}>
                            Select Existing Attorney
                        </label>
                        <select name="parties[0][attorney_id]" class="mt-1 block w-full border-gray-300 rounded-md" {{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? '' : 'disabled' }}>
                            <option value="">Choose an attorney...</option>
                            @foreach($attorneys as $attorney)
                                <option value="{{ $attorney->id }}" {{ old('parties.0.attorney_id') == $attorney->id ? 'selected' : '' }}>
                                    {{ $attorney->name }} - {{ $attorney->email }}
                                    @if($attorney->bar_number) (Bar: {{ $attorney->bar_number }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center mb-2">
                            <input type="radio" name="parties[0][attorney_option]" value="new" class="mr-2" onchange="toggleAttorneyOption(0)" {{ !old('parties.0.attorney_option') || old('parties.0.attorney_option') == 'new' ? 'checked' : '' }}>
                            Add New Attorney
                        </label>
                        <div id="new-attorney-0" class="{{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? 'opacity-50' : '' }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Attorney Name *</label>
                                    <input type="text" name="parties[0][attorney_name]" value="{{ old('parties.0.attorney_name') }}" class="mt-1 block w-full border-gray-300 rounded-md" {{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? 'disabled' : '' }}>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Attorney Email *</label>
                                    <input type="email" name="parties[0][attorney_email]" value="{{ old('parties.0.attorney_email') }}" class="mt-1 block w-full border-gray-300 rounded-md" {{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? 'disabled' : '' }}>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Attorney Phone *</label>
                                    <input type="text" name="parties[0][attorney_phone]" value="{{ old('parties.0.attorney_phone') }}" inputmode="tel" pattern="\d{3}-\d{3}-\d{4}" placeholder="555-555-5555" oninput="formatPhoneInput(this)" class="mt-1 block w-full border-gray-300 rounded-md" {{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? 'disabled' : '' }} {{ (!old('parties.0.attorney_option') || old('parties.0.attorney_option') == 'new') && !old('parties.0.attorney_id') ? 'required' : '' }}>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Bar Number</label>
                                    <input type="text" name="parties[0][bar_number]" value="{{ old('parties.0.bar_number') }}" class="mt-1 block w-full border-gray-300 rounded-md" {{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? 'disabled' : '' }}>
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Attorney Address</label>
                                <input type="text" name="parties[0][attorney_address_line1]" value="{{ old('parties.0.attorney_address_line1') }}" placeholder="Address Line 1" class="mt-1 block w-full border-gray-300 rounded-md" {{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? 'disabled' : '' }}>
                                <input type="text" name="parties[0][attorney_address_line2]" value="{{ old('parties.0.attorney_address_line2') }}" placeholder="Address Line 2 (Optional)" class="mt-2 block w-full border-gray-300 rounded-md" {{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? 'disabled' : '' }}>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                                    <input type="text" name="parties[0][attorney_city]" value="{{ old('parties.0.attorney_city') }}" placeholder="City" class="border-gray-300 rounded-md" {{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? 'disabled' : '' }}>
                                    <select name="parties[0][attorney_state]" class="border-gray-300 rounded-md" {{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? 'disabled' : '' }}>
                                        @foreach($stateOptions as $code => $label)
                                            <option value="{{ $code }}" {{ old('parties.0.attorney_state', 'NM') === $code ? 'selected' : '' }}>{{ $code }} - {{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" name="parties[0][attorney_zip]" value="{{ old('parties.0.attorney_zip') }}" placeholder="ZIP" class="border-gray-300 rounded-md" {{ (old('parties.0.attorney_option') == 'existing' || old('parties.0.attorney_id')) ? 'disabled' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="agent-fields-0" class="{{ old('parties.0.representation_mode') == 'agent' ? '' : 'hidden' }} mt-4">
                <div class="bg-amber-50 border-2 border-amber-200 rounded-lg p-4">
                    <h5 class="font-semibold text-amber-900 mb-4">Agent Information</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Agent First Name *</label>
                            <input type="text" name="parties[0][agent_first_name]" value="{{ old('parties.0.agent_first_name') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Agent Last Name *</label>
                            <input type="text" name="parties[0][agent_last_name]" value="{{ old('parties.0.agent_last_name') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Agent Email *</label>
                            <input type="email" name="parties[0][agent_email]" value="{{ old('parties.0.agent_email') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Agent Phone *</label>
                            <input type="text" name="parties[0][agent_phone]" value="{{ old('parties.0.agent_phone') }}" inputmode="tel" pattern="\d{3}-\d{3}-\d{4}" placeholder="555-555-5555" oninput="formatPhoneInput(this)" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Agent Organization</label>
                        <input type="text" name="parties[0][agent_organization]" value="{{ old('parties.0.agent_organization') }}" class="mt-1 block w-full border-gray-300 rounded-md">
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700">Agent Address</label>
                        <input type="text" name="parties[0][agent_address_line1]" value="{{ old('parties.0.agent_address_line1') }}" placeholder="Address Line 1" class="mt-1 block w-full border-gray-300 rounded-md">
                        <input type="text" name="parties[0][agent_address_line2]" value="{{ old('parties.0.agent_address_line2') }}" placeholder="Address Line 2 (Optional)" class="mt-2 block w-full border-gray-300 rounded-md">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                            <input type="text" name="parties[0][agent_city]" value="{{ old('parties.0.agent_city') }}" placeholder="City" class="border-gray-300 rounded-md">
                            <select name="parties[0][agent_state]" class="border-gray-300 rounded-md">
                                @foreach($stateOptions as $code => $label)
                                    <option value="{{ $code }}" {{ old('parties.0.agent_state', 'NM') === $code ? 'selected' : '' }}>{{ $code }} - {{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="parties[0][agent_zip]" value="{{ old('parties.0.agent_zip') }}" placeholder="ZIP" class="border-gray-300 rounded-md">
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-amber-800">I will be using an Agent and have uploaded the agent authorization form attached with my application.</p>
                </div>
            </div>

            <div id="no-representative-note-0" class="{{ old('parties.0.representation_mode') == 'none' ? '' : 'hidden' }} mt-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                    This entity currently has no attorney or agent on file. Principal contact information is required and will be used until representation is added.
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 space-x-2" id="add-party-buttons">
        <button type="button" onclick="addParty('applicant')" class="text-blue-600 text-sm hover:text-blue-800 regular-case-btn">+ Add Applicant</button>
        <button type="button" onclick="addParty('respondent')" class="text-blue-600 text-sm hover:text-blue-800 compliance-case-btn" style="display: none;">+ Add Respondent</button>
        <button type="button" onclick="addParty('protestant')" class="text-blue-600 text-sm hover:text-blue-800">+ Add Protestant</button>
        <button type="button" onclick="addParty('counsel')" class="text-blue-600 text-sm hover:text-blue-800">+ Add Counsel</button>
    </div>
</div>

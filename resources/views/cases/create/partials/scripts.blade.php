


    <script>
        let oseCount = 1, partyCount = 1, optionalDocCount = 1;
        let currentWizardStep = 0;
        let caseCreateForm = null;
        const stateOptionsHtml = `@foreach([
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
        ] as $code => $label)<option value="{{ $code }}" {{ $code === 'NM' ? 'selected' : '' }}>{{ $code }} - {{ $label }}</option>@endforeach`;

        const wizardStepMeta = [
            {
                title: 'Case Basics',
                description: 'Set the foundational details that determine the rest of the intake experience.',
            },
            {
                title: 'Parties & Counsel',
                description: 'Capture every participant, their role, service method, and representation details.',
            },
            {
                title: 'Case Numbers',
                description: 'Add OSE numbers and ranges so routing and search work correctly later.',
            },
            {
                title: 'Documents',
                description: 'Upload only the filings this case needs based on the intake path you selected.',
            },
            {
                title: 'Review & Submit',
                description: 'Confirm the summary, then save a draft or submit to the Hearing Unit.',
            },
        ];

        document.addEventListener('DOMContentLoaded', function() {
            caseCreateForm = document.getElementById('caseCreateForm');
            const caseTypeInputs = document.querySelectorAll('input[name="case_type"]');
            caseTypeInputs.forEach(input => {
                input.addEventListener('change', function() {
                    updatePartyRoleOptions();
                    updateDocumentSections();
                });
            });

            updatePartyRoleOptions();
            updateDocumentSections();
            document.querySelectorAll('select[name^="parties["][name$="[type]"]').forEach(select => {
                const match = select.name.match(/parties\[(\d+)\]\[type\]/);
                if (match) {
                    togglePersonFields(parseInt(match[1], 10));
                }
            });

            initializeWizard();
        });

        function updateDocumentSections() {
            const selectedCaseType = document.querySelector('input[name="case_type"]:checked')?.value;
            const applicationSection = document.getElementById('application-section');
            const complianceDocs = document.getElementById('compliance-documents');
            const applicationInput = document.querySelector('input[name="documents[application][]"]');

            if (selectedCaseType === 'compliance') {
                // Hide application section for compliance cases
                applicationSection.style.display = 'none';
                complianceDocs.style.display = 'block';

                // Disable application input
                if (applicationInput) {
                    applicationInput.required = false;
                    applicationInput.disabled = true;
                }
            } else {
                // Show application section for aggrieved/protested cases
                applicationSection.style.display = 'block';
                complianceDocs.style.display = 'none';

                // Enable application input
                if (applicationInput) {
                    applicationInput.required = true;
                    applicationInput.disabled = false;
                }

                // Disable compliance document inputs
                complianceDocs.querySelectorAll('input[type="file"]').forEach(input => {
                    input.required = false;
                    input.disabled = true;
                });
            }
        }

        function updateComplianceDocLabel() {
            const selectedType = document.querySelector('input[name="compliance_doc_type"]:checked');
            const fileLabel = document.getElementById('compliance-file-label');
            const fileInput = document.getElementById('compliance-file-input');

            if (selectedType) {
                const typeText = selectedType.nextElementSibling.querySelector('.font-medium').textContent;
                fileLabel.textContent = `${typeText} Document (PDF) *`;
                fileInput.disabled = false;
                fileInput.required = true;
                fileInput.style.opacity = '1';
            } else {
                fileLabel.textContent = 'Select Document Type First';
                fileInput.disabled = true;
                fileInput.required = false;
                fileInput.style.opacity = '0.5';
            }
        }

        function updatePartyRoleOptions() {
            const selectedCaseType = document.querySelector('input[name="case_type"]:checked')?.value;
            const complianceRoles = document.querySelectorAll('.compliance-role');
            const regularCaseBtns = document.querySelectorAll('.regular-case-btn');
            const complianceCaseBtns = document.querySelectorAll('.compliance-case-btn');
            const partyTitle = document.getElementById('party-0-title');
            const partyRoleSelect = document.getElementById('party-0-role');

            if (selectedCaseType === 'compliance') {
                // Show compliance roles
                complianceRoles.forEach(option => option.style.display = 'block');
                regularCaseBtns.forEach(btn => btn.style.display = 'none');
                complianceCaseBtns.forEach(btn => btn.style.display = 'inline-block');

                // Update title and default selection
                if (partyTitle) partyTitle.textContent = 'Primary Party 1 *';

                // Hide applicant option for compliance cases
                const applicantOption = partyRoleSelect?.querySelector('option[value="applicant"]');
                if (applicantOption) applicantOption.style.display = 'none';

                // Auto-select first compliance role if no role selected
                if (partyRoleSelect && (!partyRoleSelect.value || partyRoleSelect.value === 'applicant')) {
                    partyRoleSelect.value = 'respondent';
                }
            } else {
                // Show regular roles
                complianceRoles.forEach(option => option.style.display = 'none');
                regularCaseBtns.forEach(btn => btn.style.display = 'inline-block');
                complianceCaseBtns.forEach(btn => btn.style.display = 'none');

                // Update title
                if (partyTitle) partyTitle.textContent = 'Applicant 1 *';

                // Show applicant option for regular cases
                const applicantOption = partyRoleSelect?.querySelector('option[value="applicant"]');
                if (applicantOption) applicantOption.style.display = 'block';

                // Auto-select applicant for regular case types
                if (partyRoleSelect && (!partyRoleSelect.value || partyRoleSelect.value === 'respondent')) {
                    partyRoleSelect.value = 'applicant';
                }
            }
        }

        function initializeWizard() {
            const prevBtn = document.getElementById('wizardPrevBtn');
            const nextBtn = document.getElementById('wizardNextBtn');

            prevBtn?.addEventListener('click', () => goToWizardStep(currentWizardStep - 1));
            nextBtn?.addEventListener('click', () => {
                if (!validateWizardStep(currentWizardStep)) {
                    return;
                }

                if (currentWizardStep === wizardStepMeta.length - 2) {
                    populateReviewStep();
                }

                goToWizardStep(currentWizardStep + 1);
            });

            document.querySelectorAll('[data-step-target]').forEach((button) => {
                button.addEventListener('click', () => {
                    const targetStep = Number(button.dataset.stepTarget);
                    if (Number.isNaN(targetStep) || targetStep === currentWizardStep) {
                        return;
                    }

                    if (targetStep > currentWizardStep) {
                        for (let step = currentWizardStep; step < targetStep; step++) {
                            if (!validateWizardStep(step)) {
                                return;
                            }
                        }

                        if (targetStep === wizardStepMeta.length - 1) {
                            populateReviewStep();
                        }
                    }

                    goToWizardStep(targetStep);
                });
            });

            document.querySelectorAll('[data-submit-action]').forEach((button) => {
                button.addEventListener('click', () => submitCaseForm(button.dataset.submitAction));
            });

            goToWizardStep(0);
        }

        function goToWizardStep(stepIndex) {
            const stepPanels = document.querySelectorAll('.wizard-step');
            if (stepIndex < 0 || stepIndex >= stepPanels.length) {
                return;
            }

            currentWizardStep = stepIndex;

            stepPanels.forEach((panel, index) => {
                panel.classList.toggle('hidden', index !== stepIndex);
            });

            const meta = wizardStepMeta[stepIndex];
            const progressPercent = Math.round(((stepIndex + 1) / wizardStepMeta.length) * 100);

            document.getElementById('wizardStepEyebrow').textContent = `Step ${stepIndex + 1}`;
            document.getElementById('wizardStepTitle').textContent = meta.title;
            document.getElementById('wizardStepDescription').textContent = meta.description;
            document.getElementById('wizardProgressLabel').textContent = `Step ${stepIndex + 1} of ${wizardStepMeta.length}`;
            document.getElementById('wizardProgressPercent').textContent = `${progressPercent}%`;
            document.getElementById('wizardProgressBar').style.width = `${progressPercent}%`;

            document.querySelectorAll('.wizard-step-chip').forEach((chip, index) => {
                const isActive = index === stepIndex;
                const isComplete = index < stepIndex;

                chip.classList.toggle('border-slate-900', isActive);
                chip.classList.toggle('bg-slate-950', isActive);
                chip.classList.toggle('text-white', isActive);
                chip.classList.toggle('border-emerald-200', isComplete && !isActive);
                chip.classList.toggle('bg-emerald-50', isComplete && !isActive);
                chip.classList.toggle('border-slate-200', !isActive && !isComplete);
                chip.classList.toggle('bg-white', !isActive && !isComplete);

                const labels = chip.querySelectorAll('span');
                if (labels[0]) labels[0].className = `block text-xs font-semibold uppercase tracking-[0.18em] ${isActive ? 'text-slate-200' : isComplete ? 'text-emerald-600' : 'text-slate-400'}`;
                if (labels[1]) labels[1].className = `mt-1 block text-sm font-semibold ${isActive ? 'text-white' : 'text-slate-900'}`;
                if (labels[2]) labels[2].className = `mt-1 block text-xs ${isActive ? 'text-slate-200' : 'text-slate-500'}`;
            });

            const prevBtn = document.getElementById('wizardPrevBtn');
            const nextBtn = document.getElementById('wizardNextBtn');
            prevBtn?.classList.toggle('invisible', stepIndex === 0);
            nextBtn?.classList.toggle('hidden', stepIndex === wizardStepMeta.length - 1);

            if (stepIndex === wizardStepMeta.length - 1) {
                populateReviewStep();
            }

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateWizardStep(stepIndex) {
            const stepPanels = document.querySelectorAll('.wizard-step');
            const stepEl = stepPanels[stepIndex];
            if (!stepEl) {
                return true;
            }

            const radioGroups = new Map();
            const fields = Array.from(stepEl.querySelectorAll('input, select, textarea')).filter((field) => {
                if (field.disabled || field.type === 'hidden' || !field.name) {
                    return false;
                }

                return isFieldRelevantToValidation(field, stepEl);
            });

            for (const field of fields) {
                if ((field.type === 'radio' || field.type === 'checkbox') && field.required) {
                    if (!radioGroups.has(field.name)) {
                        radioGroups.set(field.name, []);
                    }
                    radioGroups.get(field.name).push(field);
                    continue;
                }

                if (!field.checkValidity()) {
                    field.reportValidity();
                    return false;
                }
            }

            for (const [, group] of radioGroups.entries()) {
                const checked = group.some((field) => field.checked);
                if (!checked) {
                    group[0].reportValidity();
                    return false;
                }
            }

            return true;
        }

        function isFieldRelevantToValidation(field, stepEl) {
            let current = field;
            while (current && current !== stepEl) {
                if (current.classList?.contains('hidden')) {
                    return false;
                }

                const style = window.getComputedStyle(current);
                if (style.display === 'none' || style.visibility === 'hidden') {
                    return false;
                }

                current = current.parentElement;
            }

            return true;
        }

        function submitCaseForm(action) {
            for (let step = 0; step < wizardStepMeta.length; step++) {
                if (!validateWizardStep(step)) {
                    goToWizardStep(step);
                    return;
                }
            }

            const actionInput = document.getElementById('caseActionInput');
            if (actionInput) {
                actionInput.value = action;
            }

            const form = caseCreateForm;
            const progressDiv = document.getElementById('upload-progress');
            if (progressDiv) {
                progressDiv.classList.remove('hidden');
            }

            form.submit();
        }

        function populateReviewStep() {
            populateReviewBasics();
            populateReviewRouting();
            populateReviewParties();
            populateReviewDocuments();
        }

        function populateReviewBasics() {
            const caseType = document.querySelector('input[name="case_type"]:checked')?.value || 'Not selected';
            const caption = document.getElementById('caption')?.value?.trim() || 'Not provided';
            const office = document.querySelector('input[name="wrd_office"]:checked')?.value || 'Not selected';
            const basics = [
                ['Case type', toTitle(caseType.replace(/_/g, ' '))],
                ['Caption', caption],
                ['ALU office', office === 'santa_fe' ? 'Santa Fe' : office === 'albuquerque' ? 'Albuquerque' : office],
            ];

            document.getElementById('reviewBasics').innerHTML = basics.map(([label, value]) => buildReviewRow(label, value)).join('');
        }

        function populateReviewRouting() {
            const attorneyCount = document.querySelectorAll('input[name="assigned_attorneys[]"]:checked').length;
            const clerkCount = document.querySelectorAll('input[name="assigned_clerks[]"]:checked').length;
            const oseEntries = Array.from(document.querySelectorAll('#ose-numbers > div')).map((row) => {
                const selects = row.querySelectorAll('select');
                const inputs = row.querySelectorAll('input[type="text"]');
                const basinFrom = selects[0]?.value || '';
                const fileFrom = inputs[0]?.value?.trim() || '';
                const basinTo = selects[1]?.value || '';
                const fileTo = inputs[1]?.value?.trim() || '';
                if (!basinFrom && !fileFrom) {
                    return null;
                }
                return basinTo || fileTo
                    ? `${basinFrom}-${fileFrom} to ${basinTo}-${fileTo}`
                    : `${basinFrom}-${fileFrom}`;
            }).filter(Boolean);

            const rows = [
                buildReviewRow('Assigned attorneys', attorneyCount ? `${attorneyCount} selected` : 'None'),
                buildReviewRow('Assigned clerks', clerkCount ? `${clerkCount} selected` : 'None'),
                buildReviewRow('OSE file numbers', oseEntries.length ? oseEntries.join(', ') : 'None entered'),
            ];

            document.getElementById('reviewRouting').innerHTML = rows.join('');
        }

        function populateReviewParties() {
            const cards = Array.from(document.querySelectorAll('#parties-list > div')).map((card) => {
                const firstNamedField = card.querySelector('[name^="parties["]');
                const match = firstNamedField?.name.match(/parties\[(\d+)\]/);
                const partyIndex = match ? match[1] : '0';
                const role = card.querySelector(`select[name="parties[${partyIndex}][role]"]`)?.value
                    || card.querySelector(`input[name="parties[${partyIndex}][role]"]`)?.value
                    || 'party';
                const type = card.querySelector(`select[name="parties[${partyIndex}][type]"]`)?.value || '';
                const isCompany = type === 'company';
                const name = isCompany
                    ? card.querySelector(`input[name="parties[${partyIndex}][organization]"]`)?.value?.trim()
                    : `${card.querySelector(`input[name="parties[${partyIndex}][first_name]"]`)?.value || ''} ${card.querySelector(`input[name="parties[${partyIndex}][last_name]"]`)?.value || ''}`.trim();
                const email = card.querySelector(`input[name="parties[${partyIndex}][email]"]`)?.value?.trim() || 'No email';
                const representation = card.querySelector(`input[name="parties[${partyIndex}][representation]"]:checked`)?.value || (isCompany ? 'attorney' : 'self');
                const attorneyOption = card.querySelector(`input[name="parties[${partyIndex}][attorney_option]"]:checked`)?.value || '';
                const representationLabel = attorneyOption === 'no_attorney_yet'
                    ? 'No counsel yet'
                    : representation === 'attorney'
                        ? 'Counselled'
                        : 'Self-represented';
                return `
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-slate-900">${name || 'Unnamed party'}</div>
                                <div class="mt-1 text-xs text-slate-500">${toTitle(role.replace(/_/g, ' '))} • ${isCompany ? 'Entity' : 'Individual'} • ${email}</div>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">${representationLabel}</span>
                        </div>
                    </div>
                `;
            });

            document.getElementById('reviewParties').innerHTML = cards.length ? cards.join('') : '<p class="text-sm text-slate-500">No parties added.</p>';
        }

        function populateReviewDocuments() {
            const fileCounts = [
                ['Application', document.querySelector('input[name="documents[application][]"]')?.files.length || 0],
                ['Compliance', document.getElementById('compliance-file-input')?.files.length || 0],
                ['Pleading', document.getElementById('pleading-file-input')?.files.length || 0],
            ];

            let optionalCount = 0;
            document.querySelectorAll('input[name^="optional_docs["][name$="[files][]"]').forEach((input) => {
                optionalCount += input.files.length;
            });
            fileCounts.push(['Supporting', optionalCount]);

            const selectedComplianceType = document.querySelector('input[name="compliance_doc_type"]:checked')?.value;
            const selectedPleadingType = document.querySelector('input[name="pleading_type"]:checked')?.value;

            const rows = [
                buildReviewRow('Application files', `${fileCounts[0][1]}`),
                buildReviewRow('Compliance files', `${fileCounts[1][1]}${selectedComplianceType ? ` (${toTitle(selectedComplianceType.replace(/_/g, ' '))})` : ''}`),
                buildReviewRow('Pleading files', `${fileCounts[2][1]}${selectedPleadingType ? ` (${toTitle(selectedPleadingType.replace(/_/g, ' '))})` : ''}`),
                buildReviewRow('Supporting files', `${fileCounts[3][1]}`),
            ];

            document.getElementById('reviewDocuments').innerHTML = rows.join('');
        }

        function buildReviewRow(label, value) {
            return `
                <div class="flex items-start justify-between gap-4 rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
                    <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">${label}</span>
                    <span class="text-sm font-medium text-slate-900 text-right">${value}</span>
                </div>
            `;
        }

        function toTitle(value) {
            return value.replace(/\b\w/g, (char) => char.toUpperCase());
        }

        function showToSection(index) {
            document.getElementById(`to-section-${index}`).classList.remove('hidden');
            document.getElementById(`add-to-${index}`).classList.add('hidden');
        }

        function hideToSection(index) {
            const toSection = document.getElementById(`to-section-${index}`);
            const addButton = document.getElementById(`add-to-${index}`);

            // Clear the to fields
            toSection.querySelector('select').selectedIndex = 0;
            toSection.querySelector('input').value = '';

            toSection.classList.add('hidden');
            addButton.classList.remove('hidden');
        }

        function addOseNumber() {
            const basinOptions = `<option value="">Select Basin</option>@foreach($basinCodes as $code)<option value="{{ $code->initial }}">{{ $code->initial }} - {{ $code->description }}</option>@endforeach`;
            document.getElementById('ose-numbers').insertAdjacentHTML('beforeend', `
                <div class="flex gap-2 items-center flex-wrap">
                    <div class="flex items-center gap-1">
                        <select name="ose_numbers[${oseCount}][basin_code_from]" class="border-gray-300 rounded-md text-sm">
                            ${basinOptions}
                        </select>
                        <span class="text-sm">-</span>
                        <input type="text" name="ose_numbers[${oseCount}][file_no_from]" placeholder="12345" class="border-gray-300 rounded-md w-20 text-sm">
                    </div>
                    <div id="to-section-${oseCount}" class="flex items-center gap-1 hidden">
                        <span class="text-sm text-gray-600">to</span>
                        <select name="ose_numbers[${oseCount}][basin_code_to]" class="border-gray-300 rounded-md text-sm">
                            ${basinOptions}
                        </select>
                        <span class="text-sm">-</span>
                        <input type="text" name="ose_numbers[${oseCount}][file_no_to]" placeholder="12350" class="border-gray-300 rounded-md w-20 text-sm">
                        <button type="button" onclick="hideToSection(${oseCount})" class="text-red-600 text-xs ml-1">✕</button>
                    </div>
                    <button id="add-to-${oseCount}" type="button" onclick="showToSection(${oseCount})" class="text-blue-600 text-xs">+ Add Range</button>
                </div>
            `);
            oseCount++;
        }

        function addParty(role) {
            const roleTitle = role.charAt(0).toUpperCase() + role.slice(1);
            document.getElementById('parties-list').insertAdjacentHTML('beforeend', `
                <div class="border rounded-lg p-4 bg-gray-50">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="font-medium text-gray-900">${roleTitle} ${partyCount + 1}</h4>
                        <button type="button" onclick="this.parentElement.parentElement.remove()" class="text-red-600 text-sm hover:text-red-800">Remove</button>
                    </div>

                    <input type="hidden" name="parties[${partyCount}][role]" value="${role}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Type *
                                <span class="ml-2 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-blue-700 bg-blue-100 rounded-full cursor-help align-middle"
                                      title="19.25.2.11 (B) and (C) NMAC:&#10;B. An individual may appear as a pro se party. Parties appearing pro se shall be responsible for familiarizing themselves with this rule, the rules of civil procedure for the district courts of New Mexico, the rules of evidence governing non-jury trials for the district courts of New Mexico, the instructions for parties in administrative proceedings, and all other rules of the OSE.&#10;&#10;C. A party that is not an individual shall be represented by an attorney.">i</span>
                            </label>
                            <select name="parties[${partyCount}][type]" required class="mt-1 block w-full border-gray-300 rounded-md" onchange="togglePersonFields(${partyCount})">
                                <option value="">Select Type</option>
                                <option value="individual">Individual</option>
                                <option value="company">Entity (Non-Person)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Service Method</label>
                            <select name="parties[${partyCount}][service_method]" class="mt-1 block w-full border-gray-300 rounded-md">
                                <option value="email">Email</option>
                                <option value="mail">Mail</option>
                            </select>
                        </div>
                    </div>

                    <div id="individual-fields-${partyCount}" class="hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name *</label>
                                <input type="text" name="parties[${partyCount}][first_name]" class="mt-1 block w-full border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                                <input type="text" name="parties[${partyCount}][last_name]" class="mt-1 block w-full border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>

                    <div id="company-fields-${partyCount}" class="hidden">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Entity Name *</label>
                            <input type="text" name="parties[${partyCount}][organization]" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">C/o First Name</label>
                                <input type="text" name="parties[${partyCount}][first_name]" class="mt-1 block w-full border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">C/o Last Name</label>
                                <input type="text" name="parties[${partyCount}][last_name]" class="mt-1 block w-full border-gray-300 rounded-md">
                            </div>
                        </div>
                    </div>

                    <!-- Representation -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Representation</label>
                        <div id="representation-${partyCount}" class="mt-2">
                            <div class="individual-representation hidden">
                                <label class="flex items-center mb-2">
                                    <input type="radio" name="parties[${partyCount}][representation]" value="self" class="mr-2" onchange="toggleAttorneyFields(${partyCount})">
                                    Self-Represented
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="parties[${partyCount}][representation]" value="attorney" class="mr-2" onchange="toggleAttorneyFields(${partyCount})">
                                    Represented by Attorney
                                </label>
                            </div>
                            <div class="company-representation hidden">
                                <input type="hidden" name="parties[${partyCount}][representation]" value="attorney">
                                <p class="text-sm text-gray-600 bg-blue-50 p-2 rounded">Entities must be represented by an attorney</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email *</label>
                            <input type="email" name="parties[${partyCount}][email]" required class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone *</label>
                            <input type="text" name="parties[${partyCount}][phone]" required inputmode="tel" pattern="\\d{3}-\\d{3}-\\d{4}" placeholder="555-555-5555" oninput="formatPhoneInput(this)" class="mt-1 block w-full border-gray-300 rounded-md">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <input type="text" name="parties[${partyCount}][address_line1]" placeholder="Address Line 1" class="mt-1 block w-full border-gray-300 rounded-md">
                        <input type="text" name="parties[${partyCount}][address_line2]" placeholder="Address Line 2 (Optional)" class="mt-2 block w-full border-gray-300 rounded-md">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="text" name="parties[${partyCount}][city]" placeholder="City" class="border-gray-300 rounded-md">
                        <select name="parties[${partyCount}][state]" class="border-gray-300 rounded-md">
                            ${stateOptionsHtml}
                        </select>
                        <input type="text" name="parties[${partyCount}][zip]" placeholder="ZIP" class="border-gray-300 rounded-md">
                    </div>

                    <!-- Attorney Fields -->
                    <div id="attorney-fields-${partyCount}" class="hidden mt-4">
                        <div class="bg-indigo-50 border-2 border-indigo-200 rounded-lg p-4">
                            <h5 class="font-semibold text-indigo-900 mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Attorney Information
                            </h5>

                        <div class="mb-4">
                            <label class="flex items-center mb-2">
                                <input type="radio" name="parties[${partyCount}][attorney_option]" value="existing" class="mr-2" onchange="toggleAttorneyOption(${partyCount})">
                                Select Existing Attorney
                            </label>
                            <select name="parties[${partyCount}][attorney_id]" class="mt-1 block w-full border-gray-300 rounded-md" disabled>
                                <option value="">Choose an attorney...</option>
                                @foreach($attorneys as $attorney)
                                    <option value="{{ $attorney->id }}">
                                        {{ $attorney->name }} - {{ $attorney->email }}
                                        @if($attorney->bar_number) (Bar: {{ $attorney->bar_number }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="flex items-center mb-2">
                                <input type="radio" name="parties[${partyCount}][attorney_option]" value="new" class="mr-2" onchange="toggleAttorneyOption(${partyCount})" checked>
                                Add New Attorney
                            </label>
                            <div id="new-attorney-${partyCount}">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Attorney Name *</label>
                                        <input type="text" name="parties[${partyCount}][attorney_name]" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Attorney Email *</label>
                                        <input type="email" name="parties[${partyCount}][attorney_email]" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Attorney Phone *</label>
                                        <input type="text" name="parties[${partyCount}][attorney_phone]" required inputmode="tel" pattern="\\d{3}-\\d{3}-\\d{4}" placeholder="555-555-5555" oninput="formatPhoneInput(this)" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Bar Number</label>
                                        <input type="text" name="parties[${partyCount}][bar_number]" class="mt-1 block w-full border-gray-300 rounded-md">
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Attorney Address</label>
                                    <input type="text" name="parties[${partyCount}][attorney_address_line1]" placeholder="Address Line 1" class="mt-1 block w-full border-gray-300 rounded-md">
                                    <input type="text" name="parties[${partyCount}][attorney_address_line2]" placeholder="Address Line 2 (Optional)" class="mt-2 block w-full border-gray-300 rounded-md">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                                        <input type="text" name="parties[${partyCount}][attorney_city]" placeholder="City" class="border-gray-300 rounded-md">
                                        <select name="parties[${partyCount}][attorney_state]" class="border-gray-300 rounded-md">
                                            ${stateOptionsHtml}
                                        </select>
                                        <input type="text" name="parties[${partyCount}][attorney_zip]" placeholder="ZIP" class="border-gray-300 rounded-md">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="no-attorney-yet-option-${partyCount}" class="mb-2 hidden">
                            <label class="flex items-center mb-1">
                                <input type="radio" name="parties[${partyCount}][attorney_option]" value="no_attorney_yet" class="mr-2" onchange="toggleAttorneyOption(${partyCount})">
                                No Attorney Yet
                            </label>
                            <p class="text-xs text-gray-600">ALU Clerk can submit now and add counsel later.</p>
                        </div>
                    </div>
                    </div>
                </div>
            `);
            partyCount++;
        }

        function togglePersonFields(index) {
            const typeSelect = document.querySelector(`select[name="parties[${index}][type]"]`);
            const individualFields = document.getElementById(`individual-fields-${index}`);
            const companyFields = document.getElementById(`company-fields-${index}`);
            const individualRep = document.querySelector(`#representation-${index} .individual-representation`);
            const companyRep = document.querySelector(`#representation-${index} .company-representation`);
            const attorneyFields = document.getElementById(`attorney-fields-${index}`);

            if (typeSelect.value === 'individual') {
                individualFields.classList.remove('hidden');
                companyFields.classList.add('hidden');
                // Enable individual name fields and make them required
                individualFields.querySelectorAll('input').forEach(input => {
                    input.disabled = false;
                    if (input.name.includes('[first_name]') || input.name.includes('[last_name]')) {
                        input.required = true;
                    }
                });
                // Disable company fields so they don't submit
                companyFields.querySelectorAll('input').forEach(input => {
                    input.disabled = true;
                    input.required = false;
                });
                if (individualRep) {
                    individualRep.classList.remove('hidden');
                    companyRep.classList.add('hidden');
                    // Disable the hidden representation field in company div
                    const hiddenRepField = companyRep.querySelector('input[type="hidden"]');
                    if (hiddenRepField) hiddenRepField.disabled = true;
                }
                const noAttorneyYetWrapper = document.getElementById(`no-attorney-yet-option-${index}`);
                if (noAttorneyYetWrapper) {
                    noAttorneyYetWrapper.classList.add('hidden');
                }
                const noAttorneyYetRadio = document.querySelector(`input[name="parties[${index}][attorney_option]"][value="no_attorney_yet"]`);
                if (noAttorneyYetRadio?.checked) {
                    const newRadio = document.querySelector(`input[name="parties[${index}][attorney_option]"][value="new"]`);
                    if (newRadio) {
                        newRadio.checked = true;
                    }
                }
                // Hide attorney fields unless attorney is selected
                const attorneySelected = document.querySelector(`input[name="parties[${index}][representation]"][value="attorney"]:checked`);
                if (attorneyFields) {
                    attorneyFields.classList.toggle('hidden', !attorneySelected);
                }
                toggleAttorneyOption(index);
            } else if (typeSelect.value === 'company') {
                individualFields.classList.add('hidden');
                companyFields.classList.remove('hidden');
                // Enable company fields
                companyFields.querySelectorAll('input').forEach(input => {
                    input.disabled = false;
                    if (input.name.includes('[organization]')) {
                        input.required = true;
                    }
                });
                // Disable individual name fields so they don't submit
                individualFields.querySelectorAll('input').forEach(input => {
                    input.disabled = true;
                    input.required = false;
                });
                if (individualRep) {
                    individualRep.classList.add('hidden');
                    companyRep.classList.remove('hidden');
                    // Enable the hidden representation field in company div
                    const hiddenRepField = companyRep.querySelector('input[type="hidden"]');
                    if (hiddenRepField) hiddenRepField.disabled = false;
                }
                const noAttorneyYetWrapper = document.getElementById(`no-attorney-yet-option-${index}`);
                if (noAttorneyYetWrapper) {
                    noAttorneyYetWrapper.classList.remove('hidden');
                }
                // Always show attorney fields for companies
                if (attorneyFields) {
                    attorneyFields.classList.remove('hidden');
                }
                toggleAttorneyOption(index);
            } else {
                individualFields.classList.add('hidden');
                companyFields.classList.add('hidden');
                // Disable all fields when nothing selected
                individualFields.querySelectorAll('input').forEach(input => {
                    input.disabled = true;
                    input.required = false;
                });
                companyFields.querySelectorAll('input').forEach(input => {
                    input.disabled = true;
                    input.required = false;
                });
                if (individualRep) {
                    individualRep.classList.add('hidden');
                    companyRep.classList.add('hidden');
                }
                if (attorneyFields) {
                    attorneyFields.classList.add('hidden');
                }
                const noAttorneyYetWrapper = document.getElementById(`no-attorney-yet-option-${index}`);
                if (noAttorneyYetWrapper) {
                    noAttorneyYetWrapper.classList.add('hidden');
                }
            }
        }

        function toggleAttorneyFields(index) {
            const attorneyFields = document.getElementById(`attorney-fields-${index}`);
            const attorneySelected = document.querySelector(`input[name="parties[${index}][representation]"][value="attorney"]:checked`);

            if (attorneyFields) {
                attorneyFields.classList.toggle('hidden', !attorneySelected);

                // Make attorney fields required when attorney representation is selected
                if (attorneySelected) {
                    const newAttorneyOption = document.querySelector(`input[name="parties[${index}][attorney_option]"][value="new"]:checked`);
                    if (newAttorneyOption) {
                        const newAttorneyDiv = document.getElementById(`new-attorney-${index}`);
                        if (newAttorneyDiv) {
                            newAttorneyDiv.querySelectorAll('input[name*="[attorney_name]"], input[name*="[attorney_email]"], input[name*="[attorney_phone]"]').forEach(input => {
                                if (!input.disabled) input.required = true;
                            });
                        }
                    }
                } else {
                    // Remove required from attorney fields when not selected
                    attorneyFields.querySelectorAll('input').forEach(input => input.required = false);
                }
            }
        }

        function toggleAttorneyOption(index) {
            const option = document.querySelector(`input[name="parties[${index}][attorney_option]"]:checked`)?.value;
            const existingSelect = document.querySelector(`select[name="parties[${index}][attorney_id]"]`);
            const newFields = document.getElementById(`new-attorney-${index}`);
            const newInputs = newFields?.querySelectorAll('input');
            const typeSelect = document.querySelector(`select[name="parties[${index}][type]"]`);
            const attorneyRadioSelected = !!document.querySelector(`input[name="parties[${index}][representation]"][value="attorney"]:checked`);
            const needsAttorneyDetails = (typeSelect?.value === 'company') || attorneyRadioSelected;

            if (option === 'existing') {
                if (existingSelect) {
                    existingSelect.disabled = false;
                    existingSelect.required = true;
                }
                if (newFields) newFields.classList.add('opacity-50');
                if (newInputs) newInputs.forEach(input => {
                    input.disabled = true;
                    input.required = false;
                });
            } else if (option === 'new') {
                if (existingSelect) {
                    existingSelect.disabled = true;
                    existingSelect.value = '';
                    existingSelect.required = false;
                }
                if (newFields) newFields.classList.remove('opacity-50');
                if (newInputs) newInputs.forEach(input => {
                    input.disabled = false;
                    // Make attorney name, email, and phone required for new attorney
                    if (input.name.includes('[attorney_name]') || input.name.includes('[attorney_email]') || input.name.includes('[attorney_phone]')) {
                        input.required = needsAttorneyDetails;
                    }
                });
            } else if (option === 'no_attorney_yet') {
                if (existingSelect) {
                    existingSelect.disabled = true;
                    existingSelect.value = '';
                    existingSelect.required = false;
                }
                if (newFields) newFields.classList.add('opacity-50');
                if (newInputs) newInputs.forEach(input => {
                    input.disabled = true;
                    input.required = false;
                    input.value = '';
                });
                alert('I will be using an Agent and have uploaded the agent authorization form attached with my application');
            }
        }

        function formatPhoneInput(input) {
            if (!input) {
                return;
            }

            const digits = input.value.replace(/\D/g, '').slice(0, 10);
            const parts = [];

            if (digits.length > 0) {
                parts.push(digits.slice(0, 3));
            }
            if (digits.length >= 4) {
                parts.push(digits.slice(3, 6));
            }
            if (digits.length >= 7) {
                parts.push(digits.slice(6, 10));
            }

            input.value = parts.join('-');
        }

        function updatePleadingLabel() {
            const selectedType = document.querySelector('input[name="pleading_type"]:checked');
            const fileLabel = document.getElementById('pleading-file-label');
            const fileInput = document.getElementById('pleading-file-input');

            if (selectedType) {
                const typeText = selectedType.nextElementSibling.querySelector('.font-medium').textContent;
                fileLabel.textContent = `${typeText} Document (PDF) *`;
                fileInput.name = 'documents[pleading][]';
                fileInput.disabled = false;
                fileInput.style.opacity = '1';
                fileInput.required = true;
            } else {
                fileLabel.textContent = 'Select Pleading Type First';
                fileInput.disabled = true;
                fileInput.style.opacity = '0.5';
                fileInput.required = false;
            }
        }

        function updateOptionalDocLabel(index) {
            const select = document.querySelector(`select[name="optional_docs[${index}][type]"]`);
            const fileInput = document.querySelector(`input[name="optional_docs[${index}][files][]"]`);

            if (select.value) {
                fileInput.disabled = false;
                fileInput.style.opacity = '1';
            } else {
                fileInput.disabled = true;
                fileInput.style.opacity = '0.5';
                fileInput.value = '';
            }
        }

        function addOptionalDocument() {
            const optionalDocsContainer = document.getElementById('optional-documents');
            const optionalDocOptions = `@foreach($optionalDocs as $docType)<option value="{{ $docType->code }}">{{ \Illuminate\Support\Str::title($docType->name) }}</option>@endforeach`;

            optionalDocsContainer.insertAdjacentHTML('beforeend', `
                <div class="border rounded-lg p-4 optional-doc-item">
                    <div class="flex justify-between items-start mb-3">
                        <h5 class="font-medium text-gray-700">Optional Document ${optionalDocCount + 1}</h5>
                        <button type="button" onclick="this.closest('.optional-doc-item').remove()" class="text-red-600 text-sm hover:text-red-800">Remove</button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                            <select name="optional_docs[${optionalDocCount}][type]" class="mt-1 block w-full border-gray-300 rounded-md" onchange="updateOptionalDocLabel(${optionalDocCount})">
                                <option value="">Select document type...</option>
                                ${optionalDocOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File Upload</label>
                            <input type="file" name="optional_docs[${optionalDocCount}][files][]" accept=".pdf" multiple class="mt-1 block w-full border-gray-300 rounded-md p-2" disabled>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Select document type first, then upload files. Files will be renamed to: YYYY-MM-DD [Document Type].pdf</p>
                </div>
            `);
            optionalDocCount++;
        }

        // File preview functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add file change listeners for previews
            const fileInputs = {
                'documents[application]': 'application-preview',
                'documents[notice_publication][]': 'notice-preview',
                'documents[request_to_docket]': 'pleading-preview',
                'documents[protest_letter][]': 'protest-preview',
                'documents[supporting][]': 'supporting-preview'
            };

            Object.entries(fileInputs).forEach(([inputName, previewId]) => {
                const input = document.querySelector(`input[name="${inputName}"]`);
                const preview = document.getElementById(previewId);

                if (input && preview) {
                    input.addEventListener('change', function() {
                        if (this.files.length > 0) {
                            if (this.multiple) {
                                const fileNames = Array.from(this.files).map(f => f.name).join(', ');
                                preview.textContent = `✓ ${this.files.length} file(s) selected: ${fileNames}`;
                            } else {
                                preview.textContent = `✓ Selected: ${this.files[0].name}`;
                            }
                            preview.classList.remove('hidden');
                        } else {
                            preview.classList.add('hidden');
                        }
                    });
                }
            });

            const form = caseCreateForm;
            form.addEventListener('submit', function(event) {
                event.preventDefault();

                if (currentWizardStep < wizardStepMeta.length - 1) {
                    if (!validateWizardStep(currentWizardStep)) {
                        return;
                    }

                    if (currentWizardStep === wizardStepMeta.length - 2) {
                        populateReviewStep();
                    }

                    goToWizardStep(Math.min(currentWizardStep + 1, wizardStepMeta.length - 1));
                    return;
                }

                submitCaseForm(document.getElementById('caseActionInput')?.value || 'submit');
            });
        });

        // File size validation
        function validateFileSize(input, maxSizeMB = 200) {
            const files = input.files;
            for (let file of files) {
                if (file.size > maxSizeMB * 1024 * 1024) {
                    alert(`File "${file.name}" is too large. Maximum size is ${maxSizeMB}MB.`);
                    input.value = '';
                    return false;
                }
            }
            return true;
        }

        // Add file size validation to all file inputs
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', function() {
                    validateFileSize(this);
                });
            });
        });
    </script>

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Case {{ $case->case_no }}</h2>
    </x-slot>

    @php
        $openRejection = $case->rejections->firstWhere('status', 'open');
        $rejectionHistory = $case->rejections;
        $rejectionCategoryLabels = [
            'missing_document' => 'Missing Document',
            'caption_issue' => 'Caption Issue',
            'party_issue' => 'Party Issue',
            'service_issue' => 'Service Issue',
            'ose_issue' => 'OSE Issue',
            'document_issue' => 'Document Issue',
            'filing_issue' => 'Filing Issue',
            'other' => 'Other',
        ];
        $documentCorrectionCategoryLabels = [
            'missing_document' => 'Missing Document',
            'caption_issue' => 'Caption Issue',
            'party_issue' => 'Party Issue',
            'service_issue' => 'Service Issue',
            'ose_issue' => 'OSE Issue',
            'document_issue' => 'Document Issue',
            'filing_issue' => 'Filing Issue',
            'other' => 'Other',
        ];
        $showDocumentCorrectionMap = $case->documents->mapWithKeys(function ($document) {
            $latestCorrection = $document->correctionCycles->firstWhere('status', 'open')
                ?? $document->correctionCycles->firstWhere('status', 'resubmitted');

            return [
                $document->id => $latestCorrection ? [
                    'id' => $latestCorrection->id,
                    'status' => $latestCorrection->status,
                    'summary' => $latestCorrection->summary,
                    'correction_type' => $latestCorrection->correction_type,
                    'items' => $latestCorrection->items->map(fn ($item) => [
                        'id' => $item->id,
                        'category' => $item->category,
                        'item_note' => $item->item_note,
                        'required_action' => $item->required_action,
                        'resolution_note' => $item->resolution_note,
                    ])->values()->all(),
                ] : null,
            ];
        });
        $showDocumentMetaMap = $case->documents->mapWithKeys(fn ($document) => [
            $document->id => [
                'id' => $document->id,
                'doc_type' => $document->doc_type,
                'doc_type_label' => $document->doc_type_label,
                'pleading_type' => $document->pleading_type,
                'custom_title' => $document->custom_title,
                'original_filename' => $document->original_filename,
                'uploaded_by_user_id' => $document->uploaded_by_user_id,
            ],
        ]);
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Case Header -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        @php
                            $applicantNames = $case->parties
                                ->where('role', 'applicant')
                                ->map(fn($party) => $party->person->full_name)
                                ->filter()
                                ->values();

                            $displayApplicants = '';
                            if ($applicantNames->count() === 1) {
                                $displayApplicants = $applicantNames[0];
                            } elseif ($applicantNames->count() === 2) {
                                $displayApplicants = $applicantNames[0] . ' and ' . $applicantNames[1];
                            } elseif ($applicantNames->count() > 2) {
                                $displayApplicants = $applicantNames[0] . ' and ' . $applicantNames[1] . ' et al.';
                            }
                        @endphp
                        @if($displayApplicants)
                            <div class="text-xl font-semibold text-gray-900 mb-1">
                                {{ $displayApplicants }}
                            </div>
                        @endif
                        <h3 class="text-lg font-medium">{{ $case->case_no }}</h3>
                        <p class="text-sm text-gray-600">{{ ucfirst($case->case_type) }} Case</p>
                        <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full
                            {{ $case->status === 'active' ? 'bg-green-100 text-green-800' :
                               ($case->status === 'draft' ? 'bg-gray-100 text-gray-800' :
                               ($case->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800')) }}">
                            {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                        </span>
                    </div>
                    <div>
                        <strong>Key Dates:</strong>
                        <div class="text-sm mt-1 space-y-1">
                            <div>Created: {{ $case->created_at->format('M j, Y g:i A') }}</div>
                            @if($case->submitted_at)
                                <div class="text-blue-600">Submitted: {{ $case->submitted_at->format('M j, Y g:i A') }}</div>
                            @endif
                            @if($case->accepted_at)
                                <div class="text-green-600">Accepted: {{ $case->accepted_at->format('M j, Y g:i A') }}</div>
                            @endif
                            @if($case->closed_at)
                                <div class="text-gray-600">Closed: {{ $case->closed_at->format('M j, Y g:i A') }}</div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <strong>Adjudication Litigation Unit Office:</strong>
                        <div class="text-sm mt-1">
                            @if($case->wrd_office_label)
                                <div>{{ $case->wrd_office_label }}</div>
                                @if(!empty($case->wrd_office_details))
                                    <div class="text-gray-600">{{ $case->wrd_office_details['address'] }}</div>
                                    <div class="text-gray-600">{{ $case->wrd_office_details['city'] }}, {{ $case->wrd_office_details['state'] }} {{ $case->wrd_office_details['zip'] }}</div>
                                @endif
                            @else
                                <span class="text-gray-500">Not set</span>
                            @endif
                        </div>

                        <strong class="mt-3 block">Assigned ALU Attorneys:</strong>
                        <div class="text-sm mt-1">
                            @if($case->aluAttorneys->count() > 0)
                                @foreach($case->aluAttorneys as $attorney)
                                    <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $attorney->getDisplayName() }}</span>
                                @endforeach
                            @else
                                <span class="text-gray-500">Not assigned</span>
                            @endif
                            @if(auth()->user()->canAssignAttorneys())
                                <a href="{{ route('cases.assign-attorney', $case) }}" class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                                    {{ $case->aluAttorneys->count() > 0 ? 'Manage' : 'Assign' }}
                                </a>
                            @endif
                        </div>



                        <strong class="mt-3 block">Assigned ALU Clerks:</strong>
                        <div class="text-sm mt-1">
                            @if($case->aluClerks->count() > 0)
                                @foreach($case->aluClerks as $clerk)
                                    <span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $clerk->getDisplayName() }}</span>
                                @endforeach
                            @else
                                <span class="text-gray-500">Not assigned</span>
                            @endif
                            @if(auth()->user()->canAssignAttorneys())
                                <a href="{{ route('cases.assign-alu-clerk', $case) }}" class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                                    {{ $case->aluClerks->count() > 0 ? 'Manage' : 'Assign' }}
                                </a>
                            @endif
                        </div>

                        @if(auth()->user()->canAssignAttorneys() || auth()->user()->isHearingUnit())
                        <strong class="mt-3 block">Assigned WRD Experts:</strong>
                        <div class="text-sm mt-1">
                            @if($case->wrds->count() > 0)
                                @foreach($case->wrds as $wrd)
                                    <span class="inline-block bg-orange-100 text-orange-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $wrd->getDisplayName() }}</span>
                                @endforeach
                            @else
                                <span class="text-gray-500">Not assigned</span>
                            @endif
                            @if(auth()->user()->canAssignAttorneys())
                                <a href="{{ route('cases.assign-wrd', $case) }}" class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                                    {{ $case->wrds->count() > 0 ? 'Manage' : 'Assign' }}
                                </a>
                            @endif
                        </div>
                          <strong class="mt-3 block">Assigned Hydrology Experts:</strong>
                        <div class="text-sm mt-1">
                            @if($case->hydrologyExperts->count() > 0)
                                @foreach($case->hydrologyExperts as $expert)
                                    <span class="inline-block bg-green-100 text-green-800 px-2 py-1 rounded text-xs mr-1 mb-1">{{ $expert->getDisplayName() }}</span>
                                @endforeach
                            @else
                                <span class="text-gray-500">Not assigned</span>
                            @endif
                            @if(auth()->user()->canAssignHydrologyExperts())
                                <a href="{{ route('cases.assign-hydrology-expert', $case) }}" class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                                    {{ $case->hydrologyExperts->count() > 0 ? 'Manage' : 'Assign' }}
                                </a>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                <div class="mt-4">
                    <strong>Caption:</strong>
                    <p class="mt-1 text-sm">{{ $case->caption }}</p>
                </div>

                @if($openRejection)
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h4 class="font-medium text-red-800 mb-1">Case Rejected by HU</h4>
                            <p class="text-sm text-red-700">{{ $openRejection->reason_summary }}</p>
                            <p class="text-xs text-red-600 mt-2">
                                Rejected {{ $openRejection->rejected_at?->format('M j, Y g:i A') }}
                                @if($openRejection->rejectedBy)
                                    by {{ $openRejection->rejectedBy->getDisplayName() }}
                                @endif
                            </p>
                        </div>
                        <div class="text-xs text-red-700 font-medium">
                            {{ $openRejection->openItems->count() }} open correction {{ $openRejection->openItems->count() === 1 ? 'item' : 'items' }}
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        @foreach($openRejection->items as $item)
                            <div class="bg-white border border-red-200 rounded-md p-3">
                                <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div class="text-xs font-semibold uppercase tracking-wide text-red-700">
                                            {{ $rejectionCategoryLabels[$item->category] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $item->category)) }}
                                        </div>
                                        <div class="text-sm text-gray-900 mt-1">{{ $item->item_note }}</div>
                                        @if($item->required_action)
                                            <div class="text-sm text-gray-700 mt-2">
                                                <strong>Required Action:</strong> {{ $item->required_action }}
                                            </div>
                                        @endif
                                    </div>
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $item->resolved_at ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $item->resolved_at ? 'Resolved for Resubmission' : 'Open' }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p class="text-xs text-red-600 mt-3">Open each correction item on the edit screen, document the fix, and resubmit only after every item is resolved.</p>
                </div>
                @elseif($case->status === 'rejected' && isset($case->metadata['rejection_reason']))
                <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <h4 class="font-medium text-red-800 mb-2">❌ Case Rejected by HU</h4>
                    <p class="text-sm text-red-700">{{ $case->metadata['rejection_reason'] }}</p>
                    <p class="text-xs text-red-600 mt-2">Please make the necessary corrections and resubmit.</p>
                </div>
                @endif

                @if(auth()->user()->canCreateCase() && in_array($case->status, ['draft', 'rejected']))
                <div class="mt-4 flex space-x-3">
                    <a href="{{ route('cases.edit', $case) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm transition-colors">
                        {{ $case->status === 'rejected' ? 'Fix & Resubmit Case' : 'Edit Case' }}
                    </a>
                    @if($case->status === 'draft' && auth()->user()->canSubmitToHU())
                    <button onclick="showSubmitModal()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm transition-colors">
                        Submit to HU
                    </button>
                    @endif
                </div>
                @endif
            </div>

            @if($rejectionHistory->count() > 0)
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium">Rejection History</h3>
                    <span class="text-sm text-gray-500">{{ $rejectionHistory->count() }} cycle{{ $rejectionHistory->count() === 1 ? '' : 's' }}</span>
                </div>
                <div class="space-y-4">
                    @foreach($rejectionHistory as $rejection)
                        <div class="border rounded-lg p-4 {{ $rejection->status === 'open' ? 'border-red-200 bg-red-50' : 'border-gray-200 bg-gray-50' }}">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $rejection->reason_summary }}</div>
                                    <div class="text-xs text-gray-600 mt-1">
                                        Rejected {{ $rejection->rejected_at?->format('M j, Y g:i A') }}
                                        @if($rejection->rejectedBy)
                                            by {{ $rejection->rejectedBy->getDisplayName() }}
                                        @endif
                                        @if($rejection->resubmitted_at)
                                            • Resubmitted {{ $rejection->resubmitted_at->format('M j, Y g:i A') }}
                                            @if($rejection->resubmittedBy)
                                                by {{ $rejection->resubmittedBy->getDisplayName() }}
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $rejection->status === 'open' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $rejection->status === 'open' ? 'Open Correction Cycle' : 'Resubmitted' }}
                                </span>
                            </div>
                            <div class="mt-3 space-y-2">
                                @foreach($rejection->items as $item)
                                    <div class="bg-white rounded border border-gray-200 px-3 py-2">
                                        <div class="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
                                            <div>
                                                <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                                                    {{ $rejectionCategoryLabels[$item->category] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $item->category)) }}
                                                </div>
                                                <div class="text-sm text-gray-900 mt-1">{{ $item->item_note }}</div>
                                                @if($item->required_action)
                                                    <div class="text-xs text-gray-700 mt-1"><strong>Required:</strong> {{ $item->required_action }}</div>
                                                @endif
                                                @if($item->resolution_note)
                                                    <div class="text-xs text-green-700 mt-2"><strong>Resolution:</strong> {{ $item->resolution_note }}</div>
                                                @endif
                                            </div>
                                            <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $item->resolved_at ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $item->resolved_at ? 'Resolved' : 'Open' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            {{-- Legacy document-correction block removed from rejection history.
                            <div class="mt-3 rounded-lg border {{ $latestDocCorrection->status === 'open' ? 'border-red-200 bg-red-50' : 'border-blue-200 bg-blue-50' }} p-3">
                                <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <div class="text-sm font-semibold {{ $latestDocCorrection->status === 'open' ? 'text-red-800' : 'text-blue-800' }}">
                                            {{ $latestDocCorrection->correction_type === 'rejected' ? 'Document Rejected by HU' : 'Document Correction Requested' }}
                                        </div>
                                        <div class="text-sm mt-1 {{ $latestDocCorrection->status === 'open' ? 'text-red-700' : 'text-blue-700' }}">{{ $latestDocCorrection->summary }}</div>
                                        <div class="text-xs mt-1 text-gray-600">
                                            Requested {{ $latestDocCorrection->requested_at?->format('M j, Y g:i A') }}
                                            @if($latestDocCorrection->requestedBy)
                                                by {{ $latestDocCorrection->requestedBy->getDisplayName() }}
                                            @endif
                                            @if($latestDocCorrection->resubmitted_at)
                                                • Corrected submission received {{ $latestDocCorrection->resubmitted_at->format('M j, Y g:i A') }}
                                            @endif
                                        </div>
                                    </div>
                                    <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $latestDocCorrection->status === 'open' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $latestDocCorrection->status === 'open' ? 'Awaiting Corrected Filing' : 'Pending HU Review' }}
                                    </span>
                                </div>
                                <div class="mt-3 space-y-2">
                                    @foreach($latestDocCorrection->items as $item)
                                    <div class="rounded border border-white bg-white/70 px-3 py-2">
                                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                                            {{ $documentCorrectionCategoryLabels[$item->category] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $item->category)) }}
                                        </div>
                                        <div class="text-sm text-gray-900 mt-1">{{ $item->item_note }}</div>
                                        @if($item->required_action)
                                        <div class="text-xs text-gray-700 mt-1"><strong>Required:</strong> {{ $item->required_action }}</div>
                                        @endif
                                        @if($item->resolution_note)
                                        <div class="text-xs text-green-700 mt-2"><strong>Resolution:</strong> {{ $item->resolution_note }}</div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            --}}
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Parties & Service List -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Parties & Service List</h3>
                    <div class="flex items-center gap-2">
                        @if(auth()->user()->isHearingUnit())
                            <a href="{{ route('cases.service-list.download', $case) }}" class="bg-gray-100 text-gray-800 px-4 py-2 rounded-md text-sm hover:bg-gray-200">
                                Download Service List
                            </a>
                        @endif
                        @if(auth()->user()->canCreateCase() || auth()->user()->isHearingUnit())
                            <a href="{{ route('cases.parties.manage', $case) }}" class="bg-blue-500 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-600">
                                {{ (auth()->user()->canCreateCase() && !in_array($case->status, ['draft', 'rejected'])) ? 'View Parties' : 'Manage Parties' }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium mb-2">Case Parties</h4>
                        @php
                            $sortedParties = $case->parties
                                ->reject(fn($party) => $party->isWrdAgencyParty())
                                ->whereNotIn('role', ['counsel', 'paralegal', 'agent'])
                                ->sortBy(function($party) {
                                    $order = ['applicant' => 1, 'protestant' => 2, 'respondent' => 3, 'intervenor' => 4];
                                    return $order[$party->role] ?? 99;
                                })
                                ->values();
                        @endphp
                        @foreach($sortedParties as $index => $party)
                        <div class="border rounded-lg mb-3 bg-gray-50">
                            <div class="p-3 cursor-pointer" onclick="togglePartyDetails({{ $index }})">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-medium">{{ $party->person->full_name }}</div>
                                        <div class="text-sm text-gray-600">{{ ucfirst($party->role) }}</div>
                                        <div class="text-xs text-gray-500">{{ $party->person->email }}</div>
                                        @php
                                            $hasAttorney = $party->attorneys->count() > 0;
                                            $hasAgent = $party->agents->count() > 0;
                                        @endphp
                                        @if($hasAttorney)
                                            <div class="text-xs text-blue-600 mt-1">
                                                <span class="bg-blue-100 px-2 py-1 rounded">Represented by Attorney</span>
                                            </div>
                                        @elseif($hasAgent)
                                            <div class="text-xs text-amber-700 mt-1">
                                                <span class="bg-amber-100 px-2 py-1 rounded">Represented by Agent</span>
                                            </div>
                                        @elseif($party->person->type === 'company')
                                            <div class="text-xs text-gray-500 mt-1">
                                                <span class="bg-gray-100 px-2 py-1 rounded">No representative yet</span>
                                            </div>
                                        @elseif($party->role !== 'paralegal')
                                            <div class="text-xs text-gray-500 mt-1">
                                                <span class="bg-gray-100 px-2 py-1 rounded">Self-Represented</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($hasAttorney)
                                            <button onclick="event.stopPropagation(); manageAttorney({{ $party->id }})" class="text-xs text-green-600 hover:text-green-800">Attorney</button>
                                        @endif
                                        <svg class="w-4 h-4 text-gray-400 transform transition-transform party-chevron-{{ $index }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div id="party-details-{{ $index }}" class="hidden px-3 pb-3 border-t bg-white">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3 text-sm">
                                    @if($party->person->type === 'individual')
                                        @if($party->person->title)
                                            <div><strong>Title:</strong> {{ $party->person->title }}</div>
                                        @endif
                                        @if($party->person->prefix || $party->person->middle_name || $party->person->suffix)
                                            <div><strong>Full Name:</strong> {{ trim($party->person->prefix . ' ' . $party->person->first_name . ' ' . $party->person->middle_name . ' ' . $party->person->last_name . ' ' . $party->person->suffix) }}</div>
                                        @endif
                                    @else
                                        @if($party->person->organization)
                                            <div><strong>Organization:</strong> {{ $party->person->organization }}</div>
                                        @endif
                                        @if($party->person->first_name || $party->person->last_name)
                                            <div><strong>Principal Contact:</strong> {{ trim(($party->person->first_name ?? '') . ' ' . ($party->person->last_name ?? '')) }}</div>
                                        @endif
                                    @endif

                                    @if($party->person->phone_mobile)
                                        <div><strong>Mobile:</strong> {{ $party->person->phone_mobile }}</div>
                                    @endif
                                    @if($party->person->phone_office)
                                        <div><strong>Office:</strong> {{ $party->person->phone_office }}</div>
                                    @endif

                                    @if($party->person->address_line1 || $party->person->city || $party->person->state)
                                        <div class="md:col-span-2">
                                            <strong>Address:</strong>
                                            <div class="mt-1">
                                                @if($party->person->address_line1)
                                                    <div>{{ $party->person->address_line1 }}</div>
                                                @endif
                                                @if($party->person->address_line2)
                                                    <div>{{ $party->person->address_line2 }}</div>
                                                @endif
                                                @if($party->person->city || $party->person->state || $party->person->zip)
                                                    <div>{{ $party->person->city }}{{ $party->person->city && ($party->person->state || $party->person->zip) ? ', ' : '' }}{{ $party->person->state }} {{ $party->person->zip }}</div>
                                                @endif

                                            </div>
                                        </div>
                                    @endif

                                    @if($hasAttorney)
                                        <div class="md:col-span-2 mt-3 pt-3 border-t">
                                            <strong class="text-blue-700">Attorney Information:</strong>
                                            <div class="mt-2 bg-blue-50 p-3 rounded">
                                                @php
                                                    $attorneyParalegals = $case->parties->where('role', 'paralegal')->where('client_party_id', $party->id);
                                                @endphp
                                                @foreach($party->attorneys as $attorneyParty)
                                                    @php
                                                        $attorney = \App\Models\Attorney::where('email', $attorneyParty->person->email)->first();
                                                    @endphp
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-3 last:mb-0">
                                                        <div><strong>Name:</strong> {{ $attorneyParty->person->full_name }}</div>
                                                        <div><strong>Email:</strong> {{ $attorneyParty->person->email }}</div>
                                                        @if($attorneyParty->person->phone_office)
                                                            <div><strong>Phone:</strong> {{ $attorneyParty->person->phone_office }}</div>
                                                        @endif
                                                        @if($attorney && $attorney->bar_number)
                                                            <div><strong>Bar Number:</strong> {{ $attorney->bar_number }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                                @if($attorneyParalegals->isNotEmpty())
                                                    <div class="mt-3 pt-3 border-t border-blue-200">
                                                        <strong class="text-purple-700 text-sm">Paralegals:</strong>
                                                        <div class="mt-2 space-y-2">
                                                            @foreach($attorneyParalegals as $paralegal)
                                                            <div class="bg-purple-50 p-2 rounded text-sm">
                                                                <div class="font-medium">{{ $paralegal->person->full_name }}</div>
                                                                <div class="text-xs text-gray-600">{{ $paralegal->person->email }}</div>
                                                                @if($paralegal->person->phone_office)
                                                                    <div class="text-xs text-gray-600">Phone: {{ $paralegal->person->phone_office }}</div>
                                                                @endif
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if($hasAgent)
                                        <div class="md:col-span-2 mt-3 pt-3 border-t">
                                            <strong class="text-amber-700">Agent Information:</strong>
                                            <div class="mt-2 bg-amber-50 p-3 rounded">
                                                @foreach($party->agents as $agentParty)
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-3 last:mb-0">
                                                        <div><strong>Name:</strong> {{ $agentParty->person->full_name }}</div>
                                                        <div><strong>Email:</strong> {{ $agentParty->person->email }}</div>
                                                        @if($agentParty->person->phone_office)
                                                            <div><strong>Phone:</strong> {{ $agentParty->person->phone_office }}</div>
                                                        @endif
                                                        @if($agentParty->person->organization)
                                                            <div><strong>Organization:</strong> {{ $agentParty->person->organization }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                        @php
                            $aluPartyIndex = $sortedParties->count();
                        @endphp
                        <div class="border rounded-lg mb-3 bg-gray-50">
                            <div class="p-3 cursor-pointer" onclick="togglePartyDetails({{ $aluPartyIndex }})">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="font-medium">Water Right Division</div>
                                        <div class="text-sm text-gray-600">Administrative Litigation Unit</div>
                                        @if($case->wrd_office_label)
                                            <div class="text-xs text-gray-500">{{ $case->wrd_office_label }}</div>
                                        @endif
                                    </div>
                                    <svg class="w-4 h-4 text-gray-400 transform transition-transform party-chevron-{{ $aluPartyIndex }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div id="party-details-{{ $aluPartyIndex }}" class="hidden px-3 pb-3 border-t bg-white">
                                <div class="mt-3 text-sm">
                                    @if($case->wrd_office_label)
                                        <div class="font-medium text-gray-900">{{ $case->wrd_office_label }}</div>
                                        @if(!empty($case->wrd_office_details))
                                            <div class="mt-1 text-gray-600">{{ $case->wrd_office_details['address'] }}</div>
                                            <div class="text-gray-600">{{ $case->wrd_office_details['city'] }}, {{ $case->wrd_office_details['state'] }} {{ $case->wrd_office_details['zip'] }}</div>
                                            <div class="text-gray-600">{{ $case->wrd_office_details['phone'] }}</div>
                                        @endif
                                    @else
                                        <div class="text-gray-500">WRD office not set on this case.</div>
                                    @endif

                                    <div class="mt-4">
                                        <div class="text-sm font-medium text-gray-900 mb-2">Representing Attorneys</div>
                                        @if($case->aluAttorneys->count() > 0)
                                            <div class="space-y-2">
                                                @foreach($case->aluAttorneys as $attorney)
                                                <div class="rounded-lg border border-blue-100 bg-blue-50 p-3">
                                                    <div class="font-medium text-gray-900">{{ $attorney->getDisplayName() }}</div>
                                                    <div class="text-sm text-gray-600">{{ $attorney->email }}</div>
                                                </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500">No ALU attorneys are assigned to represent Water Right Division on this case.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($case->parties->isEmpty())
                            <p class="text-gray-500 text-sm">No parties assigned</p>
                        @endif

                        @php
                            $userIsCounsel = auth()->user()->isAttorney() && $case->parties->where('role', 'counsel')->filter(function($party) {
                                return $party->person->email === auth()->user()->email;
                            })->isNotEmpty();
                            $userIsAssignedAluAttorney = auth()->user()->isALUAttorney() && $case->aluAttorneys->contains('id', auth()->id());

                            $counselParty = $userIsCounsel ? $case->parties->where('role', 'counsel')->filter(function($party) {
                                return $party->person->email === auth()->user()->email;
                            })->first() : null;

                            if ($counselParty) {
                                $userParalegals = $case->parties->where('role', 'paralegal')->filter(function($p) use ($counselParty) {
                                    return $p->client_party_id === $counselParty->client_party_id;
                                });
                            } elseif ($userIsAssignedAluAttorney) {
                                $userParalegals = $case->assignments
                                    ->where('assignment_type', 'alu_paralegal')
                                    ->where('assigned_by', auth()->id());
                            } else {
                                $userParalegals = collect();
                            }
                        @endphp

                        @if($userIsCounsel || $userIsAssignedAluAttorney)
                        <div class="mt-4 border-t pt-4">
                            <div class="flex justify-between items-center mb-2">
                                <h5 class="font-medium text-sm">My Paralegals</h5>
                                <button onclick="showAddParalegalModal()" class="text-xs bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">+ Add Paralegal</button>
                            </div>
                            @if($userParalegals->isEmpty())
                                <p class="text-gray-500 text-xs">No paralegals added</p>
                            @else
                                @foreach($userParalegals as $paralegal)
                                <div class="flex justify-between items-center py-2 border-b text-sm">
                                    <div>
                                        <div class="font-medium">{{ $userIsAssignedAluAttorney ? $paralegal->user?->name : $paralegal->person->full_name }}</div>
                                        <div class="text-xs text-gray-600">{{ $userIsAssignedAluAttorney ? $paralegal->user?->email : $paralegal->person->email }}</div>
                                    </div>
                                    <button onclick="removeParalegal({{ $paralegal->id }})" class="text-xs text-red-600 hover:text-red-800">Remove</button>
                                </div>
                                @endforeach
                            @endif
                        </div>
                        @endif
                    </div>
                    <div>
                        <h4 class="font-medium mb-2">Service List</h4>
                        @php
                            $renderedServiceEmails = $case->serviceList
                                ->reject(fn($service) => strtoupper(trim((string) ($service->person->organization ?? ''))) === 'WATER RIGHTS DIVISION')
                                ->pluck('email')
                                ->filter()
                                ->map(fn($email) => strtolower(trim($email)));
                        @endphp
                        @foreach($case->serviceList->reject(fn($service) => strtoupper(trim((string) ($service->person->organization ?? ''))) === 'WATER RIGHTS DIVISION') as $service)
                        <div class="py-2 border-b">
                            <div class="font-medium">{{ $service->person->full_name }}</div>
                            <div class="text-sm text-gray-600">{{ $service->email }} • {{ ucfirst($service->service_method) }}</div>
                        </div>
                        @endforeach

                        @foreach($case->aluClerks as $clerk)
                            @php $email = strtolower(trim($clerk->email ?? '')); @endphp
                            @if($email !== '' && !$renderedServiceEmails->contains($email))
                                <div class="py-2 border-b">
                                    <div class="font-medium">{{ $clerk->getDisplayName() }}</div>
                                    <div class="text-sm text-gray-600">{{ $clerk->email }} • ALU Clerk</div>
                                </div>
                                @php $renderedServiceEmails->push($email); @endphp
                            @endif
                        @endforeach

                        @foreach($case->aluAttorneys as $attorney)
                            @php $email = strtolower(trim($attorney->email ?? '')); @endphp
                            @if($email !== '' && !$renderedServiceEmails->contains($email))
                                <div class="py-2 border-b">
                                    <div class="font-medium">{{ $attorney->getDisplayName() }}</div>
                                    <div class="text-sm text-gray-600">{{ $attorney->email }} • ALU Attorney</div>
                                </div>
                                @php $renderedServiceEmails->push($email); @endphp
                            @endif
                        @endforeach

                        @foreach($case->wrds as $wrd)
                            @php $email = strtolower(trim($wrd->email ?? '')); @endphp
                            @if($email !== '' && !$renderedServiceEmails->contains($email))
                                <div class="py-2 border-b">
                                    <div class="font-medium">{{ $wrd->getDisplayName() }}</div>
                                    <div class="text-sm text-gray-600">{{ $wrd->email }} • WRD Expert</div>
                                </div>
                                @php $renderedServiceEmails->push($email); @endphp
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- OSE File Numbers -->
            @if($case->oseFileNumbers->count() > 0)
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">OSE File Numbers</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach($case->oseFileNumbers as $ose)
                    <div class="border rounded p-3">
                        <div class="text-sm">{{ $ose->file_no_from }}{{ $ose->file_no_to ? ' - ' . $ose->file_no_to : '' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- HU Validation Checklist -->
            @if($case->status === 'submitted_to_hu' && auth()->user()->isHearingUnit())
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">HU Validation Checklist</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @php
                        $hasApplication = $case->documents->where('doc_type', 'application')->count() > 0;
                        $hasRequest = $case->documents->whereIn('pleading_type', ['request_to_docket', 'request_pre_hearing'])->count() > 0;
                        $namingOk = $case->documents->filter(function($doc) {
                            return !preg_match('/^\d{4}-\d{2}-\d{2} - .+/', $doc->original_filename);
                        })->count() === 0;
                        $allPdfs = $case->documents->where('mime', '!=', 'application/pdf')->where('doc_type', '!=', 'notice_publication')->count() === 0;
                    @endphp

                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $hasApplication ? 'bg-green-500' : 'bg-red-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $hasApplication ? '✓' : '✗' }}
                        </span>
                        Application PDF Present
                    </div>

                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $hasRequest ? 'bg-green-500' : 'bg-red-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $hasRequest ? '✓' : '✗' }}
                        </span>
                        Pleading Document Present
                    </div>

                    <div class="flex items-center">
                        <span class="w-6 h-6 rounded-full {{ $namingOk ? 'bg-green-500' : 'bg-yellow-500' }} text-white text-xs flex items-center justify-center mr-3">
                            {{ $namingOk ? '✓' : '!' }}
                        </span>
                        Filename Convention {{ $namingOk ? 'Compliant' : 'Issues' }}
                    </div>
                </div>

                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                    <p class="text-sm text-blue-800">
                        <strong>Next Steps:</strong> After reviewing the checklist, go to the Documents section below to Accept or Reject the case and manage individual documents.
                    </p>
                </div>
            </div>
            @endif

            <!-- Documents -->
            <div class="bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Documents ({{ $case->documents->count() }})</h3>
                    <div class="flex space-x-2">
                        @if(auth()->user()->canWriteCase() || auth()->user()->isHearingUnit())
                        <a href="{{ route('cases.documents.manage', $case) }}" class="bg-purple-500 text-white px-4 py-2 rounded-md text-sm hover:bg-purple-600">{{ (auth()->user()->canCreateCase() && !in_array($case->status, ['draft', 'rejected'])) ? 'View Documents' : 'Manage Documents' }}</a>
                        @endif
                        @if(!in_array($case->status, ['closed', 'archived']))
                            @if(auth()->user()->canUploadDocumentsToCase($case))
                            <button onclick="showUploadModal()" class="bg-green-500 text-white px-4 py-2 rounded-md text-sm hover:bg-green-600">{{ auth()->user()->isHearingUnit() ? '+ Issue Order or Notice' : 'File Documents' }}</button>
                            @endif
                        @endif
                        @if($case->status === 'submitted_to_hu' && in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'hu_clerk']))
                        <button onclick="showAcceptanceModal()" class="bg-green-500 text-white px-4 py-2 rounded-md text-sm hover:bg-green-600">Accept Case</button>
                        <button onclick="showRejectModal()" class="bg-red-500 text-white px-4 py-2 rounded-md text-sm hover:bg-red-600">Reject Case</button>
                        @endif

                        @if($case->status === 'active' && in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'hu_clerk']))
                        <button onclick="showCloseModal()" class="bg-orange-500 text-white px-4 py-2 rounded-md text-sm hover:bg-orange-600">Close Case</button>
                        @elseif($case->status === 'closed')
                        <span class="bg-orange-100 text-orange-800 px-4 py-2 rounded-md text-sm font-medium">📁 Case Closed</span>
                        @if(auth()->user()->getCurrentRole() === 'hu_admin')
                        <button onclick="showReopenModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">Reopen Case</button>
                        @endif
                        @elseif($case->status === 'archived')
                        <span class="bg-gray-100 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">📦 Case Archived</span>
                        @endif
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="mb-4 flex flex-wrap gap-4">
                    <input type="text" id="searchInput" placeholder="Search documents..." class="border-gray-300 rounded-md text-sm flex-1 min-w-64">
                    <select id="docTypeFilter" class="border-gray-300 rounded-md text-sm">
                        <option value="">All Types</option>
                        @php
                            $documentTypes = \App\Models\DocumentType::where('is_active', true)->orderBy('name')->get();
                        @endphp
                        @foreach($documentTypes as $docType)
                            <option value="{{ $docType->code }}">{{ \Illuminate\Support\Str::title($docType->name) }}</option>
                        @endforeach
                    </select>
                    <select id="statusFilter" class="border-gray-300 rounded-md text-sm">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="stamped">E-Stamped</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <select id="perPageSelect" class="border-gray-300 rounded-md text-sm">
                        <option value="10">10 per page</option>
                        <option value="25" selected>25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>

                <div id="documentsContainer" class="space-y-2">
                    @foreach($case->documents->sortByDesc('uploaded_at') as $doc)
                    @php
                        $latestDocCorrection = $doc->correctionCycles->firstWhere('status', 'open')
                            ?? $doc->correctionCycles->firstWhere('status', 'resubmitted');
                    @endphp
                    <div class="flex items-center justify-between p-4 border rounded hover:bg-gray-50 document-item"
                         data-doc-type="{{ $doc->doc_type }}"
                         data-status="{{ $doc->stamped ? 'stamped' : ($doc->approved ? 'accepted' : ($doc->rejected_reason ? 'rejected' : 'pending')) }}"
                         data-filename="{{ strtolower($doc->original_filename) }}">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <div class="font-medium">{{ $doc->original_filename }}</div>
                                <div class="flex space-x-2">
                                    @if($doc->stamped)
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded" title="Stamped on {{ $doc->stamped_at?->format('M j, Y g:i A') }}">📋 E-Stamped</span>
                                    @endif
                                    @if($doc->approved)
                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">✓ Accepted</span>
                                    @endif
                                    @if(in_array($doc->pleading_type, ['request_to_docket', 'request_pre_hearing']))
                                        <span class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded">Pleading Document</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                {{ $doc->doc_type_label }}
                                @if($doc->pleading_type)
                                    • {{ ucfirst(str_replace('_', ' ', $doc->pleading_type)) }}
                                @endif
                                • {{ number_format($doc->size_bytes / 1024, 1) }} KB •
                                {{ $doc->uploaded_at->format('M j, Y g:i A') }}
                                @if($doc->stamped && $doc->stamped_at)
                                    <br><span class="text-blue-600">E-Stamped: {{ $doc->stamped_at->format('M j, Y g:i A') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex space-x-2">
                            <a href="{{ route('documents.preview', $doc) }}" target="_blank" class="text-gray-600 hover:text-gray-800 text-sm" title="Preview" @if(!$doc->approved) onclick="return confirmPendingDocumentAction()" @endif>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="{{ route('documents.download', $doc) }}" class="text-blue-600 hover:text-blue-800 text-sm" title="Download" @if(!$doc->approved) onclick="return confirmPendingDocumentAction()" @endif>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </a>

                            @if(auth()->user()->canCreateCase() && in_array($case->status, ['draft', 'rejected']))
                            <button onclick="deleteDocument({{ $doc->id }})" class="text-red-600 hover:text-red-800 text-sm" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                            @endif
                            @if($latestDocCorrection && in_array($latestDocCorrection->status, ['open', 'resubmitted']) && !auth()->user()->isHearingUnit() && (int) $doc->uploaded_by_user_id === (int) auth()->id())
                            <button onclick="showCaseCorrectedUploadModal({{ $doc->id }})" class="text-indigo-600 hover:text-indigo-800 text-sm" title="Submit Corrected Document">
                                Submit Corrected Document
                            </button>
                            @endif
                        </div>
                    </div>
                    @if($doc->rejected_reason || $latestDocCorrection)
                    <div class="mt-2 mb-4 space-y-2">
                        @if($doc->rejected_reason)
                        <div class="rounded bg-red-50 px-3 py-2 text-sm text-red-700">
                            <strong>Rejection Reason:</strong> {{ $doc->rejected_reason }}
                        </div>
                        @endif

                        @if($latestDocCorrection)
                        <div class="rounded-lg border {{ $latestDocCorrection->status === 'open' ? 'border-red-200 bg-red-50' : 'border-blue-200 bg-blue-50' }} p-3">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div class="text-sm font-semibold {{ $latestDocCorrection->status === 'open' ? 'text-red-800' : 'text-blue-800' }}">
                                        {{ $latestDocCorrection->correction_type === 'rejected' ? 'Document Rejected by HU' : 'Document Correction Requested' }}
                                    </div>
                                    <div class="mt-1 text-sm {{ $latestDocCorrection->status === 'open' ? 'text-red-700' : 'text-blue-700' }}">
                                        {{ $latestDocCorrection->summary }}
                                    </div>
                                    <div class="mt-1 text-xs text-gray-600">
                                        Requested {{ $latestDocCorrection->requested_at?->format('M j, Y g:i A') }}
                                        @if($latestDocCorrection->requestedBy)
                                            by {{ $latestDocCorrection->requestedBy->getDisplayName() }}
                                        @endif
                                        @if($latestDocCorrection->resubmitted_at)
                                            â€¢ Corrected submission received {{ $latestDocCorrection->resubmitted_at->format('M j, Y g:i A') }}
                                        @endif
                                    </div>
                                </div>
                                <span class="inline-flex px-2 py-1 rounded-full text-xs font-medium {{ $latestDocCorrection->status === 'open' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $latestDocCorrection->status === 'open' ? 'Awaiting Corrected Filing' : 'Pending HU Review' }}
                                </span>
                            </div>

                            <div class="mt-3 space-y-2">
                                @foreach($latestDocCorrection->items as $item)
                                <div class="rounded border border-white bg-white/70 px-3 py-2">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        {{ $documentCorrectionCategoryLabels[$item->category] ?? \Illuminate\Support\Str::title(str_replace('_', ' ', $item->category)) }}
                                    </div>
                                    <div class="mt-1 text-sm text-gray-900">{{ $item->item_note }}</div>
                                    @if($item->required_action)
                                        <div class="mt-1 text-xs text-gray-700"><strong>Required:</strong> {{ $item->required_action }}</div>
                                    @endif
                                    @if($item->resolution_note)
                                        <div class="mt-2 text-xs text-green-700"><strong>Resolution:</strong> {{ $item->resolution_note }}</div>
                                    @endif
                                </div>
                                @endforeach
                            </div>

                            @if($latestDocCorrection->replacementDocument)
                            <div class="mt-3 text-xs text-gray-700">
                                <strong>Corrected Filing:</strong> {{ $latestDocCorrection->replacementDocument->original_filename }}
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endif
                    @endforeach
                </div>

                <!-- Pagination -->
                <div id="paginationContainer" class="mt-4 flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        Showing <span id="showingFrom">1</span> to <span id="showingTo">25</span> of <span id="totalDocs">{{ $case->documents->count() }}</span> documents
                    </div>
                    <div class="flex space-x-2">
                        <button id="prevPage" class="px-3 py-1 border rounded text-sm" disabled>Previous</button>
                        <span id="pageNumbers" class="flex space-x-1"></span>
                        <button id="nextPage" class="px-3 py-1 border rounded text-sm">Next</button>
                    </div>
                </div>
            </div>

            <!-- Audit Trail -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium mb-4">Audit Trail</h3>
                <div class="space-y-3">
                    @foreach($case->auditLogs->sortByDesc('created_at') as $log)
                    <div class="flex items-start space-x-3 py-2 border-b">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                        <div class="flex-1">
                            <div class="text-sm">
                                <strong>{{ $log->user->getDisplayName() }}</strong>
                                @if($log->action === 'update_document_title')
                                    updated document title
                                @else
                                    {{ str_replace('_', ' ', $log->action) }}
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">{{ $log->created_at->format('M j, Y g:i A') }}</div>
                            @if($log->meta_json)
                                @if($log->action === 'update_document_title')
                                    <div class="text-xs text-gray-600 mt-1">
                                        <div>Original: {{ $log->meta_json['old_title'] ?? 'N/A' }}</div>
                                        <div>Current: {{ $log->meta_json['new_title'] ?? 'N/A' }}</div>
                                    </div>
                                @else
                                    <div class="text-xs text-gray-600 mt-1">{{ json_encode($log->meta_json) }}</div>
                                @endif
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>


    <div id="caseCorrectedUploadModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-2">Submit Corrected Document</h3>
                    <p id="caseCorrectedUploadSummary" class="text-sm text-gray-600 mb-4"></p>
                    <form id="caseCorrectedUploadForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="space-y-4">
                            <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                                <p id="caseCorrectedUploadDocType" class="text-sm text-blue-900 font-medium"></p>
                                <p id="caseCorrectedUploadOriginalFile" class="text-xs text-blue-700 mt-1"></p>
                            </div>
                            <div id="case-corrected-upload-items" class="space-y-3"></div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Document Title *</label>
                                <input type="text" name="custom_title" id="caseCorrectedCustomTitle" maxlength="255" required class="block w-full border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Corrected File *</label>
                                <input type="file" name="document" id="caseCorrectedDocumentFile" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" class="block w-full border-gray-300 rounded-md">
                                <p class="text-xs text-gray-500 mt-1">Upload the corrected replacement filing for HU review.</p>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="hideCaseCorrectedUploadModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Submit Corrected Document</button>
                        </div>
                    </form>
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
        const caseShowDocumentCorrectionMap = @json($showDocumentCorrectionMap);
        const caseShowDocumentMetaMap = @json($showDocumentMetaMap);
        const caseShowCorrectionCategoryLabels = @json($documentCorrectionCategoryLabels);
        // Party details toggle
        function togglePartyDetails(index) {
            const details = document.getElementById(`party-details-${index}`);
            const chevron = document.querySelector(`.party-chevron-${index}`);

            if (details.classList.contains('hidden')) {
                details.classList.remove('hidden');
                chevron.classList.add('rotate-180');
            } else {
                details.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        }
        // Document filtering and pagination
        let currentPage = 1;
        let itemsPerPage = 25;
        let filteredDocs = [];

        document.getElementById('docTypeFilter').addEventListener('change', filterAndPaginate);
        document.getElementById('statusFilter').addEventListener('change', filterAndPaginate);
        document.getElementById('searchInput').addEventListener('input', filterAndPaginate);
        document.getElementById('perPageSelect').addEventListener('change', function() {
            itemsPerPage = parseInt(this.value);
            currentPage = 1;
            filterAndPaginate();
        });

        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                paginateDocuments();
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            const totalPages = Math.ceil(filteredDocs.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                paginateDocuments();
            }
        });

        function showCaseCorrectedUploadModal(documentId) {
            const correction = caseShowDocumentCorrectionMap[documentId];
            const documentMeta = caseShowDocumentMetaMap[documentId];
            if (!correction || !documentMeta) {
                alert('No open document correction cycle is available for this filing.');
                return;
            }

            document.getElementById('caseCorrectedUploadSummary').textContent = correction.summary;
            document.getElementById('caseCorrectedUploadDocType').textContent = `Document Type: ${documentMeta.doc_type_label}`;
            document.getElementById('caseCorrectedUploadOriginalFile').textContent = `Original Filing: ${documentMeta.original_filename}`;
            document.getElementById('caseCorrectedCustomTitle').value = documentMeta.custom_title || '';
            document.getElementById('caseCorrectedDocumentFile').value = '';
            document.getElementById('caseCorrectedUploadForm').action = `/cases/{{ $case->id }}/documents/${documentId}/submit-correction`;

            const itemsContainer = document.getElementById('case-corrected-upload-items');
            itemsContainer.innerHTML = '';
            correction.items.forEach((item) => {
                itemsContainer.insertAdjacentHTML('beforeend', `
                    <div class="border rounded-md p-3 bg-gray-50">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">
                            ${caseShowCorrectionCategoryLabels[item.category] || item.category}
                        </div>
                        <div class="text-sm text-gray-900 mt-1">${item.item_note}</div>
                        ${item.required_action ? `<div class="text-xs text-gray-700 mt-1"><strong>Required:</strong> ${item.required_action}</div>` : ''}
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Resolution Note *</label>
                            <textarea name="resolution_items[${item.id}][resolution_note]" rows="3" class="block w-full border-gray-300 rounded-md" placeholder="Explain exactly how you corrected this issue.">${item.resolution_note ?? ''}</textarea>
                        </div>
                    </div>
                `);
            });

            document.getElementById('caseCorrectedUploadModal').classList.remove('hidden');
        }

        function hideCaseCorrectedUploadModal() {
            document.getElementById('caseCorrectedUploadModal').classList.add('hidden');
        }

        function filterAndPaginate() {
            const typeFilter = document.getElementById('docTypeFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const docs = document.querySelectorAll('.document-item');

            filteredDocs = [];
            docs.forEach(doc => {
                const docType = doc.getAttribute('data-doc-type');
                const docStatus = doc.getAttribute('data-status');
                const filename = doc.getAttribute('data-filename');

                const typeMatch = !typeFilter || docType === typeFilter;
                const statusMatch = !statusFilter || docStatus === statusFilter;
                const searchMatch = !searchTerm || filename.includes(searchTerm);

                if (typeMatch && statusMatch && searchMatch) {
                    filteredDocs.push(doc);
                }
            });

            currentPage = 1;
            paginateDocuments();
        }

        function paginateDocuments() {
            const docs = document.querySelectorAll('.document-item');
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const totalPages = Math.ceil(filteredDocs.length / itemsPerPage);

            // Hide all documents
            docs.forEach(doc => doc.style.display = 'none');

            // Show current page documents
            filteredDocs.slice(startIndex, endIndex).forEach(doc => {
                doc.style.display = 'flex';
            });

            // Update pagination info
            document.getElementById('showingFrom').textContent = filteredDocs.length > 0 ? startIndex + 1 : 0;
            document.getElementById('showingTo').textContent = Math.min(endIndex, filteredDocs.length);
            document.getElementById('totalDocs').textContent = filteredDocs.length;

            // Update pagination buttons
            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages || totalPages === 0;

            // Update page numbers
            const pageNumbers = document.getElementById('pageNumbers');
            pageNumbers.innerHTML = '';
            for (let i = 1; i <= Math.min(totalPages, 5); i++) {
                const pageBtn = document.createElement('button');
                pageBtn.textContent = i;
                pageBtn.className = `px-2 py-1 border rounded text-sm ${i === currentPage ? 'bg-blue-500 text-white' : ''}`;
                pageBtn.onclick = () => {
                    currentPage = i;
                    paginateDocuments();
                };
                pageNumbers.appendChild(pageBtn);
            }
        }

        // Initialize pagination
        filterAndPaginate();



        function deleteDocument(docId) {
            if (confirm('Are you sure you want to delete this document? This action cannot be undone.')) {
                fetch(`/cases/{{ $case->id }}/documents/${docId}`, {
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
                        alert('Failed to delete document');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to delete document');
                });
            }
        }

        function confirmPendingDocumentAction() {
            return confirm('The document you are about to view or download has not been accepted yet. By viewing or downloading you affirm that this document can be accepted or rejected.');
        }

        function showAcceptanceModal() {
            document.getElementById('acceptanceModal').classList.remove('hidden');
        }

        function hideAcceptanceModal() {
            document.getElementById('acceptanceModal').classList.add('hidden');
        }

        function showRejectModal() {
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        function addRejectionItem() {
            const container = document.getElementById('rejection-items');
            if (!container) {
                return;
            }

            const index = container.querySelectorAll('.rejection-item').length;
            const wrapper = document.createElement('div');
            wrapper.className = 'border rounded-md p-3 bg-gray-50 rejection-item';
            wrapper.innerHTML = `
                <div class="flex justify-end mb-2">
                    <button type="button" class="text-xs text-red-600 hover:text-red-800" onclick="this.closest('.rejection-item').remove()">Remove</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                        <select name="rejection_items[${index}][category]" class="block w-full border-gray-300 rounded-md text-sm">
                            <option value="missing_document">Missing Document</option>
                            <option value="caption_issue">Caption Issue</option>
                            <option value="party_issue">Party Issue</option>
                            <option value="service_issue">Service Issue</option>
                            <option value="ose_issue">OSE Issue</option>
                            <option value="document_issue">Document Issue</option>
                            <option value="filing_issue">Filing Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Required Action</label>
                        <input type="text" name="rejection_items[${index}][required_action]" class="block w-full border-gray-300 rounded-md text-sm" placeholder="What must be corrected?">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Issue Detail</label>
                    <textarea name="rejection_items[${index}][item_note]" rows="3" class="block w-full border-gray-300 rounded-md text-sm" placeholder="Describe the specific problem that must be fixed."></textarea>
                </div>
            `;

            container.appendChild(wrapper);
        }

        function showCloseModal() {
            document.getElementById('closeModal').classList.remove('hidden');
        }

        function hideCloseModal() {
            document.getElementById('closeModal').classList.add('hidden');
        }

        function showSubmitModal() {
            document.getElementById('submitModal').classList.remove('hidden');
        }

        function hideSubmitModal() {
            document.getElementById('submitModal').classList.add('hidden');
        }

        function toggleAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.notify-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        function toggleAcceptanceRecipients(selectAll) {
            const checkboxes = document.querySelectorAll('.acceptance-notify-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // Attorney Management
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

        function removeAttorney(partyId, counselPartyId = null) {
            const message = counselPartyId
                ? 'Remove this attorney from the party?'
                : 'Remove attorney representation for this party?';

            if (confirm(message)) {
                fetch(`/cases/{{ $case->id }}/parties/${partyId}/attorney`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(counselPartyId ? { counsel_party_id: counselPartyId } : {})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to remove attorney');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to remove attorney');
                });
            }
        }

        function assignAttorney(partyId, formData) {
            fetch(`/cases/{{ $case->id }}/parties/${partyId}/attorney`, {
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
                    alert('Failed to assign attorney');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to assign attorney');
            });
        }

        function showUploadModal() {
            document.getElementById('uploadModal').classList.remove('hidden');
        }

        function hideUploadModal() {
            document.getElementById('uploadModal').classList.add('hidden');
            document.getElementById('uploadForm').reset();
            const pleadingSection = document.getElementById('pleadingTypeSection');
            const previewDiv = document.getElementById('filenamePreview');
            if (pleadingSection) {
                pleadingSection.classList.add('hidden');
            }
            if (previewDiv) {
                previewDiv.classList.add('hidden');
            }
        }

        function updateFilenamePreview() {
            const docTypeSelect = document.querySelector('#uploadModal select[name="doc_type"]');
            const customTitleInput = document.getElementById('customTitleInput');
            const previewDiv = document.getElementById('filenamePreview');
            const previewText = document.getElementById('previewText');

            if (!docTypeSelect || !customTitleInput || !previewDiv || !previewText) {
                return;
            }

            const docType = docTypeSelect.options[docTypeSelect.selectedIndex]?.text || '';
            const customTitle = customTitleInput.value.trim();
            const titleOrType = customTitle || docType;

            if (titleOrType && docType) {
                const today = new Date().toISOString().split('T')[0];
                previewText.textContent = `${today} - ${titleOrType}.pdf`;
                previewDiv.classList.remove('hidden');
            } else {
                previewDiv.classList.add('hidden');
            }
        }

        function togglePleadingType() {
            const select = document.querySelector('#uploadModal select[name="doc_type"]');
            const pleadingSection = document.getElementById('pleadingTypeSection');

            if (!select || !pleadingSection) {
                return;
            }

            const selectedOption = select.options[select.selectedIndex];

            if (selectedOption && selectedOption.dataset.isPleading === 'true') {
                pleadingSection.classList.remove('hidden');
            } else {
                pleadingSection.classList.add('hidden');
            }

            updateFilenamePreview();
        }

        function validateFiles(input) {
            const files = Array.from(input.files);
            const maxSize = 200 * 1024 * 1024;

            for (const file of files) {
                if (file.size > maxSize) {
                    alert(`File "${file.name}" is too large. Each file must be less than 200MB.`);
                    input.value = '';
                    return;
                }
            }
        }

        function confirmUpload(event) {
            const customTitleInput = document.getElementById('customTitleInput');
            const customTitle = customTitleInput ? customTitleInput.value.trim() : '';
            if (customTitle) {
                const message = `You have entered a custom title:\n\n"${customTitle}"\n\nIs this correct?`;
                if (!confirm(message)) {
                    event.preventDefault();
                    return false;
                }
            }
            return true;
        }

        function showReopenModal() {
            document.getElementById('reopenModal').classList.remove('hidden');
        }

        function hideReopenModal() {
            document.getElementById('reopenModal').classList.add('hidden');
        }

        // Attorney modal functions
        window.toggleAttorneyFields = function() {
            const option = document.querySelector('#attorneyModal input[name="attorney_option"]:checked')?.value;
            const existingSelect = document.querySelector('#attorneyModal select[name="attorney_id"]');
            const newFields = document.getElementById('newAttorneyFields');
            const newInputs = newFields?.querySelectorAll('input') || [];

            if (option === 'existing') {
                existingSelect.disabled = false;
                newFields?.classList.add('opacity-50');
                newInputs.forEach(input => input.disabled = true);
            } else if (option === 'new') {
                existingSelect.disabled = true;
                existingSelect.value = '';
                newFields?.classList.remove('opacity-50');
                newInputs.forEach(input => input.disabled = false);
            }
        };

        window.handleAttorneyForm = function(event, partyId) {
            event.preventDefault();
            const formData = new FormData(event.target);
            assignAttorney(partyId, formData);
        };
    </script>

    <!-- Acceptance Notice Modal -->
    @if(in_array($case->status, ['submitted_to_hu', 'active']) && in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'hu_clerk']))
    <div id="acceptanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-lg w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Accept Case {{ $case->case_no }}</h3>
                    <div class="hidden">
                        <p class="text-sm text-gray-600 mb-4">The following persons will be notified of the case acceptance:</p>

                    <div class="bg-gray-50 rounded-lg p-4 mb-4 max-h-60 overflow-y-auto">
                        <h4 class="font-medium text-sm mb-2">Case Parties:</h4>
                        @foreach($case->parties->reject(fn($party) => $party->isWrdAgencyParty()) as $party)
                        <div class="text-sm py-1">
                            • {{ $party->person->full_name }} ({{ ucfirst($party->role) }}) - {{ $party->person->email }}
                        </div>
                        @endforeach

                        @if($case->assignedAttorney)
                        <h4 class="font-medium text-sm mt-3 mb-2">Assigned Attorney:</h4>
                        <div class="text-sm py-1">
                            • {{ $case->assignedAttorney->name }} - {{ $case->assignedAttorney->email }}
                        </div>
                        @endif

                        @if($case->assignedHydrologyExpert)
                        <h4 class="font-medium text-sm mt-3 mb-2">Hydrology Expert:</h4>
                        <div class="text-sm py-1">
                            • {{ $case->assignedHydrologyExpert->name }} - {{ $case->assignedHydrologyExpert->email }}
                        </div>
                        @endif
                    </div>
                    </div>

                    <p class="text-sm text-gray-600 mb-4">Select which parties or staff should be notified that the case has been accepted.</p>

                    <form action="{{ route('cases.accept', $case) }}" method="POST">
                        @csrf
                        <div class="bg-gray-50 rounded-lg p-4 mb-4 max-h-60 overflow-y-auto">
                            <div class="mb-4">
                                <label class="flex items-center">
                                    <input type="checkbox" id="acceptanceSelectAll" class="mr-2" onchange="toggleAcceptanceRecipients(this)">
                                    <span class="font-medium">Select All</span>
                                </label>
                            </div>

                            @if(!empty($acceptanceNotificationRecipients['parties']))
                            <h4 class="font-medium text-sm mb-2">Case Parties:</h4>
                            @foreach($acceptanceNotificationRecipients['parties'] as $recipient)
                            <div class="text-sm py-1">
                                <label class="flex items-center">
                                    <input type="checkbox" name="notify_recipients[]" value="{{ $recipient['token'] }}" class="mr-2 acceptance-notify-checkbox" checked>
                                    <span>{{ $recipient['name'] }} ({{ $recipient['role'] }}) - {{ $recipient['email'] }}</span>
                                </label>
                            </div>
                            @endforeach
                            @endif

                            @if(!empty($acceptanceNotificationRecipients['attorneys']))
                            <h4 class="font-medium text-sm mt-3 mb-2">Attorneys:</h4>
                            @foreach($acceptanceNotificationRecipients['attorneys'] as $recipient)
                            <div class="text-sm py-1">
                                <label class="flex items-center">
                                    <input type="checkbox" name="notify_recipients[]" value="{{ $recipient['token'] }}" class="mr-2 acceptance-notify-checkbox" checked>
                                    <span>{{ $recipient['name'] }} ({{ $recipient['role'] }}) - {{ $recipient['email'] }}</span>
                                </label>
                            </div>
                            @endforeach
                            @endif

                            @if(!empty($acceptanceNotificationRecipients['staff']))
                            <h4 class="font-medium text-sm mt-3 mb-2">Staff:</h4>
                            @foreach($acceptanceNotificationRecipients['staff'] as $recipient)
                            <div class="text-sm py-1">
                                <label class="flex items-center">
                                    <input type="checkbox" name="notify_recipients[]" value="{{ $recipient['token'] }}" class="mr-2 acceptance-notify-checkbox" checked>
                                    <span>{{ $recipient['name'] }} ({{ $recipient['role'] }}) - {{ $recipient['email'] }}</span>
                                </label>
                            </div>
                            @endforeach
                            @endif
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Message (Optional)</label>
                            <textarea name="custom_message" rows="3" class="block w-full border-gray-300 rounded-md" placeholder="Add any additional information for the selected recipients..."></textarea>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideAcceptanceModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Accept & Notify Selected</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Case Modal -->
    <div id="rejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Reject Case {{ $case->case_no }}</h3>
                    <p class="text-sm text-gray-600 mb-4">The following persons will be notified to make corrections:</p>

                    <div class="bg-red-50 rounded-lg p-4 mb-4">
                        <h4 class="font-medium text-sm mb-2">ALU Staff (for corrections):</h4>
                        <div class="text-sm py-1">
                            • {{ $case->creator->name }} (Case Creator) - {{ $case->creator->email }}
                        </div>
                        @if($case->assignedAttorney)
                        <div class="text-sm py-1">
                            • {{ $case->assignedAttorney->name }} (Assigned Attorney) - {{ $case->assignedAttorney->email }}
                        </div>
                        @endif
                    </div>

                    <form action="{{ route('cases.reject', $case) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Correction Summary *</label>
                            <textarea name="reason_summary" required rows="3" class="block w-full border-gray-300 rounded-md" placeholder="Summarize why the case is being rejected and what must be fixed before resubmission."></textarea>
                            <p class="text-xs text-gray-500 mt-1">This summary becomes the headline for the correction cycle.</p>
                        </div>
                        <div class="mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Correction Items</label>
                                <button type="button" onclick="addRejectionItem()" class="text-sm text-blue-600 hover:text-blue-800">+ Add Item</button>
                            </div>
                            <div id="rejection-items" class="space-y-3">
                                <div class="border rounded-md p-3 bg-gray-50 rejection-item">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
                                            <select name="rejection_items[0][category]" class="block w-full border-gray-300 rounded-md text-sm">
                                                <option value="missing_document">Missing Document</option>
                                                <option value="caption_issue">Caption Issue</option>
                                                <option value="party_issue">Party Issue</option>
                                                <option value="service_issue">Service Issue</option>
                                                <option value="ose_issue">OSE Issue</option>
                                                <option value="document_issue">Document Issue</option>
                                                <option value="filing_issue">Filing Issue</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Required Action</label>
                                            <input type="text" name="rejection_items[0][required_action]" class="block w-full border-gray-300 rounded-md text-sm" placeholder="What must be corrected?">
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Issue Detail</label>
                                        <textarea name="rejection_items[0][item_note]" rows="3" class="block w-full border-gray-300 rounded-md text-sm" placeholder="Describe the specific problem that must be fixed."></textarea>
                                    </div>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">List each correction separately so ALU can document exactly how it was fixed before resubmission.</p>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideRejectModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-md hover:bg-red-600">Reject & Notify ALU</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Close Case Modal -->
    @if($case->status === 'active' && in_array(auth()->user()->getCurrentRole(), ['hu_admin', 'hu_clerk']))
    <div id="closeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-lg w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Close Case {{ $case->case_no }}</h3>
                    <p class="text-sm text-gray-600 mb-4">This will close the case and notify all parties.</p>


                    <form action="{{ route('cases.close', $case) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Closure *</label>
                            <select id="closure-reason" name="reason" required class="block w-full border-gray-300 rounded-md" onchange="toggleOtherClosureReason()">
                                <option value="">Select a reason</option>
                                <option value="Applicant's failure to participate">Applicant's failure to participate</option>
                                <option value="Applicant's failure to submit hearing fee">Applicant's failure to submit hearing fee</option>
                                <option value="Final Decision">Final Decision</option>
                                <option value="Mediated Settlement">Mediated Settlement</option>
                                <option value="Withdrawal of Application">Withdrawal of Application</option>
                                <option value="Withdrawal of Protest(s)">Withdrawal of Protest(s)</option>
                                <option value="Other">Other</option>
                            </select>
                            <textarea id="closure-reason-other" name="other_reason" rows="4" class="mt-3 block w-full border-gray-300 rounded-md hidden" placeholder="Please provide the reason for closing this case..."></textarea>
                        </div>
                        <div class="mb-4">
                            {{-- <label class="flex items-start">
                                <input type="checkbox" name="closing_letter_confirmed" value="1" required class="mt-1 mr-2">
                                <span class="text-sm text-gray-700">I confirm that the closing letter has been uploaded for this case.</span>
                            </label> --}}
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideCloseModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-md hover:bg-orange-600">Close Case</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Reopen Case Modal -->
    @if($case->status === 'closed' && auth()->user()->getCurrentRole() === 'hu_admin')
    <div id="reopenModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-lg w-full">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Reopen Case {{ $case->case_no }}</h3>
                    <p class="text-sm text-gray-600 mb-4">This will reopen the case and notify all parties.</p>

                    <form action="{{ route('cases.reopen', $case) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Reopening *</label>
                            <textarea name="reason" required rows="4" class="block w-full border-gray-300 rounded-md" placeholder="Please provide the reason for reopening this case..."></textarea>
                        </div>
                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideReopenModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Reopen Case</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
    <script>
        function toggleOtherClosureReason() {
            const select = document.getElementById('closure-reason');
            const otherField = document.getElementById('closure-reason-other');
            if (!select || !otherField) return;
            if (select.value === 'Other') {
                otherField.classList.remove('hidden');
                otherField.required = true;
            } else {
                otherField.classList.add('hidden');
                otherField.required = false;
                otherField.value = '';
            }
        }
        document.addEventListener('DOMContentLoaded', toggleOtherClosureReason);
    </script>

    <!-- Submit to HU Modal -->
    @if($case->status === 'draft' && auth()->user()->canSubmitToHU())
    <div id="submitModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Submit Case {{ $case->case_no }} to Hearing Unit</h3>
                    <p class="text-sm text-gray-600 mb-4">Submitting to Hearing Unit now only notifies the Hearing Unit recipient below.</p>

                    <form action="{{ route('cases.update', $case) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="case_type" value="{{ $case->case_type }}">
                        <input type="hidden" name="caption" value="{{ $case->caption }}">
                        <input type="hidden" name="action" value="submit">
                        <input type="hidden" name="affirmation" value="1">

                        <div class="bg-gray-50 rounded-lg p-4 mb-4 max-h-60 overflow-y-auto">
                            <h4 class="font-medium text-sm mb-2">Hearing Unit:</h4>
                            @forelse($submissionNotificationRecipients as $recipient)
                            <div class="text-sm py-1">
                                <input type="hidden" name="notify_recipients[]" value="{{ $recipient['token'] }}">
                                <div class="flex items-center rounded-md border border-gray-200 bg-white px-3 py-2">
                                    <span>{{ $recipient['name'] }} ({{ $recipient['role'] }}) - {{ $recipient['email'] }}</span>
                                </div>
                            </div>
                            @empty
                            <p class="text-sm text-red-600">No Hearing Unit recipient is configured. Submission will continue without an email notification.</p>
                            @endforelse
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Message (Optional)</label>
                            <textarea name="custom_message" rows="3" class="block w-full border-gray-300 rounded-md" placeholder="Add any additional information for Hearing Unit..."></textarea>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" onclick="hideSubmitModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">Submit to HU</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Upload Document Modal -->
    <div id="uploadModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">{{ auth()->user()->getCurrentRole() === 'hu_admin' ? 'Issue Order or Notice' : 'File Document' }}</h3>
                    <form id="uploadForm" action="{{ route('cases.documents.store', $case) }}" method="POST" enctype="multipart/form-data" onsubmit="return confirmUpload(event)">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Document Type *</label>
                                <select name="doc_type" required class="block w-full border-gray-300 rounded-md" onchange="togglePleadingType()">
                                    <option value="">Select document type...</option>
                                    @php
                                        $documentTypes = \App\Models\DocumentType::where('is_active', true)
                                            ->when(auth()->user()->getCurrentRole() === 'party', function($query) {
                                                return $query->where('category', 'party_upload');
                                            })
                                            ->orderBy('name')
                                            ->get();
                                    @endphp
                                    @foreach($documentTypes as $docType)
                                    <option value="{{ $docType->code }}" data-is-pleading="{{ $docType->is_pleading ? 'true' : 'false' }}">{{ \Illuminate\Support\Str::title($docType->name) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Document Title *</label>
                                <input type="text" name="custom_title" id="customTitleInput" maxlength="255"
                                       required
                                       class="block w-full border-gray-300 rounded-md"
                                       placeholder="e.g., Motion to Dismiss for Lack of Jurisdiction"
                                       oninput="updateFilenamePreview()">
                                <p class="mt-1 text-sm text-amber-700">The title must be the exact same as what is listed as the document title.</p>
                            </div>

                            <div id="filenamePreview" class="hidden bg-blue-50 border border-blue-200 rounded-md p-3">
                                <p class="text-xs font-medium text-blue-800 mb-1">Filename Preview:</p>
                                <p id="previewText" class="text-sm text-blue-900 font-mono"></p>
                            </div>

                            <div id="pleadingTypeSection" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pleading Type</label>
                                <select name="pleading_type" class="block w-full border-gray-300 rounded-md">
                                    <option value="none">None</option>
                                    <option value="request_to_docket">Request to Docket</option>
                                    <option value="request_pre_hearing">Request for Pre-Hearing</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Files *</label>
                                <input type="file" name="document[]" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" multiple
                                       class="block w-full border-gray-300 rounded-md" onchange="validateFiles(this)">
                                <p class="text-xs text-gray-500 mt-1">Select multiple files. Supported formats: PDF, DOC, DOCX, JPG, PNG (Max: 200MB each)</p>
                            </div>

                        </div>

                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="hideUploadModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">
                                Cancel
                            </button>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                                {{ auth()->user()->getCurrentRole() === 'hu_admin' ? 'Issue Order or Notice' : 'File Document' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


    <!-- Add Paralegal Modal -->
    <div id="addParalegalModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <h3 class="text-lg font-medium mb-4">Add Paralegal</h3>
                    <form action="{{ route('cases.paralegals.add', $case) }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="individual">
                        <div class="space-y-4">
                            @php
                                $existingAluParalegalEmails = \App\Models\User::whereIn('id', \App\Models\CaseAssignment::where('assignment_type', 'alu_paralegal')->pluck('user_id'))
                                    ->pluck('email')
                                    ->filter()
                                    ->values();
                                $existingParalegals = \App\Models\Person::whereHas('caseParties', function ($query) {
                                    $query->where('role', 'paralegal');
                                })->orWhereIn('email', $existingAluParalegalEmails)
                                ->orderBy('last_name')
                                ->orderBy('first_name')
                                ->get();
                            @endphp

                            <div class="rounded-lg border border-gray-200 p-4 bg-gray-50">
                                <div class="flex flex-col md:flex-row md:items-center gap-3">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="paralegal_mode" value="existing" class="mr-2" onchange="toggleParalegalMode()" {{ $existingParalegals->isNotEmpty() ? 'checked' : '' }}>
                                        <span class="text-sm font-medium">Use Existing Paralegal</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="paralegal_mode" value="new" class="mr-2" onchange="toggleParalegalMode()" {{ $existingParalegals->isEmpty() ? 'checked' : '' }}>
                                        <span class="text-sm font-medium">Create New Paralegal</span>
                                    </label>
                                </div>

                                <div id="existingParalegalSection" class="mt-4 {{ $existingParalegals->isEmpty() ? 'hidden' : '' }}">
                                    <label class="block text-sm font-medium mb-1">Existing Paralegal</label>
                                    <select name="existing_person_id" id="existingParalegalSelect" class="w-full border-gray-300 rounded-md" onchange="toggleParalegalMode()">
                                        <option value="">Select existing paralegal...</option>
                                        @foreach($existingParalegals as $paralegalPerson)
                                        <option value="{{ $paralegalPerson->id }}">
                                            {{ $paralegalPerson->full_name }}{{ $paralegalPerson->email ? ' - ' . $paralegalPerson->email : '' }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Select an existing paralegal to reuse their saved person record.</p>
                                </div>
                            </div>

                            <div id="newParalegalFields" class="{{ $existingParalegals->isNotEmpty() ? 'hidden' : '' }}">
                                <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Prefix</label>
                                    <input type="text" name="prefix" class="w-full border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">First Name *</label>
                                    <input type="text" name="first_name" required class="w-full border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Middle Name</label>
                                    <input type="text" name="middle_name" class="w-full border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Last Name *</label>
                                    <input type="text" name="last_name" required class="w-full border-gray-300 rounded-md">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Suffix</label>
                                    <input type="text" name="suffix" class="w-full border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Title</label>
                                    <input type="text" name="title" class="w-full border-gray-300 rounded-md">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Organization</label>
                                <input type="text" name="organization" class="w-full border-gray-300 rounded-md">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium mb-1">Email *</label>
                                    <input type="email" name="email" required class="w-full border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Office Phone</label>
                                    <input type="text" name="phone_office" class="w-full border-gray-300 rounded-md">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Mobile Phone</label>
                                <input type="text" name="phone_mobile" class="w-full border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Address Line 1</label>
                                <input type="text" name="address_line1" class="w-full border-gray-300 rounded-md">
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Address Line 2</label>
                                <input type="text" name="address_line2" class="w-full border-gray-300 rounded-md">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">City</label>
                                    <input type="text" name="city" class="w-full border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">State</label>
                                    <input type="text" name="state" class="w-full border-gray-300 rounded-md">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">ZIP</label>
                                    <input type="text" name="zip" class="w-full border-gray-300 rounded-md">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1">Notes</label>
                                <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-md"></textarea>
                            </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" onclick="hideAddParalegalModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md">Cancel</button>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Add Paralegal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAddParalegalModal() {
            document.getElementById('addParalegalModal').classList.remove('hidden');
            toggleParalegalMode();
        }
        function hideAddParalegalModal() {
            document.getElementById('addParalegalModal').classList.add('hidden');
        }
        function toggleParalegalMode() {
            const existingRadio = document.querySelector('input[name="paralegal_mode"][value="existing"]');
            const newRadio = document.querySelector('input[name="paralegal_mode"][value="new"]');
            const existingSection = document.getElementById('existingParalegalSection');
            const existingSelect = document.getElementById('existingParalegalSelect');
            const newFields = document.getElementById('newParalegalFields');
            const newFieldInputs = newFields ? newFields.querySelectorAll('input, textarea, select') : [];

            const useExisting = existingRadio && existingRadio.checked && existingSection && !existingSection.classList.contains('hidden');

            if (existingSection) {
                existingSection.classList.toggle('hidden', !useExisting);
            }

            if (newFields) {
                newFields.classList.toggle('hidden', useExisting);
            }

            if (existingSelect) {
                existingSelect.required = !!useExisting;
                if (!useExisting) {
                    existingSelect.value = '';
                }
            }

            newFieldInputs.forEach((input) => {
                if (input.name === 'first_name' || input.name === 'last_name' || input.name === 'email') {
                    input.required = !useExisting;
                }
            });

            if (useExisting && existingSelect && !existingSelect.value) {
                existingSelect.focus();
            }
        }
        function removeParalegal(partyId) {
            if (confirm('Are you sure you want to remove this paralegal?')) {
                fetch(`/cases/{{ $case->id }}/paralegals/${partyId}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
                }).then(response => response.json()).then(data => { location.reload(); }).catch(error => { location.reload(); });
            }
        }
    </script>

</x-app-layout>

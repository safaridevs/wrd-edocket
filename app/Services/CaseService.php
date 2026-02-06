<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\Document;
use App\Models\OseFileNumber;
use App\Models\Person;
use App\Models\ServiceList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CaseService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    public function createCase(array $data, User $creator, ?Request $request = null): CaseModel
    {
        return DB::transaction(function () use ($data, $creator, $request) {
            $case = CaseModel::create([
                'case_no' => CaseModel::generateCaseNumber(),
                'caption' => $data['caption'],
                'case_type' => $data['case_type'],
                'created_by_user_id' => $creator->id,
                'assigned_attorney_id' => $data['assigned_attorney_id'] ?? null,
                'status' => $data['action'] === 'submit' ? 'submitted_to_hu' : 'draft',
                'submitted_at' => $data['action'] === 'submit' ? now() : null
            ]);
            
            // Create parties with proper Person relationships
            if (isset($data['parties'])) {
                foreach ($data['parties'] as $partyData) {
                    if (!empty($partyData['email'])) {
                        $person = $this->createOrFindPerson($partyData);
                        
                        // Create case party relationship
                        $clientParty = CaseParty::create([
                            'case_id' => $case->id,
                            'person_id' => $person->id,
                            'role' => $partyData['role'],
                            'service_enabled' => true
                        ]);
                        
                        // Handle attorney representation
                        if (isset($partyData['representation']) && $partyData['representation'] === 'attorney') {
                            \Log::info('Creating attorney party for case', [
                                'case_id' => $case->id,
                                'client_party_id' => $clientParty->id,
                                'party_data' => $partyData
                            ]);
                            $this->createAttorneyParty($case, $partyData, $clientParty);
                        }
                        
                        // Auto-create service list entry
                        ServiceList::create([
                            'case_id' => $case->id,
                            'person_id' => $person->id,
                            'email' => $person->email,
                            'service_method' => $partyData['service_method'] ?? 'email',
                            'is_primary' => true
                        ]);
                    }
                }
            }
            
            // Create OSE file numbers
            if (isset($data['ose_numbers'])) {
                foreach ($data['ose_numbers'] as $oseData) {
                    if ((!empty($oseData['basin_code_from']) && !empty($oseData['file_no_from'])) || 
                        (!empty($oseData['basin_code_to']) && !empty($oseData['file_no_to']))) {
                        
                        $fileNoFrom = null;
                        $fileNoTo = null;
                        $basinCode = null;
                        
                        if (!empty($oseData['basin_code_from']) && !empty($oseData['file_no_from'])) {
                            $fileNoFrom = $oseData['basin_code_from'] . '-' . $oseData['file_no_from'];
                            $basinCode = $oseData['basin_code_from'];
                        }
                        
                        if (!empty($oseData['basin_code_to']) && !empty($oseData['file_no_to'])) {
                            $fileNoTo = $oseData['basin_code_to'] . '-' . $oseData['file_no_to'];
                            $basinCode = $basinCode ?: $oseData['basin_code_to'];
                        }
                        
                        OseFileNumber::create([
                            'case_id' => $case->id,
                            'basin_code' => $basinCode,
                            'file_no_from' => $fileNoFrom,
                            'file_no_to' => $fileNoTo
                        ]);
                    }
                }
            }
            
            // Handle document uploads
            if ($request && ($request->hasFile('documents.application') || $request->hasFile('documents.pleading') || $request->has('optional_docs'))) {
                $this->handleDocumentUploads($case, $request, $creator);
            }
            
            AuditLog::log('create_case', $creator, $case, ['case_number' => $case->case_no]);
            
            // Send notifications if case was submitted directly
            if ($case->status === 'submitted_to_hu') {
                $this->notifyCaseSubmission($case);
            }
            
            return $case;
        });
    }
    
    private function createOrFindPerson(array $data): Person
    {
        // Try to find existing person by email
        $person = Person::where('email', $data['email'])->first();
        
        if (!$person) {
            $person = Person::create([
                'type' => $data['type'] ?? 'individual',
                'prefix' => $data['prefix'] ?? null,
                'first_name' => $data['first_name'] ?? null,
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'suffix' => $data['suffix'] ?? null,
                'organization' => $data['organization'] ?? null,
                'title' => $data['title'] ?? null,
                'email' => $data['email'],
                'phone_mobile' => $data['phone_mobile'] ?? $data['phone'] ?? null,
                'phone_office' => $data['phone_office'] ?? null,
                'address_line1' => $data['address_line1'] ?? $data['address'] ?? null,
                'address_line2' => $data['address_line2'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'zip' => $data['zip'] ?? null
            ]);
        }
        
        return $person;
    }
    
    private function createAttorneyParty(CaseModel $case, array $data, CaseParty $clientParty): void
    {
        \Log::info('createAttorneyParty called', [
            'case_id' => $case->id,
            'client_party_id' => $clientParty->id,
            'attorney_id' => $data['attorney_id'] ?? 'null',
            'attorney_name' => $data['attorney_name'] ?? 'null',
            'attorney_email' => $data['attorney_email'] ?? 'null'
        ]);
        
        // Handle existing attorney selection
        if (!empty($data['attorney_id'])) {
            $attorney = \App\Models\Attorney::find($data['attorney_id']);
            if ($attorney) {
                // Create attorney person if doesn't exist
                $attorneyPerson = Person::where('email', $attorney->email)->first();
                if (!$attorneyPerson) {
                    $attorneyPerson = Person::create([
                        'type' => 'individual',
                        'first_name' => explode(' ', $attorney->name)[0] ?? '',
                        'last_name' => explode(' ', $attorney->name, 2)[1] ?? '',
                        'email' => $attorney->email,
                        'phone_office' => $attorney->phone
                    ]);
                }
                
                // Create counsel party entry linked to client
                $counselParty = CaseParty::create([
                    'case_id' => $case->id,
                    'person_id' => $attorneyPerson->id,
                    'role' => 'counsel',
                    'client_party_id' => $clientParty->id,
                    'service_enabled' => true
                ]);
                \Log::info('Created counsel party for existing attorney', ['counsel_party_id' => $counselParty->id]);
            }
        }
        // Handle new attorney creation
        elseif (!empty($data['attorney_name']) && !empty($data['attorney_email'])) {
            // Create new attorney record
            $attorney = \App\Models\Attorney::firstOrCreate(
                ['email' => $data['attorney_email']],
                [
                    'name' => $data['attorney_name'],
                    'phone' => $data['attorney_phone'] ?? null,
                    'bar_number' => $data['bar_number'] ?? null,
                    'address_line1' => $data['attorney_address_line1'] ?? null,
                    'address_line2' => $data['attorney_address_line2'] ?? null,
                    'city' => $data['attorney_city'] ?? null,
                    'state' => $data['attorney_state'] ?? null,
                    'zip' => $data['attorney_zip'] ?? null
                ]
            );
            
            // Create attorney person
            $attorneyPerson = Person::firstOrCreate(
                ['email' => $attorney->email],
                [
                    'type' => 'individual',
                    'first_name' => explode(' ', $attorney->name)[0] ?? '',
                    'last_name' => explode(' ', $attorney->name, 2)[1] ?? '',
                    'email' => $attorney->email,
                    'phone_office' => $attorney->phone
                ]
            );
            
            // Create counsel party entry linked to client
            $counselParty = CaseParty::create([
                'case_id' => $case->id,
                'person_id' => $attorneyPerson->id,
                'role' => 'counsel',
                'client_party_id' => $clientParty->id,
                'service_enabled' => true
            ]);
            \Log::info('Created counsel party for new attorney', ['counsel_party_id' => $counselParty->id]);
        }
    }
    
    private function createPersonFromName(string $name): Person
    {
        $nameParts = explode(' ', trim($name));
        $firstName = $nameParts[0] ?? '';
        $lastName = count($nameParts) > 1 ? end($nameParts) : '';
        
        return Person::create([
            'type' => 'individual',
            'first_name' => $firstName,
            'last_name' => $lastName
        ]);
    }
    
    public function handleDocumentUploads(CaseModel $case, Request $request, User $uploader): void
    {
        // Load OSE file numbers for naming convention
        $case->load('oseFileNumbers');
        
        // Get OSE string for naming
        $oseString = $this->getOseString($case);
        
        // Handle required documents (Application)
        if ($request->hasFile('documents.application')) {
            $this->processFileArray($request->file('documents.application'), $case, 'application', $uploader, $oseString);
        }
        
        // Handle pleading documents (from pleading_type selection)
        $pleadingType = $request->input('pleading_type');
        \Log::info('Pleading processing', ['pleading_type' => $pleadingType, 'has_pleading_files' => $request->hasFile('documents.pleading')]);
        
        if ($pleadingType && $request->hasFile('documents.pleading')) {
            \Log::info('Processing pleading documents', ['type' => $pleadingType, 'file_count' => count($request->file('documents.pleading'))]);
            
            foreach ($request->file('documents.pleading') as $index => $file) {
                if ($file && $file->isValid()) {
                    $displayType = $this->getDisplayType($pleadingType);
                    $timestamp = now()->format('Y-m-d_His');
                    $uniqueId = substr(md5(uniqid()), 0, 6);
                    $sanitizedTitle = preg_replace('/[^A-Za-z0-9_-]/', '_', $displayType);
                    $storedFilename = "{$timestamp}_{$sanitizedTitle}_{$uniqueId}.pdf";
                    
                    $path = $file->storeAs('case_documents', $storedFilename, 'public');
                    
                    $originalFilename = now()->format('Y-m-d') . ' - ' . $displayType . '.pdf';
                    if ($index > 0) {
                        $originalFilename = now()->format('Y-m-d') . ' - ' . $displayType . ' (' . ($index + 1) . ').pdf';
                    }
                    
                    $documentData = [
                        'case_id' => $case->id,
                        'doc_type' => $pleadingType,
                        'original_filename' => $originalFilename,
                        'stored_filename' => $storedFilename,
                        'mime' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                        'checksum' => md5_file($file->getRealPath()),
                        'storage_uri' => $path,
                        'uploaded_by_user_id' => $uploader->id,
                        'uploaded_at' => now(),
                        'pleading_type' => $pleadingType
                    ];
                    
                    \Log::info('Creating pleading document', $documentData);
                    $document = Document::create($documentData);
                    \Log::info('Pleading document created', ['id' => $document->id]);
                } else {
                    \Log::warning('Invalid pleading file', ['index' => $index]);
                }
            }
        } else {
            \Log::info('No pleading documents to process', ['pleading_type' => $pleadingType, 'has_files' => $request->hasFile('documents.pleading')]);
        }
        
        // Handle multiple notice_publication documents
        if ($request->hasFile('documents.notice_publication')) {
            $this->processFileArray($request->file('documents.notice_publication'), $case, 'notice_publication', $uploader, $oseString);
        }
        
        // Handle multiple document types with standardized naming
        $multipleDocTypes = [
            'request_to_docket' => 'Request to Docket',
            'request_pre_hearing' => 'Request for Pre-Hearing',
            'protest_letter' => 'Protest Letter',
            'supporting' => 'Supporting Document'
        ];
        
        foreach ($multipleDocTypes as $docType => $displayType) {
            if ($request->hasFile("documents.{$docType}")) {
                $this->processFileArray($request->file("documents.{$docType}"), $case, $docType, $uploader, $oseString);
            }
        }
        
        // Handle optional documents from dropdown structure
        if ($request->has('optional_docs')) {
            foreach ($request->input('optional_docs') as $index => $optionalDoc) {
                if (isset($optionalDoc['type']) && $optionalDoc['type'] && $request->hasFile("optional_docs.{$index}.files")) {
                    $customTitle = $optionalDoc['custom_title'] ?? null;
                    foreach ($request->file("optional_docs.{$index}.files") as $fileIndex => $file) {
                        if ($file && $file->isValid()) {
                            $displayType = $this->getDisplayType($optionalDoc['type']);
                            $titleOrType = !empty($customTitle) ? $customTitle : $displayType;
                            
                            $timestamp = now()->format('Y-m-d_His');
                            $uniqueId = substr(md5(uniqid()), 0, 6);
                            $sanitizedTitle = preg_replace('/[^A-Za-z0-9_-]/', '_', $titleOrType);
                            $storedFilename = "{$timestamp}_{$sanitizedTitle}_{$uniqueId}.pdf";
                            
                            $path = $file->storeAs('case_documents', $storedFilename, 'public');
                            
                            $originalFilename = now()->format('Y-m-d') . ' - ' . $titleOrType . $oseString . '.pdf';
                            if ($fileIndex > 0) {
                                $originalFilename = now()->format('Y-m-d') . ' - ' . $titleOrType . $oseString . ' (' . ($fileIndex + 1) . ').pdf';
                            }
                            
                            $documentData = [
                                'case_id' => $case->id,
                                'doc_type' => $optionalDoc['type'],
                                'custom_title' => $customTitle,
                                'original_filename' => $originalFilename,
                                'stored_filename' => $storedFilename,
                                'mime' => $file->getMimeType(),
                                'size_bytes' => $file->getSize(),
                                'checksum' => md5_file($file->getRealPath()),
                                'storage_uri' => $path,
                                'uploaded_by_user_id' => $uploader->id,
                                'uploaded_at' => now()
                            ];
                            
                            Document::create($documentData);
                            \Log::info("Optional document created: {$optionalDoc['type']} - {$storedFilename}");
                        }
                    }
                }
            }
        }
        
        // Handle other documents from upload-documents form structure
        if ($request->has('documents.other')) {
            foreach ($request->input('documents.other') as $index => $otherDoc) {
                if (isset($otherDoc['type']) && $otherDoc['type'] && $request->hasFile("documents.other.{$index}.file")) {
                    $files = $request->file("documents.other.{$index}.file");
                    if (!is_array($files)) {
                        $files = [$files];
                    }
                    
                    foreach ($files as $fileIndex => $file) {
                        if ($file && $file->isValid()) {
                            $displayType = $this->getDisplayType($otherDoc['type']);
                            
                            $timestamp = now()->format('Y-m-d_His');
                            $uniqueId = substr(md5(uniqid()), 0, 6);
                            $sanitizedTitle = preg_replace('/[^A-Za-z0-9_-]/', '_', $displayType);
                            $storedFilename = "{$timestamp}_{$sanitizedTitle}_{$uniqueId}.pdf";
                            
                            $path = $file->storeAs('case_documents', $storedFilename, 'public');
                            
                            $originalFilename = now()->format('Y-m-d') . ' - ' . $displayType . $oseString . '.pdf';
                            if ($fileIndex > 0) {
                                $originalFilename = now()->format('Y-m-d') . ' - ' . $displayType . $oseString . ' (' . ($fileIndex + 1) . ').pdf';
                            }
                            
                            $documentData = [
                                'case_id' => $case->id,
                                'doc_type' => $otherDoc['type'],
                                'original_filename' => $originalFilename,
                                'stored_filename' => $storedFilename,
                                'mime' => $file->getMimeType(),
                                'size_bytes' => $file->getSize(),
                                'checksum' => md5_file($file->getRealPath()),
                                'storage_uri' => $path,
                                'uploaded_by_user_id' => $uploader->id,
                                'uploaded_at' => now()
                            ];
                            
                            Document::create($documentData);
                            \Log::info("Other document created: {$otherDoc['type']} - {$storedFilename}");
                        }
                    }
                }
            }
        }
    }

    public function submitToHU(CaseModel $case, User $user): bool
    {
        if ($case->changeStatus('submitted_to_hu', $user, 'Case submitted for HU review')) {
            AuditLog::log('submit_to_hu', $user, $case);
            
            $this->notifyCaseSubmission($case);
            
            return true;
        }
        return false;
    }

    public function routeToHearingUnit(CaseModel $case): void
    {
        $huAdmin = User::where('role', 'hu_admin')->first();
        
        if ($huAdmin) {
            $case->update([
                'assigned_to' => $huAdmin->id,
                'status' => 'pending_hu_acceptance'
            ]);

            $this->notificationService->notify(
                $huAdmin,
                'case_assignment',
                'New Case Assignment',
                "Case {$case->case_number} has been assigned to you for review.",
                $case
            );
        }
    }

    public function acceptCase(CaseModel $case, User $user): bool
    {
        if (!$user->canAcceptFilings()) {
            return false;
        }

        $case->update([
            'status' => 'active',
            'accepted_at' => now()
        ]);
        
        AuditLog::log('accept_request', $user, $case);
        
        $this->notificationService->notify(
            $case->creator,
            'case_accepted',
            'Case Accepted',
            "Your case {$case->case_no} has been accepted and is now active.",
            $case
        );
        return true;
    }

    public function rejectCase(CaseModel $case, User $user, string $reason): bool
    {
        if (!$user->canRejectFilings()) {
            return false;
        }

        $case->update([
            'status' => 'rejected',
            'metadata' => array_merge($case->metadata ?? [], ['rejection_reason' => $reason])
        ]);

        AuditLog::log('reject_request', $user, $case, ['reason' => $reason]);

        // Notify case creator (ALU Clerk)
        $this->notificationService->notify(
            $case->creator,
            'case_rejected',
            'Case Rejected - Action Required',
            "Case {$case->case_no} has been rejected by HU. Reason: {$reason}. Please make the necessary corrections and resubmit.",
            $case
        );

        // Notify assigned attorney if any
        if ($case->assignedAttorney) {
            $this->notificationService->notify(
                $case->assignedAttorney,
                'case_rejected',
                'Case Rejected',
                "Case {$case->case_no} has been rejected by HU. Reason: {$reason}",
                $case
            );
        }

        return true;
    }

    public function approveCase(CaseModel $case, User $user): bool
    {
        if (!in_array($user->role, ['hu_admin', 'hu_clerk'])) {
            return false;
        }

        $case->update(['status' => 'approved']);
        AuditLog::log('approve_case', $user, $case);

        // Notify all parties
        foreach ($case->parties as $party) {
            $this->notificationService->notify(
                $party->person,
                'case_accepted',
                'Case Accepted',
                "Case {$case->case_no} has been accepted and is proceeding to hearing.",
                $case
            );
        }

        // Notify assigned attorney
        if ($case->assignedAttorney) {
            $this->notificationService->notify(
                $case->assignedAttorney,
                'case_accepted',
                'Case Accepted',
                "Case {$case->case_no} has been accepted and is proceeding to hearing.",
                $case
            );
        }

        // Notify hydrology expert
        if ($case->assignedHydrologyExpert) {
            $this->notificationService->notify(
                $case->assignedHydrologyExpert,
                'case_accepted',
                'Case Accepted',
                "Case {$case->case_no} has been accepted and is proceeding to hearing.",
                $case
            );
        }

        return true;
    }

    public function notifySelectedParties(CaseModel $case, array $recipients, ?string $customMessage, User $user): int
    {
        $notificationCount = 0;
        $baseMessage = "Case {$case->case_no} has been accepted and is proceeding to hearing.";
        $fullMessage = $customMessage ? $baseMessage . "\n\n" . $customMessage : $baseMessage;

        foreach ($recipients as $recipient) {
            [$type, $id] = explode('_', $recipient, 2);
            
            if ($type === 'party') {
                $party = $case->parties()->find($id);
                if ($party && $party->person) {
                    $this->notificationService->notify(
                        $party->person,
                        'case_accepted',
                        'Case Accepted - Action Required',
                        $fullMessage,
                        $case
                    );
                    $notificationCount++;
                }
            } elseif ($type === 'attorney') {
                $attorney = \App\Models\Attorney::find($id);
                if ($attorney) {
                    $this->notificationService->notify(
                        $attorney,
                        'case_accepted',
                        'Case Accepted - Client Notification',
                        $fullMessage,
                        $case
                    );
                    $notificationCount++;
                }
            }
        }

        AuditLog::log('notify_parties', $user, $case, ['recipients_count' => $notificationCount]);
        return $notificationCount;
    }

    private function getOseString(CaseModel $case): string
    {
        $oseString = '';
        if ($case->oseFileNumbers && $case->oseFileNumbers->count() > 0) {
            $oseList = [];
            foreach ($case->oseFileNumbers as $ose) {
                if ($ose->file_no_from && $ose->file_no_to) {
                    $oseList[] = $ose->file_no_from . '-' . $ose->file_no_to;
                } elseif ($ose->file_no_from) {
                    $oseList[] = $ose->file_no_from;
                } elseif ($ose->file_no_to) {
                    $oseList[] = $ose->file_no_to;
                }
            }
            
            if (count($oseList) > 1) {
                // Multiple OSE numbers: use first one + "et al."
                $oseString = ' - ' . $oseList[0] . ' et al.';
            } elseif (count($oseList) === 1) {
                // Single OSE number: use as is
                $oseString = ' - ' . $oseList[0];
            }
        }
        return $oseString;
    }

    private function getDisplayType(string $docType): string
    {
        // Try to get display name from database first
        $documentType = \App\Models\DocumentType::where('code', $docType)->first();
        if ($documentType) {
            return $documentType->name;
        }
        
        // Fallback to hardcoded map
        $docTypeMap = [
            'application' => 'Application',
            'notice_publication' => 'Notice of Publication',
            'protest_letter' => 'Protest Letter',
            'supporting' => 'Supporting Document',
            'request_to_docket' => 'Request to Docket',
            'request_pre_hearing' => 'Request for Pre-Hearing',
            'affidavit' => 'Affidavit',
            'exhibit' => 'Exhibit',
            'correspondence' => 'Correspondence',
            'technical_report' => 'Technical Report',
            'legal_brief' => 'Legal Brief',
            'motion' => 'Motion',
            'order' => 'Order',
            'other' => 'Other Document'
        ];
        
        return $docTypeMap[$docType] ?? ucfirst(str_replace('_', ' ', $docType));
    }

    private function stampPleadingDocuments(CaseModel $case, User $user): void
    {
        // Get documents that need stamping: Request to Docket and Request for Pre-Hearing
        $pleadingDocuments = $case->documents()->whereIn('pleading_type', ['request_to_docket', 'request_pre_hearing'])->get();
        
        foreach ($pleadingDocuments as $document) {
            $stampText = $this->generateStampText($case, $user);
            
            $document->update([
                'stamped' => true,
                'stamp_text' => $stampText,
                'stamped_at' => now()
            ]);
            
            AuditLog::log('stamp_document', $user, $case, [
                'document_id' => $document->id,
                'document_type' => $document->pleading_type,
                'stamp_text' => $stampText
            ]);
        }
    }

    private function generateStampText(CaseModel $case, User $user): string
    {
        $stampDate = now()->format('M d, Y');
        $stampTime = now()->format('g:i A');
        
        return "FILED\n" .
               "New Mexico Office of the State Engineer\n" .
               "Water Rights Hearing Unit\n" .
               "{$stampDate} at {$stampTime}\n" .
               "Case No: {$case->case_no}";
    }

    public function updateCase(CaseModel $case, array $data, User $user, ?Request $request = null): CaseModel
    {
        return DB::transaction(function () use ($case, $data, $user, $request) {
            // Update basic case info
            $case->update([
                'caption' => $data['caption'],
                'case_type' => $data['case_type'],
                'status' => $data['action'] === 'submit' ? 'submitted_to_hu' : 'draft',
                'submitted_at' => $data['action'] === 'submit' ? now() : $case->submitted_at
            ]);
            
            // Clear existing related data
            $case->parties()->delete();
            $case->oseFileNumbers()->delete();
            $case->serviceList()->delete();
            
            // Create/update parties with proper Person relationships
            if (isset($data['parties'])) {
                foreach ($data['parties'] as $partyData) {
                    if (!empty($partyData['email']) && !empty($partyData['role'])) {
                        // Update existing person or create new one
                        if (isset($partyData['person_id']) && $partyData['person_id']) {
                            $person = Person::find($partyData['person_id']);
                            if ($person) {
                                $person->update([
                                    'type' => $partyData['type'],
                                    'prefix' => $partyData['prefix'] ?? null,
                                    'first_name' => $partyData['first_name'] ?? null,
                                    'middle_name' => $partyData['middle_name'] ?? null,
                                    'last_name' => $partyData['last_name'] ?? null,
                                    'suffix' => $partyData['suffix'] ?? null,
                                    'organization' => $partyData['organization'] ?? null,
                                    'title' => $partyData['title'] ?? null,
                                    'email' => $partyData['email'],
                                    'phone_mobile' => $partyData['phone_mobile'] ?? null,
                                    'phone_office' => $partyData['phone_office'] ?? null,
                                    'address_line1' => $partyData['address_line1'] ?? null,
                                    'address_line2' => $partyData['address_line2'] ?? null,
                                    'city' => $partyData['city'] ?? null,
                                    'state' => $partyData['state'] ?? null,
                                    'zip' => $partyData['zip'] ?? null
                                ]);
                            }
                        } else {
                            $person = $this->createOrFindPerson($partyData);
                        }
                        
                        if ($person) {
                            // Create case party relationship
                            $clientParty = CaseParty::create([
                                'case_id' => $case->id,
                                'person_id' => $person->id,
                                'role' => $partyData['role'],
                                'service_enabled' => true
                            ]);
                            
                            // Handle attorney representation
                            if (isset($partyData['representation']) && $partyData['representation'] === 'attorney') {
                                $this->createAttorneyParty($case, $partyData, $clientParty);
                            }
                            
                            // Auto-create service list entry
                            ServiceList::create([
                                'case_id' => $case->id,
                                'person_id' => $person->id,
                                'email' => $person->email,
                                'service_method' => 'email',
                                'is_primary' => true
                            ]);
                        }
                    }
                }
            }
            
            // Recreate OSE file numbers
            if (isset($data['ose_numbers'])) {
                foreach ($data['ose_numbers'] as $oseData) {
                    if ((!empty($oseData['basin_code_from']) && !empty($oseData['file_no_from'])) || 
                        (!empty($oseData['basin_code_to']) && !empty($oseData['file_no_to']))) {
                        
                        $fileNoFrom = null;
                        $fileNoTo = null;
                        $basinCode = null;
                        
                        if (!empty($oseData['basin_code_from']) && !empty($oseData['file_no_from'])) {
                            $fileNoFrom = $oseData['basin_code_from'] . '-' . $oseData['file_no_from'];
                            $basinCode = $oseData['basin_code_from'];
                        }
                        
                        if (!empty($oseData['basin_code_to']) && !empty($oseData['file_no_to'])) {
                            $fileNoTo = $oseData['basin_code_to'] . '-' . $oseData['file_no_to'];
                            $basinCode = $basinCode ?: $oseData['basin_code_to'];
                        }
                        
                        OseFileNumber::create([
                            'case_id' => $case->id,
                            'basin_code' => $basinCode,
                            'file_no_from' => $fileNoFrom,
                            'file_no_to' => $fileNoTo
                        ]);
                    }
                }
            }
            
            // Service list is now auto-generated from parties
            
            // Handle new document uploads
            if ($request && $request->hasFile('documents')) {
                $this->handleDocumentUploads($case, $request, $user);
            }
            
            AuditLog::log('update_case', $user, $case, ['action' => $data['action']]);
            
            return $case;
        });
    }

    public function closeCase(CaseModel $case, User $user, string $reason): bool
    {
        if (!in_array($user->role, ['hu_admin', 'hu_clerk'])) {
            return false;
        }

        if (!in_array($case->status, ['active', 'approved'])) {
            return false;
        }

        $case->update([
            'status' => 'closed',
            'closed_at' => now(),
            'closed_by_user_id' => $user->id,
            'closure_reason' => $reason
        ]);

        AuditLog::log('close_case', $user, $case, ['reason' => $reason]);

        // Notify all parties
        foreach ($case->parties as $party) {
            $this->notificationService->notify(
                $party->person,
                'case_closed',
                'Case Closed',
                "Case {$case->case_no} has been closed. Reason: {$reason}",
                $case
            );
        }

        return true;
    }

    public function archiveCase(CaseModel $case, User $user): bool
    {
        if (!in_array($user->role, ['hu_admin', 'admin'])) {
            return false;
        }

        if ($case->status !== 'closed') {
            return false;
        }

        $case->update([
            'status' => 'archived',
            'archived_at' => now(),
            'archived_by_user_id' => $user->id
        ]);

        AuditLog::log('archive_case', $user, $case);

        return true;
    }

    public function notifyCaseSubmission(CaseModel $case, array $recipients = [], ?string $customMessage = null): void
    {
        $baseMessage = "The Hearing Unit is in receipt of the Request to Docket OR the Request for Pre-Hearing Scheduling Conference. The Request and the associated documents will be reviewed and either accepted or rejected. If a case is rejected, we hope to provide a reason for rejection (i.e. improper naming convention, did not include all required documents such as the Application, letters of protests, letter of denial and letter of aggrieval, compliance order, etc.)";
        
        $fullMessage = "Case {$case->case_no} has been submitted to the Hearing Unit for review. {$baseMessage}";
        if ($customMessage) {
            $fullMessage .= "\n\nAdditional Information: {$customMessage}";
        }

        // If no specific recipients provided, notify all
        if (empty($recipients)) {
            $recipients = $this->getAllCaseRecipients($case);
        }

        $notifiedEmails = [];
        foreach ($recipients as $recipient) {
            [$type, $id] = explode('_', $recipient, 2);
            
            if ($type === 'party') {
                $party = $case->parties()->find($id);
                if ($party && $party->person && $party->person->email) {
                    $this->notificationService->notify(
                        $party->person,
                        'case_submitted',
                        'Case Submitted for Review',
                        $fullMessage,
                        $case,
                        false // Don't log individual notifications
                    );
                    $notifiedEmails[] = $party->person->email;
                }
            } elseif ($type === 'attorney') {
                $attorney = $case->parties()->find($id);
                if ($attorney && $attorney->person && $attorney->person->email) {
                    $this->notificationService->notify(
                        $attorney->person,
                        'case_submitted',
                        'Case Submitted for Review',
                        $fullMessage,
                        $case,
                        false // Don't log individual notifications
                    );
                    $notifiedEmails[] = $attorney->person->email;
                }
            } elseif ($type === 'staff') {
                $user = User::find($id);
                if ($user && $user->email) {
                    $this->notificationService->notify(
                        $user,
                        'case_submitted',
                        'Case Submitted for Review',
                        $fullMessage,
                        $case,
                        false // Don't log individual notifications
                    );
                    $notifiedEmails[] = $user->email;
                }
            }
        }
        
        // Log once with all recipients
        if (!empty($notifiedEmails) && auth()->user()) {
            AuditLog::log('send_notification', auth()->user(), $case, [
                'notification_type' => 'case_submitted',
                'title' => 'Case Submitted for Review',
                'recipients' => $notifiedEmails,
                'recipient_count' => count($notifiedEmails)
            ]);
        }
    }



    private function getAllCaseRecipients(CaseModel $case): array
    {
        $recipients = [];
        
        // Add all parties
        foreach ($case->parties as $party) {
            $recipients[] = ($party->role === 'counsel' ? 'attorney_' : 'party_') . $party->id;
        }
        
        // Add assigned staff (exclude hydrology experts and WRDs)
        foreach ($case->assignments as $assignment) {
            if (!in_array($assignment->assignment_type, ['hydrology_expert', 'wrd'])) {
                $recipients[] = 'staff_' . $assignment->user_id;
            }
        }
        
        return $recipients;
    }

    private function processFileArray($files, CaseModel $case, string $docType, User $uploader, string $oseString): void
    {
        foreach ($files as $index => $file) {
            if ($file && $file->isValid()) {
                $fileSize = $file->getSize();
                $this->processSingleFile($file, $index, $case, $docType, $uploader, $oseString);
                unset($file);
                if ($fileSize > 5 * 1024 * 1024) {
                    gc_collect_cycles();
                }
            }
        }
    }

    private function processSingleFile($file, int $index, CaseModel $case, string $docType, User $uploader, string $oseString, ?string $customTitle = null): void
    {
        \Log::info('Attempting file upload', ['filename' => $file->getClientOriginalName(), 'disk' => 'public', 'path' => 'case_documents']);
        
        $displayType = $this->getDisplayType($docType);
        $titleOrType = !empty($customTitle) ? $customTitle : $displayType;
        
        $timestamp = now()->format('Y-m-d_His');
        $uniqueId = substr(md5(uniqid()), 0, 6);
        $sanitizedTitle = preg_replace('/[^A-Za-z0-9_-]/', '_', $titleOrType);
        $storedFilename = "{$timestamp}_{$sanitizedTitle}_{$uniqueId}.pdf";
        
        $path = $file->storeAs('case_documents', $storedFilename, 'public');
        \Log::info('File uploaded successfully', ['path' => $path, 'full_path' => storage_path('app/public/' . $path)]);
        
        $originalFilename = now()->format('Y-m-d') . ' - ' . $titleOrType . '.pdf';
        if ($index > 0) {
            $originalFilename = now()->format('Y-m-d') . ' - ' . $titleOrType . ' (' . ($index + 1) . ').pdf';
        }
        
        $documentData = [
            'case_id' => $case->id,
            'doc_type' => $docType,
            'custom_title' => $customTitle,
            'original_filename' => $originalFilename,
            'stored_filename' => $storedFilename,
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => md5_file($file->getRealPath()),
            'storage_uri' => $path,
            'uploaded_by_user_id' => $uploader->id,
            'uploaded_at' => now()
        ];
        
        if (in_array($docType, ['request_to_docket', 'request_pre_hearing'])) {
            $documentData['pleading_type'] = $docType;
        }
        
        Document::create($documentData);
    }
}

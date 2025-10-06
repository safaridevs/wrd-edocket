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
                        
                        // Handle attorney if needed
                        $attorneyId = null;
                        if (isset($partyData['representation']) && $partyData['representation'] === 'attorney') {
                            $attorneyId = $this->createOrFindAttorney($partyData);
                        }
                        
                        // Create case party relationship
                        CaseParty::create([
                            'case_id' => $case->id,
                            'person_id' => $person->id,
                            'role' => $partyData['role'],
                            'service_enabled' => true,
                            'attorney_id' => $attorneyId,
                            'representation' => $partyData['representation'] ?? 'self'
                        ]);
                        
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
            if ($request && $request->hasFile('documents')) {
                $this->handleDocumentUploads($case, $request, $creator);
            }
            
            AuditLog::log('create_case', $creator, $case, ['case_number' => $case->case_no]);
            
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
    
    private function createOrFindAttorney(array $data): ?int
    {
        // If attorney_id is provided, use existing attorney
        if (!empty($data['attorney_id'])) {
            return $data['attorney_id'];
        }
        
        // Otherwise create new attorney if name and email provided
        if (empty($data['attorney_name']) || empty($data['attorney_email'])) {
            return null;
        }
        
        // Try to find existing attorney by email
        $attorney = \App\Models\Attorney::where('email', $data['attorney_email'])->first();
        
        if (!$attorney) {
            $attorney = \App\Models\Attorney::create([
                'name' => $data['attorney_name'],
                'email' => $data['attorney_email'],
                'phone' => $data['attorney_phone'] ?? null,
                'bar_number' => $data['bar_number'] ?? null
            ]);
        }
        
        return $attorney->id;
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
        
        $documentTypes = ['application', 'request_to_docket', 'request_for_pre_hearing'];
        $pleadingType = null; // Will be determined by document type
        
        \Log::info('Processing document types: ' . implode(', ', $documentTypes));
        
        foreach ($documentTypes as $type) {
            if ($request->hasFile("documents.{$type}")) {
                \Log::info("Processing file for type: {$type}");
                $file = $request->file("documents.{$type}");
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('case_documents', $filename, 'public');
                    
                    // Apply standardized naming convention
                    $displayType = $this->getDisplayType($type);
                    $originalFilename = now()->format('Y-m-d') . ' - ' . $displayType . $oseString . '.pdf';
                    
                    $documentData = [
                        'case_id' => $case->id,
                        'doc_type' => $type,
                        'original_filename' => $originalFilename,
                        'stored_filename' => $filename,
                        'mime' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                        'checksum' => md5_file($file->getRealPath()),
                        'storage_uri' => $path,
                        'uploaded_by_user_id' => $uploader->id,
                        'uploaded_at' => now()
                    ];
                    
                    // Add pleading type for request documents
                    if ($type === 'request_to_docket') {
                        $documentData['pleading_type'] = 'request_to_docket';
                    } elseif ($type === 'request_for_pre_hearing') {
                        $documentData['pleading_type'] = 'request_for_pre_hearing';
                    }
                    
                    $document = Document::create($documentData);
                    \Log::info("Document created with ID: {$document->id} for file: {$filename}");
                } else {
                    \Log::warning("Invalid file for type: {$type}");
                }
            } else {
                \Log::info("No file found for type: {$type}");
            }
        }
        
        // Handle multiple notice_publication documents
        if ($request->hasFile('documents.notice_publication')) {
            foreach ($request->file('documents.notice_publication') as $index => $file) {
                if ($file && $file->isValid()) {
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('case_documents', $filename, 'public');
                    
                    $displayName = now()->format('Y-m-d') . ' - Notice of Publication' . $oseString . '.pdf';
                    if ($index > 0) {
                        $displayName = now()->format('Y-m-d') . ' - Notice of Publication' . $oseString . ' (' . ($index + 1) . ').pdf';
                    }
                    
                    Document::create([
                        'case_id' => $case->id,
                        'doc_type' => 'notice_publication',
                        'original_filename' => $displayName,
                        'stored_filename' => $filename,
                        'mime' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                        'checksum' => md5_file($file->getRealPath()),
                        'storage_uri' => $path,
                        'uploaded_by_user_id' => $uploader->id,
                        'uploaded_at' => now()
                    ]);
                }
            }
        }
        
        // Handle multiple document types with standardized naming
        $multipleDocTypes = [
            'protest_letter' => 'Protest Letter',
            'supporting' => 'Supporting Document'
        ];
        
        foreach ($multipleDocTypes as $docType => $displayType) {
            if ($request->hasFile("documents.{$docType}")) {
                foreach ($request->file("documents.{$docType}") as $index => $file) {
                    if ($file && $file->isValid()) {
                        $filename = time() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs('case_documents', $filename, 'public');
                        
                        // Generate standardized display name with OSE numbers
                        $displayName = now()->format('Y-m-d') . ' - ' . $displayType . $oseString . '.pdf';
                        if ($index > 0) {
                            $displayName = now()->format('Y-m-d') . ' - ' . $displayType . $oseString . ' (' . ($index + 1) . ').pdf';
                        }
                        
                        Document::create([
                            'case_id' => $case->id,
                            'doc_type' => $docType,
                            'original_filename' => $displayName,
                            'stored_filename' => $filename,
                            'mime' => $file->getMimeType(),
                            'size_bytes' => $file->getSize(),
                            'checksum' => md5_file($file->getRealPath()),
                            'storage_uri' => $path,
                            'uploaded_by_user_id' => $uploader->id,
                            'uploaded_at' => now()
                        ]);
                    }
                }
            }
        }
        
        // Handle other document types
        if ($request->has('documents.other')) {
            foreach ($request->input('documents.other') as $index => $otherDoc) {
                if (isset($otherDoc['type']) && $otherDoc['type'] && $request->hasFile("documents.other.{$index}.file")) {
                    $file = $request->file("documents.other.{$index}.file");
                    if ($file && $file->isValid()) {
                        $filename = time() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs('case_documents', $filename, 'public');
                        
                        $displayType = $this->getDisplayType($otherDoc['type']);
                        
                        $originalFilename = now()->format('Y-m-d') . ' - ' . $displayType . $oseString . '.pdf';
                        
                        $documentData = [
                            'case_id' => $case->id,
                            'doc_type' => $otherDoc['type'],
                            'original_filename' => $originalFilename,
                            'stored_filename' => $filename,
                            'mime' => $file->getMimeType(),
                            'size_bytes' => $file->getSize(),
                            'checksum' => md5_file($file->getRealPath()),
                            'storage_uri' => $path,
                            'uploaded_by_user_id' => $uploader->id,
                            'uploaded_at' => now()
                        ];
                        
                        // Set pleading type for pleading documents
                        if (in_array($otherDoc['type'], ['request_to_docket', 'request_for_pre_hearing'])) {
                            $documentData['pleading_type'] = $otherDoc['type'];
                        }
                        
                        Document::create($documentData);
                        \Log::info("Other document created: {$otherDoc['type']} - {$filename}");
                    }
                }
            }
        }
    }

    public function submitToHU(CaseModel $case, User $user): bool
    {
        if ($case->changeStatus('submitted_to_hu', $user, 'Case submitted for HU review')) {
            AuditLog::log('submit_to_hu', $user, $case);
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
        if ($user->role !== 'hu_admin') {
            return false;
        }

        $case->update(['status' => 'approved']);
        AuditLog::log('approve_case', $user, $case);

        // Stamp pleading documents
        $this->stampPleadingDocuments($case, $user);

        // Notify all parties
        foreach ($case->parties as $party) {
            $this->notificationService->notify(
                $party->person,
                'case_accepted',
                'Case Approved',
                "Case {$case->case_no} has been approved and is proceeding to hearing.",
                $case
            );
        }

        // Notify assigned attorney
        if ($case->assignedAttorney) {
            $this->notificationService->notify(
                $case->assignedAttorney,
                'case_accepted',
                'Case Approved',
                "Case {$case->case_no} has been approved and is proceeding to hearing.",
                $case
            );
        }

        // Notify hydrology expert
        if ($case->assignedHydrologyExpert) {
            $this->notificationService->notify(
                $case->assignedHydrologyExpert,
                'case_accepted',
                'Case Approved',
                "Case {$case->case_no} has been approved and is proceeding to hearing.",
                $case
            );
        }

        return true;
    }

    public function notifySelectedParties(CaseModel $case, array $recipients, ?string $customMessage, User $user): int
    {
        $notificationCount = 0;
        $baseMessage = "Case {$case->case_no} has been approved and is proceeding to hearing.";
        $fullMessage = $customMessage ? $baseMessage . "\n\n" . $customMessage : $baseMessage;

        foreach ($recipients as $recipient) {
            [$type, $id] = explode('_', $recipient, 2);
            
            if ($type === 'party') {
                $party = $case->parties()->find($id);
                if ($party && $party->person) {
                    $this->notificationService->notify(
                        $party->person,
                        'case_approved',
                        'Case Approved - Action Required',
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
                        'case_approved',
                        'Case Approved - Client Notification',
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
            $oseString = $oseList ? ' - ' . implode(', ', $oseList) : '';
        }
        return $oseString;
    }

    private function getDisplayType(string $docType): string
    {
        $docTypeMap = [
            'application' => 'Application',
            'notice_publication' => 'Notice of Publication',
            'protest_letter' => 'Protest Letter',
            'supporting' => 'Supporting Document',
            'request_to_docket' => 'Request to Docket',
            'request_for_pre_hearing' => 'Request for Pre-Hearing',
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
        $pleadingDocuments = $case->documents()->whereIn('pleading_type', ['request_to_docket', 'request_for_pre_hearing'])->get();
        
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
                            // Handle attorney if needed
                            $attorneyId = null;
                            if (isset($partyData['representation']) && $partyData['representation'] === 'attorney') {
                                $attorneyId = $this->createOrFindAttorney($partyData);
                            }
                            
                            // Create case party relationship
                            CaseParty::create([
                                'case_id' => $case->id,
                                'person_id' => $person->id,
                                'role' => $partyData['role'],
                                'service_enabled' => true,
                                'attorney_id' => $attorneyId,
                                'representation' => $partyData['representation'] ?? 'self'
                            ]);
                            
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

    public function stampDocument(Document $document, User $user): bool
    {
        if (!in_array($document->pleading_type, ['request_to_docket', 'request_for_pre_hearing'])) {
            return false;
        }
        
        $stampText = $this->generateStampText($document->case, $user);
        
        $document->update([
            'stamped' => true,
            'stamp_text' => $stampText,
            'stamped_at' => now()
        ]);
        
        AuditLog::log('stamp_document', $user, $document->case, [
            'document_id' => $document->id,
            'document_type' => $document->pleading_type,
            'stamp_text' => $stampText
        ]);
        
        return true;
    }
}
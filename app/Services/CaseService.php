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
                'status' => 'draft'
            ]);
            
            // Store party names in case metadata (no Person records needed for names only)
            $partyData = [];
            if (isset($data['applicants'])) {
                foreach ($data['applicants'] as $name) {
                    if (!empty(trim($name))) {
                        $partyData['applicants'][] = trim($name);
                    }
                }
            }
            if (isset($data['protestants'])) {
                foreach ($data['protestants'] as $name) {
                    if (!empty(trim($name))) {
                        $partyData['protestants'][] = trim($name);
                    }
                }
            }
            if (!empty($partyData)) {
                $case->update(['metadata' => $partyData]);
            }
            
            // Create OSE file numbers
            if (isset($data['ose_numbers'])) {
                foreach ($data['ose_numbers'] as $oseData) {
                    if (!empty($oseData['basin_code']) && (!empty($oseData['file_no_from']) || !empty($oseData['file_no_to']))) {
                        OseFileNumber::create([
                            'case_id' => $case->id,
                            'basin_code' => $oseData['basin_code'],
                            'file_no_from' => $oseData['file_no_from'] ?? null,
                            'file_no_to' => $oseData['file_no_to'] ?? null
                        ]);
                    }
                }
            }
            
            // Create service list
            if (isset($data['service_list'])) {
                foreach ($data['service_list'] as $serviceData) {
                    if (!empty($serviceData['name']) && !empty($serviceData['email'])) {
                        $person = $this->createOrFindPerson($serviceData);
                        
                        ServiceList::create([
                            'case_id' => $case->id,
                            'person_id' => $person->id,
                            'email' => $serviceData['email'],
                            'service_method' => $serviceData['method'] ?? 'email'
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
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'organization' => $data['organization'] ?? null,
                'email' => $data['email'],
                'phone_mobile' => $data['phone'] ?? null,
                'address_line1' => $data['address_line1'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'zip' => $data['zip'] ?? null
            ]);
        }
        
        return $person;
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
    
    private function handleDocumentUploads(CaseModel $case, Request $request, User $uploader): void
    {
        $documentTypes = ['application', 'notice_publication', 'request_to_docket'];
        
        foreach ($documentTypes as $type) {
            if ($request->hasFile("documents.{$type}")) {
                $file = $request->file("documents.{$type}");
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('case_documents', $filename, 'public');
                
                Document::create([
                    'case_id' => $case->id,
                    'doc_type' => $type,
                    'original_filename' => $file->getClientOriginalName(),
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
        
        // Handle multiple protest letters
        if ($request->hasFile('documents.protest_letter')) {
            foreach ($request->file('documents.protest_letter') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('case_documents', $filename, 'public');
                
                Document::create([
                    'case_id' => $case->id,
                    'doc_type' => 'protest_letter',
                    'original_filename' => $file->getClientOriginalName(),
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

        if ($case->changeStatus('active', $user, 'Case accepted by HU')) {
            AuditLog::log('accept_request', $user, $case);
            
            $this->notificationService->notify(
                $case->creator,
                'case_accepted',
                'Case Accepted',
                "Your case {$case->case_number} has been accepted and is now active.",
                $case
            );
            return true;
        }
        
        return false;
    }

    public function rejectCase(CaseModel $case, User $user, string $reason): bool
    {
        if (!$user->canRejectFilings() || $case->assigned_to !== $user->id) {
            return false;
        }

        $case->update([
            'status' => 'rejected',
            'metadata' => array_merge($case->metadata ?? [], ['rejection_reason' => $reason])
        ]);

        $this->notificationService->notify(
            $case->creator,
            'case_rejected',
            'Case Rejected',
            "Your case {$case->case_number} has been rejected. Reason: {$reason}",
            $case
        );

        return true;
    }

    public function updateCase(CaseModel $case, array $data, User $user, ?Request $request = null): CaseModel
    {
        return DB::transaction(function () use ($case, $data, $user, $request) {
            // Update basic case info
            $case->update([
                'caption' => $data['caption'],
                'case_type' => $data['case_type'],
                'status' => $data['action'] === 'submit' ? 'submitted_to_hu' : 'draft'
            ]);
            
            // Clear existing related data
            $case->parties()->delete();
            $case->oseFileNumbers()->delete();
            $case->serviceList()->delete();
            
            // Update party names in case metadata
            $partyData = [];
            if (isset($data['applicants'])) {
                foreach ($data['applicants'] as $name) {
                    if (!empty(trim($name))) {
                        $partyData['applicants'][] = trim($name);
                    }
                }
            }
            if (isset($data['protestants'])) {
                foreach ($data['protestants'] as $name) {
                    if (!empty(trim($name))) {
                        $partyData['protestants'][] = trim($name);
                    }
                }
            }
            if (!empty($partyData)) {
                $case->update(['metadata' => $partyData]);
            }
            
            // Recreate OSE file numbers
            if (isset($data['ose_numbers'])) {
                foreach ($data['ose_numbers'] as $oseData) {
                    if (!empty($oseData['basin_code']) && (!empty($oseData['file_no_from']) || !empty($oseData['file_no_to']))) {
                        OseFileNumber::create([
                            'case_id' => $case->id,
                            'basin_code' => $oseData['basin_code'],
                            'file_no_from' => $oseData['file_no_from'] ?? null,
                            'file_no_to' => $oseData['file_no_to'] ?? null
                        ]);
                    }
                }
            }
            
            // Recreate service list
            if (isset($data['service_list'])) {
                foreach ($data['service_list'] as $serviceData) {
                    if (!empty($serviceData['name']) && !empty($serviceData['email'])) {
                        $person = $this->createOrFindPerson($serviceData);
                        
                        ServiceList::create([
                            'case_id' => $case->id,
                            'person_id' => $person->id,
                            'email' => $serviceData['email'],
                            'service_method' => $serviceData['method'] ?? 'email'
                        ]);
                    }
                }
            }
            
            // Handle new document uploads
            if ($request && $request->hasFile('documents')) {
                $this->handleDocumentUploads($case, $request, $user);
            }
            
            AuditLog::log('update_case', $user, $case, ['action' => $data['action']]);
            
            return $case;
        });
    }
}
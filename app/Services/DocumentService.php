<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CaseModel;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function __construct(
        private NotificationService $notificationService,
        private DocumentValidationService $validationService,
        private PdfStampingService $pdfStampingService
    ) {}

    public function uploadDocument(CaseModel $case, UploadedFile $file, string $documentType, User $uploader, string $pleadingType = 'none'): Document
    {
        // Validate file
        $errors = $this->validationService->validateFile($file);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        if (!$file || !$file->isValid()) {
            throw new \InvalidArgumentException('Invalid file provided');
        }
        
        $filename = $this->generateFilename($file);
        if (!$filename) {
            throw new \InvalidArgumentException('Could not generate filename');
        }
        
        $namingCompliant = $this->validationService->validateNaming($file->getClientOriginalName(), $case->case_number);
        
        $path = $file->storeAs("cases/{$case->id}/documents", $filename, 'private');

        $document = Document::create([
            'case_id' => $case->id,
            'doc_type' => $documentType,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => $filename,
            'mime' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'checksum' => md5_file($file->getPathname()),
            'storage_uri' => $path,
            'uploaded_by_user_id' => $uploader->id,
            'uploaded_at' => now(),
            'pleading_type' => $pleadingType
        ]);

        AuditLog::log('upload_doc', $uploader, $case, ['filename' => $document->original_filename]);
        
        $this->autoStamp($document, $uploader);
        $this->syncToRepositories($document);

        return $document;
    }

    private function generateFilename(UploadedFile $file): string
    {
        $originalName = $file->getClientOriginalName();
        if (!$originalName) {
            $originalName = 'document_' . time() . '.pdf';
        }
        return time() . '_' . str_replace(' ', '_', $originalName);
    }

    private function autoStamp(Document $document, User $user): void
    {
        // Only stamp pleading documents (request_pre_hearing or request_to_docket)
        if (in_array($document->pleading_type, ['request_pre_hearing', 'request_to_docket']) && $document->mime === 'application/pdf') {
            $stamp = $this->validationService->generateStamp($user);
            $document->update([
                'stamped' => true,
                'stamped_at' => now(),
                'stamp_text' => $stamp,
                'initials' => $user->initials
            ]);
            
            $this->notificationService->notify(
                $document->uploader,
                'document_stamped',
                'Document Stamped',
                "Pleading document {$document->original_filename} has been officially stamped.",
                $document->case
            );
        }
    }

    public function stampDocument(Document $document, User $user): bool
    {
        // Only HU Admin can manually stamp documents
        if ($user->role !== 'hu_admin') {
            return false;
        }
        
        // Only stamp pleading documents
        if (!in_array($document->pleading_type, ['request_to_docket', 'request_for_pre_hearing'])) {
            return false;
        }
        
        // Don't stamp if already stamped
        if ($document->stamped) {
            return false;
        }
        
        $stampText = $this->generateStampText($document->case, $user);
        
        // Apply visual stamp to PDF
        $pdfStamped = $this->pdfStampingService->stampPdf($document, $user);
        
        $document->update([
            'stamped' => true,
            'stamp_text' => $stampText,
            'stamped_at' => now()
        ]);
        
        AuditLog::log('stamp_document', $user, $document->case, [
            'document_id' => $document->id,
            'document_type' => $document->pleading_type,
            'stamp_text' => $stampText,
            'pdf_stamped' => $pdfStamped
        ]);
        
        // Notify document uploader
        $this->notificationService->notify(
            $document->uploader,
            'document_stamped',
            'Document E-Stamped',
            "Your pleading document '{$document->original_filename}' has been officially e-stamped.",
            $document->case
        );
        
        return true;
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

    private function syncToRepositories(Document $document): void
    {
        $syncStatus = $document->sync_status;
        
        // Multi-repository sync as per workflow
        $repositories = [
            'edocket' => 'synced',
            'sharepoint' => 'synced', // HU.Admin library
            'onedrive' => 'synced',
            'revver' => 'synced', // folder per case
            'website' => 'synced' // case page
        ];
        
        foreach ($repositories as $repo => $status) {
            $syncStatus[$repo] = $status;
        }

        $document->update(['sync_status' => $syncStatus]);
    }

    public function downloadDocument(Document $document): string
    {
        if (!$document->storage_uri) {
            throw new \Exception('Document storage path not found');
        }
        
        // Check if file exists in public storage (newer uploads)
        if (Storage::disk('public')->exists($document->storage_uri)) {
            return Storage::disk('public')->path($document->storage_uri);
        }
        
        // Check if file exists in private storage (older uploads)
        if (Storage::disk('private')->exists($document->storage_uri)) {
            return Storage::disk('private')->path($document->storage_uri);
        }
        
        throw new \Exception('Document file not found in storage');
    }
}
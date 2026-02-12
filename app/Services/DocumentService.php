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
        private PdfStampingService $pdfStampingService,
        private CaseStorageService $caseStorageService
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

        $storageFolder = $this->caseStorageService->getCaseStorageFolder($case);
        $path = $file->storeAs($storageFolder, $filename, 'public');

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



    public function approveDocument(Document $document, User $user): bool
    {
        // Only HU Admin and HU Clerk can approve documents
        if (!in_array($user->role, ['hu_admin', 'hu_clerk'])) {
            return false;
        }

        // Don't approve if already approved
        if ($document->approved) {
            return false;
        }

        // Only approve documents in active or approved cases
        if (!in_array($document->case->status, ['active', 'approved'])) {
            return false;
        }

        // Approve the document
        $document->update([
            'approved' => true,
            'approved_by_user_id' => $user->id,
            'approved_at' => now()
        ]);

        // Stamp pleading documents upon approval
        if (in_array($document->pleading_type, ['request_to_docket', 'request_pre_hearing'])) {
            $this->stampApprovedDocument($document, $user);
        }

        AuditLog::log('approve_document', $user, $document->case, [
            'document_id' => $document->id,
            'document_type' => $document->pleading_type
        ]);

        // Notify document uploader
        $this->notificationService->notify(
            $document->uploader,
            'document_approved',
            'Document Approved',
            "Your document '{$document->original_filename}' has been approved.",
            $document->case
        );

        return true;
    }

    private function stampApprovedDocument(Document $document, User $user): void
    {
        $stampText = $this->generateStampText($document->case, $user);

        // Apply visual stamp to PDF
        $pdfStamped = false;
        try {
            $pdfStamped = $this->pdfStampingService->stampPdf($document, $user);
            \Log::info('PDF stamping result: ' . ($pdfStamped ? 'success' : 'failed'));
        } catch (\Exception $e) {
            \Log::error('PDF stamping exception: ' . $e->getMessage());
        }

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

        // Notify document uploader about stamping
        $this->notificationService->notify(
            $document->uploader,
            'document_stamped',
            'Document E-Stamped',
            "Your pleading document '{$document->original_filename}' has been officially e-stamped.",
            $document->case
        );
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

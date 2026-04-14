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

        $storageFolder = $this->caseStorageService->getCaseStorageFolder($case);
        $filename = $this->generateFilename($file, $storageFolder);
        if (!$filename) {
            throw new \InvalidArgumentException('Could not generate filename');
        }

        $namingCompliant = $this->validationService->validateNaming($file->getClientOriginalName(), $case->case_number);

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

    private function generateFilename(UploadedFile $file, string $storageFolder): string
    {
        $originalName = $file->getClientOriginalName();
        if (!$originalName) {
            $originalName = 'document.pdf';
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: pathinfo($originalName, PATHINFO_EXTENSION) ?: 'pdf');
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $baseName = preg_replace('/ - [A-Za-z0-9]+-\d+(?: et al\.)?(?=( \(\d+\))?$)/', '', $baseName);
        $baseName = preg_replace('/[\\\\\\/:*?"<>|]/', '-', (string) $baseName);
        $baseName = trim(preg_replace('/\s+/', ' ', $baseName) ?: 'document');
        if ($baseName === '') {
            $baseName = 'document';
        }

        $storageFolder = trim($storageFolder, '/');

        // Keep disk filenames readable while appending a unique timestamp to avoid collisions.
        do {
            $timestamp = now()->format('Ymd_His_u');
            $candidate = "{$baseName} - {$timestamp}.{$extension}";
            $path = $storageFolder === '' ? $candidate : "{$storageFolder}/{$candidate}";
        } while (Storage::disk('public')->exists($path));

        return $candidate;
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

        // Only accept documents in active cases
        if ($document->case->status !== 'active') {
            return false;
        }

        // Accept the document
        $document->update([
            'approved' => true,
            'approved_by_user_id' => $user->id,
            'approved_at' => now()
        ]);

        AuditLog::log('approve_document', $user, $document->case, [
            'document_id' => $document->id,
            'document_type' => $document->pleading_type
        ]);

        // Notify document uploader
        $this->notificationService->notify(
            $document->uploader,
            'document_approved',
            'Document Accepted',
            "Your document '{$document->original_filename}' has been accepted.",
            $document->case
        );

        return true;
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

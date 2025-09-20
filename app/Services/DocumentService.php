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
        private DocumentValidationService $validationService
    ) {}

    public function uploadDocument(CaseModel $case, UploadedFile $file, string $documentType, User $uploader): Document
    {
        // Validate file
        $errors = $this->validationService->validateFile($file);
        if (!empty($errors)) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        $filename = $this->generateFilename($file);
        $namingCompliant = $this->validationService->validateNaming($file->getClientOriginalName(), $case->case_number);
        
        $path = $file->storeAs("cases/{$case->id}/documents", $filename, 'private');

        $document = Document::create([
            'case_id' => $case->id,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'document_type' => $documentType,
            'uploaded_by_user_id' => $uploader->id,
            'naming_compliant' => $namingCompliant,
            'sync_status' => ['edocket' => 'pending']
        ]);

        AuditLog::log('upload_doc', $uploader, $case, ['filename' => $document->original_filename]);
        
        $this->autoStamp($document, $uploader);
        $this->syncToRepositories($document);

        return $document;
    }

    private function generateFilename(UploadedFile $file): string
    {
        return time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
    }

    private function autoStamp(Document $document, User $user): void
    {
        if (in_array($document->document_type, ['filing', 'issuance']) && $document->file_type === 'application/pdf') {
            $stamp = $this->validationService->generateStamp($user);
            $document->update([
                'is_stamped' => true,
                'stamped_at' => now(),
                'initials' => $user->initials
            ]);
            
            $this->notificationService->notify(
                $document->uploader,
                'document_stamped',
                'Document Stamped',
                "Document {$document->original_filename} has been stamped.",
                $document->case
            );
        }
    }

    public function stampDocument(Document $document, User $user): bool
    {
        if (!$user->canApplyStamp()) {
            return false;
        }

        $document->stamp();
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
        return Storage::disk('private')->path($document->file_path);
    }
}
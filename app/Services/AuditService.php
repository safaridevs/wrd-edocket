<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\CaseModel;

class AuditService
{
    public static function logCaseCreated(CaseModel $case, User $user): void
    {
        AuditLog::log('create_case', $user, $case, [
            'case_number' => $case->case_number,
            'case_type' => $case->case_type
        ]);
    }

    public static function logDocumentUploaded(CaseModel $case, User $user, string $filename): void
    {
        AuditLog::log('upload_doc', $user, $case, [
            'filename' => $filename,
            'file_size' => request()->file('document')?->getSize()
        ]);
    }

    public static function logCaseStatusChange(CaseModel $case, User $user, string $oldStatus, string $newStatus): void
    {
        AuditLog::log('update_case', $user, $case, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);
    }

    public static function logDocumentApproval(CaseModel $case, User $user, int $documentId): void
    {
        AuditLog::log('approve_doc', $user, $case, [
            'document_id' => $documentId
        ]);
    }
}
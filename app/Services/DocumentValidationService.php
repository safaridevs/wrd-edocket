<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class DocumentValidationService
{
    public function validateNaming(string $filename, string $caseNumber): bool
    {
        // YYYY-MM-DD — <Doc Type> — <Short Description> — <CaseNo>
        $pattern = '/^\d{4}-\d{2}-\d{2}\s—\s.+\s—\s.+\s—\s' . preg_quote($caseNumber) . '$/';
        return preg_match($pattern, pathinfo($filename, PATHINFO_FILENAME));
    }

    public function validateFile(UploadedFile $file): array
    {
        $errors = [];
        
        // File type validation
        $allowedTypes = ['pdf', 'docx', 'mp4', 'm4a'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedTypes)) {
            $errors[] = 'Invalid file type. Allowed: PDF, DOCX, MP4, M4A';
        }

        // Size validation (100MB)
        if ($file->getSize() > 100 * 1024 * 1024) {
            $errors[] = 'File size exceeds 100MB limit';
        }

        // MIME type validation
        $allowedMimes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'video/mp4',
            'audio/mp4'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Invalid MIME type';
        }

        return $errors;
    }

    public function generateStamp(User $user): string
    {
        $timezone = new \DateTimeZone('America/Denver');
        $now = new \DateTime('now', $timezone);
        
        return sprintf(
            "ELECTRONICALLY FILED\n%s\nOSE HEARING UNIT / %s",
            $now->format('D m/d/Y @ g:i A'),
            $user->initials ?? strtoupper(substr($user->name, 0, 2))
        );
    }
}
<?php

namespace App\Services;

use App\Models\CaseModel;

class CaseStorageService
{
    public function getCaseStorageFolder(CaseModel $case): string
    {
        $metadata = $case->metadata ?? [];
        if (!empty($metadata['storage_folder'])) {
            return $metadata['storage_folder'];
        }

        $case->loadMissing('parties.person');

        $year = $this->getCaseYear($case);
        $sequence = $this->getCaseSequence($case);
        $applicantDisplay = $this->getApplicantDisplay($case);

        $folder = "{$year}/{$sequence} {$applicantDisplay}";

        $metadata['storage_folder'] = $folder;
        $case->metadata = $metadata;
        $case->save();

        return $folder;
    }

    private function getCaseYear(CaseModel $case): string
    {
        if (!empty($case->case_no) && preg_match('/^(\\d{4})-/', $case->case_no, $matches)) {
            return $matches[1];
        }

        return $case->created_at ? $case->created_at->format('Y') : now()->format('Y');
    }

    private function getCaseSequence(CaseModel $case): string
    {
        if (!empty($case->case_no) && preg_match('/^(\\d{4})-(\\d+)/', $case->case_no, $matches)) {
            $raw = ltrim($matches[2], '0');
            $number = $raw === '' ? 0 : (int) $raw;
            $width = max(3, strlen($matches[2]));
            return str_pad((string) $number, $width, '0', STR_PAD_LEFT);
        }

        return str_pad((string) $case->id, 3, '0', STR_PAD_LEFT);
    }

    private function getApplicantDisplay(CaseModel $case): string
    {
        $names = $case->parties
            ->where('role', 'applicant')
            ->map(fn($party) => $party->person?->full_name)
            ->filter()
            ->values();

        if ($names->count() === 1) {
            return $this->sanitizeFolderSegment($names[0]);
        }

        if ($names->count() === 2) {
            return $this->sanitizeFolderSegment($names[0] . ' & ' . $names[1]);
        }

        if ($names->count() > 2) {
            return $this->sanitizeFolderSegment($names[0] . ' et al');
        }

        $fallbackRoles = ['respondent', 'violator', 'alleged_violator', 'aggrieved_party', 'protestant', 'intervenor'];
        $fallbackNames = $case->parties
            ->whereIn('role', $fallbackRoles)
            ->map(fn($party) => $party->person?->full_name)
            ->filter()
            ->values();

        if ($fallbackNames->count() === 1) {
            return $this->sanitizeFolderSegment($fallbackNames[0]);
        }

        if ($fallbackNames->count() === 2) {
            return $this->sanitizeFolderSegment($fallbackNames[0] . ' & ' . $fallbackNames[1]);
        }

        if ($fallbackNames->count() > 2) {
            return $this->sanitizeFolderSegment($fallbackNames[0] . ' et al');
        }

        return 'Unknown Applicant';
    }

    private function sanitizeFolderSegment(string $value): string
    {
        $value = preg_replace('/[<>:"\\/\\\\|?*]+/', '_', $value);
        $value = preg_replace('/\\s+/', ' ', $value ?? '');
        $value = trim($value);
        $value = rtrim($value, " .");

        return $value !== '' ? $value : 'Unknown Applicant';
    }
}

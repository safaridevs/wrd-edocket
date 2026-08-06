<?php

namespace App\View;

use Illuminate\Support\Collection;

class DocumentHierarchy
{
    private const ALU_ROLES = [
        'alu_mgr',
        'alu_clerk',
        'alu_paralegal',
        'alu_atty',
        'alu_attorney',
        'alu_managing_atty',
        'alu_manager',
    ];

    private const PLEADING_TYPES = [
        'request_pre_hearing',
        'request_to_docket',
    ];

    public static function groups(Collection $documents): array
    {
        $documents = self::chronological($documents);
        $aluDocuments = $documents
            ->filter(fn ($document) => self::isAluDocument($document))
            ->values();

        $pleadingDocuments = $aluDocuments
            ->filter(fn ($document) => in_array((string) $document->pleading_type, self::PLEADING_TYPES, true))
            ->values();

        $groups = [];

        if ($pleadingDocuments->isNotEmpty()) {
            $groups[] = [
                'key' => 'pleading',
                'label' => 'Pleading Documents',
                'level' => 0,
                'show_heading' => false,
                'documents' => $pleadingDocuments,
            ];
        }

        $pleadingIds = $pleadingDocuments->pluck('id')->all();
        $pleadingChildren = $aluDocuments
            ->reject(fn ($document) => in_array($document->id, $pleadingIds, true))
            ->sort(function ($left, $right) {
                $leftIsApplication = $left->doc_type === 'application';
                $rightIsApplication = $right->doc_type === 'application';

                if ($leftIsApplication !== $rightIsApplication) {
                    return $leftIsApplication ? -1 : 1;
                }

                return self::compareChronologically($left, $right);
            })
            ->values();

        if ($pleadingChildren->isNotEmpty()) {
            $groups[] = [
                'key' => 'pleading_children',
                'label' => 'Pleading Documents',
                'level' => $pleadingDocuments->isNotEmpty() ? 1 : 0,
                'show_heading' => false,
                'documents' => $pleadingChildren,
            ];
        }

        $nonAluDocuments = $documents
            ->reject(fn ($document) => self::isAluDocument($document))
            ->values();

        if ($nonAluDocuments->isNotEmpty()) {
            $groups[] = [
                'key' => 'case_filings',
                'label' => 'Case Filings',
                'level' => 0,
                'show_heading' => false,
                'documents' => $nonAluDocuments,
            ];
        }

        return $groups;
    }

    private static function chronological(Collection $documents): Collection
    {
        return $documents->sort(fn ($left, $right) => self::compareChronologically($left, $right))->values();
    }

    private static function compareChronologically($left, $right): int
    {
        $dateComparison = $left->uploaded_at <=> $right->uploaded_at;

        return $dateComparison !== 0
            ? $dateComparison
            : ((int) $left->id <=> (int) $right->id);
    }

    private static function isAluDocument($document): bool
    {
        return in_array(self::documentUploaderRole($document), self::ALU_ROLES, true);
    }

    private static function documentUploaderRole($document): string
    {
        return (string) $document->uploader?->getCurrentRole();
    }
}

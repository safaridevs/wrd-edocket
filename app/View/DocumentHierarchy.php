<?php

namespace App\View;

use App\Models\Document;
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
        $initialDocuments = $documents
            ->filter(fn ($document) => self::filingContext($document) === Document::FILING_CONTEXT_INITIAL)
            ->values();

        $pleadingDocuments = $initialDocuments
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
        $pleadingChildren = $initialDocuments
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

        $caseFilings = $documents
            ->reject(fn ($document) => self::filingContext($document) === Document::FILING_CONTEXT_INITIAL)
            ->values();

        if ($caseFilings->isNotEmpty()) {
            $groups[] = [
                'key' => 'case_filings',
                'label' => 'Case Filings',
                'level' => 0,
                'show_heading' => false,
                'documents' => $caseFilings,
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

    private static function filingContext($document): string
    {
        if (!blank($document->filing_context ?? null)) {
            return (string) $document->filing_context;
        }

        return in_array(self::documentUploaderRole($document), self::ALU_ROLES, true)
            ? Document::FILING_CONTEXT_INITIAL
            : Document::FILING_CONTEXT_SUBSEQUENT;
    }

    private static function documentUploaderRole($document): string
    {
        return (string) $document->uploader?->getCurrentRole();
    }
}

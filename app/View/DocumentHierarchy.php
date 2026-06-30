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

    private const PLEADING_LABELS = [
        'request_pre_hearing' => 'Request for Pre-Hearing',
        'request_to_docket' => 'Request to Docket',
    ];

    public static function groups(Collection $documents): array
    {
        $documents = $documents->sortByDesc('uploaded_at')->values();
        $aluDocuments = $documents
            ->filter(fn ($document) => self::isAluDocument($document))
            ->values();

        $pleadingDocuments = $aluDocuments
            ->filter(fn ($document) => array_key_exists((string) $document->pleading_type, self::PLEADING_LABELS))
            ->values();

        $hasPleadingDocuments = $pleadingDocuments->isNotEmpty();
        $groups = [];

        if ($hasPleadingDocuments) {
            $groups[] = [
                'key' => 'pleading',
                'label' => self::pleadingGroupLabel($pleadingDocuments),
                'level' => 0,
                'documents' => $pleadingDocuments,
            ];
        }

        $categoryGroups = [
            [
                'key' => 'application',
                'label' => 'Application',
                'level' => $hasPleadingDocuments ? 1 : 0,
                'types' => ['application'],
            ],
            [
                'key' => 'notice_publication',
                'label' => 'Notice of Pub',
                'level' => $hasPleadingDocuments ? 2 : 1,
                'types' => ['notice_publication'],
            ],
            [
                'key' => 'affidavit',
                'label' => 'Affidavit',
                'level' => $hasPleadingDocuments ? 2 : 1,
                'types' => ['affidavit_publication', 'affidavit'],
            ],
        ];

        $groupedIds = $pleadingDocuments->pluck('id')->all();
        foreach ($categoryGroups as $categoryGroup) {
            $groupDocuments = $aluDocuments
                ->filter(fn ($document) => in_array($document->doc_type, $categoryGroup['types'], true))
                ->values();

            if ($groupDocuments->isEmpty()) {
                continue;
            }

            $groupedIds = array_merge($groupedIds, $groupDocuments->pluck('id')->all());
            $groups[] = [
                'key' => $categoryGroup['key'],
                'label' => $categoryGroup['label'],
                'level' => $categoryGroup['level'],
                'documents' => $groupDocuments,
            ];
        }

        $otherAluDocuments = $aluDocuments
            ->reject(fn ($document) => in_array($document->id, $groupedIds, true))
            ->values();

        if ($otherAluDocuments->isNotEmpty()) {
            $groups[] = [
                'key' => 'alu_others',
                'label' => 'Others',
                'level' => $hasPleadingDocuments ? 2 : 0,
                'documents' => $otherAluDocuments,
            ];
        }

        return array_merge($groups, self::topLevelNonAluGroups($documents));
    }

    private static function pleadingGroupLabel(Collection $pleadingDocuments): string
    {
        $labels = $pleadingDocuments
            ->pluck('pleading_type')
            ->unique()
            ->map(fn ($type) => self::PLEADING_LABELS[$type] ?? null)
            ->filter()
            ->values();

        return $labels->isNotEmpty()
            ? $labels->join(' / ')
            : 'Pleading Document';
    }

    private static function topLevelNonAluGroups(Collection $documents): array
    {
        $nonAluDocuments = $documents
            ->reject(fn ($document) => self::isAluDocument($document))
            ->values();

        $groups = [];

        $sourceGroups = [
            [
                'key' => 'hu_orders_notices',
                'label' => 'HU Orders and Notices',
                'filter' => fn ($document) => (bool) $document->uploader?->isHearingUnit(),
            ],
            [
                'key' => 'party_filings',
                'label' => 'Party Filings',
                'filter' => fn ($document) => self::isPartyDocument($document),
            ],
            [
                'key' => 'other_documents',
                'label' => 'Other Documents',
                'filter' => fn ($document) => true,
            ],
        ];

        $groupedIds = [];
        foreach ($sourceGroups as $sourceGroup) {
            $groupDocuments = $nonAluDocuments
                ->reject(fn ($document) => in_array($document->id, $groupedIds, true))
                ->filter($sourceGroup['filter'])
                ->values();

            if ($groupDocuments->isEmpty()) {
                continue;
            }

            $groupedIds = array_merge($groupedIds, $groupDocuments->pluck('id')->all());
            $groups[] = [
                'key' => $sourceGroup['key'],
                'label' => $sourceGroup['label'],
                'level' => 0,
                'documents' => $groupDocuments,
            ];
        }

        return $groups;
    }

    private static function isAluDocument($document): bool
    {
        return in_array(self::documentUploaderRole($document), self::ALU_ROLES, true);
    }

    private static function isPartyDocument($document): bool
    {
        return in_array(self::documentUploaderRole($document), ['party', 'external_attorney', 'interested_party'], true);
    }

    private static function documentUploaderRole($document): string
    {
        return (string) $document->uploader?->getCurrentRole();
    }
}

<?php

namespace App\View;

use Illuminate\Support\Collection;

class DocumentHierarchy
{
    private const PLEADING_LABELS = [
        'request_pre_hearing' => 'Request for Pre-Hearing',
        'request_to_docket' => 'Request to Docket',
    ];

    public static function groups(Collection $documents): array
    {
        $documents = $documents->sortByDesc('uploaded_at')->values();
        $pleadingDocuments = $documents
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
            $groupDocuments = $documents
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

        $otherDocuments = $documents
            ->reject(fn ($document) => in_array($document->id, $groupedIds, true))
            ->values();

        if ($otherDocuments->isNotEmpty()) {
            $groups[] = [
                'key' => 'others',
                'label' => 'Others',
                'level' => $hasPleadingDocuments ? 2 : 0,
                'documents' => $otherDocuments,
            ];
        }

        return $groups;
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
}

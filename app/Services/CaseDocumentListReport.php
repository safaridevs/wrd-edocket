<?php

namespace App\Services;

use App\Models\CaseModel;
use App\Models\Document;
use App\Models\User;
use App\View\DocumentHierarchy;
use Illuminate\Support\Collection;

class CaseDocumentListReport
{
    private const WRD_ROLES = [
        'alu_mgr',
        'alu_clerk',
        'alu_paralegal',
        'alu_atty',
        'alu_attorney',
        'contract_attorney',
        'wrd',
        'hydrology_expert',
    ];

    public function rows(CaseModel $case): Collection
    {
        $case->loadMissing([
            'documents.documentType',
            'documents.uploader.roleRelation',
            'partyHistory.person',
            'media.attachedBy',
        ]);

        $documents = $case->documents
            ->reject(fn (Document $document) => filled($document->rejected_reason))
            ->reject(fn (Document $document) => $this->isUnissuedHearingUnitDocument($document))
            ->values();

        $rows = collect();
        $topLevelNumber = 0;
        $childNumber = 0;
        $caseFilings = collect();

        foreach (DocumentHierarchy::groups($documents) as $group) {
            if ($group['key'] === 'case_filings') {
                $caseFilings = $group['documents'];
                continue;
            }

            foreach ($group['documents'] as $document) {
                if ((int) $group['level'] === 1) {
                    $childNumber++;
                    $order = $this->letterSequence($childNumber);
                } else {
                    $topLevelNumber++;
                    $order = (string) $topLevelNumber;

                    if ($group['key'] === 'pleading') {
                        $childNumber = 0;
                    }
                }

                $rows->push([
                    'order' => $order,
                    'is_child' => (int) $group['level'] === 1,
                    'date' => $this->filingDate($document),
                    'title' => $this->documentTitle($document),
                    'filer' => $this->filerLabel($case, $document),
                    'document' => $document,
                ]);
            }
        }

        $topLevelFilings = $caseFilings
            ->map(fn (Document $document) => [
                'kind' => 'document',
                'date' => $this->filingDate($document),
                'id' => $document->id,
                'item' => $document,
            ])
            ->concat($case->media->map(fn ($media) => [
                'kind' => 'media',
                'date' => $media->created_at,
                'id' => $media->id,
                'item' => $media,
            ]))
            ->sort(function (array $left, array $right) {
                $dateComparison = $left['date'] <=> $right['date'];

                return $dateComparison !== 0
                    ? $dateComparison
                    : [$left['kind'], (int) $left['id']] <=> [$right['kind'], (int) $right['id']];
            });

        foreach ($topLevelFilings as $filing) {
            $topLevelNumber++;

            if ($filing['kind'] === 'media') {
                $media = $filing['item'];
                $rows->push([
                    'order' => (string) $topLevelNumber,
                    'is_child' => false,
                    'date' => $media->created_at,
                    'title' => $media->title,
                    'filer' => 'Hearing Unit',
                    'document' => null,
                    'media' => $media,
                ]);
                continue;
            }

            $document = $filing['item'];
            $rows->push([
                'order' => (string) $topLevelNumber,
                'is_child' => false,
                'date' => $this->filingDate($document),
                'title' => $this->documentTitle($document),
                'filer' => $this->filerLabel($case, $document),
                'document' => $document,
            ]);
        }

        return $rows;
    }

    private function isUnissuedHearingUnitDocument(Document $document): bool
    {
        return $this->userRole($document->uploader) !== null
            && in_array($this->userRole($document->uploader), ['hu_admin', 'hu_clerk'], true)
            && !$document->approved;
    }

    private function filingDate(Document $document)
    {
        return in_array($this->userRole($document->uploader), ['hu_admin', 'hu_clerk'], true)
            ? ($document->approved_at ?? $document->uploaded_at)
            : $document->uploaded_at;
    }

    private function documentTitle(Document $document): string
    {
        if (filled($document->custom_title)) {
            return (string) $document->custom_title;
        }

        if (in_array($document->pleading_type, ['request_pre_hearing', 'request_to_docket'], true)) {
            return $document->pleading_type_label;
        }

        return $document->doc_type_label;
    }

    private function filerLabel(CaseModel $case, Document $document): string
    {
        $uploader = $document->uploader;
        $role = $this->userRole($uploader);

        if (in_array($role, ['hu_admin', 'hu_clerk'], true)) {
            return 'Hearing Unit';
        }

        $partyLabels = $this->partyLabelsForUploader($case, $document);
        if ($partyLabels->isNotEmpty()) {
            return $partyLabels->implode(', ');
        }

        if (in_array($role, self::WRD_ROLES, true)) {
            return 'WRD';
        }

        return $uploader?->name ?: 'Not recorded';
    }

    private function partyLabelsForUploader(CaseModel $case, Document $document): Collection
    {
        $email = strtolower(trim((string) $document->uploader?->email));
        if ($email === '') {
            return collect();
        }

        $filedAt = $this->filingDate($document);
        $history = $case->partyHistory
            ->filter(function ($participation) use ($email, $filedAt) {
                if (strtolower(trim((string) $participation->person?->email)) !== $email) {
                    return false;
                }

                if (!$filedAt) {
                    return true;
                }

                return (!$participation->effective_at || $participation->effective_at->lte($filedAt))
                    && (!$participation->terminated_at || $participation->terminated_at->gte($filedAt));
            });

        return $history
            ->map(function ($participation) use ($case) {
                if (!in_array($participation->role, ['counsel', 'paralegal', 'agent'], true)) {
                    return $this->roleLabel($participation->role);
                }

                $client = $case->partyHistory->firstWhere('id', $participation->client_party_id);

                return $client ? $this->roleLabel($client->role) : $this->roleLabel($participation->role);
            })
            ->filter()
            ->unique()
            ->values();
    }

    private function userRole(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        $role = $user->relationLoaded('roleRelation')
            ? $user->roleRelation?->name
            : null;

        return (string) ($role ?: $user->getRawOriginal('role')) ?: null;
    }

    private function roleLabel(?string $role): ?string
    {
        return match ($role) {
            'applicant' => 'Applicant',
            'protestant' => 'Protestant',
            'respondent' => 'Respondent',
            'intervenor' => 'Intervenor',
            'aggrieved_party' => 'Aggrieved Party',
            'counsel' => 'Counsel',
            'paralegal' => 'Paralegal',
            'agent' => 'Agent',
            default => $role ? ucwords(str_replace('_', ' ', $role)) : null,
        };
    }

    private function letterSequence(int $number): string
    {
        $letters = '';

        while ($number > 0) {
            $number--;
            $letters = chr(65 + ($number % 26)) . $letters;
            $number = intdiv($number, 26);
        }

        return $letters;
    }
}

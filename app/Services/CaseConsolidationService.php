<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CaseConsolidation;
use App\Models\CaseConsolidationPartyMap;
use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CaseConsolidationService
{
    public function consolidate(CaseModel $primaryCase, array $sourceCaseIds, array $data, User $user): CaseModel
    {
        $ids = collect([$primaryCase->id, ...$sourceCaseIds])->map(fn ($id) => (int) $id)->unique()->values();

        return DB::transaction(function () use ($ids, $data, $user) {
            $sources = CaseModel::query()->whereIn('id', $ids)->lockForUpdate()->get();

            if ($sources->count() !== $ids->count() || $sources->count() < 2) {
                throw ValidationException::withMessages(['source_case_ids' => 'Select at least two valid cases.']);
            }

            if ($sources->contains(fn (CaseModel $case) => $case->isConsolidatedSource() || $case->consolidation()->exists())) {
                throw ValidationException::withMessages(['source_case_ids' => 'A selected case is already part of a consolidation.']);
            }

            if ($sources->contains(fn (CaseModel $case) => !in_array($case->status, ['active', 'closed'], true))) {
                throw ValidationException::withMessages(['source_case_ids' => 'Only active or closed cases may be consolidated.']);
            }

            $sources = $sources->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])->values();
            $identity = $this->buildCombinedIdentity($sources);

            if (mb_strlen($identity['case_no']) > 255) {
                throw ValidationException::withMessages([
                    'source_case_ids' => 'The generated combined case number is longer than 255 characters. Select fewer cases.',
                ]);
            }

            if (CaseModel::where('case_no', $identity['case_no'])->exists()) {
                throw ValidationException::withMessages([
                    'source_case_ids' => "A combined case already uses {$identity['case_no']}.",
                ]);
            }

            $combined = CaseModel::create([
                'case_no' => $identity['case_no'],
                'caption' => $identity['caption'],
                'case_type' => $data['case_type'],
                'status' => 'active',
                'created_by_user_id' => $user->id,
                'updated_by_user_id' => $user->id,
                'accepted_at' => now(),
                'metadata' => ['consolidated_source_case_numbers' => $sources->pluck('case_no')->values()->all()],
            ]);

            $consolidation = CaseConsolidation::create([
                'combined_case_id' => $combined->id,
                'ordered_by_user_id' => $user->id,
                'order_date' => $data['order_date'],
                'reason' => $data['reason'] ?? null,
                'historical_access_policy' => 'source_participants',
            ]);

            foreach ($sources as $source) {
                $consolidation->sources()->create(['source_case_id' => $source->id]);
                $source->update([
                    'consolidated_into_case_id' => $combined->id,
                    'consolidated_by_user_id' => $user->id,
                    'consolidated_at' => now(),
                ]);
            }

            $this->copyParties($sources, $combined, $consolidation);
            $this->copyAssignments($sources, $combined, $user);
            $this->copyServiceList($sources, $combined);
            $this->copyOseFileNumbers($sources, $combined);

            AuditLog::log('consolidate_cases', $user, $combined, [
                'source_cases' => $sources->map->only(['id', 'case_no'])->values()->all(),
                'order_date' => $data['order_date'],
                'reason' => $data['reason'] ?? null,
            ]);

            foreach ($sources as $source) {
                AuditLog::log('case_consolidated', $user, $source, [
                    'combined_case_id' => $combined->id,
                    'combined_case_no' => $combined->case_no,
                ]);
            }

            return $combined;
        });
    }

    public function buildCombinedIdentity($sources): array
    {
        $ordered = collect($sources)->sortBy([
            ['created_at', 'asc'],
            ['id', 'asc'],
        ])->values();

        return [
            'case_no' => $ordered->pluck('case_no')->implode(' & ') . ' (Consol.)',
            'caption' => $ordered->pluck('caption')->map(fn ($caption) => trim((string) $caption))->implode(' | ') . ' | Consolidated',
        ];
    }

    private function copyParties($sources, CaseModel $combined, CaseConsolidation $consolidation): void
    {
        $sourceParties = $sources->flatMap(fn (CaseModel $case) => $case->parties()->orderBy('id')->get());
        $mapped = [];

        foreach ($sourceParties->sortBy(fn (CaseParty $party) => $party->client_party_id ? 1 : 0) as $sourceParty) {
            $combinedClientId = $sourceParty->client_party_id ? ($mapped[$sourceParty->client_party_id] ?? null) : null;
            $combinedParty = $combined->parties()->firstOrCreate(
                [
                    'person_id' => $sourceParty->person_id,
                    'role' => $sourceParty->role,
                    'client_party_id' => $combinedClientId,
                ],
                [
                    'service_enabled' => $sourceParty->service_enabled,
                    'representation_capacity' => $sourceParty->representation_capacity,
                    'effective_at' => now(),
                ]
            );

            $mapped[$sourceParty->id] = $combinedParty->id;
            CaseConsolidationPartyMap::create([
                'case_consolidation_id' => $consolidation->id,
                'source_case_party_id' => $sourceParty->id,
                'combined_case_party_id' => $combinedParty->id,
            ]);
        }
    }

    private function copyAssignments($sources, CaseModel $combined, User $user): void
    {
        foreach ($sources as $source) {
            foreach ($source->assignments()->get() as $assignment) {
                $combined->assignments()->firstOrCreate([
                    'user_id' => $assignment->user_id,
                    'assignment_type' => $assignment->assignment_type,
                ], ['assigned_by' => $user->id]);
            }
        }
    }

    private function copyServiceList($sources, CaseModel $combined): void
    {
        foreach ($sources as $source) {
            foreach ($source->serviceList()->get() as $entry) {
                $combined->serviceList()->firstOrCreate(
                    ['person_id' => $entry->person_id, 'email' => $entry->email],
                    ['service_method' => $entry->service_method, 'is_primary' => $entry->is_primary]
                );
            }
        }
    }

    private function copyOseFileNumbers($sources, CaseModel $combined): void
    {
        foreach ($sources as $source) {
            foreach ($source->oseFileNumbers()->get() as $number) {
                $combined->oseFileNumbers()->firstOrCreate($number->only(['basin_code', 'file_no_from', 'file_no_to']));
            }
        }
    }
}

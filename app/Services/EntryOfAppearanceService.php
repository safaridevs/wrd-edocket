<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CaseParty;
use App\Models\EntryOfAppearance;
use App\Models\Person;
use App\Models\ServiceList;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EntryOfAppearanceService
{
    public function approve(EntryOfAppearance $appearance, User $reviewer): EntryOfAppearance
    {
        $approved = DB::transaction(function () use ($appearance, $reviewer) {
            $appearance = EntryOfAppearance::query()->lockForUpdate()->findOrFail($appearance->id);

            if ($appearance->status !== EntryOfAppearance::STATUS_PENDING) {
                throw ValidationException::withMessages(['appearance' => 'Only pending appearances may be approved.']);
            }

            $case = $appearance->case()->firstOrFail();
            $party = $case->parties()
                ->whereKey($appearance->represented_case_party_id)
                ->whereNotIn('role', CaseParty::REPRESENTATIVE_ROLES)
                ->with('person')
                ->first();

            if (!$party || $case->status !== 'active' || $case->isConsolidatedSource()) {
                throw ValidationException::withMessages(['appearance' => 'The case or represented party is no longer eligible.']);
            }

            $email = strtolower(trim($appearance->email));
            if (CaseParty::wrdRepresentativeAssignmentForEmail($case, $email)) {
                throw ValidationException::withMessages(['appearance' => 'This attorney is assigned to represent WRD in this case.']);
            }

            $person = Person::query()->whereRaw('LOWER(email) = ?', [$email])->first();
            $attributes = [
                'type' => 'individual',
                'prefix' => $appearance->attorney_prefix,
                'first_name' => $appearance->attorney_first_name,
                'middle_name' => $appearance->attorney_middle_name,
                'last_name' => $appearance->attorney_last_name,
                'suffix' => $appearance->attorney_suffix,
                'organization' => $appearance->law_firm,
                'email' => $email,
                'phone_office' => $appearance->phone,
                'address_line1' => $appearance->address_line1,
                'address_line2' => $appearance->address_line2,
                'city' => $appearance->city,
                'state' => $appearance->state,
                'zip' => $appearance->zip,
            ];

            if ($person) {
                $person->fill(collect($attributes)
                    ->filter(fn ($value, $field) => blank($person->getAttribute($field)) && filled($value))
                    ->all());
                $person->save();
            } else {
                $person = Person::create($attributes);
            }

            $counselParty = CaseParty::firstOrCreate([
                'case_id' => $case->id,
                'person_id' => $person->id,
                'role' => 'counsel',
                'client_party_id' => $party->id,
            ], [
                'service_enabled' => true,
                'representation_capacity' => CaseParty::CAPACITY_PRIVATE_COUNSEL,
                'effective_at' => $appearance->effective_date ?? now(),
            ]);

            ServiceList::updateOrCreate([
                'case_id' => $case->id,
                'person_id' => $person->id,
            ], [
                'email' => $email,
                'service_method' => 'email',
                'is_primary' => false,
            ]);

            $party->removeFromServiceListWhileRepresented();

            $appearance->update([
                'status' => EntryOfAppearance::STATUS_APPROVED,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'person_id' => $person->id,
                'counsel_case_party_id' => $counselParty->id,
            ]);

            if ($counselParty->wasRecentlyCreated) {
                AuditLog::log('add_case_participant', $reviewer, $case, [
                    'appearance_id' => $appearance->id,
                    'source' => 'entry_of_appearance',
                    'participant' => $person->full_name,
                    'role' => 'Counsel',
                    'represented_party' => $party->person?->full_name,
                    'effective_at' => $counselParty->effective_at?->toIso8601String(),
                ]);
            }

            return $appearance->fresh(['case', 'representedParty.person', 'person']);
        });

        app(NotificationService::class)->notifyCounselParticipationChanged($approved->person, $approved->case, true);

        return $approved;
    }

    public function reject(EntryOfAppearance $appearance, User $reviewer, string $reason): EntryOfAppearance
    {
        $rejected = DB::transaction(function () use ($appearance, $reviewer, $reason) {
            $appearance = EntryOfAppearance::query()->lockForUpdate()->findOrFail($appearance->id);

            if ($appearance->status !== EntryOfAppearance::STATUS_PENDING) {
                throw ValidationException::withMessages(['appearance' => 'Only pending appearances may be rejected.']);
            }

            $appearance->update([
                'status' => EntryOfAppearance::STATUS_REJECTED,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $appearance->fresh('case');
        });

        app(NotificationService::class)->notifyEmailAddress(
            $rejected->email,
            'entry_of_appearance_rejected',
            'Entry of Appearance Rejected',
            "Your Entry of Appearance for case {$rejected->case->case_no} was rejected. Reason: {$reason}",
            $rejected->case
        );

        return $rejected;
    }
}

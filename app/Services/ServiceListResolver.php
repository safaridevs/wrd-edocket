<?php

namespace App\Services;

use App\Models\CaseModel;
use Illuminate\Support\Collection;

class ServiceListResolver
{
    private const ASSIGNMENT_LABELS = [
        'alu_clerk' => 'ALU Clerk',
        'alu_paralegal' => 'ALU Paralegal',
        'alu_atty' => 'ALU Attorney',
        'alu_attorney' => 'ALU Attorney',
        'wrd' => 'WRD Expert',
    ];

    public function resolve(CaseModel $case): Collection
    {
        $case->loadMissing([
            'parties.person',
            'serviceList.person',
            'assignments.user.serviceProfile',
        ]);

        $recipients = collect();
        $usedEmails = [];

        foreach ($case->serviceList as $serviceEntry) {
            $person = $serviceEntry->person;

            if (strtoupper(trim((string) ($person?->organization ?? ''))) === 'WATER RIGHTS DIVISION') {
                continue;
            }

            $email = $this->normalizeEmail($serviceEntry->email ?: $person?->email);
            if ($email !== '' && isset($usedEmails[$email])) {
                continue;
            }

            $roles = $case->parties
                ->where('person_id', $serviceEntry->person_id)
                ->pluck('role')
                ->filter()
                ->unique()
                ->map(fn ($role) => ucwords(str_replace('_', ' ', $role)))
                ->implode(', ');

            $phone = collect([$person?->phone_mobile, $person?->phone_office])
                ->filter(fn ($number) => filled($number))
                ->unique()
                ->implode(' / ');

            $recipients->push([
                'name' => $person?->full_name ?? '',
                'organization' => $person?->organization ?? '',
                'phone' => $phone,
                'address_line1' => $person?->address_line1 ?? '',
                'address_line2' => $person?->address_line2 ?? '',
                'city' => $person?->city ?? '',
                'state' => $person?->state ?? '',
                'zip' => $person?->zip ?? '',
                'email' => $email,
                'role' => $roles,
                'service_method' => $serviceEntry->service_method ?? '',
                'service_label' => ucfirst((string) ($serviceEntry->service_method ?? '')),
                'is_primary' => (bool) $serviceEntry->is_primary,
                'source' => 'service_list',
            ]);

            if ($email !== '') {
                $usedEmails[$email] = true;
            }
        }

        foreach ($case->assignments as $assignment) {
            if (!array_key_exists($assignment->assignment_type, self::ASSIGNMENT_LABELS)) {
                continue;
            }

            $user = $assignment->user;
            $person = $user?->serviceProfile;
            $email = $this->normalizeEmail($user?->email);

            if ($email === '' || isset($usedEmails[$email])) {
                continue;
            }

            $label = self::ASSIGNMENT_LABELS[$assignment->assignment_type];
            $userRole = $user?->relationLoaded('roleRelation')
                ? $user->roleRelation?->name
                : $user?->getRawOriginal('role');
            if ($userRole === 'contract_attorney') {
                $label = 'Contract Attorney';
            }
            $recipients->push([
                'name' => $user?->getDisplayName() ?? '',
                'organization' => $person?->organization ?? '',
                'phone' => collect([$person?->phone_mobile, $person?->phone_office, $user?->phone])
                    ->filter(fn ($number) => filled($number))
                    ->unique()
                    ->implode(' / '),
                'address_line1' => $person?->address_line1 ?? '',
                'address_line2' => $person?->address_line2 ?? '',
                'city' => $person?->city ?? '',
                'state' => $person?->state ?? '',
                'zip' => $person?->zip ?? '',
                'email' => $email,
                'role' => $label,
                'service_method' => 'email',
                'service_label' => $label,
                'is_primary' => false,
                'source' => 'assignment',
            ]);

            $usedEmails[$email] = true;
        }

        return $recipients
            ->sortBy(fn (array $recipient) => strtolower($recipient['name'] ?: $recipient['email']))
            ->values();
    }

    public function emails(CaseModel $case): array
    {
        return $this->resolve($case)
            ->pluck('email')
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }
}

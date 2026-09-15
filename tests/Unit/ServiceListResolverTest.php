<?php

namespace Tests\Unit;

use App\Models\CaseAssignment;
use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\Person;
use App\Models\ServiceList;
use App\Models\User;
use App\Services\ServiceListResolver;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class ServiceListResolverTest extends TestCase
{
    public function test_it_combines_saved_recipients_and_supported_assignments_without_duplicates(): void
    {
        $party = $this->person(10, 'Applicant Person', 'Applicant Organization', 'Party@Example.com', '505-555-0100', '505-555-0101');
        $wrdPlaceholder = $this->person(11, 'WRD', 'WATER RIGHTS DIVISION', 'division@example.com');
        $saved = $this->serviceEntry(20, $party, 'PARTY@example.com', 'email', true);
        $excluded = $this->serviceEntry(21, $wrdPlaceholder, 'division@example.com', 'email');
        $attorneyProfile = $this->person(12, 'Assigned Attorney', 'Attorney Firm', 'attorney@example.com', null, '505-555-0110');
        $attorneyProfile->address_line1 = '123 Legal Plaza';
        $attorneyProfile->city = 'Santa Fe';
        $attorneyProfile->state = 'NM';
        $attorneyProfile->zip = '87505';

        $case = new CaseModel();
        $case->setRelation('parties', new Collection([
            $this->caseParty(30, $party, 'applicant'),
        ]));
        $case->setRelation('serviceList', new Collection([$saved, $excluded]));
        $case->setRelation('assignments', new Collection([
            $this->assignment(40, 'alu_atty', 'Assigned Attorney', 'attorney@example.com', 'contract_attorney', $attorneyProfile),
            $this->assignment(41, 'wrd', 'Duplicate Party', 'party@example.com'),
            $this->assignment(42, 'hydrology_expert', 'Hydrologist', 'hydrology@example.com'),
        ]));

        $recipients = (new ServiceListResolver())->resolve($case);

        $this->assertSame(['party@example.com', 'attorney@example.com'], $recipients->pluck('email')->all());
        $this->assertSame(['service_list', 'assignment'], $recipients->pluck('source')->all());
        $this->assertTrue($recipients->first()['is_primary']);
        $this->assertSame('Applicant', $recipients->first()['role']);
        $this->assertSame('505-555-0100 / 505-555-0101', $recipients->first()['phone']);
        $this->assertSame('505-555-0110', $recipients->last()['phone']);
        $this->assertSame('123 Legal Plaza', $recipients->last()['address_line1']);
        $this->assertSame('Santa Fe', $recipients->last()['city']);
        $this->assertSame('NM', $recipients->last()['state']);
        $this->assertSame('87505', $recipients->last()['zip']);
        $this->assertSame('Contract Attorney', $recipients->last()['service_label']);
    }

    public function test_emails_returns_the_same_normalized_recipient_set(): void
    {
        $case = new CaseModel();
        $case->setRelation('parties', new Collection());
        $case->setRelation('serviceList', new Collection());
        $case->setRelation('assignments', new Collection([
            $this->assignment(1, 'alu_clerk', 'Clerk', ' Clerk@Example.com '),
            $this->assignment(2, 'alu_paralegal', 'Paralegal', 'para@example.com'),
        ]));

        $this->assertSame(
            ['clerk@example.com', 'para@example.com'],
            (new ServiceListResolver())->emails($case)
        );
    }

    private function person(int $id, string $name, string $organization, string $email, ?string $mobile = null, ?string $office = null): Person
    {
        $nameParts = explode(' ', $name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? null;
        $person = new Person([
            'type' => 'individual',
            'prefix' => null,
            'first_name' => $firstName,
            'middle_name' => null,
            'last_name' => $lastName,
            'suffix' => null,
            'organization' => $organization,
            'email' => $email,
            'phone_mobile' => $mobile,
            'phone_office' => $office,
        ]);
        $person->id = $id;

        return $person;
    }

    private function serviceEntry(int $id, Person $person, string $email, string $method, bool $primary = false): ServiceList
    {
        $entry = new ServiceList([
            'person_id' => $person->id,
            'email' => $email,
            'service_method' => $method,
            'is_primary' => $primary,
        ]);
        $entry->id = $id;
        $entry->setRelation('person', $person);

        return $entry;
    }

    private function caseParty(int $id, Person $person, string $role): CaseParty
    {
        $party = new CaseParty(['person_id' => $person->id, 'role' => $role]);
        $party->id = $id;
        $party->setRelation('person', $person);

        return $party;
    }

    private function assignment(int $id, string $type, string $name, string $email, ?string $role = null, ?Person $serviceProfile = null): CaseAssignment
    {
        $user = new User(['name' => $name, 'email' => $email]);
        if ($role) {
            $user->setRawAttributes(array_merge($user->getAttributes(), ['role' => $role]), true);
        }
        $user->id = $id + 100;
        $user->setRelation('serviceProfile', $serviceProfile);

        $assignment = new CaseAssignment(['assignment_type' => $type]);
        $assignment->id = $id;
        $assignment->setRelation('user', $user);

        return $assignment;
    }
}

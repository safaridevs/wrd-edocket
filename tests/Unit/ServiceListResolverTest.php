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
        $party = $this->person(10, 'Applicant Person', 'Applicant Organization', 'Party@Example.com');
        $wrdPlaceholder = $this->person(11, 'WRD', 'WATER RIGHTS DIVISION', 'division@example.com');
        $saved = $this->serviceEntry(20, $party, 'PARTY@example.com', 'email', true);
        $excluded = $this->serviceEntry(21, $wrdPlaceholder, 'division@example.com', 'email');

        $case = new CaseModel();
        $case->setRelation('parties', new Collection([
            $this->caseParty(30, $party, 'applicant'),
        ]));
        $case->setRelation('serviceList', new Collection([$saved, $excluded]));
        $case->setRelation('assignments', new Collection([
            $this->assignment(40, 'alu_atty', 'Assigned Attorney', 'attorney@example.com'),
            $this->assignment(41, 'wrd', 'Duplicate Party', 'party@example.com'),
            $this->assignment(42, 'hydrology_expert', 'Hydrologist', 'hydrology@example.com'),
        ]));

        $recipients = (new ServiceListResolver())->resolve($case);

        $this->assertSame(['party@example.com', 'attorney@example.com'], $recipients->pluck('email')->all());
        $this->assertSame(['service_list', 'assignment'], $recipients->pluck('source')->all());
        $this->assertTrue($recipients->first()['is_primary']);
        $this->assertSame('Applicant', $recipients->first()['role']);
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

    private function person(int $id, string $name, string $organization, string $email): Person
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

    private function assignment(int $id, string $type, string $name, string $email): CaseAssignment
    {
        $user = new User(['name' => $name, 'email' => $email]);
        $user->id = $id + 100;

        $assignment = new CaseAssignment(['assignment_type' => $type]);
        $assignment->id = $id;
        $assignment->setRelation('user', $user);

        return $assignment;
    }
}

<?php

namespace Tests\Feature;

use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\EntryOfAppearance;
use App\Models\Person;
use App\Models\ServiceList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EntryOfAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_only_when_case_number_and_party_name_match(): void
    {
        Mail::fake();
        [$case, $party] = $this->caseWithParty();

        $this->post(route('entry-of-appearances.store'), $this->form([
            'case_number' => $case->case_no,
            'party_name' => 'Wrong Party',
        ]))->assertSessionHasErrors('party_name');

        $this->assertDatabaseCount('entry_of_appearances', 0);

        $this->post(route('entry-of-appearances.store'), $this->form([
            'case_number' => strtolower($case->case_no),
            'party_name' => '  ACME, LLC  ',
        ]))->assertRedirect(route('entry-of-appearances.submitted'));

        $this->assertDatabaseHas('entry_of_appearances', [
            'case_id' => $case->id,
            'represented_case_party_id' => $party->id,
            'status' => EntryOfAppearance::STATUS_UNVERIFIED,
            'email' => 'counsel@example.com',
        ]);
        $this->assertDatabaseCount('persons', 1);
        $this->assertDatabaseCount('case_parties', 1);
    }

    public function test_verification_moves_request_to_hu_queue(): void
    {
        Mail::fake();
        [$case, $party] = $this->caseWithParty();
        $appearance = $this->appearance($case, $party);

        $url = URL::temporarySignedRoute('entry-of-appearances.verify', now()->addHour(), [
            'appearance' => $appearance->id,
            'hash' => hash('sha256', $appearance->email),
        ]);

        $this->get($url)->assertOk()->assertSee('Email verified');

        $this->assertDatabaseHas('entry_of_appearances', [
            'id' => $appearance->id,
            'status' => EntryOfAppearance::STATUS_PENDING,
        ]);
    }

    public function test_hu_admin_approval_populates_person_representation_and_service_list(): void
    {
        Mail::fake();
        [$case, $party] = $this->caseWithParty();
        $appearance = $this->appearance($case, $party, EntryOfAppearance::STATUS_PENDING);
        $admin = User::factory()->create(['role' => 'hu_admin']);

        $this->actingAs($admin)
            ->post(route('admin.entry-of-appearances.approve', $appearance))
            ->assertRedirect(route('admin.entry-of-appearances.show', $appearance));

        $person = Person::where('email', 'counsel@example.com')->firstOrFail();
        $counsel = CaseParty::where([
            'case_id' => $case->id,
            'person_id' => $person->id,
            'role' => 'counsel',
            'client_party_id' => $party->id,
        ])->firstOrFail();

        $this->assertSame('Example Law Firm', $person->organization);
        $this->assertSame(CaseParty::CAPACITY_PRIVATE_COUNSEL, $counsel->representation_capacity);
        $this->assertTrue(ServiceList::where('case_id', $case->id)->where('person_id', $person->id)->exists());
        $this->assertFalse($party->fresh()->service_enabled);
        $this->assertDatabaseHas('entry_of_appearances', [
            'id' => $appearance->id,
            'status' => EntryOfAppearance::STATUS_APPROVED,
            'person_id' => $person->id,
            'counsel_case_party_id' => $counsel->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'case_id' => $case->id,
            'user_id' => $admin->id,
            'action' => 'add_case_participant',
        ]);
    }

    public function test_non_hu_admin_cannot_review_appearance(): void
    {
        [$case, $party] = $this->caseWithParty();
        $appearance = $this->appearance($case, $party, EntryOfAppearance::STATUS_PENDING);

        $this->actingAs(User::factory()->create(['role' => 'hu_clerk']))
            ->post(route('admin.entry-of-appearances.approve', $appearance))
            ->assertForbidden();
    }

    public function test_rejection_does_not_add_a_case_audit_entry(): void
    {
        Mail::fake();
        [$case, $party] = $this->caseWithParty();
        $appearance = $this->appearance($case, $party, EntryOfAppearance::STATUS_PENDING);
        $admin = User::factory()->create(['role' => 'hu_admin']);

        $this->actingAs($admin)->post(
            route('admin.entry-of-appearances.reject', $appearance),
            ['rejection_reason' => 'Representation could not be verified.']
        )->assertRedirect(route('admin.entry-of-appearances.show', $appearance));

        $this->assertDatabaseHas('entry_of_appearances', [
            'id' => $appearance->id,
            'status' => EntryOfAppearance::STATUS_REJECTED,
        ]);
        $this->assertDatabaseMissing('audit_logs', ['case_id' => $case->id]);
        $this->assertDatabaseCount('case_parties', 1);
    }

    private function caseWithParty(): array
    {
        $creator = User::factory()->create(['role' => 'alu_mgr']);
        $case = CaseModel::create([
            'case_no' => 'HU-26-001',
            'caption' => 'In the Matter of ACME',
            'case_type' => 'aggrieved',
            'status' => 'active',
            'created_by_user_id' => $creator->id,
        ]);
        $person = Person::create(['type' => 'company', 'organization' => 'ACME LLC', 'email' => 'party@example.com']);
        $party = CaseParty::create(['case_id' => $case->id, 'person_id' => $person->id, 'role' => 'applicant']);

        return [$case, $party];
    }

    private function appearance(CaseModel $case, CaseParty $party, string $status = EntryOfAppearance::STATUS_UNVERIFIED): EntryOfAppearance
    {
        return EntryOfAppearance::create(array_merge($this->form([
            'case_number' => $case->case_no,
            'party_name' => $party->person->full_name,
        ]), [
            'case_id' => $case->id,
            'represented_case_party_id' => $party->id,
            'status' => $status,
            'submitted_case_number' => $case->case_no,
            'submitted_party_name' => $party->person->full_name,
            'email_verified_at' => $status === EntryOfAppearance::STATUS_PENDING ? now() : null,
            'verification_expires_at' => now()->addDay(),
        ]));
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'case_number' => 'HU-26-001',
            'party_name' => 'ACME LLC',
            'attorney_first_name' => 'Alex',
            'attorney_last_name' => 'Counsel',
            'law_firm' => 'Example Law Firm',
            'email' => 'counsel@example.com',
            'phone' => '505-555-0100',
            'address_line1' => '100 Legal Way',
            'city' => 'Santa Fe',
            'state' => 'NM',
            'zip' => '87501',
            'certification' => '1',
        ], $overrides);
    }
}

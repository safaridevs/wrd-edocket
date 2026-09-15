<?php

namespace Tests\Unit;

use App\Models\CaseAssignment;
use App\Models\CaseModel;
use App\Models\CaseParty;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CaseCreationAuthorizationTest extends TestCase
{
    public function test_only_internal_alu_and_hearing_unit_roles_can_view_expert_assignments(): void
    {
        foreach (['alu_mgr', 'alu_clerk', 'alu_paralegal', 'alu_atty', 'hu_admin', 'hu_clerk'] as $role) {
            $this->assertTrue($this->user($role, 1)->canViewExpertAssignments(), $role);
        }

        foreach (['contract_attorney', 'external_attorney', 'party', 'interested_party', 'wrd', 'hydrology_expert'] as $role) {
            $this->assertFalse($this->user($role, 1)->canViewExpertAssignments(), $role);
        }
    }

    public function test_alu_and_contract_attorneys_can_create_cases_but_external_attorneys_cannot(): void
    {
        $this->assertTrue($this->user('alu_mgr', 8)->canCreateCase());
        $this->assertTrue($this->user('alu_clerk', 1)->canCreateCase());
        $this->assertTrue($this->user('alu_paralegal', 2)->canCreateCase());
        $this->assertTrue($this->user('alu_atty', 3)->canCreateCase());
        $this->assertTrue($this->user('contract_attorney', 4)->canCreateCase());
        $this->assertFalse($this->user('external_attorney', 5)->canCreateCase());
        $this->assertFalse($this->user('party', 6)->canCreateCase());
        $this->assertFalse($this->user('hu_admin', 7)->canCreateCase());
    }

    public function test_alu_attorney_has_the_same_draft_access_as_alu_paralegal(): void
    {
        $attorney = $this->user('alu_atty', 10);

        $ownDraft = $this->case('draft', 10);
        $ownDraft->setRelation('assignments', new Collection());

        $assignedDraft = $this->case('draft', 99);
        $assignedDraft->setRelation('assignments', new Collection([
            $this->assignment(10, 'alu_atty'),
        ]));

        $otherDraft = $this->case('draft', 99);
        $otherDraft->setRelation('assignments', new Collection([
            $this->assignment(11, 'alu_atty'),
        ]));

        $this->assertTrue($attorney->canManageDraftCase($ownDraft));
        $this->assertTrue($attorney->canManageDraftCase($assignedDraft));
        $this->assertTrue($attorney->canUploadDocumentsToCase($assignedDraft));
        $this->assertTrue($attorney->canManageDraftCase($otherDraft));
        $this->assertTrue($attorney->canUploadDocumentsToCase($otherDraft));
    }

    public function test_draft_management_remains_limited_by_status_and_role(): void
    {
        $activeCase = $this->case('active', 20);
        $activeCase->setRelation('assignments', new Collection());

        $draft = $this->case('draft', 99);
        $draft->setRelation('assignments', new Collection());

        $this->assertFalse($this->user('alu_atty', 20)->canManageDraftCase($activeCase));
        $this->assertTrue($this->user('alu_clerk', 21)->canManageDraftCase($draft));
        $this->assertTrue($this->user('alu_paralegal', 22)->canManageDraftCase($draft));
        $this->assertFalse($this->user('external_attorney', 23)->canManageDraftCase($draft));
    }

    public function test_contract_attorney_can_manage_own_draft_without_user_administration(): void
    {
        $contractAttorney = $this->user('contract_attorney', 40);
        $ownDraft = $this->case('draft', 40);

        $this->assertTrue($contractAttorney->canManageDraftCase($ownDraft));
        $this->assertTrue($contractAttorney->canAssignAttorneys());
        $this->assertTrue($contractAttorney->canAssignHydrologyExperts());
        $this->assertFalse($contractAttorney->canManageUsers());
    }

    public function test_contract_attorney_is_limited_to_created_or_assigned_cases(): void
    {
        $contractAttorney = $this->user('contract_attorney', 50);

        $assignedDraft = $this->case('draft', 99);
        $assignedDraft->setRelation('assignments', new Collection([
            $this->assignment(50, 'alu_atty'),
        ]));

        $unrelatedDraft = $this->case('draft', 99);
        $unrelatedDraft->setRelation('assignments', new Collection([
            $this->assignment(51, 'alu_atty'),
        ]));

        $this->assertTrue($contractAttorney->canManageDraftCase($assignedDraft));
        $this->assertTrue($contractAttorney->canAccessCase($assignedDraft));
        $this->assertFalse($contractAttorney->canManageDraftCase($unrelatedDraft));
        $this->assertFalse($contractAttorney->canAccessCase($unrelatedDraft));
    }

    public function test_alu_attorney_matches_alu_paralegal_assignment_and_admin_capabilities(): void
    {
        $attorney = $this->user('alu_atty', 30);
        $paralegal = $this->user('alu_paralegal', 31);

        foreach (['canManageUsers', 'canAssignExperts', 'canAssignAttorneys', 'canAssignHydrologyExperts', 'canTransmitMaterials'] as $capability) {
            $this->assertTrue($paralegal->{$capability}(), "ALU Paralegal should have {$capability}.");
            $this->assertSame($paralegal->{$capability}(), $attorney->{$capability}(), "ALU Attorney should match ALU Paralegal for {$capability}.");
        }
    }

    public function test_only_assigned_alu_attorneys_can_file_to_active_cases(): void
    {
        $attorney = $this->user('alu_atty', 60);

        $assignedActiveCase = $this->case('active', 99);
        $assignedActiveCase->setRelation('assignments', new Collection([
            $this->assignment(60, 'alu_attorney'),
        ]));

        $unassignedActiveCase = $this->case('active', 99);
        $unassignedActiveCase->setRelation('assignments', new Collection([
            $this->assignment(61, 'alu_atty'),
        ]));

        $closedCase = $this->case('closed', 99);
        $closedCase->setRelation('assignments', new Collection([
            $this->assignment(60, 'alu_atty'),
        ]));

        $this->assertTrue($attorney->canUploadDocumentsToCase($assignedActiveCase));
        $this->assertFalse($attorney->canUploadDocumentsToCase($unassignedActiveCase));
        $this->assertFalse($attorney->canUploadDocumentsToCase($closedCase));
    }

    public function test_only_assigned_contract_attorneys_can_file_to_active_cases(): void
    {
        $attorney = $this->user('contract_attorney', 70);

        $assignedActiveCase = $this->case('active', 99);
        $assignedActiveCase->setRelation('assignments', new Collection([
            $this->assignment(70, 'alu_atty'),
        ]));

        $unassignedActiveCase = $this->case('active', 99);
        $unassignedActiveCase->setRelation('assignments', new Collection([
            $this->assignment(71, 'alu_atty'),
        ]));

        $closedCase = $this->case('closed', 99);
        $closedCase->setRelation('assignments', new Collection([
            $this->assignment(70, 'alu_atty'),
        ]));

        $this->assertTrue($attorney->canUploadDocumentsToCase($assignedActiveCase));
        $this->assertFalse($attorney->canUploadDocumentsToCase($unassignedActiveCase));
        $this->assertFalse($attorney->canUploadDocumentsToCase($closedCase));
    }

    public function test_only_assigned_alu_clerks_and_paralegals_can_file_to_active_cases(): void
    {
        $assignedClerkCase = $this->case('active', 99);
        $assignedClerkCase->setRelation('assignments', new Collection([
            $this->assignment(80, 'alu_clerk'),
        ]));

        $assignedParalegalCase = $this->case('active', 99);
        $assignedParalegalCase->setRelation('assignments', new Collection([
            $this->assignment(81, 'alu_paralegal'),
        ]));

        $unassignedCase = $this->case('active', 99);
        $unassignedCase->setRelation('assignments', new Collection());

        $this->assertTrue($this->user('alu_clerk', 80)->canUploadDocumentsToCase($assignedClerkCase));
        $this->assertTrue($this->user('alu_paralegal', 81)->canUploadDocumentsToCase($assignedParalegalCase));
        $this->assertFalse($this->user('alu_clerk', 80)->canUploadDocumentsToCase($unassignedCase));
        $this->assertFalse($this->user('alu_paralegal', 81)->canUploadDocumentsToCase($unassignedCase));
    }

    public function test_alu_support_assignment_types_preserve_clerk_and_paralegal_roles(): void
    {
        $this->assertSame('alu_clerk', $this->user('alu_clerk', 82)->aluSupportAssignmentType());
        $this->assertSame('alu_paralegal', $this->user('alu_paralegal', 83)->aluSupportAssignmentType());
        $this->assertNull($this->user('alu_atty', 84)->aluSupportAssignmentType());
    }

    public function test_alu_manager_can_cover_alu_work_across_open_case_stages(): void
    {
        $manager = $this->user('alu_mgr', 90);

        foreach (['draft', 'rejected'] as $status) {
            $case = $this->case($status, 999);
            $case->setRelation('assignments', new Collection());

            $this->assertTrue($manager->canManageDraftCase($case));
            $this->assertTrue($manager->canUploadDocumentsToCase($case));
            $this->assertTrue($manager->canManageCaseParties($case));
        }

        $submittedCase = $this->case('submitted_to_hu', 999);
        $submittedCase->setRelation('assignments', new Collection());
        $this->assertTrue($manager->canUploadDocumentsToCase($submittedCase));
        $this->assertTrue($manager->canManageCaseParties($submittedCase));

        $activeCase = $this->case('active', 999);
        $activeCase->setRelation('assignments', new Collection());
        $this->assertTrue($manager->canUploadDocumentsToCase($activeCase));
        $this->assertFalse($manager->canManageCaseParties($activeCase));

        foreach (['closed', 'archived'] as $status) {
            $case = $this->case($status, 999);
            $case->setRelation('assignments', new Collection());

            $this->assertFalse($manager->canUploadDocumentsToCase($case));
            $this->assertFalse($manager->canManageCaseParties($case));
        }
    }

    public function test_alu_manager_uses_alu_coverage_document_roles_without_hu_authority(): void
    {
        $manager = $this->user('alu_mgr', 91);

        $this->assertSame(
            ['alu_mgr', 'alu_clerk', 'alu_paralegal', 'alu_atty'],
            $manager->documentFilingRoles()
        );
        $this->assertTrue($manager->canSubmitToHU());
        $this->assertFalse($manager->canAcceptFilings());
        $this->assertFalse($manager->canRejectFilings());
        $this->assertFalse($manager->canApplyStamp());
    }

    public function test_existing_party_management_boundaries_remain_unchanged(): void
    {
        $draft = $this->case('draft', 999);
        $draft->setRelation('assignments', new Collection());
        $active = $this->case('active', 999);
        $active->setRelation('assignments', new Collection());

        $this->assertTrue($this->user('alu_clerk', 92)->canManageCaseParties($draft));
        $this->assertFalse($this->user('alu_clerk', 92)->canManageCaseParties($active));
        $this->assertTrue($this->user('hu_admin', 93)->canManageCaseParties($active));
        $this->assertTrue($this->user('hu_clerk', 94)->canManageCaseParties($active));
    }

    public function test_contract_attorney_can_serve_private_clients_on_separate_active_cases(): void
    {
        $attorney = $this->user('contract_attorney', 95, 'contract@example.test');

        $wrdCase = $this->case('active', 999);
        $wrdCase->setRelation('assignments', new Collection([
            $this->assignment(95, 'alu_atty'),
        ]));
        $wrdCase->setRelation('parties', new Collection());

        $privateCase = $this->case('active', 999);
        $privateCase->setRelation('assignments', new Collection());
        $privateCase->setRelation('parties', new Collection([
            $this->privateCounsel('contract@example.test'),
        ]));

        $this->assertSame('wrd', $attorney->contractAttorneyCapacity($wrdCase));
        $this->assertTrue($attorney->canAccessCase($wrdCase));
        $this->assertTrue($attorney->canUploadDocumentsToCase($wrdCase));
        $this->assertSame(['contract_attorney'], $attorney->documentFilingRoles($wrdCase));

        $this->assertSame('private_counsel', $attorney->contractAttorneyCapacity($privateCase));
        $this->assertTrue($attorney->canAccessCase($privateCase));
        $this->assertTrue($attorney->canUploadDocumentsToCase($privateCase));
        $this->assertSame(['external_attorney'], $attorney->documentFilingRoles($privateCase));
    }

    public function test_contract_attorney_cannot_act_in_both_capacities_on_the_same_case(): void
    {
        $attorney = $this->user('contract_attorney', 96, 'conflict@example.test');
        $case = $this->case('active', 999);
        $case->setRelation('assignments', new Collection([
            $this->assignment(96, 'alu_atty'),
        ]));
        $case->setRelation('parties', new Collection([
            $this->privateCounsel('conflict@example.test'),
        ]));

        $this->assertSame('conflict', $attorney->contractAttorneyCapacity($case));
        $this->assertFalse($attorney->canAccessCase($case));
        $this->assertFalse($attorney->canUploadDocumentsToCase($case));
    }

    public function test_private_counsel_access_does_not_grant_pre_active_alu_filing_powers(): void
    {
        $attorney = $this->user('contract_attorney', 97, 'private@example.test');

        foreach (['submitted_to_hu', 'closed'] as $status) {
            $case = $this->case($status, 999);
            $case->setRelation('assignments', new Collection());
            $case->setRelation('parties', new Collection([
                $this->privateCounsel('private@example.test'),
            ]));

            $this->assertTrue($attorney->canAccessCase($case));
            $this->assertFalse($attorney->canUploadDocumentsToCase($case));
        }
    }

    private function user(string $role, int $id, string $email = ''): User
    {
        return new class($role, $id, $email) extends User {
            private string $testRole;

            public function __construct(string $testRole = '', int $id = 0, string $email = '')
            {
                $this->testRole = $testRole;
                parent::__construct();
                $this->id = $id;
                $this->email = $email;
            }

            public function getCurrentRole(): string
            {
                return $this->testRole;
            }
        };
    }

    private function case(string $status, int $creatorId): CaseModel
    {
        $case = new CaseModel(['status' => $status, 'created_by_user_id' => $creatorId]);
        $case->id = 100;

        return $case;
    }

    private function assignment(int $userId, string $type): CaseAssignment
    {
        return new CaseAssignment([
            'user_id' => $userId,
            'assignment_type' => $type,
        ]);
    }

    private function privateCounsel(string $email): CaseParty
    {
        $party = new CaseParty([
            'role' => 'counsel',
            'representation_capacity' => CaseParty::CAPACITY_PRIVATE_COUNSEL,
        ]);
        $party->setRelation('person', new Person(['email' => $email]));

        return $party;
    }
}

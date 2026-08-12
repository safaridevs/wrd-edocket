<?php

namespace Tests\Unit;

use App\Models\CaseAssignment;
use App\Models\CaseModel;
use App\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class CaseCreationAuthorizationTest extends TestCase
{
    public function test_alu_and_contract_attorneys_can_create_cases_but_external_attorneys_cannot(): void
    {
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

    private function user(string $role, int $id): User
    {
        return new class($role, $id) extends User {
            private string $testRole;

            public function __construct(string $testRole = '', int $id = 0)
            {
                $this->testRole = $testRole;
                parent::__construct();
                $this->id = $id;
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
}

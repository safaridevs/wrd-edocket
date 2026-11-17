<?php

namespace Tests\Unit;

use App\Models\CaseModel;
use App\Models\User;
use App\Services\CaseConsolidationService;
use PHPUnit\Framework\TestCase;

class CaseConsolidationRulesTest extends TestCase
{
    public function test_consolidated_source_is_labeled_and_cannot_receive_filings(): void
    {
        $case = new CaseModel([
            'status' => 'active',
            'consolidated_into_case_id' => 42,
        ]);

        $user = $this->user('hu_admin');

        $this->assertTrue($case->isConsolidatedSource());
        $this->assertSame('Consolidated', $case->workflow_status_label);
        $this->assertSame('bg-indigo-100 text-indigo-800', $case->workflow_status_badge_class);
        $this->assertFalse($case->canUserUploadDocuments($user));
        $this->assertFalse($user->canManageDraftCase($case));
        $this->assertFalse($user->canUploadDocumentsToCase($case));
        $this->assertFalse($user->canManageCaseParties($case));
    }

    public function test_combined_case_remains_an_active_writable_case_for_hearing_unit(): void
    {
        $case = new CaseModel(['status' => 'active']);
        $user = $this->user('hu_admin');

        $this->assertFalse($case->isConsolidatedSource());
        $this->assertSame('Active', $case->workflow_status_label);
        $this->assertTrue($user->canUploadDocumentsToCase($case));
        $this->assertTrue($user->canManageCaseParties($case));
    }

    public function test_combined_identity_uses_oldest_case_first(): void
    {
        $newer = (object) [
            'id' => 2,
            'case_no' => '26-002',
            'caption' => 'Second Caption',
            'created_at' => '2026-02-01 00:00:00',
        ];

        $older = (object) [
            'id' => 1,
            'case_no' => '26-001',
            'caption' => 'First Caption',
            'created_at' => '2026-01-01 00:00:00',
        ];

        $identity = (new CaseConsolidationService())->buildCombinedIdentity([$newer, $older]);

        $this->assertSame('26-001 & 26-002 (Consol.)', $identity['case_no']);
        $this->assertSame('First Caption | Second Caption | Consolidated', $identity['caption']);
    }

    private function user(string $role): User
    {
        return new class($role) extends User {
            public function __construct(private string $testRole = '')
            {
                parent::__construct();
            }

            public function getCurrentRole(): string
            {
                return $this->testRole;
            }
        };
    }
}

<?php

namespace Tests\Unit;

use App\Models\CaseModel;
use App\Services\CaseCreationRules;
use PHPUnit\Framework\TestCase;

class CaseCreationRulesTest extends TestCase
{
    public function test_case_creation_only_accepts_roles_available_on_create_form(): void
    {
        $rules = (new CaseCreationRules())->validationRules('protested');

        $this->assertSame(
            'required|in:applicant,protestant,respondent',
            $rules['parties.*.role']
        );
    }

    public function test_rule_promulgation_is_a_supported_non_compliance_case_type(): void
    {
        $rules = (new CaseCreationRules())->validationRules('rule_promulgation');
        $protestedRules = (new CaseCreationRules())->validationRules('protested');

        $this->assertSame(
            ['aggrieved', 'protested', 'compliance', 'rule_promulgation'],
            CaseModel::caseTypes()
        );
        $this->assertStringContainsString('rule_promulgation', $rules['case_type']);
        $this->assertSame('required|array', $rules['documents.application']);
        $this->assertSame('required|in:request_pre_hearing,request_to_docket', $rules['pleading_type']);
        $this->assertArrayNotHasKey('documents.compliance', $rules);
        $this->assertSame($protestedRules['documents.application'], $rules['documents.application']);
        $this->assertSame($protestedRules['documents.application.*'], $rules['documents.application.*']);
        $this->assertSame($protestedRules['pleading_type'], $rules['pleading_type']);
    }

    public function test_compliance_document_rules_remain_unchanged(): void
    {
        $rules = (new CaseCreationRules())->validationRules('compliance');

        $this->assertSame('nullable|array', $rules['documents.application']);
        $this->assertSame('nullable|in:request_pre_hearing,request_to_docket', $rules['pleading_type']);
        $this->assertSame('required|array', $rules['documents.compliance']);
    }

    public function test_rule_promulgation_has_a_human_readable_label(): void
    {
        $case = new CaseModel(['case_type' => 'rule_promulgation']);

        $this->assertSame('Rule Promulgation', $case->case_type_label);
    }
}

<?php

namespace Tests\Unit;

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
}

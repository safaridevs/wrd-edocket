<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class DocumentFilingStampEligibilityTest extends TestCase
{
    public function test_initial_case_formation_documents_remain_unstamped(): void
    {
        foreach (['application', 'supporting', 'notice_publication', 'other'] as $type) {
            $document = $this->document(Document::FILING_CONTEXT_INITIAL, $type, 'none');

            $this->assertFalse($document->isEligibleForFilingStamp(), $type);
        }
    }

    public function test_initial_initiating_pleadings_are_eligible_for_a_filing_stamp(): void
    {
        foreach (['request_to_docket', 'request_pre_hearing'] as $pleadingType) {
            $document = $this->document(Document::FILING_CONTEXT_INITIAL, $pleadingType, $pleadingType);

            $this->assertTrue($document->isEligibleForFilingStamp(), $pleadingType);
        }
    }

    public function test_accepted_subsequent_filings_are_eligible_for_a_filing_stamp(): void
    {
        $document = $this->document(Document::FILING_CONTEXT_SUBSEQUENT, 'motion', 'none');

        $this->assertTrue($document->isEligibleForFilingStamp());
    }

    public function test_hearing_unit_documents_stay_in_the_separate_issuance_workflow(): void
    {
        $document = $this->document(Document::FILING_CONTEXT_HEARING_UNIT, 'order', 'none', true);

        $this->assertFalse($document->isEligibleForFilingStamp());
    }

    public function test_unaccepted_rejected_or_already_stamped_documents_are_not_eligible(): void
    {
        $unaccepted = $this->document(Document::FILING_CONTEXT_SUBSEQUENT, 'motion', 'none', false, false);
        $rejected = $this->document(Document::FILING_CONTEXT_SUBSEQUENT, 'motion', 'none');
        $rejected->rejected_reason = 'Correction required';
        $stamped = $this->document(Document::FILING_CONTEXT_SUBSEQUENT, 'motion', 'none');
        $stamped->stamped = true;

        $this->assertFalse($unaccepted->isEligibleForFilingStamp());
        $this->assertFalse($rejected->isEligibleForFilingStamp());
        $this->assertFalse($stamped->isEligibleForFilingStamp());
    }

    private function document(
        string $filingContext,
        string $docType,
        string $pleadingType,
        bool $hearingUnitUploader = false,
        bool $approved = true
    ): Document {
        $uploader = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isHearingUnit'])
            ->getMock();
        $uploader->method('isHearingUnit')->willReturn($hearingUnitUploader);

        $document = new Document([
            'filing_context' => $filingContext,
            'doc_type' => $docType,
            'pleading_type' => $pleadingType,
            'approved' => $approved,
            'stamped' => false,
        ]);
        $document->setRelation('uploader', $uploader);

        return $document;
    }
}

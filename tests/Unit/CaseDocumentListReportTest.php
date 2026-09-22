<?php

namespace Tests\Unit;

use App\Models\CaseModel;
use App\Models\Document;
use App\Models\User;
use App\Services\CaseDocumentListReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class CaseDocumentListReportTest extends TestCase
{
    public function test_it_numbers_the_initial_package_as_parent_and_children_then_numbers_later_filings(): void
    {
        $case = new CaseModel(['case_no' => 'OSE-2026-001']);
        $case->setRelation('partyHistory', new Collection());
        $case->setRelation('documents', new Collection([
            $this->document(1, 'pleading', 'request_to_docket', 'Request to Docket', 'alu_paralegal', '2026-01-10', Document::FILING_CONTEXT_INITIAL),
            $this->document(2, 'application', null, 'Application', 'alu_paralegal', '2026-01-09', Document::FILING_CONTEXT_INITIAL),
            $this->document(3, 'supporting', null, 'Notice of Publication', 'alu_paralegal', '2026-01-11', Document::FILING_CONTEXT_INITIAL),
            $this->document(4, 'motion', null, 'Entry of Appearance', 'external_attorney', '2026-02-01', Document::FILING_CONTEXT_SUBSEQUENT),
            $this->document(5, 'order', null, 'Docketing Order', 'hu_admin', '2026-01-20', Document::FILING_CONTEXT_HEARING_UNIT, true, '2026-01-22'),
        ]));

        $rows = (new CaseDocumentListReport())->rows($case);

        $this->assertSame(['1', 'A', 'B', '2', '3'], $rows->pluck('order')->all());
        $this->assertSame(
            ['Request to Docket', 'Application', 'Notice of Publication', 'Docketing Order', 'Entry of Appearance'],
            $rows->pluck('title')->all()
        );
        $this->assertSame(['WRD', 'WRD', 'WRD', 'Hearing Unit', 'Outside Filer'], $rows->pluck('filer')->all());
        $this->assertSame('2026-01-22', $rows[3]['date']->format('Y-m-d'));
    }

    public function test_it_excludes_rejected_documents_and_unissued_hearing_unit_previews(): void
    {
        $case = new CaseModel(['case_no' => 'OSE-2026-002']);
        $case->setRelation('partyHistory', new Collection());

        $rejected = $this->document(2, 'motion', null, 'Rejected Motion', 'external_attorney', '2026-02-01', Document::FILING_CONTEXT_SUBSEQUENT);
        $rejected->rejected_reason = 'Rejected';

        $case->setRelation('documents', new Collection([
            $this->document(1, 'motion', null, 'Filed Motion', 'external_attorney', '2026-01-01', Document::FILING_CONTEXT_SUBSEQUENT),
            $rejected,
            $this->document(3, 'order', null, 'Pending Order', 'hu_admin', '2026-02-02', Document::FILING_CONTEXT_HEARING_UNIT),
        ]));

        $rows = (new CaseDocumentListReport())->rows($case);

        $this->assertSame(['Filed Motion'], $rows->pluck('title')->all());
    }

    private function document(
        int $id,
        string $docType,
        ?string $pleadingType,
        string $title,
        string $role,
        string $uploadedAt,
        string $filingContext,
        bool $approved = false,
        ?string $approvedAt = null
    ): Document {
        $document = new Document([
            'doc_type' => $docType,
            'pleading_type' => $pleadingType,
            'custom_title' => $title,
            'uploaded_at' => Carbon::parse($uploadedAt),
            'filing_context' => $filingContext,
            'approved' => $approved,
            'approved_at' => $approvedAt ? Carbon::parse($approvedAt) : null,
        ]);
        $document->id = $id;

        $user = new User();
        $user->setRawAttributes([
            'name' => $role === 'external_attorney' ? 'Outside Filer' : 'Filer',
            'role' => $role,
            'email' => $role . '@example.test',
        ], true);
        $user->setRelation('roleRelation', null);
        $document->setRelation('uploader', $user);
        $document->setRelation('documentType', null);

        return $document;
    }
}

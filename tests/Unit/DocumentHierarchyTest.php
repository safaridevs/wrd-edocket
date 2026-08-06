<?php

namespace Tests\Unit;

use App\View\DocumentHierarchy;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use stdClass;

class DocumentHierarchyTest extends TestCase
{
    public function test_pleading_package_is_followed_by_one_chronological_top_level_stream(): void
    {
        $groups = DocumentHierarchy::groups(new Collection([
            $this->document(1, 'request_to_docket', 'request_to_docket', 'alu_clerk', '2025-02-17 09:00:00'),
            $this->document(2, 'application', 'none', 'alu_clerk', '2023-10-31 09:00:00'),
            $this->document(3, 'notice_publication', 'none', 'alu_clerk', '2024-01-12 09:00:00'),
            $this->document(4, 'motion', 'none', 'external_attorney', '2025-03-10 09:00:00'),
            $this->document(5, 'order', 'none', 'hu_admin', '2025-02-27 09:00:00'),
            $this->document(6, 'affidavit', 'none', 'alu_clerk', '2024-02-12 09:00:00'),
        ]));

        $this->assertSame([
            ['pleading', 0, false, [1]],
            ['pleading_children', 1, false, [2, 3, 6]],
            ['case_filings', 0, false, [5, 4]],
        ], array_map(fn (array $group) => [
            $group['key'],
            $group['level'],
            $group['show_heading'],
            $group['documents']->pluck('id')->all(),
        ], $groups));

        $this->assertSame(
            [1, 2, 3, 4, 5, 6],
            collect($groups)->flatMap(fn (array $group) => $group['documents']->pluck('id'))->sort()->values()->all()
        );
    }

    public function test_application_is_first_child_even_when_another_alu_document_is_older(): void
    {
        $groups = DocumentHierarchy::groups(new Collection([
            $this->document(1, 'request_pre_hearing', 'request_pre_hearing', 'alu_atty', '2025-02-17 09:00:00'),
            $this->document(2, 'notice_publication', 'none', 'alu_atty', '2023-01-01 09:00:00'),
            $this->document(3, 'application', 'none', 'alu_atty', '2023-10-31 09:00:00'),
        ]));

        $this->assertSame([3, 2], $groups[1]['documents']->pluck('id')->all());
    }

    private function document(int $id, string $docType, string $pleadingType, string $uploaderRole, string $uploadedAt): stdClass
    {
        $document = new stdClass();
        $document->id = $id;
        $document->doc_type = $docType;
        $document->pleading_type = $pleadingType;
        $document->uploaded_at = new \DateTimeImmutable($uploadedAt);
        $document->uploader = new class($uploaderRole) {
            public function __construct(private readonly string $role)
            {
            }

            public function getCurrentRole(): string
            {
                return $this->role;
            }

            public function isHearingUnit(): bool
            {
                return in_array($this->role, ['hu_admin', 'hu_clerk'], true);
            }
        };

        return $document;
    }
}

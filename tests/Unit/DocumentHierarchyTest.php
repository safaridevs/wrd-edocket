<?php

namespace Tests\Unit;

use App\View\DocumentHierarchy;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use stdClass;

class DocumentHierarchyTest extends TestCase
{
    public function test_only_alu_documents_are_nested_under_pleading_hierarchy(): void
    {
        $groups = DocumentHierarchy::groups(new Collection([
            $this->document(1, 'request_to_docket', 'request_to_docket', 'alu_clerk'),
            $this->document(2, 'application', 'none', 'alu_clerk'),
            $this->document(3, 'notice_publication', 'none', 'alu_clerk'),
            $this->document(4, 'motion', 'none', 'external_attorney'),
            $this->document(5, 'order', 'none', 'hu_admin'),
        ]));

        $this->assertSame([
            ['Request to Docket', 0, [1]],
            ['Application', 1, [2]],
            ['Notice of Pub', 2, [3]],
            ['HU Orders and Notices', 0, [5]],
            ['Party Filings', 0, [4]],
        ], array_map(fn (array $group) => [
            $group['label'],
            $group['level'],
            $group['documents']->pluck('id')->all(),
        ], $groups));
    }

    private function document(int $id, string $docType, string $pleadingType, string $uploaderRole): stdClass
    {
        $document = new stdClass();
        $document->id = $id;
        $document->doc_type = $docType;
        $document->pleading_type = $pleadingType;
        $document->uploaded_at = now()->subMinutes($id);
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

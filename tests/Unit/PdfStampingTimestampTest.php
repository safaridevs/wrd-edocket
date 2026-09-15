<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Models\User;
use App\Services\PdfConversionService;
use App\Services\PdfStampingService;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use ReflectionMethod;
use Tests\TestCase;

class PdfStampingTimestampTest extends TestCase
{
    public function test_pending_hearing_unit_preview_uses_the_final_stamp_layout(): void
    {
        $document = $this->document('hu_admin', '2026-09-09 08:15:00');

        $stamp = $this->buildStampText($document, CarbonImmutable::parse('2026-09-09 09:30:00'), true);

        $this->assertSame("Electronically Issued:\nSeptember 09, 2026 @ 9:30 AM\nOSE Hearing Unit/HU", $stamp);
    }

    public function test_hearing_unit_final_stamp_uses_the_actual_issuance_time(): void
    {
        $document = $this->document('hu_admin', '2026-09-09 08:15:00');

        $stamp = $this->buildStampText($document, CarbonImmutable::parse('2026-09-09 09:30:00'));

        $this->assertStringContainsString('September 09, 2026 @ 9:30 AM', $stamp);
        $this->assertStringNotContainsString('8:15 AM', $stamp);
    }

    public function test_non_hearing_unit_stamp_uses_the_filing_time(): void
    {
        $document = $this->document('party', '2026-09-09 08:15:00');

        $stamp = $this->buildStampText($document, $document->uploaded_at);

        $this->assertStringContainsString('September 09, 2026 @ 8:15 AM', $stamp);
    }

    public function test_unissued_hearing_unit_document_is_identified_as_pending(): void
    {
        $document = $this->document('hu_admin', '2026-09-09 08:15:00');
        $document->setRawAttributes(array_merge($document->getAttributes(), ['approved' => false]));

        $this->assertTrue($document->isPendingHearingUnitDocument());

        $document->setRawAttributes(array_merge($document->getAttributes(), ['approved' => true]));

        $this->assertFalse($document->isPendingHearingUnitDocument());
    }

    private function buildStampText(Document $document, DateTimeInterface $eventAt, bool $pending = false): string
    {
        $service = new PdfStampingService($this->createMock(PdfConversionService::class));
        $method = new ReflectionMethod($service, 'buildStampText');
        $method->setAccessible(true);

        return $method->invoke($service, $document, $document->uploader, $eventAt, $pending);
    }

    private function document(string $role, string $uploadedAt): Document
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isHearingUnit'])
            ->getMock();
        $user->method('isHearingUnit')->willReturn($role === 'hu_admin');
        $user->setRawAttributes(['initials' => 'HU']);

        $document = new Document(['uploaded_at' => $uploadedAt]);
        $document->setRelation('uploader', $user);

        return $document;
    }
}

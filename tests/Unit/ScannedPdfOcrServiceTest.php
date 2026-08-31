<?php

namespace Tests\Unit;

use App\Services\ScannedPdfOcrService;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ScannedPdfOcrServiceTest extends TestCase
{
    private ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $application = new Application(dirname(__DIR__, 2));
        $application->instance('config', new Repository([
            'edocket' => [
                'document_ocr' => [
                    'enabled' => false,
                    'python' => PHP_BINARY,
                    'script' => 'tests/Fixtures/fake_ocr.php',
                    'tesseract' => null,
                    'poppler' => null,
                    'language' => 'eng',
                    'dpi' => 300,
                    'max_pages' => 500,
                    'timeout' => 10,
                ],
            ],
        ]));
        Container::setInstance($application);
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_it_extracts_text_through_the_configured_worker(): void
    {
        $source = $this->temporaryPdf('PDF');

        try {
            $this->configureFakeWorker();

            $result = new ScannedPdfOcrService()->extract($source);

            $this->assertSame('pdf2image_pytesseract', $result['extractor']);
            $this->assertSame(1, $result['pages']);
            $this->assertStringContainsString('Recovered scanned PDF text.', $result['text']);
        } finally {
            @unlink($source);
        }
    }

    public function test_worker_failure_is_reported_without_modifying_the_source_pdf(): void
    {
        $source = $this->temporaryPdf('FAIL');
        $before = file_get_contents($source);

        try {
            $this->configureFakeWorker();

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Simulated OCR failure.');

            (new ScannedPdfOcrService())->extract($source);
        } finally {
            $this->assertSame($before, file_get_contents($source));
            @unlink($source);
        }
    }

    public function test_disabled_ocr_fails_closed_without_touching_the_source(): void
    {
        $source = $this->temporaryPdf('PDF');

        try {
            config()->set('edocket.document_ocr.enabled', false);

            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('Scanned-PDF OCR is disabled.');

            (new ScannedPdfOcrService())->extract($source);
        } finally {
            $this->assertSame('PDF', file_get_contents($source));
            @unlink($source);
        }
    }

    private function configureFakeWorker(): void
    {
        config()->set('edocket.document_ocr.enabled', true);
        config()->set('edocket.document_ocr.python', PHP_BINARY);
        config()->set('edocket.document_ocr.script', base_path('tests/Fixtures/fake_ocr.php'));
        config()->set('edocket.document_ocr.tesseract', null);
        config()->set('edocket.document_ocr.poppler', null);
        config()->set('edocket.document_ocr.timeout', 10);
    }

    private function temporaryPdf(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ocr_source_');
        file_put_contents($path, $contents);

        return $path;
    }
}

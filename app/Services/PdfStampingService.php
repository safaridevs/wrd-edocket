<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfStampingService
{
    public function stampDocument(Document $document, $case): bool
    {
        $stampText = "FILED\n" .
                    "New Mexico Office of the State Engineer\n" .
                    "Water Rights Hearing Unit\n" .
                    $document->uploaded_at->format('M d, Y') . " at " . $document->uploaded_at->format('g:i A') . "\n" .
                    "Case No: {$case->case_no}";
        
        $success = $this->stampPdf($document, auth()->user());
        
        if ($success) {
            $document->update([
                'stamped' => true,
                'stamp_text' => $stampText,
                'stamped_at' => now()
            ]);
        }
        
        return $success;
    }

    public function stampPdf(Document $document, User $user): bool
    {
        if ($document->mime !== 'application/pdf') {
            \Log::error('Document is not PDF: ' . $document->mime);
            throw new \RuntimeException('Only PDF documents can be e-stamped.');
        }

        $stampedPath = null;

        try {
            $originalPath = $this->getDocumentPath($document);
            \Log::info('Original PDF path: ' . $originalPath);

            $pageCount = $this->preflightPdf($originalPath);
            $stampedPath = $this->createStampedPdf($originalPath, $document, $user, $pageCount);
            \Log::info('Stamped PDF created at: ' . $stampedPath);

            $this->validateStampedPdf($stampedPath, $pageCount);

            // Replace original with stamped version
            $this->replaceOriginalFile($document, $stampedPath);
            \Log::info('Original file replaced with stamped version');

            return true;
        } catch (\Exception $e) {
            \Log::error('PDF stamping failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            if ($stampedPath && file_exists($stampedPath)) {
                @unlink($stampedPath);
            }

            throw new \RuntimeException($this->mapStampingErrorMessage($e), previous: $e);
        }
    }

    private function getDocumentPath(Document $document): string
    {
        if (Storage::disk('public')->exists($document->storage_uri)) {
            return Storage::disk('public')->path($document->storage_uri);
        }

        if (Storage::disk('private')->exists($document->storage_uri)) {
            return Storage::disk('private')->path($document->storage_uri);
        }

        throw new \Exception('Document file not found: ' . $document->storage_uri);
    }

    private function preflightPdf(string $originalPath): int
    {
        if (!file_exists($originalPath) || !is_readable($originalPath)) {
            throw new \RuntimeException('The source PDF could not be read from storage.');
        }

        if (filesize($originalPath) === 0) {
            throw new \RuntimeException('The source PDF is empty.');
        }

        $pdf = new Fpdi();

        try {
            $pageCount = $pdf->setSourceFile($originalPath);
        } catch (\Exception $e) {
            throw new \RuntimeException('The uploaded PDF failed preflight validation and cannot be stamped automatically.', previous: $e);
        }

        if ($pageCount < 1) {
            throw new \RuntimeException('The uploaded PDF has no pages to stamp.');
        }

        return $pageCount;
    }

    private function createStampedPdf(string $originalPath, Document $document, User $user, int $pageCount): string
    {
        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);

        try {
            // Import existing PDF
            $actualPageCount = $pdf->setSourceFile($originalPath);
            \Log::info('PDF has ' . $actualPageCount . ' pages');

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // Get page dimensions from imported page
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplId);

                // Add page with same orientation and size
                if ($size['width'] > $size['height']) {
                    $pdf->AddPage('L', [$size['width'], $size['height']]);
                } else {
                    $pdf->AddPage('P', [$size['width'], $size['height']]);
                }

                // Use the imported page as template
                $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);

                // Add stamp to first page only
                if ($pageNo === 1) {
                    $this->addStampToPage($pdf, $document, $user, $size);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error processing PDF: ' . $e->getMessage());
            throw $e;
        }

        // Ensure temp directory exists
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $stampedPath = $tempDir . '/stamped_' . time() . '_' . uniqid() . '.pdf';
        $pdf->Output($stampedPath, 'F');

        return $stampedPath;
    }

    private function validateStampedPdf(string $stampedPath, int $expectedPageCount): void
    {
        if (!file_exists($stampedPath) || !is_readable($stampedPath)) {
            throw new \RuntimeException('The stamped PDF output could not be read for validation.');
        }

        if (filesize($stampedPath) === 0) {
            throw new \RuntimeException('The stamped PDF output is empty.');
        }

        $pdf = new Fpdi();

        try {
            $actualPageCount = $pdf->setSourceFile($stampedPath);
        } catch (\Exception $e) {
            throw new \RuntimeException('The stamped PDF failed validation after creation.', previous: $e);
        }

        if ($actualPageCount !== $expectedPageCount) {
            throw new \RuntimeException("The stamped PDF page count changed unexpectedly ({$expectedPageCount} expected, {$actualPageCount} actual).");
        }
    }

    private function addStampToPage(Fpdi $pdf, Document $document, User $user, array $pageSize): void
    {
        // Use points directly (TCPDF native units)
        $pageWidth = $pageSize['width'];
        $pageHeight = $pageSize['height'];

        $y = 15;            // 15 points from top
        $rightMargin = 15;  // 15 points from right
        $lineHeight = 12;   // line height in points

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(255, 0, 0);

        $stampDate = $document->uploaded_at->format('D n/j/y');
        $stampTime = $document->uploaded_at->format('g:i A');
        $initials = $user->initials ?? 'HU';

        $stampText = "Electronically Filed\n{$stampDate} @ {$stampTime}\nOSE HEARING UNIT/{$initials}";
        
        // Calculate text width
        $lines = explode("\n", $stampText);
        $maxWidth = 0;
        foreach ($lines as $line) {
            $lineWidth = $pdf->GetStringWidth($line);
            if ($lineWidth > $maxWidth) {
                $maxWidth = $lineWidth;
            }
        }
        $stampWidth = $maxWidth + 4;
        $stampHeight = count($lines) * $lineHeight;

        // Position from right edge
        $x = $pageWidth - $rightMargin - $stampWidth;
        $pdf->SetXY($x, $y);

        // Draw text
        $pdf->MultiCell($stampWidth, $lineHeight, $stampText, 0, 'L', false);

        \Log::info("Stamp added at position: x={$x}, y={$y}, width={$stampWidth}, height={$stampHeight}, pageWidth={$pageWidth}");
    }


    private function replaceOriginalFile(Document $document, string $stampedPath): void
    {
        $disk = Storage::disk('public')->exists($document->storage_uri) ? 'public' : 'private';
        $originalContents = Storage::disk($disk)->get($document->storage_uri);

        try {
            Storage::disk($disk)->put($document->storage_uri, file_get_contents($stampedPath));
        } catch (\Exception $e) {
            Storage::disk($disk)->put($document->storage_uri, $originalContents);
            throw new \RuntimeException('The stamped PDF was created, but replacing the original file failed.', previous: $e);
        } finally {
            if (file_exists($stampedPath)) {
                @unlink($stampedPath);
            }
        }

        // Update document size
        $newSize = Storage::disk($disk)->size($document->storage_uri);
        $document->update(['size_bytes' => $newSize]);

        \Log::info('File replaced. New size: ' . $newSize . ' bytes');
    }

    private function mapStampingErrorMessage(\Throwable $e): string
    {
        $message = $e->getMessage();
        $messageLower = strtolower($message);

        if (str_contains($messageLower, 'compression technique which is not supported by the free parser')) {
            return 'This PDF cannot be e-stamped because it uses an unsupported PDF compression format. Re-save or print the document to PDF and upload the new file, then try stamping again.';
        }

        if (str_contains($messageLower, 'encrypted') || str_contains($messageLower, 'password')) {
            return 'This PDF appears to be encrypted or password protected and cannot be e-stamped automatically.';
        }

        if (str_contains($messageLower, 'empty')) {
            return 'This PDF appears to be empty and cannot be e-stamped.';
        }

        if (
            str_contains($messageLower, 'preflight')
            || str_contains($messageLower, 'validation')
            || str_contains($messageLower, 'cross-reference')
            || str_contains($messageLower, 'xref')
            || str_contains($messageLower, 'trailer')
        ) {
            return 'This PDF failed validation and cannot be e-stamped automatically. Re-save or print the document to PDF and upload the new file, then try again.';
        }

        return 'Unable to e-stamp this PDF. Check the document format and try again.';
    }
}

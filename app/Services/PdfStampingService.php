<?php

namespace App\Services;

use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Tcpdf\Fpdi;

class PdfStampingService
{
    public function stampPdf(Document $document, User $user): bool
    {
        if ($document->mime !== 'application/pdf') {
            \Log::error('Document is not PDF: ' . $document->mime);
            return false;
        }

        try {
            $originalPath = $this->getDocumentPath($document);
            \Log::info('Original PDF path: ' . $originalPath);
            
            $stampedPath = $this->createStampedPdf($originalPath, $document, $user);
            \Log::info('Stamped PDF created at: ' . $stampedPath);
            
            // Replace original with stamped version
            $this->replaceOriginalFile($document, $stampedPath);
            \Log::info('Original file replaced with stamped version');
            
            return true;
        } catch (\Exception $e) {
            \Log::error('PDF stamping failed: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return false;
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

    private function createStampedPdf(string $originalPath, Document $document, User $user): string
    {
        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        
        try {
            // Import existing PDF
            $pageCount = $pdf->setSourceFile($originalPath);
            \Log::info('PDF has ' . $pageCount . ' pages');
            
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

    private function addStampToPage(Fpdi $pdf, Document $document, User $user, array $pageSize): void
    {
        // Convert points to mm (1 point = 0.352778 mm)
        $pageWidth = $pageSize['width'] * 0.352778;
        $pageHeight = $pageSize['height'] * 0.352778;
        
        // Position stamp in top-right corner
        $stampWidth = 50;
        $stampHeight = 20;
        $x = $pageWidth - $stampWidth - 5; // 5mm from right edge
        $y = 5; // 5mm from top
        
        // Set font and size
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(0, 0, 0);
        
        // Create stamp text
        $stampDate = now()->format('D n/j/y');
        $stampTime = now()->format('g:i A');
        $initials = $user->initials ?? strtoupper(substr($user->name, 0, 2));
        
        $stampText = "Electronically Filed,\n{$stampDate} @ {$stampTime},\nOSE HEARING UNIT/{$initials}";
        
        // Draw border
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect($x, $y, $stampWidth, $stampHeight);
        
        // Add text
        $pdf->SetXY($x + 1, $y + 1);
        $pdf->MultiCell($stampWidth - 2, 4, $stampText, 0, 'C', false, 1);
        
        \Log::info('Stamp added at position: x=' . $x . ', y=' . $y . ', width=' . $stampWidth . ', height=' . $stampHeight);
    }

    private function replaceOriginalFile(Document $document, string $stampedPath): void
    {
        $disk = Storage::disk('public')->exists($document->storage_uri) ? 'public' : 'private';
        
        // Replace the original file
        Storage::disk($disk)->put($document->storage_uri, file_get_contents($stampedPath));
        
        // Clean up temp file
        unlink($stampedPath);
        
        // Update document size
        $newSize = Storage::disk($disk)->size($document->storage_uri);
        $document->update(['size_bytes' => $newSize]);
        
        \Log::info('File replaced. New size: ' . $newSize . ' bytes');
    }
}
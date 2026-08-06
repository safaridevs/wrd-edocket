<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DocumentTextExtractionService
{
    private const MIN_SEARCHABLE_TEXT_LENGTH = 20;

    public function extract(Document $document, bool $allowOcr = false): array
    {
        $path = $this->documentPath($document);

        if (!$path) {
            return $this->result('failed', null, null, 'Document file was not found in storage.');
        }

        $extension = strtolower(pathinfo($document->original_filename ?: $document->stored_filename, PATHINFO_EXTENSION));
        $mime = strtolower((string) $document->mime);

        if ($extension === 'pdf' || $mime === 'application/pdf') {
            return $this->extractPdf($path, $allowOcr);
        }

        if ($extension === 'docx' || str_contains($mime, 'wordprocessingml')) {
            return $this->extractDocx($path);
        }

        if (in_array($extension, ['txt', 'csv', 'md', 'log'], true) || str_starts_with($mime, 'text/')) {
            return $this->extractPlainText($path);
        }

        return $this->result('unsupported', null, null, 'This file type is not supported by the local text extractor yet.');
    }

    private function documentPath(Document $document): ?string
    {
        if (!$document->storage_uri) {
            return null;
        }

        foreach (['public', 'private'] as $disk) {
            if (Storage::disk($disk)->exists($document->storage_uri)) {
                return Storage::disk($disk)->path($document->storage_uri);
            }
        }

        return null;
    }

    private function extractPdf(string $path, bool $allowOcr): array
    {
        if (!$this->commandAvailable('pdftotext')) {
            if ($allowOcr && $this->commandAvailable('ocrmypdf')) {
                return $this->extractPdfWithOcr($path);
            }

            return $this->result('unsupported', null, null, 'Install Poppler pdftotext on the server to index searchable PDF text.');
        }

        $result = $this->extractPdfText($path);

        if ($result['status'] === 'indexed' && $this->hasSearchableText($result['text'])) {
            return $result;
        }

        if (!$allowOcr) {
            if ($result['status'] !== 'indexed') {
                return $result;
            }

            return $this->result('ocr_required', $result['text'], 'pdftotext', 'No searchable PDF text was found. Re-run indexing with --ocr after OCRmyPDF is installed.');
        }

        if (!$this->commandAvailable('ocrmypdf')) {
            return $this->result('ocr_required', $result['text'], 'pdftotext', 'No searchable PDF text was found. Install OCRmyPDF and re-run indexing with --ocr.');
        }

        return $this->extractPdfWithOcr($path);
    }

    private function extractPdfText(string $path): array
    {
        $output = [];
        $exitCode = 1;

        @exec('pdftotext -layout ' . escapeshellarg($path) . ' - 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            return $this->result('failed', null, 'pdftotext', trim(implode("\n", $output)) ?: 'pdftotext could not extract text from this PDF.');
        }

        return $this->result('indexed', $this->cleanText(implode("\n", $output)), 'pdftotext');
    }

    private function extractPdfWithOcr(string $path): array
    {
        $tempDirectory = storage_path('app/search-ocr');
        if (!is_dir($tempDirectory) && !mkdir($tempDirectory, 0775, true) && !is_dir($tempDirectory)) {
            return $this->result('failed', null, 'ocrmypdf', 'Could not create temporary OCR directory.');
        }

        $token = bin2hex(random_bytes(8));
        $ocrPdf = $tempDirectory . DIRECTORY_SEPARATOR . "{$token}.pdf";
        $sidecarText = $tempDirectory . DIRECTORY_SEPARATOR . "{$token}.txt";
        $output = [];
        $exitCode = 1;

        $command = implode(' ', [
            'ocrmypdf',
            '--skip-text',
            '--optimize', '0',
            '--sidecar', escapeshellarg($sidecarText),
            escapeshellarg($path),
            escapeshellarg($ocrPdf),
            '2>&1',
        ]);

        try {
            @exec($command, $output, $exitCode);

            if ($exitCode !== 0) {
                return $this->result('failed', null, 'ocrmypdf', trim(implode("\n", $output)) ?: 'OCRmyPDF could not extract text from this PDF.');
            }

            $text = is_file($sidecarText) ? file_get_contents($sidecarText) : null;
            $text = $this->cleanText($text === false ? null : $text);

            if (!$this->hasSearchableText($text)) {
                return $this->result('failed', $text, 'ocrmypdf', 'OCR completed, but no searchable text was found.');
            }

            return $this->result('ocr_indexed', $text, 'ocrmypdf');
        } finally {
            if (is_file($ocrPdf)) {
                @unlink($ocrPdf);
            }

            if (is_file($sidecarText)) {
                @unlink($sidecarText);
            }
        }
    }

    private function extractDocx(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            return $this->result('unsupported', null, null, 'PHP ZipArchive is required to index DOCX files.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return $this->result('failed', null, 'docx_zip', 'DOCX file could not be opened.');
        }

        $parts = ['word/document.xml'];
        for ($i = 1; $i <= 20; $i++) {
            $parts[] = "word/header{$i}.xml";
            $parts[] = "word/footer{$i}.xml";
        }

        $text = '';
        foreach ($parts as $part) {
            $xml = $zip->getFromName($part);
            if ($xml !== false) {
                $text .= ' ' . $this->xmlText($xml);
            }
        }

        $zip->close();

        return $this->result('indexed', $this->cleanText($text), 'docx_zip');
    }

    private function extractPlainText(string $path): array
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return $this->result('failed', null, 'plain_text', 'Text file could not be read.');
        }

        return $this->result('indexed', $this->cleanText($contents), 'plain_text');
    }

    private function xmlText(string $xml): string
    {
        $xml = preg_replace('/<w:tab\/>/', ' ', $xml) ?? $xml;
        $xml = preg_replace('/<\/w:p>/', "\n", $xml) ?? $xml;
        $xml = preg_replace('/<[^>]+>/', ' ', $xml) ?? $xml;

        return html_entity_decode($xml, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function cleanText(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    private function hasSearchableText(?string $text): bool
    {
        return mb_strlen(trim((string) $text)) >= self::MIN_SEARCHABLE_TEXT_LENGTH;
    }

    private function commandAvailable(string $command): bool
    {
        if (!function_exists('exec')) {
            return false;
        }

        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        if (in_array('exec', $disabled, true)) {
            return false;
        }

        $checkCommand = PHP_OS_FAMILY === 'Windows' ? 'where ' : 'command -v ';
        $nullDevice = PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null';
        $output = [];
        $exitCode = 1;

        @exec($checkCommand . escapeshellarg($command) . ' 2>' . $nullDevice, $output, $exitCode);

        return $exitCode === 0;
    }

    private function result(string $status, ?string $text, ?string $extractor = null, ?string $error = null): array
    {
        return [
            'status' => $status,
            'text' => $text,
            'extractor' => $extractor,
            'error' => $error,
        ];
    }
}

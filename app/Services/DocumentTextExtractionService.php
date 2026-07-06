<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class DocumentTextExtractionService
{
    public function extract(Document $document): array
    {
        $path = $this->documentPath($document);

        if (!$path) {
            return $this->result('failed', null, null, 'Document file was not found in storage.');
        }

        $extension = strtolower(pathinfo($document->original_filename ?: $document->stored_filename, PATHINFO_EXTENSION));
        $mime = strtolower((string) $document->mime);

        if ($extension === 'pdf' || $mime === 'application/pdf') {
            return $this->extractPdf($path);
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

    private function extractPdf(string $path): array
    {
        if (!$this->commandAvailable('pdftotext')) {
            return $this->result('unsupported', null, null, 'Install Poppler pdftotext on the server to index searchable PDF text.');
        }

        $output = [];
        $exitCode = 1;

        @exec('pdftotext -layout ' . escapeshellarg($path) . ' - 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            return $this->result('failed', null, 'pdftotext', trim(implode("\n", $output)) ?: 'pdftotext could not extract text from this PDF.');
        }

        return $this->result('indexed', $this->cleanText(implode("\n", $output)), 'pdftotext');
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

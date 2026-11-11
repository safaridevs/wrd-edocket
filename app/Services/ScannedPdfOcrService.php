<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class ScannedPdfOcrService
{
    public function isEnabled(): bool
    {
        return (bool) config('edocket.document_ocr.enabled', false);
    }

    public function extract(string $inputPath): array
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('Scanned-PDF OCR is disabled.');
        }

        if (!is_file($inputPath) || !is_readable($inputPath)) {
            throw new \RuntimeException('The source PDF could not be read for OCR.');
        }

        $scriptPath = $this->resolvePath((string) config('edocket.document_ocr.script', 'tools/ocr/extract_pdf.py'));
        if (!is_file($scriptPath) || !is_readable($scriptPath)) {
            throw new \RuntimeException('The scanned-PDF OCR utility is not available.');
        }

        $outputPath = $this->makeOutputPath();
        $command = [
            (string) config('edocket.document_ocr.python', 'python'),
            $scriptPath,
            $inputPath,
            $outputPath,
            '--language', (string) config('edocket.document_ocr.language', 'eng'),
            '--dpi', (string) config('edocket.document_ocr.dpi', 300),
            '--max-pages', (string) config('edocket.document_ocr.max_pages', 500),
            '--json',
        ];

        $tesseractPath = trim((string) config('edocket.document_ocr.tesseract'));
        if ($tesseractPath !== '') {
            array_push($command, '--tesseract', $tesseractPath);
        }

        $popplerPath = trim((string) config('edocket.document_ocr.poppler'));
        if ($popplerPath !== '') {
            array_push($command, '--poppler', $popplerPath);
        }

        $process = new Process($command);
        $process->setTimeout(max(1, (int) config('edocket.document_ocr.timeout', 600)));

        try {
            $process->run();
            $payload = $this->decodeJsonOutput($process->getOutput());

            if (!$process->isSuccessful() || !($payload['ok'] ?? false)) {
                $error = ($payload['error'] ?? trim($process->getErrorOutput())) ?: 'Unknown scanned-PDF OCR error.';
                throw new \RuntimeException($error);
            }

            if (!is_file($outputPath) || !is_readable($outputPath)) {
                throw new \RuntimeException('OCR completed without producing readable text output.');
            }

            $text = file_get_contents($outputPath);
            if ($text === false || trim($text) === '') {
                throw new \RuntimeException('OCR completed, but no searchable text was found.');
            }

            return [
                'text' => trim($text),
                'pages' => (int) ($payload['pages'] ?? 0),
                'extractor' => 'pdf2image_pytesseract',
            ];
        } finally {
            if (is_file($outputPath)) {
                @unlink($outputPath);
            }
        }
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function makeOutputPath(): string
    {
        $directory = storage_path('app/search-ocr');
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Could not create the temporary OCR directory.');
        }

        return $directory . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12)) . '.txt';
    }

    private function decodeJsonOutput(string $output): array
    {
        $payload = json_decode(trim($output), true);

        return is_array($payload) ? $payload : [];
    }
}

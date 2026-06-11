<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class PdfConversionService
{
    public function convertForStamping(string $inputPath): string
    {
        if (!config('edocket.pdf_conversion.enabled', true)) {
            throw new \RuntimeException('PDF conversion fallback is disabled.');
        }

        if (!file_exists($inputPath) || !is_readable($inputPath)) {
            throw new \RuntimeException('The source PDF could not be read for conversion.');
        }

        $scriptPath = $this->resolvePath((string) config('edocket.pdf_conversion.script', 'tools/pdf/convert.py'));

        if (!file_exists($scriptPath) || !is_readable($scriptPath)) {
            throw new \RuntimeException('The PDF conversion utility is not available.');
        }

        $outputPath = $this->makeTempPdfPath('converted_');
        $process = new Process([
            (string) config('edocket.pdf_conversion.python', 'python'),
            $scriptPath,
            $inputPath,
            $outputPath,
            (string) config('edocket.pdf_conversion.version', '1.4'),
            '--json',
        ]);
        $process->setTimeout((int) config('edocket.pdf_conversion.timeout', 60));
        $process->run();

        $payload = $this->decodeJsonOutput($process->getOutput());

        if (!$process->isSuccessful() || !($payload['ok'] ?? false)) {
            $this->deleteIfExists($outputPath);

            $error = ($payload['error'] ?? trim($process->getErrorOutput())) ?: 'Unknown PDF conversion error.';
            throw new \RuntimeException('PDF conversion failed: ' . $error);
        }

        $convertedPath = (string) ($payload['output'] ?? $outputPath);

        try {
            $this->validateConvertedPdf($convertedPath);
        } catch (\RuntimeException $e) {
            $this->deleteIfExists($convertedPath);
            throw $e;
        }

        return $convertedPath;
    }

    private function resolvePath(string $path): string
    {
        if ($this->isAbsolutePath($path)) {
            return $path;
        }

        return base_path($path);
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    private function makeTempPdfPath(string $prefix): string
    {
        $tempDir = storage_path('app/temp');

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        return $tempDir . DIRECTORY_SEPARATOR . $prefix . time() . '_' . uniqid() . '.pdf';
    }

    private function decodeJsonOutput(string $output): array
    {
        $output = trim($output);

        if ($output === '') {
            return [];
        }

        $payload = json_decode($output, true);

        return is_array($payload) ? $payload : [];
    }

    private function validateConvertedPdf(string $path): void
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new \RuntimeException('The converted PDF output could not be read.');
        }

        if (filesize($path) === 0) {
            throw new \RuntimeException('The converted PDF output is empty.');
        }
    }

    private function deleteIfExists(string $path): void
    {
        if ($path !== '' && file_exists($path)) {
            @unlink($path);
        }
    }
}

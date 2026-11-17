<?php

namespace App\Services;

use App\Models\CaseMedia;
use Illuminate\Validation\ValidationException;

class CaseMediaFileService
{
    private const EXTENSIONS = [
        'mp4' => ['video', 'video/mp4'],
        'm4v' => ['video', 'video/x-m4v'],
        'mov' => ['video', 'video/quicktime'],
        'webm' => ['video', 'video/webm'],
        'mp3' => ['audio', 'audio/mpeg'],
        'm4a' => ['audio', 'audio/mp4'],
        'wav' => ['audio', 'audio/wav'],
        'aac' => ['audio', 'audio/aac'],
        'ogg' => ['audio', 'audio/ogg'],
    ];

    public function inspect(string $enteredPath): array
    {
        $absolutePath = $this->resolveEnteredPath($enteredPath);
        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        if (!isset(self::EXTENSIONS[$extension])) {
            throw ValidationException::withMessages([
                'media_path' => 'The selected file is not a supported audio or video format.',
            ]);
        }

        [$mediaType, $fallbackMime] = self::EXTENSIONS[$extension];
        $detectedMime = function_exists('mime_content_type') ? @mime_content_type($absolutePath) : null;
        $mimeType = is_string($detectedMime) && preg_match('#^(audio|video)/#', $detectedMime)
            ? $detectedMime
            : $fallbackMime;

        return [
            'absolute_path' => $absolutePath,
            'relative_path' => $this->relativePath($absolutePath),
            'original_filename' => basename($absolutePath),
            'media_type' => $mediaType,
            'mime_type' => $mimeType,
            'size_bytes' => filesize($absolutePath),
        ];
    }

    public function path(CaseMedia $media): string
    {
        return $this->resolveEnteredPath($media->relative_path);
    }

    private function resolveEnteredPath(string $enteredPath): string
    {
        $root = $this->root();
        $enteredPath = trim($enteredPath, " \t\n\r\0\x0B\"'");
        $candidate = $this->isAbsolutePath($enteredPath)
            ? $enteredPath
            : $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $enteredPath);
        $resolved = realpath($candidate);

        if ($resolved === false || !is_file($resolved) || !$this->isWithinRoot($resolved, $root)) {
            throw ValidationException::withMessages([
                'media_path' => 'The media file was not found within the configured case media directory.',
            ]);
        }

        return $resolved;
    }

    private function root(): string
    {
        $configuredRoot = trim((string) config('edocket.case_media.root'));
        $root = $configuredRoot !== '' ? realpath($configuredRoot) : false;

        if ($root === false || !is_dir($root)) {
            throw ValidationException::withMessages([
                'media_path' => 'The case media directory is not configured or is unavailable.',
            ]);
        }

        return rtrim($root, DIRECTORY_SEPARATOR);
    }

    private function relativePath(string $absolutePath): string
    {
        $root = $this->root();

        return str_replace(DIRECTORY_SEPARATOR, '/', ltrim(substr($absolutePath, strlen($root)), DIRECTORY_SEPARATOR));
    }

    private function isWithinRoot(string $path, string $root): bool
    {
        $path = strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path));
        $root = strtolower(rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $root), DIRECTORY_SEPARATOR));

        return $path === $root || str_starts_with($path, $root . DIRECTORY_SEPARATOR);
    }

    private function isAbsolutePath(string $path): bool
    {
        return (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path)
            || str_starts_with($path, '\\\\')
            || str_starts_with($path, '/');
    }
}

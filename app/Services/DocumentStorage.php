<?php

namespace App\Services;

use App\Exceptions\DocumentFileNotFound;
use App\Models\Document;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The single place E-Docket reads and writes document files.
 *
 * Files live on the `documents` disk: a private local folder by default, or the S3 bucket when
 * DOCUMENTS_DRIVER=s3. Nothing here is web-served; downloads stream through authorized routes.
 *
 * Until `php artisan documents:migrate-storage` has run, reads fall back to the legacy `public`
 * and `private` local disks, so a deploy does not hide files that have not been copied yet.
 * New writes always go to the `documents` disk.
 */
class DocumentStorage
{
    public const DISK = 'documents';

    public const LEGACY_DISKS = ['public', 'private'];

    public function disk(): FilesystemAdapter
    {
        return Storage::disk(self::DISK);
    }

    /** Store an upload under $folder/$filename and return its storage key. */
    public function store(UploadedFile $file, string $folder, string $filename): string
    {
        $key = $file->storeAs($folder, $filename, ['disk' => self::DISK]);

        if (!is_string($key) || $key === '') {
            throw new \RuntimeException("Could not store document {$filename}.");
        }

        return $key;
    }

    /** Overwrite the object at $key with the contents of a local file. */
    public function putFile(string $key, string $localPath): void
    {
        $stream = fopen($localPath, 'rb');

        if ($stream === false) {
            throw new \RuntimeException("Could not read {$localPath}.");
        }

        try {
            $this->disk()->writeStream($key, $stream);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function get(string $key): string
    {
        return $this->locate($key)->get($key);
    }

    public function put(string $key, string $contents): void
    {
        $this->disk()->put($key, $contents);
    }

    public function size(string $key): int
    {
        return $this->locate($key)->size($key);
    }

    public function exists(?string $key): bool
    {
        return $this->find($key) !== null;
    }

    /** True if $key is free on the documents disk (used when choosing a new filename). */
    public function isFree(string $key): bool
    {
        return !$this->disk()->exists($key);
    }

    /** Delete the file wherever it lives, including a not-yet-migrated legacy copy. */
    public function delete(?string $key): void
    {
        if (!$key) {
            return;
        }

        foreach ([self::DISK, ...self::LEGACY_DISKS] as $name) {
            $disk = Storage::disk($name);

            if ($disk->exists($key)) {
                $disk->delete($key);
            }
        }
    }

    public function download(Document $document): StreamedResponse
    {
        return $this->response($document, 'attachment');
    }

    public function inline(Document $document): StreamedResponse
    {
        return $this->response($document, 'inline');
    }

    /**
     * Run $callback with a local filesystem path to the document's file, for tools that need
     * one (pdftotext, ocrmypdf, FPDI, the PDF converter, ZipArchive). Local disks hand over the
     * real path; remote disks are copied to a temp file that is removed afterwards.
     *
     * @template T
     * @param callable(string $localPath): T $callback
     * @return T
     */
    public function withLocalCopy(Document $document, callable $callback): mixed
    {
        $key = $document->storage_uri;
        $disk = $this->locate($key);

        if ($this->isLocal($disk)) {
            return $callback($disk->path($key));
        }

        $tempPath = $this->tempPath(pathinfo($key, PATHINFO_EXTENSION));
        $source = $disk->readStream($key);
        $target = fopen($tempPath, 'wb');

        try {
            if (!is_resource($source) || $target === false || stream_copy_to_stream($source, $target) === false) {
                throw new \RuntimeException("Could not copy {$key} to a local file.");
            }

            fclose($target);
            $target = null;

            return $callback($tempPath);
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($target)) {
                fclose($target);
            }
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function response(Document $document, string $disposition): StreamedResponse
    {
        $key = $document->storage_uri;
        $disk = $this->find($key);

        if (!$disk) {
            abort(404, 'Document file not found.');
        }

        return $disk->response(
            $key,
            $document->original_filename ?: basename($key),
            ['Content-Type' => $document->mime ?: 'application/octet-stream'],
            $disposition
        );
    }

    private function locate(?string $key): FilesystemAdapter
    {
        return $this->find($key) ?? throw DocumentFileNotFound::forKey($key);
    }

    private function find(?string $key): ?FilesystemAdapter
    {
        if (!$key) {
            return null;
        }

        foreach ([self::DISK, ...self::LEGACY_DISKS] as $name) {
            $disk = Storage::disk($name);

            if ($disk->exists($key)) {
                return $disk;
            }
        }

        return null;
    }

    private function isLocal(FilesystemAdapter $disk): bool
    {
        return $disk->getAdapter() instanceof \League\Flysystem\Local\LocalFilesystemAdapter;
    }

    private function tempPath(string $extension): string
    {
        $dir = storage_path('app/temp');

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create the temporary directory.');
        }

        return $dir . DIRECTORY_SEPARATOR . 'doc_' . bin2hex(random_bytes(8)) . ($extension !== '' ? ".{$extension}" : '');
    }
}

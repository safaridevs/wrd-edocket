<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateDocumentStorage extends Command
{
    protected $signature = 'documents:migrate-storage {--dry-run : Report what would be copied without copying anything}';

    protected $description = 'Copy document files from the legacy public/private disks onto the documents disk (safe to re-run; never deletes or overwrites)';

    public function handle(): int
    {
        $target = Storage::disk(DocumentStorage::DISK);
        $dryRun = (bool) $this->option('dry-run');

        $present = 0;
        $copied = 0;
        $missing = [];
        $failed = [];

        Document::query()->orderBy('id')->chunkById(100, function ($documents) use ($target, $dryRun, &$present, &$copied, &$missing, &$failed) {
            foreach ($documents as $document) {
                $key = $document->storage_uri;

                if (!$key) {
                    $missing[] = "{$document->id}: no storage key";
                    continue;
                }

                // Already there — including files stamped after the deploy, which must not be
                // overwritten by the unstamped legacy copy.
                if ($target->exists($key)) {
                    $present++;
                    continue;
                }

                $source = $this->legacyDiskFor($key);

                if (!$source) {
                    $missing[] = "{$document->id}: {$key}";
                    continue;
                }

                if ($dryRun) {
                    $copied++;
                    $this->line("would copy {$document->id}: {$key}");
                    continue;
                }

                try {
                    $stream = $source->readStream($key);
                    $target->writeStream($key, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }

                    $expected = $source->size($key);
                    $actual = $target->size($key);
                    if ($expected !== $actual) {
                        throw new \RuntimeException("size mismatch after copy ({$expected} vs {$actual} bytes)");
                    }

                    $copied++;
                    $this->line("copied {$document->id}: {$key}");
                } catch (\Throwable $e) {
                    $failed[] = "{$document->id}: {$key} — {$e->getMessage()}";
                }
            }
        });

        foreach ($missing as $line) {
            $this->warn("missing {$line}");
        }
        foreach ($failed as $line) {
            $this->error("failed {$line}");
        }

        $verb = $dryRun ? 'Would copy' : 'Copied';
        $this->info("{$verb} {$copied}. Already on the documents disk: {$present}. Missing everywhere: " . count($missing) . '. Failed: ' . count($failed) . '.');

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    private function legacyDiskFor(string $key): ?\Illuminate\Contracts\Filesystem\Filesystem
    {
        foreach (DocumentStorage::LEGACY_DISKS as $name) {
            $disk = Storage::disk($name);

            if ($disk->exists($key)) {
                return $disk;
            }
        }

        return null;
    }
}

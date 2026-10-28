<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Services\DocumentTextIndexService;
use Illuminate\Console\Command;

class IndexDocumentText extends Command
{
    protected $signature = 'documents:index-text {--document= : Index one document ID} {--limit= : Maximum number of documents to index} {--force : Re-index documents that already have text index rows} {--ocr : Use the Python/Tesseract fallback for scanned PDFs when normal text extraction finds no text}';

    protected $description = 'Extract searchable text from stored documents into the document_texts table';

    public function handle(DocumentTextIndexService $indexService): int
    {
        $query = Document::query()
            ->with('textIndex')
            ->orderBy('id');

        if ($documentId = $this->option('document')) {
            $query->whereKey($documentId);
        }

        if (!$this->option('force')) {
            $query->whereDoesntHave('textIndex');
        }

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $indexed = 0;
        $failed = 0;

        $allowOcr = (bool) $this->option('ocr');

        $query->chunkById(50, function ($documents) use ($indexService, $allowOcr, &$indexed, &$failed) {
            foreach ($documents as $document) {
                try {
                    $textIndex = $indexService->index($document, $allowOcr);
                    $indexed++;

                    $this->line("{$document->id}: {$textIndex->extraction_status}");
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("{$document->id}: {$e->getMessage()}");
                }
            }
        });

        $this->info("Indexed {$indexed} document(s). Failed {$failed} document(s).");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

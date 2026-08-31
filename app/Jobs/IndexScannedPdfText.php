<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentTextIndexService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndexScannedPdfText implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $documentId)
    {
        $this->timeout = max(1, (int) config('edocket.document_ocr.timeout', 600) + 30);
    }

    public int $timeout;

    public function handle(DocumentTextIndexService $indexService): void
    {
        $connection = DB::connection();
        $database = $connection->getDatabaseName();
        $document = Document::find($this->documentId);

        if (!$document) {
            throw new \RuntimeException(
                "Document {$this->documentId} was not found by the queue worker on database {$database}."
            );
        }

        Log::info('Queued scanned-PDF OCR started', [
            'document_id' => $document->id,
            'database_connection' => config('database.default'),
            'database' => $database,
        ]);

        $textIndex = $indexService->index($document, true);
        if ($textIndex->extraction_status === 'failed') {
            throw new \RuntimeException($textIndex->extraction_error ?: 'Scanned-PDF OCR failed.');
        }

        Log::info('Queued scanned-PDF OCR updated the search index', [
            'document_id' => $document->id,
            'document_text_id' => $textIndex->id,
            'extraction_status' => $textIndex->extraction_status,
            'extractor' => $textIndex->extractor,
            'text_length' => mb_strlen((string) $textIndex->content_text),
            'database_connection' => config('database.default'),
            'database' => $database,
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::warning('Queued scanned-PDF OCR failed without affecting the saved document', [
            'document_id' => $this->documentId,
            'error' => $exception?->getMessage(),
        ]);
    }
}

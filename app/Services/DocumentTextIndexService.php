<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentText;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DocumentTextIndexService
{
    public function __construct(private DocumentTextExtractionService $extractor) {}

    public function index(Document $document, bool $allowOcr = false): DocumentText
    {
        $result = $this->extractor->extract($document, $allowOcr);

        return DocumentText::updateOrCreate(
            ['document_id' => $document->id],
            [
                'content_text' => $result['text'],
                'extraction_status' => $result['status'],
                'extractor' => $result['extractor'],
                'extraction_error' => $result['error'],
                'indexed_at' => now(),
            ]
        );
    }

    public function indexBestEffort(Document $document): void
    {
        if (!Schema::hasTable('document_texts')) {
            return;
        }

        try {
            $this->index($document);
        } catch (\Throwable $e) {
            Log::warning('Document text indexing failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function search(User $user, string $term, int $perPage = 15): LengthAwarePaginator
    {
        $term = trim($term);
        $like = '%' . $this->escapeLike($term) . '%';
        $hasTextIndex = $this->isTextIndexAvailable();

        $query = Document::query()
            ->with($hasTextIndex ? ['case', 'documentType', 'textIndex'] : ['case', 'documentType'])
            ->whereHas('case', fn (Builder $query) => $this->scopeAccessibleCases($query, $user))
            ->where(function (Builder $query) use ($like, $hasTextIndex) {
                $query->where('original_filename', 'like', $like)
                    ->orWhere('custom_title', 'like', $like)
                    ->orWhere('doc_type', 'like', $like)
                    ->orWhereHas('case', function (Builder $caseQuery) use ($like) {
                        $caseQuery->where('case_no', 'like', $like)
                            ->orWhere('caption', 'like', $like);
                    });

                if ($hasTextIndex) {
                    $query->orWhereHas('textIndex', function (Builder $textQuery) use ($like) {
                        $textQuery->where('content_text', 'like', $like);
                    });
                }
            })
            ->latest('uploaded_at');

        return $query
            ->paginate($perPage)
            ->withQueryString();
    }

    public function isTextIndexAvailable(): bool
    {
        return Schema::hasTable('document_texts');
    }

    public function snippet(?string $text, string $term, int $radius = 120): ?string
    {
        $text = trim((string) $text);
        $term = trim($term);

        if ($text === '' || $term === '') {
            return null;
        }

        $position = stripos($text, $term);
        if ($position === false) {
            return mb_substr($text, 0, $radius * 2) . (mb_strlen($text) > $radius * 2 ? '...' : '');
        }

        $start = max(0, $position - $radius);
        $length = $radius + mb_strlen($term) + $radius;
        $snippet = mb_substr($text, $start, $length);

        return ($start > 0 ? '...' : '') . $snippet . (mb_strlen($text) > $start + $length ? '...' : '');
    }

    private function scopeAccessibleCases(Builder $query, User $user): void
    {
        if (!in_array($user->getCurrentRole(), ['party', 'interested_party', 'external_attorney', 'contract_attorney'], true)) {
            return;
        }

        $email = strtolower(trim((string) $user->email));

        $query->where(function (Builder $accessQuery) use ($email, $user) {
            $accessQuery->whereHas('parties.person', function (Builder $personQuery) use ($email) {
                $personQuery->whereRaw('LOWER(email) = ?', [$email]);
            })->orWhereHas('assignments', function (Builder $assignmentQuery) use ($user) {
                $assignmentQuery->whereIn('assignment_type', ['alu_paralegal', 'alu_atty', 'alu_attorney'])
                    ->where('user_id', $user->id);
            });
        });
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

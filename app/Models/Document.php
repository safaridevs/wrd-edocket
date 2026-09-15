<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use App\Services\DocumentTextIndexService;

class Document extends Model
{
    public const FILING_CONTEXT_INITIAL = 'initial_case_filing';
    public const FILING_CONTEXT_SUBSEQUENT = 'subsequent_filing';
    public const FILING_CONTEXT_HEARING_UNIT = 'hearing_unit_issuance';

    protected $fillable = [
        'case_id', 'doc_type', 'custom_title', 'original_filename', 'stored_filename', 'mime',
        'size_bytes', 'checksum', 'storage_uri', 'uploaded_by_user_id', 'uploaded_at',
        'stamped', 'stamp_text', 'stamped_at', 'approved', 'approved_by_user_id', 'approved_at', 'rejected_reason',
        'pleading_type', 'filing_context'
    ];

    protected $casts = [
        'sync_status' => 'array',
        'stamped_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_stamped' => 'boolean',
        'stamped' => 'boolean',
        'approved' => 'boolean'
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id')->withTrashed();
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'doc_type', 'code');
    }

    public function textIndex(): HasOne
    {
        return $this->hasOne(DocumentText::class);
    }

    public function correctionCycles(): HasMany
    {
        return $this->hasMany(DocumentCorrection::class, 'original_document_id')->latest('requested_at')->latest('id');
    }

    public function latestOpenCorrection(): HasOne
    {
        return $this->hasOne(DocumentCorrection::class, 'original_document_id')
            ->whereIn('status', ['open', 'resubmitted'])
            ->latest('requested_at')
            ->latest('id');
    }

    public function replacementForCorrection(): HasOne
    {
        return $this->hasOne(DocumentCorrection::class, 'replacement_document_id');
    }

    public function getDocTypeLabelAttribute(): string
    {
        if ($this->relationLoaded('documentType') && $this->documentType) {
            return $this->documentType->name;
        }

        $documentType = DocumentType::where('code', $this->doc_type)->first();
        if ($documentType) {
            return $documentType->name;
        }

        return Str::title(str_replace('_', ' ', $this->doc_type));
    }

    public function getPleadingTypeLabelAttribute(): string
    {
        return match ($this->pleading_type) {
            'request_pre_hearing' => 'Request for Pre-Hearing Scheduling Conference',
            'request_to_docket' => 'Request to Docket',
            default => Str::title(str_replace('_', ' ', (string) $this->pleading_type)),
        };
    }

    public function isPendingHearingUnitDocument(): bool
    {
        $this->loadMissing('uploader.roleRelation');

        return !$this->approved && (bool) $this->uploader?->isHearingUnit();
    }

    public function stamp(): void
    {
        $this->update([
            'is_stamped' => true,
            'stamped_at' => now()
        ]);
    }

    protected static function booted(): void
    {
        static::creating(function (Document $document) {
            if (blank($document->filing_context)) {
                $case = $document->case()->first();
                $uploader = $document->uploader()->first();

                $document->filing_context = self::filingContextFor($case, $uploader);
            }
        });

        static::saved(function (Document $document) {
            if ($document->wasRecentlyCreated || $document->wasChanged(['storage_uri', 'checksum'])) {
                app(DocumentTextIndexService::class)->indexBestEffort($document);
            }
        });
    }

    public static function filingContextFor(?CaseModel $case, ?User $uploader): string
    {
        if ($uploader?->isHearingUnit()) {
            return self::FILING_CONTEXT_HEARING_UNIT;
        }

        return $case?->status === 'active' || $case?->accepted_at
            ? self::FILING_CONTEXT_SUBSEQUENT
            : self::FILING_CONTEXT_INITIAL;
    }
}

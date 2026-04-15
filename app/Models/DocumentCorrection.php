<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCorrection extends Model
{
    protected $fillable = [
        'case_id',
        'original_document_id',
        'requested_by_user_id',
        'correction_type',
        'summary',
        'status',
        'requested_at',
        'resubmitted_at',
        'resubmitted_by_user_id',
        'replacement_document_id',
        'accepted_at',
        'accepted_by_user_id',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'resubmitted_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function originalDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'original_document_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function resubmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resubmitted_by_user_id');
    }

    public function replacementDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'replacement_document_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DocumentCorrectionItem::class, 'document_correction_id')->orderBy('sort_order')->orderBy('id');
    }
}

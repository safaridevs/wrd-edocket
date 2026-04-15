<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentCorrectionItem extends Model
{
    protected $fillable = [
        'document_correction_id',
        'category',
        'item_note',
        'required_action',
        'resolution_note',
        'resolved_by_user_id',
        'resolved_at',
        'sort_order',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function correction(): BelongsTo
    {
        return $this->belongsTo(DocumentCorrection::class, 'document_correction_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}

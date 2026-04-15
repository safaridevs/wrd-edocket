<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseRejectionItem extends Model
{
    protected $fillable = [
        'case_rejection_id',
        'category',
        'item_note',
        'document_id',
        'required_action',
        'resolution_note',
        'resolved_by_user_id',
        'resolved_at',
        'sort_order',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function rejection(): BelongsTo
    {
        return $this->belongsTo(CaseRejection::class, 'case_rejection_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }
}

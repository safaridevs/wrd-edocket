<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseRejection extends Model
{
    protected $fillable = [
        'case_id',
        'rejected_by_user_id',
        'reason_summary',
        'status',
        'rejected_at',
        'resubmitted_at',
        'resubmitted_by_user_id',
    ];

    protected $casts = [
        'rejected_at' => 'datetime',
        'resubmitted_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    public function resubmittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resubmitted_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CaseRejectionItem::class, 'case_rejection_id')->orderBy('sort_order')->orderBy('id');
    }

    public function openItems(): HasMany
    {
        return $this->items()->whereNull('resolved_at');
    }
}

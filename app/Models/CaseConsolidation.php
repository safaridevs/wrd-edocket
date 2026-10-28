<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseConsolidation extends Model
{
    protected $fillable = [
        'combined_case_id', 'ordered_by_user_id', 'order_date', 'reason', 'historical_access_policy',
    ];

    protected $casts = ['order_date' => 'date'];

    public function combinedCase(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'combined_case_id');
    }

    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by_user_id')->withTrashed();
    }

    public function sources(): HasMany
    {
        return $this->hasMany(CaseConsolidationSource::class);
    }
}

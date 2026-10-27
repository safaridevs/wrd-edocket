<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseConsolidationSource extends Model
{
    protected $fillable = ['case_consolidation_id', 'source_case_id'];

    public function consolidation(): BelongsTo
    {
        return $this->belongsTo(CaseConsolidation::class, 'case_consolidation_id');
    }

    public function sourceCase(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'source_case_id');
    }
}

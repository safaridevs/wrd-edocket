<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseAssignment extends Model
{
    protected $fillable = [
        'case_id', 'user_id', 'assignment_type', 'assigned_by'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
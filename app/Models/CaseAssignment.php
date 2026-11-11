<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseAssignment extends Model
{
    public const TYPE_ALU_PARALEGAL = 'alu_paralegal';
    public const TYPE_CONTRACT_PARALEGAL = 'contract_paralegal';
    public const PARALEGAL_TYPES = [
        self::TYPE_ALU_PARALEGAL,
        self::TYPE_CONTRACT_PARALEGAL,
    ];

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
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by')->withTrashed();
    }
}

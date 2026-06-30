<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStatusAudit extends Model
{
    public const TYPE_WORKFLOW = 'workflow';
    public const TYPE_HU_DISPLAY = 'hu_display';

    protected $fillable = [
        'case_id', 'status_type', 'from_status', 'to_status', 'changed_by', 'reason'
    ];

    public static function record(CaseModel $case, User $user, ?string $fromStatus, ?string $toStatus, ?string $reason = null, string $statusType = self::TYPE_WORKFLOW): self
    {
        return self::create([
            'case_id' => $case->id,
            'status_type' => $statusType,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by' => $user->id,
            'reason' => $reason,
        ]);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by')->withTrashed();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = ['case_id', 'user_id', 'action', 'meta_json'];

    protected $casts = ['meta_json' => 'array'];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(string $action, User $user, ?CaseModel $case = null, array $meta = []): void
    {
        self::create([
            'case_id' => $case?->id,
            'user_id' => $user->id,
            'action' => $action,
            'meta_json' => $meta
        ]);
    }
}
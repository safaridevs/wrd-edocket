<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseMedia extends Model
{
    use SoftDeletes;

    protected $table = 'case_media';

    protected $fillable = [
        'case_id',
        'title',
        'description',
        'media_type',
        'relative_path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'received_at',
        'attached_by_user_id',
        'removed_by_user_id',
        'removed_at',
    ];

    protected $casts = [
        'received_at' => 'date',
        'removed_at' => 'datetime',
        'size_bytes' => 'integer',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function attachedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attached_by_user_id')->withTrashed();
    }

    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by_user_id')->withTrashed();
    }
}

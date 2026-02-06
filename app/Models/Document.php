<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'case_id', 'doc_type', 'custom_title', 'original_filename', 'stored_filename', 'mime',
        'size_bytes', 'checksum', 'storage_uri', 'uploaded_by_user_id', 'uploaded_at',
        'stamped', 'stamp_text', 'stamped_at', 'approved', 'approved_by_user_id', 'approved_at', 'rejected_reason',
        'pleading_type'
    ];

    protected $casts = [
        'sync_status' => 'array',
        'stamped_at' => 'datetime',
        'uploaded_at' => 'datetime',
        'approved_at' => 'datetime',
        'is_stamped' => 'boolean',
        'stamped' => 'boolean',
        'approved' => 'boolean'
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function stamp(): void
    {
        $this->update([
            'is_stamped' => true,
            'stamped_at' => now()
        ]);
    }
}
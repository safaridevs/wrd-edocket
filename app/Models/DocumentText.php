<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentText extends Model
{
    protected $fillable = [
        'document_id',
        'content_text',
        'extraction_status',
        'extractor',
        'extraction_error',
        'indexed_at',
    ];

    protected $casts = [
        'indexed_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepoSync extends Model
{
    protected $fillable = [
        'document_id', 'destination', 'status', 'external_id', 
        'attempts', 'last_error', 'synced_at'
    ];

    protected $casts = ['synced_at' => 'datetime'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function markSuccess(string $externalId): void
    {
        $this->update([
            'status' => 'success',
            'external_id' => $externalId,
            'synced_at' => now(),
            'last_error' => null
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'attempts' => $this->attempts + 1,
            'last_error' => $error
        ]);
    }
}
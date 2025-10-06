<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseParty extends Model
{
    protected $fillable = [
        'case_id', 'person_id', 'role', 'service_enabled', 'attorney_id', 'representation'
    ];

    protected $casts = [
        'service_enabled' => 'boolean'
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function attorney(): BelongsTo
    {
        return $this->belongsTo(Attorney::class);
    }
}
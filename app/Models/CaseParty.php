<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseParty extends Model
{
    protected $fillable = [
        'case_id', 'name', 'type', 'email', 'contact_info', 'is_served'
    ];

    protected $casts = [
        'contact_info' => 'array',
        'is_served' => 'boolean'
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
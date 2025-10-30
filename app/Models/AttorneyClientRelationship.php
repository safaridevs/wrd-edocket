<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttorneyClientRelationship extends Model
{
    protected $fillable = [
        'attorney_id',
        'client_person_id', 
        'case_id',
        'status',
        'effective_date',
        'termination_date',
        'notes'
    ];

    protected $casts = [
        'effective_date' => 'date',
        'termination_date' => 'date',
    ];

    public function attorney(): BelongsTo
    {
        return $this->belongsTo(Attorney::class, 'attorney_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'client_person_id');
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseParty extends Model
{
    protected $fillable = [
        'case_id', 'person_id', 'role', 'service_enabled', 'client_party_id'
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(CaseParty::class, 'client_party_id');
    }
    
    public function clientParty(): BelongsTo
    {
        return $this->belongsTo(CaseParty::class, 'client_party_id');
    }

    public function attorneys()
    {
        return $this->hasMany(CaseParty::class, 'client_party_id')->where('role', 'counsel');
    }


}
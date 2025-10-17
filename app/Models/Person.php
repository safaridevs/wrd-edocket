<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $table = 'persons';
    
    protected $fillable = [
        'type', 'prefix', 'first_name', 'middle_name', 'last_name', 'suffix',
        'organization', 'title', 'email', 'phone_mobile', 'phone_office',
        'address_line1', 'address_line2', 'city', 'state', 'zip', 'notes'
    ];

    public function caseParties(): HasMany
    {
        return $this->hasMany(CaseParty::class);
    }

    public function serviceList(): HasMany
    {
        return $this->hasMany(ServiceList::class);
    }

    public function getFullNameAttribute(): string
    {
        if ($this->type === 'company') {
            return $this->organization ?: 'Unknown Organization';
        }
        
        $name = trim(implode(' ', array_filter([
            $this->prefix, $this->first_name, $this->middle_name, $this->last_name, $this->suffix
        ])));
        
        return $name ?: 'Unknown Person';
    }
}
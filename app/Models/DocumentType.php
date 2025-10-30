<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'allowed_roles',
        'is_required',
        'is_pleading',
        'allows_multiple',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'allowed_roles' => 'array',
        'is_required' => 'boolean',
        'is_pleading' => 'boolean',
        'allows_multiple' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function scopeForRole($query, $role)
    {
        return $query->where('is_active', true)
                    ->whereJsonContains('allowed_roles', $role);
    }

    public function scopeForCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    public function scopePleading($query)
    {
        return $query->where('is_pleading', true);
    }

    public function scopeForCaseCreation($query)
    {
        return $query->where('is_active', true)
                    ->where('category', 'case_creation')
                    ->whereJsonContains('allowed_roles', 'alu_clerk');
    }
}
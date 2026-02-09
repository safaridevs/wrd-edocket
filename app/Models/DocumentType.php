<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DocumentType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'category',
        'is_required',
        'is_pleading',
        'allows_multiple',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_pleading' => 'boolean',
        'allows_multiple' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function scopeForRole($query, $roleName)
    {
        return $query->where('is_active', true)
                    ->whereHas('roles', function($q) use ($roleName) {
                        $q->where('name', $roleName);
                    });
    }

    public function scopeForRoleGroup($query, $group)
    {
        return $query->where('is_active', true)
                    ->whereHas('roles', function($q) use ($group) {
                        $q->where('group', $group);
                    });
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
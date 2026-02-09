<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'group', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function documentTypes(): BelongsToMany
    {
        return $this->belongsToMany(DocumentType::class);
    }
}

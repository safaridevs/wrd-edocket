<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OseFileNumber extends Model
{
    protected $fillable = ['case_id', 'basin_code', 'file_no_from', 'file_no_to'];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }
}
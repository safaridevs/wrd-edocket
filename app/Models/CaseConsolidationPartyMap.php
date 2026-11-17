<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseConsolidationPartyMap extends Model
{
    protected $fillable = [
        'case_consolidation_id', 'source_case_party_id', 'combined_case_party_id',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attorney extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'bar_number',
        'address_line1',
        'address_line2',
        'city',
        'state',
        'zip',
    ];

    public function parties()
    {
        return $this->hasMany(Party::class);
    }
}
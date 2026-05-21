<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseParty extends Model
{
    public const CAPACITY_PRIVATE_COUNSEL = 'private_counsel';

    protected $fillable = [
        'case_id', 'person_id', 'role', 'service_enabled', 'client_party_id', 'representation_capacity'
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

    public function agents()
    {
        return $this->hasMany(CaseParty::class, 'client_party_id')->where('role', 'agent');
    }

    public function isWrdAgencyParty(): bool
    {
        $organization = strtoupper(trim((string) ($this->person->organization ?? '')));

        return $this->person?->type === 'company' && $organization === 'WATER RIGHTS DIVISION';
    }

    public function isPrivateCounsel(): bool
    {
        return $this->role === 'counsel'
            && ($this->representation_capacity === self::CAPACITY_PRIVATE_COUNSEL || $this->representation_capacity === null);
    }

    public static function privateCounselForEmail(CaseModel $case, ?string $email): ?self
    {
        $email = strtolower(trim((string) $email));

        if ($email === '') {
            return null;
        }

        return $case->parties()
            ->where('role', 'counsel')
            ->where(function ($query) {
                $query->where('representation_capacity', self::CAPACITY_PRIVATE_COUNSEL)
                    ->orWhereNull('representation_capacity');
            })
            ->whereHas('person', function ($query) use ($email) {
                $query->whereRaw('LOWER(email) = ?', [$email]);
            })
            ->first();
    }

    public static function wrdRepresentativeAssignmentForEmail(CaseModel $case, ?string $email): ?CaseAssignment
    {
        $email = strtolower(trim((string) $email));

        if ($email === '') {
            return null;
        }

        return $case->assignments()
            ->whereIn('assignment_type', ['alu_atty', 'alu_attorney'])
            ->whereHas('user', function ($query) use ($email) {
                $query->whereRaw('LOWER(email) = ?', [$email]);
            })
            ->first();
    }

}

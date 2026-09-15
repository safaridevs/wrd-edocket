<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CaseParty extends Model
{
    use SoftDeletes;

    public const CAPACITY_PRIVATE_COUNSEL = 'private_counsel';
    public const REPRESENTATIVE_ROLES = ['counsel', 'paralegal', 'agent'];

    protected $fillable = [
        'case_id', 'person_id', 'role', 'service_enabled', 'client_party_id', 'representation_capacity',
        'effective_at', 'terminated_at', 'terminated_by_user_id',
    ];

    protected $casts = [
        'service_enabled' => 'boolean',
        'effective_at' => 'datetime',
        'terminated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (CaseParty $party) {
            $party->effective_at ??= now();
        });
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function terminatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terminated_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull($this->getQualifiedDeletedAtColumn());
    }

    public function scopeHistorical($query)
    {
        return $query->withTrashed()->whereNotNull($this->getQualifiedDeletedAtColumn());
    }

    public function terminateParticipation(?int $userId = null): void
    {
        if ($this->trashed()) {
            return;
        }

        $this->forceFill([
            'terminated_at' => now(),
            'terminated_by_user_id' => $userId,
        ])->save();

        $this->delete();
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

    public function privateCounsel()
    {
        return $this->attorneys()
            ->where(function ($query) {
                $query->where('representation_capacity', self::CAPACITY_PRIVATE_COUNSEL)
                    ->orWhereNull('representation_capacity');
            });
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

    public function isDirectParty(): bool
    {
        return !in_array($this->role, self::REPRESENTATIVE_ROLES, true);
    }

    public function hasPrivateCounsel(): bool
    {
        return $this->privateCounsel()->exists();
    }

    public function removeFromServiceListWhileRepresented(): void
    {
        if (!$this->isDirectParty()) {
            return;
        }

        if ($this->service_enabled) {
            $this->forceFill(['service_enabled' => false])->save();
        }

        ServiceList::where('case_id', $this->case_id)
            ->where('person_id', $this->person_id)
            ->delete();
    }

    public function restoreServiceListIfUnrepresented(string $serviceMethod = 'email'): void
    {
        if (!$this->isDirectParty() || $this->hasPrivateCounsel()) {
            return;
        }

        if (!$this->service_enabled) {
            $this->forceFill(['service_enabled' => true])->save();
        }

        if (empty($this->person?->email)) {
            return;
        }

        ServiceList::firstOrCreate(
            [
                'case_id' => $this->case_id,
                'person_id' => $this->person_id,
            ],
            [
                'email' => $this->person->email,
                'service_method' => $serviceMethod,
                'is_primary' => true,
            ]
        );
    }

    public static function directPartiesForEmail(CaseModel $case, ?string $email)
    {
        $email = strtolower(trim((string) $email));

        if ($email === '') {
            return collect();
        }

        return $case->parties()
            ->whereNotIn('role', self::REPRESENTATIVE_ROLES)
            ->whereHas('person', function ($query) use ($email) {
                $query->whereRaw('LOWER(email) = ?', [$email]);
            })
            ->with('person')
            ->get();
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

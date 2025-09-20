<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'initials',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function createdCases(): HasMany
    {
        return $this->hasMany(CaseModel::class, 'created_by_user_id');
    }

    public function assignedCases(): HasMany
    {
        return $this->hasMany(CaseModel::class, 'updated_by_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by_user_id');
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    // Role checks
    public function isWRDExpert(): bool { return $this->role === 'wrd_expert'; }
    public function isWRAPDirector(): bool { return $this->role === 'wrap_director'; }
    public function isALUManagingAtty(): bool { return $this->role === 'alu_managing_atty'; }
    public function isALULawClerk(): bool { return $this->role === 'alu_law_clerk'; }
    public function isALUAttorney(): bool { return $this->role === 'alu_attorney'; }
    public function isHydrologyExpert(): bool { return $this->role === 'hydrology_expert'; }
    public function isHUAdmin(): bool { return $this->role === 'hu_admin'; }
    public function isHULawClerk(): bool { return $this->role === 'hu_law_clerk'; }
    public function isInterestedParty(): bool { return $this->role === 'interested_party'; }
    public function isSystemAdmin(): bool { return $this->role === 'system_admin'; }
    public function isHearingUnit(): bool { return in_array($this->role, ['hu_admin', 'hu_clerk']); }

    // Permission methods
    public function canCreateCase(): bool
    {
        return in_array($this->role, ['alu_clerk']);
    }

    public function canReadCase(): bool
    {
        return !in_array($this->role, []);
    }

    public function canWriteCase(): bool
    {
        return in_array($this->role, ['alu_clerk', 'alu_atty', 'hu_admin', 'hu_clerk']);
    }

    public function canAcceptFilings(): bool
    {
        return in_array($this->role, ['hu_admin', 'hu_clerk']);
    }

    public function canRejectFilings(): bool
    {
        return $this->role === 'hu_admin';
    }

    public function canApplyStamp(): bool
    {
        return in_array($this->role, ['hu_admin', 'hu_clerk']);
    }

    public function canFileToCase(): bool
    {
        return $this->role === 'party';
    }

    public function canManageUsers(): bool
    {
        return $this->role === 'admin';
    }

    public function canAssignExperts(): bool
    {
        return $this->role === 'wrap_dir';
    }

    public function canAssignAttorneys(): bool
    {
        return $this->role === 'alu_mgr';
    }

    public function canTransmitMaterials(): bool
    {
        return in_array($this->role, ['wrd', 'wrap_dir', 'alu_clerk']);
    }

    public function getPermissions(): array
    {
        return [
            'create_case' => $this->canCreateCase(),
            'read_case' => $this->canReadCase(),
            'write_case' => $this->canWriteCase(),
            'accept_filings' => $this->canAcceptFilings(),
            'reject_filings' => $this->canRejectFilings(),
            'apply_stamp' => $this->canApplyStamp(),
            'file_to_case' => $this->canFileToCase(),
            'manage_users' => $this->canManageUsers(),
            'assign_experts' => $this->canAssignExperts(),
            'assign_attorneys' => $this->canAssignAttorneys(),
            'transmit_materials' => $this->canTransmitMaterials(),
        ];
    }
}

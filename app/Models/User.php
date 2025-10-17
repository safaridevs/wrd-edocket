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
        return $this->getCurrentRole() === $role;
    }
    
    public function getCurrentRole(): string
    {
        return session('impersonated_role', $this->role);
    }

    // Role checks
    public function isWRDExpert(): bool { return $this->getCurrentRole() === 'wrd_expert'; }
    public function isWRAPDirector(): bool { return $this->getCurrentRole() === 'wrap_director'; }
    public function isALUManagingAtty(): bool { return $this->getCurrentRole() === 'alu_managing_atty'; }
    public function isALULawClerk(): bool { return $this->getCurrentRole() === 'alu_law_clerk'; }
    public function isALUAttorney(): bool { return $this->getCurrentRole() === 'alu_attorney'; }
    public function isHydrologyExpert(): bool { return $this->getCurrentRole() === 'hydrology_expert'; }
    public function isHUAdmin(): bool { return $this->getCurrentRole() === 'hu_admin'; }
    public function isHULawClerk(): bool { return $this->getCurrentRole() === 'hu_law_clerk'; }
    public function isInterestedParty(): bool { return $this->getCurrentRole() === 'interested_party'; }
    public function isSystemAdmin(): bool { return $this->getCurrentRole() === 'system_admin'; }
    public function isHearingUnit(): bool { return in_array($this->getCurrentRole(), ['hu_admin', 'hu_clerk']); }

    // Permission methods
    public function canCreateCase(): bool
    {
        return in_array($this->getCurrentRole(), ['alu_clerk']);
    }

    public function canReadCase(): bool
    {
        return !in_array($this->getCurrentRole(), []);
    }

    public function canWriteCase(): bool
    {
        return in_array($this->getCurrentRole(), ['alu_clerk', 'alu_atty', 'hu_admin', 'hu_clerk']);
    }

    public function canAcceptFilings(): bool
    {
        return in_array($this->getCurrentRole(), ['hu_admin', 'hu_clerk']);
    }

    public function canRejectFilings(): bool
    {
        return $this->getCurrentRole() === 'hu_admin';
    }

    public function canApplyStamp(): bool
    {
        return in_array($this->getCurrentRole(), ['hu_admin', 'hu_clerk']);
    }

    public function canFileToCase(): bool
    {
        return $this->getCurrentRole() === 'party';
    }

    public function canUploadDocuments(): bool
    {
        return in_array($this->getCurrentRole(), ['alu_clerk', 'party']) || $this->isHearingUnit();
    }

    public function canSubmitToHU(): bool
    {
        return in_array($this->getCurrentRole(), ['alu_clerk', 'party']);
    }

    public function canAccessCase(CaseModel $case): bool
    {
        if ($this->getCurrentRole() !== 'party') {
            return true;
        }
        
        // Check if user email matches any person or attorney in the case
        return $case->parties()->whereHas('person', function($query) {
            $query->where('email', $this->email);
        })->exists() || 
        $case->parties()->whereHas('attorney', function($query) {
            $query->where('email', $this->email);
        })->exists();
    }

    public function canManageUsers(): bool
    {
        return $this->getCurrentRole() === 'admin';
    }

    public function canAssignExperts(): bool
    {
        return $this->getCurrentRole() === 'wrap_dir';
    }

    public function canAssignAttorneys(): bool
    {
        return in_array($this->getCurrentRole(), ['alu_mgr', 'alu_clerk']);
    }

    public function canAssignHydrologyExperts(): bool
    {
        return $this->getCurrentRole() === 'alu_mgr';
    }

    public function canTransmitMaterials(): bool
    {
        return in_array($this->getCurrentRole(), ['wrd', 'wrap_dir', 'alu_clerk']);
    }

    public function canModifyPersons(): bool
    {
        return in_array($this->getCurrentRole(), ['hu_admin', 'hu_clerk']);
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

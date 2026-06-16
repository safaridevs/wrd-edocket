<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseModel extends Model
{
    protected $table = 'cases';

    public const ALU_DOCUMENT_UPLOADER_ROLES = [
        'alu_mgr',
        'alu_clerk',
        'alu_paralegal',
        'alu_atty',
        'alu_attorney',
        'alu_managing_atty',
        'alu_manager',
    ];

    public const HU_DISPLAY_STATUSES = [
        'stayed' => 'Stayed',
        'in_mediation' => 'In Mediation',
    ];

    protected $fillable = [
        'case_no', 'caption', 'case_type', 'status', 'reynolds_report_url',
        'created_by_user_id', 'updated_by_user_id', 'assigned_attorney_id', 'assigned_hydrology_expert_id', 'assigned_alu_clerk_id', 'assigned_wrd_id', 'metadata',
        'submitted_at', 'accepted_at', 'closed_at', 'archived_at', 'closed_by_user_id', 'archived_by_user_id', 'closure_reason',
        'hu_display_status', 'hu_display_status_note', 'hu_display_status_updated_by', 'hu_display_status_updated_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'closed_at' => 'datetime',
        'archived_at' => 'datetime',
        'hu_display_status_updated_at' => 'datetime',
        'metadata' => 'array'
    ];

    // Handle JSON for SQL Server compatibility
    public function getMetadataAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    public function setMetadataAttribute($value)
    {
        $this->attributes['metadata'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getWrdOfficeAttribute(): ?string
    {
        return $this->metadata['wrd_office'] ?? null;
    }

    public function getWrdOfficeLabelAttribute(): ?string
    {
        return match ($this->wrd_office) {
            'albuquerque' => 'Albuquerque Office',
            'santa_fe' => 'Santa Fe Office',
            default => null,
        };
    }

    public function getWrdOfficeDetailsAttribute(): array
    {
        return match ($this->wrd_office) {
            'albuquerque' => [
                'address' => '5550 San Antonio Dr NE',
                'city' => 'Albuquerque',
                'state' => 'NM',
                'zip' => '87109',
                'phone' => '(505) 469-9662',
            ],
            'santa_fe' => [
                'address' => '407 Galisteo St STE 102',
                'city' => 'Santa Fe',
                'state' => 'NM',
                'zip' => '87501',
                'phone' => '(505) 827-6120',
            ],
            default => [],
        };
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function assignedAttorney(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_attorney_id');
    }

    public function assignedHydrologyExpert(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_hydrology_expert_id');
    }

    public function assignedAluClerk(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_alu_clerk_id');
    }

    public function assignedWrd(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_wrd_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by_user_id');
    }

    public function huDisplayStatusUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hu_display_status_updated_by');
    }

    // New many-to-many relationships
    public function hydrologyExperts()
    {
        return $this->belongsToMany(User::class, 'case_assignments', 'case_id', 'user_id')
            ->wherePivot('assignment_type', 'hydrology_expert')
            ->withPivot('assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function wrds()
    {
        return $this->belongsToMany(User::class, 'case_assignments', 'case_id', 'user_id')
            ->wherePivot('assignment_type', 'wrd')
            ->withPivot('assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function aluClerks()
    {
        return $this->belongsToMany(User::class, 'case_assignments', 'case_id', 'user_id')
            ->wherePivot('assignment_type', 'alu_clerk')
            ->withPivot('assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function aluAttorneys()
    {
        return $this->belongsToMany(User::class, 'case_assignments', 'case_id', 'user_id')
            ->wherePivot('assignment_type', 'alu_atty')
            ->withPivot('assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function aluParalegals()
    {
        return $this->belongsToMany(User::class, 'case_assignments', 'case_id', 'user_id')
            ->wherePivot('assignment_type', 'alu_paralegal')
            ->withPivot('assigned_at', 'assigned_by')
            ->withTimestamps();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CaseAssignment::class, 'case_id');
    }

    public function getAttorneysForParty($personId)
    {
        return $this->parties()
            ->where('person_id', $personId)
            ->whereIn('role', ['counsel'])
            ->get();
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'case_id');
    }

    public function pendingAluDocumentsForAcceptance(): HasMany
    {
        return $this->documents()
            ->where(function (Builder $query) {
                $query->where('approved', false)
                    ->orWhereNull('approved');
            })
            ->whereHas('uploader', function (Builder $query) {
                $query->whereIn('role', self::ALU_DOCUMENT_UPLOADER_ROLES)
                    ->orWhereHas('roleRelation', function (Builder $roleQuery) {
                        $roleQuery->where('group', 'alu')
                            ->orWhereIn('name', self::ALU_DOCUMENT_UPLOADER_ROLES);
                    });
            });
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'case_id');
    }

    public function parties(): HasMany
    {
        return $this->hasMany(CaseParty::class, 'case_id');
    }

    public function serviceList(): HasMany
    {
        return $this->hasMany(ServiceList::class, 'case_id');
    }

    public function oseFileNumbers(): HasMany
    {
        return $this->hasMany(OseFileNumber::class, 'case_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'case_id');
    }

    public function statusAudits(): HasMany
    {
        return $this->hasMany(CaseStatusAudit::class, 'case_id');
    }

    public static function huDisplayStatuses(): array
    {
        return self::HU_DISPLAY_STATUSES;
    }

    public function getHuDisplayStatusLabelAttribute(): ?string
    {
        if (!$this->hu_display_status) {
            return null;
        }

        return self::HU_DISPLAY_STATUSES[$this->hu_display_status]
            ?? ucwords(str_replace('_', ' ', $this->hu_display_status));
    }

    public function getWorkflowStatusLabelAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function getVisibleStatusLabelAttribute(): string
    {
        return $this->hu_display_status_label ?? $this->workflow_status_label;
    }

    public function getVisibleStatusBadgeClassAttribute(): string
    {
        return match ($this->hu_display_status) {
            'stayed' => 'bg-orange-100 text-orange-800',
            'in_mediation' => 'bg-purple-100 text-purple-800',
            default => match ($this->status) {
                'active' => 'bg-green-100 text-green-800',
                'draft' => 'bg-gray-100 text-gray-800',
                'rejected' => 'bg-red-100 text-red-800',
                'submitted_to_hu' => 'bg-yellow-100 text-yellow-800',
                default => 'bg-blue-100 text-blue-800',
            },
        };
    }

    public function rejections(): HasMany
    {
        return $this->hasMany(CaseRejection::class, 'case_id')->latest('rejected_at')->latest('id');
    }

    public function openRejection(): HasMany
    {
        return $this->rejections()->where('status', 'open');
    }

    public function changeStatus(string $newStatus, User $user, ?string $reason = null): bool
    {
        $validTransitions = [
            'draft' => ['submitted_to_hu'],
            'submitted_to_hu' => ['active', 'rejected'],
            'rejected' => ['submitted_to_hu'],
            'active' => ['closed'],
            'closed' => ['archived'],
            'archived' => []
        ];

        if (!in_array($newStatus, $validTransitions[$this->status] ?? [])) {
            return false;
        }

        $oldStatus = $this->status;

        // Update timestamps
        $timestampField = match($newStatus) {
            'submitted_to_hu' => 'submitted_at',
            'active' => 'accepted_at',
            'closed' => 'closed_at',
            default => null
        };

        $updates = ['status' => $newStatus];
        if ($timestampField) {
            $updates[$timestampField] = now();
        }

        $this->update($updates);

        // Create audit entry
        CaseStatusAudit::record($this, $user, $oldStatus, $newStatus, $reason);

        return true;
    }

    public function canUserUploadDocuments($user): bool
    {
        $currentRole = method_exists($user, 'getCurrentRole') ? $user->getCurrentRole() : $user->role;

        // ALU and HU staff can always upload
        if (in_array($currentRole, ['alu_clerk', 'alu_paralegal', 'alu_mgr', 'hu_admin', 'hu_clerk'])) {
            return true;
        }

        // Parties, attorneys, and paralegals can upload if case allows it
        if (in_array($currentRole, ['party', 'external_attorney'], true) && $this->status === 'active') {
            // Check if user is a party, counsel, or paralegal on this case
            $isPartyMember = $this->parties()->whereHas('person', function($query) use ($user) {
                $query->where('email', $user->email);
            })->exists();

            if ($isPartyMember) return true;

            return $this->assignments()
                ->whereIn('assignment_type', ['alu_atty', 'alu_attorney'])
                ->where('user_id', $user->id)
                ->exists();
        }

        // Attorneys can upload for their clients
        if ($this->status === 'active') {
            return $this->parties()
                ->whereIn('role', ['counsel'])
                ->whereHas('person', function($query) use ($user) {
                    $query->where('email', $user->email);
                })
                ->exists();
        }

        return false;
    }

    public static function generateCaseNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return sprintf('%s-%03d', $year, $count);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseModel extends Model
{
    protected $table = 'cases';
    
    protected $fillable = [
        'case_no', 'caption', 'case_type', 'status', 'reynolds_report_url',
        'created_by_user_id', 'updated_by_user_id', 'assigned_attorney_id', 'assigned_hydrology_expert_id', 'assigned_alu_clerk_id', 'assigned_wrd_id', 'metadata',
        'submitted_at', 'accepted_at', 'closed_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'closed_at' => 'datetime',
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
            ->wherePivot('assignment_type', 'alu_attorney')
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
        CaseStatusAudit::create([
            'case_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'changed_by' => $user->id,
            'reason' => $reason
        ]);

        return true;
    }

    public function canUserUploadDocuments($user): bool
    {
        // ALU and HU staff can always upload
        if (in_array($user->role, ['alu_clerk', 'alu_managing_attorney', 'hu_admin', 'hu_clerk'])) {
            return true;
        }

        // Parties can upload if case allows it
        if ($user->role === 'party' && in_array($this->status, ['active', 'approved'])) {
            $isParty = $this->parties()->whereHas('person', function($query) use ($user) {
                $query->where('email', $user->email);
            })->exists();
            
            if ($isParty) return true;
        }

        // Attorneys can upload for their clients
        if (in_array($this->status, ['active', 'approved'])) {
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
        return sprintf('WR-%s-%04d', $year, $count);
    }
}
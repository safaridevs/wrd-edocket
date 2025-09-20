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
        'created_by_user_id', 'updated_by_user_id', 'metadata',
        'submitted_at', 'accepted_at', 'closed_at'
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'accepted_at' => 'datetime',
        'closed_at' => 'datetime',
        'metadata' => 'array'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
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
            'submitted_to_hu' => ['active', 'draft'],
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

    public static function generateCaseNumber(): string
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;
        return sprintf('WR-%s-%04d', $year, $count);
    }
}
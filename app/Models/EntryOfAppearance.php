<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntryOfAppearance extends Model
{
    public const STATUS_UNVERIFIED = 'unverified';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'case_id', 'represented_case_party_id', 'status', 'submitted_case_number',
        'submitted_party_name', 'attorney_prefix', 'attorney_first_name',
        'attorney_middle_name', 'attorney_last_name', 'attorney_suffix', 'law_firm',
        'email', 'phone', 'address_line1', 'address_line2', 'city', 'state', 'zip',
        'effective_date', 'notes', 'email_verified_at', 'verification_expires_at',
        'submission_ip', 'submission_user_agent', 'reviewed_by_user_id', 'reviewed_at',
        'rejection_reason', 'person_id', 'counsel_case_party_id',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'email_verified_at' => 'datetime',
        'verification_expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function representedParty(): BelongsTo
    {
        return $this->belongsTo(CaseParty::class, 'represented_case_party_id')->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id')->withTrashed();
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function counselCaseParty(): BelongsTo
    {
        return $this->belongsTo(CaseParty::class, 'counsel_case_party_id')->withTrashed();
    }

    public function getAttorneyNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->attorney_prefix,
            $this->attorney_first_name,
            $this->attorney_middle_name,
            $this->attorney_last_name,
            $this->attorney_suffix,
        ])));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnderwritingDecision extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_id',
        'eligibility_assessment_id',
        'customer_id',
        'institution_id',
        'loan_product_id',
        'decision_number',
        'decision_status',
        'reviewed_by',
        'approved_by',
        'reviewed_at',
        'approved_at',
        'declined_at',
        'requested_amount',
        'requested_tenure_months',
        'approved_amount',
        'approved_tenure_months',
        'approved_interest_rate',
        'approved_interest_method',
        'final_decision',
        'decision_reason',
        'reviewer_notes',
        'approver_notes',
        'attached_conditions',
        'waived_conditions',
        'requires_override',
        'override_requested',
        'override_approved',
        'override_justification',
        'override_policy_breaches',
        'override_requested_by',
        'override_approved_by',
        'override_requested_at',
        'override_approved_at',
        'override_declined_at',
        'override_decline_reason',
        'manual_risk_grade',
        'risk_grade_justification',
        'maker_checker_required',
        'maker_id',
        'checker_id',
        'maker_submitted_at',
        'checker_reviewed_at',
        'final_monthly_installment',
        'final_total_interest',
        'final_total_repayment',
        'final_dti_ratio',
        'final_dsr_ratio',
        'final_ltv_ratio',
        'workflow_stage',
        'approval_level',
        'approval_history',
        'is_high_value',
        'is_exception_case',
        'is_expedited',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'requested_tenure_months' => 'integer',
        'approved_amount' => 'decimal:2',
        'approved_tenure_months' => 'integer',
        'approved_interest_rate' => 'decimal:2',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'declined_at' => 'datetime',
        'attached_conditions' => 'array',
        'waived_conditions' => 'array',
        'requires_override' => 'boolean',
        'override_requested' => 'boolean',
        'override_approved' => 'boolean',
        'override_policy_breaches' => 'array',
        'override_requested_at' => 'datetime',
        'override_approved_at' => 'datetime',
        'override_declined_at' => 'datetime',
        'maker_checker_required' => 'boolean',
        'maker_submitted_at' => 'datetime',
        'checker_reviewed_at' => 'datetime',
        'final_monthly_installment' => 'decimal:2',
        'final_total_interest' => 'decimal:2',
        'final_total_repayment' => 'decimal:2',
        'final_dti_ratio' => 'decimal:2',
        'final_dsr_ratio' => 'decimal:2',
        'final_ltv_ratio' => 'decimal:2',
        'approval_level' => 'integer',
        'approval_history' => 'array',
        'is_high_value' => 'boolean',
        'is_exception_case' => 'boolean',
        'is_expedited' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($decision) {
            if (empty($decision->decision_number)) {
                $count = UnderwritingDecision::where('institution_id', $decision->institution_id)->count();
                $nextNumber = $count + 1;
                $decision->decision_number = 'DEC-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Relationships
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function eligibilityAssessment(): BelongsTo
    {
        return $this->belongsTo(EligibilityAssessment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function overrideRequester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_requested_by');
    }

    public function overrideApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'override_approved_by');
    }

    public function maker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_id');
    }

    /**
     * Scopes
     */
    public function scopeForApplication($query, int $applicationId)
    {
        return $query->where('application_id', $applicationId);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('decision_status', $status);
    }

    public function scopePendingReview($query)
    {
        return $query->where('decision_status', 'pending_review');
    }

    public function scopeUnderReview($query)
    {
        return $query->where('decision_status', 'under_review');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('decision_status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('decision_status', 'approved');
    }

    public function scopeDeclined($query)
    {
        return $query->where('decision_status', 'declined');
    }

    public function scopeRequiringOverride($query)
    {
        return $query->where('requires_override', true);
    }

    public function scopeOverrideRequested($query)
    {
        return $query->where('override_requested', true)
            ->whereNull('override_approved_at')
            ->whereNull('override_declined_at');
    }

    public function scopeHighValue($query)
    {
        return $query->where('is_high_value', true);
    }

    public function scopeExpedited($query)
    {
        return $query->where('is_expedited', true);
    }

    public function scopeForReviewer($query, int $userId)
    {
        return $query->where('reviewed_by', $userId);
    }

    /**
     * Status Check Methods
     */
    public function isDraft(): bool
    {
        return $this->decision_status === 'draft';
    }

    public function isPendingReview(): bool
    {
        return $this->decision_status === 'pending_review';
    }

    public function isUnderReview(): bool
    {
        return $this->decision_status === 'under_review';
    }

    public function isPendingApproval(): bool
    {
        return $this->decision_status === 'pending_approval';
    }

    public function isApproved(): bool
    {
        return $this->decision_status === 'approved';
    }

    public function isDeclined(): bool
    {
        return $this->decision_status === 'declined';
    }

    public function isCancelled(): bool
    {
        return $this->decision_status === 'cancelled';
    }

    /**
     * Workflow Transition Methods
     */
    public function submitForReview(int $makerId): void
    {
        $this->update([
            'decision_status' => 'pending_review',
            'maker_id' => $makerId,
            'maker_submitted_at' => now(),
        ]);

        $this->logApproval('submitted', $makerId, 'Submitted for credit officer review');
    }

    public function startReview(int $reviewerId): void
    {
        $this->update([
            'decision_status' => 'under_review',
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
            'workflow_stage' => 'credit_officer',
        ]);

        $this->logApproval('review_started', $reviewerId, 'Credit officer started review');
    }

    public function submitForApproval(int $reviewerId, string $notes = null): void
    {
        $this->update([
            'decision_status' => 'pending_approval',
            'reviewed_by' => $reviewerId,
            'reviewer_notes' => $notes,
            'workflow_stage' => 'supervisor',
            'approval_level' => 1,
        ]);

        $this->logApproval('forwarded_for_approval', $reviewerId, 'Forwarded to supervisor for approval');
    }

    public function approve(int $approverId, array $approvalData): void
    {
        $updateData = [
            'decision_status' => 'approved',
            'final_decision' => 'approved',
            'approved_by' => $approverId,
            'approved_at' => now(),
            'approver_notes' => $approvalData['notes'] ?? null,
            'approval_level' => ($this->approval_level ?? 0) + 1,
        ];

        // Set approved amounts if provided
        if (isset($approvalData['approved_amount'])) {
            $updateData['approved_amount'] = $approvalData['approved_amount'];
        }
        if (isset($approvalData['approved_tenure_months'])) {
            $updateData['approved_tenure_months'] = $approvalData['approved_tenure_months'];
        }
        if (isset($approvalData['approved_interest_rate'])) {
            $updateData['approved_interest_rate'] = $approvalData['approved_interest_rate'];
        }
        if (isset($approvalData['approved_interest_method'])) {
            $updateData['approved_interest_method'] = $approvalData['approved_interest_method'];
        }

        $this->update($updateData);

        $this->logApproval('approved', $approverId, 'Decision approved');

        // Update application status
        $this->application->update([
            'status' => \App\Enums\ApplicationStatus::APPROVED,
            'approved_at' => now(),
            'approved_by' => $approverId,
        ]);
    }

    public function decline(int $approverId, string $reason): void
    {
        $this->update([
            'decision_status' => 'declined',
            'final_decision' => 'declined',
            'approved_by' => $approverId,
            'declined_at' => now(),
            'decision_reason' => $reason,
            'approval_level' => ($this->approval_level ?? 0) + 1,
        ]);

        $this->logApproval('declined', $approverId, "Decision declined: {$reason}");

        // Update application status
        $this->application->update([
            'status' => \App\Enums\ApplicationStatus::REJECTED,
            'rejected_at' => now(),
        ]);
    }

    /**
     * Override Management Methods
     */
    public function requestOverride(int $userId, string $justification, array $breaches): void
    {
        $this->update([
            'override_requested' => true,
            'override_requested_by' => $userId,
            'override_requested_at' => now(),
            'override_justification' => $justification,
            'override_policy_breaches' => $breaches,
        ]);

        $this->logApproval('override_requested', $userId, "Override requested: {$justification}");
    }

    public function approveOverride(int $supervisorId, string $notes = null): void
    {
        $this->update([
            'override_approved' => true,
            'override_approved_by' => $supervisorId,
            'override_approved_at' => now(),
            'requires_override' => false,
        ]);

        $this->logApproval('override_approved', $supervisorId, "Override approved: {$notes}");
    }

    public function declineOverride(int $supervisorId, string $reason): void
    {
        $this->update([
            'override_declined_at' => now(),
            'override_decline_reason' => $reason,
        ]);

        $this->logApproval('override_declined', $supervisorId, "Override declined: {$reason}");
    }

    /**
     * Check if decision has overrides
     */
    public function hasOverride(): bool
    {
        return $this->override_approved === true;
    }

    public function hasConditions(): bool
    {
        return !empty($this->attached_conditions);
    }

    /**
     * Get condition count
     */
    public function getConditionCountAttribute(): int
    {
        return count($this->attached_conditions ?? []);
    }

    /**
     * Check if approved amount differs from requested
     */
    public function hasAmountVariance(): bool
    {
        return $this->approved_amount && $this->approved_amount != $this->requested_amount;
    }

    /**
     * Get variance details
     */
    public function getVariancePercentageAttribute(): ?float
    {
        if (!$this->approved_amount || $this->requested_amount == 0) {
            return null;
        }

        return round((($this->approved_amount - $this->requested_amount) / $this->requested_amount) * 100, 2);
    }

    /**
     * Log approval action to history
     */
    private function logApproval(string $action, int $userId, string $notes): void
    {
        $history = $this->approval_history ?? [];
        
        $history[] = [
            'action' => $action,
            'user_id' => $userId,
            'notes' => $notes,
            'timestamp' => now()->toISOString(),
        ];

        $this->update(['approval_history' => $history]);
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->decision_status) {
            'draft' => 'gray',
            'pending_review' => 'blue',
            'under_review' => 'yellow',
            'pending_approval' => 'orange',
            'approved' => 'green',
            'declined' => 'red',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }

    /**
     * Check if user can review this decision
     */
    public function canBeReviewedBy(User $user): bool
    {
        // Check if pending review
        if (!$this->isPendingReview()) {
            return false;
        }

        // Check if user has credit officer role
        return $user->hasRole('credit-officer') || $user->hasRole('institution-admin');
    }

    /**
     * Check if user can approve this decision
     */
    public function canBeApprovedBy(User $user): bool
    {
        // Check if pending approval
        if (!$this->isPendingApproval()) {
            return false;
        }

        // Check if user has supervisor role
        return $user->hasRole('supervisor') || $user->hasRole('institution-admin');
    }
}

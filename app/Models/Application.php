<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'institution_id',
        'loan_product_id',
        'created_by',
        'application_number',
        'status',
        'requested_amount',
        'requested_tenure_months',
        'property_type',
        'property_value',
        'property_address',
        'submitted_at',
        'reviewed_at',
        'approved_at',
        'rejected_at',
        'disbursed_at',
        'reviewed_by',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'status' => ApplicationStatus::class,
        'requested_amount' => 'decimal:2',
        'requested_tenure_months' => 'integer',
        'property_value' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'disbursed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->application_number)) {
                $institution = Institution::find($application->institution_id);
                $prefix = 'APP';
                
                $count = Application::where('institution_id', $application->institution_id)->count();
                $nextNumber = $count + 1;
                
                $application->application_number = $prefix . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the customer that owns the application.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the institution that owns the application.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the loan product for the application.
     */
    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }

    /**
     * Get the user who created the application.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who reviewed the application.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the user who approved the application.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the bank statement imports for the application.
     */
    public function bankStatementImports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class);
    }

    /**
     * Get the statement analytics for the application.
     */
    public function statementAnalytics(): HasMany
    {
        return $this->hasMany(StatementAnalytics::class);
    }

    /**
     * Get the eligibility assessments for the application.
     */
    public function eligibilityAssessments(): HasMany
    {
        return $this->hasMany(EligibilityAssessment::class);
    }

    /**
     * Get the underwriting decisions for the application.
     */
    public function underwritingDecisions(): HasMany
    {
        return $this->hasMany(UnderwritingDecision::class);
    }

    /**
     * Get the latest underwriting decision.
     */
    public function latestUnderwritingDecision()
    {
        return $this->hasOne(UnderwritingDecision::class)->latestOfMany();
    }

    /**
     * Check if application has an underwriting decision.
     */
    public function hasUnderwritingDecision(): bool
    {
        return $this->underwritingDecisions()->exists();
    }

    /**
     * Check if application has a pending underwriting decision.
     */
    public function hasPendingUnderwritingDecision(): bool
    {
        return $this->underwritingDecisions()
            ->whereIn('decision_status', ['pending_review', 'under_review', 'pending_approval'])
            ->exists();
    }

    /**
     * Get the loans for the application.
     */
    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the latest loan.
     */
    public function latestLoan()
    {
        return $this->hasOne(Loan::class)->latestOfMany();
    }

    /**
     * Check if application has a loan.
     */
    public function hasLoan(): bool
    {
        return $this->loans()->exists();
    }

    /**
     * Check if application is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === ApplicationStatus::DRAFT;
    }

    /**
     * Check if application is submitted.
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, [
            ApplicationStatus::SUBMITTED,
            ApplicationStatus::UNDER_REVIEW,
        ]);
    }

    /**
     * Check if application is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === ApplicationStatus::APPROVED;
    }

    /**
     * Check if application is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === ApplicationStatus::REJECTED;
    }

    /**
     * Submit application.
     */
    public function submit(): void
    {
        $this->update([
            'status' => ApplicationStatus::SUBMITTED,
            'submitted_at' => now(),
        ]);
    }

    /**
     * Mark application as under review.
     */
    public function markAsUnderReview(int $reviewerId): void
    {
        $this->update([
            'status' => ApplicationStatus::UNDER_REVIEW,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Approve application.
     */
    public function approve(int $approverId): void
    {
        $this->update([
            'status' => ApplicationStatus::APPROVED,
            'approved_by' => $approverId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject application.
     */
    public function reject(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => ApplicationStatus::REJECTED,
            'reviewed_by' => $reviewerId,
            'rejected_at' => now(),
            'notes' => $notes,
        ]);
    }

    /**
     * Calculate LTV ratio if property value provided.
     */
    public function getLtvRatioAttribute(): ?float
    {
        if (!$this->property_value || $this->property_value == 0) {
            return null;
        }

        return ($this->requested_amount / $this->property_value) * 100;
    }

    /**
     * Scope to filter by customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to filter by institution.
     */
    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, ApplicationStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get draft applications.
     */
    public function scopeDrafts($query)
    {
        return $query->where('status', ApplicationStatus::DRAFT);
    }

    /**
     * Scope to get submitted applications.
     */
    public function scopeSubmitted($query)
    {
        return $query->whereIn('status', [
            ApplicationStatus::SUBMITTED,
            ApplicationStatus::UNDER_REVIEW,
        ]);
    }

    /**
     * Scope to get approved applications.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', ApplicationStatus::APPROVED);
    }
}

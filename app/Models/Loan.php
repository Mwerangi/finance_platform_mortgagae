<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Loan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'application_id',
        'customer_id',
        'institution_id',
        'loan_product_id',
        'underwriting_decision_id',
        'loan_account_number',
        'external_reference_number',
        'status',
        'approved_amount',
        'approved_tenure_months',
        'approved_interest_rate',
        'interest_method',
        'monthly_installment',
        'total_interest',
        'total_repayment',
        'disbursed_amount',
        'disbursement_date',
        'disbursement_method',
        'disbursement_reference',
        'disbursement_notes',
        'disbursed_by',
        'disbursement_approved_at',
        'disbursement_approved_by',
        'activation_date',
        'first_installment_date',
        'maturity_date',
        'closure_date',
        'closed_at',
        'principal_outstanding',
        'interest_outstanding',
        'total_outstanding',
        'penalties_outstanding',
        'fees_outstanding',
        'total_paid',
        'principal_paid',
        'interest_paid',
        'penalties_paid',
        'fees_paid',
        'installments_paid',
        'installments_remaining',
        'days_past_due',
        'arrears_amount',
        'last_payment_date',
        'last_payment_amount',
        'next_payment_due_date',
        'next_payment_amount',
        'aging_bucket',
        'property_type',
        'property_value',
        'property_address',
        'property_title_number',
        'ltv_ratio',
        'collateral_description',
        'collateral_documents',
        'insurance_required',
        'insurance_provider',
        'insurance_policy_number',
        'insurance_premium',
        'insurance_expiry_date',
        'allows_early_settlement',
        'early_settlement_penalty_rate',
        'early_settlement_date',
        'early_settlement_amount',
        'is_restructured',
        'original_loan_id',
        'restructured_date',
        'restructure_reason',
        'written_off_date',
        'written_off_amount',
        'writeoff_reason',
        'written_off_by',
        'risk_classification',
        'provision_amount',
        'provision_rate',
        'notes',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'approved_amount' => 'decimal:2',
        'approved_interest_rate' => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'total_interest' => 'decimal:2',
        'total_repayment' => 'decimal:2',
        'disbursed_amount' => 'decimal:2',
        'disbursement_date' => 'date',
        'disbursement_approved_at' => 'datetime',
        'activation_date' => 'date',
        'first_installment_date' => 'date',
        'maturity_date' => 'date',
        'closure_date' => 'date',
        'closed_at' => 'datetime',
        'principal_outstanding' => 'decimal:2',
        'interest_outstanding' => 'decimal:2',
        'total_outstanding' => 'decimal:2',
        'penalties_outstanding' => 'decimal:2',
        'fees_outstanding' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'interest_paid' => 'decimal:2',
        'penalties_paid' => 'decimal:2',
        'fees_paid' => 'decimal:2',
        'last_payment_date' => 'date',
        'last_payment_amount' => 'decimal:2',
        'next_payment_due_date' => 'date',
        'next_payment_amount' => 'decimal:2',
        'property_value' => 'decimal:2',
        'ltv_ratio' => 'decimal:2',
        'collateral_documents' => 'array',
        'insurance_premium' => 'decimal:2',
        'insurance_expiry_date' => 'date',
        'early_settlement_penalty_rate' => 'decimal:2',
        'early_settlement_date' => 'date',
        'early_settlement_amount' => 'decimal:2',
        'restructured_date' => 'date',
        'written_off_date' => 'date',
        'written_off_amount' => 'decimal:2',
        'provision_amount' => 'decimal:2',
        'provision_rate' => 'decimal:2',
        'metadata' => 'array',
        'insurance_required' => 'boolean',
        'allows_early_settlement' => 'boolean',
        'is_restructured' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($loan) {
            if (empty($loan->loan_account_number)) {
                $count = Loan::where('institution_id', $loan->institution_id)->count();
                $nextNumber = $count + 1;
                $loan->loan_account_number = 'LOAN-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
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

    public function underwritingDecision(): BelongsTo
    {
        return $this->belongsTo(UnderwritingDecision::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(LoanSchedule::class)->orderBy('installment_number');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(Repayment::class)->orderBy('payment_date', 'desc');
    }

    public function collectionsQueue(): HasMany
    {
        return $this->hasMany(CollectionsQueue::class);
    }

    public function collectionsActions(): HasMany
    {
        return $this->hasMany(CollectionsAction::class)->orderBy('action_date', 'desc');
    }

    public function promisesToPay(): HasMany
    {
        return $this->hasMany(PromiseToPay::class)->orderBy('commitment_date', 'desc');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disburser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by');
    }

    public function disbursementApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursement_approved_by');
    }

    public function originalLoan(): BelongsTo
    {
        return $this->belongsTo(Loan::class, 'original_loan_id');
    }

    public function restructuredLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'original_loan_id');
    }

    // ========================================
    // QUERY SCOPES
    // ========================================

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
        return $query->where('status', $status);
    }

    public function scopePendingDisbursement($query)
    {
        return $query->where('status', 'pending_disbursement');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFullyPaid($query)
    {
        return $query->where('status', 'fully_paid');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeDefaulted($query)
    {
        return $query->where('status', 'defaulted');
    }

    public function scopeWrittenOff($query)
    {
        return $query->where('status', 'written_off');
    }

    public function scopeRestructured($query)
    {
        return $query->where('is_restructured', true);
    }

    public function scopePerforming($query)
    {
        return $query->where('status', 'active')
            ->where('aging_bucket', 'current');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'active')
            ->where('days_past_due', '>', 0);
    }

    public function scopeNPL($query)
    {
        return $query->where('status', 'active')
            ->where('aging_bucket', 'npl');
    }

    public function scopePAR30($query)
    {
        return $query->where('status', 'active')
            ->where('days_past_due', '>=', 30);
    }

    public function scopePAR60($query)
    {
        return $query->where('status', 'active')
            ->where('days_past_due', '>=', 60);
    }

    public function scopePAR90($query)
    {
        return $query->where('status', 'active')
            ->where('days_past_due', '>=', 90);
    }

    public function scopeInAgingBucket($query, string $bucket)
    {
        return $query->where('aging_bucket', $bucket);
    }

    public function scopeDisbursedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('disbursement_date', [$startDate, $endDate]);
    }

    public function scopeMaturityBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('maturity_date', [$startDate, $endDate]);
    }

    // ========================================
    // STATUS CHECK METHODS
    // ========================================

    public function isPendingDisbursement(): bool
    {
        return $this->status === 'pending_disbursement';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFullyPaid(): bool
    {
        return $this->status === 'fully_paid';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isDefaulted(): bool
    {
        return $this->status === 'defaulted';
    }

    public function isWrittenOff(): bool
    {
        return $this->status === 'written_off';
    }

    public function isRestructured(): bool
    {
        return $this->is_restructured === true;
    }

    public function isOverdue(): bool
    {
        return $this->days_past_due > 0;
    }

    public function isNPL(): bool
    {
        return $this->days_past_due >= 90;
    }

    public function isPerforming(): bool
    {
        return $this->isActive() && $this->days_past_due === 0;
    }

    public function hasActivePTP(): bool
    {
        return $this->promisesToPay()
            ->where('status', 'open')
            ->where('commitment_date', '>=', now()->startOfDay())
            ->exists();
    }

    // ========================================
    // LIFECYCLE METHODS
    // ========================================

    /**
     * Disburse the loan
     */
    public function disburse(array $disbursementData): void
    {
        $this->update([
            'disbursed_amount' => $disbursementData['disbursed_amount'],
            'disbursement_date' => $disbursementData['disbursement_date'],
            'disbursement_method' => $disbursementData['disbursement_method'],
            'disbursement_reference' => $disbursementData['disbursement_reference'] ?? null,
            'disbursement_notes' => $disbursementData['disbursement_notes'] ?? null,
            'disbursed_by' => $disbursementData['disbursed_by'],
            'disbursement_approved_at' => now(),
            'disbursement_approved_by' => $disbursementData['approved_by'] ?? $disbursementData['disbursed_by'],
        ]);
    }

    /**
     * Activate the loan
     */
    public function activate(array $activationData): void
    {
        $this->update([
            'status' => 'active',
            'activation_date' => $activationData['activation_date'],
            'first_installment_date' => $activationData['first_installment_date'],
            'maturity_date' => $activationData['maturity_date'],
            'principal_outstanding' => $this->approved_amount,
            'interest_outstanding' => $this->total_interest,
            'total_outstanding' => $this->total_repayment,
            'installments_remaining' => $this->approved_tenure_months,
        ]);
    }

    /**
     * Close the loan
     */
    public function close(string $reason = null): void
    {
        $this->update([
            'status' => 'closed',
            'closure_date' => now(),
            'closed_at' => now(),
            'notes' => $reason ? $this->notes . "\n\nClosure: " . $reason : $this->notes,
        ]);
    }

    /**
     * Mark as fully paid
     */
    public function markAsFullyPaid(): void
    {
        $this->update([
            'status' => 'fully_paid',
            'principal_outstanding' => 0,
            'interest_outstanding' => 0,
            'total_outstanding' => 0,
            'penalties_outstanding' => 0,
            'fees_outstanding' => 0,
            'arrears_amount' => 0,
            'days_past_due' => 0,
            'aging_bucket' => 'current',
            'installments_remaining' => 0,
        ]);
    }

    /**
     * Mark as defaulted
     */
    public function markAsDefaulted(string $reason = null): void
    {
        $this->update([
            'status' => 'defaulted',
            'risk_classification' => 'loss',
            'notes' => $reason ? $this->notes . "\n\nDefaulted: " . $reason : $this->notes,
        ]);
    }

    /**
     * Write off the loan
     */
    public function writeOff(int $userId, string $reason, ?float $amount = null): void
    {
        $this->update([
            'status' => 'written_off',
            'written_off_date' => now(),
            'written_off_amount' => $amount ?? $this->total_outstanding,
            'writeoff_reason' => $reason,
            'written_off_by' => $userId,
            'risk_classification' => 'loss',
        ]);
    }

    // ========================================
    // COMPUTED ATTRIBUTES
    // ========================================

    /**
     * Get the repayment progress percentage
     */
    public function getRepaymentProgressAttribute(): float
    {
        if ($this->total_repayment == 0) {
            return 0;
        }
        return ($this->total_paid / $this->total_repayment) * 100;
    }

    /**
     * Get the outstanding percentage
     */
    public function getOutstandingPercentageAttribute(): float
    {
        if ($this->total_repayment == 0) {
            return 0;
        }
        return ($this->total_outstanding / $this->total_repayment) * 100;
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending_disbursement' => 'blue',
            'active' => $this->days_past_due > 0 ? 'orange' : 'green',
            'fully_paid' => 'green',
            'closed' => 'gray',
            'defaulted' => 'red',
            'written_off' => 'red',
            'restructured' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get aging bucket color for UI
     */
    public function getAgingBucketColorAttribute(): string
    {
        return match($this->aging_bucket) {
            'current' => 'green',
            'bucket_30' => 'yellow',
            'bucket_60' => 'orange',
            'bucket_90' => 'red',
            'bucket_180' => 'red',
            'npl' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get risk classification color for UI
     */
    public function getRiskColorAttribute(): string
    {
        return match($this->risk_classification) {
            'performing' => 'green',
            'watch_list' => 'yellow',
            'substandard' => 'orange',
            'doubtful' => 'red',
            'loss' => 'red',
            default => 'gray',
        };
    }

    /**
     * Calculate days to maturity
     */
    public function getDaysToMaturityAttribute(): ?int
    {
        if (!$this->maturity_date) {
            return null;
        }
        return now()->diffInDays($this->maturity_date, false);
    }

    /**
     * Calculate months elapsed since disbursement
     */
    public function getMonthsElapsedAttribute(): ?int
    {
        if (!$this->disbursement_date) {
            return null;
        }
        return now()->diffInMonths($this->disbursement_date);
    }

    /**
     * Check if insurance is expired
     */
    public function getIsInsuranceExpiredAttribute(): bool
    {
        if (!$this->insurance_required || !$this->insurance_expiry_date) {
            return false;
        }
        return now()->greaterThan($this->insurance_expiry_date);
    }
}

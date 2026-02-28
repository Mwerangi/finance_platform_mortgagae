<?php

namespace App\Models;

use App\Enums\IncomeClassification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EligibilityAssessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_id',
        'prospect_id',
        'customer_id',
        'institution_id',
        'loan_product_id',
        'statement_analytics_id',
        'assessment_version',
        'assessment_type',
        'requested_amount',
        'requested_tenure_months',
        'property_value',
        'income_classification',
        'gross_monthly_income',
        'net_monthly_income',
        'income_stability_score',
        'total_monthly_debt',
        'detected_debt_count',
        'dti_ratio',
        'dsr_ratio',
        'ltv_ratio',
        'proposed_installment',
        'net_disposable_income',
        'net_surplus_after_loan',
        'business_safety_factor',
        'max_installment_from_income',
        'max_loan_from_affordability',
        'max_loan_from_ltv',
        'final_max_loan',
        'optimal_tenure_months',
        'risk_grade',
        'risk_score',
        'risk_factors',
        'risk_explanation',
        'cash_flow_volatility',
        'system_decision',
        'decision_reason',
        'policy_breaches',
        'conditions',
        'is_recommendable',
        'final_recommendation',
        'is_stress_test',
        'stress_scenario',
        'stress_test_params',
        'stressed_installment',
        'stressed_net_surplus',
        'passes_stress_test',
        'interest_method',
        'interest_rate',
        'monthly_interest_rate',
        'total_interest',
        'total_repayment',
        'effective_apr',
        'assessed_by',
        'assessed_at',
        'calculation_details',
        'notes',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'requested_tenure_months' => 'integer',
        'property_value' => 'decimal:2',
        'gross_monthly_income' => 'decimal:2',
        'net_monthly_income' => 'decimal:2',
        'income_stability_score' => 'decimal:2',
        'total_monthly_debt' => 'decimal:2',
        'detected_debt_count' => 'integer',
        'dti_ratio' => 'decimal:2',
        'dsr_ratio' => 'decimal:2',
        'ltv_ratio' => 'decimal:2',
        'proposed_installment' => 'decimal:2',
        'net_disposable_income' => 'decimal:2',
        'net_surplus_after_loan' => 'decimal:2',
        'business_safety_factor' => 'decimal:2',
        'max_installment_from_income' => 'decimal:2',
        'max_loan_from_affordability' => 'decimal:2',
        'max_loan_from_ltv' => 'decimal:2',
        'final_max_loan' => 'decimal:2',
        'optimal_tenure_months' => 'integer',
        'risk_score' => 'decimal:2',
        'risk_factors' => 'array',
        'risk_explanation' => 'array',
        'cash_flow_volatility' => 'decimal:2',
        'policy_breaches' => 'array',
        'conditions' => 'array',
        'is_recommendable' => 'boolean',
        'final_recommendation' => 'array',
        'is_stress_test' => 'boolean',
        'stress_test_params' => 'array',
        'stressed_installment' => 'decimal:2',
        'stressed_net_surplus' => 'decimal:2',
        'passes_stress_test' => 'boolean',
        'interest_rate' => 'decimal:2',
        'monthly_interest_rate' => 'decimal:6',
        'total_interest' => 'decimal:2',
        'total_repayment' => 'decimal:2',
        'effective_apr' => 'decimal:2',
        'assessed_at' => 'datetime',
        'calculation_details' => 'array',
    ];

    /**
     * Relationships
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
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

    public function statementAnalytics(): BelongsTo
    {
        return $this->belongsTo(StatementAnalytics::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
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

    public function scopeWithDecision($query, string $decision)
    {
        return $query->where('system_decision', $decision);
    }

    public function scopeWithRiskGrade($query, string $grade)
    {
        return $query->where('risk_grade', $grade);
    }

    public function scopeEligible($query)
    {
        return $query->where('system_decision', 'eligible');
    }

    public function scopeConditional($query)
    {
        return $query->where('system_decision', 'conditional');
    }

    public function scopeDeclined($query)
    {
        return $query->where('system_decision', 'outside_policy')
            ->orWhere('system_decision', 'declined');
    }

    public function scopeRecommendable($query)
    {
        return $query->where('is_recommendable', true);
    }

    public function scopeStressTests($query)
    {
        return $query->where('is_stress_test', true);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('assessed_at', 'desc');
    }

    /**
     * Decision Status Methods
     */
    public function isEligible(): bool
    {
        return $this->system_decision === 'eligible';
    }

    public function isConditional(): bool
    {
        return $this->system_decision === 'conditional';
    }

    public function isOutsidePolicy(): bool
    {
        return $this->system_decision === 'outside_policy';
    }

    public function isDeclined(): bool
    {
        return $this->system_decision === 'declined';
    }

    public function isRecommendable(): bool
    {
        return $this->is_recommendable === true;
    }

    /**
     * Risk Methods
     */
    public function isLowRisk(): bool
    {
        return in_array($this->risk_grade, ['A', 'B']);
    }

    public function isMediumRisk(): bool
    {
        return $this->risk_grade === 'C';
    }

    public function isHighRisk(): bool
    {
        return in_array($this->risk_grade, ['D', 'E']);
    }

    /**
     * Check if request exceeds maximum affordable amount
     */
    public function exceedsMaxLoan(): bool
    {
        return $this->requested_amount > $this->final_max_loan;
    }

    /**
     * Get affordability headroom (how much more customer could borrow)
     */
    public function getAffordabilityHeadroomAttribute(): float
    {
        return max(0, $this->final_max_loan - $this->requested_amount);
    }

    /**
     * Get utilization ratio (requested / max loan * 100)
     */
    public function getUtilizationRatioAttribute(): float
    {
        if ($this->final_max_loan == 0) {
            return 0;
        }
        
        return round(($this->requested_amount / $this->final_max_loan) * 100, 2);
    }

    /**
     * Check if assessment has policy breaches
     */
    public function hasPolicyBreaches(): bool
    {
        return !empty($this->policy_breaches);
    }

    /**
     * Check if assessment has conditions
     */
    public function hasConditions(): bool
    {
        return !empty($this->conditions);
    }

    /**
     * Get policy breach count
     */
    public function getPolicyBreachCountAttribute(): int
    {
        return count($this->policy_breaches ?? []);
    }

    /**
     * Get condition count
     */
    public function getConditionCountAttribute(): int
    {
        return count($this->conditions ?? []);
    }

    /**
     * Check if this is initial assessment
     */
    public function isInitialAssessment(): bool
    {
        return $this->assessment_type === 'initial';
    }

    /**
     * Check if this is a rerun
     */
    public function isRerun(): bool
    {
        return $this->assessment_type === 'rerun';
    }

    /**
     * Check if this is a stress test
     */
    public function isStressTest(): bool
    {
        return $this->is_stress_test === true;
    }

    /**
     * Get risk grade color for UI
     */
    public function getRiskGradeColorAttribute(): string
    {
        return match($this->risk_grade) {
            'A' => 'green',
            'B' => 'blue',
            'C' => 'yellow',
            'D' => 'orange',
            'E' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get decision color for UI
     */
    public function getDecisionColorAttribute(): string
    {
        return match($this->system_decision) {
            'eligible' => 'green',
            'conditional' => 'yellow',
            'outside_policy' => 'orange',
            'declined' => 'red',
            default => 'gray',
        };
    }
}

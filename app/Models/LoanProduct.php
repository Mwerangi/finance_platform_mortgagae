<?php

namespace App\Models;

use App\Enums\InterestModel;
use App\Enums\LoanProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoanProduct extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'name',
        'code',
        'description',
        'interest_model',
        'annual_interest_rate',
        'rate_type',
        'min_tenure_months',
        'max_tenure_months',
        'min_loan_amount',
        'max_loan_amount',
        'max_ltv_percentage',
        'max_dsr_salary_percentage',
        'max_dti_percentage',
        'business_safety_factor',
        'max_dsr_business_percentage',
        'fees',
        'penalties',
        'credit_policy',
        'status',
        'activated_at',
        'deactivated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'annual_interest_rate' => 'decimal:2',
        'min_tenure_months' => 'integer',
        'max_tenure_months' => 'integer',
        'min_loan_amount' => 'decimal:2',
        'max_loan_amount' => 'decimal:2',
        'max_ltv_percentage' => 'decimal:2',
        'max_dsr_salary_percentage' => 'decimal:2',
        'max_dti_percentage' => 'decimal:2',
        'business_safety_factor' => 'decimal:2',
        'max_dsr_business_percentage' => 'decimal:2',
        'fees' => 'array',
        'penalties' => 'array',
        'credit_policy' => 'array',
        'interest_model' => InterestModel::class,
        'status' => LoanProductStatus::class,
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the institution that owns the loan product.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the applications for the loan product.
     */
    public function applications()
    {
        return $this->hasMany(\App\Models\Application::class, 'loan_product_id');
    }

    /**
     * Get the loans for the loan product.
     */
    public function loans()
    {
        return $this->hasMany(\App\Models\Loan::class, 'loan_product_id');
    }

    /**
     * Check if product is active.
     */
    public function isActive(): bool
    {
        return $this->status === LoanProductStatus::ACTIVE;
    }

    /**
     * Activate the loan product.
     */
    public function activate(): void
    {
        $this->update([
            'status' => LoanProductStatus::ACTIVE,
            'activated_at' => now(),
            'deactivated_at' => null,
        ]);
    }

    /**
     * Deactivate the loan product.
     */
    public function deactivate(): void
    {
        $this->update([
            'status' => LoanProductStatus::INACTIVE,
            'deactivated_at' => now(),
        ]);
    }

    /**
     * Archive the loan product.
     */
    public function archive(): void
    {
        $this->update([
            'status' => LoanProductStatus::ARCHIVED,
            'deactivated_at' => now(),
        ]);
    }

    /**
     * Get monthly interest rate (decimal).
     */
    public function getMonthlyInterestRate(): float
    {
        return $this->annual_interest_rate / 100 / 12;
    }

    /**
     * Get fees configuration with defaults.
     */
    public function getFeesConfig(): array
    {
        return array_merge([
            'processing_fee' => null,
            'appraisal_fee' => null,
            'insurance_fee' => null,
            'other_fees' => [],
        ], $this->fees ?? []);
    }

    /**
     * Get penalties configuration with defaults.
     */
    public function getPenaltiesConfig(): array
    {
        return array_merge([
            'late_payment' => null,
            'early_repayment' => null,
        ], $this->penalties ?? []);
    }

    /**
     * Get credit policy with defaults.
     */
    public function getCreditPolicyConfig(): array
    {
        return array_merge([
            'min_volatility_score' => 0.3,
            'min_income_stability_score' => 0.5,
            'min_account_age_months' => 6,
            'max_debt_exposure_ratio' => 0.5,
            'risk_grade_thresholds' => [
                'A' => ['min_score' => 80, 'max_ltv' => 90],
                'B' => ['min_score' => 65, 'max_ltv' => 80],
                'C' => ['min_score' => 50, 'max_ltv' => 70],
                'D' => ['min_score' => 0, 'max_ltv' => 60],
            ],
        ], $this->credit_policy ?? []);
    }

    /**
     * Calculate monthly installment for reducing balance.
     *
     * @param float $principal Loan amount
     * @param int $tenureMonths Number of months
     * @return float Monthly installment amount
     */
    public function calculateReducingBalanceInstallment(float $principal, int $tenureMonths): float
    {
        $monthlyRate = $this->getMonthlyInterestRate();
        
        if ($monthlyRate == 0) {
            return $principal / $tenureMonths;
        }
        
        // PMT formula: P * [r * (1 + r)^n] / [(1 + r)^n - 1]
        $factor = pow(1 + $monthlyRate, $tenureMonths);
        $installment = $principal * ($monthlyRate * $factor) / ($factor - 1);
        
        return round($installment, 2);
    }

    /**
     * Calculate total interest for reducing balance.
     *
     * @param float $principal Loan amount
     * @param int $tenureMonths Number of months
     * @return float Total interest amount
     */
    public function calculateReducingBalanceTotalInterest(float $principal, int $tenureMonths): float
    {
        $installment = $this->calculateReducingBalanceInstallment($principal, $tenureMonths);
        $totalPayment = $installment * $tenureMonths;
        
        return round($totalPayment - $principal, 2);
    }

    /**
     * Calculate monthly installment for flat rate.
     *
     * @param float $principal Loan amount
     * @param int $tenureMonths Number of months
     * @return float Monthly installment amount
     */
    public function calculateFlatRateInstallment(float $principal, int $tenureMonths): float
    {
        $totalInterest = $this->calculateFlatRateTotalInterest($principal, $tenureMonths);
        $totalPayment = $principal + $totalInterest;
        
        return round($totalPayment / $tenureMonths, 2);
    }

    /**
     * Calculate total interest for flat rate.
     *
     * @param float $principal Loan amount
     * @param int $tenureMonths Number of months
     * @return float Total interest amount
     */
    public function calculateFlatRateTotalInterest(float $principal, int $tenureMonths): float
    {
        $annualRate = $this->annual_interest_rate / 100;
        $years = $tenureMonths / 12;
        
        return round($principal * $annualRate * $years, 2);
    }

    /**
     * Calculate monthly installment based on interest model.
     *
     * @param float $principal Loan amount
     * @param int $tenureMonths Number of months
     * @return float Monthly installment amount
     */
    public function calculateInstallment(float $principal, int $tenureMonths): float
    {
        return match($this->interest_model) {
            InterestModel::REDUCING_BALANCE => $this->calculateReducingBalanceInstallment($principal, $tenureMonths),
            InterestModel::FLAT_RATE => $this->calculateFlatRateInstallment($principal, $tenureMonths),
        };
    }

    /**
     * Calculate total interest based on interest model.
     *
     * @param float $principal Loan amount
     * @param int $tenureMonths Number of months
     * @return float Total interest amount
     */
    public function calculateTotalInterest(float $principal, int $tenureMonths): float
    {
        return match($this->interest_model) {
            InterestModel::REDUCING_BALANCE => $this->calculateReducingBalanceTotalInterest($principal, $tenureMonths),
            InterestModel::FLAT_RATE => $this->calculateFlatRateTotalInterest($principal, $tenureMonths),
        };
    }

    /**
     * Validate loan amount against product limits.
     *
     * @param float $amount Requested loan amount
     * @return bool
     */
    public function isValidLoanAmount(float $amount): bool
    {
        return $amount >= $this->min_loan_amount && $amount <= $this->max_loan_amount;
    }

    /**
     * Validate tenure against product limits.
     *
     * @param int $months Requested tenure in months
     * @return bool
     */
    public function isValidTenure(int $months): bool
    {
        return $months >= $this->min_tenure_months && $months <= $this->max_tenure_months;
    }

    /**
     * Scope to only active products.
     */
    public function scopeActive($query)
    {
        return $query->where('status', LoanProductStatus::ACTIVE);
    }

    /**
     * Scope to products for a specific institution.
     */
    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}

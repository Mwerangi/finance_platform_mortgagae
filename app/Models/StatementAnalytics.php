<?php

namespace App\Models;

use App\Enums\IncomeClassification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StatementAnalytics extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bank_statement_import_id',
        'customer_id',
        'institution_id',
        'application_id',
        'analysis_months',
        'analysis_start_date',
        'analysis_end_date',
        'monthly_inflows',
        'monthly_outflows',
        'monthly_net_surplus',
        'avg_monthly_inflow',
        'avg_monthly_outflow',
        'avg_net_surplus',
        'opening_balance',
        'closing_balance',
        'income_classification',
        'estimated_net_income',
        'income_stability_score',
        'has_regular_salary',
        'has_business_income',
        'income_sources',
        'total_debt_obligations',
        'estimated_monthly_debt',
        'debt_payment_count',
        'detected_debts',
        'cash_flow_volatility_score',
        'negative_balance_days',
        'bounce_count',
        'gambling_transaction_count',
        'large_unexplained_outflows',
        'risk_flags',
        'overall_risk_assessment',
        'debt_to_income_ratio',
        'disposable_income_ratio',
        'computed_at',
        'computed_by',
    ];

    protected $casts = [
        'analysis_months' => 'integer',
        'analysis_start_date' => 'date',
        'analysis_end_date' => 'date',
        'monthly_inflows' => 'array',
        'monthly_outflows' => 'array',
        'monthly_net_surplus' => 'array',
        'avg_monthly_inflow' => 'decimal:2',
        'avg_monthly_outflow' => 'decimal:2',
        'avg_net_surplus' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'income_classification' => IncomeClassification::class,
        'estimated_net_income' => 'decimal:2',
        'income_stability_score' => 'decimal:2',
        'has_regular_salary' => 'boolean',
        'has_business_income' => 'boolean',
        'income_sources' => 'array',
        'total_debt_obligations' => 'decimal:2',
        'estimated_monthly_debt' => 'decimal:2',
        'debt_payment_count' => 'integer',
        'detected_debts' => 'array',
        'cash_flow_volatility_score' => 'decimal:2',
        'negative_balance_days' => 'integer',
        'bounce_count' => 'integer',
        'gambling_transaction_count' => 'integer',
        'large_unexplained_outflows' => 'decimal:2',
        'risk_flags' => 'array',
        'debt_to_income_ratio' => 'decimal:2',
        'disposable_income_ratio' => 'decimal:2',
        'computed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the import that owns the analytics.
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    /**
     * Get the customer that owns the analytics.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the institution that owns the analytics.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the application associated with the analytics.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the user who computed the analytics.
     */
    public function computer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'computed_by');
    }

    /**
     * Check if customer has regular salary.
     */
    public function hasRegularSalary(): bool
    {
        return $this->has_regular_salary;
    }

    /**
     * Check if customer has business income.
     */
    public function hasBusinessIncome(): bool
    {
        return $this->has_business_income;
    }

    /**
     * Check if income is stable.
     */
    public function hasStableIncome(): bool
    {
        return $this->income_stability_score >= 70;
    }

    /**
     * Check if cash flow is volatile.
     */
    public function hasVolatileCashFlow(): bool
    {
        return $this->cash_flow_volatility_score >= 50;
    }

    /**
     * Check if analytics indicate high risk.
     */
    public function isHighRisk(): bool
    {
        return $this->overall_risk_assessment === 'high';
    }

    /**
     * Check if analytics indicate low risk.
     */
    public function isLowRisk(): bool
    {
        return $this->overall_risk_assessment === 'low';
    }

    /**
     * Get monthly average for a specific month.
     */
    public function getMonthlyInflow(string $month): ?float
    {
        $monthlyData = collect($this->monthly_inflows ?? [])
            ->firstWhere('month', $month);

        return $monthlyData['inflow'] ?? null;
    }

    /**
     * Get monthly outflow for a specific month.
     */
    public function getMonthlyOutflow(string $month): ?float
    {
        $monthlyData = collect($this->monthly_outflows ?? [])
            ->firstWhere('month', $month);

        return $monthlyData['outflow'] ?? null;
    }

    /**
     * Calculate affordability score (0-100).
     */
    public function getAffordabilityScoreAttribute(): int
    {
        $score = 100;

        // Deduct for high debt-to-income ratio
        if ($this->debt_to_income_ratio > 40) {
            $score -= 30;
        } elseif ($this->debt_to_income_ratio > 30) {
            $score -= 20;
        } elseif ($this->debt_to_income_ratio > 20) {
            $score -= 10;
        }

        // Deduct for low income stability
        if ($this->income_stability_score < 50) {
            $score -= 20;
        } elseif ($this->income_stability_score < 70) {
            $score -= 10;
        }

        // Deduct for volatile cash flow
        if ($this->cash_flow_volatility_score > 70) {
            $score -= 20;
        } elseif ($this->cash_flow_volatility_score > 50) {
            $score -= 10;
        }

        // Deduct for negative balance days
        if ($this->negative_balance_days > 10) {
            $score -= 15;
        } elseif ($this->negative_balance_days > 5) {
            $score -= 10;
        }

        // Deduct for bounces
        $score -= min($this->bounce_count * 5, 20);

        // Deduct for gambling
        if ($this->gambling_transaction_count > 0) {
            $score -= 10;
        }

        return max($score, 0);
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
     * Scope to filter by income classification.
     */
    public function scopeWithIncomeType($query, IncomeClassification $classification)
    {
        return $query->where('income_classification', $classification);
    }

    /**
     * Scope to filter high risk analytics.
     */
    public function scopeHighRisk($query)
    {
        return $query->where('overall_risk_assessment', 'high');
    }

    /**
     * Scope to filter low risk analytics.
     */
    public function scopeLowRisk($query)
    {
        return $query->where('overall_risk_assessment', 'low');
    }
}

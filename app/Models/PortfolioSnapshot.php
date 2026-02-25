<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'snapshot_date',
        'snapshot_type',
        // Portfolio size
        'total_loans',
        'active_loans',
        'closed_loans',
        'written_off_loans',
        // Portfolio value
        'total_disbursed',
        'principal_outstanding',
        'interest_outstanding',
        'total_outstanding',
        'penalties_outstanding',
        'fees_outstanding',
        // Collections
        'total_collected',
        'principal_collected',
        'interest_collected',
        'penalties_collected',
        'fees_collected',
        // Arrears
        'total_arrears',
        'loans_in_arrears',
        // Aging buckets
        'current_count',
        'current_amount',
        'bucket_30_count',
        'bucket_30_amount',
        'bucket_60_count',
        'bucket_60_amount',
        'bucket_90_count',
        'bucket_90_amount',
        'bucket_180_count',
        'bucket_180_amount',
        'npl_count',
        'npl_amount',
        // PAR metrics
        'par_30_count',
        'par_30_amount',
        'par_30_ratio',
        'par_60_count',
        'par_60_amount',
        'par_60_ratio',
        'par_90_count',
        'par_90_amount',
        'par_90_ratio',
        // NPL
        'npl_ratio',
        // Collection rate
        'expected_collections',
        'actual_collections',
        'collection_rate',
        // Write-offs
        'writeoff_count',
        'writeoff_amount',
        'writeoff_ratio',
        // Provision
        'total_provision',
        'provision_coverage_ratio',
        // Portfolio growth
        'new_loans_disbursed',
        'new_loans_count',
        'portfolio_growth_rate',
        // Average metrics
        'average_loan_size',
        'average_outstanding',
        'average_tenure_months',
        'average_interest_rate',
        // Risk classification
        'performing_count',
        'watch_list_count',
        'substandard_count',
        'doubtful_count',
        'loss_count',
        // Metadata
        'additional_metrics',
        'computed_at',
        'notes',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'total_loans' => 'integer',
        'active_loans' => 'integer',
        'closed_loans' => 'integer',
        'written_off_loans' => 'integer',
        'total_disbursed' => 'decimal:2',
        'principal_outstanding' => 'decimal:2',
        'interest_outstanding' => 'decimal:2',
        'total_outstanding' => 'decimal:2',
        'penalties_outstanding' => 'decimal:2',
        'fees_outstanding' => 'decimal:2',
        'total_collected' => 'decimal:2',
        'principal_collected' => 'decimal:2',
        'interest_collected' => 'decimal:2',
        'penalties_collected' => 'decimal:2',
        'fees_collected' => 'decimal:2',
        'total_arrears' => 'decimal:2',
        'loans_in_arrears' => 'integer',
        'current_count' => 'integer',
        'current_amount' => 'decimal:2',
        'bucket_30_count' => 'integer',
        'bucket_30_amount' => 'decimal:2',
        'bucket_60_count' => 'integer',
        'bucket_60_amount' => 'decimal:2',
        'bucket_90_count' => 'integer',
        'bucket_90_amount' => 'decimal:2',
        'bucket_180_count' => 'integer',
        'bucket_180_amount' => 'decimal:2',
        'npl_count' => 'integer',
        'npl_amount' => 'decimal:2',
        'par_30_count' => 'integer',
        'par_30_amount' => 'decimal:2',
        'par_30_ratio' => 'decimal:2',
        'par_60_count' => 'integer',
        'par_60_amount' => 'decimal:2',
        'par_60_ratio' => 'decimal:2',
        'par_90_count' => 'integer',
        'par_90_amount' => 'decimal:2',
        'par_90_ratio' => 'decimal:2',
        'npl_ratio' => 'decimal:2',
        'expected_collections' => 'decimal:2',
        'actual_collections' => 'decimal:2',
        'collection_rate' => 'decimal:2',
        'writeoff_count' => 'integer',
        'writeoff_amount' => 'decimal:2',
        'writeoff_ratio' => 'decimal:2',
        'total_provision' => 'decimal:2',
        'provision_coverage_ratio' => 'decimal:2',
        'new_loans_disbursed' => 'decimal:2',
        'new_loans_count' => 'integer',
        'portfolio_growth_rate' => 'decimal:2',
        'average_loan_size' => 'decimal:2',
        'average_outstanding' => 'decimal:2',
        'average_tenure_months' => 'integer',
        'average_interest_rate' => 'decimal:2',
        'performing_count' => 'integer',
        'watch_list_count' => 'integer',
        'substandard_count' => 'integer',
        'doubtful_count' => 'integer',
        'loss_count' => 'integer',
        'additional_metrics' => 'array',
        'computed_at' => 'datetime',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeBySnapshotType($query, string $type)
    {
        return $query->where('snapshot_type', $type);
    }

    public function scopeDaily($query)
    {
        return $query->where('snapshot_type', 'daily');
    }

    public function scopeMonthly($query)
    {
        return $query->where('snapshot_type', 'monthly');
    }

    public function scopeQuarterly($query)
    {
        return $query->where('snapshot_type', 'quarterly');
    }

    public function scopeAnnual($query)
    {
        return $query->where('snapshot_type', 'annual');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('snapshot_date', $date);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('snapshot_date', [$startDate, $endDate]);
    }

    public function scopeLatestSnapshot($query)
    {
        return $query->orderBy('snapshot_date', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('snapshot_date', $year)
                    ->whereMonth('snapshot_date', $month);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('snapshot_date', $year);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('snapshot_date', '>=', now()->subDays($days));
    }

    // ==========================================
    // COMPUTED ATTRIBUTES
    // ==========================================

    /**
     * Get aging distribution
     */
    public function getAgingDistributionAttribute(): array
    {
        return [
            'current' => [
                'count' => $this->current_count,
                'amount' => (float) $this->current_amount,
                'label' => '0-30 days',
            ],
            'bucket_30' => [
                'count' => $this->bucket_30_count,
                'amount' => (float) $this->bucket_30_amount,
                'label' => '31-60 days',
            ],
            'bucket_60' => [
                'count' => $this->bucket_60_count,
                'amount' => (float) $this->bucket_60_amount,
                'label' => '61-90 days',
            ],
            'bucket_90' => [
                'count' => $this->bucket_90_count,
                'amount' => (float) $this->bucket_90_amount,
                'label' => '91-180 days',
            ],
            'bucket_180' => [
                'count' => $this->bucket_180_count,
                'amount' => (float) $this->bucket_180_amount,
                'label' => '180+ days',
            ],
            'npl' => [
                'count' => $this->npl_count,
                'amount' => (float) $this->npl_amount,
                'label' => 'NPL (90+ days)',
            ],
        ];
    }

    /**
     * Get PAR metrics
     */
    public function getParMetricsAttribute(): array
    {
        return [
            'par_30' => [
                'count' => $this->par_30_count,
                'amount' => (float) $this->par_30_amount,
                'ratio' => (float) $this->par_30_ratio,
                'label' => 'PAR 30+',
            ],
            'par_60' => [
                'count' => $this->par_60_count,
                'amount' => (float) $this->par_60_amount,
                'ratio' => (float) $this->par_60_ratio,
                'label' => 'PAR 60+',
            ],
            'par_90' => [
                'count' => $this->par_90_count,
                'amount' => (float) $this->par_90_amount,
                'ratio' => (float) $this->par_90_ratio,
                'label' => 'PAR 90+ / NPL',
            ],
        ];
    }

    /**
     * Get risk distribution
     */
    public function getRiskDistributionAttribute(): array
    {
        return [
            'performing' => [
                'count' => $this->performing_count,
                'label' => 'Performing',
                'color' => 'green',
            ],
            'watch_list' => [
                'count' => $this->watch_list_count,
                'label' => 'Watch List',
                'color' => 'yellow',
            ],
            'substandard' => [
                'count' => $this->substandard_count,
                'label' => 'Substandard',
                'color' => 'orange',
            ],
            'doubtful' => [
                'count' => $this->doubtful_count,
                'label' => 'Doubtful',
                'color' => 'red',
            ],
            'loss' => [
                'count' => $this->loss_count,
                'label' => 'Loss',
                'color' => 'dark-red',
            ],
        ];
    }

    /**
     * Get portfolio composition percentages
     */
    public function getPortfolioCompositionAttribute(): array
    {
        if ($this->total_loans === 0) {
            return [
                'active' => 0,
                'closed' => 0,
                'written_off' => 0,
            ];
        }

        return [
            'active' => round(($this->active_loans / $this->total_loans) * 100, 2),
            'closed' => round(($this->closed_loans / $this->total_loans) * 100, 2),
            'written_off' => round(($this->written_off_loans / $this->total_loans) * 100, 2),
        ];
    }

    /**
     * Get collection performance
     */
    public function getCollectionPerformanceAttribute(): array
    {
        return [
            'expected' => (float) $this->expected_collections,
            'actual' => (float) $this->actual_collections,
            'rate' => (float) $this->collection_rate,
            'shortfall' => (float) ($this->expected_collections - $this->actual_collections),
            'excess' => (float) max(0, $this->actual_collections - $this->expected_collections),
        ];
    }

    /**
     * Get portfolio health score (0-100)
     */
    public function getHealthScoreAttribute(): float
    {
        $score = 100;

        // Deduct for PAR ratios
        $score -= ($this->par_30_ratio ?? 0) * 0.3;
        $score -= ($this->par_60_ratio ?? 0) * 0.5;
        $score -= ($this->par_90_ratio ?? 0) * 1.0;

        // Deduct for write-offs
        $score -= ($this->writeoff_ratio ?? 0) * 0.5;

        // Add for collection rate
        $score += (($this->collection_rate ?? 0) - 100) * 0.1;

        // Deduct for provision coverage issues
        if ($this->provision_coverage_ratio < 100) {
            $score -= (100 - $this->provision_coverage_ratio) * 0.2;
        }

        return max(0, min(100, round($score, 2)));
    }

    /**
     * Get health status based on score
     */
    public function getHealthStatusAttribute(): string
    {
        $score = $this->health_score;

        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Fair';
        if ($score >= 40) return 'Poor';
        return 'Critical';
    }

    /**
     * Get health color
     */
    public function getHealthColorAttribute(): string
    {
        $score = $this->health_score;

        if ($score >= 90) return 'dark-green';
        if ($score >= 75) return 'green';
        if ($score >= 60) return 'yellow';
        if ($score >= 40) return 'orange';
        return 'red';
    }

    /**
     * Get outstanding breakdown percentages
     */
    public function getOutstandingBreakdownAttribute(): array
    {
        if ($this->total_outstanding == 0) {
            return [
                'principal' => 0,
                'interest' => 0,
                'penalties' => 0,
                'fees' => 0,
            ];
        }

        return [
            'principal' => round(($this->principal_outstanding / $this->total_outstanding) * 100, 2),
            'interest' => round(($this->interest_outstanding / $this->total_outstanding) * 100, 2),
            'penalties' => round(($this->penalties_outstanding / $this->total_outstanding) * 100, 2),
            'fees' => round(($this->fees_outstanding / $this->total_outstanding) * 100, 2),
        ];
    }

    /**
     * Get collections breakdown percentages
     */
    public function getCollectionsBreakdownAttribute(): array
    {
        if ($this->total_collected == 0) {
            return [
                'principal' => 0,
                'interest' => 0,
                'penalties' => 0,
                'fees' => 0,
            ];
        }

        return [
            'principal' => round(($this->principal_collected / $this->total_collected) * 100, 2),
            'interest' => round(($this->interest_collected / $this->total_collected) * 100, 2),
            'penalties' => round(($this->penalties_collected / $this->total_collected) * 100, 2),
            'fees' => round(($this->fees_collected / $this->total_collected) * 100, 2),
        ];
    }

    /**
     * Get portfolio at risk percentage
     */
    public function getTotalParPercentageAttribute(): float
    {
        if ($this->active_loans === 0) {
            return 0;
        }

        $parLoans = $this->par_30_count;
        return round(($parLoans / $this->active_loans) * 100, 2);
    }

    /**
     * Get arrears percentage
     */
    public function getArrearsPercentageAttribute(): float
    {
        if ($this->total_outstanding == 0) {
            return 0;
        }

        return round(($this->total_arrears / $this->total_outstanding) * 100, 2);
    }

    /**
     * Get average metrics summary
     */
    public function getAverageMetricsAttribute(): array
    {
        return [
            'loan_size' => (float) $this->average_loan_size,
            'outstanding' => (float) $this->average_outstanding,
            'tenure_months' => $this->average_tenure_months,
            'interest_rate' => (float) $this->average_interest_rate,
        ];
    }

    /**
     * Get snapshot summary
     */
    public function getSummaryAttribute(): array
    {
        return [
            'date' => $this->snapshot_date->format('Y-m-d'),
            'type' => $this->snapshot_type,
            'portfolio_size' => [
                'total' => $this->total_loans,
                'active' => $this->active_loans,
                'composition' => $this->portfolio_composition,
            ],
            'portfolio_value' => [
                'disbursed' => (float) $this->total_disbursed,
                'outstanding' => (float) $this->total_outstanding,
                'breakdown' => $this->outstanding_breakdown,
            ],
            'risk_metrics' => [
                'par_30' => (float) $this->par_30_ratio,
                'par_60' => (float) $this->par_60_ratio,
                'par_90' => (float) $this->par_90_ratio,
                'npl_ratio' => (float) $this->npl_ratio,
            ],
            'performance' => [
                'collection_rate' => (float) $this->collection_rate,
                'growth_rate' => (float) $this->portfolio_growth_rate,
            ],
            'health' => [
                'score' => $this->health_score,
                'status' => $this->health_status,
                'color' => $this->health_color,
            ],
        ];
    }
}

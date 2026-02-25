<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\PortfolioSnapshot;
use App\Models\Repayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    /**
     * Compute portfolio snapshot for a specific date
     */
    public function computeSnapshot(
        int $institutionId,
        Carbon $snapshotDate,
        string $snapshotType = 'daily'
    ): PortfolioSnapshot {
        // Determine period for growth and collections calculations
        $periodStart = $this->getPeriodStart($snapshotDate, $snapshotType);
        
        // Get all loans disbursed on or before snapshot date
        $allLoans = Loan::forInstitution($institutionId)
            ->where(function ($query) use ($snapshotDate) {
                $query->whereDate('disbursement_date', '<=', $snapshotDate)
                    ->orWhereNull('disbursement_date');
            })
            ->get();

        // Active loans only (for most calculations)
        $activeLoans = $allLoans->where('status', 'active');

        // Portfolio size metrics
        $portfolioSize = $this->calculatePortfolioSize($allLoans);

        // Portfolio value metrics
        $portfolioValue = $this->calculatePortfolioValue($activeLoans);

        // Collections in period
        $collections = $this->calculateCollections($institutionId, $periodStart, $snapshotDate);

        // Arrears metrics
        $arrears = $this->calculateArrears($activeLoans);

        // Aging distribution
        $aging = $this->calculateAgingDistribution($activeLoans);

        // PAR metrics
        $par = $this->calculatePARMetrics($activeLoans, $portfolioValue['total_outstanding']);

        // Collection rate
        $collectionRate = $this->calculateCollectionRate($institutionId, $periodStart, $snapshotDate);

        // Write-offs in period
        $writeoffs = $this->calculateWriteoffs($allLoans, $periodStart, $snapshotDate);

        // Provision
        $provision = $this->calculateProvision($activeLoans, $par['par_90_amount']);

        // Portfolio growth
        $growth = $this->calculateGrowth($institutionId, $periodStart, $snapshotDate, $portfolioValue['total_outstanding']);

        // Average metrics
        $averages = $this->calculateAverages($activeLoans);

        // Risk classification distribution
        $riskDistribution = $this->calculateRiskDistribution($activeLoans);

        // Create or update snapshot
        $snapshot = PortfolioSnapshot::updateOrCreate(
            [
                'institution_id' => $institutionId,
                'snapshot_date' => $snapshotDate,
                'snapshot_type' => $snapshotType,
            ],
            array_merge(
                $portfolioSize,
                $portfolioValue,
                $collections,
                $arrears,
                $aging,
                $par,
                $collectionRate,
                $writeoffs,
                $provision,
                $growth,
                $averages,
                $riskDistribution,
                [
                    'computed_at' => now(),
                ]
            )
        );

        return $snapshot;
    }

    /**
     * Get period start date based on snapshot type
     */
    protected function getPeriodStart(Carbon $date, string $type): Carbon
    {
        return match($type) {
            'daily' => $date->copy()->startOfDay(),
            'monthly' => $date->copy()->startOfMonth(),
            'quarterly' => $date->copy()->startOfQuarter(),
            'annual' => $date->copy()->startOfYear(),
            default => $date->copy()->startOfDay(),
        };
    }

    /**
     * Calculate portfolio size metrics
     */
    protected function calculatePortfolioSize($loans): array
    {
        return [
            'total_loans' => $loans->count(),
            'active_loans' => $loans->where('status', 'active')->count(),
            'closed_loans' => $loans->where('status', 'closed')->count(),
            'written_off_loans' => $loans->where('status', 'written_off')->count(),
        ];
    }

    /**
     * Calculate portfolio value metrics
     */
    protected function calculatePortfolioValue($activeLoans): array
    {
        return [
            'total_disbursed' => $activeLoans->sum('disbursed_amount'),
            'principal_outstanding' => $activeLoans->sum('principal_outstanding'),
            'interest_outstanding' => $activeLoans->sum('interest_outstanding'),
            'total_outstanding' => $activeLoans->sum('total_outstanding'),
            'penalties_outstanding' => $activeLoans->sum('penalties_outstanding'),
            'fees_outstanding' => $activeLoans->sum('fees_outstanding'),
        ];
    }

    /**
     * Calculate collections in period
     */
    protected function calculateCollections(int $institutionId, Carbon $start, Carbon $end): array
    {
        $repayments = Repayment::forInstitution($institutionId)
            ->notReversed()
            ->whereBetween('payment_date', [$start, $end])
            ->get();

        return [
            'total_collected' => $repayments->sum('amount'),
            'principal_collected' => $repayments->sum('principal_amount'),
            'interest_collected' => $repayments->sum('interest_amount'),
            'penalties_collected' => $repayments->sum('penalties_amount'),
            'fees_collected' => $repayments->sum('fees_amount'),
        ];
    }

    /**
     * Calculate arrears metrics
     */
    protected function calculateArrears($activeLoans): array
    {
        $loansInArrears = $activeLoans->where('days_past_due', '>', 0);

        return [
            'total_arrears' => $loansInArrears->sum('arrears_amount'),
            'loans_in_arrears' => $loansInArrears->count(),
        ];
    }

    /**
     * Calculate aging bucket distribution
     */
    protected function calculateAgingDistribution($activeLoans): array
    {
        $buckets = [
            'current' => $activeLoans->where('aging_bucket', 'current'),
            'bucket_30' => $activeLoans->where('aging_bucket', 'bucket_30'),
            'bucket_60' => $activeLoans->where('aging_bucket', 'bucket_60'),
            'bucket_90' => $activeLoans->where('aging_bucket', 'bucket_90'),
            'bucket_180' => $activeLoans->where('aging_bucket', 'bucket_180'),
            'npl' => $activeLoans->where('aging_bucket', 'npl'),
        ];

        return [
            'current_count' => $buckets['current']->count(),
            'current_amount' => $buckets['current']->sum('total_outstanding'),
            'bucket_30_count' => $buckets['bucket_30']->count(),
            'bucket_30_amount' => $buckets['bucket_30']->sum('total_outstanding'),
            'bucket_60_count' => $buckets['bucket_60']->count(),
            'bucket_60_amount' => $buckets['bucket_60']->sum('total_outstanding'),
            'bucket_90_count' => $buckets['bucket_90']->count(),
            'bucket_90_amount' => $buckets['bucket_90']->sum('total_outstanding'),
            'bucket_180_count' => $buckets['bucket_180']->count(),
            'bucket_180_amount' => $buckets['bucket_180']->sum('total_outstanding'),
            'npl_count' => $buckets['npl']->count(),
            'npl_amount' => $buckets['npl']->sum('total_outstanding'),
        ];
    }

    /**
     * Calculate PAR (Portfolio at Risk) metrics
     */
    protected function calculatePARMetrics($activeLoans, float $totalOutstanding): array
    {
        // PAR 30: loans with 30+ days past due
        $par30Loans = $activeLoans->where('days_past_due', '>=', 30);
        $par30Amount = $par30Loans->sum('total_outstanding');
        $par30Ratio = $totalOutstanding > 0 ? round(($par30Amount / $totalOutstanding) * 100, 2) : 0;

        // PAR 60: loans with 60+ days past due
        $par60Loans = $activeLoans->where('days_past_due', '>=', 60);
        $par60Amount = $par60Loans->sum('total_outstanding');
        $par60Ratio = $totalOutstanding > 0 ? round(($par60Amount / $totalOutstanding) * 100, 2) : 0;

        // PAR 90 / NPL: loans with 90+ days past due
        $par90Loans = $activeLoans->where('days_past_due', '>=', 90);
        $par90Amount = $par90Loans->sum('total_outstanding');
        $par90Ratio = $totalOutstanding > 0 ? round(($par90Amount / $totalOutstanding) * 100, 2) : 0;

        return [
            'par_30_count' => $par30Loans->count(),
            'par_30_amount' => $par30Amount,
            'par_30_ratio' => $par30Ratio,
            'par_60_count' => $par60Loans->count(),
            'par_60_amount' => $par60Amount,
            'par_60_ratio' => $par60Ratio,
            'par_90_count' => $par90Loans->count(),
            'par_90_amount' => $par90Amount,
            'par_90_ratio' => $par90Ratio,
            'npl_ratio' => $par90Ratio, // NPL ratio is same as PAR 90+
        ];
    }

    /**
     * Calculate collection rate for period
     */
    protected function calculateCollectionRate(int $institutionId, Carbon $start, Carbon $end): array
    {
        // Expected: sum of schedule amounts due in period
        $expected = LoanSchedule::forInstitution($institutionId)
            ->whereBetween('due_date', [$start, $end])
            ->sum('total_due');

        // Actual: sum of payments received in period
        $actual = Repayment::forInstitution($institutionId)
            ->notReversed()
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount');

        $rate = $expected > 0 ? round(($actual / $expected) * 100, 2) : 0;

        return [
            'expected_collections' => $expected,
            'actual_collections' => $actual,
            'collection_rate' => $rate,
        ];
    }

    /**
     * Calculate write-offs in period
     */
    protected function calculateWriteoffs($loans, Carbon $start, Carbon $end): array
    {
        $writeoffs = $loans->filter(function ($loan) use ($start, $end) {
            return $loan->status === 'written_off' &&
                   $loan->written_off_date &&
                   Carbon::parse($loan->written_off_date)->between($start, $end);
        });

        $totalOutstanding = $loans->where('status', 'active')->sum('total_outstanding');
        $writeoffAmount = $writeoffs->sum('written_off_amount');
        $writeoffRatio = $totalOutstanding > 0 ? round(($writeoffAmount / $totalOutstanding) * 100, 2) : 0;

        return [
            'writeoff_count' => $writeoffs->count(),
            'writeoff_amount' => $writeoffAmount,
            'writeoff_ratio' => $writeoffRatio,
        ];
    }

    /**
     * Calculate provision metrics
     */
    protected function calculateProvision($activeLoans, float $nplAmount): array
    {
        $totalProvision = $activeLoans->sum('provision_amount');
        $coverageRatio = $nplAmount > 0 ? round(($totalProvision / $nplAmount) * 100, 2) : 0;

        return [
            'total_provision' => $totalProvision,
            'provision_coverage_ratio' => $coverageRatio,
        ];
    }

    /**
     * Calculate portfolio growth in period
     */
    protected function calculateGrowth(
        int $institutionId,
        Carbon $start,
        Carbon $end,
        float $currentOutstanding
    ): array {
        // New loans disbursed in period
        $newLoans = Loan::forInstitution($institutionId)
            ->whereBetween('disbursement_date', [$start, $end])
            ->get();

        $newDisbursed = $newLoans->sum('disbursed_amount');
        $newCount = $newLoans->count();

        // Get previous period outstanding for growth calculation
        $previousSnapshot = PortfolioSnapshot::forInstitution($institutionId)
            ->where('snapshot_date', '<', $start)
            ->latestSnapshot()
            ->first();

        $previousOutstanding = $previousSnapshot?->total_outstanding ?? 0;
        
        $growthRate = $previousOutstanding > 0 
            ? round((($currentOutstanding - $previousOutstanding) / $previousOutstanding) * 100, 2)
            : 0;

        return [
            'new_loans_disbursed' => $newDisbursed,
            'new_loans_count' => $newCount,
            'portfolio_growth_rate' => $growthRate,
        ];
    }

    /**
     * Calculate average metrics
     */
    protected function calculateAverages($activeLoans): array
    {
        $count = $activeLoans->count();

        if ($count === 0) {
            return [
                'average_loan_size' => 0,
                'average_outstanding' => 0,
                'average_tenure_months' => 0,
                'average_interest_rate' => 0,
            ];
        }

        return [
            'average_loan_size' => round($activeLoans->avg('approved_amount'), 2),
            'average_outstanding' => round($activeLoans->avg('total_outstanding'), 2),
            'average_tenure_months' => round($activeLoans->avg('approved_tenure_months')),
            'average_interest_rate' => round($activeLoans->avg('approved_interest_rate'), 2),
        ];
    }

    /**
     * Calculate risk classification distribution
     */
    protected function calculateRiskDistribution($activeLoans): array
    {
        return [
            'performing_count' => $activeLoans->where('risk_classification', 'performing')->count(),
            'watch_list_count' => $activeLoans->where('risk_classification', 'watch_list')->count(),
            'substandard_count' => $activeLoans->where('risk_classification', 'substandard')->count(),
            'doubtful_count' => $activeLoans->where('risk_classification', 'doubtful')->count(),
            'loss_count' => $activeLoans->where('risk_classification', 'loss')->count(),
        ];
    }

    /**
     * Get aging distribution trend over time
     */
    public function getAgingTrend(
        int $institutionId,
        Carbon $startDate,
        Carbon $endDate,
        string $snapshotType = 'daily'
    ): array {
        $snapshots = PortfolioSnapshot::forInstitution($institutionId)
            ->bySnapshotType($snapshotType)
            ->betweenDates($startDate, $endDate)
            ->orderBy('snapshot_date')
            ->get();

        return $snapshots->map(function ($snapshot) {
            return [
                'date' => $snapshot->snapshot_date->format('Y-m-d'),
                'aging_distribution' => $snapshot->aging_distribution,
            ];
        })->toArray();
    }

    /**
     * Get PAR trend over time
     */
    public function getPARTrend(
        int $institutionId,
        Carbon $startDate,
        Carbon $endDate,
        string $snapshotType = 'daily'
    ): array {
        $snapshots = PortfolioSnapshot::forInstitution($institutionId)
            ->bySnapshotType($snapshotType)
            ->betweenDates($startDate, $endDate)
            ->orderBy('snapshot_date')
            ->get();

        return $snapshots->map(function ($snapshot) {
            return [
                'date' => $snapshot->snapshot_date->format('Y-m-d'),
                'par_30' => (float) $snapshot->par_30_ratio,
                'par_60' => (float) $snapshot->par_60_ratio,
                'par_90' => (float) $snapshot->par_90_ratio,
            ];
        })->toArray();
    }

    /**
     * Get collection rate trend over time
     */
    public function getCollectionRateTrend(
        int $institutionId,
        Carbon $startDate,
        Carbon $endDate,
        string $snapshotType = 'daily'
    ): array {
        $snapshots = PortfolioSnapshot::forInstitution($institutionId)
            ->bySnapshotType($snapshotType)
            ->betweenDates($startDate, $endDate)
            ->orderBy('snapshot_date')
            ->get();

        return $snapshots->map(function ($snapshot) {
            return [
                'date' => $snapshot->snapshot_date->format('Y-m-d'),
                'collection_rate' => (float) $snapshot->collection_rate,
                'expected' => (float) $snapshot->expected_collections,
                'actual' => (float) $snapshot->actual_collections,
            ];
        })->toArray();
    }

    /**
     * Get comprehensive portfolio trends
     */
    public function getPortfolioTrends(
        int $institutionId,
        Carbon $startDate,
        Carbon $endDate,
        string $snapshotType = 'daily'
    ): array {
        $snapshots = PortfolioSnapshot::forInstitution($institutionId)
            ->bySnapshotType($snapshotType)
            ->betweenDates($startDate, $endDate)
            ->orderBy('snapshot_date')
            ->get();

        return [
            'par_trend' => $snapshots->map(fn($s) => [
                'date' => $s->snapshot_date->format('Y-m-d'),
                'par_30' => (float) $s->par_30_ratio,
                'par_60' => (float) $s->par_60_ratio,
                'par_90' => (float) $s->par_90_ratio,
            ])->toArray(),
            'npl_trend' => $snapshots->map(fn($s) => [
                'date' => $s->snapshot_date->format('Y-m-d'),
                'npl_ratio' => (float) $s->npl_ratio,
                'npl_amount' => (float) $s->npl_amount,
            ])->toArray(),
            'collection_trend' => $snapshots->map(fn($s) => [
                'date' => $s->snapshot_date->format('Y-m-d'),
                'rate' => (float) $s->collection_rate,
            ])->toArray(),
            'growth_trend' => $snapshots->map(fn($s) => [
                'date' => $s->snapshot_date->format('Y-m-d'),
                'rate' => (float) $s->portfolio_growth_rate,
                'disbursed' => (float) $s->new_loans_disbursed,
            ])->toArray(),
            'portfolio_size_trend' => $snapshots->map(fn($s) => [
                'date' => $s->snapshot_date->format('Y-m-d'),
                'total_loans' => $s->total_loans,
                'active_loans' => $s->active_loans,
                'outstanding' => (float) $s->total_outstanding,
            ])->toArray(),
        ];
    }

    /**
     * Get latest snapshot or compute if not exists
     */
    public function getOrComputeLatestSnapshot(
        int $institutionId,
        string $snapshotType = 'daily'
    ): PortfolioSnapshot {
        $today = now()->startOfDay();
        
        $snapshot = PortfolioSnapshot::forInstitution($institutionId)
            ->bySnapshotType($snapshotType)
            ->forDate($today)
            ->first();

        if (!$snapshot) {
            $snapshot = $this->computeSnapshot($institutionId, $today, $snapshotType);
        }

        return $snapshot;
    }
}

<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\CollectionsQueue;
use App\Models\PortfolioSnapshot;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get executive dashboard data.
     */
    public function getExecutiveDashboard(int $institutionId): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfYear = $now->copy()->startOfYear();

        return [
            'applications' => $this->getApplicationMetrics($institutionId, $startOfMonth, $now),
            'portfolio' => $this->getPortfolioMetrics($institutionId),
            'collections' => $this->getCollectionsMetrics($institutionId),
            'trends' => $this->getTrendMetrics($institutionId, 12), // Last 12 months
            'generated_at' => $now,
        ];
    }

    /**
     * Get application metrics.
     */
    protected function getApplicationMetrics(int $institutionId, Carbon $startDate, Carbon $endDate): array
    {
        $applications = Application::where('institution_id', $institutionId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('underwritingDecision')
            ->get();

        $total = $applications->count();
        $approved = $applications->filter(fn($a) => $a->underwritingDecision?->final_decision === 'approved')->count();
        $declined = $applications->filter(fn($a) => $a->underwritingDecision?->final_decision === 'declined')->count();
        $pending = $applications->filter(fn($a) => !$a->underwritingDecision || $a->underwritingDecision->final_decision === 'pending')->count();

        $approvedAmount = $applications->filter(fn($a) => $a->underwritingDecision?->final_decision === 'approved')
            ->sum(fn($a) => $a->underwritingDecision->approved_amount ?? 0);

        return [
            'total' => $total,
            'approved' => $approved,
            'declined' => $declined,
            'pending' => $pending,
            'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
            'approved_amount' => $approvedAmount,
        ];
    }

    /**
     * Get portfolio metrics.
     */
    protected function getPortfolioMetrics(int $institutionId): array
    {
        $loans = Loan::where('institution_id', $institutionId)->get();
        $activeLoans = $loans->where('status', 'active');

        $totalDisbursed = $loans->sum('disbursed_amount');
        $totalOutstanding = $activeLoans->sum('total_outstanding');
        $totalCollected = $loans->sum('total_paid');
        $totalArrears = $activeLoans->sum('arrears_amount');

        $nplLoans = $activeLoans->where('days_past_due', '>=', 90);
        $nplAmount = $nplLoans->sum('total_outstanding');
        $nplRatio = $totalOutstanding > 0 ? ($nplAmount / $totalOutstanding) * 100 : 0;

        $par30Loans = $activeLoans->where('days_past_due', '>=', 30);
        $par30Amount = $par30Loans->sum('total_outstanding');
        $par30Ratio = $totalOutstanding > 0 ? ($par30Amount / $totalOutstanding) * 100 : 0;

        return [
            'total_loans' => $loans->count(),
            'active_loans' => $activeLoans->count(),
            'total_disbursed' => $totalDisbursed,
            'total_outstanding' => $totalOutstanding,
            'total_collected' => $totalCollected,
            'total_arrears' => $totalArrears,
            'loans_in_arrears' => $activeLoans->where('days_past_due', '>', 0)->count(),
            'npl_count' => $nplLoans->count(),
            'npl_amount' => $nplAmount,
            'npl_ratio' => round($nplRatio, 2),
            'par30_count' => $par30Loans->count(),
            'par30_amount' => $par30Amount,
            'par30_ratio' => round($par30Ratio, 2),
            'avg_loan_size' => $loans->count() > 0 ? round($totalDisbursed / $loans->count(), 2) : 0,
        ];
    }

    /**
     * Get collections metrics.
     */
    protected function getCollectionsMetrics(int $institutionId): array
    {
        $queue = CollectionsQueue::where('institution_id', $institutionId)->get();

        $totalItems = $queue->count();
        $criticalItems = $queue->where('priority_level', 'critical')->count();
        $highItems = $queue->where('priority_level', 'high')->count();

        $totalArrears = $queue->sum('total_arrears');
        $avgDPD = $queue->avg('days_past_due');

        $activePTPs = \App\Models\PromiseToPay::where('institution_id', $institutionId)
            ->where('status', 'open')
            ->count();

        $duePTPs = \App\Models\PromiseToPay::where('institution_id', $institutionId)
            ->where('status', 'open')
            ->where('commitment_date', '<=', now())
            ->count();

        return [
            'total_in_queue' => $totalItems,
            'critical_items' => $criticalItems,
            'high_priority_items' => $highItems,
            'total_arrears_in_queue' => $totalArrears,
            'avg_days_past_due' => round($avgDPD ?? 0, 2),
            'active_ptps' => $activePTPs,
            'due_ptps' => $duePTPs,
        ];
    }

    /**
     * Get trend metrics (monthly data).
     */
    protected function getTrendMetrics(int $institutionId, int $months = 12): array
    {
        $startDate = now()->subMonths($months)->startOfMonth();
        $trends = [];

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $startDate->copy()->addMonths($i);
            $monthEnd = $monthStart->copy()->endOfMonth();
            $monthKey = $monthStart->format('Y-m');

            // Applications for the month
            $applications = Application::where('institution_id', $institutionId)
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->with('underwritingDecision')
                ->get();

            $approved = $applications->filter(fn($a) => $a->underwritingDecision?->final_decision === 'approved')->count();

            // Disbursements for the month
            $disbursements = Loan::where('institution_id', $institutionId)
                ->whereBetween('disbursement_date', [$monthStart, $monthEnd])
                ->sum('disbursed_amount');

            // Collections for the month
            $collections = Repayment::where('institution_id', $institutionId)
                ->whereBetween('payment_date', [$monthStart, $monthEnd])
                ->where('status', 'confirmed')
                ->sum('payment_amount');

            // Get or calculate snapshot for month-end
            $snapshot = PortfolioSnapshot::where('institution_id', $institutionId)
                ->whereDate('snapshot_date', $monthEnd)
                ->first();

            $trends[] = [
                'month' => $monthKey,
                'month_name' => $monthStart->format('M Y'),
                'applications' => $applications->count(),
                'approved_applications' => $approved,
                'disbursements' => $disbursements,
                'collections' => $collections,
                'portfolio_outstanding' => $snapshot->principal_outstanding ?? 0,
                'par30_ratio' => $snapshot->par_30_ratio ?? 0,
                'npl_ratio' => $snapshot->npl_ratio ?? 0,
            ];
        }

        return $trends;
    }

    /**
     * Get portfolio performance dashboard.
     */
    public function getPortfolioPerformance(int $institutionId): array
    {
        $loans = Loan::where('institution_id', $institutionId)->get();
        $activeLoans = $loans->where('status', 'active');

        // Aging distribution
        $agingDistribution = [
            'current' => $activeLoans->where('aging_bucket', 'current')->count(),
            'bucket_30' => $activeLoans->where('aging_bucket', 'bucket_30')->count(),
            'bucket_60' => $activeLoans->where('aging_bucket', 'bucket_60')->count(),
            'bucket_90' => $activeLoans->where('aging_bucket', 'bucket_90')->count(),
            'bucket_180' => $activeLoans->where('aging_bucket', 'bucket_180')->count(),
            'npl' => $activeLoans->where('aging_bucket', 'npl')->count(),
        ];

        // Risk classification
        $riskDistribution = [
            'performing' => $activeLoans->where('risk_classification', 'performing')->count(),
            'watch_list' => $activeLoans->where('risk_classification', 'watch_list')->count(),
            'substandard' => $activeLoans->where('risk_classification', 'substandard')->count(),
            'doubtful' => $activeLoans->where('risk_classification', 'doubtful')->count(),
            'loss' => $activeLoans->where('risk_classification', 'loss')->count(),
        ];

        // Product distribution
        $productDistribution = $loans->groupBy('loan_product_id')->map(function ($items) {
            return [
                'count' => $items->count(),
                'amount' => $items->sum('disbursed_amount'),
                'outstanding' => $items->where('status', 'active')->sum('total_outstanding'),
            ];
        });

        return [
            'aging_distribution' => $agingDistribution,
            'risk_distribution' => $riskDistribution,
            'product_distribution' => $productDistribution,
            'portfolio_metrics' => $this->getPortfolioMetrics($institutionId),
        ];
    }

    /**
     * Get collections performance dashboard.
     */
    public function getCollectionsPerformance(int $institutionId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? now()->subMonth();
        $endDate = $endDate ?? now();

        // Actions taken
        $actions = \App\Models\CollectionsAction::where('institution_id', $institutionId)
            ->whereBetween('action_date', [$startDate, $endDate])
            ->get();

        $totalActions = $actions->count();
        $successfulActions = $actions->filter(fn($a) => $a->isSuccessful())->count();

        // PTPs performance
        $ptps = \App\Models\PromiseToPay::where('institution_id', $institutionId)
            ->whereBetween('promise_date', [$startDate, $endDate])
            ->get();

        $totalPTPs = $ptps->count();
        $keptPTPs = $ptps->where('status', 'kept')->count();
        $brokenPTPs = $ptps->where('status', 'broken')->count();

        // Officer performance
        $officerStats = \App\Models\CollectionsAction::where('institution_id', $institutionId)
            ->whereBetween('action_date', [$startDate, $endDate])
            ->select('performed_by', DB::raw('COUNT(*) as action_count'))
            ->groupBy('performed_by')
            ->with('performedBy:id,name')
            ->get()
            ->map(function ($stat) {
                return [
                    'officer_id' => $stat->performed_by,
                    'officer_name' => $stat->performedBy->name ?? 'Unknown',
                    'actions' => $stat->action_count,
                ];
            });

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'actions' => [
                'total' => $totalActions,
                'successful' => $successfulActions,
                'success_rate' => $totalActions > 0 ? round(($successfulActions / $totalActions) * 100, 2) : 0,
            ],
            'promises' => [
                'total' => $totalPTPs,
                'kept' => $keptPTPs,
                'broken' => $brokenPTPs,
                'fulfillment_rate' => $totalPTPs > 0 ? round(($keptPTPs / $totalPTPs) * 100, 2) : 0,
            ],
            'officer_performance' => $officerStats,
            'queue_metrics' => $this->getCollectionsMetrics($institutionId),
        ];
    }

    /**
     * Get risk trends dashboard.
     */
    public function getRiskTrends(int $institutionId, int $months = 12): array
    {
        $snapshots = PortfolioSnapshot::where('institution_id', $institutionId)
            ->where('snapshot_type', 'monthly')
            ->orderBy('snapshot_date', 'desc')
            ->limit($months)
            ->get()
            ->reverse()
            ->values();

        $parTrend = $snapshots->map(function ($snapshot) {
            return [
                'date' => $snapshot->snapshot_date->format('Y-m'),
                'par30' => $snapshot->par_30_ratio,
                'par60' => $snapshot->par_60_ratio,
                'par90' => $snapshot->par_90_ratio,
            ];
        });

        $nplTrend = $snapshots->map(function ($snapshot) {
            return [
                'date' => $snapshot->snapshot_date->format('Y-m'),
                'npl_ratio' => $snapshot->npl_ratio,
                'npl_count' => $snapshot->npl_count,
                'npl_amount' => $snapshot->npl_amount,
            ];
        });

        $collectionRateTrend = $snapshots->map(function ($snapshot) {
            return [
                'date' => $snapshot->snapshot_date->format('Y-m'),
                'collection_rate' => $snapshot->collection_rate,
                'expected' => $snapshot->expected_collections,
                'actual' => $snapshot->actual_collections,
            ];
        });

        return [
            'par_trend' => $parTrend,
            'npl_trend' => $nplTrend,
            'collection_rate_trend' => $collectionRateTrend,
        ];
    }

    /**
     * Get monthly KPI summary.
     */
    public function getMonthlyKPIs(int $institutionId, int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return [
            'period' => $startDate->format('F Y'),
            'applications' => $this->getApplicationMetrics($institutionId, $startDate, $endDate),
            'disbursements' => $this->getDisbursementKPIs($institutionId, $startDate, $endDate),
            'collections' => $this->getCollectionKPIs($institutionId, $startDate, $endDate),
            'portfolio' => $this->getPortfolioMetrics($institutionId),
        ];
    }

    /**
     * Get disbursement KPIs.
     */
    protected function getDisbursementKPIs(int $institutionId, Carbon $startDate, Carbon $endDate): array
    {
        $disbursements = Loan::where('institution_id', $institutionId)
            ->whereBetween('disbursement_date', [$startDate, $endDate])
            ->get();

        return [
            'count' => $disbursements->count(),
            'amount' => $disbursements->sum('disbursed_amount'),
            'avg_loan_size' => $disbursements->count() > 0 ? round($disbursements->sum('disbursed_amount') / $disbursements->count(), 2) : 0,
        ];
    }

    /**
     * Get collection KPIs.
     */
    protected function getCollectionKPIs(int $institutionId, Carbon $startDate, Carbon $endDate): array
    {
        $collections = Repayment::where('institution_id', $institutionId)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->where('status', 'confirmed')
            ->get();

        return [
            'count' => $collections->count(),
            'amount' => $collections->sum('payment_amount'),
            'principal' => $collections->sum('principal_amount'),
            'interest' => $collections->sum('interest_amount'),
            'penalties' => $collections->sum('penalty_amount'),
        ];
    }
}

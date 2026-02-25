<?php

namespace App\Http\Controllers;

use App\Services\PortfolioService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PortfolioController extends Controller
{
    protected PortfolioService $portfolioService;

    public function __construct(PortfolioService $portfolioService)
    {
        $this->portfolioService = $portfolioService;
    }

    /**
     * Get current portfolio snapshot
     * GET /api/portfolio/snapshot
     */
    public function getCurrentSnapshot(Request $request)
    {
        $institutionId = $request->user()->institution_id;
        $snapshotType = $request->type ?? 'daily';

        $snapshot = $this->portfolioService->getOrComputeLatestSnapshot($institutionId, $snapshotType);

        return response()->json([
            'snapshot' => $this->formatSnapshotDetails($snapshot),
        ]);
    }

    /**
     * Get aging distribution
     * GET /api/portfolio/aging
     */
    public function getAgingDistribution(Request $request)
    {
        $institutionId = $request->user()->institution_id;
        $snapshot = $this->portfolioService->getOrComputeLatestSnapshot($institutionId, 'daily');

        return response()->json([
            'aging_distribution' => $snapshot->aging_distribution,
            'summary' => [
                'total_active_loans' => $snapshot->active_loans,
                'total_outstanding' => (float) $snapshot->total_outstanding,
                'loans_in_arrears' => $snapshot->loans_in_arrears,
                'total_arrears' => (float) $snapshot->total_arrears,
                'arrears_percentage' => $snapshot->arrears_percentage,
            ],
        ]);
    }

    /**
     * Get PAR metrics
     * GET /api/portfolio/par
     */
    public function getPARMetrics(Request $request)
    {
        $institutionId = $request->user()->institution_id;
        $snapshot = $this->portfolioService->getOrComputeLatestSnapshot($institutionId, 'daily');

        // Get previous period for comparison
        $previousDate = now()->subMonth()->startOfDay();
        $previousSnapshot = \App\Models\PortfolioSnapshot::forInstitution($institutionId)
            ->daily()
            ->forDate($previousDate)
            ->first();

        $metrics = $snapshot->par_metrics;

        // Add trends
        if ($previousSnapshot) {
            foreach (['par_30', 'par_60', 'par_90'] as $level) {
                $current = $metrics[$level]['ratio'];
                $previous = (float) $previousSnapshot->{$level . '_ratio'};
                $change = $current - $previous;
                
                $metrics[$level]['previous_ratio'] = $previous;
                $metrics[$level]['change'] = round($change, 2);
                $metrics[$level]['trend'] = $change > 0 ? 'increasing' : ($change < 0 ? 'decreasing' : 'stable');
            }
        }

        return response()->json([
            'par_metrics' => $metrics,
            'snapshot_date' => $snapshot->snapshot_date->format('Y-m-d'),
            'total_outstanding' => (float) $snapshot->total_outstanding,
        ]);
    }

    /**
     * Get NPL metrics
     * GET /api/portfolio/npl
     */
    public function getNPLMetrics(Request $request)
    {
        $institutionId = $request->user()->institution_id;
        $snapshot = $this->portfolioService->getOrComputeLatestSnapshot($institutionId, 'daily');

        // Get previous period for comparison
        $previousDate = now()->subMonth()->startOfDay();
        $previousSnapshot = \App\Models\PortfolioSnapshot::forInstitution($institutionId)
            ->daily()
            ->forDate($previousDate)
            ->first();

        $currentRatio = (float) $snapshot->npl_ratio;
        $previousRatio = $previousSnapshot ? (float) $previousSnapshot->npl_ratio : null;
        $change = $previousRatio !== null ? round($currentRatio - $previousRatio, 2) : null;

        return response()->json([
            'npl_metrics' => [
                'count' => $snapshot->npl_count,
                'amount' => (float) $snapshot->npl_amount,
                'ratio' => $currentRatio,
                'previous_ratio' => $previousRatio,
                'change' => $change,
                'trend' => $change !== null ? ($change > 0 ? 'increasing' : ($change < 0 ? 'decreasing' : 'stable')) : null,
            ],
            'provision' => [
                'total_provision' => (float) $snapshot->total_provision,
                'coverage_ratio' => (float) $snapshot->provision_coverage_ratio,
            ],
            'snapshot_date' => $snapshot->snapshot_date->format('Y-m-d'),
            'total_outstanding' => (float) $snapshot->total_outstanding,
        ]);
    }

    /**
     * Get collection rate
     * GET /api/portfolio/collection-rate
     */
    public function getCollectionRate(Request $request)
    {
        $institutionId = $request->user()->institution_id;
        $snapshot = $this->portfolioService->getOrComputeLatestSnapshot($institutionId, 'daily');

        // Get previous period for comparison
        $previousDate = now()->subMonth()->startOfDay();
        $previousSnapshot = \App\Models\PortfolioSnapshot::forInstitution($institutionId)
            ->daily()
            ->forDate($previousDate)
            ->first();

        $currentRate = (float) $snapshot->collection_rate;
        $previousRate = $previousSnapshot ? (float) $previousSnapshot->collection_rate : null;
        $change = $previousRate !== null ? round($currentRate - $previousRate, 2) : null;

        return response()->json([
            'collection_performance' => array_merge($snapshot->collection_performance, [
                'previous_rate' => $previousRate,
                'change' => $change,
                'trend' => $change !== null ? ($change > 0 ? 'improving' : ($change < 0 ? 'declining' : 'stable')) : null,
            ]),
            'snapshot_date' => $snapshot->snapshot_date->format('Y-m-d'),
        ]);
    }

    /**
     * Get portfolio trends
     * GET /api/portfolio/trends
     */
    public function getTrends(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => 'nullable|in:daily,monthly,quarterly,annual',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $endDate = $request->end_date ? Carbon::parse($request->end_date) : now();
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : $endDate->copy()->subDays(30);
        $type = $request->type ?? 'daily';

        $trends = $this->portfolioService->getPortfolioTrends($institutionId, $startDate, $endDate, $type);

        return response()->json([
            'trends' => $trends,
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'type' => $type,
            ],
        ]);
    }

    /**
     * Compute snapshot manually
     * POST /api/portfolio/compute-snapshot
     */
    public function computeSnapshot(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        $validator = Validator::make($request->all(), [
            'snapshot_date' => 'nullable|date',
            'snapshot_type' => 'nullable|in:daily,monthly,quarterly,annual',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $snapshotDate = $request->snapshot_date ? Carbon::parse($request->snapshot_date) : now();
        $snapshotType = $request->snapshot_type ?? 'daily';

        try {
            $snapshot = $this->portfolioService->computeSnapshot($institutionId, $snapshotDate, $snapshotType);

            return response()->json([
                'message' => 'Snapshot computed successfully',
                'snapshot' => $this->formatSnapshotDetails($snapshot),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to compute snapshot: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get portfolio composition
     * GET /api/portfolio/composition
     */
    public function getComposition(Request $request)
    {
        $institutionId = $request->user()->institution_id;
        $snapshot = $this->portfolioService->getOrComputeLatestSnapshot($institutionId, 'daily');

        return response()->json([
            'composition' => [
                'by_status' => $snapshot->portfolio_composition,
                'by_aging' => [
                    'current' => [
                        'count' => $snapshot->current_count,
                        'amount' => (float) $snapshot->current_amount,
                        'percentage' => $snapshot->active_loans > 0 
                            ? round(($snapshot->current_count / $snapshot->active_loans) * 100, 2)
                            : 0,
                    ],
                    'bucket_30' => [
                        'count' => $snapshot->bucket_30_count,
                        'amount' => (float) $snapshot->bucket_30_amount,
                        'percentage' => $snapshot->active_loans > 0 
                            ? round(($snapshot->bucket_30_count / $snapshot->active_loans) * 100, 2)
                            : 0,
                    ],
                    'bucket_60' => [
                        'count' => $snapshot->bucket_60_count,
                        'amount' => (float) $snapshot->bucket_60_amount,
                        'percentage' => $snapshot->active_loans > 0 
                            ? round(($snapshot->bucket_60_count / $snapshot->active_loans) * 100, 2)
                            : 0,
                    ],
                    'bucket_90' => [
                        'count' => $snapshot->bucket_90_count,
                        'amount' => (float) $snapshot->bucket_90_amount,
                        'percentage' => $snapshot->active_loans > 0 
                            ? round(($snapshot->bucket_90_count / $snapshot->active_loans) * 100, 2)
                            : 0,
                    ],
                    'bucket_180' => [
                        'count' => $snapshot->bucket_180_count,
                        'amount' => (float) $snapshot->bucket_180_amount,
                        'percentage' => $snapshot->active_loans > 0 
                            ? round(($snapshot->bucket_180_count / $snapshot->active_loans) * 100, 2)
                            : 0,
                    ],
                    'npl' => [
                        'count' => $snapshot->npl_count,
                        'amount' => (float) $snapshot->npl_amount,
                        'percentage' => $snapshot->active_loans > 0 
                            ? round(($snapshot->npl_count / $snapshot->active_loans) * 100, 2)
                            : 0,
                    ],
                ],
                'by_risk' => $snapshot->risk_distribution,
            ],
            'snapshot_date' => $snapshot->snapshot_date->format('Y-m-d'),
        ]);
    }

    /**
     * Format snapshot details
     */
    protected function formatSnapshotDetails($snapshot): array
    {
        return [
            'id' => $snapshot->id,
            'snapshot_date' => $snapshot->snapshot_date->format('Y-m-d'),
            'snapshot_type' => $snapshot->snapshot_type,
            'computed_at' => $snapshot->computed_at?->toDateTimeString(),
            'summary' => $snapshot->summary,
            'portfolio_size' => [
                'total_loans' => $snapshot->total_loans,
                'active_loans' => $snapshot->active_loans,
                'closed_loans' => $snapshot->closed_loans,
                'written_off_loans' => $snapshot->written_off_loans,
                'composition' => $snapshot->portfolio_composition,
            ],
            'portfolio_value' => [
                'total_disbursed' => (float) $snapshot->total_disbursed,
                'total_outstanding' => (float) $snapshot->total_outstanding,
                'principal_outstanding' => (float) $snapshot->principal_outstanding,
                'interest_outstanding' => (float) $snapshot->interest_outstanding,
                'penalties_outstanding' => (float) $snapshot->penalties_outstanding,
                'fees_outstanding' => (float) $snapshot->fees_outstanding,
                'breakdown' => $snapshot->outstanding_breakdown,
            ],
            'collections' => [
                'total_collected' => (float) $snapshot->total_collected,
                'breakdown' => $snapshot->collections_breakdown,
            ],
            'arrears' => [
                'total_arrears' => (float) $snapshot->total_arrears,
                'loans_in_arrears' => $snapshot->loans_in_arrears,
                'arrears_percentage' => $snapshot->arrears_percentage,
            ],
            'aging_distribution' => $snapshot->aging_distribution,
            'par_metrics' => $snapshot->par_metrics,
            'npl_metrics' => [
                'count' => $snapshot->npl_count,
                'amount' => (float) $snapshot->npl_amount,
                'ratio' => (float) $snapshot->npl_ratio,
            ],
            'collection_performance' => $snapshot->collection_performance,
            'writeoffs' => [
                'count' => $snapshot->writeoff_count,
                'amount' => (float) $snapshot->writeoff_amount,
                'ratio' => (float) $snapshot->writeoff_ratio,
            ],
            'provision' => [
                'total_provision' => (float) $snapshot->total_provision,
                'coverage_ratio' => (float) $snapshot->provision_coverage_ratio,
            ],
            'growth' => [
                'new_loans_disbursed' => (float) $snapshot->new_loans_disbursed,
                'new_loans_count' => $snapshot->new_loans_count,
                'growth_rate' => (float) $snapshot->portfolio_growth_rate,
            ],
            'averages' => $snapshot->average_metrics,
            'risk_distribution' => $snapshot->risk_distribution,
            'health' => [
                'score' => $snapshot->health_score,
                'status' => $snapshot->health_status,
                'color' => $snapshot->health_color,
            ],
        ];
    }
}

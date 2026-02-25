<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Services\ExportService;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected ExportService $exportService,
        protected DashboardService $dashboardService
    ) {}

    /**
     * Generate eligibility report PDF.
     */
    public function generateEligibilityReport(Request $request, int $institutionId, int $applicationId): BinaryFileResponse|JsonResponse
    {
        try {
            $filePath = $this->reportService->generateEligibilityReport($institutionId, $applicationId);
            
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/pdf'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate eligibility report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate bank statement report PDF.
     */
    public function generateBankStatementReport(Request $request, int $institutionId, int $statementId): BinaryFileResponse|JsonResponse
    {
        try {
            $filePath = $this->reportService->generateBankStatementReport($institutionId, $statementId);
            
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/pdf'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate bank statement report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate application summary report PDF.
     */
    public function generateApplicationSummary(Request $request, int $institutionId, int $applicationId): BinaryFileResponse|JsonResponse
    {
        try {
            $filePath = $this->reportService->generateApplicationSummaryReport($institutionId, $applicationId);
            
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/pdf'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate application summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate affordability report PDF.
     */
    public function generateAffordabilityReport(Request $request, int $institutionId, int $applicationId): BinaryFileResponse|JsonResponse
    {
        try {
            $filePath = $this->reportService->generateAffordabilityReport($institutionId, $applicationId);
            
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/pdf'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate affordability report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate monthly portfolio pack PDF.
     */
    public function generatePortfolioPack(Request $request, int $institutionId): BinaryFileResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $this->reportService->generateMonthlyPortfolioPack(
                $institutionId,
                $request->integer('year'),
                $request->integer('month')
            );
            
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/pdf'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate portfolio pack',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get approval rate report (JSON).
     */
    public function getApprovalRateReport(Request $request, int $institutionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'months' => 'nullable|integer|min:1|max:24',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $months = $request->integer('months', 6);
            $report = $this->reportService->generateApprovalRateReport($institutionId, $months);
            
            return response()->json([
                'message' => 'Approval rate report generated successfully',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate approval rate report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get risk distribution report (JSON).
     */
    public function getRiskDistributionReport(Request $request, int $institutionId): JsonResponse
    {
        try {
            $report = $this->reportService->generateRiskDistributionReport($institutionId);
            
            return response()->json([
                'message' => 'Risk distribution report generated successfully',
                'data' => $report
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate risk distribution report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get decline reason analysis (JSON).
     */
    public function getDeclineReasonAnalysis(Request $request, int $institutionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'months' => 'nullable|integer|min:1|max:24',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $months = $request->integer('months', 6);
            $analysis = $this->reportService->getDeclineReasonAnalysis($institutionId, $months);
            
            return response()->json([
                'message' => 'Decline reason analysis generated successfully',
                'data' => $analysis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate decline analysis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export applications to Excel.
     */
    public function exportApplications(Request $request, int $institutionId): BinaryFileResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:pending,approved,declined',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $this->exportService->exportApplications(
                $institutionId,
                $request->input('status'),
                $request->date('start_date'),
                $request->date('end_date')
            );
            
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export applications',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export loans to Excel.
     */
    public function exportLoans(Request $request, int $institutionId): BinaryFileResponse|JsonResponse
    {
        try {
            $filePath = $this->exportService->exportLoans($institutionId);
            
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export loans',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export repayments to Excel.
     */
    public function exportRepayments(Request $request, int $institutionId): BinaryFileResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'loan_id' => 'nullable|integer|exists:loans,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filePath = $this->exportService->exportRepayments(
                $institutionId,
                $request->date('start_date'),
                $request->date('end_date'),
                $request->integer('loan_id')
            );
            
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export repayments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export collections queue to Excel.
     */
    public function exportCollectionsQueue(Request $request, int $institutionId): BinaryFileResponse|JsonResponse
    {
        try {
            $filePath = $this->exportService->exportCollectionsQueue($institutionId);
            
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export collections queue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export portfolio summary to Excel.
     */
    public function exportPortfolioSummary(Request $request, int $institutionId): BinaryFileResponse|JsonResponse
    {
        try {
            $filePath = $this->exportService->exportPortfolioSummary($institutionId);
            
            return response()->download($filePath, basename($filePath), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export portfolio summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get executive dashboard data.
     */
    public function getExecutiveDashboard(Request $request, int $institutionId): JsonResponse
    {
        try {
            $data = $this->dashboardService->getExecutiveDashboard($institutionId);
            
            return response()->json([
                'message' => 'Executive dashboard data retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get portfolio performance dashboard.
     */
    public function getPortfolioPerformance(Request $request, int $institutionId): JsonResponse
    {
        try {
            $data = $this->dashboardService->getPortfolioPerformance($institutionId);
            
            return response()->json([
                'message' => 'Portfolio performance data retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve portfolio performance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get collections performance dashboard.
     */
    public function getCollectionsPerformance(Request $request, int $institutionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $this->dashboardService->getCollectionsPerformance(
                $institutionId,
                $request->date('start_date'),
                $request->date('end_date')
            );
            
            return response()->json([
                'message' => 'Collections performance data retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve collections performance',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get risk trends dashboard.
     */
    public function getRiskTrends(Request $request, int $institutionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'months' => 'nullable|integer|min:1|max:24',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $months = $request->integer('months', 12);
            $data = $this->dashboardService->getRiskTrends($institutionId, $months);
            
            return response()->json([
                'message' => 'Risk trends data retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve risk trends',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get monthly KPI summary.
     */
    public function getMonthlyKPIs(Request $request, int $institutionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $this->dashboardService->getMonthlyKPIs(
                $institutionId,
                $request->integer('year'),
                $request->integer('month')
            );
            
            return response()->json([
                'message' => 'Monthly KPI data retrieved successfully',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve monthly KPIs',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

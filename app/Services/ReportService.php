<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Prospect;
use App\Models\EligibilityAssessment;
use App\Models\Institution;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class ReportService
{
    /**
     * Generate eligibility decision report PDF.
     */
    public function generateEligibilityReport(int $institutionId, int $applicationId): string
    {
        $application = Application::with([
            'customer',
            'loanProduct',
            'institution',
            'bankStatementImports.analytics',
            'statementAnalytics',
            'eligibilityAssessments' => function ($query) {
                $query->latest();
            },
            'latestUnderwritingDecision'
        ])->where('institution_id', $institutionId)
          ->findOrFail($applicationId);

        $latestAssessment = $application->eligibilityAssessments->first();
        
        if (!$latestAssessment) {
            throw new \Exception('No eligibility assessment found for this application');
        }

        // Get statement analytics if available
        $analytics = $application->statementAnalytics->first() 
            ?? $application->bankStatementImports->first()?->analytics;

        $data = [
            'application' => $application,
            'assessment' => $latestAssessment,
            'customer' => $application->customer,
            'product' => $application->loanProduct,
            'institution' => $application->institution,
            'analytics' => $analytics,
            'underwritingDecision' => $application->latestUnderwritingDecision,
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.eligibility', $data);
        
        // Apply institution branding if available
        if ($application->institution->branding) {
            $pdf->setOption('margin-top', 20);
            $pdf->setOption('margin-bottom', 20);
        }

        // Ensure reports directory exists
        $reportsDir = storage_path('app/reports');
        if (!file_exists($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }

        $filename = "eligibility_report_{$application->application_number}_" . now()->format('Ymd_His') . ".pdf";
        $path = storage_path("app/reports/{$filename}");
        
        $pdf->save($path);
        
        return $path;
    }

    /**
     * Generate prospect/pre-qualification eligibility report PDF.
     */
    public function generateProspectReport(int $prospectId): string
    {
        $prospect = Prospect::with([
            'institution',
            'loanProduct',
            'statementImport.analytics',
            'eligibilityAssessment'
        ])->findOrFail($prospectId);

        $assessment = $prospect->eligibilityAssessment;
        
        if (!$assessment) {
            throw new \Exception('No eligibility assessment found for this prospect');
        }
        
        if (!$prospect->institution) {
            throw new \Exception('Institution information not found for this prospect');
        }

        $data = [
            'prospect' => $prospect,
            'assessment' => $assessment,
            'institution' => $prospect->institution,
            'product' => $prospect->loanProduct,
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.prospect-eligibility', $data);
        
        // Apply institution branding if available
        if ($prospect->institution && $prospect->institution->branding) {
            $pdf->setOption('margin-top', 20);
            $pdf->setOption('margin-bottom', 20);
        }

        // Ensure reports directory exists
        $reportsDir = storage_path('app/reports');
        if (!file_exists($reportsDir)) {
            mkdir($reportsDir, 0755, true);
        }

        $filename = "prequalification_report_{$prospect->id}_" . now()->format('Ymd_His') . ".pdf";
        $path = storage_path("app/reports/{$filename}");
        
        $pdf->save($path);
        
        return $path;
    }

    /**
     * Generate bank statement analytics report PDF.
     */
    public function generateBankStatementReport(int $applicationId): string
    {
        $application = Application::with([
            'customer',
            'institution',
            'bankStatementImports.analytics',
            'bankStatementImports.transactions'
        ])->findOrFail($applicationId);

        $bankStatement = $application->bankStatementImports->first();
        
        if (!$bankStatement || !$bankStatement->analytics) {
            throw new \Exception('No bank statement analytics found for this application');
        }

        $data = [
            'application' => $application,
            'customer' => $application->customer,
            'institution' => $application->institution,
            'statement' => $bankStatement,
            'analytics' => $bankStatement->analytics,
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.bank-statement', $data);
        
        $filename = "bank_statement_report_{$application->application_number}_" . now()->format('Ymd_His') . ".pdf";
        $path = storage_path("app/reports/{$filename}");
        
        $pdf->save($path);
        
        return $path;
    }

    /**
     * Generate application summary report PDF.
     */
    public function generateApplicationSummaryReport(int $applicationId): string
    {
        $application = Application::with([
            'customer',
            'loanProduct',
            'institution',
            'bankStatementImports.analytics',
            'eligibilityAssessments' => function ($query) {
                $query->latest()->first();
            },
            'underwritingDecision'
        ])->findOrFail($applicationId);

        $data = [
            'application' => $application,
            'customer' => $application->customer,
            'product' => $application->loanProduct,
            'institution' => $application->institution,
            'assessment' => $application->eligibilityAssessments->first(),
            'decision' => $application->underwritingDecision,
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.application-summary', $data);
        
        $filename = "application_summary_{$application->application_number}_" . now()->format('Ymd_His') . ".pdf";
        $path = storage_path("app/reports/{$filename}");
        
        $pdf->save($path);
        
        return $path;
    }

    /**
     * Generate affordability & stress test report PDF.
     */
    public function generateAffordabilityReport(int $applicationId): string
    {
        $application = Application::with([
            'customer',
            'loanProduct',
            'institution',
            'eligibilityAssessments' => function ($query) {
                $query->latest()->first();
            }
        ])->findOrFail($applicationId);

        $latestAssessment = $application->eligibilityAssessments->first();
        
        if (!$latestAssessment) {
            throw new \Exception('No eligibility assessment found for this application');
        }

        $data = [
            'application' => $application,
            'assessment' => $latestAssessment,
            'customer' => $application->customer,
            'product' => $application->loanProduct,
            'institution' => $application->institution,
            'stress_tests' => $latestAssessment->stress_test_results ?? [],
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.affordability', $data);
        
        $filename = "affordability_report_{$application->application_number}_" . now()->format('Ymd_His') . ".pdf";
        $path = storage_path("app/reports/{$filename}");
        
        $pdf->save($path);
        
        return $path;
    }

    /**
     * Generate monthly portfolio pack PDF.
     */
    public function generateMonthlyPortfolioPack(int $institutionId, int $year, int $month): string
    {
        $institution = Institution::findOrFail($institutionId);
        
        $startDate = date('Y-m-d', strtotime("$year-$month-01"));
        $endDate = date('Y-m-t', strtotime("$year-$month-01"));

        // Gather all portfolio data
        $portfolioService = app(\App\Services\PortfolioService::class);
        
        $data = [
            'institution' => $institution,
            'period' => date('F Y', strtotime($startDate)),
            'year' => $year,
            'month' => $month,
            'snapshot' => $portfolioService->getCurrentSnapshot($institutionId),
            'aging' => $portfolioService->getAgingDistribution($institutionId),
            'par_metrics' => $portfolioService->getPARMetrics($institutionId),
            'npl_metrics' => $portfolioService->getNPLMetrics($institutionId),
            'trends' => $portfolioService->getTrends($institutionId, 12), // 12 months
            'generated_at' => now(),
        ];

        $pdf = Pdf::loadView('reports.monthly-portfolio-pack', $data)
            ->setPaper('a4', 'portrait');
        
        $filename = "portfolio_pack_{$institution->code}_{$year}_{$month}_" . now()->format('Ymd_His') . ".pdf";
        $path = storage_path("app/reports/{$filename}");
        
        $pdf->save($path);
        
        return $path;
    }

    /**
     * Generate approval rate analysis report.
     */
    public function generateApprovalRateReport(int $institutionId, string $startDate, string $endDate): array
    {
        $applications = Application::where('institution_id', $institutionId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('underwritingDecision')
            ->get();

        $total = $applications->count();
        $approved = $applications->filter(function ($app) {
            return $app->underwritingDecision && 
                   $app->underwritingDecision->final_decision === 'approved';
        })->count();
        
        $declined = $applications->filter(function ($app) {
            return $app->underwritingDecision && 
                   $app->underwritingDecision->final_decision === 'declined';
        })->count();
        
        $pending = $total - $approved - $declined;

        $approvalRate = $total > 0 ? ($approved / $total) * 100 : 0;
        $declineRate = $total > 0 ? ($declined / $total) * 100 : 0;

        // By product
        $byProduct = $applications->groupBy('loan_product_id')->map(function ($apps, $productId) {
            $total = $apps->count();
            $approved = $apps->filter(function ($app) {
                return $app->underwritingDecision && 
                       $app->underwritingDecision->final_decision === 'approved';
            })->count();
            
            return [
                'product_name' => $apps->first()->loanProduct->name ?? 'Unknown',
                'total' => $total,
                'approved' => $approved,
                'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
            ];
        })->values();

        // By month
        $byMonth = $applications->groupBy(function ($app) {
            return $app->created_at->format('Y-m');
        })->map(function ($apps, $month) {
            $total = $apps->count();
            $approved = $apps->filter(function ($app) {
                return $app->underwritingDecision && 
                       $app->underwritingDecision->final_decision === 'approved';
            })->count();
            
            return [
                'month' => $month,
                'total' => $total,
                'approved' => $approved,
                'approval_rate' => $total > 0 ? round(($approved / $total) * 100, 2) : 0,
            ];
        })->values();

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'summary' => [
                'total_applications' => $total,
                'approved' => $approved,
                'declined' => $declined,
                'pending' => $pending,
                'approval_rate' => round($approvalRate, 2),
                'decline_rate' => round($declineRate, 2),
            ],
            'by_product' => $byProduct,
            'by_month' => $byMonth,
        ];
    }

    /**
     * Generate risk grade distribution report.
     */
    public function generateRiskDistributionReport(int $institutionId, string $startDate, string $endDate): array
    {
        $assessments = EligibilityAssessment::whereHas('application', function ($query) use ($institutionId) {
            $query->where('institution_id', $institutionId);
        })
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();

        $distribution = $assessments->groupBy('risk_grade')->map(function ($items, $grade) {
            return [
                'count' => $items->count(),
                'avg_dti' => round($items->avg('dti'), 2),
                'avg_dsr' => round($items->avg('dsr'), 2),
                'avg_ltv' => round($items->avg('ltv'), 2),
            ];
        });

        $avgMetrics = [
            'dti' => round($assessments->avg('dti'), 2),
            'dsr' => round($assessments->avg('dsr'), 2),
            'ltv' => round($assessments->avg('ltv'), 2),
        ];

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_assessments' => $assessments->count(),
            'distribution' => $distribution,
            'average_metrics' => $avgMetrics,
        ];
    }

    /**
     * Get decline reason analysis.
     */
    public function getDeclineReasonAnalysis(int $institutionId, string $startDate, string $endDate): array
    {
        $decisions = \App\Models\UnderwritingDecision::where('institution_id', $institutionId)
            ->where('final_decision', 'declined')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $byReason = $decisions->groupBy('decline_reason')->map(function ($items) {
            return $items->count();
        })->sortDesc();

        return [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_declines' => $decisions->count(),
            'by_reason' => $byReason,
            'top_reasons' => $byReason->take(5),
        ];
    }
}

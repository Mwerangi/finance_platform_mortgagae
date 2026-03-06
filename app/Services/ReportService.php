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

        $analytics = $prospect->statementImport?->analytics;
        
        // CRITICAL: Data Quality Check
        $dataQuality = $this->validateDataQuality($analytics);
        
        // Initialize narrative service
        $narrativeService = new NarrativeExplanationService();
        
        // Generate all narrative explanations (only if data is sufficient)
        $narratives = [];
        if ($dataQuality['is_sufficient']) {
            $narratives = $this->generateNarratives($prospect, $assessment, $analytics, $narrativeService);
        }

        $data = [
            'prospect' => $prospect,
            'assessment' => $assessment,
            'institution' => $prospect->institution,
            'product' => $prospect->loanProduct,
            'analytics' => $analytics,
            'narratives' => $narratives,
            'data_quality' => $dataQuality,
            'generated_at' => now(),
            'narrativeService' => $narrativeService, // Pass service for condition humanization
        ];

        $pdf = Pdf::loadView('reports.prospect-eligibility', $data);
        
        // Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');
        
        // Apply institution branding if available
        if ($prospect->institution && $prospect->institution->branding) {
            $pdf->setOption('margin-top', 10);
            $pdf->setOption('margin-bottom', 10);
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

    /**
     * Validate data quality for report generation.
     */
    private function validateDataQuality($analytics): array
    {
        $creditCount = $analytics ? ($analytics->total_credit_count ?? 0) : 0;
        $debitCount = $analytics ? ($analytics->total_debit_count ?? 0) : 0;
        $transactionCount = $creditCount + $debitCount;
        $monthsCount = $analytics ? ($analytics->analysis_months ?? 0) : 0;
        
        $isSufficient = $transactionCount > 0 && $creditCount > 0 && $monthsCount >= 3;
        
        $issues = [];
        
        if ($transactionCount === 0) {
            $issues[] = 'No transactions analyzed - bank statement may be empty or failed to process';
        }
        
        if ($creditCount === 0) {
            $issues[] = 'No income transactions detected - cannot verify income capacity';
        }
        
        if ($monthsCount < 3) {
            $issues[] = 'Insufficient analysis period (minimum 3 months required for reliable assessment)';
        }
        
        return [
            'is_sufficient' => $isSufficient,
            'transaction_count' => $transactionCount,
            'credit_count' => $creditCount,
            'debit_count' => $debitCount,
            'months_count' => $monthsCount,
            'issues' => $issues,
            'quality_level' => $this->determineQualityLevel($transactionCount, $monthsCount)
        ];
    }
    
    /**
     * Determine data quality level.
     */
    private function determineQualityLevel(int $transactionCount, int $monthsCount): string
    {
        if ($transactionCount === 0 || $monthsCount < 3) {
            return 'insufficient';
        }
        
        if ($transactionCount < 50 || $monthsCount < 6) {
            return 'limited';
        }
        
        if ($transactionCount >= 200 && $monthsCount >= 9) {
            return 'excellent';
        }
        
        return 'adequate';
    }

    /**
     * Generate narrative explanations for prospect report.
     */
    private function generateNarratives($prospect, $assessment, $analytics, NarrativeExplanationService $narrativeService): array
    {
        $narratives = [];
        
        // 1. Applicant Summary
        $creditCount = $analytics ? ($analytics->total_credit_count ?? 0) : 0;
        $debitCount = $analytics ? ($analytics->total_debit_count ?? 0) : 0;
        $transactionCount = $creditCount + $debitCount;
        $monthsAnalyzed = $analytics ? ($analytics->analysis_months ?? 6) : 6;
        $customerType = $prospect->customer_type ? $prospect->customer_type->value : 'salary';
        
        $narratives['applicant_summary'] = $narrativeService->explainApplicantSummary(
            (int) $transactionCount,
            (int) $monthsAnalyzed,
            $customerType
        );
        
        // 2. Income Volatility
        $volatility = $analytics ? ($analytics->cash_flow_volatility_score ?? 0) : 0;
        $narratives['income_volatility'] = $narrativeService->explainIncomeVolatility((float) $volatility);
        
        // 3. Income Stability
        $stabilityScore = $analytics ? ($analytics->income_stability_score ?? 70) : 70;
        $narratives['income_stability'] = $narrativeService->explainIncomeStability((float) $stabilityScore);
        
        // 4. Income Source
        $narratives['income_source'] = $narrativeService->explainIncomeSource($customerType);
        
        // 5. Affordability
        $avgIncome = $assessment->net_monthly_income ?? ($analytics ? ($analytics->avg_monthly_inflow ?? 0) : 0);
        $estimatedExpenses = $avgIncome * 0.35; // Rough estimate
        $disposableIncome = $avgIncome - $estimatedExpenses - ($assessment->total_monthly_debt ?? 0);
        
        $narratives['affordability'] = $narrativeService->explainAffordability(
            (float) $avgIncome,
            (float) $estimatedExpenses,
            (float) $disposableIncome,
            $assessment->dti_ratio
        );
        
        // 6. Debt Ratios
        $narratives['debt_ratios'] = $narrativeService->explainDebtRatios(
            $assessment->dti_ratio,
            $assessment->dsr_ratio,
            (float) ($assessment->total_monthly_debt ?? 0),
            $customerType
        );
        
        // 7. Loan Capacity
        $recommendedInstallment = $assessment->max_installment_from_income ?? 0;
        $maxLoanAmount = $assessment->final_max_loan ?? 0;
        $tenure = $prospect->requested_tenure ?? 240;
        $interestRate = $prospect->loanProduct ? ($prospect->loanProduct->annual_interest_rate ?? 12.0) : 12.0;
        
        $narratives['loan_capacity'] = $narrativeService->explainLoanCapacity(
            (float) $recommendedInstallment,
            (float) $maxLoanAmount,
            (int) $tenure,
            (float) $interestRate
        );
        
        // 8. Behavioral Patterns
        if ($analytics) {
            $narratives['behavioral_patterns'] = $narrativeService->explainBehavioralPatterns($analytics);
        } else {
            $narratives['behavioral_patterns'] = "Transaction behavior analysis is not available due to insufficient data.";
        }
        
        // 9. Risk Indicators
        $riskFlags = [];
        if ($analytics && ($analytics->pass_through_risk_flag ?? false)) {
            $riskFlags[] = "High pass-through transaction activity detected, indicating potential money flow irregularities that require verification";
        }
        if ($analytics && isset($analytics->summary['bounce_count']) && $analytics->summary['bounce_count'] > 0) {
            $riskFlags[] = "Bounced transactions present in account history, suggesting occasional payment difficulties";
        }
        
        $narratives['risk_indicators'] = $narrativeService->explainRiskIndicators(
            (float) $volatility,
            (float) $stabilityScore,
            (int) $monthsAnalyzed,
            $riskFlags
        );
        
        // 10. LTV Explanation (if property provided)
        if (($prospect->property_value ?? 0) > 0 && ($prospect->requested_amount ?? 0) > 0) {
            $ltvRatio = ($prospect->requested_amount / $prospect->property_value) * 100;
            $narratives['ltv'] = $narrativeService->explainLTV(
                (float) $ltvRatio,
                (float) $prospect->requested_amount,
                (float) $prospect->property_value
            );
        }
        
        // 11. Final Recommendation
        $decision = $assessment->system_decision ?? 'pending';
        $conditions = $assessment->conditions ? (is_array($assessment->conditions) ? $assessment->conditions : json_decode($assessment->conditions, true)) : [];
        
        $narratives['final_recommendation'] = $narrativeService->explainFinalRecommendation(
            $decision,
            (float) $maxLoanAmount,
            (float) $recommendedInstallment,
            $assessment->risk_grade ?? 'C',
            $conditions ?? []
        );
        
        return $narratives;
    }
}

<?php

namespace App\Jobs;

use App\Models\BankStatementImport;
use App\Models\Prospect;
use App\Models\StatementAnalytics;
use App\Services\ProspectService;
use App\Services\StatementAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ComputeAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BankStatementImport $import
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(StatementAnalyticsService $analyticsService): void
    {
        Log::info("Starting analytics computation for import #{$this->import->id}");

        try {
            // Check if import is completed
            if (!$this->import->isCompleted()) {
                throw new \Exception("Import #{$this->import->id} is not completed yet");
            }

            // Delete existing analytics if any
            $this->import->analytics()->delete();

            // Compute analytics using service
            $analyticsData = $analyticsService->computeAnalytics($this->import);

            // Create analytics record
            $analytics = StatementAnalytics::create([
                'bank_statement_import_id' => $this->import->id,
                'customer_id' => $this->import->customer_id,
                'institution_id' => $this->import->institution_id,
                'application_id' => $this->import->application_id,
                
                // Period
                'analysis_months' => $analyticsData['analysis_months'],
                'analysis_start_date' => $analyticsData['analysis_start_date'],
                'analysis_end_date' => $analyticsData['analysis_end_date'],
                
                // Monthly aggregations
                'monthly_inflows' => $analyticsData['monthly_inflows'],
                'monthly_outflows' => $analyticsData['monthly_outflows'],
                'monthly_net_surplus' => $analyticsData['monthly_net_surplus'],
                'avg_monthly_inflow' => $analyticsData['avg_monthly_inflow'],
                'avg_monthly_outflow' => $analyticsData['avg_monthly_outflow'],
                'avg_net_surplus' => $analyticsData['avg_net_surplus'],
                'opening_balance' => $analyticsData['opening_balance'],
                'closing_balance' => $analyticsData['closing_balance'],
                
                // === NEW: Transaction Summary ===
                'total_credits' => $analyticsData['total_credits'] ?? 0,
                'total_debits' => $analyticsData['total_debits'] ?? 0,
                'total_credit_count' => $analyticsData['total_credit_count'] ?? 0,
                'total_debit_count' => $analyticsData['total_debit_count'] ?? 0,
                'avg_credit_amount' => $analyticsData['avg_credit_amount'] ?? 0,
                'avg_debit_amount' => $analyticsData['avg_debit_amount'] ?? 0,
                
                // Income analysis
                'income_classification' => $analyticsData['income_classification'],
                'estimated_net_income' => $analyticsData['estimated_net_income'],
                'income_stability_score' => $analyticsData['income_stability_score'],
                'has_regular_salary' => $analyticsData['has_regular_salary'],
                'has_business_income' => $analyticsData['has_business_income'],
                'income_sources' => $analyticsData['income_sources'],
                
                // === NEW: Income Source Composition ===
                'salary_income' => $analyticsData['salary_income'] ?? 0,
                'business_income' => $analyticsData['business_income'] ?? 0,
                'loan_inflows' => $analyticsData['loan_inflows'] ?? 0,
                'bulk_deposits' => $analyticsData['bulk_deposits'] ?? 0,
                'transfer_inflows' => $analyticsData['transfer_inflows'] ?? 0,
                'other_income' => $analyticsData['other_income'] ?? 0,
                'income_composition_breakdown' => $analyticsData['income_composition_breakdown'] ?? [],
                
                // Debt analysis
                'total_debt_obligations' => $analyticsData['total_debt_obligations'],
                'estimated_monthly_debt' => $analyticsData['estimated_monthly_debt'],
                'debt_payment_count' => $analyticsData['debt_payment_count'],
                'detected_debts' => $analyticsData['detected_debts'],
                
                // === NEW: Loan Detection ===
                'detected_loan_count' => $analyticsData['detected_loan_count'] ?? 0,
                'detected_monthly_loan_repayment' => $analyticsData['detected_monthly_loan_repayment'] ?? 0,
                'detected_loans' => $analyticsData['detected_loans'] ?? [],
                'loan_stacking_detected' => $analyticsData['loan_stacking_detected'] ?? false,
                'loan_detection_confidence' => $analyticsData['loan_detection_confidence'] ?? null,
                
                // === NEW: Bulk Deposit Analysis ===
                'bulk_deposit_count' => $analyticsData['bulk_deposit_count'] ?? 0,
                'largest_single_deposit' => $analyticsData['largest_single_deposit'] ?? 0,
                'bulk_deposit_details' => $analyticsData['bulk_deposit_details'] ?? [],
                'suspicious_deposits_flagged' => $analyticsData['suspicious_deposits_flagged'] ?? false,
                
                // Risk metrics
                'cash_flow_volatility_score' => $analyticsData['cash_flow_volatility_score'],
                'negative_balance_days' => $analyticsData['negative_balance_days'],
                'bounce_count' => $analyticsData['bounce_count'],
                'gambling_transaction_count' => $analyticsData['gambling_transaction_count'],
                'large_unexplained_outflows' => $analyticsData['large_unexplained_outflows'],
                'risk_flags' => $analyticsData['risk_flags'],
                
                // === NEW: Behavioral Analysis ===
                'transaction_frequency_score' => $analyticsData['transaction_frequency_score'] ?? 0,
                'cash_withdrawal_ratio' => $analyticsData['cash_withdrawal_ratio'] ?? 0,
                'income_volatility_coefficient' => $analyticsData['income_volatility_coefficient'] ?? 0,
                'transaction_pattern' => $analyticsData['transaction_pattern'] ?? null,
                'behavioral_risk_level' => $analyticsData['behavioral_risk_level'] ?? null,
                'behavioral_flags' => $analyticsData['behavioral_flags'] ?? [],
                
                // Overall assessment
                'overall_risk_assessment' => $analyticsData['overall_risk_assessment'],
                'debt_to_income_ratio' => $analyticsData['debt_to_income_ratio'],
                'disposable_income_ratio' => $analyticsData['disposable_income_ratio'],
                
                // Metadata
                'computed_at' => now(),
                'computed_by' => Auth::id(),
            ]);

            Log::info("Analytics computation completed for import #{$this->import->id}", [
                'analytics_id' => $analytics->id,
                'risk_assessment' => $analytics->overall_risk_assessment,
                'income_classification' => $analytics->income_classification->value,
                'dti_ratio' => $analytics->debt_to_income_ratio,
                'loan_stacking_detected' => $analytics->loan_stacking_detected,
                'detected_loan_count' => $analytics->detected_loan_count,
                'behavioral_risk_level' => $analytics->behavioral_risk_level,
            ]);

            // Check if this is for a prospect and run eligibility assessment
            $prospect = Prospect::where('bank_statement_import_id', $this->import->id)->first();
            
            if ($prospect) {
                Log::info("Running eligibility assessment for prospect #{$prospect->id}");
                
                try {
                    $prospectService = app(ProspectService::class);
                    $prospectService->runEligibilityAssessment($prospect, $analytics);
                    
                    Log::info("Eligibility assessment completed for prospect #{$prospect->id}");
                } catch (\Exception $e) {
                    Log::error("Eligibility assessment failed for prospect #{$prospect->id}: " . $e->getMessage());
                    // Don't throw - analytics computation succeeded
                }
            }

        } catch (\Exception $e) {
            Log::error("Analytics computation failed for import #{$this->import->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Analytics computation job failed permanently for import #{$this->import->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

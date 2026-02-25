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
                'analysis_months' => $analyticsData['analysis_months'],
                'analysis_start_date' => $analyticsData['analysis_start_date'],
                'analysis_end_date' => $analyticsData['analysis_end_date'],
                'monthly_inflows' => $analyticsData['monthly_inflows'],
                'monthly_outflows' => $analyticsData['monthly_outflows'],
                'monthly_net_surplus' => $analyticsData['monthly_net_surplus'],
                'avg_monthly_inflow' => $analyticsData['avg_monthly_inflow'],
                'avg_monthly_outflow' => $analyticsData['avg_monthly_outflow'],
                'avg_net_surplus' => $analyticsData['avg_net_surplus'],
                'opening_balance' => $analyticsData['opening_balance'],
                'closing_balance' => $analyticsData['closing_balance'],
                'income_classification' => $analyticsData['income_classification'],
                'estimated_net_income' => $analyticsData['estimated_net_income'],
                'income_stability_score' => $analyticsData['income_stability_score'],
                'has_regular_salary' => $analyticsData['has_regular_salary'],
                'has_business_income' => $analyticsData['has_business_income'],
                'income_sources' => $analyticsData['income_sources'],
                'total_debt_obligations' => $analyticsData['total_debt_obligations'],
                'estimated_monthly_debt' => $analyticsData['estimated_monthly_debt'],
                'debt_payment_count' => $analyticsData['debt_payment_count'],
                'detected_debts' => $analyticsData['detected_debts'],
                'cash_flow_volatility_score' => $analyticsData['cash_flow_volatility_score'],
                'negative_balance_days' => $analyticsData['negative_balance_days'],
                'bounce_count' => $analyticsData['bounce_count'],
                'gambling_transaction_count' => $analyticsData['gambling_transaction_count'],
                'large_unexplained_outflows' => $analyticsData['large_unexplained_outflows'],
                'risk_flags' => $analyticsData['risk_flags'],
                'overall_risk_assessment' => $analyticsData['overall_risk_assessment'],
                'debt_to_income_ratio' => $analyticsData['debt_to_income_ratio'],
                'disposable_income_ratio' => $analyticsData['disposable_income_ratio'],
                'computed_at' => now(),
                'computed_by' => Auth::id(), // May be null if run via queue
            ]);

            Log::info("Analytics computation completed for import #{$this->import->id}", [
                'analytics_id' => $analytics->id,
                'risk_assessment' => $analytics->overall_risk_assessment,
                'income_classification' => $analytics->income_classification->value,
                'dti_ratio' => $analytics->debt_to_income_ratio,
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

<?php

namespace App\Jobs;

use App\Models\Application;
use App\Models\EligibilityAssessment;
use App\Services\EligibilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RunEligibilityAssessmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Application $application,
        public ?array $stressTestParams = null,
        public ?int $assessedBy = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(EligibilityService $eligibilityService): void
    {
        Log::info("Running eligibility assessment for application #{$this->application->id}", [
            'stress_test' => !empty($this->stressTestParams),
            'params' => $this->stressTestParams,
        ]);

        try {
            // Run eligibility assessment
            $assessmentData = $eligibilityService->assessEligibility(
                $this->application,
                $this->stressTestParams
            );

            // Get statement analytics
            $analytics = $this->application->statementAnalytics()->latest()->first();

            // Create eligibility assessment record
            $assessment = EligibilityAssessment::create([
                'application_id' => $this->application->id,
                'customer_id' => $this->application->customer_id,
                'institution_id' => $this->application->institution_id,
                'loan_product_id' => $this->application->loan_product_id,
                'statement_analytics_id' => $analytics?->id,
                'assessment_version' => $assessmentData['assessment_version'],
                'assessment_type' => $assessmentData['assessment_type'],
                'requested_amount' => $assessmentData['requested_amount'],
                'requested_tenure_months' => $assessmentData['requested_tenure_months'],
                'property_value' => $assessmentData['property_value'],
                'income_classification' => $assessmentData['income_classification'],
                'gross_monthly_income' => $assessmentData['gross_monthly_income'],
                'net_monthly_income' => $assessmentData['net_monthly_income'],
                'income_stability_score' => $assessmentData['income_stability_score'],
                'total_monthly_debt' => $assessmentData['total_monthly_debt'],
                'detected_debt_count' => $assessmentData['detected_debt_count'],
                'dti_ratio' => $assessmentData['dti_ratio'],
                'dsr_ratio' => $assessmentData['dsr_ratio'],
                'ltv_ratio' => $assessmentData['ltv_ratio'],
                'proposed_installment' => $assessmentData['proposed_installment'],
                'net_disposable_income' => $assessmentData['net_disposable_income'],
                'net_surplus_after_loan' => $assessmentData['net_surplus_after_loan'],
                'business_safety_factor' => $assessmentData['business_safety_factor'],
                'max_installment_from_income' => $assessmentData['max_installment_from_income'],
                'max_loan_from_affordability' => $assessmentData['max_loan_from_affordability'],
                'max_loan_from_ltv' => $assessmentData['max_loan_from_ltv'],
                'final_max_loan' => $assessmentData['final_max_loan'],
                'optimal_tenure_months' => $assessmentData['optimal_tenure_months'],
                'risk_grade' => $assessmentData['risk_grade'],
                'risk_score' => $assessmentData['risk_score'],
                'risk_factors' => $assessmentData['risk_factors'],
                'cash_flow_volatility' => $assessmentData['cash_flow_volatility'],
                'system_decision' => $assessmentData['system_decision'],
                'decision_reason' => $assessmentData['decision_reason'],
                'policy_breaches' => $assessmentData['policy_breaches'],
                'conditions' => $assessmentData['conditions'],
                'is_recommendable' => $assessmentData['is_recommendable'],
                'is_stress_test' => $assessmentData['is_stress_test'],
                'stress_scenario' => $assessmentData['stress_scenario'],
                'stress_test_params' => $assessmentData['stress_test_params'],
                'stressed_installment' => $assessmentData['stressed_installment'],
                'stressed_net_surplus' => $assessmentData['stressed_net_surplus'],
                'passes_stress_test' => $assessmentData['passes_stress_test'],
                'interest_method' => $assessmentData['interest_method'],
                'interest_rate' => $assessmentData['interest_rate'],
                'monthly_interest_rate' => $assessmentData['monthly_interest_rate'],
                'total_interest' => $assessmentData['total_interest'],
                'total_repayment' => $assessmentData['total_repayment'],
                'effective_apr' => $assessmentData['effective_apr'],
                'assessed_by' => $this->assessedBy ?? Auth::id(),
                'assessed_at' => now(),
                'calculation_details' => $assessmentData['calculation_details'],
            ]);

            Log::info("Eligibility assessment completed for application #{$this->application->id}", [
                'assessment_id' => $assessment->id,
                'decision' => $assessment->system_decision,
                'risk_grade' => $assessment->risk_grade,
                'final_max_loan' => $assessment->final_max_loan,
                'is_recommendable' => $assessment->is_recommendable,
            ]);

        } catch (\Exception $e) {
            Log::error("Eligibility assessment failed for application #{$this->application->id}", [
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
        Log::error("Eligibility assessment job failed permanently for application #{$this->application->id}", [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

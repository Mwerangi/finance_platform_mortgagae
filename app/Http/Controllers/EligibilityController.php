<?php

namespace App\Http\Controllers;

use App\Jobs\RunEligibilityAssessmentJob;
use App\Models\Application;
use App\Models\EligibilityAssessment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EligibilityController extends Controller
{
    /**
     * Run eligibility assessment for an application
     */
    public function runAssessment(Request $request, Application $application): JsonResponse
    {
        // Ensure user has access to this institution's data
        $user = Auth::user();
        if ($application->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if application has statement analytics
        $analytics = $application->statementAnalytics()->latest()->first();
        if (!$analytics) {
            return response()->json([
                'message' => 'Cannot run eligibility assessment. No bank statement analytics found for this application.',
            ], 400);
        }

        // Dispatch eligibility assessment job
        RunEligibilityAssessmentJob::dispatch($application, null, $user->id);

        return response()->json([
            'message' => 'Eligibility assessment queued successfully',
            'application_id' => $application->id,
        ], 202);
    }

    /**
     * Get latest eligibility assessment for an application
     */
    public function getLatest(Application $application): JsonResponse
    {
        // Ensure user has access to this institution's data
        $user = Auth::user();
        if ($application->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $assessment = $application->eligibilityAssessments()
            ->where('is_stress_test', false)
            ->latest('assessed_at')
            ->with(['customer', 'loanProduct', 'statementAnalytics', 'assessor'])
            ->first();

        if (!$assessment) {
            return response()->json([
                'message' => 'No eligibility assessment found for this application',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatAssessment($assessment),
        ]);
    }

    /**
     * Get eligibility assessment history for an application
     */
    public function getHistory(Request $request, Application $application): JsonResponse
    {
        // Ensure user has access to this institution's data
        $user = Auth::user();
        if ($application->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $includeStressTests = $request->boolean('include_stress_tests', false);

        $query = $application->eligibilityAssessments()
            ->with(['assessor'])
            ->latest('assessed_at');

        if (!$includeStressTests) {
            $query->where('is_stress_test', false);
        }

        $assessments = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $assessments->map(fn($a) => $this->formatAssessment($a, false)),
            'meta' => [
                'current_page' => $assessments->currentPage(),
                'total' => $assessments->total(),
                'per_page' => $assessments->perPage(),
                'last_page' => $assessments->lastPage(),
            ],
        ]);
    }

    /**
     * Run stress test
     */
    public function runStressTest(Request $request, Application $application): JsonResponse
    {
        // Ensure user has access to this institution's data
        $user = Auth::user();
        if ($application->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'scenario_type' => 'required|in:income_shock,rate_increase,combined',
            'income_shock_percent' => 'nullable|numeric|min:0|max:50',
            'rate_increase_percent' => 'nullable|numeric|min:0|max:10',
        ]);

        // Build stress test parameters
        $stressParams = [];

        if (in_array($validated['scenario_type'], ['income_shock', 'combined'])) {
            $stressParams['income_shock_percent'] = $validated['income_shock_percent'] ?? 20;
        }

        if (in_array($validated['scenario_type'], ['rate_increase', 'combined'])) {
            $stressParams['rate_increase_percent'] = $validated['rate_increase_percent'] ?? 3;
        }

        // Dispatch stress test job
        RunEligibilityAssessmentJob::dispatch($application, $stressParams, $user->id);

        return response()->json([
            'message' => 'Stress test queued successfully',
            'application_id' => $application->id,
            'scenario_type' => $validated['scenario_type'],
            'parameters' => $stressParams,
        ], 202);
    }

    /**
     * Get maximum loan recommendations
     */
    public function getMaxLoanRecommendations(Application $application): JsonResponse
    {
        // Ensure user has access to this institution's data
        $user = Auth::user();
        if ($application->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $latestAssessment = $application->eligibilityAssessments()
            ->where('is_stress_test', false)
            ->latest('assessed_at')
            ->first();

        if (!$latestAssessment) {
            return response()->json([
                'message' => 'No eligibility assessment found. Please run assessment first.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'requested_amount' => $latestAssessment->requested_amount,
                'final_max_loan' => $latestAssessment->final_max_loan,
                'max_loan_from_affordability' => $latestAssessment->max_loan_from_affordability,
                'max_loan_from_ltv' => $latestAssessment->max_loan_from_ltv,
                'affordability_headroom' => $latestAssessment->affordability_headroom,
                'utilization_ratio' => $latestAssessment->utilization_ratio,
                'optimal_tenure_months' => $latestAssessment->optimal_tenure_months,
                'recommendation' => $this->buildRecommendation($latestAssessment),
            ],
        ]);
    }

    /**
     * Get eligibility summary for an application
     */
    public function getSummary(Application $application): JsonResponse
    {
        // Ensure user has access to this institution's data
        $user = Auth::user();
        if ($application->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $latestAssessment = $application->eligibilityAssessments()
            ->where('is_stress_test', false)
            ->latest('assessed_at')
            ->first();

        if (!$latestAssessment) {
            return response()->json([
                'message' => 'No eligibility assessment found',
            ], 404);
        }

        // Get stress test results
        $stressTests = $application->eligibilityAssessments()
            ->where('is_stress_test', true)
            ->latest('assessed_at')
            ->limit(3)
            ->get();

        return response()->json([
            'data' => [
                'application' => [
                    'id' => $application->id,
                    'application_number' => $application->application_number,
                    'status' => $application->status->value,
                    'requested_amount' => $application->requested_amount,
                    'requested_tenure' => $application->requested_tenure,
                ],
                'assessment' => [
                    'decision' => $latestAssessment->system_decision,
                    'decision_reason' => $latestAssessment->decision_reason,
                    'is_recommendable' => $latestAssessment->is_recommendable,
                    'risk_grade' => $latestAssessment->risk_grade,
                    'risk_score' => $latestAssessment->risk_score,
                    'assessed_at' => $latestAssessment->assessed_at,
                ],
                'financial_metrics' => [
                    'dti_ratio' => $latestAssessment->dti_ratio,
                    'dsr_ratio' => $latestAssessment->dsr_ratio,
                    'ltv_ratio' => $latestAssessment->ltv_ratio,
                    'proposed_installment' => $latestAssessment->proposed_installment,
                    'net_surplus_after_loan' => $latestAssessment->net_surplus_after_loan,
                    'income_stability_score' => $latestAssessment->income_stability_score,
                ],
                'max_loan' => [
                    'final_max_loan' => $latestAssessment->final_max_loan,
                    'affordability_headroom' => $latestAssessment->affordability_headroom,
                    'utilization_ratio' => $latestAssessment->utilization_ratio,
                ],
                'policy_status' => [
                    'breach_count' => $latestAssessment->policy_breach_count,
                    'condition_count' => $latestAssessment->condition_count,
                    'breaches' => $latestAssessment->policy_breaches,
                    'conditions' => $latestAssessment->conditions,
                ],
                'stress_tests' => $stressTests->map(function ($test) {
                    return [
                        'scenario' => $test->stress_scenario,
                        'passes' => $test->passes_stress_test,
                        'stressed_surplus' => $test->stressed_net_surplus,
                        'assessed_at' => $test->assessed_at,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Format assessment for response
     */
    private function formatAssessment(EligibilityAssessment $assessment, bool $includeDetails = true): array
    {
        $data = [
            'id' => $assessment->id,
            'application_id' => $assessment->application_id,
            'assessment_type' => $assessment->assessment_type,
            'assessed_at' => $assessment->assessed_at,
            'decision' => [
                'system_decision' => $assessment->system_decision,
                'decision_reason' => $assessment->decision_reason,
                'is_recommendable' => $assessment->is_recommendable,
                'decision_color' => $assessment->decision_color,
            ],
            'risk' => [
                'risk_grade' => $assessment->risk_grade,
                'risk_score' => $assessment->risk_score,
                'risk_grade_color' => $assessment->risk_grade_color,
            ],
            'financial' => [
                'requested_amount' => $assessment->requested_amount,
                'final_max_loan' => $assessment->final_max_loan,
                'proposed_installment' => $assessment->proposed_installment,
                'dti_ratio' => $assessment->dti_ratio,
                'dsr_ratio' => $assessment->dsr_ratio,
                'ltv_ratio' => $assessment->ltv_ratio,
            ],
        ];

        if ($includeDetails) {
            $data['income_analysis'] = [
                'income_classification' => $assessment->income_classification,
                'gross_monthly_income' => $assessment->gross_monthly_income,
                'net_monthly_income' => $assessment->net_monthly_income,
                'income_stability_score' => $assessment->income_stability_score,
                'business_safety_factor' => $assessment->business_safety_factor,
            ];

            $data['debt_analysis'] = [
                'total_monthly_debt' => $assessment->total_monthly_debt,
                'detected_debt_count' => $assessment->detected_debt_count,
                'net_disposable_income' => $assessment->net_disposable_income,
                'net_surplus_after_loan' => $assessment->net_surplus_after_loan,
            ];

            $data['max_loan_breakdown'] = [
                'max_installment_from_income' => $assessment->max_installment_from_income,
                'max_loan_from_affordability' => $assessment->max_loan_from_affordability,
                'max_loan_from_ltv' => $assessment->max_loan_from_ltv,
                'final_max_loan' => $assessment->final_max_loan,
                'optimal_tenure_months' => $assessment->optimal_tenure_months,
                'affordability_headroom' => $assessment->affordability_headroom,
                'utilization_ratio' => $assessment->utilization_ratio,
            ];

            $data['policy_status'] = [
                'breach_count' => $assessment->policy_breach_count,
                'condition_count' => $assessment->condition_count,
                'policy_breaches' => $assessment->policy_breaches,
                'conditions' => $assessment->conditions,
            ];

            $data['amortization'] = [
                'interest_method' => $assessment->interest_method,
                'interest_rate' => $assessment->interest_rate,
                'total_interest' => $assessment->total_interest,
                'total_repayment' => $assessment->total_repayment,
                'effective_apr' => $assessment->effective_apr,
            ];

            if ($assessment->isStressTest()) {
                $data['stress_test'] = [
                    'scenario' => $assessment->stress_scenario,
                    'parameters' => $assessment->stress_test_params,
                    'stressed_installment' => $assessment->stressed_installment,
                    'stressed_net_surplus' => $assessment->stressed_net_surplus,
                    'passes_stress_test' => $assessment->passes_stress_test,
                ];
            }

            if ($assessment->relationLoaded('customer')) {
                $data['customer'] = [
                    'id' => $assessment->customer->id,
                    'full_name' => $assessment->customer->full_name,
                    'customer_number' => $assessment->customer->customer_number,
                ];
            }

            if ($assessment->relationLoaded('assessor') && $assessment->assessor) {
                $data['assessor'] = [
                    'id' => $assessment->assessor->id,
                    'name' => $assessment->assessor->name,
                ];
            }
        }

        return $data;
    }

    /**
     * Build recommendation text
     */
    private function buildRecommendation(EligibilityAssessment $assessment): string
    {
        if ($assessment->exceedsMaxLoan()) {
            $difference = $assessment->requested_amount - $assessment->final_max_loan;
            return "Requested amount exceeds maximum affordable loan by TZS " . number_format($difference, 2) . 
                   ". Recommend reducing loan amount to TZS " . number_format($assessment->final_max_loan, 2) . 
                   " or extending tenure to " . $assessment->optimal_tenure_months . " months.";
        }

        if ($assessment->utilization_ratio < 50) {
            return "Customer has significant affordability headroom (TZS " . 
                   number_format($assessment->affordability_headroom, 2) . 
                   "). Could consider increasing loan amount if needed.";
        }

        if ($assessment->utilization_ratio > 90) {
            return "Customer is utilizing " . $assessment->utilization_ratio . 
                   "% of maximum affordable amount. Consider stress testing before approval.";
        }

        return "Loan amount is within comfortable affordability range.";
    }
}

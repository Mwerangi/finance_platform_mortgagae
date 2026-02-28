<?php

namespace App\Services;

use App\Models\Application;
use App\Models\LoanProduct;
use App\Models\StatementAnalytics;
use Illuminate\Support\Facades\Log;

class EligibilityService
{
    /**
     * Run comprehensive eligibility assessment
     */
    public function assessEligibility(Application $application, ?array $stressTestParams = null): array
    {
        Log::info("Running eligibility assessment for application #{$application->id}");

        // Load required relationships only if not already loaded
        if (!$application->relationLoaded('customer') || !$application->relationLoaded('loanProduct') || !$application->relationLoaded('statementAnalytics')) {
            $application->load(['customer', 'loanProduct', 'statementAnalytics']);
        }
        
        // Get statement analytics - use loaded relationship if available
        $analytics = $application->relationLoaded('statementAnalytics') && $application->statementAnalytics
            ? $application->statementAnalytics
            : $application->statementAnalytics()->latest()->first();
        
        if (!$analytics) {
            throw new \Exception("No statement analytics found for application #{$application->id}");
        }

        // Get loan product
        $loanProduct = $application->loanProduct;

        // Requested loan details
        $requestedAmount = $application->requested_amount;
        $requestedTenure = $application->requested_tenure;
        $propertyValue = $application->property_value;

        // Income analysis
        $incomeData = $this->analyzeIncome($analytics, $stressTestParams);

        // Debt analysis
        $debtData = $this->analyzeDebt($analytics);

        // Calculate proposed installment
        $proposedInstallment = $this->calculateInstallment(
            $requestedAmount,
            $requestedTenure,
            $loanProduct->annual_interest_rate ?? 18.0,
            'reducing_balance' // Default method
        );

        // Calculate ratios
        $ratios = $this->calculateRatios(
            $incomeData,
            $debtData,
            $proposedInstallment,
            $propertyValue,
            $requestedAmount
        );

        // Calculate maximum loan
        $maxLoanData = $this->calculateMaximumLoan(
            $incomeData,
            $debtData,
            $loanProduct,
            $propertyValue,
            $analytics
        );

        // Calculate risk grade
        $riskData = $this->calculateRiskGrade($analytics, $ratios, $incomeData);

        // Generate risk and loan explanations
        $riskExplanation = $this->generateRiskExplanation(
            $riskData,
            $maxLoanData,
            $analytics,
            $ratios,
            $incomeData,
            $requestedAmount
        );
        
        // Debug logging
        \Log::info('Risk Explanation Generated', [
            'has_data' => !empty($riskExplanation),
            'keys' => $riskExplanation ? array_keys($riskExplanation) : [],
            'driver_count' => isset($riskExplanation['primary_risk_drivers']) ? count($riskExplanation['primary_risk_drivers']) : 0
        ]);

        // Evaluate policy rules
        $policyResult = $this->evaluatePolicyRules(
            $ratios,
            $riskData,
            $incomeData,
            $loanProduct,
            $analytics
        );
        
        // Generate final recommendation
        $finalRecommendation = $this->generateFinalRecommendation(
            $policyResult,
            $ratios,
            $riskData,
            $incomeData,
            $maxLoanData,
            $requestedAmount,
            $analytics
        );

        // Calculate amortization details
        $amortization = $this->calculateAmortizationDetails(
            $requestedAmount,
            $requestedTenure,
            $loanProduct->annual_interest_rate ?? 18.0,
            'reducing_balance' // Default method
        );

        // Handle stress test
        $stressTestData = null;
        if ($stressTestParams) {
            $stressTestData = $this->runStressTest($incomeData, $proposedInstallment, $stressTestParams);
        }

        return [
            // Assessment metadata
            'assessment_type' => $stressTestParams ? 'stress_test' : 'initial',
            'assessment_version' => '1.0',
            
            // Requested details
            'requested_amount' => $requestedAmount,
            'requested_tenure_months' => $requestedTenure,
            'property_value' => $propertyValue,
            
            // Income & debt
            'income_classification' => $analytics->income_classification->value,
            'gross_monthly_income' => $incomeData['gross_income'],
            'net_monthly_income' => $incomeData['net_income'],
            'income_stability_score' => $analytics->income_stability_score,
            'total_monthly_debt' => $debtData['total_monthly_debt'],
            'detected_debt_count' => $debtData['debt_count'],
            
            // Ratios
            'dti_ratio' => $ratios['dti'],
            'dsr_ratio' => $ratios['dsr'],
            'ltv_ratio' => $ratios['ltv'],
            'proposed_installment' => $proposedInstallment,
            'net_disposable_income' => $incomeData['disposable_income'],
            'net_surplus_after_loan' => $incomeData['surplus_after_loan'],
            'business_safety_factor' => $incomeData['safety_factor'],
            
            // Maximum loan
            'max_installment_from_income' => $maxLoanData['max_installment'],
            'max_loan_from_affordability' => $maxLoanData['max_from_affordability'],
            'max_loan_from_ltv' => $maxLoanData['max_from_ltv'],
            'final_max_loan' => $maxLoanData['final_max_loan'],
            'optimal_tenure_months' => $maxLoanData['optimal_tenure'],
            
            // Risk assessment
            'risk_grade' => $riskData['grade'],
            'risk_score' => $riskData['score'],
            'risk_factors' => $riskData['factors'],
            'cash_flow_volatility' => $analytics->cash_flow_volatility_score,
            'risk_explanation' => $riskExplanation,
            
            // Decision
            'system_decision' => $policyResult['decision'],
            'decision_reason' => $policyResult['reason'],
            'policy_breaches' => $policyResult['breaches'],
            'conditions' => $policyResult['conditions'],
            'is_recommendable' => $policyResult['is_recommendable'],
            
            // Final Recommendation
            'final_recommendation' => $finalRecommendation,
            
            // Stress test
            'is_stress_test' => !empty($stressTestParams),
            'stress_scenario' => $stressTestData['scenario'] ?? null,
            'stress_test_params' => $stressTestParams,
            'stressed_installment' => $stressTestData['stressed_installment'] ?? null,
            'stressed_net_surplus' => $stressTestData['stressed_surplus'] ?? null,
            'passes_stress_test' => $stressTestData['passes'] ?? null,
            
            // Amortization
            'interest_method' => 'reducing_balance',
            'interest_rate' => $loanProduct->annual_interest_rate ?? 18.0,
            'monthly_interest_rate' => $amortization['monthly_rate'],
            'total_interest' => $amortization['total_interest'],
            'total_repayment' => $amortization['total_repayment'],
            'effective_apr' => $amortization['effective_apr'],
            
            // Audit trail
            'calculation_details' => [
                'income' => $incomeData,
                'debt' => $debtData,
                'ratios' => $ratios,
                'max_loan' => $maxLoanData,
                'risk' => $riskData,
                'policy' => $policyResult,
            ],
        ];
    }

    /**
     * Analyze income from statement analytics
     */
    private function analyzeIncome(StatementAnalytics $analytics, ?array $stressTestParams = null): array
    {
        $grossIncome = $analytics->avg_monthly_inflow;
        $estimatedIncome = $analytics->estimated_net_income;

        // Apply stress test if requested
        if ($stressTestParams && isset($stressTestParams['income_shock_percent'])) {
            $shockPercent = $stressTestParams['income_shock_percent'];
            $grossIncome *= (1 - $shockPercent / 100);
            $estimatedIncome *= (1 - $shockPercent / 100);
        }

        // Apply business safety factor for business/mixed income
        $safetyFactor = 1.0;
        if (in_array($analytics->income_classification->value, ['business', 'mixed'])) {
            $safetyFactor = 0.7; // 70% of business income considered
            $netIncome = $estimatedIncome * $safetyFactor;
        } else {
            $netIncome = $estimatedIncome;
        }

        return [
            'gross_income' => round($grossIncome, 2),
            'net_income' => round($netIncome, 2),
            'estimated_income' => round($estimatedIncome, 2),
            'safety_factor' => $safetyFactor,
            'disposable_income' => 0, // Will be set after debt calculation
            'surplus_after_loan' => 0, // Will be set after installment calculation
        ];
    }

    /**
     * Analyze debt from statement analytics
     */
    private function analyzeDebt(StatementAnalytics $analytics): array
    {
        return [
            'total_monthly_debt' => round($analytics->estimated_monthly_debt, 2),
            'debt_count' => count($analytics->detected_debts ?? []),
            'detected_debts' => $analytics->detected_debts ?? [],
        ];
    }

    /**
     * Calculate installment based on interest method
     */
    public function calculateInstallment(
        float $principal,
        int $tenureMonths,
        float $annualRate,
        string $method
    ): float {
        if ($method === 'reducing_balance') {
            return $this->calculateReducingBalanceInstallment($principal, $tenureMonths, $annualRate);
        } else {
            return $this->calculateFlatRateInstallment($principal, $tenureMonths, $annualRate);
        }
    }

    /**
     * Calculate reducing balance monthly installment
     */
    private function calculateReducingBalanceInstallment(
        float $principal,
        int $tenureMonths,
        float $annualRate
    ): float {
        $monthlyRate = $annualRate / 100 / 12;
        
        if ($monthlyRate == 0) {
            return $principal / $tenureMonths;
        }

        // PMT formula: P * [r(1+r)^n] / [(1+r)^n - 1]
        $installment = $principal * 
            ($monthlyRate * pow(1 + $monthlyRate, $tenureMonths)) / 
            (pow(1 + $monthlyRate, $tenureMonths) - 1);

        return round($installment, 2);
    }

    /**
     * Calculate flat rate monthly installment
     */
    private function calculateFlatRateInstallment(
        float $principal,
        int $tenureMonths,
        float $annualRate
    ): float {
        $totalInterest = ($principal * $annualRate / 100) * ($tenureMonths / 12);
        $totalRepayment = $principal + $totalInterest;
        
        return round($totalRepayment / $tenureMonths, 2);
    }

    /**
     * Calculate financial ratios
     */
    private function calculateRatios(
        array &$incomeData,
        array $debtData,
        float $proposedInstallment,
        ?float $propertyValue,
        float $requestedAmount
    ): array {
        $netIncome = $incomeData['net_income'];
        $totalDebt = $debtData['total_monthly_debt'];

        // Disposable income after existing debts
        $disposableIncome = $netIncome - $totalDebt;
        $incomeData['disposable_income'] = round($disposableIncome, 2);

        // Surplus after proposed loan
        $surplusAfterLoan = $disposableIncome - $proposedInstallment;
        $incomeData['surplus_after_loan'] = round($surplusAfterLoan, 2);

        // DTI: Total debt / Income (including new loan)
        $dti = $netIncome > 0 ? (($totalDebt + $proposedInstallment) / $netIncome) * 100 : 0;

        // DSR: New installment / Disposable income
        $dsr = $disposableIncome > 0 ? ($proposedInstallment / $disposableIncome) * 100 : 0;

        // LTV: Loan / Property Value
        $ltv = null;
        if ($propertyValue && $propertyValue > 0) {
            $ltv = ($requestedAmount / $propertyValue) * 100;
        }

        return [
            'dti' => round($dti, 2),
            'dsr' => round($dsr, 2),
            'ltv' => $ltv ? round($ltv, 2) : null,
        ];
    }

    /**
     * Calculate maximum affordable loan
     */
    private function calculateMaximumLoan(
        array $incomeData,
        array $debtData,
        LoanProduct $loanProduct,
        ?float $propertyValue,
        StatementAnalytics $analytics
    ): array {
        $disposableIncome = $incomeData['net_income'] - $debtData['total_monthly_debt'];

        // Maximum installment: 50% of disposable income (configurable per product)
        $maxInstallmentRatio = $loanProduct->max_dsr_ratio ?? 50;
        $maxInstallment = $disposableIncome * ($maxInstallmentRatio / 100);

        // Maximum loan from affordability
        $maxFromAffordability = $this->calculateMaxLoanFromInstallment(
            $maxInstallment,
            $loanProduct->max_tenure_months ?? 12,
            $loanProduct->annual_interest_rate ?? 18.0,
            'reducing_balance' // Default method
        );

        // Maximum loan from LTV
        $maxFromLtv = null;
        if ($propertyValue && $loanProduct->max_ltv_ratio) {
            $maxFromLtv = $propertyValue * ($loanProduct->max_ltv_ratio / 100);
        }

        // Final max loan: minimum of affordability and LTV
        $finalMaxLoan = $maxFromAffordability;
        if ($maxFromLtv !== null) {
            $finalMaxLoan = min($maxFromAffordability, $maxFromLtv);
        }

        // ISSUE #2 FIX: Apply volatility-adjusted cap for high-volatility income profiles
        // High volatility + sporadic pattern = future income uncertainty
        $volatility = $analytics->income_volatility_coefficient ?? 0;
        $transactionPattern = $analytics->transaction_pattern ?? 'unknown';
        
        $volatilityAdjusted = false;
        $volatilityReductionPct = 0;
        
        if ($volatility > 60 && in_array($transactionPattern, ['sporadic', 'irregular'])) {
            // Calculate stability factor: reduce exposure based on excess volatility
            // Formula: 1 - ((volatility - 60) / 100 × penalty_weight)
            // Penalty weight: 0.5 (50% reduction for 100% excess volatility)
            $excessVolatility = $volatility - 60;
            $penaltyWeight = 0.5;
            $stabilityFactor = 1 - (($excessVolatility / 100) * $penaltyWeight);
            $stabilityFactor = max(0.5, $stabilityFactor); // Min 50% of original
            
            $originalMax = $finalMaxLoan;
            $finalMaxLoan = $finalMaxLoan * $stabilityFactor;
            $volatilityAdjusted = true;
            $volatilityReductionPct = round((1 - $stabilityFactor) * 100, 2);
            
            Log::info("Volatility-adjusted max loan", [
                'volatility' => $volatility,
                'pattern' => $transactionPattern,
                'original_max' => $originalMax,
                'stability_factor' => round($stabilityFactor, 4),
                'adjusted_max' => round($finalMaxLoan, 2),
                'reduction_pct' => $volatilityReductionPct,
            ]);
        }

        // Optimal tenure for max loan
        $optimalTenure = $loanProduct->max_tenure;

        return [
            'max_installment' => round($maxInstallment, 2),
            'max_from_affordability' => round($maxFromAffordability, 2),
            'max_from_ltv' => $maxFromLtv ? round($maxFromLtv, 2) : null,
            'final_max_loan' => round($finalMaxLoan, 2),
            'optimal_tenure' => $optimalTenure,
            'volatility_adjusted' => $volatilityAdjusted,
            'volatility_reduction_pct' => $volatilityReductionPct,
        ];
    }

    /**
     * Calculate max loan from installment
     */
    private function calculateMaxLoanFromInstallment(
        float $installment,
        int $tenureMonths,
        float $annualRate,
        string $method
    ): float {
        if ($method === 'reducing_balance') {
            $monthlyRate = $annualRate / 100 / 12;
            
            if ($monthlyRate == 0) {
                return $installment * $tenureMonths;
            }

            // Reverse PMT formula: PV = PMT * [(1+r)^n - 1] / [r(1+r)^n]
            $principal = $installment * 
                (pow(1 + $monthlyRate, $tenureMonths) - 1) / 
                ($monthlyRate * pow(1 + $monthlyRate, $tenureMonths));

            return $principal;
        } else {
            // Flat rate
            $totalInterestRate = ($annualRate / 100) * ($tenureMonths / 12);
            $principal = ($installment * $tenureMonths) / (1 + $totalInterestRate);
            
            return $principal;
        }
    }

    /**
     * Calculate risk grade
     */
    private function calculateRiskGrade(
        StatementAnalytics $analytics,
        array $ratios,
        array $incomeData
    ): array {
        $riskScore = 0;
        $factors = [];

        // DTI contribution (30 points max)
        if ($ratios['dti'] > 60) {
            $riskScore += 30;
            $factors[] = ['factor' => 'high_dti', 'value' => $ratios['dti'], 'weight' => 30];
        } elseif ($ratios['dti'] > 45) {
            $riskScore += 20;
            $factors[] = ['factor' => 'moderate_dti', 'value' => $ratios['dti'], 'weight' => 20];
        } elseif ($ratios['dti'] > 30) {
            $riskScore += 10;
            $factors[] = ['factor' => 'acceptable_dti', 'value' => $ratios['dti'], 'weight' => 10];
        }

        // Income stability (25 points max)
        if ($analytics->income_stability_score < 40) {
            $riskScore += 25;
            $factors[] = ['factor' => 'unstable_income', 'value' => $analytics->income_stability_score, 'weight' => 25];
        } elseif ($analytics->income_stability_score < 60) {
            $riskScore += 15;
            $factors[] = ['factor' => 'moderate_income_stability', 'value' => $analytics->income_stability_score, 'weight' => 15];
        } elseif ($analytics->income_stability_score < 75) {
            $riskScore += 8;
            $factors[] = ['factor' => 'fair_income_stability', 'value' => $analytics->income_stability_score, 'weight' => 8];
        }

        // Cash flow volatility (20 points max)
        if ($analytics->cash_flow_volatility_score > 70) {
            $riskScore += 20;
            $factors[] = ['factor' => 'high_volatility', 'value' => $analytics->cash_flow_volatility_score, 'weight' => 20];
        } elseif ($analytics->cash_flow_volatility_score > 50) {
            $riskScore += 12;
            $factors[] = ['factor' => 'moderate_volatility', 'value' => $analytics->cash_flow_volatility_score, 'weight' => 12];
        } elseif ($analytics->cash_flow_volatility_score > 30) {
            $riskScore += 6;
            $factors[] = ['factor' => 'some_volatility', 'value' => $analytics->cash_flow_volatility_score, 'weight' => 6];
        }

        // Negative balance days (15 points max)
        if ($analytics->negative_balance_days > 10) {
            $riskScore += 15;
            $factors[] = ['factor' => 'frequent_negative_balance', 'value' => $analytics->negative_balance_days, 'weight' => 15];
        } elseif ($analytics->negative_balance_days > 5) {
            $riskScore += 10;
            $factors[] = ['factor' => 'occasional_negative_balance', 'value' => $analytics->negative_balance_days, 'weight' => 10];
        } elseif ($analytics->negative_balance_days > 2) {
            $riskScore += 5;
            $factors[] = ['factor' => 'some_negative_balance', 'value' => $analytics->negative_balance_days, 'weight' => 5];
        }

        // Bounce count (10 points max)
        if ($analytics->bounce_count > 0) {
            $riskScore += min(10, $analytics->bounce_count * 5);
            $factors[] = ['factor' => 'bounced_transactions', 'value' => $analytics->bounce_count, 'weight' => min(10, $analytics->bounce_count * 5)];
        }

        // Pass-through transaction ratio (15 points max) - Suspicious cash-out behavior
        if (isset($analytics->pass_through_risk_flag) && $analytics->pass_through_risk_flag) {
            $passThroughRatio = $analytics->pass_through_ratio ?? 0;
            $riskWeight = config('mortgage.analytics.pass_through.risk_weight', 15);
            $riskScore += $riskWeight;
            $factors[] = ['factor' => 'high_pass_through', 'value' => $passThroughRatio, 'weight' => $riskWeight];
        }

        // Determine grade
        $grade = match(true) {
            $riskScore <= 15 => 'A',
            $riskScore <= 30 => 'B',
            $riskScore <= 50 => 'C',
            $riskScore <= 70 => 'D',
            default => 'E',
        };

        return [
            'score' => round($riskScore, 2),
            'grade' => $grade,
            'factors' => $factors,
        ];
    }

    /**
     * Generate plain-English risk and loan amount explanations
     */
    private function generateRiskExplanation(
        array $riskData,
        array $maxLoanData,
        StatementAnalytics $analytics,
        array $ratios,
        array $incomeData,
        float $requestedAmount
    ): array {
        // Top risk drivers (sort by weight, take top 3-5)
        $topFactors = collect($riskData['factors'])
            ->sortByDesc('weight')
            ->take(5)
            ->map(function ($factor) {
                return [
                    'factor' => $this->explainRiskFactor($factor),
                    'points' => $factor['weight'],
                ];
            })
            ->values()
            ->toArray();

        // Generate risk grade reasoning
        $gradeReasons = [];
        foreach ($riskData['factors'] as $factor) {
            $gradeReasons[] = $this->explainRiskFactor($factor);
        }

        // Loan limit determination explanation
        $loanLimitExplanation = $this->explainLoanLimit(
            $maxLoanData,
            $incomeData,
            $ratios,
            $analytics,
            $requestedAmount
        );

        return [
            'risk_grade' => $riskData['grade'],
            'risk_score' => $riskData['score'],
            'primary_risk_drivers' => $topFactors,
            'risk_grade_reasoning' => "Risk Grade {$riskData['grade']} assigned based on: " . implode('; ', array_slice($gradeReasons, 0, 3)),
            'loan_limit_determination' => $loanLimitExplanation,
            'limiting_factor' => $maxLoanData['limiting_factor'] ?? 'affordability',
        ];
    }

    /**
     * Explain a single risk factor in plain English
     */
    private function explainRiskFactor(array $factor): string
    {
        return match($factor['factor']) {
            'high_dti' => "High debt-to-income ratio ({$factor['value']}%) indicates significant existing obligations",
            'moderate_dti' => "Moderate debt-to-income ratio ({$factor['value']}%) shows manageable debt levels",
            'acceptable_dti' => "Acceptable debt-to-income ratio ({$factor['value']}%)",
            
            'unstable_income' => "Unstable income pattern (stability score: {$factor['value']}/100)",
            'moderate_income_stability' => "Moderate income stability (score: {$factor['value']}/100)",
            'fair_income_stability' => "Fair income stability (score: {$factor['value']}/100)",
            
            'high_volatility' => "High income volatility ({$factor['value']}%) suggests unpredictable cash flow",
            'moderate_volatility' => "Moderate income volatility ({$factor['value']}%) detected",
            'some_volatility' => "Some income volatility ({$factor['value']}%) present",
            
            'frequent_negative_balance' => "Frequent negative balance days ({$factor['value']} days) indicate cash flow stress",
            'occasional_negative_balance' => "Occasional negative balance ({$factor['value']} days)",
            'some_negative_balance' => "Some negative balance occurrences ({$factor['value']} days)",
            
            'bounced_transactions' => "Bounced transactions detected ({$factor['value']} instances)",
            'loan_stacking' => "Multiple active loans detected ({$factor['value']} obligations)",
            'high_pass_through' => "High pass-through transaction ratio ({$factor['value']}%) indicates potential money laundering risk",
            default => ucfirst(str_replace('_', ' ', $factor['factor'])) . " (value: {$factor['value']})",
        };
    }

    /**
     * Explain loan limit determination
     */
    private function explainLoanLimit(
        array $maxLoanData,
        array $incomeData,
        array $ratios,
        StatementAnalytics $analytics,
        float $requestedAmount
    ): string {
        $netIncome = number_format($incomeData['net_income'], 0);
        $maxFromAffordability = number_format($maxLoanData['max_from_affordability'], 0);
        $finalMax = number_format($maxLoanData['final_max_loan'], 0);
        
        $dsr = round($ratios['dsr'], 1);
        $dsrCap = ($analytics->income_classification->value === 'salary') ? 40 : 35;

        $explanation = "Based on net monthly income of TZS {$netIncome}, ";
        $explanation .= "with DSR cap at {$dsrCap}%, maximum affordable loan is TZS {$maxFromAffordability}. ";
        
        if ($maxLoanData['max_from_ltv'] !== null && $maxLoanData['max_from_ltv'] < $maxLoanData['max_from_affordability']) {
            $maxFromLTV = number_format($maxLoanData['max_from_ltv'], 0);
            $explanation .= "However, LTV constraint limits this to TZS {$maxFromLTV}. ";
        }
        
        // Check if volatility adjustment was applied
        if (isset($maxLoanData['volatility_adjusted']) && $maxLoanData['volatility_adjusted']) {
            $volatility = round($analytics->cash_flow_volatility_score, 1);
            $reductionPct = $maxLoanData['volatility_reduction_pct'] ?? 0;
            $explanation .= "Additionally, high income volatility ({$volatility}%) resulted in a {$reductionPct}% reduction for risk mitigation. ";
        }
        
        $explanation .= "Final maximum loan: TZS {$finalMax}.";
        
        if ($requestedAmount > $maxLoanData['final_max_loan']) {
            $requestedFormatted = number_format($requestedAmount, 0);
            $excess = number_format($requestedAmount - $maxLoanData['final_max_loan'], 0);
            $explanation .= " Requested amount (TZS {$requestedFormatted}) exceeds maximum by TZS {$excess}.";
        }
        
        return $explanation;
    }

    /**
     * Generate final recommendation with all key ratios and decision
     * 
     * @param array $policyResult
     * @param array $ratios
     * @param array $riskData
     * @param array $incomeData
     * @param array $maxLoanData
     * @param float $requestedAmount
     * @param StatementAnalytics $analytics
     * @return array
     */
    private function generateFinalRecommendation(
        array $policyResult,
        array $ratios,
        array $riskData,
        array $incomeData,
        array $maxLoanData,
        float $requestedAmount,
        StatementAnalytics $analytics
    ): array {
        // Determine system decision (map to required format)
        $systemDecision = match($policyResult['decision']) {
            'eligible' => 'Eligible',
            'conditional' => 'Conditional',
            'outside_policy' => 'Outside Policy',
            default => 'Declined'
        };

        // Determine recommended loan amount
        $recommendedAmount = min($requestedAmount, $maxLoanData['final_max_loan']);

        // Calculate confidence level
        $confidenceLevel = $this->calculateDecisionConfidence($policyResult, $riskData, $incomeData);

        // Compile supporting factors
        $supportingFactors = $this->identifySupportingFactors($incomeData, $ratios, $riskData, $analytics);

        // Compile risk factors
        $riskFactors = $this->identifyRiskFactors($ratios, $riskData, $incomeData, $analytics, $policyResult);

        // Generate summary reasoning (1-3 sentences)
        $summaryReasoning = $this->generateSummaryReasoning(
            $systemDecision,
            $riskData,
            $ratios,
            $incomeData,
            $recommendedAmount,
            $requestedAmount
        );

        // Determine recommended action
        $recommendedAction = $this->determineRecommendedAction($systemDecision, $policyResult, $riskData);

        return [
            'system_decision' => $systemDecision,
            'recommended_loan_amount' => $recommendedAmount,
            'confidence_level' => $confidenceLevel,
            
            // Key ratios
            'key_ratios' => [
                'dti' => [
                    'value' => round($ratios['dti'], 2),
                    'label' => 'Debt-to-Income Ratio',
                    'status' => $this->getRatioStatus($ratios['dti'], 35, 45),
                    'threshold' => 45,
                ],
                'dsr' => [
                    'value' => round($ratios['dsr'], 2),
                    'label' => 'Debt Service Ratio',
                    'status' => $this->getRatioStatus($ratios['dsr'], 40, 50),
                    'threshold' => 50,
                ],
                'ltv' => [
                    'value' => $ratios['ltv'] !== null ? round($ratios['ltv'], 2) : null,
                    'label' => 'Loan-to-Value Ratio',
                    'status' => $ratios['ltv'] !== null ? $this->getRatioStatus($ratios['ltv'], 70, 80) : 'n/a',
                    'threshold' => 80,
                ],
                'income_stability' => [
                    'value' => round($analytics->income_stability_score, 2),
                    'label' => 'Income Stability Score',
                    'status' => $this->getRatioStatus($analytics->income_stability_score, 60, 40, true), // Higher is better
                    'threshold' => 40,
                ],
                'cash_flow_volatility' => [
                    'value' => round($analytics->cash_flow_volatility_score, 2),
                    'label' => 'Cash Flow Volatility',
                    'status' => $this->getRatioStatus($analytics->cash_flow_volatility_score, 50, 70),
                    'threshold' => 70,
                ],
            ],
            
            'risk_grade' => $riskData['grade'],
            'risk_score' => round($riskData['score'], 2),
            'conditions' => $policyResult['conditions'],
            'supporting_factors' => $supportingFactors,
            'risk_factors' => $riskFactors,
            'summary_reasoning' => $summaryReasoning,
            'recommended_action' => $recommendedAction,
        ];
    }

    /**
     * Calculate decision confidence level
     * 
     * @param array $policyResult
     * @param array $riskData
     * @param array $incomeData
     * @return string
     */
    private function calculateDecisionConfidence(array $policyResult, array $riskData, array $incomeData): string
    {
        $score = 0;
        
        // Decision quality (40 points)
        if ($policyResult['decision'] === 'eligible') {
            $score += 40;
        } elseif ($policyResult['decision'] === 'conditional' && count($policyResult['conditions']) <= 2) {
            $score += 30;
        } elseif ($policyResult['decision'] === 'conditional') {
            $score += 20;
        }
        
        // Risk grade (35 points)
        $riskPoints = match($riskData['grade']) {
            'A' => 35,
            'B' => 28,
            'C' => 20,
            'D' => 12,
            'E' => 5,
            default => 0
        };
        $score += $riskPoints;
        
        // Income stability (25 points)
        if ($incomeData['gross_income'] > 1000000 && $incomeData['surplus_after_loan'] > 300000) {
            $score += 25;
        } elseif ($incomeData['gross_income'] > 500000 && $incomeData['surplus_after_loan'] > 200000) {
            $score += 18;
        } elseif ($incomeData['surplus_after_loan'] > 100000) {
            $score += 12;
        } else {
            $score += 5;
        }
        
        // Map score to confidence level
        if ($score >= 80) {
            return 'High';
        } elseif ($score >= 60) {
            return 'Medium';
        } else {
            return 'Low';
        }
    }

    /**
     * Identify supporting factors
     * 
     * @param array $incomeData
     * @param array $ratios
     * @param array $riskData
     * @param StatementAnalytics $analytics
     * @return array
     */
    private function identifySupportingFactors(array $incomeData, array $ratios, array $riskData, StatementAnalytics $analytics): array
    {
        $factors = [];
        
        // Good risk grade
        if (in_array($riskData['grade'], ['A', 'B'])) {
            $factors[] = "Strong risk profile (Grade {$riskData['grade']})";
        }
        
        // Low DTI
        if ($ratios['dti'] < 35) {
            $factors[] = "Low debt burden (DTI: {$ratios['dti']}%)";
        }
        
        // High income stability
        if ($analytics->income_stability_score >= 70) {
            $factors[] = "Consistent income pattern (Stability: {$analytics->income_stability_score}%)";
        }
        
        // Adequate surplus
        if ($incomeData['surplus_after_loan'] > 300000) {
            $surplus = number_format($incomeData['surplus_after_loan'], 0);
            $factors[] = "Strong surplus after loan payment (TZS {$surplus})";
        }
        
        // Low volatility
        if ($analytics->cash_flow_volatility_score < 40) {
            $factors[] = "Stable cash flow patterns";
        }
        
        // No bounces
        if ($analytics->bounce_count === 0) {
            $factors[] = "No bounced transactions";
        }
        
        // Regular income
        if ($analytics->income_classification->value === 'salary') {
            $factors[] = "Regular salary income";
        }
        
        return $factors;
    }

    /**
     * Identify risk factors
     * 
     * @param array $ratios
     * @param array $riskData
     * @param array $incomeData
     * @param StatementAnalytics $analytics
     * @param array $policyResult
     * @return array
     */
    private function identifyRiskFactors(array $ratios, array $riskData, array $incomeData, StatementAnalytics $analytics, array $policyResult): array
    {
        $factors = [];
        
        // High DTI
        if ($ratios['dti'] >= 45) {
            $factors[] = "High debt-to-income ratio ({$ratios['dti']}%)";
        } elseif ($ratios['dti'] >= 35) {
            $factors[] = "Elevated debt burden ({$ratios['dti']}% DTI)";
        }
        
        // High DSR
        if ($ratios['dsr'] >= 50) {
            $factors[] = "High debt service ratio ({$ratios['dsr']}%)";
        }
        
        // Poor risk grade
        if (in_array($riskData['grade'], ['D', 'E'])) {
            $factors[] = "Weak risk profile (Grade {$riskData['grade']})";
        }
        
        // Low income stability
        if ($analytics->income_stability_score < 50) {
            $factors[] = "Irregular income pattern (Stability: {$analytics->income_stability_score}%)";
        }
        
        // High volatility
        if ($analytics->cash_flow_volatility_score > 70) {
            $factors[] = "High cash flow volatility ({$analytics->cash_flow_volatility_score}%)";
        }
        
        // Low surplus
        if ($incomeData['surplus_after_loan'] < 200000) {
            $surplus = number_format($incomeData['surplus_after_loan'], 0);
            $factors[] = "Limited surplus after loan payment (TZS {$surplus})";
        }
        
        // Bounces
        if ($analytics->bounce_count > 0) {
            $factors[] = "Bounced transactions detected ({$analytics->bounce_count})";
        }
        
        // Loan stacking
        if (($analytics->detected_loan_count ?? 0) >= 3) {
            $factors[] = "Multiple existing loans detected";
        }
        
        // Policy breaches
        foreach ($policyResult['breaches'] as $breach) {
            $rule = str_replace('_', ' ', $breach['rule']);
            $factors[] = ucfirst($rule);
        }
        
        return $factors;
    }

    /**
     * Generate summary reasoning
     * 
     * @param string $systemDecision
     * @param array $riskData
     * @param array $ratios
     * @param array $incomeData
     * @param float $recommendedAmount
     * @param float $requestedAmount
     * @return string
     */
    private function generateSummaryReasoning(
        string $systemDecision,
        array $riskData,
        array $ratios,
        array $incomeData,
        float $recommendedAmount,
        float $requestedAmount
    ): string {
        $recommendedFormatted = number_format($recommendedAmount, 0);
        $requestedFormatted = number_format($requestedAmount, 0);
        
        if ($systemDecision === 'Eligible') {
            if ($recommendedAmount >= $requestedAmount) {
                return "Applicant qualifies for the full requested amount of TZS {$requestedFormatted}. " .
                       "Risk Grade {$riskData['grade']} with healthy DTI ({$ratios['dti']}%) and DSR ({$ratios['dsr']}%). " .
                       "Strong affordability and minimal policy concerns.";
            } else {
                return "Applicant is eligible but recommended loan amount is TZS {$recommendedFormatted}, lower than requested TZS {$requestedFormatted} due to affordability constraints. " .
                       "Risk Grade {$riskData['grade']} with DTI of {$ratios['dti']}% and DSR of {$ratios['dsr']}%.";
            }
        }
        
        if ($systemDecision === 'Conditional') {
            return "Applicant meets basic criteria but has some concerns requiring conditions. " .
                   "Risk Grade {$riskData['grade']} with DTI of {$ratios['dti']}% and DSR of {$ratios['dsr']}%. " .
                   "Recommended amount: TZS {$recommendedFormatted}, subject to addressing specified conditions.";
        }
        
        if ($systemDecision === 'Outside Policy') {
            return "Application falls outside standard policy limits. " .
                   "DTI of {$ratios['dti']}% and/or DSR of {$ratios['dsr']}% exceed thresholds. " .
                   "Risk Grade {$riskData['grade']}. Requires management override for approval.";
        }
        
        return "Application does not meet minimum eligibility requirements at this time.";
    }

    /**
     * Determine recommended action
     * 
     * @param string $systemDecision
     * @param array $policyResult
     * @param array $riskData
     * @return string
     */
    private function determineRecommendedAction(string $systemDecision, array $policyResult, array $riskData): string
    {
        if ($systemDecision === 'Eligible') {
            return 'Approve application and proceed with loan documentation.';
        }
        
        if ($systemDecision === 'Conditional') {
            $conditionCount = count($policyResult['conditions']);
            return "Approve subject to {$conditionCount} condition(s). Review and confirm mitigation measures before final approval.";
        }
        
        if ($systemDecision === 'Outside Policy') {
            if (in_array($riskData['grade'], ['A', 'B', 'C'])) {
                return 'Refer to Credit Committee for exception approval. Applicant shows reasonable risk profile despite policy breach.';
            } else {
                return 'Recommend decline unless exceptional circumstances exist. High risk profile combined with policy breaches.';
            }
        }
        
        return 'Decline application. Does not meet minimum requirements.';
    }

    /**
     * Get ratio status
     * 
     * @param float $value
     * @param float $goodThreshold
     * @param float $badThreshold
     * @param bool $higherIsBetter
     * @return string
     */
    private function getRatioStatus(float $value, float $goodThreshold, float $badThreshold, bool $higherIsBetter = false): string
    {
        if ($higherIsBetter) {
            if ($value >= $goodThreshold) {
                return 'good';
            } elseif ($value >= $badThreshold) {
                return 'acceptable';
            } else {
                return 'poor';
            }
        } else {
            if ($value <= $goodThreshold) {
                return 'good';
            } elseif ($value <= $badThreshold) {
                return 'acceptable';
            } else {
                return 'poor';
            }
        }
    }

    /**
     * Evaluate policy rules
     */
    private function evaluatePolicyRules(
        array $ratios,
        array $riskData,
        array $incomeData,
        LoanProduct $loanProduct,
        StatementAnalytics $analytics
    ): array {
        $breaches = [];
        $conditions = [];
        $decision = 'eligible';

        // Check DTI threshold
        if ($ratios['dti'] > ($loanProduct->max_dti_ratio ?? 50)) {
            $breaches[] = [
                'rule' => 'max_dti_exceeded',
                'threshold' => $loanProduct->max_dti_ratio ?? 50,
                'actual' => $ratios['dti'],
            ];
        }

        // Check DSR threshold
        if ($ratios['dsr'] > ($loanProduct->max_dsr_ratio ?? 50)) {
            $breaches[] = [
                'rule' => 'max_dsr_exceeded',
                'threshold' => $loanProduct->max_dsr_ratio ?? 50,
                'actual' => $ratios['dsr'],
            ];
        }

        // Check LTV threshold (if property provided)
        if ($ratios['ltv'] !== null && $ratios['ltv'] > ($loanProduct->max_ltv_ratio ?? 80)) {
            $breaches[] = [
                'rule' => 'max_ltv_exceeded',
                'threshold' => $loanProduct->max_ltv_ratio ?? 80,
                'actual' => $ratios['ltv'],
            ];
        }

        // Check minimum surplus
        $minSurplus = 200000; // TZS 200,000 minimum
        if ($incomeData['surplus_after_loan'] < $minSurplus) {
            $breaches[] = [
                'rule' => 'insufficient_surplus',
                'threshold' => $minSurplus,
                'actual' => $incomeData['surplus_after_loan'],
            ];
        }

        // Check income stability
        if ($analytics->income_stability_score < 40) {
            $conditions[] = [
                'condition' => 'low_income_stability',
                'recommendation' => 'Require guarantor or additional collateral',
                'severity' => 'high',
            ];
        }

        // Check volatility
        if ($analytics->cash_flow_volatility_score > 70) {
            $conditions[] = [
                'condition' => 'high_cash_flow_volatility',
                'recommendation' => 'Consider shorter tenure to reduce risk',
                'severity' => 'medium',
            ];
        }

        // Check bounces
        if ($analytics->bounce_count > 0) {
            $conditions[] = [
                'condition' => 'bounced_transactions_detected',
                'recommendation' => 'Request explanation for bounced payments',
                'severity' => 'high',
            ];
        }

        // Determine final decision
        if (count($breaches) > 0) {
            $decision = 'outside_policy';
        } elseif (count($conditions) > 0) {
            $decision = 'conditional';
        } else {
            $decision = 'eligible';
        }

        // Check if recommendable
        $isRecommendable = ($decision === 'eligible' || $decision === 'conditional') 
            && in_array($riskData['grade'], ['A', 'B', 'C']);

        $reason = $this->buildDecisionReason($decision, $breaches, $conditions);

        return [
            'decision' => $decision,
            'reason' => $reason,
            'breaches' => $breaches,
            'conditions' => $conditions,
            'is_recommendable' => $isRecommendable,
        ];
    }

    /**
     * Build decision reason
     */
    private function buildDecisionReason(string $decision, array $breaches, array $conditions): string
    {
        if ($decision === 'eligible') {
            return 'Application meets all policy requirements and is recommended for approval.';
        }

        if ($decision === 'conditional') {
            $conditionCount = count($conditions);
            return "Application is conditionally approved subject to {$conditionCount} condition(s). Review required.";
        }

        if ($decision === 'outside_policy') {
            $breachCount = count($breaches);
            return "Application falls outside policy limits with {$breachCount} breach(es). Override required for approval.";
        }

        return 'Application declined.';
    }

    /**
     * Calculate amortization details
     */
    private function calculateAmortizationDetails(
        float $principal,
        int $tenureMonths,
        float $annualRate,
        string $method
    ): array {
        $monthlyRate = $annualRate / 100 / 12;

        if ($method === 'reducing_balance') {
            $installment = $this->calculateReducingBalanceInstallment($principal, $tenureMonths, $annualRate);
            $totalRepayment = $installment * $tenureMonths;
            $totalInterest = $totalRepayment - $principal;
            $effectiveApr = $annualRate; // Same for reducing balance

        } else {
            // Flat rate
            $totalInterest = ($principal * $annualRate / 100) * ($tenureMonths / 12);
            $totalRepayment = $principal + $totalInterest;
            
            // Calculate effective APR (approximate)
            $effectiveApr = ($totalInterest / $principal) / ($tenureMonths / 12) * 100;
        }

        return [
            'monthly_rate' => round($monthlyRate, 6),
            'total_interest' => round($totalInterest, 2),
            'total_repayment' => round($totalRepayment, 2),
            'effective_apr' => round($effectiveApr, 2),
        ];
    }

    /**
     * Run stress test
     */
    private function runStressTest(array $incomeData, float $baseInstallment, array $params): array
    {
        $scenario = '';
        $stressedIncome = $incomeData['net_income'];
        $stressedInstallment = $baseInstallment;

        // Apply income shock
        if (isset($params['income_shock_percent'])) {
            $shockPercent = $params['income_shock_percent'];
            $stressedIncome *= (1 - $shockPercent / 100);
            $scenario .= "income_drop_{$shockPercent}pct_";
        }

        // Apply rate increase
        if (isset($params['rate_increase_percent'])) {
            $rateIncrease = $params['rate_increase_percent'];
            // Approximate installment increase
            $stressedInstallment *= (1 + $rateIncrease / 100);
            $scenario .= "rate_increase_{$rateIncrease}pct";
        }

        $stressedSurplus = $stressedIncome - $stressedInstallment;
        $passes = $stressedSurplus >= 200000; // Minimum surplus threshold

        return [
            'scenario' => rtrim($scenario, '_'),
            'stressed_installment' => round($stressedInstallment, 2),
            'stressed_surplus' => round($stressedSurplus, 2),
            'passes' => $passes,
        ];
    }

    /**
     * Calculate Debt-to-Income ratio
     * 
     * @param float $monthlyGrossIncome Monthly gross income
     * @param float $requestedLoanPayment Requested loan monthly payment
     * @param float $existingLoanPayments Total existing loan payments
     * @return float DTI percentage
     */
    public function calculateDTI(
        float $monthlyGrossIncome,
        float $requestedLoanPayment,
        float $existingLoanPayments
    ): float {
        if ($monthlyGrossIncome <= 0) {
            return 100.0; // If no income, DTI is 100%
        }

        $totalDebt = $requestedLoanPayment + $existingLoanPayments;
        $dti = ($totalDebt / $monthlyGrossIncome) * 100;

        return round($dti, 2);
    }

    /**
     * Calculate Debt Service Ratio
     * 
     * @param float $monthlyNetSalary Monthly net salary
     * @param float $requestedLoanPayment Requested loan monthly payment
     * @param float $existingLoanPayments Total existing loan payments
     * @return float DSR percentage
     */
    public function calculateDSR(
        float $monthlyNetSalary,
        float $requestedLoanPayment,
        float $existingLoanPayments
    ): float {
        if ($monthlyNetSalary <= 0) {
            return 100.0; // If no income, DSR is 100%
        }

        $totalDebtService = $requestedLoanPayment + $existingLoanPayments;
        $dsr = ($totalDebtService / $monthlyNetSalary) * 100;

        return round($dsr, 2);
    }

    /**
     * Calculate Loan-to-Value ratio
     * 
     * @param float $loanAmount Loan amount requested
     * @param float $collateralValue Value of collateral/property
     * @return float LTV percentage
     */
    public function calculateLTV(
        float $loanAmount,
        float $collateralValue
    ): float {
        if ($collateralValue <= 0) {
            return 100.0; // If no collateral value, LTV is 100%
        }

        $ltv = ($loanAmount / $collateralValue) * 100;

        return round($ltv, 2);
    }
}

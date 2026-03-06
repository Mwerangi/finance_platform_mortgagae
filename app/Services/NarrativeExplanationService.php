<?php

namespace App\Services;

use App\Models\EligibilityAssessment;
use App\Models\StatementAnalytics;

/**
 * Generates human-readable narrative explanations for financial metrics
 * to make pre-qualification reports understandable by non-technical users.
 */
class NarrativeExplanationService
{
    /**
     * Generate income volatility explanation
     */
    public function explainIncomeVolatility(float $volatility): string
    {
        // Bind explanation DIRECTLY to the actual volatility value
        if ($volatility == 0) { // Use loose comparison to handle 0.0 and 0
            return "Volatility of 0% indicates either insufficient transaction data or perfectly uniform income deposits. In most real-world scenarios, this suggests limited data rather than true stability. Additional months of transaction history are recommended for reliable volatility assessment.";
        } elseif ($volatility < 10) {
            return "This indicates that the applicant's income is highly stable with minimal month-to-month variation (less than 10%). This is an excellent indicator for mortgage qualification as it suggests reliable, predictable income that can consistently support loan repayments.";
        } elseif ($volatility < 15) {
            return "This indicates that the applicant's income is very stable with low month-to-month variation. This predictable income pattern is favorable for mortgage qualification and demonstrates consistent earning capacity.";
        } elseif ($volatility < 25) {
            return "This indicates that the applicant's income fluctuates slightly month to month but remains within an acceptable stability range. The variation is normal and does not significantly impact mortgage qualification.";
        } elseif ($volatility < 35) {
            return "This shows moderate income fluctuation, which suggests some variability in earnings. While this is not unusual, especially for business or commission-based income, it requires careful consideration of repayment capacity. The applicant should demonstrate sufficient buffer capacity to handle income variations.";
        } else {
            return "This indicates significant income volatility, meaning income varies substantially from month to month. High volatility increases lending risk as it makes future income less predictable. This may require additional collateral, higher down payment, or alternative income verification to support mortgage approval.";
        }
    }

    /**
     * Generate income stability explanation
     */
    public function explainIncomeStability(float $stabilityScore): string
    {
        // Bind explanation DIRECTLY to the actual stability score
        if ($stabilityScore == 0) { // Use loose comparison to handle 0.0 and 0
            return "A stability score of 0/100 indicates no consistent income deposits were detected in the analyzed period. This prevents reliable prediction of future income and significantly increases lending risk. The applicant may need to provide additional income verification documents or extend the analysis period.";
        } elseif ($stabilityScore == 100) {
            return "A perfect stability score of 100/100 indicates exceptional income pattern with perfectly consistent deposits. This is extremely rare in real-world scenarios and suggests either limited transaction history or very structured income deposits. While favorable, additional verification may be needed to confirm sustainability.";
        } elseif ($stabilityScore < 30) {
            return "The income pattern shows limited consistency with irregular deposits or significant variations. This low score (" . number_format($stabilityScore, 0) . "/100) makes it difficult to predict future income with confidence and may require alternative income verification methods or co-borrower support for mortgage approval.";
        } elseif ($stabilityScore < 50) {
            return "The income pattern shows moderate consistency. There are noticeable variations in deposit amounts or frequency, which suggests fluctuating earnings. A score of " . number_format($stabilityScore, 0) . "/100 is manageable but may require additional documentation to verify income sustainability.";
        } elseif ($stabilityScore < 70) {
            return "The income shows good consistency with regular deposits. While there may be some variation in amounts or timing, the overall pattern demonstrates reliable income generation that supports mortgage qualification. A score of " . number_format($stabilityScore, 0) . "/100 indicates acceptable stability.";
        } else {
            return "The income pattern is highly consistent and predictable. Regular deposits at similar amounts indicate strong employment stability or established business operations. A score of " . number_format($stabilityScore, 0) . "/100 significantly strengthens the mortgage application.";
        }
    }

    /**
     * Generate income source analysis explanation
     */
    public function explainIncomeSource(string $customerType, array $incomeBreakdown = []): string
    {
        switch ($customerType) {
            case 'salary':
                return "The majority of income originates from salary deposits, indicating primary employment income. Salary-based income is generally viewed favorably for mortgage applications as it provides predictable, regular cash flow. The employment stability suggests reliable capacity to service monthly mortgage installments.";
            
            case 'business':
                return "Income appears to come primarily from business operations, as evidenced by business deposits and varied transaction patterns. Business income can be more variable than salary but demonstrates entrepreneurial capacity. The system evaluates business income sustainability over the analysis period to ensure adequate mortgage repayment capacity.";
            
            case 'mixed':
                return "Income comes from mixed sources including salary deposits, business income, and other transfers, suggesting diversified income streams. Having multiple income sources can be beneficial as it provides income security through diversification, though the system evaluates the consistency of each stream to ensure reliable mortgage repayment capacity.";
            
            default:
                return "The income sources have been analyzed to determine the primary source of funds. Understanding income composition is critical for assessing income stability and predicting future repayment capacity.";
        }
    }

    /**
     * Generate affordability explanation
     */
    public function explainAffordability(
        float $avgIncome,
        float $estimatedExpenses,
        float $disposableIncome,
        float $dtiRatio = null
    ): string {
        $disposablePercentage = $avgIncome > 0 ? ($disposableIncome / $avgIncome) * 100 : 0;
        
        $explanation = "After accounting for estimated living expenses (approximately TZS " . number_format($estimatedExpenses, 0) . " per month), ";
        $explanation .= "the applicant retains approximately TZS " . number_format($disposableIncome, 0) . " per month, ";
        $explanation .= "which represents " . number_format($disposablePercentage, 1) . "% of gross monthly income. ";
        
        if ($disposablePercentage >= 40) {
            $explanation .= "This substantial disposable income provides strong capacity for mortgage repayment with comfortable financial cushion for unexpected expenses.";
        } elseif ($disposablePercentage >= 30) {
            $explanation .= "This healthy disposable income level provides adequate capacity for mortgage repayment with reasonable buffer for financial contingencies.";
        } elseif ($disposablePercentage >= 20) {
            $explanation .= "This moderate disposable income provides capacity for mortgage repayment, though careful budgeting will be important to maintain financial stability.";
        } else {
            $explanation .= "This limited disposable income suggests tight financial margins. The mortgage installment must be carefully sized to avoid financial strain and ensure sustainable repayment.";
        }
        
        return $explanation;
    }

    /**
     * Generate DTI/DSR ratio explanation
     */
    public function explainDebtRatios(
        float $dtiRatio = null,
        float $dsrRatio = null,
        float $existingDebt = 0,
        string $customerType = 'salary'
    ): string {
        if ($existingDebt > 0 && $dtiRatio !== null) {
            $explanation = "The Debt-to-Income (DTI) ratio of " . number_format($dtiRatio, 1) . "% indicates that ";
            
            if ($dtiRatio <= 30) {
                $explanation .= "existing debt obligations consume a low portion of monthly income, leaving ample capacity for additional mortgage payments. This is an excellent debt position for mortgage qualification.";
            } elseif ($dtiRatio <= 40) {
                $explanation .= "existing debt obligations are at a manageable level, leaving reasonable capacity for mortgage payments. This debt position is acceptable for mortgage qualification with appropriate loan sizing.";
            } elseif ($dtiRatio <= 50) {
                $explanation .= "existing debt obligations consume a significant portion of income. While still within acceptable limits, the mortgage must be carefully sized to avoid over-leverage and ensure affordable monthly payments.";
            } else {
                $explanation .= "existing debt obligations consume a high portion of income, which limits capacity for additional mortgage debt. This elevated debt burden may require debt consolidation, higher income verification, or lower loan amounts.";
            }
        } elseif ($dsrRatio !== null) {
            $explanation = "Since no significant existing loan obligations were detected, the system applies the Debt Service Ratio (DSR) of " . number_format($dsrRatio, 1) . "% ";
            
            if ($customerType === 'business') {
                $explanation .= "to business income. For business income, the system applies conservative assumptions to account for income variability. ";
            } else {
                $explanation .= "to salary income. ";
            }
            
            $explanation .= "This ratio ensures that the proposed mortgage installment remains within the applicant's safe repayment capacity while maintaining adequate funds for other living expenses and financial obligations.";
        } else {
            $explanation = "The debt analysis evaluates the applicant's capacity to service the proposed mortgage while maintaining financial stability. ";
            $explanation .= "The system ensures that total debt obligations remain within prudent lending limits based on verified income.";
        }
        
        return $explanation;
    }

    /**
     * Generate loan capacity explanation
     */
    public function explainLoanCapacity(
        float $recommendedInstallment,
        float $maxLoanAmount,
        int $tenure,
        float $interestRate
    ): string {
        // CRITICAL: Check for invalid calculation results
        if ($recommendedInstallment <= 0 && $maxLoanAmount > 0) {
            return "CALCULATION ERROR: The system calculated a maximum loan amount of TZS " . number_format($maxLoanAmount, 0) . " but the recommended monthly installment is zero or negative. This indicates a data processing error. Please contact your loan officer for manual assessment.";
        }
        
        if ($maxLoanAmount <= 0) {
            return "Based on the current financial assessment, the system cannot recommend a loan amount at this time. This may be due to insufficient income verification, high existing debt obligations, or other risk factors identified in the analysis. Please address the conditions listed in this report and consider reapplying after financial profile improvements.";
        }
        
        $tenureYears = $tenure / 12;
        $tenureFormatted = $this->formatTenure($tenure);
        
        $explanation = "Based on the applicant's verified income, existing obligations, and financial behavior, the system estimates a safe monthly mortgage installment of approximately TZS " . number_format($recommendedInstallment, 0) . ". ";
        
        $explanation .= "Over a " . $tenureFormatted . " repayment period at " . number_format($interestRate, 2) . "% annual interest rate, ";
        $explanation .= "this installment capacity translates to a maximum recommended loan amount of TZS " . number_format($maxLoanAmount, 0) . ". ";
        
        $explanation .= "This recommendation balances mortgage affordability with the need to maintain healthy financial reserves for unexpected expenses and lifestyle needs.";
        
        return $explanation;
    }
    
    /**
     * Format tenure properly (fix decimal issue)
     */
    public function formatTenure(int $tenure): string
    {
        $years = intdiv($tenure, 12);
        $months = $tenure % 12;
        
        if ($months === 0) {
            return $years . " years (" . $tenure . " months)";
        }
        
        return $tenure . " months (" . $years . " years " . $months . " months)";
    }
    
    /**
     * Convert system condition labels to human-readable text.
     */
    public function humanizeCondition($condition): string
    {
        // If already human text, return as is
        if (is_string($condition) && !$this->looksLikeSystemLabel($condition)) {
            return $condition;
        }
        
        // If it's an array with description, use that
        if (is_array($condition) && isset($condition['description'])) {
            return $condition['description'];
        }
        
        // Extract the label
        $label = is_array($condition) ? ($condition['condition'] ?? implode(': ', $condition)) : $condition;
        
        // Map system labels to human text
        $labelMap = [
            'low_income_stability' => 'Income stability score below acceptable threshold - provide additional income verification documents',
            'high_cash_flow_volatility' => 'High variability in monthly cash flow - demonstrate consistent income over extended period',
            'high_dti_ratio' => 'Debt-to-income ratio exceeds policy limits - reduce existing debt obligations or increase verifiable income',
            'high_dsr_ratio' => 'Debt service ratio beyond acceptable range - debt consolidation or income increase required',
            'insufficient_transaction_history' => 'Limited transaction history - provide bank statements covering at least 6 months',
            'irregular_income_deposits' => 'Irregular income deposit patterns - provide employment letter or business income verification',
            'negative_balance_frequency' => 'Account shows frequent negative balances - demonstrate improved cash flow management',
            'high_ltv_ratio' => 'Loan-to-value ratio exceeds policy threshold - increase down payment or reduce loan amount',
            'short_analysis_period' => 'Analysis period too short for reliable assessment - provide additional months of bank statements',
            'pass_through_activity_detected' => 'High pass-through transaction activity detected - provide explanation and supporting documentation',
            'bounced_transactions' => 'Bounced transactions detected in account history - demonstrate financial stability improvement',
            'gambling_transactions' => 'Gambling-related transactions identified - may require additional risk assessment',
            'business_income_verification' => 'Business income requires additional verification - provide business registration and tax returns',
            'property_valuation_required' => 'Property valuation required to confirm market value and LTV calculation',
            'co_borrower_required' => 'Co-borrower or guarantor required to strengthen application',
            'higher_down_payment' => 'Higher down payment required to reduce lending risk - minimum X% of property value',
        ];
        
        // Convert snake_case to human text
        if (isset($labelMap[$label])) {
            return $labelMap[$label];
        }
        
        // Fallback: convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $label));
    }
    
    /**
     * Check if string looks like a system label (snake_case).
     */
    private function looksLikeSystemLabel(string $text): bool
    {
        return preg_match('/^[a-z_]+$/', $text) && str_contains($text, '_');
    }

    /**
     * Generate behavioral analysis explanation
     */
    public function explainBehavioralPatterns(StatementAnalytics $analytics): string
    {
        $patterns = [];
        $riskLevel = 'positive';
        
        // Check cash flow volatility
        if ($analytics->cash_flow_volatility > 35) {
            $patterns[] = "significant cash flow fluctuations that suggest irregular spending or income patterns";
            $riskLevel = 'caution';
        }
        
        // Check negative balance frequency
        if ($analytics->negative_balance_frequency > 2) {
            $patterns[] = "instances of account balance going negative, indicating occasional cash flow challenges";
            $riskLevel = 'caution';
        }
        
        // Check bounce transactions
        if (isset($analytics->summary['bounce_count']) && $analytics->summary['bounce_count'] > 0) {
            $patterns[] = "bounced transactions, which raise concerns about financial management";
            $riskLevel = 'warning';
        }
        
        // Check gambling transactions
        if (isset($analytics->summary['gambling_transactions']) && $analytics->summary['gambling_transactions'] > 0) {
            $patterns[] = "gambling-related transactions, which may indicate risky financial behavior";
            $riskLevel = 'warning';
        }
        
        // Check pass-through activity
        if (isset($analytics->pass_through_risk_flag) && $analytics->pass_through_risk_flag) {
            $patterns[] = "high pass-through activity (money in → money out patterns), which requires additional verification";
            $riskLevel = 'warning';
        }
        
        // Generate explanation
        if (count($patterns) === 0) {
            return "The account demonstrates generally stable financial behavior with consistent deposits, controlled spending patterns, and healthy balance management. No significant risk indicators were detected in the transaction patterns. This positive financial behavior strengthens the mortgage application and suggests responsible money management.";
        }
        
        $explanation = "The transaction analysis identified ";
        
        if ($riskLevel === 'warning') {
            $explanation .= "some concerning behavioral patterns including: ";
        } elseif ($riskLevel === 'caution') {
            $explanation .= "certain patterns that require attention, including: ";
        }
        
        $explanation .= implode('; ', $patterns) . ". ";
        
        if ($riskLevel === 'warning') {
            $explanation .= "These patterns may indicate elevated financial risk and should be discussed during the formal credit review process. Additional documentation or explanations may be required to address these concerns.";
        } else {
            $explanation .= "While these patterns are noted, they do not necessarily disqualify the applicant but should be monitored and may warrant discussion during credit review.";
        }
        
        return $explanation;
    }

    /**
     * Generate risk indicator explanation
     */
    public function explainRiskIndicators(
        float $volatility,
        float $stabilityScore,
        int $monthsAnalyzed,
        array $riskFlags = []
    ): string {
        $risks = [];
        
        // Check volatility
        if ($volatility > 30) {
            $risks[] = "High income fluctuation (" . number_format($volatility, 1) . "%) makes future income prediction challenging and increases repayment uncertainty";
        }
        
        // Check stability
        if ($stabilityScore < 50) {
            $risks[] = "Low income consistency score (" . number_format($stabilityScore, 0) . "/100) suggests irregular income patterns that may affect repayment capacity";
        }
        
        // Check analysis period
        if ($monthsAnalyzed < 6) {
            $risks[] = "Limited transaction history (" . $monthsAnalyzed . " months) provides insufficient data for comprehensive income verification. Ideally, 6-12 months of transaction history is preferred";
        }
        
        // Add custom risk flags
        foreach ($riskFlags as $flag) {
            $risks[] = $flag;
        }
        
        if (count($risks) === 0) {
            return "No significant risk indicators were identified in the financial analysis. The applicant demonstrates stable income patterns, healthy transaction history, and responsible financial behavior that support mortgage qualification. The overall risk profile is favorable for lending consideration.";
        }
        
        $explanation = "The following risk indicators were identified during the assessment:\n\n";
        
        foreach ($risks as $index => $risk) {
            $explanation .= ($index + 1) . ". " . $risk . "\n";
        }
        
        $explanation .= "\nThese factors do not necessarily disqualify the application but may require mitigation through additional collateral, higher down payment, co-borrower support, or alternative income verification. Each risk indicator is considered in the context of the overall financial profile.";
        
        return $explanation;
    }

    /**
     * Generate final recommendation explanation
     */
    public function explainFinalRecommendation(
        string $decision,
        float $maxLoanAmount,
        float $recommendedInstallment,
        string $riskGrade,
        array $conditions = []
    ): string {
        // CRITICAL: Check for invalid calculation results
        if ($recommendedInstallment <= 0 && $maxLoanAmount > 0) {
            return "⚠️ LOAN CAPACITY COULD NOT BE DETERMINED DUE TO CALCULATION ERROR. The system encountered an error while computing the recommended installment amount, despite calculating a loan capacity. This is a technical issue that requires manual review. Please contact your loan officer for a manual financial assessment. Do not proceed with any financial commitments based on this report.";
        }
        
        $explanation = "";
        
        switch (strtolower($decision)) {
            case 'approved':
            case 'eligible':
                $explanation = "Based on comprehensive analysis of the applicant's bank transactions, income stability, debt obligations, and financial behavior, the system determines that the applicant demonstrates sufficient financial capacity to support the recommended mortgage amount. ";
                $explanation .= "The income is stable and predictable, existing obligations are manageable, and no significant risk indicators were detected. ";
                $explanation .= "The applicant qualifies for a maximum loan amount of TZS " . number_format($maxLoanAmount, 0) . " with estimated monthly installment of TZS " . number_format($recommendedInstallment, 0) . ". ";
                $explanation .= "Risk Grade: " . strtoupper($riskGrade) . " - This indicates favorable lending risk profile.";
                break;
            
            case 'conditionally_approved':
            case 'conditional':
                $explanation = "Based on the financial analysis, the applicant demonstrates reasonable capacity to support a mortgage, subject to certain conditions being met. ";
                $explanation .= "The applicant may qualify for a maximum loan amount of TZS " . number_format($maxLoanAmount, 0) . " with estimated monthly installment of TZS " . number_format($recommendedInstallment, 0) . ", ";
                $explanation .= "provided the following conditions are addressed:\n\n";
                
                if (count($conditions) > 0) {
                    foreach ($conditions as $index => $condition) {
                        $rawCondition = is_array($condition) 
                            ? ($condition['description'] ?? $condition['condition'] ?? 'Additional verification required')
                            : $condition;
                        // Humanize the condition text
                        $conditionText = $this->humanizeCondition($rawCondition);
                        $explanation .= "• " . $conditionText . "\n";
                    }
                    $explanation .= "\n";
                }
                
                $explanation .= "Risk Grade: " . strtoupper($riskGrade) . " - Meeting these conditions will help mitigate identified risks and strengthen the application.";
                break;
            
            case 'rejected':
            case 'not_recommended':
                $explanation = "After careful analysis of the provided financial data, the system determines that the applicant's current financial profile does not meet the minimum requirements for mortgage pre-qualification. ";
                $explanation .= "This may be due to insufficient income, high existing debt obligations, significant income volatility, concerning transaction patterns, or other risk factors identified during analysis. ";
                $explanation .= "Risk Grade: " . strtoupper($riskGrade) . ". ";
                $explanation .= "This preliminary assessment does not permanently disqualify the applicant. We recommend addressing the identified concerns and reapplying after financial profile improvements, or exploring alternative mortgage products that may better suit the current financial situation.";
                break;
            
            default:
                $explanation = "The financial assessment has been completed. Please review the detailed analysis sections above for comprehensive evaluation of income, affordability, debt capacity, and risk indicators.";
        }
        
        return $explanation;
    }

    /**
     * Generate applicant summary explanation
     */
    public function explainApplicantSummary(int $transactionCount, int $monthsAnalyzed, string $customerType): string
    {
        return "The system analyzed " . number_format($transactionCount) . " bank transactions over a " . $monthsAnalyzed . "-month period to comprehensively evaluate the applicant's income stability, spending patterns, debt obligations, and overall financial behavior. This automated analysis identifies income sources, calculates affordability metrics, assesses financial risk indicators, and generates a mortgage pre-qualification recommendation. The applicant's profile has been classified as " . ucfirst($customerType) . " income type based on the predominant income patterns detected.";
    }

    /**
     * Generate LTV explanation
     */
    public function explainLTV(float $ltvRatio, float $loanAmount, float $propertyValue): string
    {
        $explanation = "The Loan-to-Value (LTV) ratio of " . number_format($ltvRatio, 1) . "% represents the proportion of property value being financed through the mortgage. ";
        $explanation .= "With a property valued at TZS " . number_format($propertyValue, 0) . ", the requested loan of TZS " . number_format($loanAmount, 0) . " requires ";
        
        $downPaymentPercent = 100 - $ltvRatio;
        $downPaymentAmount = $propertyValue - $loanAmount;
        
        $explanation .= number_format($downPaymentPercent, 1) . "% down payment (TZS " . number_format($downPaymentAmount, 0) . "). ";
        
        if ($ltvRatio <= 70) {
            $explanation .= "This conservative LTV ratio significantly reduces lending risk and demonstrates strong borrower equity commitment. Lower LTV ratios typically qualify for better mortgage terms.";
        } elseif ($ltvRatio <= 80) {
            $explanation .= "This is within the standard acceptable LTV range for mortgage lending, balancing borrower affordability with lender risk management.";
        } elseif ($ltvRatio <= 90) {
            $explanation .= "This elevated LTV ratio indicates higher financing proportion, which increases lending risk. Additional risk mitigation measures such as mortgage insurance may be required.";
        } else {
            $explanation .= "This very high LTV ratio presents substantial lending risk due to minimal borrower equity. Such high financing may require additional collateral, guarantees, or may exceed lending policy thresholds.";
        }
        
        return $explanation;
    }
}

<?php

namespace App\Services;

use App\Enums\IncomeClassification;
use App\Enums\TransactionType;
use App\Models\BankStatementImport;
use App\Models\BankTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StatementAnalyticsService
{
    /**
     * Compute comprehensive analytics for a bank statement import.
     */
    public function computeAnalytics(BankStatementImport $import): array
    {
        Log::info("Computing analytics for import #{$import->id}");

        $transactions = $import->transactions()->orderBy('transaction_date')->get();

        if ($transactions->isEmpty()) {
            throw new \Exception("No transactions found for import #{$import->id}");
        }

        // Group transactions by month
        $monthlyGroups = $transactions->groupBy(function ($transaction) {
            return $transaction->transaction_date->format('Y-m');
        });

        // Compute monthly aggregations
        $monthlyData = $this->computeMonthlyAggregations($monthlyGroups);

        // === NEW: Transaction Summary ===
        $transactionSummary = $this->computeTransactionSummary($transactions);

        // === NEW: Advanced Loan Detection ===
        $loanDetection = $this->detectLoans($transactions);

        // === NEW: Income Source Composition ===
        $incomeComposition = $this->analyzeIncomeComposition($transactions, $loanDetection);

        // Compute income analysis (enhanced with composition data)
        $incomeAnalysis = $this->analyzeIncome($transactions, $monthlyData, $incomeComposition);

        // Compute debt analysis (enhanced with loan detection)
        $debtAnalysis = $this->analyzeDebts($transactions, $loanDetection);

        // === NEW: Bulk Deposit Analysis ===
        $bulkDepositAnalysis = $this->analyzeBulkDeposits($transactions, $incomeComposition);

        // Compute risk metrics
        $riskMetrics = $this->computeRiskMetrics($transactions, $monthlyData);

        // === NEW: Behavioral Analysis ===
        $behavioralAnalysis = $this->analyzeBehavior($transactions, $monthlyData, $incomeComposition);

        // === NEW: Pass-Through Detection ===
        $passThroughAnalysis = $this->detectPassThrough($transactions);

        // Determine overall risk assessment
        $overallRisk = $this->assessOverallRisk($riskMetrics, $incomeAnalysis, $behavioralAnalysis, $loanDetection);

        // Compute ratios (enhanced with detected loans)
        $ratios = $this->computeRatios($incomeAnalysis, $debtAnalysis);

        return array_merge([
            'analysis_months' => $monthlyGroups->count(),
            'analysis_start_date' => $transactions->first()->transaction_date,
            'analysis_end_date' => $transactions->last()->transaction_date,
            'monthly_inflows' => $monthlyData['inflows'],
            'monthly_outflows' => $monthlyData['outflows'],
            'monthly_net_surplus' => $monthlyData['net_surplus'],
            'avg_monthly_inflow' => $monthlyData['avg_inflow'],
            'avg_monthly_outflow' => $monthlyData['avg_outflow'],
            'avg_net_surplus' => $monthlyData['avg_net_surplus'],
            'opening_balance' => $transactions->first()->balance,
            'closing_balance' => $transactions->last()->balance,
        ], 
        $transactionSummary,
        [
            'income_classification' => $incomeAnalysis['classification'],
            'estimated_net_income' => $incomeAnalysis['estimated_income'],
            'income_stability_score' => $incomeAnalysis['stability_score'],
            'has_regular_salary' => $incomeAnalysis['has_salary'],
            'has_business_income' => $incomeAnalysis['has_business'],
            'income_sources' => $incomeAnalysis['sources'],
        ],
        $incomeComposition,
        [
            'total_debt_obligations' => $debtAnalysis['total_debt'],
            'estimated_monthly_debt' => $debtAnalysis['monthly_debt'],
            'debt_payment_count' => $debtAnalysis['payment_count'],
            'detected_debts' => $debtAnalysis['detected_debts'],
        ],
        $loanDetection,
        $bulkDepositAnalysis,
        [
            'cash_flow_volatility_score' => $riskMetrics['volatility_score'],
            'negative_balance_days' => $riskMetrics['negative_balance_days'],
            'bounce_count' => $riskMetrics['bounce_count'],
            'gambling_transaction_count' => $riskMetrics['gambling_count'],
            'large_unexplained_outflows' => $riskMetrics['large_outflows'],
            'risk_flags' => $riskMetrics['flags'],
        ],
        $behavioralAnalysis,
        $passThroughAnalysis,
        [
            'overall_risk_assessment' => $overallRisk,
            'debt_to_income_ratio' => $ratios['dti'],
            'disposable_income_ratio' => $ratios['disposable'],
        ]);
    }

    /**
     * Compute monthly aggregations.
     */
    private function computeMonthlyAggregations(Collection $monthlyGroups): array
    {
        $inflows = [];
        $outflows = [];
        $netSurplus = [];

        foreach ($monthlyGroups as $month => $transactions) {
            $inflow = $transactions->sum('credit');
            $outflow = $transactions->sum('debit');
            $net = $inflow - $outflow;

            $inflows[] = ['month' => $month, 'inflow' => round($inflow, 2)];
            $outflows[] = ['month' => $month, 'outflow' => round($outflow, 2)];
            $netSurplus[] = ['month' => $month, 'net_surplus' => round($net, 2)];
        }

        return [
            'inflows' => $inflows,
            'outflows' => $outflows,
            'net_surplus' => $netSurplus,
            'avg_inflow' => round(collect($inflows)->avg('inflow'), 2),
            'avg_outflow' => round(collect($outflows)->avg('outflow'), 2),
            'avg_net_surplus' => round(collect($netSurplus)->avg('net_surplus'), 2),
        ];
    }

    /**
     * Analyze income patterns.
     */
    private function analyzeIncome(Collection $transactions, array $monthlyData, array $incomeComposition = []): array
    {
        $incomeTransactions = $transactions->where('is_income', true);

        // Detect salary patterns (regular monthly credits of similar amounts)
        $monthlySalaries = [];
        $salaryKeywords = ['salary', 'wage', 'payroll', 'employer', 'tra', 'nssf'];

        foreach ($incomeTransactions as $transaction) {
            foreach ($salaryKeywords as $keyword) {
                if (stripos($transaction->description, $keyword) !== false) {
                    $month = $transaction->transaction_date->format('Y-m');
                    if (!isset($monthlySalaries[$month])) {
                        $monthlySalaries[$month] = [];
                    }
                    $monthlySalaries[$month][] = $transaction->credit;
                    break;
                }
            }
        }

        $hasSalary = count($monthlySalaries) >= 2;
        $avgSalary = 0;

        if ($hasSalary) {
            $salaryAmounts = [];
            foreach ($monthlySalaries as $month => $amounts) {
                $salaryAmounts[] = max($amounts); // Take highest in month
            }
            $avgSalary = collect($salaryAmounts)->avg();
        }

        // Detect business income patterns
        $businessKeywords = ['sales', 'invoice', 'payment received', 'deposit', 'mpesa', 'tigopesa'];
        $businessIncome = 0;
        $businessTransactionCount = 0;

        foreach ($incomeTransactions as $transaction) {
            foreach ($businessKeywords as $keyword) {
                if (stripos($transaction->description, $keyword) !== false) {
                    $businessIncome += $transaction->credit;
                    $businessTransactionCount++;
                    break;
                }
            }
        }

        $hasBusiness = $businessTransactionCount >= 5;

        // Classify income
        $classification = IncomeClassification::UNKNOWN;
        if ($hasSalary && $hasBusiness) {
            $classification = IncomeClassification::MIXED;
        } elseif ($hasSalary) {
            $classification = IncomeClassification::SALARY;
        } elseif ($hasBusiness) {
            $classification = IncomeClassification::BUSINESS;
        } elseif ($incomeTransactions->count() >= 10) {
            $classification = IncomeClassification::IRREGULAR;
        }

        // Estimate net income based on recurring sources (excluding loan inflows and bulk deposits)
        // This is the correct basis for DTI calculation
        $monthsCovered = max(count($monthlyData['inflows']), 1);
        $recurringIncome = ($incomeComposition['salary_income'] ?? 0) 
                         + ($incomeComposition['business_income'] ?? 0)
                         + ($incomeComposition['transfer_inflows'] ?? 0)
                         + ($incomeComposition['other_income'] ?? 0);
        
        // ALWAYS use the recurring income from composition data (more accurate than keyword matching)
        $estimatedMonthlyIncome = $recurringIncome / $monthsCovered;

        // Calculate income stability score (0-100)
        $stabilityScore = $this->calculateIncomeStability($monthlyData['inflows']);

        // Identify income sources
        $sources = [];
        if ($hasSalary) {
            $sources[] = [
                'type' => 'salary',
                'avg_amount' => round($avgSalary, 2),
                'frequency' => 'monthly',
            ];
        }
        if ($hasBusiness) {
            $sources[] = [
                'type' => 'business',
                'avg_amount' => round($businessIncome / max($businessTransactionCount, 1), 2),
                'frequency' => 'irregular',
            ];
        }

        return [
            'classification' => $classification,
            'estimated_income' => round($estimatedMonthlyIncome, 2),
            'stability_score' => $stabilityScore,
            'has_salary' => $hasSalary,
            'has_business' => $hasBusiness,
            'sources' => $sources,
        ];
    }

    /**
     * Calculate income stability score.
     */
    private function calculateIncomeStability(array $monthlyInflows): float
    {
        if (count($monthlyInflows) < 2) {
            return 0;
        }

        $amounts = collect($monthlyInflows)->pluck('inflow');
        $avg = $amounts->avg();
        $stdDev = $this->standardDeviation($amounts);

        if ($avg == 0) {
            return 0;
        }

        // Coefficient of variation (lower is more stable)
        $cv = $stdDev / $avg;

        // Convert to stability score (0-100, higher is better)
        $score = max(0, 100 - ($cv * 100));

        return round($score, 2);
    }

    /**
     * Analyze debt obligations.
     */
    private function analyzeDebts(Collection $transactions, array $loanDetection = []): array
    {
        $debtKeywords = ['loan', 'credit', 'repayment', 'installment', 'mortgage', 'azam'];
        
        $debtPayments = [];
        $totalDebtAmount = 0;

        foreach ($transactions as $transaction) {
            if ($transaction->debit > 0) {
                foreach ($debtKeywords as $keyword) {
                    if (stripos($transaction->description, $keyword) !== false) {
                        $debtPayments[] = [
                            'description' => $transaction->description,
                            'amount' => $transaction->debit,
                            'date' => $transaction->transaction_date->format('Y-m-d'),
                        ];
                        $totalDebtAmount += $transaction->debit;

                        // Mark transaction as debt payment
                        $transaction->update(['is_debt_payment' => true]);
                        break;
                    }
                }
            }
        }

        $paymentCount = count($debtPayments);
        $monthsCovered = $transactions->last()->transaction_date->diffInMonths($transactions->first()->transaction_date) + 1;
        
        // Use detected monthly loan repayment if available, otherwise estimate
        $monthlyDebt = !empty($loanDetection) && ($loanDetection['detected_monthly_loan_repayment'] ?? 0) > 0
            ? $loanDetection['detected_monthly_loan_repayment']
            : ($monthsCovered > 0 ? $totalDebtAmount / $monthsCovered : 0);

        // Group by similar amounts to detect recurring debts
        $detectedDebts = [];
        $grouped = collect($debtPayments)->groupBy(function ($payment) {
            return round($payment['amount'] / 100) * 100; // Group by 100s
        });

        foreach ($grouped as $amountGroup => $payments) {
            if (count($payments) >= 2) {
                $detectedDebts[] = [
                    'estimated_amount' => round(collect($payments)->avg('amount'), 2),
                    'frequency' => count($payments),
                    'description' => $payments[0]['description'],
                ];
            }
        }

        return [
            'total_debt' => round($totalDebtAmount, 2),
            'monthly_debt' => round($monthlyDebt, 2),
            'payment_count' => $paymentCount,
            'detected_debts' => $detectedDebts,
        ];
    }

    /**
     * Compute risk metrics.
     */
    private function computeRiskMetrics(Collection $transactions, array $monthlyData): array
    {
        // Cash flow volatility
        $netSurplusAmounts = collect($monthlyData['net_surplus'])->pluck('net_surplus');
        $avgNetSurplus = $netSurplusAmounts->avg();
        $volatility = $avgNetSurplus != 0 
            ? ($this->standardDeviation($netSurplusAmounts) / abs($avgNetSurplus)) * 100 
            : 0;
        $volatilityScore = min(100, round($volatility, 2));

        // Negative balance days
        $negativeBalanceDays = $transactions->where('balance', '<', 0)->count();

        // Bounce/return transactions
        $bounceKeywords = ['bounce', 'return', 'insufficient', 'rejected', 'failed'];
        $bounceCount = 0;
        foreach ($transactions as $transaction) {
            foreach ($bounceKeywords as $keyword) {
                if (stripos($transaction->description, $keyword) !== false) {
                    $bounceCount++;
                    break;
                }
            }
        }

        // Gambling transactions
        $gamblingKeywords = ['bet', 'betting', 'casino', 'gamble', 'jackpot', 'sports betting'];
        $gamblingCount = 0;
        foreach ($transactions as $transaction) {
            foreach ($gamblingKeywords as $keyword) {
                if (stripos($transaction->description, $keyword) !== false) {
                    $gamblingCount++;
                    
                    // Add risk flag
                    $transaction->addRiskFlag('gambling', 'Gambling transaction detected');
                    break;
                }
            }
        }

        // Large unexplained outflows (> 50% of avg monthly inflow)
        $threshold = $monthlyData['avg_inflow'] * 0.5;
        $largeOutflows = $transactions
            ->where('debit', '>', $threshold)
            ->where('is_debt_payment', false)
            ->sum('debit');

        // Collect risk flags
        $flags = [];
        if ($volatilityScore > 50) {
            $flags[] = ['flag' => 'high_volatility', 'severity' => 'medium'];
        }
        if ($negativeBalanceDays > 5) {
            $flags[] = ['flag' => 'frequent_negative_balance', 'severity' => 'high'];
        }
        if ($bounceCount > 0) {
            $flags[] = ['flag' => 'bounced_transactions', 'severity' => 'high', 'count' => $bounceCount];
        }
        if ($gamblingCount > 0) {
            $flags[] = ['flag' => 'gambling_activity', 'severity' => 'medium', 'count' => $gamblingCount];
        }
        if ($largeOutflows > 0) {
            $flags[] = ['flag' => 'large_unexplained_outflows', 'severity' => 'medium', 'amount' => round($largeOutflows, 2)];
        }

        return [
            'volatility_score' => $volatilityScore,
            'negative_balance_days' => $negativeBalanceDays,
            'bounce_count' => $bounceCount,
            'gambling_count' => $gamblingCount,
            'large_outflows' => round($largeOutflows, 2),
            'flags' => $flags,
        ];
    }

    /**
     * Assess overall risk level.
     */
    private function assessOverallRisk(array $riskMetrics, array $incomeAnalysis, array $behavioralAnalysis = [], array $loanDetection = []): string
    {
        $riskScore = 0;

        // Get DTI ratio for context (will be calculated later, so use estimated values)
        $dti = $this->computeRatios($incomeAnalysis, $loanDetection)['dti'] ?? 0;

        // Volatility (0-30 points) - ADJUSTED: Reduced penalty when affordability is strong
        // If DTI < 20% and income is sufficient, volatility is less concerning
        $volatilityPenalty = 0;
        
        if ($riskMetrics['volatility_score'] > 70) {
            $volatilityPenalty = 30;
        } elseif ($riskMetrics['volatility_score'] > 50) {
            $volatilityPenalty = 20;
        } elseif ($riskMetrics['volatility_score'] > 30) {
            $volatilityPenalty = 10;
        }
        
        // Reduce volatility penalty by 50% if DTI is healthy (< 20%)
        // Strong affordability means volatility is less of a concern
        if ($dti > 0 && $dti < 20) {
            $volatilityPenalty = round($volatilityPenalty * 0.5);
            \Log::info("✅ Volatility penalty reduced due to healthy DTI", [
                'original_penalty' => $volatilityPenalty * 2,
                'adjusted_penalty' => $volatilityPenalty,
                'dti' => $dti,
                'volatility' => $riskMetrics['volatility_score'],
            ]);
        }
        
        $riskScore += $volatilityPenalty;

        // Negative balance (0-20 points)
        if ($riskMetrics['negative_balance_days'] > 10) {
            $riskScore += 20;
        } elseif ($riskMetrics['negative_balance_days'] > 5) {
            $riskScore += 15;
        } elseif ($riskMetrics['negative_balance_days'] > 2) {
            $riskScore += 10;
        }

        // Bounces (0-25 points)
        $riskScore += min(25, $riskMetrics['bounce_count'] * 10);

        // Gambling (0-15 points)
        if ($riskMetrics['gambling_count'] > 5) {
            $riskScore += 15;
        } elseif ($riskMetrics['gambling_count'] > 0) {
            $riskScore += 10;
        }

        // Income stability (0-10 points)
        if ($incomeAnalysis['stability_score'] < 40) {
            $riskScore += 10;
        } elseif ($incomeAnalysis['stability_score'] < 60) {
            $riskScore += 5;
        }

        // === NEW: Loan stacking penalty (0-15 points) ===
        if (!empty($loanDetection) && $loanDetection['loan_stacking_detected']) {
            $riskScore += 15;
        }

        // === NEW: Behavioral risk (0-10 points) ===
        if (!empty($behavioralAnalysis)) {
            if ($behavioralAnalysis['behavioral_risk_level'] === 'high') {
                $riskScore += 10;
            } elseif ($behavioralAnalysis['behavioral_risk_level'] === 'medium') {
                $riskScore += 5;
            }
        }

        // Assess risk level
        if ($riskScore >= 60) {
            return 'high';
        } elseif ($riskScore >= 30) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Compute financial ratios.
     */
    private function computeRatios(array $incomeAnalysis, array $debtAnalysis): array
    {
        // Handle both old and new structure
        $income = $incomeAnalysis['estimated_income'] ?? 0;
        
        // For loan detection array, extract monthly debt
        if (isset($debtAnalysis['detected_monthly_loan_repayment'])) {
            $debt = $debtAnalysis['detected_monthly_loan_repayment'];
        } else {
            $debt = $debtAnalysis['monthly_debt'] ?? 0;
        }

        $dti = $income > 0 ? round(($debt / $income) * 100, 2) : 0;
        $disposable = $income > 0 ? round((($income - $debt) / $income) * 100, 2) : 0;

        return [
            'dti' => $dti,
            'disposable' => max(0, $disposable),
        ];
    }

    /**
     * Calculate standard deviation.
     */
    private function standardDeviation(Collection $values): float
    {
        $avg = $values->avg();
        $variance = $values->map(function ($value) use ($avg) {
            return pow($value - $avg, 2);
        })->avg();

        return sqrt($variance);
    }

    /**
     * ============================================================
     * NEW METHODS FOR ADVANCED UNDERWRITING INTELLIGENCE
     * ============================================================
     */

    /**
     * 1. Compute transaction summary (total credits, debits, counts, averages)
     */
    private function computeTransactionSummary(Collection $transactions): array
    {
        $credits = $transactions->where('credit', '>', 0);
        $debits = $transactions->where('debit', '>', 0);

        $totalCredits = $credits->sum('credit');
        $totalDebits = $debits->sum('debit');
        $creditCount = $credits->count();
        $debitCount = $debits->count();

        return [
            'total_credits' => round($totalCredits, 2),
            'total_debits' => round($totalDebits, 2),
            'total_credit_count' => $creditCount,
            'total_debit_count' => $debitCount,
            'avg_credit_amount' => $creditCount > 0 ? round($totalCredits / $creditCount, 2) : 0,
            'avg_debit_amount' => $debitCount > 0 ? round($totalDebits / $debitCount, 2) : 0,
        ];
    }

    /**
     * 2. Detect loans using LoanDetectionService
     */
    private function detectLoans(Collection $transactions): array
    {
        // Transform transactions to format expected by LoanDetectionService
        $transformedTransactions = $transactions->map(function ($transaction) {
            return [
                'date' => $transaction->transaction_date->format('Y-m-d'),
                'description' => $transaction->description ?? '',
                'debit' => $transaction->debit ?? 0,
                'credit' => $transaction->credit ?? 0,
            ];
        });

        $loanDetectionService = new LoanDetectionService();
        $loanResults = $loanDetectionService->detectLoans(collect($transformedTransactions));

        return [
            'detected_loan_count' => $loanResults['loan_count'],
            'detected_monthly_loan_repayment' => round($loanResults['total_monthly_repayment'], 2),
            'detected_loans' => $loanResults['detected_loans'],
            'loan_stacking_detected' => $loanResults['loan_stacking_detected'],
            'loan_detection_confidence' => $loanResults['confidence'],
            'loan_inflows' => round($loanResults['loan_inflows'], 2),
        ];
    }

    /**
     * 3. Analyze income source composition
     */
    private function analyzeIncomeComposition(Collection $transactions, array $loanDetection): array
    {
        $credits = $transactions->where('credit', '>', 0);
        
        if ($credits->isEmpty()) {
            return [
                'salary_income' => 0,
                'business_income' => 0,
                'loan_inflows' => 0,
                'bulk_deposits' => 0,
                'transfer_inflows' => 0,
                'other_income' => 0,
                'income_composition_breakdown' => [],
            ];
        }

        $salaryIncome = 0;
        $businessIncome = 0;
        $transferInflows = 0;
        $loanInflowsTotal = 0;
        $bulkDeposits = 0;
        $otherIncome = 0;

        // Keywords for classification
        $salaryKeywords = ['salary', 'wage', 'payroll', 'employer', 'employment', 'tra', 'nssf'];
        $businessKeywords = ['sales', 'invoice', 'payment received', 'pos', 'till', 'merchant'];
        $transferKeywords = ['transfer', 'mpesa', 'tigopesa', 'airtel money', 'halopesa', 'tpesa'];
        $loanKeywords = ['loan', 'mkopo', 'disbursement', 'advance', 'credit line'];

        // Calculate bulk deposit threshold (3x average credit)
        $avgCredit = $credits->avg('credit');
        $bulkThreshold = $avgCredit * 3;

        $incomeBreakdown = [];

        foreach ($credits as $transaction) {
            $description = strtolower($transaction->description ?? '');
            $amount = $transaction->credit;
            $source = null;

            // Priority 1: Check for loan disbursements (explicit keywords)
            foreach ($loanKeywords as $keyword) {
                if (stripos($description, $keyword) !== false) {
                    $loanInflowsTotal += $amount;
                    $source = 'loan_inflow';
                    break;
                }
            }

            // Priority 2: Check for salary
            if (!$source) {
                foreach ($salaryKeywords as $keyword) {
                    if (stripos($description, $keyword) !== false) {
                        $salaryIncome += $amount;
                        $source = 'salary';
                        break;
                    }
                }
            }

            // Priority 3: Check for business income
            if (!$source) {
                foreach ($businessKeywords as $keyword) {
                    if (stripos($description, $keyword) !== false) {
                        $businessIncome += $amount;
                        $source = 'business';
                        break;
                    }
                }
            }

            // Priority 4: Check for bulk deposits (large unexplained amounts)
            if (!$source && $amount > $bulkThreshold) {
                $bulkDeposits += $amount;
                $source = 'bulk_deposit';
            }

            // Priority 5: Check for transfers
            if (!$source) {
                foreach ($transferKeywords as $keyword) {
                    if (stripos($description, $keyword) !== false) {
                        $transferInflows += $amount;
                        $source = 'transfer';
                        break;
                    }
                }
            }

            // Priority 6: Everything else is "other"
            if (!$source) {
                $otherIncome += $amount;
                $source = 'other';
            }

            $incomeBreakdown[] = [
                'date' => $transaction->transaction_date->format('Y-m-d'),
                'amount' => $amount,
                'source' => $source,
                'description' => $transaction->description,
            ];
        }

        // Verify totals match (all credits should be accounted for)
        $totalClassified = $salaryIncome + $businessIncome + $loanInflowsTotal + $bulkDeposits + $transferInflows + $otherIncome;
        $totalCredits = $credits->sum('credit');
        
        // Enhanced validation logging
        if (abs($totalClassified - $totalCredits) > 1) { // Allow 1 TZS rounding difference
            \Log::error("❌ Income composition MISMATCH - Double counting detected!", [
                'salary_income' => $salaryIncome,
                'business_income' => $businessIncome,
                'loan_inflows' => $loanInflowsTotal,
                'bulk_deposits' => $bulkDeposits,
                'transfer_inflows' => $transferInflows,
                'other_income' => $otherIncome,
                'total_classified' => $totalClassified,
                'total_credits' => $totalCredits,
                'difference' => $totalClassified - $totalCredits,
                'difference_percentage' => round((($totalClassified - $totalCredits) / $totalCredits) * 100, 2) . '%',
            ]);
        } else {
            \Log::info("✅ Income composition validated - Categories are mutually exclusive", [
                'total_credits' => $totalCredits,
                'total_classified' => $totalClassified,
                'breakdown' => [
                    'salary' => $salaryIncome . ' (' . round(($salaryIncome / $totalCredits) * 100, 1) . '%)',
                    'business' => $businessIncome . ' (' . round(($businessIncome / $totalCredits) * 100, 1) . '%)',
                    'loans' => $loanInflowsTotal . ' (' . round(($loanInflowsTotal / $totalCredits) * 100, 1) . '%)',
                    'bulk' => $bulkDeposits . ' (' . round(($bulkDeposits / $totalCredits) * 100, 1) . '%)',
                    'transfers' => $transferInflows . ' (' . round(($transferInflows / $totalCredits) * 100, 1) . '%)',
                    'other' => $otherIncome . ' (' . round(($otherIncome / $totalCredits) * 100, 1) . '%)',
                ],
            ]);
        }

        return [
            'salary_income' => round($salaryIncome, 2),
            'business_income' => round($businessIncome, 2),
            'loan_inflows' => round($loanInflowsTotal, 2),
            'bulk_deposits' => round($bulkDeposits, 2),
            'transfer_inflows' => round($transferInflows, 2),
            'other_income' => round($otherIncome, 2),
            'income_composition_breakdown' => $incomeBreakdown,
        ];
    }

    /**
     * Helper to calculate bulk deposits total
     */
    private function calculateBulkDepositsTotal(Collection $credits): float
    {
        if ($credits->isEmpty()) {
            return 0;
        }

        $avgCredit = $credits->avg('credit');
        $threshold = $avgCredit * 3; // 3x average is considered "bulk"

        return $credits->where('credit', '>', $threshold)->sum('credit');
    }

    /**
     * 4. Analyze bulk deposits
     */
    private function analyzeBulkDeposits(Collection $transactions, array $incomeComposition): array
    {
        $credits = $transactions->where('credit', '>', 0);

        if ($credits->isEmpty()) {
            return [
                'bulk_deposit_count' => 0,
                'largest_single_deposit' => 0,
                'bulk_deposit_details' => [],
                'suspicious_deposits_flagged' => false,
            ];
        }

        // Calculate monthly average inflow (more accurate than simple average)
        $monthlyGroups = $transactions->where('credit', '>', 0)->groupBy(function ($t) {
            return $t->transaction_date->format('Y-m');
        });
        
        $monthlyInflows = $monthlyGroups->map(function ($monthTransactions) {
            return $monthTransactions->sum('credit');
        });
        
        $avgMonthlyInflow = $monthlyInflows->avg();
        
        // Use 2x monthly average as threshold (business-friendly)
        // This is more reasonable than 3x single transaction average
        $threshold = $avgMonthlyInflow * 2;

        $bulkDeposits = $credits->where('credit', '>', $threshold);
        $largestDeposit = $credits->max('credit');

        $bulkDepositDetails = [];
        $suspiciousCount = 0;

        foreach ($bulkDeposits as $deposit) {
            $description = strtolower($deposit->description ?? '');
            
            // Categorize source
            $source = 'unknown';
            $suspicious = false;

            $loanKeywords = ['loan', 'mkopo', 'disbursement', 'advance'];
            $salaryKeywords = ['salary', 'wage', 'payroll'];
            $transferKeywords = ['transfer', 'mpesa'];

            foreach ($loanKeywords as $keyword) {
                if (stripos($description, $keyword) !== false) {
                    $source = 'loan';
                    break;
                }
            }

            if ($source === 'unknown') {
                foreach ($salaryKeywords as $keyword) {
                    if (stripos($description, $keyword) !== false) {
                        $source = 'salary';
                        break;
                    }
                }
            }

            if ($source === 'unknown') {
                foreach ($transferKeywords as $keyword) {
                    if (stripos($description, $keyword) !== false) {
                        $source = 'transfer';
                        break;
                    }
                }
            }

            // Flag as suspicious if source unknown and amount > 2x monthly average
            // More lenient for business clients with legitimate large deposits
            if ($source === 'unknown' && $deposit->credit > ($avgMonthlyInflow * 2)) {
                $suspicious = true;
                $suspiciousCount++;
            }

            $bulkDepositDetails[] = [
                'date' => $deposit->transaction_date->format('Y-m-d'),
                'amount' => round($deposit->credit, 2),
                'description' => $deposit->description,
                'source' => $source,
                'suspicious' => $suspicious,
                'reason' => $suspicious ? 'Large unexplained deposit' : null,
            ];
        }

        return [
            'bulk_deposit_count' => $bulkDeposits->count(),
            'largest_single_deposit' => round($largestDeposit, 2),
            'bulk_deposit_details' => $bulkDepositDetails,
            'suspicious_deposits_flagged' => $suspiciousCount > 0,
        ];
    }

    /**
     * 5. Analyze transaction behavior and patterns
     */
    private function analyzeBehavior(Collection $transactions, array $monthlyData, array $incomeComposition): array
    {
        if ($transactions->isEmpty()) {
            return [
                'transaction_frequency_score' => 0,
                'cash_withdrawal_ratio' => 0,
                'income_volatility_coefficient' => 0,
                'transaction_pattern' => 'unknown',
                'behavioral_risk_level' => 'low',
                'behavioral_flags' => [],
            ];
        }

        // Transaction frequency analysis
        $totalDays = $transactions->first()->transaction_date->diffInDays($transactions->last()->transaction_date) + 1;
        $transactionsPerDay = $totalDays > 0 ? $transactions->count() / $totalDays : 0;
        
        // Normalize to 0-100 scale (assume 5+ transactions/day = 100)
        $frequencyScore = min(100, round($transactionsPerDay * 20, 2));

        // Cash withdrawal analysis
        $cashKeywords = ['cash', 'atm', 'withdrawal', 'withdrw'];
        $cashWithdrawals = 0;
        $totalDebits = $transactions->sum('debit');

        foreach ($transactions as $transaction) {
            if ($transaction->debit > 0) {
                foreach ($cashKeywords as $keyword) {
                    if (stripos($transaction->description ?? '', $keyword) !== false) {
                        $cashWithdrawals += $transaction->debit;
                        break;
                    }
                }
            }
        }

        $cashWithdrawalRatio = $totalDebits > 0 ? round(($cashWithdrawals / $totalDebits) * 100, 2) : 0;

        // Income volatility (coefficient of variation) - FIXED: Calculate on recurring income only
        // Use the income composition breakdown to identify recurring income month-by-month
        $recurringIncomeByMonth = [];
        
        if (isset($incomeComposition['income_composition_breakdown']) && 
            is_array($incomeComposition['income_composition_breakdown'])) {
            
            foreach ($incomeComposition['income_composition_breakdown'] as $item) {
                // Only count recurring income sources (exclude bulk deposits and loan inflows)
                if (in_array($item['source'], ['salary', 'business', 'transfer', 'other'])) {
                    $month = substr($item['date'], 0, 7); // Extract 'Y-m'
                    if (!isset($recurringIncomeByMonth[$month])) {
                        $recurringIncomeByMonth[$month] = 0;
                    }
                    $recurringIncomeByMonth[$month] += $item['amount'];
                }
            }
        }
        
        $recurringMonthlyInflows = collect(array_values($recurringIncomeByMonth));
        $avgRecurringInflow = $recurringMonthlyInflows->avg();
        
        // Calculate volatility on recurring income only
        $incomeVolatility = ($avgRecurringInflow > 0 && $recurringMonthlyInflows->count() > 1)
            ? round(($this->standardDeviation($recurringMonthlyInflows) / $avgRecurringInflow) * 100, 2) 
            : 0;

        // Transaction pattern classification
        $pattern = 'regular';
        if ($transactionsPerDay < 0.5) {
            $pattern = 'sporadic';
        } elseif ($incomeVolatility > 50) {
            $pattern = 'irregular';
        }

        // Behavioral risk assessment
        $behavioralFlags = [];
        $riskLevel = 'low';

        if ($incomeVolatility > 70) {
            $behavioralFlags[] = [
                'flag' => 'high_income_volatility',
                'value' => $incomeVolatility,
                'severity' => 'high',
            ];
        }

        if ($cashWithdrawalRatio > 70) {
            $behavioralFlags[] = [
                'flag' => 'high_cash_withdrawal_ratio',
                'value' => $cashWithdrawalRatio,
                'severity' => 'medium',
            ];
        }

        if ($pattern === 'sporadic') {
            $behavioralFlags[] = [
                'flag' => 'sporadic_transaction_pattern',
                'severity' => 'medium',
            ];
        }

        // Determine overall behavioral risk
        $highSeverityCount = collect($behavioralFlags)->where('severity', 'high')->count();
        $mediumSeverityCount = collect($behavioralFlags)->where('severity', 'medium')->count();

        if ($highSeverityCount > 0 || $mediumSeverityCount >= 2) {
            $riskLevel = 'high';
        } elseif ($mediumSeverityCount > 0) {
            $riskLevel = 'medium';
        }

        return [
            'transaction_frequency_score' => $frequencyScore,
            'cash_withdrawal_ratio' => $cashWithdrawalRatio,
            'income_volatility_coefficient' => $incomeVolatility,
            'transaction_pattern' => $pattern,
            'behavioral_risk_level' => $riskLevel,
            'behavioral_flags' => $behavioralFlags,
        ];
    }

    /**
     * Detect pass-through / immediate cash-out patterns
     * Suspicious "money in → money out" behavior
     */
    private function detectPassThrough(Collection $transactions): array
    {
        $config = config('mortgage.analytics.pass_through');
        
        if (!$config['enabled']) {
            return [
                'pass_through_count' => 0,
                'pass_through_total_amount' => 0,
                'pass_through_ratio' => 0,
                'pass_through_risk_flag' => false,
                'pass_through_transactions' => [],
            ];
        }

        $amountTolerance = $config['amount_tolerance_percentage'] / 100; // Convert to decimal
        $timeWindowDays = $config['time_window_days'];
        $riskThreshold = $config['risk_threshold_ratio'];

        $credits = $transactions->where('credit', '>', 0)->sortBy('transaction_date');
        $debits = $transactions->where('debit', '>', 0)->sortBy('transaction_date');
        
        $totalCredits = $credits->sum('credit');
        $passThroughCount = 0;
        $passThroughAmount = 0;
        $passThroughTransactions = [];

        foreach ($credits as $credit) {
            // Look for matching debits within time window
            $matchingDebits = $debits->filter(function ($debit) use ($credit, $amountTolerance, $timeWindowDays) {
                // Check time window
                $daysDiff = $credit->transaction_date->diffInDays($debit->transaction_date, false);
                if ($daysDiff < 0 || $daysDiff > $timeWindowDays) {
                    return false;
                }

                // Check amount similarity (±tolerance%)
                $lowerBound = $credit->credit * (1 - $amountTolerance);
                $upperBound = $credit->credit * (1 + $amountTolerance);
                
                return $debit->debit >= $lowerBound && $debit->debit <= $upperBound;
            });

            if ($matchingDebits->isNotEmpty()) {
                $passThroughCount++;
                $passThroughAmount += $credit->credit;
                
                // Track the pattern
                $passThroughTransactions[] = [
                    'credit_date' => $credit->transaction_date->format('Y-m-d'),
                    'credit_amount' => $credit->credit,
                    'credit_description' => $credit->description,
                    'debit_date' => $matchingDebits->first()->transaction_date->format('Y-m-d'),
                    'debit_amount' => $matchingDebits->first()->debit,
                    'debit_description' => $matchingDebits->first()->description,
                    'days_difference' => $credit->transaction_date->diffInDays($matchingDebits->first()->transaction_date),
                ];
            }
        }

        $passThroughRatio = $totalCredits > 0 ? ($passThroughAmount / $totalCredits) : 0;
        $riskFlag = $passThroughRatio > $riskThreshold;

        return [
            'pass_through_count' => $passThroughCount,
            'pass_through_total_amount' => round($passThroughAmount, 2),
            'pass_through_ratio' => round($passThroughRatio * 100, 2), // As percentage
            'pass_through_risk_flag' => $riskFlag,
            'pass_through_transactions' => array_slice($passThroughTransactions, 0, 10), // Limit to 10 examples
        ];
    }
}


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

        // Compute income analysis
        $incomeAnalysis = $this->analyzeIncome($transactions, $monthlyData);

        // Compute debt analysis
        $debtAnalysis = $this->analyzeDebts($transactions);

        // Compute risk metrics
        $riskMetrics = $this->computeRiskMetrics($transactions, $monthlyData);

        // Determine overall risk assessment
        $overallRisk = $this->assessOverallRisk($riskMetrics, $incomeAnalysis);

        // Compute ratios
        $ratios = $this->computeRatios($incomeAnalysis, $debtAnalysis);

        return [
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
            'income_classification' => $incomeAnalysis['classification'],
            'estimated_net_income' => $incomeAnalysis['estimated_income'],
            'income_stability_score' => $incomeAnalysis['stability_score'],
            'has_regular_salary' => $incomeAnalysis['has_salary'],
            'has_business_income' => $incomeAnalysis['has_business'],
            'income_sources' => $incomeAnalysis['sources'],
            'total_debt_obligations' => $debtAnalysis['total_debt'],
            'estimated_monthly_debt' => $debtAnalysis['monthly_debt'],
            'debt_payment_count' => $debtAnalysis['payment_count'],
            'detected_debts' => $debtAnalysis['detected_debts'],
            'cash_flow_volatility_score' => $riskMetrics['volatility_score'],
            'negative_balance_days' => $riskMetrics['negative_balance_days'],
            'bounce_count' => $riskMetrics['bounce_count'],
            'gambling_transaction_count' => $riskMetrics['gambling_count'],
            'large_unexplained_outflows' => $riskMetrics['large_outflows'],
            'risk_flags' => $riskMetrics['flags'],
            'overall_risk_assessment' => $overallRisk,
            'debt_to_income_ratio' => $ratios['dti'],
            'disposable_income_ratio' => $ratios['disposable'],
        ];
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
    private function analyzeIncome(Collection $transactions, array $monthlyData): array
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

        // Estimate net income
        $estimatedIncome = $hasSalary ? $avgSalary : $monthlyData['avg_inflow'];

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
            'estimated_income' => round($estimatedIncome, 2),
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
    private function analyzeDebts(Collection $transactions): array
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
        $monthlyDebt = $monthsCovered > 0 ? $totalDebtAmount / $monthsCovered : 0;

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
    private function assessOverallRisk(array $riskMetrics, array $incomeAnalysis): string
    {
        $riskScore = 0;

        // Volatility (0-30 points)
        if ($riskMetrics['volatility_score'] > 70) {
            $riskScore += 30;
        } elseif ($riskMetrics['volatility_score'] > 50) {
            $riskScore += 20;
        } elseif ($riskMetrics['volatility_score'] > 30) {
            $riskScore += 10;
        }

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
        $income = $incomeAnalysis['estimated_income'];
        $debt = $debtAnalysis['monthly_debt'];

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
}

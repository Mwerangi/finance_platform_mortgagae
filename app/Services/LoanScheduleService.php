<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Service for generating loan repayment schedules
 */
class LoanScheduleService
{
    /**
     * Generate loan repayment schedule
     * 
     * @param int $loanId Loan ID
     * @param float $principal Principal amount
     * @param float $annualRate Annual interest rate percentage
     * @param int $termMonths Loan term in months
     * @param string $calculationMethod Either 'reducing_balance' or 'flat_rate'
     * @param Carbon $startDate Starting date for schedule
     * @return array Array of installments with payment details
     */
    public function generateSchedule(
        int $loanId,
        float $principal,
        float $annualRate,
        int $termMonths,
        string $calculationMethod,
        Carbon $startDate
    ): array {
        if ($calculationMethod === 'reducing_balance') {
            return $this->generateReducingBalanceSchedule(
                $loanId,
                $principal,
                $annualRate,
                $termMonths,
                $startDate
            );
        } else {
            return $this->generateFlatRateSchedule(
                $loanId,
                $principal,
                $annualRate,
                $termMonths,
                $startDate
            );
        }
    }

    /**
     * Generate reducing balance schedule
     * 
     * @param int $loanId Loan ID
     * @param float $principal Principal amount
     * @param float $annualRate Annual interest rate
     * @param int $termMonths Term in months
     * @param Carbon $startDate Start date
     * @return array Schedule array
     */
    private function generateReducingBalanceSchedule(
        int $loanId,
        float $principal,
        float $annualRate,
        int $termMonths,
        Carbon $startDate
    ): array {
        $monthlyRate = $annualRate / 100 / 12;
        $schedule = [];
        $balance = $principal;

        // Calculate monthly installment using PMT formula
        if ($monthlyRate == 0) {
            $monthlyInstallment = $principal / $termMonths;
        } else {
            // PMT formula: P * [r(1+r)^n] / [(1+r)^n - 1]
            $monthlyInstallment = $principal * 
                ($monthlyRate * pow(1 + $monthlyRate, $termMonths)) / 
                (pow(1 + $monthlyRate, $termMonths) - 1);
        }

        for ($i = 1; $i <= $termMonths; $i++) {
            // Calculate interest on remaining balance
            $interestDue = $balance * $monthlyRate;
            
            // Principal payment is installment minus interest
            $principalDue = $monthlyInstallment - $interestDue;
            
            // Ensure we don't overpay on the last installment
            if ($i === $termMonths) {
                $principalDue = $balance;
                $totalDue = $principalDue + $interestDue;
            } else {
                $totalDue = $monthlyInstallment;
            }

            // Round values for storage
            $principalDueRounded = round($principalDue, 2);
            $interestDueRounded = round($interestDue, 2);
            $totalDueRounded = round($totalDue, 2);

            // Calculate due date (add months to start date)
            $dueDate = $startDate->copy()->addMonths($i);

            $schedule[] = [
                'installment_number' => $i,
                'due_date' => $dueDate->toDateString(),
                'principal_due' => $principalDueRounded,
                'interest_due' => $interestDueRounded,
                'total_due' => $totalDueRounded,
                'balance_after_payment' => round($balance - $principalDue, 2),
            ];

            // Update balance using actual (unrounded) principal for precise calculation
            $balance -= $principalDue;
        }

        // Adjust last installment for rounding errors
        $totalPrincipalPaid = array_sum(array_column($schedule, 'principal_due'));
        $roundingAdjustment = round($principal - $totalPrincipalPaid, 2);
        
        if (abs($roundingAdjustment) > 0) {
            $lastIndex = count($schedule) - 1;
            $schedule[$lastIndex]['principal_due'] = round($schedule[$lastIndex]['principal_due'] + $roundingAdjustment, 2);
            $schedule[$lastIndex]['total_due'] = round($schedule[$lastIndex]['principal_due'] + $schedule[$lastIndex]['interest_due'], 2);
            $schedule[$lastIndex]['balance_after_payment'] = 0.00;
        }

        return $schedule;
    }

    /**
     * Generate flat rate schedule
     * 
     * @param int $loanId Loan ID
     * @param float $principal Principal amount
     * @param float $annualRate Annual interest rate
     * @param int $termMonths Term in months
     * @param Carbon $startDate Start date
     * @return array Schedule array
     */
    private function generateFlatRateSchedule(
        int $loanId,
        float $principal,
        float $annualRate,
        int $termMonths,
        Carbon $startDate
    ): array {
        // Calculate total interest on original principal
        $totalInterest = ($principal * $annualRate / 100) * ($termMonths / 12);
        $totalRepayment = $principal + $totalInterest;
        
        // Fixed monthly installment
        $monthlyInstallment = $totalRepayment / $termMonths;
        
        // Fixed principal and interest portions
        $principalPerMonth = $principal / $termMonths;
        $interestPerMonth = $totalInterest / $termMonths;

        $schedule = [];
        $balance = $principal;

        for ($i = 1; $i <= $termMonths; $i++) {
            // Calculate due date (add months to start date)
            $dueDate = $startDate->copy()->addMonths($i);

            $schedule[] = [
                'installment_number' => $i,
                'due_date' => $dueDate->toDateString(),
                'principal_due' => round($principalPerMonth, 2),
                'interest_due' => round($interestPerMonth, 2),
                'total_due' => round($monthlyInstallment, 2),
                'balance_after_payment' => round($balance - $principalPerMonth, 2),
            ];

            $balance -= $principalPerMonth;
        }

        return $schedule;
    }
}

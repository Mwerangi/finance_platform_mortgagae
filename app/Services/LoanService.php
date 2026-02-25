<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use Carbon\Carbon;

class LoanService
{
    /**
     * Create a loan from an approved application
     */
    public function createLoanFromApplication($application, $underwritingDecision): Loan
    {
        $loan = Loan::create([
            'application_id' => $application->id,
            'customer_id' => $application->customer_id,
            'institution_id' => $application->institution_id,
            'loan_product_id' => $application->loan_product_id,
            'underwriting_decision_id' => $underwritingDecision->id,
            'approved_amount' => $underwritingDecision->approved_amount,
            'approved_tenure_months' => $underwritingDecision->approved_tenure_months,
            'approved_interest_rate' => $underwritingDecision->approved_interest_rate,
            'interest_method' => $underwritingDecision->approved_interest_method,
            'monthly_installment' => $underwritingDecision->final_monthly_installment,
            'total_interest' => $underwritingDecision->final_total_interest,
            'total_repayment' => $underwritingDecision->final_total_repayment,
            'property_type' => $application->property_type,
            'property_value' => $application->property_value,
            'property_address' => $application->property_address,
            'ltv_ratio' => $underwritingDecision->final_ltv_ratio,
            'status' => 'pending_disbursement',
            'created_by' => auth()->id(),
        ]);

        return $loan;
    }

    /**
     * Generate loan repayment schedule
     */
    public function generateSchedule(Loan $loan, Carbon $firstInstallmentDate): array
    {
        if ($loan->interest_method === 'reducing_balance') {
            return $this->generateReducingBalanceSchedule($loan, $firstInstallmentDate);
        } else {
            return $this->generateFlatRateSchedule($loan, $firstInstallmentDate);
        }
    }

    /**
     * Generate reducing balance schedule
     */
    private function generateReducingBalanceSchedule(Loan $loan, Carbon $firstInstallmentDate): array
    {
        $schedule = [];
        $principal = $loan->approved_amount;
        $monthlyRate = ($loan->approved_interest_rate / 100) / 12;
        $tenure = $loan->approved_tenure_months;
        $monthlyInstallment = $loan->monthly_installment;
        
        $openingBalance = $principal;
        $dueDate = $firstInstallmentDate->copy();
        
        for ($i = 1; $i <= $tenure; $i++) {
            // Interest for this period
            $interestDue = $openingBalance * $monthlyRate;
            
            // Principal for this period
            $principalDue = $monthlyInstallment - $interestDue;
            
            // Closing balance
            $closingBalance = $openingBalance - $principalDue;
            
            // Handle last installment rounding
            if ($i === $tenure) {
                $principalDue = $openingBalance;
                $closingBalance = 0;
                $monthlyInstallment = $principalDue + $interestDue;
            }
            
            $schedule[] = [
                'loan_id' => $loan->id,
                'institution_id' => $loan->institution_id,
                'installment_number' => $i,
                'due_date' => $dueDate->toDateString(),
                'status' => 'pending',
                'principal_due' => round($principalDue, 2),
                'interest_due' => round($interestDue, 2),
                'total_due' => round($principalDue + $interestDue, 2),
                'penalties_due' => 0,
                'fees_due' => 0,
                'opening_balance' => round($openingBalance, 2),
                'closing_balance' => round($closingBalance, 2),
                'principal_paid' => 0,
                'interest_paid' => 0,
                'penalties_paid' => 0,
                'fees_paid' => 0,
                'total_paid' => 0,
                'balance_remaining' => round($principalDue + $interestDue, 2),
                'days_past_due' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // Move to next month
            $openingBalance = $closingBalance;
            $dueDate->addMonth();
        }
        
        return $schedule;
    }

    /**
     * Generate flat rate schedule
     */
    private function generateFlatRateSchedule(Loan $loan, Carbon $firstInstallmentDate): array
    {
        $schedule = [];
        $principal = $loan->approved_amount;
        $annualRate = $loan->approved_interest_rate / 100;
        $tenure = $loan->approved_tenure_months;
        
        // Total interest for entire loan period
        $totalInterest = $principal * $annualRate * ($tenure / 12);
        
        // Interest per month (flat - same every month)
        $interestPerMonth = $totalInterest / $tenure;
        
        // Principal per month (equal installments)
        $principalPerMonth = $principal / $tenure;
        
        // Monthly installment
        $monthlyInstallment = $principalPerMonth + $interestPerMonth;
        
        $openingBalance = $principal;
        $dueDate = $firstInstallmentDate->copy();
        
        for ($i = 1; $i <= $tenure; $i++) {
            $principalDue = $principalPerMonth;
            $interestDue = $interestPerMonth;
            $closingBalance = $openingBalance - $principalDue;
            
            // Handle last installment rounding
            if ($i === $tenure) {
                $principalDue = $openingBalance;
                $closingBalance = 0;
                $monthlyInstallment = $principalDue + $interestDue;
            }
            
            $schedule[] = [
                'loan_id' => $loan->id,
                'institution_id' => $loan->institution_id,
                'installment_number' => $i,
                'due_date' => $dueDate->toDateString(),
                'status' => 'pending',
                'principal_due' => round($principalDue, 2),
                'interest_due' => round($interestDue, 2),
                'total_due' => round($principalDue + $interestDue, 2),
                'penalties_due' => 0,
                'fees_due' => 0,
                'opening_balance' => round($openingBalance, 2),
                'closing_balance' => round($closingBalance, 2),
                'principal_paid' => 0,
                'interest_paid' => 0,
                'penalties_paid' => 0,
                'fees_paid' => 0,
                'total_paid' => 0,
                'balance_remaining' => round($principalDue + $interestDue, 2),
                'days_past_due' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            $openingBalance = $closingBalance;
            $dueDate->addMonth();
        }
        
        return $schedule;
    }

    /**
     * Save schedule to database
     */
    public function saveSchedule(array $schedule): void
    {
        LoanSchedule::insert($schedule);
    }

    /**
     * Regenerate schedule (after restructuring or changes)
     */
    public function regenerateSchedule(Loan $loan, Carbon $firstInstallmentDate): void
    {
        // Delete existing schedule
        $loan->schedules()->delete();
        
        // Generate new schedule
        $schedule = $this->generateSchedule($loan, $firstInstallmentDate);
        
        // Save new schedule
        $this->saveSchedule($schedule);
    }

    /**
     * Calculate early settlement amount
     */
    public function calculateEarlySettlement(Loan $loan, Carbon $settlementDate = null): array
    {
        $settlementDate = $settlementDate ?? now();
        
        // Outstanding principal
        $principalOutstanding = $loan->principal_outstanding;
        
        // Outstanding interest (if reducing balance, recalculate based on remaining tenure)
        $interestOutstanding = $loan->interest_outstanding;
        
        // Penalties and fees
        $penaltiesOutstanding = $loan->penalties_outstanding;
        $feesOutstanding = $loan->fees_outstanding;
        
        // Early settlement penalty
        $earlySettlementPenalty = 0;
        if ($loan->early_settlement_penalty_rate && $loan->early_settlement_penalty_rate > 0) {
            $earlySettlementPenalty = $principalOutstanding * ($loan->early_settlement_penalty_rate / 100);
        }
        
        // Total settlement amount
        $totalSettlement = $principalOutstanding + $interestOutstanding + $penaltiesOutstanding + $feesOutstanding + $earlySettlementPenalty;
        
        // Savings (remaining scheduled interest vs actual interest)
        $remainingScheduledInterest = $loan->total_interest - $loan->interest_paid;
        $interestSavings = $remainingScheduledInterest - $interestOutstanding;
        
        return [
            'settlement_date' => $settlementDate->toDateString(),
            'principal_outstanding' => round($principalOutstanding, 2),
            'interest_outstanding' => round($interestOutstanding, 2),
            'penalties_outstanding' => round($penaltiesOutstanding, 2),
            'fees_outstanding' => round($feesOutstanding, 2),
            'early_settlement_penalty' => round($earlySettlementPenalty, 2),
            'total_settlement_amount' => round($totalSettlement, 2),
            'remaining_scheduled_interest' => round($remainingScheduledInterest, 2),
            'interest_savings' => round($interestSavings, 2),
            'installments_remaining' => $loan->installments_remaining,
            'months_saved' => $loan->installments_remaining,
        ];
    }

    /**
     * Process loan disbursement and activation
     */
    public function disburseAndActivate(Loan $loan, array $disbursementData): void
    {
        // Disburse
        $loan->disburse($disbursementData);
        
        // Calculate dates
        $disbursementDate = Carbon::parse($disbursementData['disbursement_date']);
        $firstInstallmentDate = $disbursementDate->copy()->addMonth()->startOfMonth();
        $maturityDate = $firstInstallmentDate->copy()->addMonths($loan->approved_tenure_months - 1);
        
        // Activate
        $loan->activate([
            'activation_date' => $disbursementDate,
            'first_installment_date' => $firstInstallmentDate,
            'maturity_date' => $maturityDate,
        ]);
        
        // Generate schedule
        $schedule = $this->generateSchedule($loan, $firstInstallmentDate);
        $this->saveSchedule($schedule);
        
        // Update next payment details
        $firstSchedule = $loan->schedules()->first();
        if ($firstSchedule) {
            $loan->update([
                'next_payment_due_date' => $firstSchedule->due_date,
                'next_payment_amount' => $firstSchedule->total_due,
            ]);
        }
    }

    /**
     * Update loan aging and DPD
     */
    public function updateAgingAndDPD(Loan $loan): void
    {
        // Get oldest overdue installment
        $oldestOverdue = $loan->schedules()
            ->unpaid()
            ->where('due_date', '<', now())
            ->orderBy('due_date', 'asc')
            ->first();
        
        if ($oldestOverdue) {
            $daysPastDue = now()->diffInDays($oldestOverdue->due_date);
            
            // Update DPD
            $loan->update(['days_past_due' => $daysPastDue]);
            
            // Update aging bucket
            $agingBucket = match(true) {
                $daysPastDue >= 180 => 'bucket_180',
                $daysPastDue >= 90 => 'npl',
                $daysPastDue >= 61 => 'bucket_90',
                $daysPastDue >= 31 => 'bucket_60',
                $daysPastDue >= 1 => 'bucket_30',
                default => 'current',
            };
            
            $loan->update(['aging_bucket' => $agingBucket]);
            
            // Update arrears amount (sum of all overdue installments)
            $arrearsAmount = $loan->schedules()
                ->unpaid()
                ->where('due_date', '<', now())
                ->sum('balance_remaining');
            
            $loan->update(['arrears_amount' => $arrearsAmount]);
            
            // Mark schedules as overdue
            $loan->schedules()
                ->where('due_date', '<', now())
                ->whereIn('status', ['pending', 'partially_paid'])
                ->get()
                ->each(function($schedule) {
                    $schedule->markAsOverdue();
                });
        } else {
            // No overdue installments
            $loan->update([
                'days_past_due' => 0,
                'aging_bucket' => 'current',
                'arrears_amount' => 0,
            ]);
        }
    }

    /**
     * Get loan summary
     */
    public function getLoanSummary(Loan $loan): array
    {
        return [
            'loan' => [
                'id' => $loan->id,
                'loan_account_number' => $loan->loan_account_number,
                'status' => $loan->status,
                'status_color' => $loan->status_color,
            ],
            'customer' => [
                'id' => $loan->customer_id,
                'name' => $loan->customer->full_name,
                'customer_number' => $loan->customer->customer_number,
            ],
            'loan_details' => [
                'approved_amount' => $loan->approved_amount,
                'disbursed_amount' => $loan->disbursed_amount,
                'approved_tenure_months' => $loan->approved_tenure_months,
                'interest_rate' => $loan->approved_interest_rate,
                'interest_method' => $loan->interest_method,
                'monthly_installment' => $loan->monthly_installment,
                'total_interest' => $loan->total_interest,
                'total_repayment' => $loan->total_repayment,
            ],
            'dates' => [
                'disbursement_date' => $loan->disbursement_date,
                'activation_date' => $loan->activation_date,
                'first_installment_date' => $loan->first_installment_date,
                'maturity_date' => $loan->maturity_date,
                'days_to_maturity' => $loan->days_to_maturity,
                'months_elapsed' => $loan->months_elapsed,
            ],
            'balances' => [
                'principal_outstanding' => $loan->principal_outstanding,
                'interest_outstanding' => $loan->interest_outstanding,
                'total_outstanding' => $loan->total_outstanding,
                'penalties_outstanding' => $loan->penalties_outstanding,
                'fees_outstanding' => $loan->fees_outstanding,
                'outstanding_percentage' => $loan->outstanding_percentage,
            ],
            'payments' => [
                'total_paid' => $loan->total_paid,
                'principal_paid' => $loan->principal_paid,
                'interest_paid' => $loan->interest_paid,
                'penalties_paid' => $loan->penalties_paid,
                'fees_paid' => $loan->fees_paid,
                'installments_paid' => $loan->installments_paid,
                'installments_remaining' => $loan->installments_remaining,
                'repayment_progress' => $loan->repayment_progress,
            ],
            'arrears' => [
                'days_past_due' => $loan->days_past_due,
                'arrears_amount' => $loan->arrears_amount,
                'aging_bucket' => $loan->aging_bucket,
                'aging_bucket_color' => $loan->aging_bucket_color,
                'is_overdue' => $loan->isOverdue(),
                'is_npl' => $loan->isNPL(),
            ],
            'next_payment' => [
                'due_date' => $loan->next_payment_due_date,
                'amount' => $loan->next_payment_amount,
            ],
            'risk' => [
                'risk_classification' => $loan->risk_classification,
                'risk_color' => $loan->risk_color,
                'provision_amount' => $loan->provision_amount,
                'provision_rate' => $loan->provision_rate,
            ],
        ];
    }
}

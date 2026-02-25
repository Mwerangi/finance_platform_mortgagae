<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Repayment;
use App\Models\RepaymentImportBatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RepaymentService
{
    protected LoanService $loanService;

    public function __construct(LoanService $loanService)
    {
        $this->loanService = $loanService;
    }

    /**
     * Allocate payment to a loan
     * 
     * This is the core payment allocation algorithm that:
     * 1. Finds all unpaid/partially-paid schedules (FIFO - oldest first)
     * 2. Allocates payment following waterfall: penalties → fees → interest → principal
     * 3. Handles partial payments, overpayments, and advance payments
     * 4. Updates loan balances and schedule records
     * 5. Recalculates DPD and aging bucket
     */
    public function allocatePayment(
        Loan $loan, 
        float $amount, 
        Carbon $paymentDate, 
        array $paymentDetails = []
    ): Repayment {
        return DB::transaction(function () use ($loan, $amount, $paymentDate, $paymentDetails) {
            // Capture loan state before payment
            $outstandingBefore = $loan->total_outstanding;
            $dpd = $loan->days_past_due;

            // Get all unpaid schedules ordered by due date (FIFO)
            $schedules = $loan->schedules()
                ->unpaid()
                ->orderBy('due_date', 'asc')
                ->get();

            // Initialize allocation tracking
            $remainingAmount = $amount;
            $totalAllocation = [
                'principal' => 0,
                'interest' => 0,
                'penalties' => 0,
                'fees' => 0,
            ];

            $schedulesUpdated = [];
            $firstSchedule = null;

            // Allocate payment across schedules
            foreach ($schedules as $schedule) {
                if ($remainingAmount <= 0) {
                    break;
                }

                if (!$firstSchedule) {
                    $firstSchedule = $schedule;
                }

                // Calculate what's due on this schedule
                $penaltiesDue = $schedule->penalties_due - $schedule->penalties_paid;
                $feesDue = $schedule->fees_due - $schedule->fees_paid;
                $interestDue = $schedule->interest_due - $schedule->interest_paid;
                $principalDue = $schedule->principal_due - $schedule->principal_paid;

                // Allocate following waterfall: penalties → fees → interest → principal
                $allocation = [
                    'penalties' => 0,
                    'fees' => 0,
                    'interest' => 0,
                    'principal' => 0,
                ];

                // Allocate to penalties first
                if ($remainingAmount > 0 && $penaltiesDue > 0) {
                    $allocation['penalties'] = min($remainingAmount, $penaltiesDue);
                    $remainingAmount -= $allocation['penalties'];
                }

                // Then to fees
                if ($remainingAmount > 0 && $feesDue > 0) {
                    $allocation['fees'] = min($remainingAmount, $feesDue);
                    $remainingAmount -= $allocation['fees'];
                }

                // Then to interest
                if ($remainingAmount > 0 && $interestDue > 0) {
                    $allocation['interest'] = min($remainingAmount, $interestDue);
                    $remainingAmount -= $allocation['interest'];
                }

                // Finally to principal
                if ($remainingAmount > 0 && $principalDue > 0) {
                    $allocation['principal'] = min($remainingAmount, $principalDue);
                    $remainingAmount -= $allocation['principal'];
                }

                // Record payment on this schedule
                if (array_sum($allocation) > 0) {
                    $schedule->recordPayment(array_sum($allocation), $allocation);
                    $schedulesUpdated[] = $schedule;

                    // Accumulate total allocation
                    foreach ($allocation as $key => $value) {
                        $totalAllocation[$key] += $value;
                    }
                }
            }

            // Update loan balances
            $this->updateLoanBalances($loan, $totalAllocation);

            // Determine payment flags
            $isPartialPayment = $schedulesUpdated && !$this->isFullyPaid($schedulesUpdated[0]);
            $isOverpayment = $remainingAmount > 0;
            $isAdvancePayment = $this->isAdvancePayment($schedules, $paymentDate);

            // Create repayment record
            $repayment = Repayment::create([
                'institution_id' => $loan->institution_id,
                'loan_id' => $loan->id,
                'customer_id' => $loan->customer_id,
                'loan_schedule_id' => $firstSchedule?->id,
                'transaction_reference' => $paymentDetails['transaction_reference'] ?? null,
                'payment_date' => $paymentDate,
                'amount' => $amount,
                'payment_method' => $paymentDetails['payment_method'] ?? null,
                'payment_channel' => $paymentDetails['payment_channel'] ?? null,
                'principal_amount' => $totalAllocation['principal'],
                'interest_amount' => $totalAllocation['interest'],
                'penalties_amount' => $totalAllocation['penalties'],
                'fees_amount' => $totalAllocation['fees'],
                'unallocated_amount' => $remainingAmount,
                'status' => 'allocated',
                'is_partial_payment' => $isPartialPayment,
                'is_advance_payment' => $isAdvancePayment,
                'is_overpayment' => $isOverpayment,
                'installment_number' => $firstSchedule?->installment_number,
                'days_past_due_at_payment' => $dpd,
                'outstanding_before_payment' => $outstandingBefore,
                'outstanding_after_payment' => $loan->fresh()->total_outstanding,
                'recorded_by' => $paymentDetails['recorded_by'] ?? auth()->id(),
                'notes' => $paymentDetails['notes'] ?? null,
                'metadata' => $paymentDetails['metadata'] ?? null,
            ]);

            // Recalculate aging and DPD
            $this->loanService->updateAgingAndDPD($loan);

            return $repayment;
        });
    }

    /**
     * Update loan balances after payment allocation
     */
    protected function updateLoanBalances(Loan $loan, array $allocation): void
    {
        $loan->increment('total_paid', array_sum($allocation));
        $loan->increment('principal_paid', $allocation['principal']);
        $loan->increment('interest_paid', $allocation['interest']);
        $loan->increment('penalties_paid', $allocation['penalties']);
        $loan->increment('fees_paid', $allocation['fees']);

        $loan->decrement('principal_outstanding', $allocation['principal']);
        $loan->decrement('interest_outstanding', $allocation['interest']);
        $loan->decrement('penalties_outstanding', $allocation['penalties']);
        $loan->decrement('fees_outstanding', $allocation['fees']);
        $loan->decrement('total_outstanding', array_sum($allocation));

        // Update last payment details
        $loan->update([
            'last_payment_date' => now(),
            'last_payment_amount' => array_sum($allocation),
        ]);

        // Count paid installments
        $paidCount = $loan->schedules()->fullyPaid()->count();
        $remainingCount = $loan->schedules()->unpaid()->count();
        
        $loan->update([
            'installments_paid' => $paidCount,
            'installments_remaining' => $remainingCount,
        ]);

        // Check if fully paid
        if ($loan->fresh()->total_outstanding <= 0) {
            $loan->markAsFullyPaid();
        }
    }

    /**
     * Check if a schedule is fully paid
     */
    protected function isFullyPaid(LoanSchedule $schedule): bool
    {
        return $schedule->fresh()->balance_remaining <= 0;
    }

    /**
     * Check if this is an advance payment (paid before due date)
     */
    protected function isAdvancePayment($schedules, Carbon $paymentDate): bool
    {
        if ($schedules->isEmpty()) {
            return false;
        }

        $firstUnpaid = $schedules->first();
        return $paymentDate->lt($firstUnpaid->due_date);
    }

    /**
     * Match transaction to loan by account number or reference
     */
    public function matchTransaction(
        int $institutionId,
        string $loanAccountNumber,
        float $amount,
        Carbon $date,
        ?string $reference = null
    ): ?Loan {
        // Try to find loan by account number
        $loan = Loan::forInstitution($institutionId)
            ->where('loan_account_number', $loanAccountNumber)
            ->active()
            ->first();

        if ($loan) {
            return $loan;
        }

        // Try to find by external reference
        if ($reference) {
            $loan = Loan::forInstitution($institutionId)
                ->where('external_reference_number', $reference)
                ->active()
                ->first();
        }

        return $loan;
    }

    /**
     * Record unmatched transaction for manual review
     */
    public function recordUnmatchedTransaction(
        int $institutionId,
        array $transactionData,
        int $importBatchId
    ): Repayment {
        return Repayment::create([
            'institution_id' => $institutionId,
            'import_batch_id' => $importBatchId,
            'transaction_reference' => $transactionData['reference'] ?? null,
            'payment_date' => $transactionData['date'],
            'amount' => $transactionData['amount'],
            'payment_method' => $transactionData['method'] ?? null,
            'payment_channel' => $transactionData['channel'] ?? null,
            'status' => 'pending',
            'notes' => 'Unmatched transaction - requires manual review',
            'metadata' => [
                'original_data' => $transactionData,
                'match_attempted_at' => now()->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Reverse a payment
     */
    public function reversePayment(Repayment $repayment, int $userId, string $reason): Repayment
    {
        return DB::transaction(function () use ($repayment, $userId, $reason) {
            if ($repayment->is_reversed) {
                throw new \Exception('Payment has already been reversed');
            }

            $loan = $repayment->loan;

            // Reverse the allocation on the loan
            $this->reverseLoanBalances($loan, [
                'principal' => $repayment->principal_amount,
                'interest' => $repayment->interest_amount,
                'penalties' => $repayment->penalties_amount,
                'fees' => $repayment->fees_amount,
            ]);

            // Reverse the schedule payments
            if ($repayment->loan_schedule_id) {
                $this->reverseSchedulePayment($repayment);
            }

            // Create offsetting entry
            $offsetting = $repayment->reverse($userId, $reason);

            // Recalculate aging and DPD
            $this->loanService->updateAgingAndDPD($loan);

            return $offsetting;
        });
    }

    /**
     * Reverse loan balances
     */
    protected function reverseLoanBalances(Loan $loan, array $allocation): void
    {
        $loan->decrement('total_paid', array_sum($allocation));
        $loan->decrement('principal_paid', $allocation['principal']);
        $loan->decrement('interest_paid', $allocation['interest']);
        $loan->decrement('penalties_paid', $allocation['penalties']);
        $loan->decrement('fees_paid', $allocation['fees']);

        $loan->increment('principal_outstanding', $allocation['principal']);
        $loan->increment('interest_outstanding', $allocation['interest']);
        $loan->increment('penalties_outstanding', $allocation['penalties']);
        $loan->increment('fees_outstanding', $allocation['fees']);
        $loan->increment('total_outstanding', array_sum($allocation));

        // Recount installments
        $paidCount = $loan->schedules()->fullyPaid()->count();
        $remainingCount = $loan->schedules()->unpaid()->count();
        
        $loan->update([
            'installments_paid' => $paidCount,
            'installments_remaining' => $remainingCount,
        ]);
    }

    /**
     * Reverse schedule payment
     */
    protected function reverseSchedulePayment(Repayment $repayment): void
    {
        $schedule = $repayment->loanSchedule;
        
        if (!$schedule) {
            return;
        }

        // Reverse the amounts
        $schedule->decrement('principal_paid', $repayment->principal_amount);
        $schedule->decrement('interest_paid', $repayment->interest_amount);
        $schedule->decrement('penalties_paid', $repayment->penalties_amount);
        $schedule->decrement('fees_paid', $repayment->fees_amount);
        $schedule->decrement('total_paid', $repayment->amount);

        // Recalculate balance remaining
        $schedule->balance_remaining = $schedule->total_due - $schedule->total_paid;
        $schedule->save();

        // Update status
        if ($schedule->total_paid <= 0) {
            $schedule->update(['status' => 'pending']);
        } elseif ($schedule->balance_remaining > 0) {
            $schedule->update(['status' => 'partially_paid']);
        }
    }

    /**
     * Get repayment history for a loan
     */
    public function getRepaymentHistory(Loan $loan, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = $loan->repayments()->notReversed();

        if (isset($filters['date_from'])) {
            $query->where('payment_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('payment_date', '<=', $filters['date_to']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->orderBy('payment_date', 'desc')->get();
    }

    /**
     * Get repayment summary for a loan
     */
    public function getRepaymentSummary(Loan $loan): array
    {
        $repayments = $loan->repayments()->notReversed()->get();

        return [
            'total_payments' => $repayments->count(),
            'total_amount' => $repayments->sum('amount'),
            'principal_paid' => $repayments->sum('principal_amount'),
            'interest_paid' => $repayments->sum('interest_amount'),
            'penalties_paid' => $repayments->sum('penalties_amount'),
            'fees_paid' => $repayments->sum('fees_amount'),
            'last_payment' => [
                'date' => $loan->last_payment_date?->format('Y-m-d'),
                'amount' => (float) $loan->last_payment_amount,
            ],
            'average_payment_amount' => $repayments->avg('amount'),
            'payment_methods' => $repayments->groupBy('payment_method')
                ->map(fn($group) => [
                    'count' => $group->count(),
                    'total_amount' => $group->sum('amount'),
                ])
                ->toArray(),
        ];
    }

    /**
     * Calculate expected vs actual collections for a period
     */
    public function calculateCollectionRate(
        int $institutionId,
        Carbon $startDate,
        Carbon $endDate
    ): array {
        // Expected: sum of schedule amounts due in period
        $expected = LoanSchedule::forInstitution($institutionId)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->sum('total_due');

        // Actual: sum of payments received in period (excluding reversed)
        $actual = Repayment::forInstitution($institutionId)
            ->notReversed()
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->sum('amount');

        $rate = $expected > 0 ? round(($actual / $expected) * 100, 2) : 0;

        return [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'expected_collections' => (float) $expected,
            'actual_collections' => (float) $actual,
            'collection_rate' => $rate,
            'shortfall' => (float) max(0, $expected - $actual),
            'excess' => (float) max(0, $actual - $expected),
        ];
    }
}

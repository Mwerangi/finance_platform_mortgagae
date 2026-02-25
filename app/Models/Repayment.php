<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'loan_id',
        'customer_id',
        'loan_schedule_id',
        'import_batch_id',
        'transaction_reference',
        'receipt_number',
        'payment_date',
        'amount',
        'payment_method',
        'payment_channel',
        'principal_amount',
        'interest_amount',
        'penalties_amount',
        'fees_amount',
        'unallocated_amount',
        'status',
        'is_partial_payment',
        'is_advance_payment',
        'is_overpayment',
        'installment_number',
        'days_past_due_at_payment',
        'outstanding_before_payment',
        'outstanding_after_payment',
        'is_reversed',
        'reversed_at',
        'reversed_by',
        'reversal_reason',
        'recorded_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'penalties_amount' => 'decimal:2',
        'fees_amount' => 'decimal:2',
        'unallocated_amount' => 'decimal:2',
        'outstanding_before_payment' => 'decimal:2',
        'outstanding_after_payment' => 'decimal:2',
        'is_partial_payment' => 'boolean',
        'is_advance_payment' => 'boolean',
        'is_overpayment' => 'boolean',
        'is_reversed' => 'boolean',
        'days_past_due_at_payment' => 'integer',
        'installment_number' => 'integer',
        'metadata' => 'array',
        'reversed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($repayment) {
            if (empty($repayment->receipt_number)) {
                $repayment->receipt_number = static::generateReceiptNumber($repayment->institution_id);
            }
        });
    }

    /**
     * Generate a unique receipt number for the institution
     */
    protected static function generateReceiptNumber(int $institutionId): string
    {
        $count = static::where('institution_id', $institutionId)->count();
        return 'RCP-' . str_pad((string)($count + 1), 6, '0', STR_PAD_LEFT);
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function loanSchedule(): BelongsTo
    {
        return $this->belongsTo(LoanSchedule::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(RepaymentImportBatch::class, 'import_batch_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeForLoan($query, int $loanId)
    {
        return $query->where('loan_id', $loanId);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeForBatch($query, int $batchId)
    {
        return $query->where('import_batch_id', $batchId);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAllocated($query)
    {
        return $query->where('status', 'allocated');
    }

    public function scopeReversed($query)
    {
        return $query->where('is_reversed', true);
    }

    public function scopeNotReversed($query)
    {
        return $query->where('is_reversed', false);
    }

    public function scopeDisputed($query)
    {
        return $query->where('status', 'disputed');
    }

    public function scopeByPaymentDate($query, string $direction = 'desc')
    {
        return $query->orderBy('payment_date', $direction);
    }

    public function scopePaidBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    public function scopePaidToday($query)
    {
        return $query->whereDate('payment_date', today());
    }

    public function scopePaidThisMonth($query)
    {
        return $query->whereYear('payment_date', now()->year)
                    ->whereMonth('payment_date', now()->month);
    }

    public function scopeByPaymentMethod($query, string $method)
    {
        return $query->where('payment_method', $method);
    }

    public function scopePartialPayments($query)
    {
        return $query->where('is_partial_payment', true);
    }

    public function scopeAdvancePayments($query)
    {
        return $query->where('is_advance_payment', true);
    }

    public function scopeOverpayments($query)
    {
        return $query->where('is_overpayment', true);
    }

    public function scopeWithUnallocatedAmount($query)
    {
        return $query->where('unallocated_amount', '>', 0);
    }

    // ==========================================
    // STATUS CHECKS
    // ==========================================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAllocated(): bool
    {
        return $this->status === 'allocated';
    }

    public function isReversed(): bool
    {
        return $this->is_reversed === true;
    }

    public function isDisputed(): bool
    {
        return $this->status === 'disputed';
    }

    public function isFullyAllocated(): bool
    {
        return $this->unallocated_amount == 0;
    }

    public function hasUnallocatedAmount(): bool
    {
        return $this->unallocated_amount > 0;
    }

    // ==========================================
    // ALLOCATION METHODS
    // ==========================================

    /**
     * Mark payment as allocated with breakdown
     */
    public function markAsAllocated(array $allocation): bool
    {
        $updates = [
            'status' => 'allocated',
            'principal_amount' => $allocation['principal'] ?? 0,
            'interest_amount' => $allocation['interest'] ?? 0,
            'penalties_amount' => $allocation['penalties'] ?? 0,
            'fees_amount' => $allocation['fees'] ?? 0,
        ];

        // Calculate unallocated amount
        $totalAllocated = $updates['principal_amount'] + $updates['interest_amount'] + 
                         $updates['penalties_amount'] + $updates['fees_amount'];
        $updates['unallocated_amount'] = $this->amount - $totalAllocated;

        // Set flags
        if ($updates['unallocated_amount'] > 0) {
            $updates['is_overpayment'] = true;
        }

        return $this->update($updates);
    }

    /**
     * Update loan state captured at payment time
     */
    public function captureLoanState(Loan $loan, ?LoanSchedule $schedule = null): bool
    {
        return $this->update([
            'days_past_due_at_payment' => $loan->days_past_due,
            'outstanding_before_payment' => $loan->total_outstanding,
            'installment_number' => $schedule?->installment_number,
        ]);
    }

    /**
     * Update outstanding after payment
     */
    public function updateOutstandingAfter(float $outstandingAfter): bool
    {
        return $this->update([
            'outstanding_after_payment' => $outstandingAfter,
        ]);
    }

    // ==========================================
    // REVERSAL METHODS
    // ==========================================

    /**
     * Reverse this payment
     */
    public function reverse(int $reversedByUserId, string $reason): ?Repayment
    {
        if ($this->is_reversed) {
            return null;
        }

        // Mark this payment as reversed
        $this->update([
            'is_reversed' => true,
            'reversed_at' => now(),
            'reversed_by' => $reversedByUserId,
            'reversal_reason' => $reason,
        ]);

        // Create offsetting repayment entry with negative amounts
        $offsetting = static::create([
            'institution_id' => $this->institution_id,
            'loan_id' => $this->loan_id,
            'customer_id' => $this->customer_id,
            'loan_schedule_id' => $this->loan_schedule_id,
            'transaction_reference' => $this->transaction_reference . '-REVERSAL',
            'payment_date' => now()->toDateString(),
            'amount' => -$this->amount,
            'payment_method' => $this->payment_method,
            'payment_channel' => $this->payment_channel,
            'principal_amount' => -$this->principal_amount,
            'interest_amount' => -$this->interest_amount,
            'penalties_amount' => -$this->penalties_amount,
            'fees_amount' => -$this->fees_amount,
            'unallocated_amount' => -$this->unallocated_amount,
            'status' => 'allocated',
            'days_past_due_at_payment' => $this->loan?->days_past_due,
            'outstanding_before_payment' => $this->loan?->total_outstanding,
            'recorded_by' => $reversedByUserId,
            'notes' => "Reversal of payment {$this->receipt_number}. Reason: {$reason}",
            'metadata' => [
                'original_payment_id' => $this->id,
                'reversal_reason' => $reason,
            ],
        ]);

        return $offsetting;
    }

    // ==========================================
    // COMPUTED ATTRIBUTES
    // ==========================================

    public function getTotalAllocatedAttribute(): float
    {
        return $this->principal_amount + $this->interest_amount + 
               $this->penalties_amount + $this->fees_amount;
    }

    public function getAllocationBreakdownAttribute(): array
    {
        return [
            'principal' => (float) $this->principal_amount,
            'interest' => (float) $this->interest_amount,
            'penalties' => (float) $this->penalties_amount,
            'fees' => (float) $this->fees_amount,
            'unallocated' => (float) $this->unallocated_amount,
            'total' => (float) $this->amount,
        ];
    }

    public function getAllocationPercentagesAttribute(): array
    {
        if ($this->amount == 0) {
            return [
                'principal' => 0,
                'interest' => 0,
                'penalties' => 0,
                'fees' => 0,
                'unallocated' => 0,
            ];
        }

        return [
            'principal' => round(($this->principal_amount / $this->amount) * 100, 2),
            'interest' => round(($this->interest_amount / $this->amount) * 100, 2),
            'penalties' => round(($this->penalties_amount / $this->amount) * 100, 2),
            'fees' => round(($this->fees_amount / $this->amount) * 100, 2),
            'unallocated' => round(($this->unallocated_amount / $this->amount) * 100, 2),
        ];
    }

    public function getStatusColorAttribute(): string
    {
        if ($this->is_reversed) {
            return 'red';
        }

        return match($this->status) {
            'pending' => 'yellow',
            'allocated' => 'green',
            'disputed' => 'orange',
            default => 'gray',
        };
    }

    public function getPaymentMethodDisplayAttribute(): string
    {
        return match($this->payment_method) {
            'bank_transfer' => 'Bank Transfer',
            'cheque' => 'Cheque',
            'cash' => 'Cash',
            'mobile_money' => 'Mobile Money',
            'direct_debit' => 'Direct Debit',
            'standing_order' => 'Standing Order',
            'other' => 'Other',
            default => 'Unknown',
        };
    }

    public function getDaysToAllocationAttribute(): ?int
    {
        if (!$this->isAllocated()) {
            return null;
        }

        return $this->payment_date->diffInDays($this->created_at);
    }

    public function getBalanceImpactAttribute(): float
    {
        return $this->outstanding_before_payment - $this->outstanding_after_payment;
    }
}

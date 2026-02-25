<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanSchedule extends Model
{
    protected $fillable = [
        'loan_id',
        'institution_id',
        'installment_number',
        'due_date',
        'status',
        'principal_due',
        'interest_due',
        'total_due',
        'penalties_due',
        'fees_due',
        'opening_balance',
        'closing_balance',
        'principal_paid',
        'interest_paid',
        'penalties_paid',
        'fees_paid',
        'total_paid',
        'balance_remaining',
        'paid_date',
        'last_payment_date',
        'days_past_due',
        'overdue_since',
        'payment_history',
        'notes',
    ];

    protected $casts = [
        'due_date' => 'date',
        'principal_due' => 'decimal:2',
        'interest_due' => 'decimal:2',
        'total_due' => 'decimal:2',
        'penalties_due' => 'decimal:2',
        'fees_due' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'principal_paid' => 'decimal:2',
        'interest_paid' => 'decimal:2',
        'penalties_paid' => 'decimal:2',
        'fees_paid' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_remaining' => 'decimal:2',
        'paid_date' => 'date',
        'last_payment_date' => 'date',
        'overdue_since' => 'date',
        'payment_history' => 'array',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    // ========================================
    // QUERY SCOPES
    // ========================================

    public function scopeForLoan($query, int $loanId)
    {
        return $query->where('loan_id', $loanId);
    }

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePartiallyPaid($query)
    {
        return $query->where('status', 'partially_paid');
    }

    public function scopeFullyPaid($query)
    {
        return $query->where('status', 'fully_paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeUnpaid($query)
    {
        return $query->whereIn('status', ['pending', 'partially_paid', 'overdue']);
    }

    public function scopeDueBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('due_date', [$startDate, $endDate]);
    }

    public function scopeDueBefore($query, $date)
    {
        return $query->where('due_date', '<', $date);
    }

    public function scopeDueAfter($query, $date)
    {
        return $query->where('due_date', '>', $date);
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeDueThisMonth($query)
    {
        return $query->whereBetween('due_date', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    // ========================================
    // STATUS CHECK METHODS
    // ========================================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPartiallyPaid(): bool
    {
        return $this->status === 'partially_paid';
    }

    public function isFullyPaid(): bool
    {
        return $this->status === 'fully_paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    public function isWaived(): bool
    {
        return $this->status === 'waived';
    }

    // ========================================
    // PAYMENT METHODS
    // ========================================

    /**
     * Record a payment against this installment
     */
    public function recordPayment(float $amount, array $allocation): void
    {
        $this->principal_paid += $allocation['principal'] ?? 0;
        $this->interest_paid += $allocation['interest'] ?? 0;
        $this->penalties_paid += $allocation['penalties'] ?? 0;
        $this->fees_paid += $allocation['fees'] ?? 0;
        $this->total_paid += $amount;
        
        $this->balance_remaining = $this->getTotalDueAttribute() - $this->total_paid;
        
        // Update status
        if ($this->balance_remaining <= 0) {
            $this->status = 'fully_paid';
            $this->paid_date = now();
        } else {
            $this->status = 'partially_paid';
        }
        
        $this->last_payment_date = now();
        
        // Add to payment history
        $history = $this->payment_history ?? [];
        $history[] = [
            'amount' => $amount,
            'allocation' => $allocation,
            'paid_at' => now()->toISOString(),
        ];
        $this->payment_history = $history;
        
        $this->save();
    }

    /**
     * Mark as overdue
     */
    public function markAsOverdue(): void
    {
        if ($this->status !== 'overdue') {
            $this->update([
                'status' => 'overdue',
                'overdue_since' => $this->overdue_since ?? now(),
            ]);
        }
        
        // Update days past due
        $this->updateDaysPastDue();
    }

    /**
     * Update days past due
     */
    public function updateDaysPastDue(): void
    {
        if ($this->due_date && now()->greaterThan($this->due_date) && !$this->isFullyPaid()) {
            $this->days_past_due = now()->diffInDays($this->due_date);
            $this->save();
        } else {
            $this->days_past_due = 0;
            $this->save();
        }
    }

    /**
     * Waive this installment
     */
    public function waive(string $reason): void
    {
        $this->update([
            'status' => 'waived',
            'notes' => $reason,
        ]);
    }

    // ========================================
    // COMPUTED ATTRIBUTES
    // ========================================

    /**
     * Get total amount due (including penalties and fees)
     */
    public function getTotalDueAttribute(): float
    {
        return $this->principal_due + $this->interest_due + $this->penalties_due + $this->fees_due;
    }

    /**
     * Get payment progress percentage
     */
    public function getPaymentProgressAttribute(): float
    {
        $totalDue = $this->getTotalDueAttribute();
        if ($totalDue == 0) {
            return 100;
        }
        return ($this->total_paid / $totalDue) * 100;
    }

    /**
     * Check if due date is in the past
     */
    public function getIsPastDueAttribute(): bool
    {
        return $this->due_date && now()->greaterThan($this->due_date) && !$this->isFullyPaid();
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'partially_paid' => 'yellow',
            'fully_paid' => 'green',
            'overdue' => 'red',
            'waived' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get days until due
     */
    public function getDaysUntilDueAttribute(): ?int
    {
        if (!$this->due_date) {
            return null;
        }
        return now()->diffInDays($this->due_date, false);
    }
}

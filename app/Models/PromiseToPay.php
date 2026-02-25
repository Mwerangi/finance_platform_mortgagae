<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromiseToPay extends Model
{
    use HasFactory;

    protected $table = 'promise_to_pay';

    protected $fillable = [
        'institution_id',
        'loan_id',
        'customer_id',
        'collections_action_id',
        'created_by',
        'promise_date',
        'commitment_date',
        'promised_amount',
        'principal_amount',
        'interest_amount',
        'penalty_amount',
        'fees_amount',
        'status',
        'amount_paid',
        'actual_payment_date',
        'payment_id',
        'follow_up_date',
        'days_overdue',
        'reminder_sent',
        'reschedule_count',
        'original_commitment_date',
        'notes',
        'customer_reason',
        'additional_data',
    ];

    protected $casts = [
        'promise_date' => 'date',
        'commitment_date' => 'date',
        'promised_amount' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'fees_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'actual_payment_date' => 'date',
        'follow_up_date' => 'date',
        'days_overdue' => 'integer',
        'reminder_sent' => 'boolean',
        'reschedule_count' => 'integer',
        'original_commitment_date' => 'date',
        'additional_data' => 'array',
    ];

    /**
     * Get the institution that owns the promise to pay.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the loan associated with this promise.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the customer associated with this promise.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the collections action that created this promise.
     */
    public function collectionsAction(): BelongsTo
    {
        return $this->belongsTo(CollectionsAction::class, 'collections_action_id');
    }

    /**
     * Get the user who created this promise.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the payment associated with this promise.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Repayment::class, 'payment_id');
    }

    /**
     * Scope a query to only include promises with a specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include open promises.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include broken promises.
     */
    public function scopeBroken($query)
    {
        return $query->where('status', 'broken');
    }

    /**
     * Scope a query to only include kept promises.
     */
    public function scopeKept($query)
    {
        return $query->whereIn('status', ['kept', 'partially_kept']);
    }

    /**
     * Scope a query to only include due promises.
     */
    public function scopeDue($query)
    {
        return $query->where('commitment_date', '<=', now())
            ->where('status', 'open');
    }

    /**
     * Scope a query to only include overdue promises.
     */
    public function scopeOverdue($query)
    {
        return $query->where('commitment_date', '<', now())
            ->where('status', 'open');
    }

    /**
     * Scope a query to only include upcoming promises (within next 7 days).
     */
    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('commitment_date', '>=', now())
            ->where('commitment_date', '<=', now()->addDays($days))
            ->where('status', 'open');
    }

    /**
     * Update the days overdue based on commitment date.
     */
    public function updateDaysOverdue(): void
    {
        if ($this->status === 'open' && $this->commitment_date->isPast()) {
            $this->days_overdue = now()->diffInDays($this->commitment_date);
        } else {
            $this->days_overdue = 0;
        }
    }

    /**
     * Check if the promise is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'open' && $this->commitment_date->isPast();
    }

    /**
     * Check if the promise is due today.
     */
    public function isDueToday(): bool
    {
        return $this->status === 'open' && $this->commitment_date->isToday();
    }

    /**
     * Check if the promise is upcoming (within next 7 days).
     */
    public function isUpcoming($days = 7): bool
    {
        return $this->status === 'open' 
            && $this->commitment_date->isFuture()
            && $this->commitment_date->diffInDays(now()) <= $days;
    }

    /**
     * Mark the promise as kept.
     */
    public function markAsKept(float $amountPaid, $paymentId = null): void
    {
        $this->amount_paid = $amountPaid;
        $this->actual_payment_date = now();
        $this->payment_id = $paymentId;

        if ($amountPaid >= $this->promised_amount) {
            $this->status = 'kept';
        } else {
            $this->status = 'partially_kept';
        }

        $this->save();
    }

    /**
     * Mark the promise as broken.
     */
    public function markAsBroken(): void
    {
        $this->status = 'broken';
        $this->updateDaysOverdue();
        $this->save();
    }

    /**
     * Reschedule the promise to a new date.
     */
    public function reschedule($newCommitmentDate, $reason = null): void
    {
        if ($this->reschedule_count === 0) {
            $this->original_commitment_date = $this->commitment_date;
        }

        $this->commitment_date = $newCommitmentDate;
        $this->reschedule_count++;
        $this->status = 'rescheduled';
        $this->days_overdue = 0;

        if ($reason) {
            $this->customer_reason = $reason;
        }

        $this->save();

        // After rescheduling, status goes back to 'open'
        $this->status = 'open';
        $this->save();
    }

    /**
     * Cancel the promise.
     */
    public function cancel($reason = null): void
    {
        $this->status = 'cancelled';

        if ($reason) {
            $this->notes = $reason;
        }

        $this->save();
    }

    /**
     * Get the fulfillment percentage.
     */
    public function getFulfillmentPercentageAttribute(): float
    {
        if ($this->promised_amount == 0) {
            return 0;
        }

        return round(($this->amount_paid / $this->promised_amount) * 100, 2);
    }

    /**
     * Get the outstanding amount.
     */
    public function getOutstandingAmountAttribute(): float
    {
        return max(0, $this->promised_amount - $this->amount_paid);
    }
}

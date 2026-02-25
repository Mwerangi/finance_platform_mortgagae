<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionsAction extends Model
{
    use HasFactory;

    protected $table = 'collections_actions';

    protected $fillable = [
        'institution_id',
        'loan_id',
        'customer_id',
        'queue_id',
        'performed_by',
        'action_type',
        'action_date',
        'contact_method',
        'outcome',
        'promise_to_pay_id',
        'notes',
        'customer_response',
        'amount_committed',
        'commitment_date',
        'next_action_date',
        'next_action_type',
        'latitude',
        'longitude',
        'duration_minutes',
        'additional_data',
    ];

    protected $casts = [
        'action_date' => 'datetime',
        'amount_committed' => 'decimal:2',
        'commitment_date' => 'date',
        'next_action_date' => 'date',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'duration_minutes' => 'integer',
        'additional_data' => 'array',
    ];

    /**
     * Get the institution that owns the action.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the loan associated with this action.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the customer associated with this action.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the collections queue item associated with this action.
     */
    public function queue(): BelongsTo
    {
        return $this->belongsTo(CollectionsQueue::class, 'queue_id');
    }

    /**
     * Get the user who performed this action.
     */
    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * Get the promise to pay created from this action.
     */
    public function promiseToPay(): BelongsTo
    {
        return $this->belongsTo(PromiseToPay::class, 'promise_to_pay_id');
    }

    /**
     * Scope a query to only include actions of a specific type.
     */
    public function scopeActionType($query, $type)
    {
        return $query->where('action_type', $type);
    }

    /**
     * Scope a query to only include actions with a specific outcome.
     */
    public function scopeOutcome($query, $outcome)
    {
        return $query->where('outcome', $outcome);
    }

    /**
     * Scope a query to only include actions performed by a specific user.
     */
    public function scopePerformedBy($query, $userId)
    {
        return $query->where('performed_by', $userId);
    }

    /**
     * Scope a query to only include actions within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('action_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include successful actions.
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('outcome', [
            'successful',
            'payment_promised',
            'payment_received',
            'partial_payment',
        ]);
    }

    /**
     * Check if the action was successful.
     */
    public function isSuccessful(): bool
    {
        return in_array($this->outcome, [
            'successful',
            'payment_promised',
            'payment_received',
            'partial_payment',
        ]);
    }

    /**
     * Check if the action resulted in a payment promise.
     */
    public function hasPaymentPromise(): bool
    {
        return $this->outcome === 'payment_promised' || $this->promise_to_pay_id !== null;
    }

    /**
     * Check if the action was a field visit with location data.
     */
    public function hasGeolocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get a human-readable action type label.
     */
    public function getActionTypeLabel(): string
    {
        return match ($this->action_type) {
            'phone_call' => 'Phone Call',
            'sms' => 'SMS',
            'email' => 'Email',
            'field_visit' => 'Field Visit',
            'office_visit' => 'Office Visit',
            'letter' => 'Letter',
            'legal_notice' => 'Legal Notice',
            'other' => 'Other',
            default => $this->action_type,
        };
    }

    /**
     * Get a human-readable outcome label.
     */
    public function getOutcomeLabel(): string
    {
        return match ($this->outcome) {
            'successful' => 'Contact Made',
            'no_answer' => 'No Answer',
            'wrong_number' => 'Wrong Number',
            'call_back_requested' => 'Callback Requested',
            'payment_promised' => 'Payment Promised',
            'payment_received' => 'Payment Received',
            'dispute_raised' => 'Dispute Raised',
            'refused_to_pay' => 'Refused to Pay',
            'partial_payment' => 'Partial Payment',
            'other' => 'Other',
            default => $this->outcome ?? 'Unknown',
        };
    }
}

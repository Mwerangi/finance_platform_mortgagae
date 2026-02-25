<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionsQueue extends Model
{
    use HasFactory;

    protected $table = 'collections_queue';

    protected $fillable = [
        'institution_id',
        'loan_id',
        'customer_id',
        'assigned_to',
        'days_past_due',
        'total_arrears',
        'principal_arrears',
        'interest_arrears',
        'penalty_arrears',
        'fees_arrears',
        'priority_score',
        'priority_level',
        'delinquency_bucket',
        'status',
        'assigned_at',
        'last_action_at',
        'next_action_due',
        'customer_phone',
        'customer_email',
        'customer_address',
        'contact_attempts',
        'successful_contacts',
        'broken_promises',
        'is_legal_case',
        'has_active_ptp',
        'customer_reachable',
        'notes',
        'additional_data',
    ];

    protected $casts = [
        'days_past_due' => 'integer',
        'total_arrears' => 'decimal:2',
        'principal_arrears' => 'decimal:2',
        'interest_arrears' => 'decimal:2',
        'penalty_arrears' => 'decimal:2',
        'fees_arrears' => 'decimal:2',
        'priority_score' => 'integer',
        'assigned_at' => 'datetime',
        'last_action_at' => 'datetime',
        'next_action_due' => 'datetime',
        'contact_attempts' => 'integer',
        'successful_contacts' => 'integer',
        'broken_promises' => 'integer',
        'is_legal_case' => 'boolean',
        'has_active_ptp' => 'boolean',
        'customer_reachable' => 'boolean',
        'additional_data' => 'array',
    ];

    /**
     * Get the institution that owns the collections queue item.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the loan associated with this queue item.
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the customer associated with this queue item.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user (collections officer) assigned to this item.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the collections actions for this queue item.
     */
    public function actions(): HasMany
    {
        return $this->hasMany(CollectionsAction::class, 'queue_id');
    }

    /**
     * Get the latest action for this queue item.
     */
    public function latestAction(): BelongsTo
    {
        return $this->belongsTo(CollectionsAction::class, 'queue_id')
            ->latestOfMany();
    }

    /**
     * Scope a query to only include items with a specific status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include items assigned to a specific user.
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope a query to only include items with a specific priority level.
     */
    public function scopePriorityLevel($query, $level)
    {
        return $query->where('priority_level', $level);
    }

    /**
     * Scope a query to only include items in a specific delinquency bucket.
     */
    public function scopeDelinquencyBucket($query, $bucket)
    {
        return $query->where('delinquency_bucket', $bucket);
    }

    /**
     * Scope a query to order by priority (high to low).
     */
    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority_score', 'desc')
            ->orderBy('days_past_due', 'desc');
    }

    /**
     * Scope a query to only include items due for action.
     */
    public function scopeDueForAction($query)
    {
        return $query->where('next_action_due', '<=', now())
            ->orWhereNull('next_action_due');
    }

    /**
     * Calculate the priority score based on multiple factors.
     */
    public function calculatePriorityScore(): int
    {
        $score = 0;

        // Days past due (0-40 points)
        $score += min($this->days_past_due, 40);

        // Arrears amount (0-30 points, scaled by 10k)
        $score += min(floor($this->total_arrears / 10000) * 5, 30);

        // Delinquency bucket multiplier
        $bucketScores = [
            'current' => 0,
            '1-30' => 10,
            '31-60' => 20,
            '61-90' => 30,
            '91-180' => 40,
            '180+' => 50,
        ];
        $score += $bucketScores[$this->delinquency_bucket] ?? 0;

        // Broken promises penalty
        $score += $this->broken_promises * 5;

        // Contact attempts (negative score for many unsuccessful attempts)
        if ($this->contact_attempts > $this->successful_contacts * 3) {
            $score -= 10;
        }

        return max(0, min($score, 100)); // Cap between 0-100
    }

    /**
     * Update the priority level based on priority score.
     */
    public function updatePriorityLevel(): void
    {
        $score = $this->priority_score;

        if ($score >= 70) {
            $this->priority_level = 'critical';
        } elseif ($score >= 50) {
            $this->priority_level = 'high';
        } elseif ($score >= 30) {
            $this->priority_level = 'medium';
        } else {
            $this->priority_level = 'low';
        }
    }

    /**
     * Determine delinquency bucket based on days past due.
     */
    public function updateDelinquencyBucket(): void
    {
        $dpd = $this->days_past_due;

        if ($dpd <= 0) {
            $this->delinquency_bucket = 'current';
        } elseif ($dpd <= 30) {
            $this->delinquency_bucket = '1-30';
        } elseif ($dpd <= 60) {
            $this->delinquency_bucket = '31-60';
        } elseif ($dpd <= 90) {
            $this->delinquency_bucket = '61-90';
        } elseif ($dpd <= 180) {
            $this->delinquency_bucket = '91-180';
        } else {
            $this->delinquency_bucket = '180+';
        }
    }

    /**
     * Get the contact success rate.
     */
    public function getContactSuccessRateAttribute(): float
    {
        if ($this->contact_attempts === 0) {
            return 0;
        }

        return round(($this->successful_contacts / $this->contact_attempts) * 100, 2);
    }

    /**
     * Check if this item is overdue for action.
     */
    public function isOverdueForAction(): bool
    {
        return $this->next_action_due && $this->next_action_due->isPast();
    }
}

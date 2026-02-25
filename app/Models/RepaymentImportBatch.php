<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepaymentImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'batch_number',
        'original_filename',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'total_amount',
        'matched_amount',
        'unmatched_amount',
        'started_at',
        'completed_at',
        'processing_duration_seconds',
        'errors',
        'notes',
        'uploaded_by',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'total_amount' => 'decimal:2',
        'matched_amount' => 'decimal:2',
        'unmatched_amount' => 'decimal:2',
        'processing_duration_seconds' => 'integer',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (empty($batch->batch_number)) {
                $batch->batch_number = static::generateBatchNumber($batch->institution_id);
            }
        });
    }

    /**
     * Generate a unique batch number for the institution
     */
    protected static function generateBatchNumber(int $institutionId): string
    {
        $count = static::where('institution_id', $institutionId)->count();
        return 'AUTO-' . str_pad((string)($count + 1), 6, '0', STR_PAD_LEFT);
    }

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(Repayment::class, 'import_batch_id');
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePartiallyCompleted($query)
    {
        return $query->where('status', 'partially_completed');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeUploadedBy($query, int $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    public function scopeCreatedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // ==========================================
    // STATUS CHECKS
    // ==========================================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPartiallyCompleted(): bool
    {
        return $this->status === 'partially_completed';
    }

    public function isFinished(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'partially_completed']);
    }

    // ==========================================
    // LIFECYCLE METHODS
    // ==========================================

    /**
     * Start processing the batch
     */
    public function startProcessing(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        return $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark batch as completed
     */
    public function markCompleted(): bool
    {
        if (!$this->isProcessing()) {
            return false;
        }

        $processingDuration = null;
        if ($this->started_at) {
            $processingDuration = now()->diffInSeconds($this->started_at);
        }

        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'processing_duration_seconds' => $processingDuration,
        ]);
    }

    /**
     * Mark batch as failed
     */
    public function markFailed(string $errorMessage = null): bool
    {
        $errors = $this->errors ?? [];
        
        if ($errorMessage) {
            $errors[] = [
                'message' => $errorMessage,
                'timestamp' => now()->toDateTimeString(),
            ];
        }

        $processingDuration = null;
        if ($this->started_at) {
            $processingDuration = now()->diffInSeconds($this->started_at);
        }

        return $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'processing_duration_seconds' => $processingDuration,
            'errors' => $errors,
        ]);
    }

    /**
     * Mark batch as partially completed
     */
    public function markPartiallyCompleted(): bool
    {
        if (!$this->isProcessing()) {
            return false;
        }

        $processingDuration = null;
        if ($this->started_at) {
            $processingDuration = now()->diffInSeconds($this->started_at);
        }

        return $this->update([
            'status' => 'partially_completed',
            'completed_at' => now(),
            'processing_duration_seconds' => $processingDuration,
        ]);
    }

    /**
     * Record an error
     */
    public function recordError(string $rowNumber, string $errorMessage, array $rowData = []): bool
    {
        $errors = $this->errors ?? [];
        
        $errors[] = [
            'row' => $rowNumber,
            'message' => $errorMessage,
            'data' => $rowData,
            'timestamp' => now()->toDateTimeString(),
        ];

        return $this->update([
            'errors' => $errors,
        ]);
    }

    /**
     * Update processing statistics
     */
    public function updateStatistics(array $stats): bool
    {
        return $this->update($stats);
    }

    /**
     * Increment processed rows
     */
    public function incrementProcessed(bool $success = true): bool
    {
        $updates = [
            'processed_rows' => $this->processed_rows + 1,
        ];

        if ($success) {
            $updates['successful_rows'] = $this->successful_rows + 1;
        } else {
            $updates['failed_rows'] = $this->failed_rows + 1;
        }

        return $this->update($updates);
    }

    // ==========================================
    // COMPUTED ATTRIBUTES
    // ==========================================

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->successful_rows / $this->total_rows) * 100, 2);
    }

    public function getFailureRateAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->failed_rows / $this->total_rows) * 100, 2);
    }

    public function getMatchedPercentageAttribute(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        return round(($this->matched_amount / $this->total_amount) * 100, 2);
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'partially_completed' => 'yellow',
            default => 'gray',
        };
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->processing_duration_seconds) {
            return '-';
        }

        $seconds = $this->processing_duration_seconds;
        
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m {$remainingSeconds}s";
    }

    public function getErrorCountAttribute(): int
    {
        return is_array($this->errors) ? count($this->errors) : 0;
    }
}

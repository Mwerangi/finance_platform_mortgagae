<?php

namespace App\Models;

use App\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankStatementImport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'institution_id',
        'application_id',
        'uploaded_by',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'bank_name',
        'account_number',
        'import_status',
        'rows_total',
        'rows_processed',
        'rows_failed',
        'statement_start_date',
        'statement_end_date',
        'statement_months',
        'processing_started_at',
        'processing_completed_at',
        'error_log',
        'processing_notes',
    ];

    protected $casts = [
        'import_status' => ImportStatus::class,
        'rows_total' => 'integer',
        'rows_processed' => 'integer',
        'rows_failed' => 'integer',
        'statement_months' => 'integer',
        'file_size' => 'integer',
        'statement_start_date' => 'date',
        'statement_end_date' => 'date',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'error_log' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the customer that owns the import.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the institution that owns the import.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the application associated with the import.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * Get the user who uploaded the file.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the transactions for this import.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    /**
     * Get the analytics for this import.
     */
    public function analytics(): HasOne
    {
        return $this->hasOne(StatementAnalytics::class);
    }

    /**
     * Check if import is completed.
     */
    public function isCompleted(): bool
    {
        return $this->import_status === ImportStatus::COMPLETED;
    }

    /**
     * Check if import failed.
     */
    public function isFailed(): bool
    {
        return $this->import_status === ImportStatus::FAILED;
    }

    /**
     * Check if import is processing.
     */
    public function isProcessing(): bool
    {
        return $this->import_status === ImportStatus::PROCESSING;
    }

    /**
     * Mark import as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'import_status' => ImportStatus::PROCESSING,
            'processing_started_at' => now(),
        ]);
    }

    /**
     * Mark import as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'import_status' => ImportStatus::COMPLETED,
            'processing_completed_at' => now(),
        ]);
    }

    /**
     * Mark import as failed.
     */
    public function markAsFailed(array $errors = []): void
    {
        $this->update([
            'import_status' => ImportStatus::FAILED,
            'processing_completed_at' => now(),
            'error_log' => $errors,
        ]);
    }

    /**
     * Get file size in human-readable format.
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->rows_total == 0) {
            return 0;
        }
        
        return (int) (($this->rows_processed / $this->rows_total) * 100);
    }

    /**
     * Scope to filter by customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to filter by institution.
     */
    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, ImportStatus $status)
    {
        return $query->where('import_status', $status);
    }

    /**
     * Scope to get completed imports.
     */
    public function scopeCompleted($query)
    {
        return $query->where('import_status', ImportStatus::COMPLETED);
    }
}

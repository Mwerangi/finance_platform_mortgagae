<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankTransaction extends Model
{
    protected $fillable = [
        'bank_statement_import_id',
        'customer_id',
        'institution_id',
        'transaction_date',
        'description',
        'transaction_hash',
        'debit',
        'credit',
        'balance',
        'transaction_type',
        'category',
        'is_income',
        'is_expense',
        'is_debt_payment',
        'is_recurring',
        'risk_flags',
        'is_flagged',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'transaction_type' => TransactionType::class,
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
        'is_income' => 'boolean',
        'is_expense' => 'boolean',
        'is_debt_payment' => 'boolean',
        'is_recurring' => 'boolean',
        'is_flagged' => 'boolean',
        'risk_flags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Generate transaction hash for deduplication
            if (empty($transaction->transaction_hash)) {
                $transaction->transaction_hash = md5(
                    $transaction->customer_id .
                    $transaction->transaction_date->format('Y-m-d') .
                    $transaction->description .
                    $transaction->debit .
                    $transaction->credit
                );
            }

            // Auto-classify income/expense based on debit/credit
            if ($transaction->credit > 0 && empty($transaction->is_income)) {
                $transaction->is_income = true;
            }
            if ($transaction->debit > 0 && empty($transaction->is_expense)) {
                $transaction->is_expense = true;
            }

            // Set is_flagged if risk_flags exist
            if (!empty($transaction->risk_flags)) {
                $transaction->is_flagged = true;
            }
        });
    }

    /**
     * Get the import that owns the transaction.
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    /**
     * Get the customer that owns the transaction.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the institution that owns the transaction.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get transaction amount (debit or credit).
     */
    public function getAmountAttribute(): float
    {
        return max($this->debit, $this->credit);
    }

    /**
     * Check if transaction has risk flags.
     */
    public function hasRiskFlags(): bool
    {
        return !empty($this->risk_flags);
    }

    /**
     * Add a risk flag.
     */
    public function addRiskFlag(string $flag, ?string $reason = null): void
    {
        $flags = $this->risk_flags ?? [];
        $flags[] = [
            'flag' => $flag,
            'reason' => $reason,
            'flagged_at' => now()->toDateTimeString(),
        ];

        $this->update([
            'risk_flags' => $flags,
            'is_flagged' => true,
        ]);
    }

    /**
     * Scope to filter by customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to filter by import.
     */
    public function scopeForImport($query, int $importId)
    {
        return $query->where('bank_statement_import_id', $importId);
    }

    /**
     * Scope to filter income transactions.
     */
    public function scopeIncome($query)
    {
        return $query->where('is_income', true);
    }

    /**
     * Scope to filter expense transactions.
     */
    public function scopeExpense($query)
    {
        return $query->where('is_expense', true);
    }

    /**
     * Scope to filter debt payments.
     */
    public function scopeDebtPayments($query)
    {
        return $query->where('is_debt_payment', true);
    }

    /**
     * Scope to filter flagged transactions.
     */
    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by transaction type.
     */
    public function scopeOfType($query, TransactionType $type)
    {
        return $query->where('transaction_type', $type);
    }
}

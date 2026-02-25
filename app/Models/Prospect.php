<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prospect extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'institution_id',
        'first_name',
        'middle_name',
        'last_name',
        'phone',
        'email',
        'id_number',
        'customer_type',
        'loan_purpose',
        'requested_amount',
        'requested_tenure',
        'loan_product_id',
        'property_location',
        'property_value',
        'status',
        'bank_statement_import_id',
        'eligibility_assessment_id',
        'converted_to_customer_id',
        'converted_at',
        'notes',
        'source',
        'created_by',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'property_value' => 'decimal:2',
        'requested_tenure' => 'integer',
        'converted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'full_name',
        'can_convert_to_customer',
    ];

    // Relationships
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function loanProduct(): BelongsTo
    {
        return $this->belongsTo(LoanProduct::class);
    }

    public function eligibilityAssessment(): BelongsTo
    {
        return $this->belongsTo(EligibilityAssessment::class);
    }

    public function statementImport(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
    }

    public function convertedToCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_to_customer_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProspectDocument::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function eligibilityAssessments(): HasMany
    {
        return $this->hasMany(EligibilityAssessment::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }

    public function getCanConvertToCustomerAttribute(): bool
    {
        return $this->canConvertToCustomer();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeStatementUploaded($query)
    {
        return $query->where('status', 'statement_uploaded');
    }

    public function scopeEligible($query)
    {
        return $query->where('status', 'eligibility_passed');
    }

    public function scopeIneligible($query)
    {
        return $query->where('status', 'eligibility_failed');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted_to_customer');
    }

    public function scopeNotConverted($query)
    {
        return $query->whereIn('status', ['pending', 'statement_uploaded', 'eligibility_passed', 'eligibility_failed']);
    }

    // Helper Methods
    public function canConvertToCustomer(): bool
    {
        return $this->status === 'eligibility_passed' && is_null($this->converted_to_customer_id);
    }

    public function isEligible(): bool
    {
        return $this->status === 'eligibility_passed';
    }

    public function isIneligible(): bool
    {
        return $this->status === 'eligibility_failed';
    }
}

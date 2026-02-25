<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'document_type_id',
        'customer_type',
        'loan_purpose',
        'stage',
        'is_required',
        'can_skip_with_supervisor_approval',
        'instructions',
        'display_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'can_skip_with_supervisor_approval' => 'boolean',
        'display_order' => 'integer',
    ];

    // Relationships
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    // Scopes
    public function scopeForCustomerType($query, string $customerType)
    {
        return $query->where(function ($q) use ($customerType) {
            $q->where('customer_type', $customerType)
              ->orWhere('customer_type', 'both');
        });
    }

    public function scopeForLoanPurpose($query, string $loanPurpose)
    {
        return $query->where(function ($q) use ($loanPurpose) {
            $q->where('loan_purpose', $loanPurpose)
              ->orWhere('loan_purpose', 'all');
        });
    }

    public function scopeForStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOptional($query)
    {
        return $query->where('is_required', false);
    }

    public function scopeForInstitution($query, ?int $institutionId)
    {
        return $query->where(function ($q) use ($institutionId) {
            $q->whereNull('institution_id')
              ->orWhere('institution_id', $institutionId);
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    // Helper Methods
    public static function getRequiredDocuments(
        string $customerType,
        string $loanPurpose,
        string $stage,
        ?int $institutionId = null
    ): \Illuminate\Database\Eloquent\Collection {
        return self::with('documentType')
            ->forInstitution($institutionId)
            ->forCustomerType($customerType)
            ->forLoanPurpose($loanPurpose)
            ->forStage($stage)
            ->required()
            ->ordered()
            ->get();
    }

    public static function getAllDocuments(
        string $customerType,
        string $loanPurpose,
        string $stage,
        ?int $institutionId = null
    ): \Illuminate\Database\Eloquent\Collection {
        return self::with('documentType')
            ->forInstitution($institutionId)
            ->forCustomerType($customerType)
            ->forLoanPurpose($loanPurpose)
            ->forStage($stage)
            ->ordered()
            ->get();
    }
}

<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'institution_id',
        'prospect_id',
        'customer_code',
        'customer_type',
        'first_name',
        'middle_name',
        'last_name',
        'date_of_birth',
        'gender',
        'marital_status',
        'national_id',
        'tin',
        'passport_number',
        'phone_primary',
        'phone_secondary',
        'email',
        'physical_address',
        'city',
        'region',
        'postal_code',
        'country',
        'employer_name',
        'business_name',
        'occupation',
        'industry',
        'employment_start_date',
        'next_of_kin_name',
        'next_of_kin_relationship',
        'next_of_kin_phone',
        'next_of_kin_address',
        'profile_completion_percentage',
        'kyc_verified',
        'kyc_verified_at',
        'kyc_verified_by',
        'notes',
        'status',
        'source',
        'deactivated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'customer_type' => CustomerType::class,
        'date_of_birth' => 'date',
        'employment_start_date' => 'date',
        'profile_completion_percentage' => 'integer',
        'kyc_verified' => 'boolean',
        'kyc_verified_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['full_name'];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($customer) {
            if (empty($customer->customer_code)) {
                $institution = Institution::find($customer->institution_id);
                $prefix = $institution ? ($institution->getSettingsConfig()['customer_id_prefix'] ?? 'CUS') : 'CUS';
                
                // Count existing customers for this institution to get next number
                $count = Customer::where('institution_id', $customer->institution_id)->count();
                $nextNumber = $count + 1;
                
                $customer->customer_code = $prefix . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
            }
        });

        static::saved(function ($customer) {
            $customer->updateProfileCompletion();
        });
    }

    /**
     * Get the institution that owns the customer.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the prospect this customer was converted from.
     */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    /**
     * Get the KYC documents for the customer.
     */
    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    /**
     * Get the applications for the customer.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Get the bank statement imports for the customer.
     */
    public function bankStatementImports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class);
    }

    /**
     * Get the bank transactions for the customer.
     */
    public function bankTransactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    /**
     * Get the statement analytics for the customer.
     */
    public function statementAnalytics(): HasMany
    {
        return $this->hasMany(StatementAnalytics::class);
    }

    /**
     * Get the user who verified the KYC.
     */
    public function kycVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kyc_verified_by');
    }

    /**
     * Get the customer's full name.
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->middle_name ? $this->middle_name . ' ' : '') . $this->last_name);
    }

    /**
     * Update profile completion percentage.
     * Tracks: basic info, contact details, employment, next of kin, and KYC documents
     */
    public function updateProfileCompletion(): void
    {
        $totalScore = 0;
        $maxScore = 0;

        // 1. Basic Information (30 points)
        $basicFields = [
            'first_name' => 5,
            'last_name' => 5,
            'date_of_birth' => 3,
            'gender' => 2,
            'national_id' => 5,
            'marital_status' => 2,
        ];
        
        foreach ($basicFields as $field => $points) {
            $maxScore += $points;
            if (!empty($this->$field)) {
                $totalScore += $points;
            }
        }

        // 2. Contact Information (20 points)
        $contactFields = [
            'phone_primary' => 7,
            'email' => 7,
            'physical_address' => 3,
            'city' => 2,
            'country' => 1,
        ];
        
        foreach ($contactFields as $field => $points) {
            $maxScore += $points;
            if (!empty($this->$field)) {
                $totalScore += $points;
            }
        }

        // 3. Employment/Business Information (20 points)
        $employmentPoints = 20;
        $maxScore += $employmentPoints;
        
        if ($this->customer_type === CustomerType::SALARY) {
            $employmentFilled = 0;
            $employmentTotal = 3;
            if (!empty($this->employer_name)) $employmentFilled++;
            if (!empty($this->occupation)) $employmentFilled++;
            if (!empty($this->employment_start_date)) $employmentFilled++;
            $totalScore += round(($employmentFilled / $employmentTotal) * $employmentPoints);
        } elseif ($this->customer_type === CustomerType::BUSINESS) {
            $businessFilled = 0;
            $businessTotal = 3;
            if (!empty($this->business_name)) $businessFilled++;
            if (!empty($this->industry)) $businessFilled++;
            if (!empty($this->occupation)) $businessFilled++;
            $totalScore += round(($businessFilled / $businessTotal) * $employmentPoints);
        } else {
            // If type not specified, give partial credit for any employment field
            if (!empty($this->employer_name) || !empty($this->business_name) || !empty($this->occupation)) {
                $totalScore += round($employmentPoints / 2);
            }
        }

        // 4. Next of Kin Information (10 points)
        $nokFields = [
            'next_of_kin_name' => 4,
            'next_of_kin_relationship' => 2,
            'next_of_kin_phone' => 4,
        ];
        
        foreach ($nokFields as $field => $points) {
            $maxScore += $points;
            if (!empty($this->$field)) {
                $totalScore += $points;
            }
        }

        // 5. KYC Documents (20 points)
        $kycPoints = 20;
        $maxScore += $kycPoints;
        
        // Required document types for complete KYC
        $requiredDocTypes = [
            'national_id',
            'passport',
            'utility_bill',
            'bank_statement',
        ];
        
        $uploadedDocTypes = $this->kycDocuments()
            ->whereNull('deleted_at')
            ->pluck('document_type')
            ->map(fn($type) => $type->value ?? $type)
            ->unique()
            ->toArray();
        
        $docScore = 0;
        $verifiedDocCount = 0;
        
        foreach ($requiredDocTypes as $docType) {
            if (in_array($docType, $uploadedDocTypes)) {
                $docScore += ($kycPoints / count($requiredDocTypes));
                
                // Check if this document type is verified
                $isVerified = $this->kycDocuments()
                    ->whereNull('deleted_at')
                    ->where('document_type', $docType)
                    ->where('verification_status', \App\Enums\VerificationStatus::VERIFIED)
                    ->exists();
                
                if ($isVerified) {
                    $verifiedDocCount++;
                }
            }
        }
        
        $totalScore += $docScore;

        // Bonus: KYC Verified Status (additional 5 points if all docs verified)
        if ($this->kyc_verified || $verifiedDocCount >= count($requiredDocTypes)) {
            $totalScore += 5;
            $maxScore += 5;
        } else {
            $maxScore += 5;
        }

        // Calculate final percentage
        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100) : 0;
        
        // Ensure percentage is between 0 and 100
        $percentage = min(100, max(0, $percentage));
        
        if ($this->profile_completion_percentage !== $percentage) {
            $this->updateQuietly(['profile_completion_percentage' => $percentage]);
        }
    }

    /**
     * Check if customer is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if customer is KYC verified.
     */
    public function isKycVerified(): bool
    {
        return $this->kyc_verified === true;
    }

    /**
     * Mark customer KYC as verified.
     */
    public function verifyKyc(int $verifiedBy): void
    {
        $this->update([
            'kyc_verified' => true,
            'kyc_verified_at' => now(),
            'kyc_verified_by' => $verifiedBy,
        ]);
    }

    /**
     * Deactivate the customer.
     */
    public function deactivate(): void
    {
        $this->update([
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);
    }

    /**
     * Activate the customer.
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'deactivated_at' => null,
        ]);
    }

    /**
     * Scope to only active customers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to KYC verified customers.
     */
    public function scopeKycVerified($query)
    {
        return $query->where('kyc_verified', true);
    }

    /**
     * Scope to customers of a specific type.
     */
    public function scopeOfType($query, CustomerType $type)
    {
        return $query->where('customer_type', $type);
    }

    /**
     * Scope to customers for a specific institution.
     */
    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }
}

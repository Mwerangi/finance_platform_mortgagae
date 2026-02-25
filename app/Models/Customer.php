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
     */
    public function updateProfileCompletion(): void
    {
        $fields = [
            'first_name',
            'last_name',
            'date_of_birth',
            'gender',
            'national_id',
            'phone_primary',
            'email',
            'physical_address',
            'city',
            'occupation',
        ];

        // Additional fields based on customer type
        if ($this->customer_type === CustomerType::SALARY) {
            $fields[] = 'employer_name';
        } elseif ($this->customer_type === CustomerType::BUSINESS) {
            $fields[] = 'business_name';
        }

        $filled = 0;
        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                $filled++;
            }
        }

        $percentage = round(($filled / count($fields)) * 100);
        
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

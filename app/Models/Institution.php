<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'timezone',
        'currency',
        'date_format',
        'branding',
        'settings',
        'status',
        'activated_at',
        'deactivated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'branding' => 'array',
        'settings' => 'array',
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($institution) {
            if (empty($institution->slug)) {
                $institution->slug = Str::slug($institution->name);
            }
            if (empty($institution->code)) {
                $institution->code = 'INST' . str_pad(Institution::max('id') + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the users for the institution.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the loan products for the institution.
     */
    public function loanProducts(): HasMany
    {
        return $this->hasMany(LoanProduct::class);
    }

    /**
     * Get the customers for the institution.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Check if institution is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Activate the institution.
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
            'activated_at' => now(),
            'deactivated_at' => null,
        ]);
    }

    /**
     * Deactivate the institution.
     */
    public function deactivate(): void
    {
        $this->update([
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);
    }

    /**
     * Get branding configuration with defaults.
     */
    public function getBrandingConfig(): array
    {
        return array_merge([
            'logo_url' => null,
            'favicon_url' => null,
            'primary_color' => '#1E40AF',
            'secondary_color' => '#64748B',
            'accent_color' => '#10B981',
            'custom_domain' => null,
            'email_from_name' => $this->name,
            'email_from_address' => $this->email,
        ], $this->branding ?? []);
    }

    /**
     * Get settings with defaults.
     */
    public function getSettingsConfig(): array
    {
        return array_merge([
            'features' => ['analytics', 'collections'],
            'loan_account_prefix' => 'LN',
            'customer_id_prefix' => 'CUS',
            'max_file_size_mb' => 50,
            'allowed_file_types' => ['xlsx', 'csv', 'xls'],
            'require_kyc_verification' => true,
            'auto_run_analytics' => true,
        ], $this->settings ?? []);
    }

    /**
     * Update branding configuration.
     */
    public function updateBranding(array $branding): void
    {
        $currentBranding = $this->branding ?? [];
        $this->update([
            'branding' => array_merge($currentBranding, $branding),
        ]);
    }

    /**
     * Update settings configuration.
     */
    public function updateSettings(array $settings): void
    {
        $currentSettings = $this->settings ?? [];
        $this->update([
            'settings' => array_merge($currentSettings, $settings),
        ]);
    }

    /**
     * Scope to only active institutions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'key',
        'label',
        'value',
        'default_value',
        'data_type',
        'description',
        'unit',
        'display_order',
        'is_public',
        'is_editable',
        'requires_restart',
        'validation_rules',
        'options',
        'updated_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_editable' => 'boolean',
        'requires_restart' => 'boolean',
        'validation_rules' => 'array',
        'options' => 'array',
        'display_order' => 'integer',
    ];

    // Categories
    public const CATEGORY_POLICY_RISK = 'policy_risk';
    public const CATEGORY_INTEREST_RATES = 'interest_rates';
    public const CATEGORY_ASSESSMENT = 'assessment';
    public const CATEGORY_WORKFLOW = 'workflow';
    public const CATEGORY_DOCUMENTS = 'documents';
    public const CATEGORY_EMAILS = 'emails';
    public const CATEGORY_BRANDING = 'branding';
    public const CATEGORY_SYSTEM = 'system';
    public const CATEGORY_INTEGRATIONS = 'integrations';
    public const CATEGORY_COMPLIANCE = 'compliance';

    /**
     * Get the user who last updated this setting
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get typed value based on data_type
     */
    public function getTypedValueAttribute()
    {
        $value = $this->value ?? $this->default_value;

        return match ($this->data_type) {
            'number' => is_numeric($value) ? (float) $value : $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Static helper to get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting?->typed_value ?? $default;
        });
    }

    /**
     * Static helper to set a setting value
     */
    public static function set(string $key, $value, ?int $userId = null): bool
    {
        $setting = static::where('key', $key)->first();
        
        if (!$setting) {
            return false;
        }

        if (!$setting->is_editable) {
            return false;
        }

        $setting->update([
            'value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
            'updated_by' => $userId ?? auth()->id(),
        ]);

        Cache::forget("setting.{$key}");

        return true;
    }

    /**
     * Get all settings grouped by category
     */
    public static function getAllGrouped(): array
    {
        return static::orderBy('category')
            ->orderBy('display_order')
            ->get()
            ->groupBy('category')
            ->toArray();
    }

    /**
     * Get public settings (exposed to frontend)
     */
    public static function getPublic(): array
    {
        return Cache::remember('settings.public', 3600, function () {
            return static::where('is_public', true)
                ->get()
                ->pluck('typed_value', 'key')
                ->toArray();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('settings.public');
        
        static::all()->each(function ($setting) {
            Cache::forget("setting.{$setting->key}");
        });
    }

    /**
     * Boot method to clear cache on update
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("setting.{$setting->key}");
            Cache::forget('settings.public');
        });

        static::deleted(function ($setting) {
            Cache::forget("setting.{$setting->key}");
            Cache::forget('settings.public');
        });
    }
}

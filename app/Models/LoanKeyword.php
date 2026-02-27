<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LoanKeyword extends Model
{
    protected $table = 'loan_detection_keywords';
    
    protected $fillable = [
        'institution_id',
        'keyword',
        'type',
        'language',
        'weight',
        'is_active',
        'description',
    ];

    protected $casts = [
        'institution_id' => 'integer',
        'weight' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get all active keywords for loan detection
     * 
     * @param int|null $institutionId
     * @return \Illuminate\Support\Collection
     */
    public static function getActiveKeywords(?int $institutionId = null)
    {
        $cacheKey = 'loan_keywords_' . ($institutionId ?? 'global');
        
        return Cache::remember($cacheKey, 3600, function () use ($institutionId) {
            return static::where('is_active', true)
                ->when($institutionId, function ($query, $institutionId) {
                    $query->where(function ($q) use ($institutionId) {
                        $q->where('institution_id', $institutionId)
                          ->orWhereNull('institution_id');
                    });
                }, function ($query) {
                    $query->whereNull('institution_id');
                })
                ->orderBy('weight', 'desc')
                ->get();
        });
    }

    /**
     * Get keywords by type
     * 
     * @param string $type 'repayment' or 'disbursement'
     * @param int|null $institutionId
     * @return \Illuminate\Support\Collection
     */
    public static function getByType(string $type, ?int $institutionId = null)
    {
        return static::getActiveKeywords($institutionId)
            ->where('type', $type);
    }

    /**
     * Clear keywords cache
     */
    public static function clearCache(?int $institutionId = null)
    {
        $keys = $institutionId 
            ? ['loan_keywords_' . $institutionId, 'loan_keywords_global']
            : ['loan_keywords_global'];
            
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Boot method to clear cache on model changes
     */
    protected static function booted()
    {
        static::saved(function ($keyword) {
            static::clearCache($keyword->institution_id);
        });

        static::deleted(function ($keyword) {
            static::clearCache($keyword->institution_id);
        });
    }
}


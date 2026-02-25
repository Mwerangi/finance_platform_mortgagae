<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'institution_id',
        'user_id',
        'user_name',
        'user_role',
        'event_type',
        'event_category',
        'action',
        'description',
        'entity_type',
        'entity_id',
        'http_method',
        'request_url',
        'request_body',
        'response_status',
        'ip_address',
        'user_agent',
        'session_id',
        'old_values',
        'new_values',
        'metadata',
        'is_critical',
        'is_sensitive',
        'severity',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'is_critical' => 'boolean',
        'is_sensitive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the institution that owns the audit log.
     */
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by event type.
     */
    public function scopeEventType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope for filtering by event category.
     */
    public function scopeEventCategory($query, string $category)
    {
        return $query->where('event_category', $category);
    }

    /**
     * Scope for filtering by entity.
     */
    public function scopeForEntity($query, string $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)
                     ->where('entity_id', $entityId);
    }

    /**
     * Scope for filtering by institution.
     */
    public function scopeForInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Scope for filtering by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for critical events only.
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    /**
     * Scope for date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for severity level.
     */
    public function scopeSeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Get changes made (comparison of old vs new values).
     */
    public function getChanges(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        $allKeys = array_unique(array_merge(
            array_keys($this->old_values),
            array_keys($this->new_values)
        ));

        foreach ($allKeys as $key) {
            $oldValue = $this->old_values[$key] ?? null;
            $newValue = $this->new_values[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Check if log contains sensitive data.
     */
    public function hasSensitiveData(): bool
    {
        return $this->is_sensitive;
    }

    /**
     * Get timeline of events for an entity.
     */
    public static function getEntityTimeline(string $entityType, $entityId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return static::forEntity($entityType, $entityId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user activity summary.
     */
    public static function getUserActivity(int $userId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $logs = static::byUser($userId)
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'total_actions' => $logs->count(),
            'by_event_type' => $logs->groupBy('event_type')->map->count(),
            'by_action' => $logs->groupBy('action')->map->count(),
            'by_entity_type' => $logs->groupBy('entity_type')->map->count(),
            'critical_events' => $logs->where('is_critical', true)->count(),
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get institution activity summary.
     */
    public static function getInstitutionActivity(int $institutionId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $logs = static::forInstitution($institutionId)
            ->where('created_at', '>=', $startDate)
            ->get();

        return [
            'total_actions' => $logs->count(),
            'unique_users' => $logs->pluck('user_id')->unique()->filter()->count(),
            'by_event_type' => $logs->groupBy('event_type')->map->count(),
            'by_severity' => $logs->groupBy('severity')->map->count(),
            'critical_events' => $logs->where('is_critical', true)->count(),
            'sensitive_events' => $logs->where('is_sensitive', true)->count(),
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
        ];
    }
}

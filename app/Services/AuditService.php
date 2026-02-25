<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log an audit event.
     */
    public function log(array $data): AuditLog
    {
        // Auto-fill user information if authenticated
        if (Auth::check() && !isset($data['user_id'])) {
            $user = Auth::user();
            $data['user_id'] = $user->id;
            $data['user_name'] = $user->name;
            $data['user_role'] = $user->roles->first()?->name ?? 'unknown';
        }

        // Auto-fill institution if available
        if (Auth::check() && !isset($data['institution_id']) && Auth::user()->institution_id) {
            $data['institution_id'] = Auth::user()->institution_id;
        }

        return AuditLog::create($data);
    }

    /**
     * Log authentication event.
     */
    public function logAuthentication(string $action, string $description, array $metadata = []): AuditLog
    {
        return $this->log([
            'event_type' => 'authentication',
            'event_category' => $action,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'is_critical' => in_array($action, ['login_failed', 'password_reset', 'account_locked']),
            'severity' => $this->determineSeverity('authentication', $action),
        ]);
    }

    /**
     * Log authorization event (access denied).
     */
    public function logAuthorization(string $action, string $resource, string $description, array $metadata = []): AuditLog
    {
        return $this->log([
            'event_type' => 'authorization',
            'event_category' => 'access_denied',
            'action' => $action,
            'description' => $description,
            'entity_type' => $resource,
            'metadata' => $metadata,
            'is_critical' => true,
            'severity' => 'medium',
        ]);
    }

    /**
     * Log data modification event.
     */
    public function logDataModification(
        string $action,
        string $entityType,
        $entityId,
        array $oldValues = null,
        array $newValues = null,
        string $description = null
    ): AuditLog {
        $description = $description ?? ucfirst($action) . " {$entityType} (ID: {$entityId})";

        return $this->log([
            'event_type' => 'data_modification',
            'event_category' => "{$entityType}_{$action}",
            'action' => $action,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'is_critical' => in_array($action, ['delete', 'restore']),
            'is_sensitive' => $this->isSensitiveEntity($entityType),
            'severity' => $this->determineSeverity('data_modification', $action),
        ]);
    }

    /**
     * Log decision event (approval, decline, override).
     */
    public function logDecision(
        string $action,
        string $entityType,
        $entityId,
        string $decision,
        string $reason = null,
        array $metadata = []
    ): AuditLog {
        $description = ucfirst($action) . " {$entityType} (ID: {$entityId}): {$decision}";
        if ($reason) {
            $description .= " - {$reason}";
        }

        return $this->log([
            'event_type' => 'decision',
            'event_category' => "{$entityType}_{$action}",
            'action' => $action,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id' => (string) $entityId,
            'metadata' => array_merge(['decision' => $decision, 'reason' => $reason], $metadata),
            'is_critical' => in_array($action, ['approve', 'decline', 'override']),
            'severity' => 'high',
        ]);
    }

    /**
     * Log import/upload event.
     */
    public function logImport(
        string $importType,
        string $filename,
        int $recordsProcessed,
        int $recordsFailed = 0,
        array $metadata = []
    ): AuditLog {
        $description = "Imported {$importType}: {$filename} ({$recordsProcessed} processed, {$recordsFailed} failed)";

        return $this->log([
            'event_type' => 'import',
            'event_category' => "{$importType}_import",
            'action' => 'upload',
            'description' => $description,
            'metadata' => array_merge([
                'filename' => $filename,
                'records_processed' => $recordsProcessed,
                'records_failed' => $recordsFailed,
            ], $metadata),
            'is_critical' => $recordsFailed > 0,
            'severity' => $recordsFailed > 0 ? 'medium' : 'low',
        ]);
    }

    /**
     * Log configuration change.
     */
    public function logConfigurationChange(
        string $configType,
        array $oldValues,
        array $newValues,
        string $description = null
    ): AuditLog {
        $description = $description ?? "Configuration changed: {$configType}";

        return $this->log([
            'event_type' => 'configuration',
            'event_category' => 'config_change',
            'action' => 'update',
            'description' => $description,
            'entity_type' => $configType,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'is_critical' => true,
            'severity' => 'high',
        ]);
    }

    /**
     * Log file access event.
     */
    public function logFileAccess(
        string $action,
        string $filename,
        string $entityType = null,
        $entityId = null,
        array $metadata = []
    ): AuditLog {
        $description = ucfirst($action) . " file: {$filename}";

        return $this->log([
            'event_type' => 'file_access',
            'event_category' => "file_{$action}",
            'action' => $action,
            'description' => $description,
            'entity_type' => $entityType,
            'entity_id' => $entityId ? (string) $entityId : null,
            'metadata' => array_merge(['filename' => $filename], $metadata),
            'is_sensitive' => true,
            'severity' => 'low',
        ]);
    }

    /**
     * Log HTTP request (for audit middleware).
     */
    public function logRequest(Request $request, int $responseStatus): AuditLog
    {
        $user = Auth::user();
        $action = $this->getActionFromRequest($request);

        return $this->log([
            'event_type' => 'api_request',
            'event_category' => 'http_request',
            'action' => $action,
            'description' => "{$request->method()} {$request->path()}",
            'http_method' => $request->method(),
            'request_url' => $request->fullUrl(),
            'request_body' => $this->sanitizeRequestBody($request),
            'response_status' => $responseStatus,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()?->getId(),
            'is_sensitive' => $this->isRequestSensitive($request),
            'severity' => $responseStatus >= 400 ? 'medium' : 'low',
        ]);
    }

    /**
     * Determine if an entity type is sensitive.
     */
    protected function isSensitiveEntity(string $entityType): bool
    {
        $sensitiveEntities = [
            'Customer',
            'KycDocument',
            'BankStatementImport',
            'BankStatementTransaction',
            'User',
            'Application',
        ];

        return in_array($entityType, $sensitiveEntities);
    }

    /**
     * Determine severity based on event type and action.
     */
    protected function determineSeverity(string $eventType, string $action): string
    {
        $highSeverityActions = ['delete', 'approve', 'decline', 'override', 'login_failed', 'access_denied'];
        $mediumSeverityActions = ['update', 'restore', 'verify', 'reject'];

        if (in_array($action, $highSeverityActions)) {
            return 'high';
        }

        if (in_array($action, $mediumSeverityActions)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get action from HTTP request.
     */
    protected function getActionFromRequest(Request $request): string
    {
        $method = strtolower($request->method());
        
        $actionMap = [
            'get' => 'view',
            'post' => 'create',
            'put' => 'update',
            'patch' => 'update',
            'delete' => 'delete',
        ];

        return $actionMap[$method] ?? $method;
    }

    /**
     * Sanitize request body (remove sensitive fields).
     */
    protected function sanitizeRequestBody(Request $request): ?string
    {
        $body = $request->all();
        
        // Remove sensitive fields
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'token',
            'api_token',
            'secret',
            'api_key',
            'credit_card',
            'cvv',
            'pin',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($body[$field])) {
                $body[$field] = '[REDACTED]';
            }
        }

        // Limit body size (max 10KB)
        $json = json_encode($body);
        if (strlen($json) > 10240) {
            return substr($json, 0, 10240) . '... [TRUNCATED]';
        }

        return $json;
    }

    /**
     * Check if request is sensitive.
     */
    protected function isRequestSensitive(Request $request): bool
    {
        $sensitivePaths = [
            'auth',
            'password',
            'kyc-documents',
            'bank-statements',
            'customers',
        ];

        foreach ($sensitivePaths as $path) {
            if (str_contains($request->path(), $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get audit statistics for an institution.
     */
    public function getStatistics(int $institutionId, int $days = 30): array
    {
        return AuditLog::getInstitutionActivity($institutionId, $days);
    }

    /**
     * Get user activity summary.
     */
    public function getUserActivitySummary(int $userId, int $days = 30): array
    {
        return AuditLog::getUserActivity($userId, $days);
    }

    /**
     * Export audit logs (filtered).
     */
    public function exportLogs(array $filters): \Illuminate\Support\Collection
    {
        $query = AuditLog::query();

        if (isset($filters['institution_id'])) {
            $query->forInstitution($filters['institution_id']);
        }

        if (isset($filters['user_id'])) {
            $query->byUser($filters['user_id']);
        }

        if (isset($filters['event_type'])) {
            $query->eventType($filters['event_type']);
        }

        if (isset($filters['entity_type']) && isset($filters['entity_id'])) {
            $query->forEntity($filters['entity_type'], $filters['entity_id']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        }

        if (isset($filters['severity'])) {
            $query->severity($filters['severity']);
        }

        if (isset($filters['is_critical'])) {
            $query->critical();
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 1000)
            ->get();
    }
}

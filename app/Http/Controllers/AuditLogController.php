<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AuditLogController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected ExportService $exportService
    ) {}

    /**
     * Get audit logs with filters.
     */
    public function index(Request $request, int $institutionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'nullable|string',
            'event_category' => 'nullable|string',
            'action' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable|string',
            'severity' => 'nullable|in:low,medium,high,critical',
            'is_critical' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = AuditLog::forInstitution($institutionId)
                ->with(['user:id,name,email', 'institution:id,name']);

            // Apply filters
            if ($request->filled('event_type')) {
                $query->eventType($request->input('event_type'));
            }

            if ($request->filled('event_category')) {
                $query->eventCategory($request->input('event_category'));
            }

            if ($request->filled('action')) {
                $query->where('action', $request->input('action'));
            }

            if ($request->filled('user_id')) {
                $query->byUser($request->integer('user_id'));
            }

            if ($request->filled('entity_type') && $request->filled('entity_id')) {
                $query->forEntity($request->input('entity_type'), $request->input('entity_id'));
            }

            if ($request->filled('severity')) {
                $query->severity($request->input('severity'));
            }

            if ($request->boolean('is_critical')) {
                $query->critical();
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->dateRange(
                    $request->date('start_date'),
                    $request->date('end_date')
                );
            }

            $perPage = $request->integer('per_page', 50);
            $logs = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'message' => 'Audit logs retrieved successfully',
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single audit log details.
     */
    public function show(Request $request, int $institutionId, int $logId): JsonResponse
    {
        try {
            $log = AuditLog::forInstitution($institutionId)
                ->with(['user', 'institution'])
                ->findOrFail($logId);

            return response()->json([
                'message' => 'Audit log retrieved successfully',
                'data' => $log
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Audit log not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get entity audit trail.
     */
    public function getEntityAudit(Request $request, int $institutionId, string $entityType, string $entityId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:10|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $limit = $request->integer('limit', 50);
            
            $logs = AuditLog::forInstitution($institutionId)
                ->forEntity($entityType, $entityId)
                ->with(['user:id,name,email'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            return response()->json([
                'message' => 'Entity audit trail retrieved successfully',
                'data' => [
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'total_events' => $logs->count(),
                    'logs' => $logs,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve entity audit trail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user activity.
     */
    public function getUserActivity(Request $request, int $institutionId, int $userId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365',
            'include_logs' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $days = $request->integer('days', 30);
            $includeLogs = $request->boolean('include_logs', false);

            $summary = $this->auditService->getUserActivitySummary($userId, $days);

            $response = [
                'message' => 'User activity retrieved successfully',
                'data' => [
                    'user_id' => $userId,
                    'summary' => $summary,
                ]
            ];

            if ($includeLogs) {
                $logs = AuditLog::forInstitution($institutionId)
                    ->byUser($userId)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->orderBy('created_at', 'desc')
                    ->limit(100)
                    ->get();

                $response['data']['recent_logs'] = $logs;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve user activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get institution audit statistics.
     */
    public function getStatistics(Request $request, int $institutionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $days = $request->integer('days', 30);
            $statistics = $this->auditService->getStatistics($institutionId, $days);

            return response()->json([
                'message' => 'Audit statistics retrieved successfully',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve audit statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export audit logs to Excel.
     */
    public function export(Request $request, int $institutionId): BinaryFileResponse|JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_type' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
            'entity_type' => 'nullable|string',
            'entity_id' => 'nullable|string',
            'severity' => 'nullable|in:low,medium,high,critical',
            'is_critical' => 'nullable|boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'limit' => 'nullable|integer|min:100|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $filters = array_merge(
                ['institution_id' => $institutionId],
                $request->only([
                    'event_type',
                    'user_id',
                    'entity_type',
                    'entity_id',
                    'severity',
                    'is_critical',
                    'start_date',
                    'end_date',
                    'limit'
                ])
            );

            $logs = $this->auditService->exportLogs($filters);

            // Transform for Excel export
            $exportData = $logs->map(function ($log) {
                return [
                    'ID' => $log->id,
                    'Date/Time' => $log->created_at->format('Y-m-d H:i:s'),
                    'User' => $log->user_name ?? 'System',
                    'User Role' => $log->user_role ?? 'N/A',
                    'Event Type' => $log->event_type,
                    'Event Category' => $log->event_category,
                    'Action' => $log->action,
                    'Description' => $log->description,
                    'Entity Type' => $log->entity_type ?? 'N/A',
                    'Entity ID' => $log->entity_id ?? 'N/A',
                    'HTTP Method' => $log->http_method ?? 'N/A',
                    'IP Address' => $log->ip_address ?? 'N/A',
                    'Severity' => ucfirst($log->severity),
                    'Critical' => $log->is_critical ? 'Yes' : 'No',
                    'Sensitive' => $log->is_sensitive ? 'Yes' : 'No',
                ];
            });

            $filename = 'audit-logs-export-' . now()->format('Y-m-d-His') . '.xlsx';
            $filePath = $this->exportService->generateExcel($exportData, $filename);

            return response()->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get critical events.
     */
    public function getCriticalEvents(Request $request, int $institutionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:90',
            'per_page' => 'nullable|integer|min:10|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $days = $request->integer('days', 7);
            $perPage = $request->integer('per_page', 50);

            $logs = AuditLog::forInstitution($institutionId)
                ->critical()
                ->where('created_at', '>=', now()->subDays($days))
                ->with(['user:id,name,email'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'message' => 'Critical events retrieved successfully',
                'data' => $logs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve critical events',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get event timeline for dashboard.
     */
    public function getTimeline(Request $request, int $institutionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'hours' => 'nullable|integer|min:1|max:168', // Up to 7 days
            'event_types' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $hours = $request->integer('hours', 24);
            $eventTypes = $request->input('event_types', []);

            $query = AuditLog::forInstitution($institutionId)
                ->where('created_at', '>=', now()->subHours($hours))
                ->with(['user:id,name']);

            if (!empty($eventTypes)) {
                $query->whereIn('event_type', $eventTypes);
            }

            $logs = $query->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            // Group by hour
            $timeline = $logs->groupBy(function ($log) {
                return $log->created_at->format('Y-m-d H:00');
            })->map(function ($group, $hour) {
                return [
                    'hour' => $hour,
                    'count' => $group->count(),
                    'by_event_type' => $group->groupBy('event_type')->map->count(),
                    'critical_count' => $group->where('is_critical', true)->count(),
                ];
            })->values();

            return response()->json([
                'message' => 'Event timeline retrieved successfully',
                'data' => [
                    'hours' => $hours,
                    'timeline' => $timeline,
                    'total_events' => $logs->count(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve event timeline',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

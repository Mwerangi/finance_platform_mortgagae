<?php

namespace App\Http\Controllers;

use App\Services\CollectionsService;
use App\Services\CollectionsActionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class CollectionsController extends Controller
{
    protected CollectionsService $collectionsService;
    protected CollectionsActionService $actionService;

    public function __construct(
        CollectionsService $collectionsService,
        CollectionsActionService $actionService
    ) {
        $this->collectionsService = $collectionsService;
        $this->actionService = $actionService;
    }

    /**
     * Generate collections queue for an institution.
     */
    public function generateQueue(Request $request, int $institutionId): JsonResponse
    {
        try {
            $result = $this->collectionsService->generateQueue($institutionId);

            return response()->json([
                'message' => 'Collections queue generated successfully',
                'data' => $result,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to generate collections queue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get collections queue with filters.
     */
    public function getQueue(Request $request, int $institutionId): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'priority_level',
                'delinquency_bucket',
                'assigned_to',
                'has_active_ptp',
                'is_legal_case',
                'min_dpd',
                'max_dpd',
                'sort_by',
                'sort_order',
                'per_page',
            ]);

            $queue = $this->collectionsService->getQueue($institutionId, $filters);

            return response()->json([
                'message' => 'Collections queue retrieved successfully',
                'data' => $queue,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve collections queue',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign queue items to officers.
     */
    public function assignToOfficers(Request $request, int $institutionId): JsonResponse
    {
        $request->validate([
            'assignments' => 'required|array',
            'assignments.*.officer_id' => 'required|exists:users,id',
            'assignments.*.queue_ids' => 'required|array',
            'assignments.*.queue_ids.*' => 'exists:collections_queue,id',
        ]);

        try {
            $results = $this->collectionsService->assignToOfficers(
                $institutionId,
                $request->input('assignments')
            );

            return response()->json([
                'message' => 'Queue items assigned successfully',
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to assign queue items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Auto-distribute unassigned items to officers.
     */
    public function autoDistribute(Request $request, int $institutionId): JsonResponse
    {
        $request->validate([
            'officer_ids' => 'required|array',
            'officer_ids.*' => 'exists:users,id',
        ]);

        try {
            $results = $this->collectionsService->autoDistribute(
                $institutionId,
                $request->input('officer_ids')
            );

            return response()->json([
                'message' => 'Queue items distributed successfully',
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to distribute queue items',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update queue item status.
     */
    public function updateQueueStatus(Request $request, int $institutionId, int $queueId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:pending,assigned,in_progress,contacted,ptp_made,resolved,escalated,closed',
            'notes' => 'nullable|string',
            'next_action_due' => 'nullable|date',
        ]);

        try {
            $queueItem = $this->collectionsService->updateStatus(
                $queueId,
                $request->input('status'),
                $request->only(['notes', 'next_action_due'])
            );

            return response()->json([
                'message' => 'Queue item status updated successfully',
                'data' => $queueItem,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update queue item status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Escalate queue item to legal.
     */
    public function escalateToLegal(Request $request, int $institutionId, int $queueId): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        try {
            $queueItem = $this->collectionsService->escalateToLegal(
                $queueId,
                $request->input('reason')
            );

            return response()->json([
                'message' => 'Queue item escalated to legal successfully',
                'data' => $queueItem,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to escalate queue item',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Log a collections action.
     */
    public function logAction(Request $request, int $institutionId): JsonResponse
    {
        $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'customer_id' => 'required|exists:customers,id',
            'queue_id' => 'nullable|exists:collections_queue,id',
            'action_type' => 'required|in:phone_call,sms,email,field_visit,office_visit,letter,legal_notice,other',
            'action_date' => 'required|date',
            'contact_method' => 'nullable|string',
            'outcome' => 'required|in:successful,no_answer,wrong_number,call_back_requested,payment_promised,payment_received,dispute_raised,refused_to_pay,partial_payment,other',
            'notes' => 'nullable|string',
            'customer_response' => 'nullable|string',
            'amount_committed' => 'nullable|numeric|min:0',
            'commitment_date' => 'nullable|date|after_or_equal:today',
            'next_action_date' => 'nullable|date',
            'next_action_type' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'duration_minutes' => 'nullable|integer|min:0',
        ]);

        try {
            $data = $request->all();
            $data['institution_id'] = $institutionId;
            $data['performed_by'] = auth()->id();

            $action = $this->actionService->logAction($data);

            return response()->json([
                'message' => 'Collections action logged successfully',
                'data' => $action,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to log collections action',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loan collections history.
     */
    public function getLoanHistory(Request $request, int $institutionId, int $loanId): JsonResponse
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            $history = $this->actionService->getLoanHistory($loanId, $startDate, $endDate);

            return response()->json([
                'message' => 'Loan collections history retrieved successfully',
                'data' => $history,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve loan history',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a promise to pay.
     */
    public function createPromiseToPay(Request $request, int $institutionId): JsonResponse
    {
        $request->validate([
            'loan_id' => 'required|exists:loans,id',
            'customer_id' => 'required|exists:customers,id',
            'collections_action_id' => 'nullable|exists:collections_actions,id',
            'promise_date' => 'required|date',
            'commitment_date' => 'required|date|after_or_equal:promise_date',
            'promised_amount' => 'required|numeric|min:0',
            'principal_amount' => 'nullable|numeric|min:0',
            'interest_amount' => 'nullable|numeric|min:0',
            'penalty_amount' => 'nullable|numeric|min:0',
            'fees_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'customer_reason' => 'nullable|string',
        ]);

        try {
            $data = $request->all();
            $data['institution_id'] = $institutionId;
            $data['created_by'] = auth()->id();
            $data['status'] = 'open';

            $ptp = $this->actionService->createPromiseToPay($data);

            return response()->json([
                'message' => 'Promise to pay created successfully',
                'data' => $ptp,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create promise to pay',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update promise to pay status.
     */
    public function updatePromiseStatus(Request $request, int $institutionId, int $ptpId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:open,kept,partially_kept,broken,rescheduled,cancelled',
            'amount_paid' => 'nullable|numeric|min:0',
            'actual_payment_date' => 'nullable|date',
            'payment_id' => 'nullable|exists:repayments,id',
        ]);

        try {
            $ptp = $this->actionService->updatePromiseStatus(
                $ptpId,
                $request->only(['status', 'amount_paid', 'actual_payment_date', 'payment_id'])
            );

            return response()->json([
                'message' => 'Promise to pay status updated successfully',
                'data' => $ptp,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update promise status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get promise to pay by ID.
     */
    public function getPromiseToPay(Request $request, int $institutionId, int $ptpId): JsonResponse
    {
        try {
            $ptp = $this->actionService->getPromiseToPay($ptpId);

            return response()->json([
                'message' => 'Promise to pay retrieved successfully',
                'data' => $ptp,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve promise to pay',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get promises to pay with filters.
     */
    public function getPromisesToPay(Request $request, int $institutionId): JsonResponse
    {
        try {
            $filters = $request->only([
                'status',
                'loan_id',
                'customer_id',
                'created_by',
                'commitment_date_from',
                'commitment_date_to',
                'sort_by',
                'sort_order',
                'per_page',
            ]);

            $ptps = $this->actionService->getPromisesToPay($institutionId, $filters);

            return response()->json([
                'message' => 'Promises to pay retrieved successfully',
                'data' => $ptps,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve promises to pay',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get collections performance metrics.
     */
    public function getPerformanceMetrics(Request $request, int $institutionId): JsonResponse
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            $metrics = $this->collectionsService->getPerformanceMetrics($institutionId, $startDate, $endDate);

            return response()->json([
                'message' => 'Performance metrics retrieved successfully',
                'data' => $metrics,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve performance metrics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get collections officer performance.
     */
    public function getOfficerPerformance(Request $request, int $institutionId, int $officerId): JsonResponse
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            $performance = $this->collectionsService->getOfficerPerformance($institutionId, $officerId, $startDate, $endDate);

            return response()->json([
                'message' => 'Officer performance retrieved successfully',
                'data' => $performance,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve officer performance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get action effectiveness analysis.
     */
    public function getActionEffectiveness(Request $request, int $institutionId): JsonResponse
    {
        try {
            $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
            $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

            $effectiveness = $this->actionService->getActionEffectiveness($institutionId, $startDate, $endDate);

            return response()->json([
                'message' => 'Action effectiveness retrieved successfully',
                'data' => $effectiveness,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve action effectiveness',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

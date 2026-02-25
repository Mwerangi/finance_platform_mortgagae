<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\UnderwritingDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UnderwritingController extends Controller
{
    /**
     * Get pending decisions for reviewer
     */
    public function getPendingReviews(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = UnderwritingDecision::where('institution_id', $user->institution_id)
            ->with(['application', 'customer', 'reviewer'])
            ->latest('created_at');

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->status);
        } else {
            // Default: show pending and under review
            $query->whereIn('decision_status', ['pending_review', 'under_review']);
        }

        // Filter by reviewer
        if ($request->boolean('my_queue')) {
            $query->where('reviewed_by', $user->id);
        }

        // Filter by high value
        if ($request->boolean('high_value_only')) {
            $query->highValue();
        }

        // Filter by expedited
        if ($request->boolean('expedited_only')) {
            $query->expedited();
        }

        $decisions = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $decisions->map(fn($d) => $this->formatDecision($d, false)),
            'meta' => [
                'current_page' => $decisions->currentPage(),
                'total' => $decisions->total(),
                'per_page' => $decisions->perPage(),
                'last_page' => $decisions->lastPage(),
            ],
        ]);
    }

    /**
     * Get pending approvals for supervisor
     */
    public function getPendingApprovals(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = UnderwritingDecision::where('institution_id', $user->institution_id)
            ->pendingApproval()
            ->with(['application', 'customer', 'reviewer', 'eligibilityAssessment'])
            ->latest('reviewed_at');

        // Filter by high value
        if ($request->boolean('high_value_only')) {
            $query->highValue();
        }

        // Filter by override required
        if ($request->boolean('override_only')) {
            $query->requiringOverride();
        }

        $decisions = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $decisions->map(fn($d) => $this->formatDecision($d, true)),
            'meta' => [
                'current_page' => $decisions->currentPage(),
                'total' => $decisions->total(),
                'per_page' => $decisions->perPage(),
                'last_page' => $decisions->lastPage(),
            ],
        ]);
    }

    /**
     * Submit underwriting decision
     */
    public function submitDecision(Request $request, Application $application): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($application->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate application has eligibility assessment
        $assessment = $application->eligibilityAssessments()
            ->where('is_stress_test', false)
            ->latest('assessed_at')
            ->first();

        if (!$assessment) {
            return response()->json([
                'message' => 'Cannot create underwriting decision. No eligibility assessment found.',
            ], 400);
        }

        $validated = $request->validate([
            'requested_amount' => 'required|numeric|min:0',
            'requested_tenure_months' => 'required|integer|min:1',
            'attached_conditions' => 'nullable|array',
            'attached_conditions.*.condition' => 'required|string',
            'attached_conditions.*.severity' => 'required|in:low,medium,high',
            'waived_conditions' => 'nullable|array',
            'reviewer_notes' => 'nullable|string|max:2000',
            'manual_risk_grade' => 'nullable|in:A,B,C,D,E',
            'risk_grade_justification' => 'nullable|string|max:1000',
            'is_expedited' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            // Check if decision already exists
            $existingDecision = $application->underwritingDecisions()->latest()->first();
            if ($existingDecision && !$existingDecision->isDeclined() && !$existingDecision->isCancelled()) {
                return response()->json([
                    'message' => 'An active underwriting decision already exists for this application.',
                ], 400);
            }

            // Create decision
            $decision = UnderwritingDecision::create([
                'application_id' => $application->id,
                'eligibility_assessment_id' => $assessment->id,
                'customer_id' => $application->customer_id,
                'institution_id' => $application->institution_id,
                'loan_product_id' => $application->loan_product_id,
                'requested_amount' => $validated['requested_amount'],
                'requested_tenure_months' => $validated['requested_tenure_months'],
                'attached_conditions' => $validated['attached_conditions'] ?? [],
                'waived_conditions' => $validated['waived_conditions'] ?? [],
                'reviewer_notes' => $validated['reviewer_notes'] ?? null,
                'manual_risk_grade' => $validated['manual_risk_grade'] ?? null,
                'risk_grade_justification' => $validated['risk_grade_justification'] ?? null,
                'is_expedited' => $validated['is_expedited'] ?? false,
                'decision_status' => 'draft',
            ]);

            // Check if requires override (based on eligibility breaches)
            if ($assessment->hasPolicyBreaches()) {
                $decision->update([
                    'requires_override' => true,
                    'override_policy_breaches' => $assessment->policy_breaches,
                ]);
            }

            // Check if high value (configurable threshold, e.g., > 100M TZS)
            $highValueThreshold = 100000000; // 100M TZS
            if ($validated['requested_amount'] > $highValueThreshold) {
                $decision->update(['is_high_value' => true]);
            }

            // Copy final calculations from assessment
            $decision->update([
                'final_monthly_installment' => $assessment->proposed_installment,
                'final_total_interest' => $assessment->total_interest,
                'final_total_repayment' => $assessment->total_repayment,
                'final_dti_ratio' => $assessment->dti_ratio,
                'final_dsr_ratio' => $assessment->dsr_ratio,
                'final_ltv_ratio' => $assessment->ltv_ratio,
            ]);

            // Submit for review
            $decision->submitForReview($user->id);

            // Update application status
            $application->markAsUnderReview($user->id);

            DB::commit();

            return response()->json([
                'message' => 'Underwriting decision submitted successfully',
                'data' => $this->formatDecision($decision->fresh(['application', 'customer', 'eligibilityAssessment']), true),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to submit decision',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start reviewing a decision
     */
    public function startReview(UnderwritingDecision $decision): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($decision->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if can be reviewed
        if (!$decision->canBeReviewedBy($user)) {
            return response()->json([
                'message' => 'Cannot review this decision. Check status and permissions.',
            ], 400);
        }

        $decision->startReview($user->id);

        return response()->json([
            'message' => 'Review started successfully',
            'data' => $this->formatDecision($decision->fresh(), true),
        ]);
    }

    /**
     * Complete review and forward for approval
     */
    public function completeReview(Request $request, UnderwritingDecision $decision): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($decision->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if under review by this user
        if (!$decision->isUnderReview() || $decision->reviewed_by !== $user->id) {
            return response()->json([
                'message' => 'Cannot complete review. Check status and assignment.',
            ], 400);
        }

        $validated = $request->validate([
            'reviewer_notes' => 'required|string|max:2000',
            'recommendation' => 'required|in:approve,decline,defer',
        ]);

        if ($validated['recommendation'] === 'decline') {
            $decision->decline($user->id, $validated['reviewer_notes']);

            return response()->json([
                'message' => 'Decision declined',
                'data' => $this->formatDecision($decision->fresh(), true),
            ]);
        }

        // Forward for approval
        $decision->submitForApproval($user->id, $validated['reviewer_notes']);

        return response()->json([
            'message' => 'Decision forwarded for supervisor approval',
            'data' => $this->formatDecision($decision->fresh(), true),
        ]);
    }

    /**
     * Approve decision (Supervisor)
     */
    public function approveDecision(Request $request, UnderwritingDecision $decision): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($decision->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if can be approved
        if (!$decision->canBeApprovedBy($user)) {
            return response()->json([
                'message' => 'Cannot approve this decision. Check status and permissions.',
            ], 400);
        }

        // Check if requires override
        if ($decision->requires_override && !$decision->override_approved) {
            return response()->json([
                'message' => 'Cannot approve. Decision requires override approval first.',
            ], 400);
        }

        $validated = $request->validate([
            'approved_amount' => 'nullable|numeric|min:0',
            'approved_tenure_months' => 'nullable|integer|min:1',
            'approved_interest_rate' => 'nullable|numeric|min:0|max:100',
            'approved_interest_method' => 'nullable|in:reducing_balance,flat_rate',
            'notes' => 'nullable|string|max:2000',
        ]);

        // Default to requested amounts if not specified
        if (!isset($validated['approved_amount'])) {
            $validated['approved_amount'] = $decision->requested_amount;
        }
        if (!isset($validated['approved_tenure_months'])) {
            $validated['approved_tenure_months'] = $decision->requested_tenure_months;
        }
        if (!isset($validated['approved_interest_rate'])) {
            $validated['approved_interest_rate'] = $decision->loanProduct->interest_rate;
        }
        if (!isset($validated['approved_interest_method'])) {
            $validated['approved_interest_method'] = $decision->loanProduct->interest_method;
        }

        $decision->approve($user->id, $validated);

        return response()->json([
            'message' => 'Decision approved successfully',
            'data' => $this->formatDecision($decision->fresh(), true),
        ]);
    }

    /**
     * Decline decision (Supervisor)
     */
    public function declineDecision(Request $request, UnderwritingDecision $decision): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($decision->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if can be approved (same permission for decline)
        if (!$decision->canBeApprovedBy($user)) {
            return response()->json([
                'message' => 'Cannot decline this decision. Check status and permissions.',
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $decision->decline($user->id, $validated['reason']);

        return response()->json([
            'message' => 'Decision declined',
            'data' => $this->formatDecision($decision->fresh(), true),
        ]);
    }

    /**
     * Request override for policy breaches
     */
    public function requestOverride(Request $request, UnderwritingDecision $decision): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($decision->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if requires override
        if (!$decision->requires_override) {
            return response()->json([
                'message' => 'This decision does not require override.',
            ], 400);
        }

        // Check if already requested
        if ($decision->override_requested) {
            return response()->json([
                'message' => 'Override has already been requested for this decision.',
            ], 400);
        }

        $validated = $request->validate([
            'justification' => 'required|string|max:2000',
            'policy_breaches' => 'required|array',
        ]);

        $decision->requestOverride(
            $user->id,
            $validated['justification'],
            $validated['policy_breaches']
        );

        return response()->json([
            'message' => 'Override request submitted successfully',
            'data' => $this->formatDecision($decision->fresh(), true),
        ]);
    }

    /**
     * Approve override request (Supervisor/Manager)
     */
    public function approveOverride(Request $request, UnderwritingDecision $decision): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($decision->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if override requested
        if (!$decision->override_requested) {
            return response()->json([
                'message' => 'No override request found for this decision.',
            ], 400);
        }

        // Check if already approved/declined
        if ($decision->override_approved || $decision->override_declined_at) {
            return response()->json([
                'message' => 'Override request has already been processed.',
            ], 400);
        }

        // Check if user has permission (supervisor or higher)
        if (!$user->hasAnyRole(['supervisor', 'institution-admin', 'provider-super-admin'])) {
            return response()->json([
                'message' => 'Insufficient permissions to approve override.',
            ], 403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $decision->approveOverride($user->id, $validated['notes'] ?? null);

        return response()->json([
            'message' => 'Override approved successfully',
            'data' => $this->formatDecision($decision->fresh(), true),
        ]);
    }

    /**
     * Decline override request
     */
    public function declineOverride(Request $request, UnderwritingDecision $decision): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($decision->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if override requested
        if (!$decision->override_requested) {
            return response()->json([
                'message' => 'No override request found for this decision.',
            ], 400);
        }

        // Check if user has permission
        if (!$user->hasAnyRole(['supervisor', 'institution-admin', 'provider-super-admin'])) {
            return response()->json([
                'message' => 'Insufficient permissions to decline override.',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:2000',
        ]);

        $decision->declineOverride($user->id, $validated['reason']);

        return response()->json([
            'message' => 'Override declined',
            'data' => $this->formatDecision($decision->fresh(), true),
        ]);
    }

    /**
     * Get decision history for an application
     */
    public function getDecisionHistory(Application $application): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($application->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $decisions = $application->underwritingDecisions()
            ->with(['reviewer', 'approver'])
            ->latest('created_at')
            ->get();

        return response()->json([
            'data' => $decisions->map(fn($d) => $this->formatDecision($d, false)),
        ]);
    }

    /**
     * Get single decision details
     */
    public function getDecision(UnderwritingDecision $decision): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($decision->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $decision->load([
            'application',
            'customer',
            'eligibilityAssessment',
            'loanProduct',
            'reviewer',
            'approver',
            'overrideRequester',
            'overrideApprover',
        ]);

        return response()->json([
            'data' => $this->formatDecision($decision, true),
        ]);
    }

    /**
     * Format decision for response
     */
    private function formatDecision(UnderwritingDecision $decision, bool $includeDetails): array
    {
        $data = [
            'id' => $decision->id,
            'decision_number' => $decision->decision_number,
            'application_id' => $decision->application_id,
            'decision_status' => $decision->decision_status,
            'status_color' => $decision->status_color,
            'final_decision' => $decision->final_decision,
            'requested_amount' => $decision->requested_amount,
            'requested_tenure_months' => $decision->requested_tenure_months,
            'approved_amount' => $decision->approved_amount,
            'approved_tenure_months' => $decision->approved_tenure_months,
            'is_high_value' => $decision->is_high_value,
            'is_expedited' => $decision->is_expedited,
            'requires_override' => $decision->requires_override,
            'override_requested' => $decision->override_requested,
            'override_approved' => $decision->override_approved,
            'created_at' => $decision->created_at,
        ];

        if ($includeDetails) {
            $data['eligibility_assessment'] = $decision->eligibilityAssessment ? [
                'id' => $decision->eligibilityAssessment->id,
                'system_decision' => $decision->eligibilityAssessment->system_decision,
                'risk_grade' => $decision->eligibilityAssessment->risk_grade,
                'final_max_loan' => $decision->eligibilityAssessment->final_max_loan,
                'policy_breaches' => $decision->eligibilityAssessment->policy_breaches,
            ] : null;

            $data['financial_details'] = [
                'approved_interest_rate' => $decision->approved_interest_rate,
                'approved_interest_method' => $decision->approved_interest_method,
                'final_monthly_installment' => $decision->final_monthly_installment,
                'final_total_interest' => $decision->final_total_interest,
                'final_total_repayment' => $decision->final_total_repayment,
                'final_dti_ratio' => $decision->final_dti_ratio,
                'final_dsr_ratio' => $decision->final_dsr_ratio,
                'final_ltv_ratio' => $decision->final_ltv_ratio,
            ];

            $data['conditions'] = [
                'attached_conditions' => $decision->attached_conditions,
                'waived_conditions' => $decision->waived_conditions,
                'condition_count' => $decision->condition_count,
            ];

            $data['override_details'] = $decision->override_requested ? [
                'justification' => $decision->override_justification,
                'policy_breaches' => $decision->override_policy_breaches,
                'requested_by' => $decision->overrideRequester?->name,
                'requested_at' => $decision->override_requested_at,
                'approved' => $decision->override_approved,
                'approved_by' => $decision->overrideApprover?->name,
                'approved_at' => $decision->override_approved_at,
                'declined_at' => $decision->override_declined_at,
                'decline_reason' => $decision->override_decline_reason,
            ] : null;

            $data['workflow'] = [
                'workflow_stage' => $decision->workflow_stage,
                'approval_level' => $decision->approval_level,
                'reviewed_by' => $decision->reviewer?->name,
                'reviewed_at' => $decision->reviewed_at,
                'approved_by' => $decision->approver?->name,
                'approved_at' => $decision->approved_at,
                'declined_at' => $decision->declined_at,
                'reviewer_notes' => $decision->reviewer_notes,
                'approver_notes' => $decision->approver_notes,
                'approval_history' => $decision->approval_history,
            ];

            $data['variance'] = [
                'has_amount_variance' => $decision->hasAmountVariance(),
                'variance_percentage' => $decision->variance_percentage,
            ];

            if ($decision->relationLoaded('customer')) {
                $data['customer'] = [
                    'id' => $decision->customer->id,
                    'full_name' => $decision->customer->full_name,
                    'customer_number' => $decision->customer->customer_number,
                ];
            }

            if ($decision->relationLoaded('application')) {
                $data['application'] = [
                    'id' => $decision->application->id,
                    'application_number' => $decision->application->application_number,
                    'status' => $decision->application->status->value,
                ];
            }
        }

        return $data;
    }
}

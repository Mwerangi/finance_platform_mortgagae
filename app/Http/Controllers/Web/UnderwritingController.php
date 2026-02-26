<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\UnderwritingDecision;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnderwritingController extends Controller
{
    /**
     * Display pending reviews (for credit officers)
     */
    public function pendingReviews(Request $request)
    {
        $user = $request->user();

        $query = UnderwritingDecision::where('institution_id', $user->institution_id)
            ->with(['application.customer', 'application.loanProduct', 'reviewer'])
            ->whereIn('decision_status', ['pending_review', 'under_review'])
            ->latest('created_at');

        // Filter by high value
        if ($request->boolean('high_value_only')) {
            $query->where('is_high_value', true);
        }

        // Filter by expedited
        if ($request->boolean('expedited_only')) {
            $query->where('is_expedited', true);
        }

        // Filter by my queue
        if ($request->boolean('my_queue')) {
            $query->where('reviewed_by', $user->id);
        }

        $decisions = $query->paginate(15)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => UnderwritingDecision::where('institution_id', $user->institution_id)
                ->whereIn('decision_status', ['pending_review', 'under_review'])
                ->count(),
            'my_queue' => UnderwritingDecision::where('institution_id', $user->institution_id)
                ->where('reviewed_by', $user->id)
                ->where('decision_status', 'under_review')
                ->count(),
            'high_value' => UnderwritingDecision::where('institution_id', $user->institution_id)
                ->whereIn('decision_status', ['pending_review', 'under_review'])
                ->where('is_high_value', true)
                ->count(),
            'expedited' => UnderwritingDecision::where('institution_id', $user->institution_id)
                ->whereIn('decision_status', ['pending_review', 'under_review'])
                ->where('is_expedited', true)
                ->count(),
        ];

        return Inertia::render('Underwriting/PendingReviews', [
            'decisions' => $decisions,
            'stats' => $stats,
            'filters' => $request->only(['high_value_only', 'expedited_only', 'my_queue']),
        ]);
    }

    /**
     * Display pending approvals (for supervisors)
     */
    public function pendingApprovals(Request $request)
    {
        $user = $request->user();

        $query = UnderwritingDecision::where('institution_id', $user->institution_id)
            ->with(['application.customer', 'application.loanProduct', 'reviewer', 'eligibilityAssessment'])
            ->where('decision_status', 'pending_approval')
            ->latest('reviewed_at');

        // Filter by high value
        if ($request->boolean('high_value_only')) {
            $query->where('is_high_value', true);
        }

        // Filter by override required
        if ($request->boolean('override_only')) {
            $query->where('requires_override', true);
        }

        $decisions = $query->paginate(15)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => UnderwritingDecision::where('institution_id', $user->institution_id)
                ->where('decision_status', 'pending_approval')
                ->count(),
            'high_value' => UnderwritingDecision::where('institution_id', $user->institution_id)
                ->where('decision_status', 'pending_approval')
                ->where('is_high_value', true)
                ->count(),
            'requires_override' => UnderwritingDecision::where('institution_id', $user->institution_id)
                ->where('decision_status', 'pending_approval')
                ->where('requires_override', true)
                ->count(),
        ];

        return Inertia::render('Underwriting/PendingApprovals', [
            'decisions' => $decisions,
            'stats' => $stats,
            'filters' => $request->only(['high_value_only', 'override_only']),
        ]);
    }

    /**
     * Show review form for credit officer
     */
    public function showReview(Request $request, UnderwritingDecision $decision)
    {
        $user = $request->user();

        // Validate access
        if ($decision->institution_id !== $user->institution_id) {
            abort(403);
        }

        $decision->load([
            'application.customer',
            'application.loanProduct',
            'application.statementAnalytics' => function($query) {
                $query->latest();
            },
            'eligibilityAssessment',
            'reviewer',
        ]);

        // Check if user can review
        $canReview = $request->user()->hasAnyRole([
            'provider-super-admin',
            'institution-admin',
            'credit-officer',
            'credit-manager',
        ]);

        return Inertia::render('Underwriting/ReviewDecision', [
            'decision' => $decision,
            'canReview' => $canReview,
        ]);
    }

    /**
     * Show approval form for supervisor
     */
    public function showApproval(Request $request, UnderwritingDecision $decision)
    {
        $user = $request->user();

        // Validate access
        if ($decision->institution_id !== $user->institution_id) {
            abort(403);
        }

        $decision->load([
            'application.customer',
            'application.loanProduct',
            'application.statementAnalytics' => function($query) {
                $query->latest();
            },
            'eligibilityAssessment',
            'reviewer',
            'approver',
        ]);

        // Check if user can approve
        $canApprove = $request->user()->hasAnyRole([
            'provider-super-admin',
            'institution-admin',
            'credit-manager',
            'supervisor',
        ]);

        return Inertia::render('Underwriting/ApprovalDecision', [
            'decision' => $decision,
            'canApprove' => $canApprove,
        ]);
    }
}

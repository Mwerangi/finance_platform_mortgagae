<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CollectionsQueue;
use App\Services\CollectionsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CollectionsController extends Controller
{
    protected CollectionsService $collectionsService;

    public function __construct(CollectionsService $collectionsService)
    {
        $this->collectionsService = $collectionsService;
    }

    /**
     * Display collections queue
     */
    public function index(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        // Get filters from request
        $filters = $request->only([
            'status',
            'priority_level',
            'delinquency_bucket',
            'assigned_to',
            'search'
        ]);

        // Build query
        $query = CollectionsQueue::where('institution_id', $institutionId)
            ->with(['loan.customer', 'loan.loanProduct', 'assignedTo', 'latestAction']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority_level')) {
            $query->where('priority_level', $request->priority_level);
        }

        if ($request->filled('delinquency_bucket')) {
            $query->where('delinquency_bucket', $request->delinquency_bucket);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('loan', function ($q) use ($search) {
                $q->where('loan_account_number', 'like', "%{$search}%");
            })->orWhereHas('customer', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }

        // Sort by priority
        $queue = $query->orderByPriority()->paginate(20)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => CollectionsQueue::where('institution_id', $institutionId)
                ->whereIn('status', ['pending', 'assigned', 'in_progress'])->count(),
            'critical' => CollectionsQueue::where('institution_id', $institutionId)
                ->whereIn('status', ['pending', 'assigned', 'in_progress'])
                ->where('priority_level', 'critical')->count(),
            'high' => CollectionsQueue::where('institution_id', $institutionId)
                ->whereIn('status', ['pending', 'assigned', 'in_progress'])
                ->where('priority_level', 'high')->count(),
            'total_arrears' => CollectionsQueue::where('institution_id', $institutionId)
                ->whereIn('status', ['pending', 'assigned', 'in_progress'])
                ->sum('total_arrears'),
            'with_ptp' => CollectionsQueue::where('institution_id', $institutionId)
                ->whereIn('status', ['pending', 'assigned', 'in_progress'])
                ->where('has_active_ptp', true)->count(),
        ];

        // Get officers for assignment filter
        $officers = \App\Models\User::where('institution_id', $institutionId)
            ->whereHas('roles', function ($q) {
                $q->where('name', 'collections-officer');
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Collections/Index', [
            'queue' => $queue,
            'stats' => $stats,
            'officers' => $officers,
            'filters' => $filters,
        ]);
    }

    /**
     * Generate collections queue
     */
    public function generateQueue(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        try {
            $result = $this->collectionsService->generateQueue($institutionId);
            
            return back()->with('success', "Collections queue generated: {$result['created']} created, {$result['updated']} updated");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate collections queue: ' . $e->getMessage());
        }
    }
}

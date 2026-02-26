<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Customer;
use App\Models\LoanProduct;
use App\Enums\ApplicationStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    /**
     * Display a listing of applications
     */
    public function index(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        $query = Application::with(['customer', 'loanProduct'])
            ->where('institution_id', $institutionId);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('loan_product_id')) {
            $query->where('loan_product_id', $request->loan_product_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('application_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%");
                  });
            });
        }

        // Paginate
        $applications = $query->latest()->paginate(15)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => Application::where('institution_id', $institutionId)->count(),
            'pending' => Application::where('institution_id', $institutionId)
                ->whereIn('status', [ApplicationStatus::SUBMITTED, ApplicationStatus::UNDER_REVIEW])
                ->count(),
            'approved' => Application::where('institution_id', $institutionId)
                ->where('status', ApplicationStatus::APPROVED)
                ->count(),
            'disbursed' => Application::where('institution_id', $institutionId)
                ->where('status', ApplicationStatus::DISBURSED)
                ->count(),
        ];

        return Inertia::render('Applications/Index', [
            'applications' => $applications,
            'stats' => $stats,
            'loanProducts' => LoanProduct::where('institution_id', $institutionId)
                ->where('status', 'active')
                ->select('id', 'name')
                ->get(),
            'filters' => $request->only(['status', 'loan_product_id', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new application
     */
    public function create()
    {
        $institutionId = auth()->user()->institution_id;

        return Inertia::render('Applications/Create', [
            'customers' => Customer::where('institution_id', $institutionId)
                ->where('status', 'active')
                ->where('kyc_verified', true)
                ->select('id', 'first_name', 'middle_name', 'last_name', 'customer_code')
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'customer_code' => $customer->customer_code,
                        'full_name' => $customer->full_name,
                    ];
                }),
            'loanProducts' => LoanProduct::where('institution_id', $institutionId)
                ->where('status', 'active')
                ->get(),
        ]);
    }

    /**
     * Store a newly created application
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'loan_product_id' => 'required|exists:loan_products,id',
            'requested_amount' => 'required|numeric|min:0',
            'requested_tenure_months' => 'required|integer|min:1',
            'property_type' => 'nullable|string|max:255',
            'property_value' => 'nullable|numeric|min:0',
            'property_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'save_as_draft' => 'boolean'
        ]);

        $validated['institution_id'] = $request->user()->institution_id;
        $validated['created_by'] = $request->user()->id;
        
        // Set status based on save_as_draft flag
        if ($request->save_as_draft) {
            $validated['status'] = ApplicationStatus::DRAFT;
        } else {
            $validated['status'] = ApplicationStatus::SUBMITTED;
            $validated['submitted_at'] = now();
        }

        unset($validated['save_as_draft']);

        $application = Application::create($validated);

        return redirect()->route('applications.show', $application)
            ->with('success', 'Application created successfully');
    }

    /**
     * Display the specified application
     */
    public function show(Request $request, Application $application)
    {
        $application->load([
            'customer',
            'loanProduct',
            'creator',
            'reviewer',
            'approver',
            'latestUnderwritingDecision',
            'latestLoan',
            'eligibilityAssessments' => function($query) {
                $query->latest()->with(['assessor', 'statementAnalytics']);
            }
        ]);

        // Get the latest eligibility assessment
        $latestEligibility = $application->eligibilityAssessments->first();

        // Check if user can approve/reject applications
        $canApprove = $request->user()->hasAnyRole([
            'provider-super-admin',
            'institution-admin',
            'credit-manager',
            'supervisor'
        ]);

        return Inertia::render('Applications/Show', [
            'application' => $application,
            'latestUnderwriting' => $application->latestUnderwritingDecision,
            'latestEligibility' => $latestEligibility,
            'canApprove' => $canApprove
        ]);
    }

    /**
     * Show the form for editing the specified application
     */
    public function edit(Application $application)
    {
        // Only allow editing draft applications
        if ($application->status !== ApplicationStatus::DRAFT) {
            return redirect()->route('applications.show', $application)
                ->with('error', 'Only draft applications can be edited');
        }

        $application->load(['customer', 'loanProduct']);
        $institutionId = $application->institution_id;

        return Inertia::render('Applications/Edit', [
            'application' => $application,
            'customers' => Customer::where('institution_id', $institutionId)
                ->where('status', 'active')
                ->where('kyc_verified', true)
                ->select('id', 'first_name', 'middle_name', 'last_name', 'customer_code')
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'customer_code' => $customer->customer_code,
                        'full_name' => $customer->full_name,
                    ];
                }),
            'loanProducts' => LoanProduct::where('institution_id', $institutionId)
                ->where('status', 'active')
                ->get(),
        ]);
    }

    /**
     * Update the specified application
     */
    public function update(Request $request, Application $application)
    {
        // Only allow updating draft applications
        if ($application->status !== ApplicationStatus::DRAFT) {
            return back()->with('error', 'Only draft applications can be edited');
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'loan_product_id' => 'required|exists:loan_products,id',
            'requested_amount' => 'required|numeric|min:0',
            'requested_tenure_months' => 'required|integer|min:1',
            'property_type' => 'nullable|string|max:255',
            'property_value' => 'nullable|numeric|min:0',
            'property_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'save_as_draft' => 'boolean'
        ]);

        // Set status based on save_as_draft flag
        if (!$request->save_as_draft) {
            // Check if customer KYC is verified before allowing submission
            $customer = \App\Models\Customer::find($validated['customer_id']);
            if (!$customer->kyc_verified) {
                return back()->with('error', 'Cannot submit application: Customer KYC verification is required. Please complete KYC verification first.');
            }
            
            $validated['status'] = ApplicationStatus::SUBMITTED;
            $validated['submitted_at'] = now();
        }

        unset($validated['save_as_draft']);

        $application->update($validated);

        return redirect()->route('applications.show', $application)
            ->with('success', 'Application updated successfully');
    }

    /**
     * Remove the specified application
     */
    public function destroy(Application $application)
    {
        // Only allow deletion of draft applications
        if ($application->status !== ApplicationStatus::DRAFT) {
            return back()->with('error', 'Only draft applications can be deleted');
        }

        $application->delete();

        return redirect()->route('applications.index')
            ->with('success', 'Application deleted successfully');
    }

    /**
     * Start reviewing an application
     */
    public function startReview(Application $application)
    {
        if ($application->status !== ApplicationStatus::SUBMITTED) {
            return back()->with('error', 'Application must be submitted before review');
        }

        $application->markAsUnderReview(auth()->id());

        return back()->with('success', 'Application is now under review');
    }

    /**
     * Approve an application
     */
    public function approve(Application $application)
    {
        // Check authorization
        $user = auth()->user();
        if (!$user->hasAnyRole(['provider-super-admin', 'institution-admin', 'credit-manager', 'supervisor'])) {
            abort(403, 'You do not have permission to approve applications.');
        }

        if ($application->status !== ApplicationStatus::UNDER_REVIEW) {
            return back()->with('error', 'Application must be under review to approve');
        }

        $application->approve($user->id);

        return back()->with('success', 'Application approved successfully. You can now proceed to disburse the loan.');
    }

    /**
     * Reject an application
     */
    public function reject(Request $request, Application $application)
    {
        // Check authorization
        $user = auth()->user();
        if (!$user->hasAnyRole(['provider-super-admin', 'institution-admin', 'credit-manager', 'supervisor'])) {
            abort(403, 'You do not have permission to reject applications.');
        }

        $request->validate([
            'notes' => 'required|string|min:10|max:1000'
        ], [
            'notes.required' => 'Please provide a reason for rejection',
            'notes.min' => 'Rejection reason must be at least 10 characters',
        ]);

        if ($application->status !== ApplicationStatus::UNDER_REVIEW) {
            return back()->with('error', 'Application must be under review to reject');
        }

        $application->reject($user->id, $request->notes);

        return back()->with('success', 'Application rejected successfully.');
    }
}

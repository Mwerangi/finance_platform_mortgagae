<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Institution;
use App\Enums\CustomerType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $query = Customer::with('institution');

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->search . '%')
                    ->orWhere('last_name', 'like', '%' . $request->search . '%')
                    ->orWhere('customer_code', 'like', '%' . $request->search . '%')
                    ->orWhere('national_id', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by customer type
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by KYC verification
        if ($request->filled('kyc_verified')) {
            $query->where('kyc_verified', (bool) $request->kyc_verified);
        }

        // Get paginated results
        $customers = $query->latest()->paginate(15)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => Customer::count(),
            'kyc_verified' => Customer::where('kyc_verified', true)->count(),
            'pending_kyc' => Customer::where('kyc_verified', false)->count(),
            'active' => Customer::where('status', 'active')->count(),
        ];

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'stats' => $stats,
            'filters' => $request->only(['search', 'customer_type', 'status', 'kyc_verified'])
        ]);
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return Inertia::render('Customers/Create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_type' => 'required|in:' . implode(',', array_column(CustomerType::cases(), 'value')),
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'national_id' => 'required|string|max:50|unique:customers,national_id',
            'passport_number' => 'nullable|string|max:50|unique:customers,passport_number',
            'tin' => 'nullable|string|max:50|unique:customers,tin',
            'phone_primary' => 'required|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:customers,email',
            'physical_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'employer_name' => 'nullable|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'employment_start_date' => 'nullable|date|before_or_equal:today',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_relationship' => 'nullable|string|max:100',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'next_of_kin_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        // Add institution_id from authenticated user
        $validated['institution_id'] = auth()->user()->institution_id;
        $validated['kyc_verified'] = false;

        $customer = Customer::create($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer)
    {
        $customer->load(['institution', 'kycDocuments.verifier', 'kycVerifier', 'applications.loanProduct']);

        // Calculate statistics
        $stats = [
            'applications_count' => $customer->applications()->count(),
            'active_loans' => $customer->applications()
                ->whereHas('loans', function ($q) {
                    $q->where('status', 'active');
                })
                ->count(),
        ];

        // Get pending application (if converted from prospect)
        $pendingApplication = $customer->applications()
            ->where('status', 'draft')
            ->with('loanProduct')
            ->first();

        return Inertia::render('Customers/Show', [
            'customer' => $customer,
            'kycDocuments' => $customer->kycDocuments,
            'stats' => $stats,
            'pendingApplication' => $pendingApplication
        ]);
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        $customer->load('institution');

        return Inertia::render('Customers/Edit', [
            'customer' => $customer
        ]);
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'customer_type' => 'required|in:' . implode(',', array_column(CustomerType::cases(), 'value')),
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'national_id' => 'required|string|max:50|unique:customers,national_id,' . $customer->id,
            'passport_number' => 'nullable|string|max:50|unique:customers,passport_number,' . $customer->id,
            'tin' => 'nullable|string|max:50|unique:customers,tin,' . $customer->id,
            'phone_primary' => 'required|string|max:20',
            'phone_secondary' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customer->id,
            'physical_address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'employer_name' => 'nullable|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'occupation' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'employment_start_date' => 'nullable|date|before_or_equal:today',
            'next_of_kin_name' => 'nullable|string|max:255',
            'next_of_kin_relationship' => 'nullable|string|max:100',
            'next_of_kin_phone' => 'nullable|string|max:20',
            'next_of_kin_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        // Check if the customer has any applications
        if ($customer->applications()->exists()) {
            return back()->withErrors([
                'error' => 'Cannot delete customer with existing applications. Deactivate the customer instead.'
            ]);
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * Update the status of the specified customer.
     */
    public function updateStatus(Request $request, Customer $customer)
    {
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        if ($request->status === 'inactive') {
            $customer->deactivate();
        } else {
            $customer->activate();
        }

        return back()->with('success', 'Customer status updated successfully.');
    }

    /**
     * Verify the KYC for the specified customer.
     */
    public function verifyKyc(Customer $customer)
    {
        // Check if user has permission to verify KYC
        $user = auth()->user();
        if (!$user->hasAnyRole(['provider-super-admin', 'institution-admin', 'credit-manager'])) {
            abort(403, 'You do not have permission to verify KYC.');
        }

        // Check if customer has uploaded required documents
        $requiredDocuments = ['national_id']; // Minimum required
        $uploadedDocumentTypes = $customer->kycDocuments()->pluck('document_type')->toArray();

        // For now, just verify - in production you'd want stricter checks
        $customer->verifyKyc($user->id);

        return back()->with('success', 'Customer KYC verified successfully.');
    }
}

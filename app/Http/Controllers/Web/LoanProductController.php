<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LoanProduct;
use App\Models\Institution;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Enums\LoanProductStatus;
use App\Enums\InterestModel;

class LoanProductController extends Controller
{
    /**
     * Display a listing of loan products.
     */
    public function index(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        $query = LoanProduct::with('institution')
            ->where('institution_id', $institutionId);

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('product_code', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by interest model
        if ($request->filled('interest_model')) {
            $query->where('interest_model', $request->interest_model);
        }

        // Get paginated results
        $products = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('LoanProducts/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'status', 'interest_model'])
        ]);
    }

    /**
     * Show the form for creating a new loan product.
     */
    public function create()
    {
        return Inertia::render('LoanProducts/Create', [
            'institutions' => Institution::select('id', 'name')->get()
        ]);
    }

    /**
     * Store a newly created loan product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:loan_products,code',
            'description' => 'nullable|string',
            'interest_model' => 'required|in:' . implode(',', array_column(InterestModel::cases(), 'value')),
            'annual_interest_rate' => 'required|numeric|min:0|max:100',
            'rate_type' => 'nullable|string|max:50',
            'min_tenure_months' => 'required|integer|min:1',
            'max_tenure_months' => 'required|integer|min:1|gte:min_tenure_months',
            'min_loan_amount' => 'required|numeric|min:0',
            'max_loan_amount' => 'required|numeric|min:0|gte:min_loan_amount',
            'max_ltv_percentage' => 'nullable|numeric|min:0|max:100',
            'max_dsr_salary_percentage' => 'nullable|numeric|min:0|max:100',
            'max_dti_percentage' => 'nullable|numeric|min:0|max:100',
            'business_safety_factor' => 'nullable|numeric|min:0|max:1',
            'max_dsr_business_percentage' => 'nullable|numeric|min:0|max:100',
            'fees' => 'nullable|array',
            'fees.*.type' => 'required_with:fees|string',
            'fees.*.amount' => 'required_with:fees|numeric|min:0',
            'fees.*.frequency' => 'nullable|string',
            'penalties' => 'nullable|array',
            'penalties.*.type' => 'required_with:penalties|string',
            'penalties.*.amount' => 'required_with:penalties|numeric|min:0',
            'penalties.*.trigger' => 'nullable|string',
            'credit_policy' => 'nullable|array'
        ]);

        // Get the authenticated user's institution
        $validated['institution_id'] = auth()->user()->institution_id;
        $validated['status'] = LoanProductStatus::INACTIVE->value;

        $product = LoanProduct::create($validated);

        return redirect()->route('loan-products.index')
            ->with('success', 'Loan product created successfully.');
    }

    /**
     * Display the specified loan product.
     */
    public function show(LoanProduct $loanProduct)
    {
        $loanProduct->load('institution');

        // Calculate usage statistics
        $stats = [
            'total_applications' => $loanProduct->applications()->count(),
            'active_loans' => $loanProduct->loans()->where('status', 'active')->count(),
            'total_disbursed' => $loanProduct->loans()
                ->where('status', 'disbursed')
                ->sum('approved_amount')
        ];

        return Inertia::render('LoanProducts/Show', [
            'product' => $loanProduct,
            'stats' => $stats
        ]);
    }

    /**
     * Show the form for editing the specified loan product.
     */
    public function edit(LoanProduct $loanProduct)
    {
        $loanProduct->load('institution');

        return Inertia::render('LoanProducts/Edit', [
            'product' => $loanProduct,
            'institutions' => Institution::select('id', 'name')->get()
        ]);
    }

    /**
     * Update the specified loan product in storage.
     */
    public function update(Request $request, LoanProduct $loanProduct)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:loan_products,code,' . $loanProduct->id,
            'description' => 'nullable|string',
            'interest_model' => 'required|in:' . implode(',', array_column(InterestModel::cases(), 'value')),
            'annual_interest_rate' => 'required|numeric|min:0|max:100',
            'rate_type' => 'nullable|string|max:50',
            'min_tenure_months' => 'required|integer|min:1',
            'max_tenure_months' => 'required|integer|min:1|gte:min_tenure_months',
            'min_loan_amount' => 'required|numeric|min:0',
            'max_loan_amount' => 'required|numeric|min:0|gte:min_loan_amount',
            'max_ltv_percentage' => 'nullable|numeric|min:0|max:100',
            'max_dsr_salary_percentage' => 'nullable|numeric|min:0|max:100',
            'max_dti_percentage' => 'nullable|numeric|min:0|max:100',
            'business_safety_factor' => 'nullable|numeric|min:0|max:1',
            'max_dsr_business_percentage' => 'nullable|numeric|min:0|max:100',
            'fees' => 'nullable|array',
            'fees.*.type' => 'required_with:fees|string',
            'fees.*.amount' => 'required_with:fees|numeric|min:0',
            'fees.*.frequency' => 'nullable|string',
            'penalties' => 'nullable|array',
            'penalties.*.type' => 'required_with:penalties|string',
            'penalties.*.amount' => 'required_with:penalties|numeric|min:0',
            'penalties.*.trigger' => 'nullable|string',
            'credit_policy' => 'nullable|array'
        ]);

        $loanProduct->update($validated);

        return redirect()->route('loan-products.index')
            ->with('success', 'Loan product updated successfully.');
    }

    /**
     * Remove the specified loan product from storage.
     */
    public function destroy(LoanProduct $loanProduct)
    {
        // Check if the product has any applications
        if ($loanProduct->applications()->exists()) {
            return back()->withErrors([
                'error' => 'Cannot delete product with existing applications. Deactivate it instead.'
            ]);
        }

        $loanProduct->delete();

        return redirect()->route('loan-products.index')
            ->with('success', 'Loan product deleted successfully.');
    }

    /**
     * Update the status of the specified loan product.
     */
    public function updateStatus(Request $request, LoanProduct $loanProduct)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', array_column(LoanProductStatus::cases(), 'value'))
        ]);

        $loanProduct->update(['status' => $request->status]);

        return back()->with('success', 'Product status updated successfully.');
    }

    /**
     * Activate the specified loan product.
     */
    public function activate(LoanProduct $loanProduct)
    {
        $loanProduct->activate();

        return back()->with('success', 'Product activated successfully.');
    }

    /**
     * Deactivate the specified loan product.
     */
    public function deactivate(LoanProduct $loanProduct)
    {
        $loanProduct->deactivate();

        return back()->with('success', 'Product deactivated successfully.');
    }
}

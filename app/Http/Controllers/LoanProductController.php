<?php

namespace App\Http\Controllers;

use App\Enums\InterestModel;
use App\Enums\LoanProductStatus;
use App\Models\LoanProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class LoanProductController extends Controller
{
    /**
     * Display a listing of loan products.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $status = $request->input('status');
        $interestModel = $request->input('interest_model');
        
        $user = $request->user();
        
        // Provider Super Admin can see all institutions, others see only their own
        $query = LoanProduct::with('institution');
        
        if (!$user->hasRole('provider-super-admin')) {
            $query->where('institution_id', $user->institution_id);
        } elseif ($request->has('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($interestModel) {
            $query->where('interest_model', $interestModel);
        }

        $products = $query->latest()->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created loan product.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'institution_id' => ['sometimes', 'exists:institutions,id'],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'unique:loan_products,code'],
            'description' => ['nullable', 'string'],
            'interest_model' => ['required', new Enum(InterestModel::class)],
            'annual_interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'rate_type' => ['sometimes', 'string'],
            'min_tenure_months' => ['required', 'integer', 'min:1'],
            'max_tenure_months' => ['required', 'integer', 'min:1', 'gte:min_tenure_months'],
            'min_loan_amount' => ['required', 'numeric', 'min:0'],
            'max_loan_amount' => ['required', 'numeric', 'min:0', 'gte:min_loan_amount'],
            'max_ltv_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_dsr_salary_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_dti_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'business_safety_factor' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'max_dsr_business_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fees' => ['nullable', 'array'],
            'penalties' => ['nullable', 'array'],
            'credit_policy' => ['nullable', 'array'],
            'status' => ['sometimes', new Enum(LoanProductStatus::class)],
        ]);

        // Set institution_id based on user role
        if (!isset($validated['institution_id'])) {
            if ($user->hasRole('provider-super-admin')) {
                return response()->json([
                    'message' => 'Provider Super Admin must specify institution_id.',
                ], 422);
            }
            $validated['institution_id'] = $user->institution_id;
        }

        $product = LoanProduct::create($validated);

        if ($product->status === LoanProductStatus::ACTIVE) {
            $product->update(['activated_at' => now()]);
        }

        $product->load('institution');

        return response()->json([
            'message' => 'Loan product created successfully.',
            'loan_product' => $product,
        ], 201);
    }

    /**
     * Display the specified loan product.
     */
    public function show(LoanProduct $loanProduct): JsonResponse
    {
        $loanProduct->load('institution');

        return response()->json([
            'loan_product' => $loanProduct,
            'fees' => $loanProduct->getFeesConfig(),
            'penalties' => $loanProduct->getPenaltiesConfig(),
            'credit_policy' => $loanProduct->getCreditPolicyConfig(),
        ]);
    }

    /**
     * Update the specified loan product.
     */
    public function update(Request $request, LoanProduct $loanProduct): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'code' => ['sometimes', 'string', Rule::unique('loan_products')->ignore($loanProduct->id)],
            'description' => ['nullable', 'string'],
            'interest_model' => ['sometimes', new Enum(InterestModel::class)],
            'annual_interest_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'rate_type' => ['sometimes', 'string'],
            'min_tenure_months' => ['sometimes', 'integer', 'min:1'],
            'max_tenure_months' => ['sometimes', 'integer', 'min:1'],
            'min_loan_amount' => ['sometimes', 'numeric', 'min:0'],
            'max_loan_amount' => ['sometimes', 'numeric', 'min:0'],
            'max_ltv_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_dsr_salary_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_dti_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'business_safety_factor' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'max_dsr_business_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fees' => ['nullable', 'array'],
            'penalties' => ['nullable', 'array'],
            'credit_policy' => ['nullable', 'array'],
            'status' => ['sometimes', new Enum(LoanProductStatus::class)],
        ]);

        $loanProduct->update($validated);
        $loanProduct->load('institution');

        return response()->json([
            'message' => 'Loan product updated successfully.',
            'loan_product' => $loanProduct,
        ]);
    }

    /**
     * Remove the specified loan product.
     */
    public function destroy(LoanProduct $loanProduct): JsonResponse
    {
        $loanProduct->delete();

        return response()->json([
            'message' => 'Loan product deleted successfully.',
        ]);
    }

    /**
     * Toggle product status (activate/deactivate).
     */
    public function toggleStatus(LoanProduct $loanProduct): JsonResponse
    {
        if ($loanProduct->isActive()) {
            $loanProduct->deactivate();
            $message = 'Loan product deactivated successfully.';
        } else {
            $loanProduct->activate();
            $message = 'Loan product activated successfully.';
        }

        return response()->json([
            'message' => $message,
            'loan_product' => $loanProduct,
        ]);
    }

    /**
     * Archive a loan product.
     */
    public function archive(LoanProduct $loanProduct): JsonResponse
    {
        $loanProduct->archive();

        return response()->json([
            'message' => 'Loan product archived successfully.',
            'loan_product' => $loanProduct,
        ]);
    }

    /**
     * Duplicate a loan product.
     */
    public function duplicate(LoanProduct $loanProduct): JsonResponse
    {
        $newProduct = $loanProduct->replicate();
        $newProduct->name = $loanProduct->name . ' (Copy)';
        $newProduct->code = $loanProduct->code . '-COPY-' . time();
        $newProduct->status = LoanProductStatus::DRAFT;
        $newProduct->activated_at = null;
        $newProduct->deactivated_at = null;
        $newProduct->save();

        $newProduct->load('institution');

        return response()->json([
            'message' => 'Loan product duplicated successfully.',
            'loan_product' => $newProduct,
        ], 201);
    }

    /**
     * Calculate installment for given parameters.
     */
    public function calculateInstallment(Request $request, LoanProduct $loanProduct): JsonResponse
    {
        $validated = $request->validate([
            'principal' => ['required', 'numeric', 'min:0'],
            'tenure_months' => ['required', 'integer', 'min:1'],
        ]);

        $principal = $validated['principal'];
        $tenureMonths = $validated['tenure_months'];

        // Validate against product limits
        if (!$loanProduct->isValidLoanAmount($principal)) {
            return response()->json([
                'message' => 'Loan amount is outside product limits.',
                'min_amount' => $loanProduct->min_loan_amount,
                'max_amount' => $loanProduct->max_loan_amount,
            ], 422);
        }

        if (!$loanProduct->isValidTenure($tenureMonths)) {
            return response()->json([
                'message' => 'Tenure is outside product limits.',
                'min_tenure' => $loanProduct->min_tenure_months,
                'max_tenure' => $loanProduct->max_tenure_months,
            ], 422);
        }

        $monthlyInstallment = $loanProduct->calculateInstallment($principal, $tenureMonths);
        $totalInterest = $loanProduct->calculateTotalInterest($principal, $tenureMonths);
        $totalPayment = $principal + $totalInterest;

        return response()->json([
            'calculations' => [
                'principal' => $principal,
                'tenure_months' => $tenureMonths,
                'interest_model' => $loanProduct->interest_model->value,
                'annual_interest_rate' => $loanProduct->annual_interest_rate,
                'monthly_installment' => $monthlyInstallment,
                'total_interest' => $totalInterest,
                'total_payment' => $totalPayment,
                'monthly_interest_rate' => round($loanProduct->getMonthlyInterestRate() * 100, 4),
            ],
        ]);
    }
}

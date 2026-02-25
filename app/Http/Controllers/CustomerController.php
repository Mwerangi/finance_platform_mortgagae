<?php

namespace App\Http\Controllers;

use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $status = $request->input('status');
        $customerType = $request->input('customer_type');
        $kycVerified = $request->input('kyc_verified');
        
        $user = $request->user();
        
        // Scope to institution
        $query = Customer::with('institution');
        
        if (!$user->hasRole('provider-super-admin')) {
            $query->where('institution_id', $user->institution_id);
        } elseif ($request->has('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('phone_primary', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($customerType) {
            $query->where('customer_type', $customerType);
        }

        if ($kycVerified !== null) {
            $query->where('kyc_verified', filter_var($kycVerified, FILTER_VALIDATE_BOOLEAN));
        }

        $customers = $query->latest()->paginate($perPage);

        return response()->json($customers);
    }

    /**
     * Store a newly created customer.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'institution_id' => ['sometimes', 'exists:institutions,id'],
            'customer_type' => ['required', new Enum(CustomerType::class)],
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'national_id' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:255'],
            'passport_number' => ['nullable', 'string', 'max:255'],
            'phone_primary' => ['required', 'string', 'max:255'],
           'phone_secondary' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'physical_address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'employment_start_date' => ['nullable', 'date'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_relationship' => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:255'],
            'next_of_kin_address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended', 'blacklisted'])],
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

        $customer = Customer::create($validated);
        $customer->load('institution');

        return response()->json([
            'message' => 'Customer created successfully.',
            'customer' => $customer,
        ], 201);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['institution', 'kycDocuments', 'kycVerifier']);

        return response()->json([
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'customer_type' => ['sometimes', new Enum(CustomerType::class)],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'date_of_birth' => ['sometimes', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'national_id' => ['nullable', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:255'],
            'passport_number' => ['nullable', 'string', 'max:255'],
            'phone_primary' => ['sometimes', 'string', 'max:255'],
            'phone_secondary' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'physical_address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'employer_name' => ['nullable', 'string', 'max:255'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:255'],
            'employment_start_date' => ['nullable', 'date'],
            'next_of_kin_name' => ['nullable', 'string', 'max:255'],
            'next_of_kin_relationship' => ['nullable', 'string', 'max:255'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:255'],
            'next_of_kin_address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'suspended', 'blacklisted'])],
        ]);

        $customer->update($validated);
        $customer->load('institution');

        return response()->json([
            'message' => 'Customer updated successfully.',
            'customer' => $customer,
        ]);
    }

    /**
     * Remove the specified customer.
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully.',
        ]);
    }

    /**
     * Verify customer KYC.
     */
    public function verifyKyc(Customer $customer, Request $request): JsonResponse
    {
        $customer->verifyKyc($request->user()->id);

        return response()->json([
            'message' => 'Customer KYC verified successfully.',
            'customer' => $customer->load('kycVerifier'),
        ]);
    }

    /**
     * Toggle customer status.
     */
    public function toggleStatus(Customer $customer): JsonResponse
    {
        if ($customer->isActive()) {
            $customer->deactivate();
            $message = 'Customer deactivated successfully.';
        } else {
            $customer->activate();
            $message = 'Customer activated successfully.';
        }

        return response()->json([
            'message' => $message,
            'customer' => $customer,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Loan;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    protected LoanService $loanService;

    public function __construct(LoanService $loanService)
    {
        $this->loanService = $loanService;
    }

    /**
     * Get list of loans
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Loan::where('institution_id', $user->institution_id)
            ->with(['customer', 'loanProduct']);

        // Filter by status
        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        // Filter by customer
        if ($request->has('customer_id')) {
            $query->forCustomer($request->customer_id);
        }

        // Filter by aging bucket
        if ($request->has('aging_bucket')) {
            $query->inAgingBucket($request->aging_bucket);
        }

        // Filter by disbursement date range
        if ($request->has('disbursed_from') && $request->has('disbursed_to')) {
            $query->disbursedBetween($request->disbursed_from, $request->disbursed_to);
        }

        // Filter overdue only
        if ($request->boolean('overdue_only')) {
            $query->overdue();
        }

        // Filter NPL only
        if ($request->boolean('npl_only')) {
            $query->NPL();
        }

        // Search by loan account number
        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('loan_account_number', 'like', '%' . $request->search . '%')
                  ->orWhere('external_reference_number', 'like', '%' . $request->search . '%');
            });
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $loans = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => $loans->map(fn($loan) => $this->formatLoanSummary($loan)),
            'meta' => [
                'current_page' => $loans->currentPage(),
                'total' => $loans->total(),
                'per_page' => $loans->perPage(),
                'last_page' => $loans->lastPage(),
            ],
        ]);
    }

    /**
     * Create loan from approved application
     */
    public function createFromApplication(Request $request, Application $application): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($application->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if application is approved
        if (!$application->isApproved()) {
            return response()->json([
                'message' => 'Cannot create loan. Application is not approved.',
            ], 400);
        }

        // Check if loan already exists
        if (Loan::where('application_id', $application->id)->exists()) {
            return response()->json([
                'message' => 'Loan already exists for this application.',
            ], 400);
        }

        // Get underwriting decision
        $underwritingDecision = $application->underwritingDecisions()
            ->approved()
            ->latest()
            ->first();

        if (!$underwritingDecision) {
            return response()->json([
                'message' => 'No approved underwriting decision found.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $loan = $this->loanService->createLoanFromApplication($application, $underwritingDecision);

            DB::commit();

            return response()->json([
                'message' => 'Loan created successfully',
                'data' => $this->formatLoanDetails($loan->fresh(['customer', 'loanProduct', 'application'])),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create loan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loan details
     */
    public function show(Loan $loan): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($loan->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $loan->load(['customer', 'loanProduct', 'application', 'underwritingDecision']);

        return response()->json([
            'data' => $this->formatLoanDetails($loan),
        ]);
    }

    /**
     * Disburse and activate loan
     */
    public function disburse(Request $request, Loan $loan): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($loan->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if pending disbursement
        if (!$loan->isPendingDisbursement()) {
            return response()->json([
                'message' => 'Loan is not pending disbursement.',
            ], 400);
        }

        $validated = $request->validate([
            'disbursed_amount' => 'required|numeric|min:0|max:' . $loan->approved_amount,
            'disbursement_date' => 'required|date',
            'disbursement_method' => 'required|in:bank_transfer,cheque,cash,mobile_money',
            'disbursement_reference' => 'nullable|string|max:255',
            'disbursement_notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $this->loanService->disburseAndActivate($loan, [
                'disbursed_amount' => $validated['disbursed_amount'],
                'disbursement_date' => $validated['disbursement_date'],
                'disbursement_method' => $validated['disbursement_method'],
                'disbursement_reference' => $validated['disbursement_reference'] ?? null,
                'disbursement_notes' => $validated['disbursement_notes'] ?? null,
                'disbursed_by' => $user->id,
                'approved_by' => $user->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Loan disbursed and activated successfully',
                'data' => $this->formatLoanDetails($loan->fresh()),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to disburse loan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loan schedule
     */
    public function getSchedule(Loan $loan): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($loan->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $schedules = $loan->schedules()->get();

        return response()->json([
            'data' => $schedules->map(fn($schedule) => [
                'id' => $schedule->id,
                'installment_number' => $schedule->installment_number,
                'due_date' => $schedule->due_date,
                'status' => $schedule->status,
                'status_color' => $schedule->status_color,
                'principal_due' => $schedule->principal_due,
                'interest_due' => $schedule->interest_due,
                'total_due' => $schedule->total_due,
                'penalties_due' => $schedule->penalties_due,
                'fees_due' => $schedule->fees_due,
                'opening_balance' => $schedule->opening_balance,
                'closing_balance' => $schedule->closing_balance,
                'principal_paid' => $schedule->principal_paid,
                'interest_paid' => $schedule->interest_paid,
                'penalties_paid' => $schedule->penalties_paid,
                'fees_paid' => $schedule->fees_paid,
                'total_paid' => $schedule->total_paid,
                'balance_remaining' => $schedule->balance_remaining,
                'payment_progress' => $schedule->payment_progress,
                'days_past_due' => $schedule->days_past_due,
                'is_past_due' => $schedule->is_past_due,
                'days_until_due' => $schedule->days_until_due,
            ]),
            'summary' => [
                'total_installments' => $schedules->count(),
                'paid_installments' => $schedules->where('status', 'fully_paid')->count(),
                'pending_installments' => $schedules->whereIn('status', ['pending', 'partially_paid'])->count(),
                'overdue_installments' => $schedules->where('status', 'overdue')->count(),
                'total_principal_due' => $schedules->sum('principal_due'),
                'total_interest_due' => $schedules->sum('interest_due'),
                'total_paid' => $schedules->sum('total_paid'),
                'total_remaining' => $schedules->sum('balance_remaining'),
            ],
        ]);
    }

    /**
     * Calculate early settlement
     */
    public function calculateEarlySettlement(Request $request, Loan $loan): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($loan->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if loan is active
        if (!$loan->isActive()) {
            return response()->json([
                'message' => 'Loan is not active. Cannot calculate early settlement.',
            ], 400);
        }

        $validated = $request->validate([
            'settlement_date' => 'nullable|date',
        ]);

        $settlementDate = $validated['settlement_date'] ?? now();

        $calculation = $this->loanService->calculateEarlySettlement(
            $loan,
            Carbon::parse($settlementDate)
        );

        return response()->json([
            'data' => $calculation,
        ]);
    }

    /**
     * Close loan
     */
    public function close(Request $request, Loan $loan): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($loan->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if can be closed
        if ($loan->isClosed() || $loan->isWrittenOff()) {
            return response()->json([
                'message' => 'Loan is already closed or written off.',
            ], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $loan->close($validated['reason'] ?? null);

        return response()->json([
            'message' => 'Loan closed successfully',
            'data' => $this->formatLoanDetails($loan->fresh()),
        ]);
    }

    /**
     * Get loan summary (dashboard)
     */
    public function getSummary(Loan $loan): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($loan->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $summary = $this->loanService->getLoanSummary($loan);

        return response()->json(['data' => $summary]);
    }

    /**
     * Update loan aging and DPD (manual trigger)
     */
    public function updateAging(Loan $loan): JsonResponse
    {
        $user = Auth::user();

        // Validate access
        if ($loan->institution_id !== $user->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->loanService->updateAgingAndDPD($loan);

        return response()->json([
            'message' => 'Loan aging updated successfully',
            'data' => [
                'days_past_due' => $loan->fresh()->days_past_due,
                'aging_bucket' => $loan->fresh()->aging_bucket,
                'arrears_amount' => $loan->fresh()->arrears_amount,
            ],
        ]);
    }

    /**
     * Format loan summary for list
     */
    private function formatLoanSummary(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'loan_account_number' => $loan->loan_account_number,
            'external_reference_number' => $loan->external_reference_number,
            'status' => $loan->status,
            'status_color' => $loan->status_color,
            'customer' => [
                'id' => $loan->customer_id,
                'name' => $loan->customer->full_name ?? null,
                'customer_number' => $loan->customer->customer_number ?? null,
            ],
            'loan_product' => [
                'id' => $loan->loan_product_id,
                'name' => $loan->loanProduct->name ?? null,
            ],
            'approved_amount' => $loan->approved_amount,
            'disbursed_amount' => $loan->disbursed_amount,
            'total_outstanding' => $loan->total_outstanding,
            'total_paid' => $loan->total_paid,
            'repayment_progress' => $loan->repayment_progress,
            'days_past_due' => $loan->days_past_due,
            'aging_bucket' => $loan->aging_bucket,
            'aging_bucket_color' => $loan->aging_bucket_color,
            'disbursement_date' => $loan->disbursement_date,
            'maturity_date' => $loan->maturity_date,
            'created_at' => $loan->created_at,
        ];
    }

    /**
     * Format loan details for single view
     */
    private function formatLoanDetails(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'loan_account_number' => $loan->loan_account_number,
            'external_reference_number' => $loan->external_reference_number,
            'status' => $loan->status,
            'status_color' => $loan->status_color,
            
            'customer' => $loan->customer ? [
                'id' => $loan->customer->id,
                'full_name' => $loan->customer->full_name,
                'customer_number' => $loan->customer->customer_number,
            ] : null,
            
            'loan_product' => $loan->loanProduct ? [
                'id' => $loan->loanProduct->id,
                'name' => $loan->loanProduct->name,
            ] : null,
            
            'application' => $loan->application ? [
                'id' => $loan->application->id,
                'application_number' => $loan->application->application_number,
            ] : null,
            
            'loan_terms' => [
                'approved_amount' => $loan->approved_amount,
                'approved_tenure_months' => $loan->approved_tenure_months,
                'approved_interest_rate' => $loan->approved_interest_rate,
                'interest_method' => $loan->interest_method,
                'monthly_installment' => $loan->monthly_installment,
                'total_interest' => $loan->total_interest,
                'total_repayment' => $loan->total_repayment,
            ],
            
            'disbursement' => [
                'disbursed_amount' => $loan->disbursed_amount,
                'disbursement_date' => $loan->disbursement_date,
                'disbursement_method' => $loan->disbursement_method,
                'disbursement_reference' => $loan->disbursement_reference,
                'disbursement_notes' => $loan->disbursement_notes,
                'disbursement_approved_at' => $loan->disbursement_approved_at,
            ],
            
            'dates' => [
                'activation_date' => $loan->activation_date,
                'first_installment_date' => $loan->first_installment_date,
                'maturity_date' => $loan->maturity_date,
                'closure_date' => $loan->closure_date,
                'days_to_maturity' => $loan->days_to_maturity,
                'months_elapsed' => $loan->months_elapsed,
            ],
            
            'balances' => [
                'principal_outstanding' => $loan->principal_outstanding,
                'interest_outstanding' => $loan->interest_outstanding,
                'total_outstanding' => $loan->total_outstanding,
                'penalties_outstanding' => $loan->penalties_outstanding,
                'fees_outstanding' => $loan->fees_outstanding,
                'outstanding_percentage' => $loan->outstanding_percentage,
            ],
            
            'payments' => [
                'total_paid' => $loan->total_paid,
                'principal_paid' => $loan->principal_paid,
                'interest_paid' => $loan->interest_paid,
                'penalties_paid' => $loan->penalties_paid,
                'fees_paid' => $loan->fees_paid,
                'installments_paid' => $loan->installments_paid,
                'installments_remaining' => $loan->installments_remaining,
                'repayment_progress' => $loan->repayment_progress,
                'last_payment_date' => $loan->last_payment_date,
                'last_payment_amount' => $loan->last_payment_amount,
            ],
            
            'arrears' => [
                'days_past_due' => $loan->days_past_due,
                'arrears_amount' => $loan->arrears_amount,
                'aging_bucket' => $loan->aging_bucket,
                'aging_bucket_color' => $loan->aging_bucket_color,
                'next_payment_due_date' => $loan->next_payment_due_date,
                'next_payment_amount' => $loan->next_payment_amount,
            ],
            
            'property' => [
                'property_type' => $loan->property_type,
                'property_value' => $loan->property_value,
                'property_address' => $loan->property_address,
                'property_title_number' => $loan->property_title_number,
                'ltv_ratio' => $loan->ltv_ratio,
                'collateral_description' => $loan->collateral_description,
            ],
            
            'insurance' => [
                'insurance_required' => $loan->insurance_required,
                'insurance_provider' => $loan->insurance_provider,
                'insurance_policy_number' => $loan->insurance_policy_number,
                'insurance_premium' => $loan->insurance_premium,
                'insurance_expiry_date' => $loan->insurance_expiry_date,
                'is_insurance_expired' => $loan->is_insurance_expired,
            ],
            
            'risk' => [
                'risk_classification' => $loan->risk_classification,
                'risk_color' => $loan->risk_color,
                'provision_amount' => $loan->provision_amount,
                'provision_rate' => $loan->provision_rate,
            ],
            
            'restructure' => [
                'is_restructured' => $loan->is_restructured,
                'original_loan_id' => $loan->original_loan_id,
                'restructured_date' => $loan->restructured_date,
                'restructure_reason' => $loan->restructure_reason,
            ],
            
            'writeoff' => [
                'written_off_date' => $loan->written_off_date,
                'written_off_amount' => $loan->written_off_amount,
                'writeoff_reason' => $loan->writeoff_reason,
            ],
            
            'notes' => $loan->notes,
            'created_at' => $loan->created_at,
            'updated_at' => $loan->updated_at,
        ];
    }
}

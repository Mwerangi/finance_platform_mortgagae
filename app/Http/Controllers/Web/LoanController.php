<?php

namespace App\Http\Controllers\Web;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Application;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoanController extends Controller
{
    /**
     * Display a listing of loans
     */
    public function index(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        $query = Loan::with(['customer', 'loanProduct'])
            ->where('institution_id', $institutionId);

        // Apply filters
        if ($request->filled('status')) {
            $query->withStatus($request->status);
        }

        if ($request->filled('aging_bucket')) {
            $query->inAgingBucket($request->aging_bucket);
        }

        if ($request->boolean('overdue_only')) {
            $query->overdue();
        }

        if ($request->boolean('npl_only')) {
            $query->NPL();
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('loan_account_number', 'like', "%{$search}%")
                  ->orWhere('external_reference_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%");
                  });
            });
        }

        // Paginate
        $loans = $query->latest()->paginate(15)->withQueryString();

        // Calculate stats
        $stats = [
            'total' => Loan::where('institution_id', $institutionId)->count(),
            'active' => Loan::where('institution_id', $institutionId)->active()->count(),
            'pending_disbursement' => Loan::where('institution_id', $institutionId)
                ->pendingDisbursement()->count(),
            'overdue' => Loan::where('institution_id', $institutionId)->overdue()->count(),
            'npl' => Loan::where('institution_id', $institutionId)->NPL()->count(),
            'total_portfolio' => Loan::where('institution_id', $institutionId)
                ->active()->sum('principal_outstanding'),
            'total_outstanding' => Loan::where('institution_id', $institutionId)
                ->active()->sum('total_outstanding'),
        ];

        return Inertia::render('Loans/Index', [
            'loans' => $loans,
            'stats' => $stats,
            'filters' => $request->only(['status', 'aging_bucket', 'overdue_only', 'npl_only', 'search']),
        ]);
    }

    /**
     * Display the specified loan
     */
    public function show(Request $request, Loan $loan)
    {
        $loan->load([
            'customer',
            'loanProduct',
            'application',
            'underwritingDecision',
            'schedules' => function($query) {
                $query->orderBy('installment_number', 'asc');
            },
            'repayments' => function($query) {
                $query->latest()->limit(10);
            },
            'collectionsQueue' => function($query) {
                $query->where('status', 'open')->latest();
            },
            'promisesToPay' => function($query) {
                $query->where('status', 'open')->latest();
            },
            'creator',
            'disburser',
        ]);

        // Check permissions for actions
        $canDisburse = $request->user()->hasAnyRole(['institution-admin', 'provider-super-admin']);
        $canWriteOff = $request->user()->hasAnyRole(['institution-admin', 'provider-super-admin']);

        // Get next unpaid/partially paid installment
        $nextInstallment = $loan->schedules()
            ->whereIn('status', ['pending', 'partially_paid'])
            ->orderBy('due_date', 'asc')
            ->first();

        return Inertia::render('Loans/Show', [
            'loan' => $loan,
            'canDisburse' => $canDisburse,
            'canWriteOff' => $canWriteOff,
            'nextInstallment' => $nextInstallment,
        ]);
    }

    /**
     * Show loan disbursement form
     */
    public function showDisbursement(Application $application)
    {
        // Check if application is approved
        if ($application->status !== ApplicationStatus::APPROVED) {
            return redirect()->route('applications.show', $application)
                ->with('error', 'Only approved applications can be disbursed');
        }

        // Check if loan already exists
        if ($application->loans()->exists()) {
            $loan = $application->loans()->first();
            return redirect()->route('loans.show', $loan)
                ->with('info', 'Loan already exists for this application');
        }

        $application->load([
            'customer',
            'loanProduct',
            'latestUnderwritingDecision'
        ]);

        return Inertia::render('Loans/Disburse', [
            'application' => $application,
        ]);
    }

    /**
     * Disburse a loan from an approved application
     */
    public function disburse(Request $request, Application $application)
    {
        // Check if application is approved
        if ($application->status !== ApplicationStatus::APPROVED) {
            return back()->with('error', 'Only approved applications can be disbursed');
        }

        // Check if loan already exists
        if ($application->loans()->exists()) {
            $loan = $application->loans()->first();
            return redirect()->route('loans.show', $loan)
                ->with('info', 'Loan already exists for this application');
        }

        $validated = $request->validate([
            'disbursed_amount' => 'required|numeric|min:0',
            'disbursement_date' => 'required|date',
            'disbursement_method' => 'required|string|in:bank_transfer,cash,cheque,mobile_money',
            'disbursement_reference' => 'nullable|string|max:255',
            'disbursement_notes' => 'nullable|string',
            'activation_date' => 'required|date',
            'first_installment_date' => 'required|date',
        ]);

        // Load loan product to get interest rate
        $application->load('loanProduct');

        // Calculate loan amounts BEFORE creating the loan
        $principal = $validated['disbursed_amount'];
        $interestRate = $application->loanProduct->annual_interest_rate / 100;
        $months = $application->requested_tenure_months;
        $interestMethod = $application->loanProduct->interest_model;
        
        if ($interestMethod === 'reducing_balance') {
            $monthlyRate = $interestRate / 12;
            $monthlyPayment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
            $totalRepayment = $monthlyPayment * $months;
            $totalInterest = $totalRepayment - $principal;
        } else {
            // Flat rate
            $totalInterest = $principal * $interestRate * ($months / 12);
            $totalRepayment = $principal + $totalInterest;
            $monthlyPayment = $totalRepayment / $months;
        }

        // Create loan from application with all calculated values
        $loan = Loan::create([
            'application_id' => $application->id,
            'customer_id' => $application->customer_id,
            'institution_id' => $application->institution_id,
            'loan_product_id' => $application->loan_product_id,
            'underwriting_decision_id' => $application->latestUnderwritingDecision?->id,
            'status' => 'pending_disbursement',
            'approved_amount' => $application->requested_amount,
            'approved_tenure_months' => $application->requested_tenure_months,
            'approved_interest_rate' => $application->loanProduct->annual_interest_rate,
            'interest_method' => $interestMethod,
            'monthly_installment' => $monthlyPayment,
            'total_interest' => $totalInterest,
            'total_repayment' => $totalRepayment,
            'property_type' => $application->property_type,
            'property_value' => $application->property_value,
            'property_address' => $application->property_address,
            'ltv_ratio' => $application->property_value && $application->property_value > 0 
                ? round(($application->requested_amount / $application->property_value) * 100, 2) 
                : null,
            'created_by' => $request->user()->id,
        ]);

        // Disburse the loan
        $loan->disburse([
            'disbursed_amount' => $validated['disbursed_amount'],
            'disbursement_date' => $validated['disbursement_date'],
            'disbursement_method' => $validated['disbursement_method'],
            'disbursement_reference' => $validated['disbursement_reference'] ?? null,
            'disbursement_notes' => $validated['disbursement_notes'] ?? null,
            'disbursed_by' => $request->user()->id,
            'approved_by' => $request->user()->id,
        ]);

        // Calculate maturity date
        $maturityDate = \Carbon\Carbon::parse($validated['first_installment_date'])
            ->addMonths($months);

        // Activate the loan
        $loan->activate([
            'activation_date' => $validated['activation_date'],
            'first_installment_date' => $validated['first_installment_date'],
            'maturity_date' => $maturityDate,
        ]);

        // Update application status
        $application->update(['status' => ApplicationStatus::DISBURSED, 'disbursed_at' => now()]);

        return redirect()->route('loans.show', $loan)
            ->with('success', 'Loan disbursed successfully');
    }

    /**
     * Activate a loan
     */
    public function activate(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'activation_date' => 'required|date',
            'first_installment_date' => 'required|date|after_or_equal:activation_date',
        ]);

        if (!$loan->isPendingDisbursement()) {
            return back()->with('error', 'Only pending disbursement loans can be activated');
        }

        // Calculate maturity date
        $maturityDate = \Carbon\Carbon::parse($validated['first_installment_date'])
            ->addMonths($loan->approved_tenure_months);

        $loan->activate([
            'activation_date' => $validated['activation_date'],
            'first_installment_date' => $validated['first_installment_date'],
            'maturity_date' => $maturityDate,
        ]);

        return back()->with('success', 'Loan activated successfully');
    }

    /**
     * Add manual payment to loan
     */
    public function addPayment(Request $request, Loan $loan)
    {
        // Get selected installment or default to next unpaid installment
        $selectedInstallmentId = $request->input('installment_id');
        
        if ($selectedInstallmentId) {
            $targetInstallment = $loan->schedules()
                ->where('id', $selectedInstallmentId)
                ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                ->first();
                
            if (!$targetInstallment) {
                return back()->with('error', 'Selected installment not found or already paid');
            }
        } else {
            $targetInstallment = $loan->schedules()
                ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
                ->orderBy('due_date', 'asc')
                ->first();
        }

        $minAmount = $targetInstallment ? $targetInstallment->balance_remaining : 0.01;

        $validated = $request->validate([
            'payment_date' => 'required|date',
            'amount' => ['required', 'numeric', 'min:' . $minAmount],
            'payment_method' => 'required|in:cash,bank_transfer,mobile_money,cheque,standing_order',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'installment_id' => 'nullable|exists:loan_schedules,id'
        ], [
            'amount.min' => $targetInstallment 
                ? 'Payment amount must be at least ' . number_format($minAmount, 2) . ' (installment balance)'
                : 'Payment amount must be greater than 0'
        ]);

        // Check if loan is active
        if ($loan->status !== 'active' && $loan->status !== 'defaulted') {
            return back()->with('error', 'Can only add payments to active or defaulted loans');
        }

        // Create the repayment
        $repayment = \App\Models\Repayment::create([
            'institution_id' => $loan->institution_id,
            'loan_id' => $loan->id,
            'customer_id' => $loan->customer_id,
            'payment_date' => $validated['payment_date'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'transaction_reference' => $validated['reference_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'recorded_by' => $request->user()->id,
        ]);

        // Allocate payment to loan (principal, interest, etc.)
        $this->allocatePayment($repayment, $loan, $targetInstallment);

        // Update loan outstanding amounts and total paid
        $loan->update([
            'principal_outstanding' => max(0, $loan->principal_outstanding - $repayment->principal_amount),
            'interest_outstanding' => max(0, $loan->interest_outstanding - $repayment->interest_amount),
            'total_outstanding' => max(0, $loan->total_outstanding - $repayment->amount),
            'total_paid' => $loan->total_paid + $repayment->amount,
        ]);

        // Check if loan is fully paid
        if ($loan->total_outstanding <= 0) {
            $loan->update(['status' => 'fully_paid']);
        }

        return back()->with('success', 'Payment recorded successfully');
    }

    /**
     * Allocate payment to installments (principal and interest)
     * 
     * @param \App\Models\Repayment $repayment
     * @param Loan $loan
     * @param \App\Models\LoanSchedule|null $startFromInstallment Optional installment to start allocation from
     */
    protected function allocatePayment(\App\Models\Repayment $repayment, Loan $loan, $startFromInstallment = null)
    {
        $remainingAmount = $repayment->amount;
        $totalPrincipal = 0;
        $totalInterest = 0;
        $totalPenalties = 0;
        $totalFees = 0;
        
        // Get all unpaid/partially paid installments in order
        $query = $loan->schedules()
            ->whereIn('status', ['pending', 'partially_paid', 'overdue']);
        
        // If a specific installment is selected, start from that installment
        if ($startFromInstallment) {
            $query->where('installment_number', '>=', $startFromInstallment->installment_number);
        }
        
        $installments = $query->orderBy('installment_number', 'asc')->get();
        
        // If no schedules exist, use simple allocation based on loan outstanding amounts
        if ($installments->isEmpty()) {
            // Allocate to interest first, then principal (standard practice)
            $interestPayment = min($remainingAmount, $loan->interest_outstanding);
            $remainingAmount -= $interestPayment;
            $totalInterest = $interestPayment;
            
            $principalPayment = min($remainingAmount, $loan->principal_outstanding);
            $remainingAmount -= $principalPayment;
            $totalPrincipal = $principalPayment;
            
            $repayment->update([
                'principal_amount' => $totalPrincipal,
                'interest_amount' => $totalInterest,
                'penalties_amount' => 0,
                'fees_amount' => 0,
                'unallocated_amount' => $remainingAmount,
                'outstanding_before_payment' => $loan->total_outstanding,
                'outstanding_after_payment' => max(0, $loan->total_outstanding - ($totalPrincipal + $totalInterest)),
                'status' => 'allocated',
                'is_overpayment' => $remainingAmount > 0,
                'is_advance_payment' => $remainingAmount > 0,
            ]);
            
            return;
        }
        
        // Allocate across multiple installments when schedules exist
        foreach ($installments as $installment) {
            if ($remainingAmount <= 0) break;
            
            // Calculate what's still owed on this installment
            $penaltiesOwed = $installment->penalties_due - $installment->penalties_paid;
            $feesOwed = $installment->fees_due - $installment->fees_paid;
            $interestOwed = $installment->interest_due - $installment->interest_paid;
            $principalOwed = $installment->principal_due - $installment->principal_paid;
            
            // Allocate in order: penalties -> fees -> interest -> principal
            $penaltyPayment = min($remainingAmount, $penaltiesOwed);
            $remainingAmount -= $penaltyPayment;
            $totalPenalties += $penaltyPayment;
            
            $feePayment = min($remainingAmount, $feesOwed);
            $remainingAmount -= $feePayment;
            $totalFees += $feePayment;
            
            $interestPayment = min($remainingAmount, $interestOwed);
            $remainingAmount -= $interestPayment;
            $totalInterest += $interestPayment;
            
            $principalPayment = min($remainingAmount, $principalOwed);
            $remainingAmount -= $principalPayment;
            $totalPrincipal += $principalPayment;
            
            // Calculate new balance and status
            $totalPaymentForInstallment = $penaltyPayment + $feePayment + $interestPayment + $principalPayment;
            $newBalance = max(0, $installment->balance_remaining - $totalPaymentForInstallment);
            $newTotalPaid = $installment->total_paid + $totalPaymentForInstallment;
            
            // Determine new status
            $newStatus = $installment->status;
            if ($newBalance <= 0) {
                $newStatus = 'fully_paid';
            } elseif ($newTotalPaid > 0) {
                $newStatus = 'partially_paid';
            }
            
            // Update installment
            $installment->update([
                'penalties_paid' => $installment->penalties_paid + $penaltyPayment,
                'fees_paid' => $installment->fees_paid + $feePayment,
                'interest_paid' => $installment->interest_paid + $interestPayment,
                'principal_paid' => $installment->principal_paid + $principalPayment,
                'total_paid' => $newTotalPaid,
                'balance_remaining' => $newBalance,
                'last_payment_date' => $repayment->payment_date,
                'status' => $newStatus,
            ]);
            
            // Record the payment in installment history
            $paymentHistory = $installment->payment_history ?? [];
            $paymentHistory[] = [
                'repayment_id' => $repayment->id,
                'date' => $repayment->payment_date,
                'amount' => $totalPaymentForInstallment,
                'penalties' => $penaltyPayment,
                'fees' => $feePayment,
                'interest' => $interestPayment,
                'principal' => $principalPayment,
            ];
            $installment->update(['payment_history' => $paymentHistory]);
            
            // Link repayment to this installment if it's the first one
            if (!$repayment->loan_schedule_id && $totalPaymentForInstallment > 0) {
                $repayment->update(['loan_schedule_id' => $installment->id]);
            }
        }
        
        // Update repayment with allocation
        $repayment->update([
            'principal_amount' => $totalPrincipal,
            'interest_amount' => $totalInterest,
            'penalties_amount' => $totalPenalties,
            'fees_amount' => $totalFees,
            'unallocated_amount' => $remainingAmount,
            'outstanding_before_payment' => $loan->total_outstanding,
            'outstanding_after_payment' => max(0, $loan->total_outstanding - ($totalPrincipal + $totalInterest + $totalPenalties + $totalFees)),
            'status' => 'allocated',
            'is_overpayment' => $remainingAmount > 0,
            'is_advance_payment' => $remainingAmount > 0,
        ]);
    }

    /**
     * Write off a loan
     */
    public function writeOff(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'amount' => 'nullable|numeric|min:0'
        ]);

        if (!$loan->isActive() && !$loan->isDefaulted()) {
            return back()->with('error', 'Only active or defaulted loans can be written off');
        }

        $loan->writeOff(
            $request->user()->id,
            $validated['reason'],
            $validated['amount'] ?? null
        );

        return back()->with('success', 'Loan written off successfully');
    }

    /**
     * Mark loan as defaulted
     */
    public function markAsDefaulted(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000'
        ]);

        if (!$loan->isActive()) {
            return back()->with('error', 'Only active loans can be marked as defaulted');
        }

        $loan->markAsDefaulted($validated['reason']);

        return back()->with('success', 'Loan marked as defaulted');
    }

    /**
     * Close a loan
     */
    public function close(Request $request, Loan $loan)
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000'
        ]);

        if (!$loan->isFullyPaid() && !$loan->isWrittenOff()) {
            return back()->with('error', 'Only fully paid or written off loans can be closed');
        }

        $loan->close($validated['reason'] ?? null);

        return back()->with('success', 'Loan closed successfully');
    }

    /**
     * Display disbursements list
     */
    public function disbursements(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        $query = Loan::with(['customer', 'loanProduct', 'disburser'])
            ->where('institution_id', $institutionId)
            ->whereNotNull('disbursement_date');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('loan_account_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('disbursement_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('disbursement_date', '<=', $request->date_to);
        }

        $loans = $query->orderBy('disbursement_date', 'desc')->paginate(20);

        return Inertia::render('Loans/Disbursements', [
            'loans' => $loans,
            'filters' => $request->only(['search', 'date_from', 'date_to'])
        ]);
    }

    /**
     * Display repayments list
     */
    public function repayments(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        // Get repayments with related data
        $query = \App\Models\Repayment::with(['loan.customer', 'loan.loanProduct', 'recordedBy'])
            ->whereHas('loan', function ($q) use ($institutionId) {
                $q->where('institution_id', $institutionId);
            });

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('loan', function ($q) use ($search) {
                      $q->where('loan_account_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                        });
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        $repayments = $query->orderBy('payment_date', 'desc')->paginate(20);

        // Calculate summary stats
        $stats = [
            'total_amount' => $repayments->sum('amount'),
            'total_principal' => $repayments->sum('principal_amount'),
            'total_interest' => $repayments->sum('interest_amount'),
            'total_count' => $repayments->total(),
        ];

        return Inertia::render('Loans/Repayments', [
            'repayments' => $repayments,
            'stats' => $stats,
            'filters' => $request->only(['search', 'date_from', 'date_to', 'payment_method'])
        ]);
    }
}

<?php

namespace App\Http\Controllers\Web;

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
            'schedules',
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

        return Inertia::render('Loans/Show', [
            'loan' => $loan,
            'canDisburse' => $canDisburse,
            'canWriteOff' => $canWriteOff,
        ]);
    }

    /**
     * Show loan disbursement form
     */
    public function showDisbursement(Application $application)
    {
        // Check if application is approved
        if ($application->status !== 'approved') {
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
        if ($application->status !== 'approved') {
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

        // Create loan from application
        $loan = Loan::create([
            'application_id' => $application->id,
            'customer_id' => $application->customer_id,
            'institution_id' => $application->institution_id,
            'loan_product_id' => $application->loan_product_id,
            'underwriting_decision_id' => $application->latestUnderwritingDecision?->id,
            'status' => 'pending_disbursement',
            'approved_amount' => $application->requested_amount,
            'approved_tenure_months' => $application->requested_tenure_months,
            'approved_interest_rate' => $application->loanProduct->interest_rate,
            'interest_method' => $application->loanProduct->interest_model,
            'property_type' => $application->property_type,
            'property_value' => $application->property_value,
            'property_address' => $application->property_address,
            'ltv_ratio' => $application->ltv_ratio,
            'created_by' => $request->user()->id,
        ]);

        // Calculate loan amounts (simplified - should use LoanService)
        $principal = $validated['disbursed_amount'];
        $interestRate = $loan->approved_interest_rate / 100;
        $months = $loan->approved_tenure_months;
        
        if ($loan->interest_method === 'reducing_balance') {
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

        $loan->update([
            'monthly_installment' => $monthlyPayment,
            'total_interest' => $totalInterest,
            'total_repayment' => $totalRepayment,
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
        $application->update(['status' => 'disbursed', 'disbursed_at' => now()]);

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
}

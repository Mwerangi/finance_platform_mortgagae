<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ParseBankStatementJob;
use App\Models\BankStatementImport;
use App\Models\LoanProduct;
use App\Models\Prospect;
use App\Models\StatementAnalytics;
use App\Services\ProspectService;
use App\Services\StatementAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProspectController extends Controller
{
    public function __construct(
        protected ProspectService $prospectService,
        protected StatementAnalyticsService $statementAnalyticsService
    ) {}

    /**
     * Display pre-qualification start page
     */
    public function start()
    {
        $loanProducts = LoanProduct::where('institution_id', auth()->user()->institution_id)
            ->active()
            ->select('id', 'name', 'code', 'annual_interest_rate', 'min_loan_amount', 'max_loan_amount', 'min_tenure_months', 'max_tenure_months')
            ->get();

        return Inertia::render('PreQualify/Start', [
            'loanProducts' => $loanProducts,
        ]);
    }

    /**
     * Store prospect and redirect to statement upload
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'id_number' => 'required|string|max:50|unique:prospects,id_number',
            'customer_type' => ['required', Rule::in(['salary', 'business', 'mixed'])],
            'loan_purpose' => ['required', Rule::in([
                'home_purchase',
                'home_refinance',
                'home_completion',
                'home_construction',
                'home_equity_release',
            ])],
            'requested_amount' => 'required|numeric|min:1000',
            'requested_tenure' => 'required|integer|min:1|max:360',
            'loan_product_id' => 'nullable|exists:loan_products,id',
            'property_location' => 'nullable|string|max:255',
            'property_value' => 'nullable|numeric|min:0',
        ]);

        $validated['institution_id'] = auth()->user()->institution_id;
        $validated['created_by'] = auth()->id();

        $prospect = $this->prospectService->createProspect($validated);

        return redirect()->route('pre-qualify.statement', $prospect->id)
            ->with('success', 'Basic information saved. Please upload bank statement for eligibility check.');
    }

    /**
     * Display bank statement upload page
     */
    public function statement(Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        return Inertia::render('PreQualify/Statement', [
            'prospect' => $prospect->load('loanProduct'),
        ]);
    }

    /**
     * Upload and analyze bank statement, then run eligibility
     */
    public function uploadStatement(Request $request, Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
        ]);

        try {
            DB::beginTransaction();

            // Store the uploaded file
            $file = $request->file('file');
            $path = $file->store('bank-statements', 'private');

            // Create bank statement import record
            $statementImport = BankStatementImport::create([
                'institution_id' => $prospect->institution_id,
                'customer_id' => null, // No customer yet
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getClientOriginalExtension(),
                'file_size' => $file->getSize(),
                'bank_name' => $validated['bank_name'] ?? 'Unknown',
                'account_number' => $validated['account_number'] ?? null,
                'import_status' => 'pending',
                'uploaded_by' => auth()->id(),
            ]);

            // Update prospect with statement import reference
            $prospect->update([
                'bank_statement_import_id' => $statementImport->id,
                'status' => 'statement_uploaded',
            ]);

            // Process the statement asynchronously (parse transactions)
            ParseBankStatementJob::dispatch($statementImport);

            DB::commit();

            // Redirect to processing page with polling
            return redirect()->route('pre-qualify.processing', $prospect->id)
                ->with('success', 'Bank statement uploaded successfully. Processing transactions...');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process bank statement: ' . $e->getMessage());
            
            return back()->withErrors([
                'file' => 'Failed to process bank statement: ' . $e->getMessage()
            ])->withInput();
        }
    }

    /**
     * Show processing status page (polls for completion)
     */
    public function processing(Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        // Check if statement import exists
        if (!$prospect->bank_statement_import_id) {
            return redirect()->route('pre-qualify.statement', $prospect->id)
                ->withErrors(['file' => 'No bank statement found. Please upload a statement.']);
        }

        $statementImport = $prospect->statementImport;

        // If already completed, redirect to results
        if ($statementImport->import_status === 'completed' && $prospect->eligibility_assessment_id) {
            return redirect()->route('pre-qualify.results', $prospect->id);
        }

        // If failed, redirect back to upload
        if ($statementImport->import_status === 'failed') {
            return redirect()->route('pre-qualify.statement', $prospect->id)
                ->withErrors(['file' => 'Failed to process bank statement. Please try again or contact support.']);
        }

        return Inertia::render('PreQualify/Processing', [
            'prospect' => $prospect->load('loanProduct'),
            'statementImport' => $statementImport,
        ]);
    }

    /**
     * API endpoint to check processing status
     */
    public function checkStatus(Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        $statementImport = $prospect->statementImport;

        if (!$statementImport) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $response = [
            'status' => $statementImport->import_status->value,
            'rows_processed' => $statementImport->rows_processed,
            'rows_total' => $statementImport->rows_total,
            'progress' => $statementImport->rows_total > 0 
                ? round(($statementImport->rows_processed / $statementImport->rows_total) * 100, 1)
                : 0,
        ];

        // Check if analytics are computed and assessment is done
        if ($statementImport->import_status->value === 'completed') {
            $hasAnalytics = $statementImport->analytics()->exists();
            $hasAssessment = $prospect->eligibility_assessment_id !== null;

            $response['has_analytics'] = $hasAnalytics;
            $response['has_assessment'] = $hasAssessment;
            $response['ready'] = $hasAnalytics && $hasAssessment;
        }

        return response()->json($response);
    }

    /**
     * Cancel bank statement processing and revert to statement upload step
     */
    public function cancelProcessing(Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            $statementImport = $prospect->statementImport;

            if ($statementImport) {
                // Delete the uploaded file from storage
                if ($statementImport->file_path && Storage::disk('private')->exists($statementImport->file_path)) {
                    Storage::disk('private')->delete($statementImport->file_path);
                }

                // Delete related analytics if any
                if ($statementImport->analytics) {
                    $statementImport->analytics->delete();
                }

                // Delete the statement import record
                $statementImport->delete();
            }

            // Reset prospect status and remove statement reference
            $prospect->update([
                'bank_statement_import_id' => null,
                'status' => 'pending',
            ]);

            DB::commit();

            return redirect()->route('pre-qualify.statement', $prospect->id)
                ->with('success', 'Processing cancelled. You can upload a new statement.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to cancel processing: ' . $e->getMessage());
            
            return back()->withErrors([
                'error' => 'Failed to cancel processing: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Manually trigger analytics and assessment (if parsing is done but analytics failed)
     */
    public function retryAnalytics(Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        $statementImport = $prospect->statementImport;

        if (!$statementImport || $statementImport->import_status !== 'completed') {
            return back()->withErrors(['error' => 'Bank statement must be successfully parsed first.']);
        }

        try {
            DB::beginTransaction();

            // Compute analytics
            $analyticsData = $this->statementAnalyticsService->computeAnalytics($statementImport);

            // Create or update statement analytics record
            $statementAnalytics = StatementAnalytics::updateOrCreate(
                ['bank_statement_import_id' => $statementImport->id],
                [
                    'customer_id' => null,
                    'institution_id' => $prospect->institution_id,
                    'analysis_months' => $analyticsData['analysis_months'],
                    'analysis_start_date' => $analyticsData['analysis_start_date'],
                    'analysis_end_date' => $analyticsData['analysis_end_date'],
                    'opening_balance' => $analyticsData['opening_balance'],
                    'closing_balance' => $analyticsData['closing_balance'],
                    'avg_monthly_inflow' => $analyticsData['avg_monthly_inflow'],
                    'avg_monthly_outflow' => $analyticsData['avg_monthly_outflow'],
                    'avg_net_surplus' => $analyticsData['avg_net_surplus'],
                    'income_classification' => $analyticsData['income_classification'],
                    'estimated_net_income' => $analyticsData['estimated_net_income'],
                    'income_stability_score' => $analyticsData['income_stability_score'],
                    'has_regular_salary' => $analyticsData['has_regular_salary'],
                    'has_business_income' => $analyticsData['has_business_income'],
                    'income_sources' => $analyticsData['income_sources'],
                    'total_debt_obligations' => $analyticsData['total_debt_obligations'],
                    'estimated_monthly_debt' => $analyticsData['estimated_monthly_debt'],
                    'debt_payment_count' => $analyticsData['debt_payment_count'],
                    'detected_debts' => $analyticsData['detected_debts'],
                    'cash_flow_volatility_score' => $analyticsData['cash_flow_volatility_score'],
                    'negative_balance_days' => $analyticsData['negative_balance_days'],
                    'bounce_count' => $analyticsData['bounce_count'],
                    'gambling_transaction_count' => $analyticsData['gambling_transaction_count'],
                    'large_unexplained_outflows' => $analyticsData['large_unexplained_outflows'],
                    'risk_flags' => $analyticsData['risk_flags'],
                    'overall_risk_assessment' => $analyticsData['overall_risk_assessment'],
                    'debt_to_income_ratio' => $analyticsData['debt_to_income_ratio'],
                    'disposable_income_ratio' => $analyticsData['disposable_income_ratio'],
                    'monthly_inflows' => $analyticsData['monthly_inflows'],
                    'monthly_outflows' => $analyticsData['monthly_outflows'],
                    'monthly_net_surplus' => $analyticsData['monthly_net_surplus'],
                ]
            );

            // Run eligibility assessment
            $assessment = $this->prospectService->runEligibilityAssessment($prospect, $statementAnalytics);

            DB::commit();

           return redirect()->route('pre-qualify.results', $prospect->id)
                ->with('success', 'Analytics computed and eligibility assessment completed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to compute analytics: ' . $e->getMessage());
            
            return back()->withErrors(['error' => 'Failed to compute analytics: ' . $e->getMessage()]);
        }
    }

    /**
     * Display eligibility results
     */
    public function results(Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        // Check if assessment exists
        if (!$prospect->eligibility_assessment_id) {
            return redirect()->route('pre-qualify.statement', $prospect->id)
                ->withErrors(['error' => 'No eligibility assessment found. Please complete bank statement upload first.']);
        }

        // Get analytics data from bank statement import
        $analytics = null;
        $bankStatement = $prospect->statementImport;
        if ($bankStatement) {
            $analytics = $bankStatement->analytics;
            
            // Auto-recompute if analytics are missing or invalid (all zeros but transactions exist)
            $transactionCount = $bankStatement->transactions()->count();
            $needsRecompute = false;
            
            if (!$analytics && $transactionCount > 0) {
                Log::info("Analytics missing for import #{$bankStatement->id}, recomputing...");
                $needsRecompute = true;
            } elseif ($analytics && 
                      $analytics->total_credits == 0 && 
                      $analytics->total_debits == 0 && 
                      $transactionCount > 0) {
                Log::info("Analytics invalid (zeros) for import #{$bankStatement->id}, recomputing...");
                $needsRecompute = true;
            }
            
            if ($needsRecompute) {
                try {
                    // Delete old analytics if exists
                    if ($analytics) {
                        $analytics->delete();
                    }
                    
                    // Recompute synchronously
                    \App\Jobs\ComputeAnalyticsJob::dispatchSync($bankStatement);
                    
                    // Reload analytics
                    $bankStatement->refresh();
                    $analytics = $bankStatement->analytics;
                    
                    Log::info("Analytics recomputed successfully for import #{$bankStatement->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to recompute analytics for import #{$bankStatement->id}: {$e->getMessage()}");
                    // Continue anyway - show what we have
                }
            }
        }

        return Inertia::render('PreQualify/Results', [
            'prospect' => $prospect->load(['loanProduct', 'eligibilityAssessment']),
            'assessment' => $prospect->eligibilityAssessment,
            'analytics' => $analytics,
        ]);
    }

    /**
     * Amend loan parameters and re-run eligibility assessment
     * Only accessible to privileged users (managers, credit officers, admins)
     */
    public function amendAndReassess(Request $request, Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        // Authorization check - only managers, credit officers, and admins can amend
        $user = auth()->user();
        $canAmend = $user->hasAnyRole(['provider-super-admin', 'institution-admin', 'credit-manager', 'credit-officer']) || 
                   $user->hasPermission('amend_loan_assessment') ||
                   $user->hasPermission('applications.make-decisions');
        
        if (!$canAmend) {
            return back()->with('error', 'You do not have permission to amend loan parameters.');
        }

        // Validate the amended parameters
        $validated = $request->validate([
            'requested_amount' => 'required|numeric|min:1000000',
            'requested_tenure' => 'required|integer|min:3|max:360',
            'amendment_reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Update prospect with new parameters
            $prospect->update([
                'requested_amount' => $validated['requested_amount'],
                'requested_tenure' => $validated['requested_tenure'],
            ]);

            // Get the latest analytics
            $analytics = $prospect->statementImport?->analytics;
            
            if (!$analytics) {
                throw new \Exception('No statement analytics found. Please upload bank statement first.');
            }

            // Re-run eligibility assessment
            $newAssessment = $this->prospectService->runEligibilityAssessment($prospect, $analytics);

            // Log the amendment
            Log::info('Loan parameters amended and reassessed', [
                'prospect_id' => $prospect->id,
                'amended_by' => $user->id,
                'old_amount' => $prospect->getOriginal('requested_amount'),
                'new_amount' => $validated['requested_amount'],
                'old_tenure' => $prospect->getOriginal('requested_tenure'),
                'new_tenure' => $validated['requested_tenure'],
                'reason' => $validated['amendment_reason'],
                'new_decision' => $newAssessment->system_decision,
            ]);

            // Store amendment reason in assessment notes or separate table if needed
            // For now, we'll add it to the calculation_details
            $calculationDetails = $newAssessment->calculation_details;
            $calculationDetails['amendment_history'] = [
                'amended_at' => now()->toISOString(),
                'amended_by' => $user->name,
                'reason' => $validated['amendment_reason'],
                'previous_amount' => $prospect->getOriginal('requested_amount'),
                'previous_tenure' => $prospect->getOriginal('requested_tenure'),
            ];
            $newAssessment->update(['calculation_details' => $calculationDetails]);

            DB::commit();

            return redirect()->route('pre-qualify.results', $prospect->id)
                ->with('success', 'Loan parameters updated and assessment re-run successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to amend and reassess loan', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Failed to re-run assessment: ' . $e->getMessage());
        }
    }

    /**
     * Re-run eligibility check using existing bank statement data
     * Useful when analytics algorithms are updated or user wants to refresh assessment
     */
    public function rerunEligibilityCheck(Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            // Check if bank statement import exists
            $statementImport = $prospect->statementImport;
            if (!$statementImport) {
                return back()->with('error', 'No bank statement found. Please upload a bank statement first.');
            }

            // Check if transactions exist
            $transactionCount = $statementImport->transactions()->count();
            if ($transactionCount === 0) {
                return back()->with('error', 'No transactions found in bank statement. Cannot run eligibility check.');
            }

            Log::info("Re-running eligibility check for prospect #{$prospect->id}", [
                'user_id' => auth()->id(),
                'import_id' => $statementImport->id,
                'transaction_count' => $transactionCount,
            ]);

            // Step 1: Recompute analytics from existing transactions
            $existingAnalytics = $statementImport->analytics;
            if ($existingAnalytics) {
                $existingAnalytics->delete();
            }

            // Dispatch analytics computation synchronously
            \App\Jobs\ComputeAnalyticsJob::dispatchSync($statementImport);

            // Reload to get fresh analytics
            $statementImport->refresh();
            $analytics = $statementImport->analytics;

            if (!$analytics) {
                throw new \Exception('Failed to compute analytics. Please try again.');
            }

            // Step 2: Re-run eligibility assessment
            $newAssessment = $this->prospectService->runEligibilityAssessment($prospect, $analytics);

            DB::commit();

            Log::info("Successfully re-ran eligibility check for prospect #{$prospect->id}", [
                'assessment_id' => $newAssessment->id,
                'decision' => $newAssessment->system_decision,
                'risk_grade' => $newAssessment->risk_grade,
            ]);

            return redirect()->route('pre-qualify.results', $prospect->id)
                ->with('success', 'Eligibility check has been re-run successfully! Analytics and assessment have been updated.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to re-run eligibility check for prospect #{$prospect->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->with('error', 'Failed to re-run eligibility check: ' . $e->getMessage());
        }
    }

    /**
     * Convert prospect to customer
     */
    public function convertToCustomer(Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        if (!$prospect->canConvertToCustomer()) {
            return back()->with('error', 'This prospect is not eligible for conversion to customer.');
        }

        try {
            $customer = $this->prospectService->convertToCustomer($prospect);

            // Get the newly created application
            $application = \App\Models\Application::where('customer_id', $customer->id)
                ->where('institution_id', $customer->institution_id)
                ->latest()
                ->first();

            // Redirect to customer profile for KYC completion
            return redirect()->route('customers.show', $customer->id)
                ->with('success', 'Prospect successfully converted! Please complete KYC verification to proceed with the application.')
                ->with('info', 'Upload required KYC documents (National ID, Proof of Address, etc.) to continue.')
                ->with('application_id', $application?->id);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to convert prospect: ' . $e->getMessage());
        }
    }

    /**
     * Display all prospects (index page)
     */
    public function index(Request $request)
    {
        $query = Prospect::where('institution_id', auth()->user()->institution_id)
            ->with(['loanProduct', 'eligibilityAssessment', 'convertedToCustomer'])
            ->whereNotIn('status', ['converted_to_customer']); // Exclude converted prospects

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        $prospects = $query->latest()->paginate(15);

        return Inertia::render('Prospects/Index', [
            'prospects' => $prospects,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Display single prospect
     */
    public function show(Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        $prospect->load([
            'loanProduct',
            'eligibilityAssessment',
            'convertedToCustomer',
            'documents.documentType',
            'createdBy',
        ]);

        return Inertia::render('Prospects/Show', [
            'prospect' => $prospect,
        ]);
    }

    /**
     * Override policy decision for declined applications
     * Requires senior management authorization
     */
    public function overrideDecision(Request $request, Prospect $prospect)
    { // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        // Authorization check - only super admins, institution admins, and credit managers can override
        $user = auth()->user();
        $canOverride = $user->hasAnyRole(['provider-super-admin', 'institution-admin', 'credit-manager']) || 
                      $user->hasPermission('applications.approve-overrides');
        
        if (!$canOverride) {
            return back()->with('error', 'You do not have permission to override policy decisions.');
        }

        // Validate the override parameters
        $validated = $request->validate([
            'override_reason' => 'required|string|min:50|max:1000',
            'conditions' => 'nullable|string|max:1000',
            'approved_amount' => 'required|numeric|min:1000000',
            'approved_tenure' => 'required|integer|min:3|max:360',
        ]);

        try {
            DB::beginTransaction();

            // Get the latest assessment
            $assessment = $prospect->eligibilityAssessments()->latest()->first();
            
            if (!$assessment) {
                throw new \Exception('No eligibility assessment found.');
            }

            // Update the assessment with override information
            $calculationDetails = $assessment->calculation_details ?: [];
            $calculationDetails['override'] = [
                'overridden_at' => now()->toISOString(),
                'overridden_by' => $user->name,
                'overridden_by_id' => $user->id,
                'override_reason' => $validated['override_reason'],
                'special_conditions' => $validated['conditions'],
                'original_decision' => $assessment->system_decision,
                'original_amount' => $assessment->final_max_loan,
                'approved_amount' => $validated['approved_amount'],
                'approved_tenure' => $validated['approved_tenure'],
            ];

            // Update assessment to reflect override
            $assessment->update([
                'system_decision' => 'conditional',  // Change to conditional with override
                'is_eligible' => true,
                'final_max_loan' => $validated['approved_amount'],
                'decision_reason' => 'APPROVED WITH MANAGEMENT OVERRIDE: ' . $validated['override_reason'],
                'calculation_details' => $calculationDetails,
            ]);

            // Add override conditions if provided
            if (!empty($validated['conditions'])) {
                $conditions = $assessment->conditions ?: [];
                $conditions[] = [
                    'condition_type' => 'override_condition',
                    'title' => 'Management Override Conditions',
                    'description' => $validated['conditions'],
                    'severity' => 'high',
                    'required_action' => 'Must be fulfilled before final approval',
                ];
                $assessment->update(['conditions' => $conditions]);
            }

            // Update prospect status to eligibility_passed since override approves it
            $prospect->update([
                'status' => 'eligibility_passed',
                'requested_amount' => $validated['approved_amount'],
                'requested_tenure' => $validated['approved_tenure'],
            ]);

            // Log the override
            Log::warning('Policy decision overridden by senior management', [
                'prospect_id' => $prospect->id,
                'assessment_id' => $assessment->id,
                'overridden_by' => $user->id,
                'original_decision' => $calculationDetails['override']['original_decision'],
                'new_decision' => 'conditional',
                'approved_amount' => $validated['approved_amount'],
                'override_reason' => $validated['override_reason'],
            ]);

            DB::commit();

            return redirect()->route('pre-qualify.results', $prospect->id)
                ->with('success', 'Policy decision overridden. Application approved with conditions.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to override policy decision', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
            ]);
            
            return back()->with('error', 'Failed to override decision: ' . $e->getMessage());
        }
    }

    /**
     * Delete a prospect from the system
     */
    public function destroy(Prospect $prospect)
    {
        // Ensure user can access this prospect
        if ($prospect->institution_id !== auth()->user()->institution_id) {
            abort(403);
        }

        // Prevent deletion of converted prospects
        if ($prospect->status === 'converted_to_customer' || $prospect->customer_id) {
            return back()->with('error', 'Cannot delete a prospect that has been converted to a customer.');
        }

        try {
            DB::beginTransaction();

            // Delete associated bank statement import and transactions if exists
            if ($prospect->bank_statement_import_id) {
                $import = BankStatementImport::find($prospect->bank_statement_import_id);
                if ($import) {
                    // Delete transactions first
                    $import->transactions()->delete();
                    
                    // Delete analytics if exists
                    $import->analytics()->delete();
                    
                    // Delete the import record
                    $import->delete();
                }
            }

            // Delete eligibility assessments
            $prospect->eligibilityAssessments()->delete();

            // Delete uploaded files from storage
            if ($prospect->uploaded_statement_path && Storage::exists($prospect->uploaded_statement_path)) {
                Storage::delete($prospect->uploaded_statement_path);
            }

            // Delete the prospect
            $prospect->delete();

            DB::commit();

            Log::info('Prospect deleted', [
                'prospect_id' => $prospect->id,
                'deleted_by' => auth()->id(),
                'name' => $prospect->first_name . ' ' . $prospect->last_name,
            ]);

            return redirect()->route('prospects.index')
                ->with('success', 'Prospect deleted successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete prospect', [
                'prospect_id' => $prospect->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->with('error', 'Failed to delete prospect: ' . $e->getMessage());
        }
    }
}

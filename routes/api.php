<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('v1')->group(function () {
    // Authentication routes
    Route::post('/auth/login', [\App\Http\Controllers\AuthController::class, 'login']);
    Route::post('/auth/register', [\App\Http\Controllers\AuthController::class, 'register']);
});

// Protected routes (require authentication)
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout']);
        Route::post('/logout-all', [\App\Http\Controllers\AuthController::class, 'logoutAll']);
        Route::get('/me', [\App\Http\Controllers\AuthController::class, 'me']);
        Route::put('/password', [\App\Http\Controllers\AuthController::class, 'updatePassword']);
    });

    // Users & Roles (with permission middleware)
    Route::prefix('users')->middleware('permission:users.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\UserController::class, 'index']);
        Route::get('/{user}', [\App\Http\Controllers\UserController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\UserController::class, 'store'])->middleware('permission:users.create');
        Route::put('/{user}', [\App\Http\Controllers\UserController::class, 'update'])->middleware('permission:users.edit');
        Route::delete('/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->middleware('permission:users.delete');
        
        // Role management
        Route::post('/{user}/assign-role', [\App\Http\Controllers\UserController::class, 'assignRole'])->middleware('permission:users.manage-roles');
        Route::post('/{user}/remove-role', [\App\Http\Controllers\UserController::class, 'removeRole'])->middleware('permission:users.manage-roles');
    });

    // Roles
    Route::get('/roles', [\App\Http\Controllers\UserController::class, 'getRoles']);

    // Institutions (Provider Super Admin can view all, others view their own)
    Route::prefix('institutions')->group(function () {
        // Provider Super Admin - manage all institutions
        Route::middleware('role:provider-super-admin')->group(function () {
            Route::get('/', [\App\Http\Controllers\InstitutionController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\InstitutionController::class, 'store'])->middleware('permission:institutions.create');
            Route::get('/{institution}', [\App\Http\Controllers\InstitutionController::class, 'show']);
            Route::put('/{institution}', [\App\Http\Controllers\InstitutionController::class, 'update'])->middleware('permission:institutions.edit');
            Route::delete('/{institution}', [\App\Http\Controllers\InstitutionController::class, 'destroy'])->middleware('permission:institutions.delete');
            Route::post('/{institution}/toggle-status', [\App\Http\Controllers\InstitutionController::class, 'toggleStatus']);
        });

        // Institution settings & branding (accessible by institution admins)
        Route::middleware('permission:institutions.view')->group(function () {
            Route::get('/settings', [\App\Http\Controllers\InstitutionController::class, 'getSettings']);
            Route::put('/settings', [\App\Http\Controllers\InstitutionController::class, 'updateSettings'])->middleware('permission:institutions.edit');
            
            Route::get('/branding', [\App\Http\Controllers\InstitutionController::class, 'getBranding']);
            Route::put('/branding', [\App\Http\Controllers\InstitutionController::class, 'updateBranding'])->middleware('permission:institutions.manage-branding');
            Route::post('/branding/logo', [\App\Http\Controllers\InstitutionController::class, 'uploadLogo'])->middleware('permission:institutions.manage-branding');
        });
    });

    // Loan Products
    Route::prefix('loan-products')->middleware('permission:loan-products.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\LoanProductController::class, 'index']);
        Route::get('/{loanProduct}', [\App\Http\Controllers\LoanProductController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\LoanProductController::class, 'store'])->middleware('permission:loan-products.create');
        Route::put('/{loanProduct}', [\App\Http\Controllers\LoanProductController::class, 'update'])->middleware('permission:loan-products.edit');
        Route::delete('/{loanProduct}', [\App\Http\Controllers\LoanProductController::class, 'destroy'])->middleware('permission:loan-products.delete');
        
        // Product actions
        Route::post('/{loanProduct}/toggle-status', [\App\Http\Controllers\LoanProductController::class, 'toggleStatus'])->middleware('permission:loan-products.toggle-status');
        Route::post('/{loanProduct}/archive', [\App\Http\Controllers\LoanProductController::class, 'archive'])->middleware('permission:loan-products.edit');
        Route::post('/{loanProduct}/duplicate', [\App\Http\Controllers\LoanProductController::class, 'duplicate'])->middleware('permission:loan-products.create');
        
        // Calculations
        Route::post('/{loanProduct}/calculate-installment', [\App\Http\Controllers\LoanProductController::class, 'calculateInstallment']);
    });

    // Customers & KYC Management
    Route::prefix('customers')->middleware('permission:customers.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\CustomerController::class, 'index']);
        Route::get('/{customer}', [\App\Http\Controllers\CustomerController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\CustomerController::class, 'store'])->middleware('permission:customers.create');
        Route::put('/{customer}', [\App\Http\Controllers\CustomerController::class, 'update'])->middleware('permission:customers.edit');
        Route::delete('/{customer}', [\App\Http\Controllers\CustomerController::class, 'destroy'])->middleware('permission:customers.delete');
        
        // Customer actions
        Route::post('/{customer}/verify-kyc', [\App\Http\Controllers\CustomerController::class, 'verifyKyc'])->middleware('permission:customers.verify-kyc');
        Route::post('/{customer}/toggle-status', [\App\Http\Controllers\CustomerController::class, 'toggleStatus'])->middleware('permission:customers.edit');
        
        // KYC Documents for a customer
        Route::prefix('{customer}/kyc-documents')->group(function () {
            Route::get('/', [\App\Http\Controllers\KycDocumentController::class, 'index']);
            Route::post('/', [\App\Http\Controllers\KycDocumentController::class, 'store'])->middleware('permission:customers.manage-kyc');
        });
    });

    // KYC Documents (direct access)
    Route::prefix('kyc-documents')->middleware('permission:customers.view')->group(function () {
        Route::get('/{kycDocument}', [\App\Http\Controllers\KycDocumentController::class, 'show']);
        Route::put('/{kycDocument}', [\App\Http\Controllers\KycDocumentController::class, 'update'])->middleware('permission:customers.manage-kyc');
        Route::delete('/{kycDocument}', [\App\Http\Controllers\KycDocumentController::class, 'destroy'])->middleware('permission:customers.manage-kyc');
        
        // Verification actions
        Route::post('/{kycDocument}/verify', [\App\Http\Controllers\KycDocumentController::class, 'verify'])->middleware('permission:customers.verify-kyc');
        Route::post('/{kycDocument}/reject', [\App\Http\Controllers\KycDocumentController::class, 'reject'])->middleware('permission:customers.verify-kyc');
        Route::get('/{kycDocument}/download', [\App\Http\Controllers\KycDocumentController::class, 'download']);
    });

    // Bank Statements & Analytics
    Route::prefix('bank-statements')->middleware('permission:customers.view')->group(function () {
        Route::get('/', [\App\Http\Controllers\BankStatementController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\BankStatementController::class, 'store'])->middleware('permission:customers.manage-kyc');
        Route::get('/{bankStatementImport}', [\App\Http\Controllers\BankStatementController::class, 'show']);
        Route::delete('/{bankStatementImport}', [\App\Http\Controllers\BankStatementController::class, 'destroy'])->middleware('permission:customers.manage-kyc');
        
        // Transactions & Analytics
        Route::get('/{bankStatementImport}/transactions', [\App\Http\Controllers\BankStatementController::class, 'transactions']);
        Route::get('/{bankStatementImport}/analytics', [\App\Http\Controllers\BankStatementController::class, 'analytics']);
        Route::post('/{bankStatementImport}/recompute-analytics', [\App\Http\Controllers\BankStatementController::class, 'recomputeAnalytics']);
        Route::get('/{bankStatementImport}/download', [\App\Http\Controllers\BankStatementController::class, 'download']);
        
        // Customer statistics
        Route::get('/customers/{customer}/stats', [\App\Http\Controllers\BankStatementController::class, 'customerStats']);
    });

    // Applications
    Route::apiResource('applications', \App\Http\Controllers\ApplicationController::class)
        ->names([
            'index' => 'api.applications.index',
            'store' => 'api.applications.store',
            'show' => 'api.applications.show',
            'update' => 'api.applications.update',
            'destroy' => 'api.applications.destroy',
        ]);
    Route::prefix('applications/{application}')->group(function () {
        Route::post('/submit', [\App\Http\Controllers\ApplicationController::class, 'submit']);
        
        // Bank Statement
        Route::post('/bank-statement/upload', [\App\Http\Controllers\BankStatementController::class, 'upload']);
        Route::get('/bank-statement/imports', [\App\Http\Controllers\BankStatementController::class, 'getImports']);
        Route::get('/bank-statement/imports/{import}/status', [\App\Http\Controllers\BankStatementController::class, 'getImportStatus']);
        Route::get('/bank-statement/analytics', [\App\Http\Controllers\BankStatementController::class, 'getAnalytics']);
        
        // Eligibility Assessment
        Route::post('/eligibility/run', [\App\Http\Controllers\EligibilityController::class, 'runAssessment']);
        Route::get('/eligibility/latest', [\App\Http\Controllers\EligibilityController::class, 'getLatest']);
        Route::get('/eligibility/history', [\App\Http\Controllers\EligibilityController::class, 'getHistory']);
        Route::post('/eligibility/stress-test', [\App\Http\Controllers\EligibilityController::class, 'runStressTest']);
        Route::get('/eligibility/max-loan', [\App\Http\Controllers\EligibilityController::class, 'getMaxLoanRecommendations']);
        Route::get('/eligibility/summary', [\App\Http\Controllers\EligibilityController::class, 'getSummary']);
        
        // Underwriting Decision
        Route::post('/underwriting/submit', [\App\Http\Controllers\UnderwritingController::class, 'submitDecision']);
        Route::get('/underwriting/history', [\App\Http\Controllers\UnderwritingController::class, 'getDecisionHistory']);
        
        // Loan Creation
        Route::post('/create-loan', [\App\Http\Controllers\LoanController::class, 'createFromApplication'])
            ->middleware('permission:loans.create');
    });

    // Underwriting Decisions (Workflow Management)
    Route::prefix('underwriting')->middleware('permission:applications.view')->group(function () {
        // Queue Management
        Route::get('/pending-reviews', [\App\Http\Controllers\UnderwritingController::class, 'getPendingReviews']);
        Route::get('/pending-approvals', [\App\Http\Controllers\UnderwritingController::class, 'getPendingApprovals']);
        
        // Decision Management
        Route::get('/decisions/{decision}', [\App\Http\Controllers\UnderwritingController::class, 'getDecision']);
        
        // Workflow Actions (Credit Officer)
        Route::post('/decisions/{decision}/start-review', [\App\Http\Controllers\UnderwritingController::class, 'startReview'])
            ->middleware('role:credit-officer|institution-admin|provider-super-admin');
        Route::post('/decisions/{decision}/complete-review', [\App\Http\Controllers\UnderwritingController::class, 'completeReview'])
            ->middleware('role:credit-officer|institution-admin|provider-super-admin');
        
        // Workflow Actions (Supervisor/Manager)
        Route::post('/decisions/{decision}/approve', [\App\Http\Controllers\UnderwritingController::class, 'approveDecision'])
            ->middleware('role:supervisor|institution-admin|provider-super-admin');
        Route::post('/decisions/{decision}/decline', [\App\Http\Controllers\UnderwritingController::class, 'declineDecision'])
            ->middleware('role:supervisor|institution-admin|provider-super-admin');
        
        // Override Management
        Route::post('/decisions/{decision}/request-override', [\App\Http\Controllers\UnderwritingController::class, 'requestOverride'])
            ->middleware('role:credit-officer|institution-admin|provider-super-admin');
        Route::post('/decisions/{decision}/approve-override', [\App\Http\Controllers\UnderwritingController::class, 'approveOverride'])
            ->middleware('role:supervisor|institution-admin|provider-super-admin');
        Route::post('/decisions/{decision}/decline-override', [\App\Http\Controllers\UnderwritingController::class, 'declineOverride'])
            ->middleware('role:supervisor|institution-admin|provider-super-admin');
    });

    // Loans Management
    Route::prefix('loans')->middleware('permission:applications.view')->group(function () {
        // List & View
        Route::get('/', [\App\Http\Controllers\LoanController::class, 'index']);
        Route::get('/{loan}', [\App\Http\Controllers\LoanController::class, 'show']);
        Route::get('/{loan}/summary', [\App\Http\Controllers\LoanController::class, 'getSummary']);
        
        // Loan Lifecycle
        Route::post('/{loan}/disburse', [\App\Http\Controllers\LoanController::class, 'disburse'])
            ->middleware('permission:loans.disburse');
        Route::post('/{loan}/close', [\App\Http\Controllers\LoanController::class, 'close'])
            ->middleware('permission:loans.manage');
        
        // Schedule
        Route::get('/{loan}/schedule', [\App\Http\Controllers\LoanController::class, 'getSchedule']);
        
        // Early Settlement
        Route::post('/{loan}/calculate-early-settlement', [\App\Http\Controllers\LoanController::class, 'calculateEarlySettlement']);
        
        // Aging Update
        Route::post('/{loan}/update-aging', [\App\Http\Controllers\LoanController::class, 'updateAging']);
        
        // Repayments
        Route::get('/{loan}/repayments', [\App\Http\Controllers\RepaymentController::class, 'getLoanRepayments']);
        
        // Collections (future)
        Route::get('/{loan}/collections-actions', [\App\Http\Controllers\CollectionsController::class, 'getActions']);
        Route::post('/collections-actions', [\App\Http\Controllers\CollectionsController::class, 'logAction']);
        Route::get('/promise-to-pay', [\App\Http\Controllers\CollectionsController::class, 'getPromises']);
        Route::post('/promise-to-pay', [\App\Http\Controllers\CollectionsController::class, 'createPromise']);
        Route::put('/promise-to-pay/{promise}', [\App\Http\Controllers\CollectionsController::class, 'updatePromise']);
    });

    // Repayments & Imports
    Route::prefix('repayments')->middleware('permission:applications.view')->group(function () {
        // Upload repayment statement
        Route::post('/upload', [\App\Http\Controllers\RepaymentController::class, 'uploadStatement'])
            ->middleware('permission:repayments.upload');
        
        // Import batch management
        Route::get('/batches', [\App\Http\Controllers\RepaymentController::class, 'getBatchHistory']);
        Route::get('/batches/{batch}', [\App\Http\Controllers\RepaymentController::class, 'getBatchStatus']);
        
        // Individual repayment management
        Route::get('/{repayment}', [\App\Http\Controllers\RepaymentController::class, 'show']);
        Route::post('/{repayment}/reverse', [\App\Http\Controllers\RepaymentController::class, 'reversePayment'])
            ->middleware('permission:repayments.reverse');
    });

    // Portfolio Monitoring
    Route::prefix('portfolio')->middleware('permission:applications.view')->group(function () {
        // Current snapshot
        Route::get('/snapshot', [\App\Http\Controllers\PortfolioController::class, 'getCurrentSnapshot']);
        
        // Distribution & Composition
        Route::get('/aging', [\App\Http\Controllers\PortfolioController::class, 'getAgingDistribution']);
        Route::get('/composition', [\App\Http\Controllers\PortfolioController::class, 'getComposition']);
        
        // Risk Metrics
        Route::get('/par', [\App\Http\Controllers\PortfolioController::class, 'getPARMetrics']);
        Route::get('/npl', [\App\Http\Controllers\PortfolioController::class, 'getNPLMetrics']);
        Route::get('/collection-rate', [\App\Http\Controllers\PortfolioController::class, 'getCollectionRate']);
        
        // Trends & Historical Data
        Route::get('/trends', [\App\Http\Controllers\PortfolioController::class, 'getTrends']);
        
        // Manual snapshot computation
        Route::post('/compute-snapshot', [\App\Http\Controllers\PortfolioController::class, 'computeSnapshot'])
            ->middleware('permission:portfolio.manage');
    });

    // Collections Management (Phase 10)
    Route::prefix('collections')->middleware('permission:applications.view')->group(function () {
        // Collections Queue
        Route::post('/{institutionId}/queue/generate', [\App\Http\Controllers\CollectionsController::class, 'generateQueue'])
            ->middleware('permission:collections.manage');
        Route::get('/{institutionId}/queue', [\App\Http\Controllers\CollectionsController::class, 'getQueue']);
        Route::post('/{institutionId}/queue/assign', [\App\Http\Controllers\CollectionsController::class, 'assignToOfficers'])
            ->middleware('permission:collections.manage');
        Route::post('/{institutionId}/queue/auto-distribute', [\App\Http\Controllers\CollectionsController::class, 'autoDistribute'])
            ->middleware('permission:collections.manage');
        Route::put('/{institutionId}/queue/{queueId}/status', [\App\Http\Controllers\CollectionsController::class, 'updateQueueStatus'])
            ->middleware('permission:collections.manage');
        Route::post('/{institutionId}/queue/{queueId}/escalate', [\App\Http\Controllers\CollectionsController::class, 'escalateToLegal'])
            ->middleware('permission:collections.manage');
        
        // Collections Actions
        Route::post('/{institutionId}/actions', [\App\Http\Controllers\CollectionsController::class, 'logAction'])
            ->middleware('permission:collections.actions');
        Route::get('/{institutionId}/loans/{loanId}/history', [\App\Http\Controllers\CollectionsController::class, 'getLoanHistory']);
        
        // Promise to Pay
        Route::post('/{institutionId}/promise-to-pay', [\App\Http\Controllers\CollectionsController::class, 'createPromiseToPay'])
            ->middleware('permission:collections.actions');
        Route::get('/{institutionId}/promise-to-pay/{ptpId}', [\App\Http\Controllers\CollectionsController::class, 'getPromiseToPay']);
        Route::get('/{institutionId}/promise-to-pay', [\App\Http\Controllers\CollectionsController::class, 'getPromisesToPay']);
        Route::put('/{institutionId}/promise-to-pay/{ptpId}/status', [\App\Http\Controllers\CollectionsController::class, 'updatePromiseStatus'])
            ->middleware('permission:collections.actions');
        
        // Reporting & Analytics
        Route::get('/{institutionId}/metrics', [\App\Http\Controllers\CollectionsController::class, 'getPerformanceMetrics']);
        Route::get('/{institutionId}/officers/{officerId}/performance', [\App\Http\Controllers\CollectionsController::class, 'getOfficerPerformance']);
        Route::get('/{institutionId}/action-effectiveness', [\App\Http\Controllers\CollectionsController::class, 'getActionEffectiveness']);
    });

    // Reports & Analytics (Phase 11)
    Route::prefix('reports/{institutionId}')->middleware('permission:applications.view')->group(function () {
        // PDF Report Generation
        Route::get('/eligibility/{applicationId}', [\App\Http\Controllers\ReportController::class, 'generateEligibilityReport'])
            ->middleware('permission:reports.generate');
        Route::get('/bank-statement/{statementId}', [\App\Http\Controllers\ReportController::class, 'generateBankStatementReport'])
            ->middleware('permission:reports.generate');
        Route::get('/application/{applicationId}/summary', [\App\Http\Controllers\ReportController::class, 'generateApplicationSummary'])
            ->middleware('permission:reports.generate');
        Route::get('/application/{applicationId}/affordability', [\App\Http\Controllers\ReportController::class, 'generateAffordabilityReport'])
            ->middleware('permission:reports.generate');
        Route::post('/portfolio/monthly-pack', [\App\Http\Controllers\ReportController::class, 'generatePortfolioPack'])
            ->middleware('permission:reports.generate');
        
        // JSON Analytics Reports
        Route::get('/analytics/approval-rate', [\App\Http\Controllers\ReportController::class, 'getApprovalRateReport'])
            ->middleware('permission:reports.view');
        Route::get('/analytics/risk-distribution', [\App\Http\Controllers\ReportController::class, 'getRiskDistributionReport'])
            ->middleware('permission:reports.view');
        Route::get('/analytics/decline-reasons', [\App\Http\Controllers\ReportController::class, 'getDeclineReasonAnalysis'])
            ->middleware('permission:reports.view');
        
        // Excel Exports
        Route::post('/export/applications', [\App\Http\Controllers\ReportController::class, 'exportApplications'])
            ->middleware('permission:reports.export');
        Route::post('/export/loans', [\App\Http\Controllers\ReportController::class, 'exportLoans'])
            ->middleware('permission:reports.export');
        Route::post('/export/repayments', [\App\Http\Controllers\ReportController::class, 'exportRepayments'])
            ->middleware('permission:reports.export');
        Route::post('/export/collections-queue', [\App\Http\Controllers\ReportController::class, 'exportCollectionsQueue'])
            ->middleware('permission:reports.export');
        Route::post('/export/portfolio-summary', [\App\Http\Controllers\ReportController::class, 'exportPortfolioSummary'])
            ->middleware('permission:reports.export');
    });

    // Dashboard (Phase 11)
    Route::prefix('dashboard/{institutionId}')->middleware('permission:applications.view')->group(function () {
        // Executive Dashboard
        Route::get('/executive', [\App\Http\Controllers\ReportController::class, 'getExecutiveDashboard']);
        
        // Portfolio Performance
        Route::get('/portfolio-performance', [\App\Http\Controllers\ReportController::class, 'getPortfolioPerformance']);
        
        // Collections Performance
        Route::get('/collections-performance', [\App\Http\Controllers\ReportController::class, 'getCollectionsPerformance']);
        
        // Risk Trends
        Route::get('/risk-trends', [\App\Http\Controllers\ReportController::class, 'getRiskTrends']);
        
        // Monthly KPIs
        Route::get('/monthly-kpis', [\App\Http\Controllers\ReportController::class, 'getMonthlyKPIs']);
    });

    // Audit Logs (Phase 12)
    Route::prefix('audit-logs/{institutionId}')->middleware('permission:applications.view')->group(function () {
        // List & Filter
        Route::get('/', [\App\Http\Controllers\AuditLogController::class, 'index']);
        Route::get('/{logId}', [\App\Http\Controllers\AuditLogController::class, 'show']);
        
        // Entity Audit Trail
        Route::get('/entity/{entityType}/{entityId}', [\App\Http\Controllers\AuditLogController::class, 'getEntityAudit']);
        
        // User Activity
        Route::get('/user/{userId}/activity', [\App\Http\Controllers\AuditLogController::class, 'getUserActivity']);
        
        // Statistics & Analytics
        Route::get('/statistics', [\App\Http\Controllers\AuditLogController::class, 'getStatistics']);
        Route::get('/critical-events', [\App\Http\Controllers\AuditLogController::class, 'getCriticalEvents']);
        Route::get('/timeline', [\App\Http\Controllers\AuditLogController::class, 'getTimeline']);
        
        // Export
        Route::post('/export', [\App\Http\Controllers\AuditLogController::class, 'export'])
            ->middleware('permission:reports.export');
    });

    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index']);
});

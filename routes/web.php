<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return Inertia::render('Auth/Login');
    })->name('login');
    
    Route::get('/login', function () {
        return Inertia::render('Auth/Login');
    });
    
    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            return redirect()->intended('/dashboard');
        }

        throw ValidationException::withMessages([
            'email' => 'The provided credentials do not match our records.',
        ]);
    });
});

// Authenticated Routes
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'executive'])
        ->name('dashboard');
    
    // User Management
    Route::resource('users', \App\Http\Controllers\Web\UserController::class);
    Route::put('/users/{user}/status', [\App\Http\Controllers\Web\UserController::class, 'updateStatus'])
        ->name('users.status');
    Route::post('/users/{user}/password-reset', [\App\Http\Controllers\Web\UserController::class, 'sendPasswordReset'])
        ->name('users.password-reset');
    
    // Loan Products
    Route::resource('loan-products', \App\Http\Controllers\Web\LoanProductController::class);
    Route::put('/loan-products/{loanProduct}/status', [\App\Http\Controllers\Web\LoanProductController::class, 'updateStatus'])
        ->name('loan-products.status');
    Route::put('/loan-products/{loanProduct}/activate', [\App\Http\Controllers\Web\LoanProductController::class, 'activate'])
        ->name('loan-products.activate');
    Route::put('/loan-products/{loanProduct}/deactivate', [\App\Http\Controllers\Web\LoanProductController::class, 'deactivate'])
        ->name('loan-products.deactivate');
    
    // Customers
    Route::resource('customers', \App\Http\Controllers\Web\CustomerController::class);
    Route::put('/customers/{customer}/status', [\App\Http\Controllers\Web\CustomerController::class, 'updateStatus'])
        ->name('customers.status');
    Route::post('/customers/{customer}/verify-kyc', [\App\Http\Controllers\Web\CustomerController::class, 'verifyKyc'])
        ->name('customers.verify-kyc');
    
    // KYC Documents for customers (web routes)
    Route::post('/customers/{customer}/kyc-documents', [\App\Http\Controllers\KycDocumentController::class, 'store'])
        ->name('customers.kyc-documents.store');
    Route::delete('/kyc-documents/{kycDocument}', [\App\Http\Controllers\KycDocumentController::class, 'destroy'])
        ->name('kyc-documents.destroy');
    Route::get('/kyc-documents/{kycDocument}/download', [\App\Http\Controllers\KycDocumentController::class, 'download'])
        ->name('kyc-documents.download');
    
    // Applications
    // Define specific action routes BEFORE the resource to avoid conflicts
    Route::get('/applications/{application}/disburse', [\App\Http\Controllers\Web\LoanController::class, 'showDisbursement'])
        ->name('loans.show-disbursement')
        ->where('application', '[0-9]+');
    Route::post('/applications/{application}/disburse', [\App\Http\Controllers\Web\LoanController::class, 'disburse'])
        ->name('loans.disburse')
        ->where('application', '[0-9]+');
    Route::post('/applications/{application}/start-review', [\App\Http\Controllers\ApplicationController::class, 'startReview'])
        ->name('applications.start-review')
        ->where('application', '[0-9]+');
    Route::post('/applications/{application}/approve', [\App\Http\Controllers\ApplicationController::class, 'approve'])
        ->name('applications.approve')
        ->where('application', '[0-9]+');
    Route::post('/applications/{application}/reject', [\App\Http\Controllers\ApplicationController::class, 'reject'])
        ->name('applications.reject')
        ->where('application', '[0-9]+');
    
    // Resource routes with constraint to only match numeric IDs
    Route::resource('applications', \App\Http\Controllers\ApplicationController::class)
        ->where(['application' => '[0-9]+']);
    
    // Underwriting
    Route::get('/underwriting/pending-reviews', [\App\Http\Controllers\Web\UnderwritingController::class, 'pendingReviews'])
        ->name('underwriting.pending-reviews');
    Route::get('/underwriting/pending-approvals', [\App\Http\Controllers\Web\UnderwritingController::class, 'pendingApprovals'])
        ->name('underwriting.pending-approvals');
    Route::get('/underwriting/{decision}/review', [\App\Http\Controllers\Web\UnderwritingController::class, 'showReview'])
        ->name('underwriting.show-review');
    Route::get('/underwriting/{decision}/approve', [\App\Http\Controllers\Web\UnderwritingController::class, 'showApproval'])
        ->name('underwriting.show-approval');
    Route::post('/underwriting/{decision}/start-review', [\App\Http\Controllers\UnderwritingController::class, 'startReview'])
        ->name('underwriting.start-review');
    Route::post('/underwriting/{decision}/complete-review', [\App\Http\Controllers\UnderwritingController::class, 'completeReview'])
        ->name('underwriting.complete-review');
    Route::post('/underwriting/{decision}/approve-decision', [\App\Http\Controllers\UnderwritingController::class, 'approveDecision'])
        ->name('underwriting.approve-decision');
    Route::post('/underwriting/{decision}/decline-decision', [\App\Http\Controllers\UnderwritingController::class, 'declineDecision'])
        ->name('underwriting.decline-decision');
    Route::post('/underwriting/{decision}/request-override', [\App\Http\Controllers\UnderwritingController::class, 'requestOverride'])
        ->name('underwriting.request-override');
    Route::post('/underwriting/{decision}/approve-override', [\App\Http\Controllers\UnderwritingController::class, 'approveOverride'])
        ->name('underwriting.approve-override');
    Route::post('/underwriting/{decision}/decline-override', [\App\Http\Controllers\UnderwritingController::class, 'declineOverride'])
        ->name('underwriting.decline-override');
    
    // Loans
    Route::get('/loans', [\App\Http\Controllers\Web\LoanController::class, 'index'])
        ->name('loans.index');
    Route::get('/loans/disbursements', [\App\Http\Controllers\Web\LoanController::class, 'disbursements'])
        ->name('loans.disbursements');
    Route::get('/loans/repayments', [\App\Http\Controllers\Web\LoanController::class, 'repayments'])
        ->name('loans.repayments');
    Route::get('/loans/{loan}', [\App\Http\Controllers\Web\LoanController::class, 'show'])
        ->name('loans.show');
    Route::post('/loans/{loan}/add-payment', [\App\Http\Controllers\Web\LoanController::class, 'addPayment'])
        ->name('loans.add-payment');
    Route::post('/loans/{loan}/activate', [\App\Http\Controllers\Web\LoanController::class, 'activate'])
        ->name('loans.activate');
    Route::post('/loans/{loan}/write-off', [\App\Http\Controllers\Web\LoanController::class, 'writeOff'])
        ->name('loans.write-off');
    Route::post('/loans/{loan}/mark-defaulted', [\App\Http\Controllers\Web\LoanController::class, 'markAsDefaulted'])
        ->name('loans.mark-defaulted');
    Route::post('/loans/{loan}/close', [\App\Http\Controllers\Web\LoanController::class, 'close'])
        ->name('loans.close');
    
    // Collections
    Route::get('/collections', [\App\Http\Controllers\Web\CollectionsController::class, 'index'])
        ->name('collections.index');
    Route::post('/collections/generate', [\App\Http\Controllers\Web\CollectionsController::class, 'generateQueue'])
        ->name('collections.generate');
    
    // Reports
    Route::get('/reports/portfolio', function () {
        return Inertia::render('Reports/Portfolio');
    })->name('reports.portfolio');
    
    Route::get('/reports/analytics', function () {
        return Inertia::render('Reports/Analytics');
    })->name('reports.analytics');
    
    Route::get('/reports/exports', function () {
        return Inertia::render('Reports/Exports');
    })->name('reports.exports');
    
    // Profile & Settings
    Route::get('/profile', function () {
        return Inertia::render('Profile/Edit');
    })->name('profile.edit');
    
    Route::get('/settings', function () {
        return Inertia::render('Settings/Index');
    })->name('settings.index');
    
    // Pre-Qualification (Prospects)
    Route::get('/pre-qualify', [\App\Http\Controllers\Web\ProspectController::class, 'start'])
        ->name('pre-qualify.start');
    Route::post('/pre-qualify', [\App\Http\Controllers\Web\ProspectController::class, 'store'])
        ->name('pre-qualify.store');
    Route::get('/pre-qualify/{prospect}/statement', [\App\Http\Controllers\Web\ProspectController::class, 'statement'])
        ->name('pre-qualify.statement');
    Route::post('/pre-qualify/{prospect}/statement', [\App\Http\Controllers\Web\ProspectController::class, 'uploadStatement'])
        ->name('pre-qualify.upload-statement');
    Route::get('/pre-qualify/{prospect}/processing', [\App\Http\Controllers\Web\ProspectController::class, 'processing'])
        ->name('pre-qualify.processing');
    Route::get('/pre-qualify/{prospect}/status', [\App\Http\Controllers\Web\ProspectController::class, 'checkStatus'])
        ->name('pre-qualify.check-status');
    Route::post('/pre-qualify/{prospect}/cancel-processing', [\App\Http\Controllers\Web\ProspectController::class, 'cancelProcessing'])
        ->name('pre-qualify.cancel-processing');
    Route::post('/pre-qualify/{prospect}/retry-analytics', [\App\Http\Controllers\Web\ProspectController::class, 'retryAnalytics'])
        ->name('pre-qualify.retry-analytics');
    Route::get('/pre-qualify/{prospect}/results', [\App\Http\Controllers\Web\ProspectController::class, 'results'])
        ->name('pre-qualify.results');
    Route::post('/pre-qualify/{prospect}/amend-and-reassess', [\App\Http\Controllers\Web\ProspectController::class, 'amendAndReassess'])
        ->name('pre-qualify.amend-and-reassess');
    Route::post('/pre-qualify/{prospect}/override-decision', [\App\Http\Controllers\Web\ProspectController::class, 'overrideDecision'])
        ->name('pre-qualify.override-decision');
    Route::post('/pre-qualify/{prospect}/convert', [\App\Http\Controllers\Web\ProspectController::class, 'convertToCustomer'])
        ->name('pre-qualify.convert');
    
    // Prospects Management
    Route::get('/prospects', [\App\Http\Controllers\Web\ProspectController::class, 'index'])
        ->name('prospects.index');
    Route::get('/prospects/{prospect}', [\App\Http\Controllers\Web\ProspectController::class, 'show'])
        ->name('prospects.show');
    
    // Logout
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});


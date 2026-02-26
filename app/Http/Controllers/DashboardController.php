<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display executive dashboard
     */
    public function executive(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        try {
            // Get dashboard data
            $dashboardData = $this->dashboardService->getExecutiveDashboard($institutionId);
            
            // Format stats for the view
            $stats = [
                'applications_total' => $dashboardData['applications']['total'] ?? 0,
                'applications_pending' => $dashboardData['applications']['pending'] ?? 0,
                'applications_approved' => $dashboardData['applications']['approved'] ?? 0,
                'applications_declined' => $dashboardData['applications']['declined'] ?? 0,
                'approval_rate' => $dashboardData['applications']['approval_rate'] ?? 0,
                
                'loans_total' => $dashboardData['portfolio']['total_loans'] ?? 0,
                'loans_active' => $dashboardData['portfolio']['active_loans'] ?? 0,
                'loans_in_arrears' => $dashboardData['portfolio']['loans_in_arrears'] ?? 0,
                
                'portfolio_value' => $dashboardData['portfolio']['total_outstanding'] ?? 0,
                'portfolio_disbursed' => $dashboardData['portfolio']['total_disbursed'] ?? 0,
                'portfolio_collected' => $dashboardData['portfolio']['total_collected'] ?? 0,
                'portfolio_arrears' => $dashboardData['portfolio']['total_arrears'] ?? 0,
                
                'npl_count' => $dashboardData['portfolio']['npl_count'] ?? 0,
                'npl_ratio' => $dashboardData['portfolio']['npl_ratio'] ?? 0,
                'par30_ratio' => $dashboardData['portfolio']['par30_ratio'] ?? 0,
                
                'collections_queue' => $dashboardData['collections']['total_in_queue'] ?? 0,
                'collections_critical' => $dashboardData['collections']['critical_items'] ?? 0,
                'collections_arrears' => $dashboardData['collections']['total_arrears_in_queue'] ?? 0,
                'avg_dpd' => $dashboardData['collections']['avg_days_past_due'] ?? 0,
                
                'collection_rate' => $dashboardData['portfolio']['total_disbursed'] > 0 
                    ? round(($dashboardData['portfolio']['total_collected'] / $dashboardData['portfolio']['total_disbursed']) * 100, 2) 
                    : 0,
            ];
            
            // Get recent applications
            $recentApplications = \App\Models\Application::where('institution_id', $institutionId)
                ->with(['customer', 'loanProduct'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($app) {
                    return [
                        'id' => $app->id,
                        'application_number' => $app->application_number,
                        'customer_name' => $app->customer->first_name . ' ' . $app->customer->last_name,
                        'customer_email' => $app->customer->email,
                        'amount' => $app->requested_amount,
                        'product' => $app->loanProduct->name ?? 'N/A',
                        'status' => $app->status->value,
                        'created_at' => $app->created_at->format('Y-m-d')
                    ];
                });

            return Inertia::render('Dashboard/Executive', [
                'stats' => $stats,
                'recentApplications' => $recentApplications,
                'trends' => $dashboardData['trends'] ?? [],
            ]);
        } catch (\Exception $e) {
            // If dashboard service fails, return with empty data
            return Inertia::render('Dashboard/Executive', [
                'stats' => [
                    'applications_total' => 0,
                    'loans_active' => 0,
                    'portfolio_value' => 0,
                    'collection_rate' => 0,
                ],
                'recentApplications' => [],
                'trends' => [],
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display portfolio dashboard
     */
    public function portfolio(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        $performance = $this->dashboardService->getPortfolioPerformance($institutionId);

        return Inertia::render('Dashboard/Portfolio', [
            'performance' => $performance,
        ]);
    }

    /**
     * Display collections dashboard
     */
    public function collections(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        $performance = $this->dashboardService->getCollectionsPerformance($institutionId);

        return Inertia::render('Dashboard/Collections', [
            'performance' => $performance,
        ]);
    }
}

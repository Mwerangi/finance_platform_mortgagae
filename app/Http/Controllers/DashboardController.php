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
            $stats = $this->dashboardService->getExecutiveDashboard($institutionId);
            
            // Get recent applications (mock data for now)
            $recentApplications = [
                [
                    'id' => 1,
                    'application_number' => 'APP-2026-001',
                    'customer_name' => 'John Doe',
                    'customer_email' => 'john@example.com',
                    'amount' => 50000000,
                    'status' => 'under_review',
                    'created_at' => '2026-02-20'
                ],
                [
                    'id' => 2,
                    'application_number' => 'APP-2026-002',
                    'customer_name' => 'Jane Smith',
                    'customer_email' => 'jane@example.com',
                    'amount' => 75000000,
                    'status' => 'approved',
                    'created_at' => '2026-02-19'
                ],
                [
                    'id' => 3,
                    'application_number' => 'APP-2026-003',
                    'customer_name' => 'Bob Johnson',
                    'customer_email' => 'bob@example.com',
                    'amount' => 30000000,
                    'status' => 'pending',
                    'created_at' => '2026-02-18'
                ],
            ];

            return Inertia::render('Dashboard/Executive', [
                'stats' => $stats,
                'recentApplications' => $recentApplications,
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

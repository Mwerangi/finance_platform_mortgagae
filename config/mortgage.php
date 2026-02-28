<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mortgage Platform Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the core configuration settings for the mortgage
    | eligibility, underwriting, and monitoring platform.
    |
    */

    'upload' => [
        'max_size' => env('MAX_UPLOAD_SIZE', 10240), // KB
        'allowed_extensions' => ['xlsx', 'xls', 'csv'],
        'excel_memory_limit' => env('EXCEL_MEMORY_LIMIT', 2048), // MB
    ],

    'pdf' => [
        'orientation' => env('PDF_ORIENTATION', 'portrait'),
        'paper_size' => env('PDF_PAPER_SIZE', 'a4'),
    ],

    'institution' => [
        'default_timezone' => env('DEFAULT_TIMEZONE', 'Africa/Nairobi'),
        'default_currency' => env('DEFAULT_CURRENCY', 'KES'),
    ],

    'interest' => [
        'models' => [
            'reducing_balance' => 'Reducing Balance',
            'flat_rate' => 'Flat Rate',
        ],
        'default_model' => env('DEFAULT_INTEREST_MODEL', 'reducing_balance'),
        'rate_types' => [
            'fixed' => 'Fixed Rate',
            // 'variable' => 'Variable Rate', // V2
        ],
        'business_safety_factor' => env('DEFAULT_BUSINESS_SAFETY_FACTOR', 0.65),
    ],

    'risk' => [
        'thresholds' => [
            'max_dti' => env('MAX_DTI_RATIO', 0.50), // 50%
            'max_dsr_salary' => env('MAX_DSR_SALARY', 0.40), // 40%
            'max_dsr_business' => env('MAX_DSR_BUSINESS', 0.35), // 35%
            'max_ltv' => env('MAX_LTV_RATIO', 0.80), // 80%
        ],
        'grades' => [
            'A' => ['min_score' => 80, 'label' => 'Low Risk'],
            'B' => ['min_score' => 60, 'label' => 'Medium Risk'],
            'C' => ['min_score' => 40, 'label' => 'High Risk'],
            'D' => ['min_score' => 0, 'label' => 'Very High Risk'],
        ],
    ],

    'stress_test' => [
        'income_drop_percentage' => env('STRESS_INCOME_DROP_PCT', 0.20), // 20%
        'rate_increase_percentage' => env('STRESS_RATE_INCREASE_PCT', 3.00), // 3%
        'scenarios' => [
            'income_drop' => 'Income Drop',
            'rate_increase' => 'Rate Increase',
            'combined' => 'Combined Stress',
        ],
    ],

    'portfolio' => [
        'par_days' => [
            'par30' => env('PAR30_DAYS', 30),
            'par60' => env('PAR60_DAYS', 60),
            'par90' => env('PAR90_DAYS', 90),
        ],
        'npl_days' => env('NPL_DAYS', 90),
        'aging_buckets' => [
            'current' => ['min' => 0, 'max' => 30, 'label' => '0-30 days'],
            'bucket_1' => ['min' => 31, 'max' => 60, 'label' => '31-60 days'],
            'bucket_2' => ['min' => 61, 'max' => 90, 'label' => '61-90 days'],
            'bucket_3' => ['min' => 91, 'max' => null, 'label' => '90+ days'],
        ],
    ],

    'analytics' => [
        'min_statement_months' => 3,
        'recommended_statement_months' => 6,
        'max_statement_months' => 12,
        'income_detection' => [
            'salary_patterns' => ['salary', 'wages', 'payroll', 'net pay', 'gross pay'],
            'business_patterns' => ['sales', 'revenue', 'payment received', 'invoice'],
        ],
        'debt_patterns' => ['loan repayment', 'credit card', 'mortgage', 'finance', 'installment'],
        
        // Pass-through detection thresholds
        'pass_through' => [
            'enabled' => env('PASS_THROUGH_DETECTION_ENABLED', true),
            'amount_tolerance_percentage' => env('PASS_THROUGH_AMOUNT_TOLERANCE', 5), // ±5%
            'time_window_days' => env('PASS_THROUGH_TIME_WINDOW_DAYS', 2), // 0-2 days
            'risk_threshold_ratio' => env('PASS_THROUGH_RISK_THRESHOLD', 0.30), // 30% of total credits
            'risk_weight' => env('PASS_THROUGH_RISK_WEIGHT', 15), // Risk score impact (max 15 points)
        ],
        
        // Loan detection configuration
        'loan_detection' => [
            'require_recurring_pattern' => env('LOAN_REQUIRE_RECURRING', true),
            'min_consistency_score' => env('LOAN_MIN_CONSISTENCY_SCORE', 60), // 0-100
            'max_variance_percentage' => env('LOAN_MAX_VARIANCE_PCT', 15), // Amount variance tolerance
            'known_lenders' => [
                'faulu', 'kcb', 'equity', 'co-operative', 'family bank',
                'ncba', 'stanbic', 'absa', 'dtb', 'branch', 'tala',
                'm-shwari', 'mkopapp', 'timiza', 'fuliza', 'kcb mpesa',
            ],
        ],
        
        // Bulk deposit detection
        'bulk_deposit' => [
            'threshold_multiplier' => env('BULK_DEPOSIT_MULTIPLIER', 2.0), // 2x avg monthly income
        ],
        
        // Volatility thresholds
        'volatility' => [
            'high_threshold' => env('VOLATILITY_HIGH_THRESHOLD', 70), // %
            'moderate_threshold' => env('VOLATILITY_MODERATE_THRESHOLD', 50), // %
            'adjustment_start' => env('VOLATILITY_ADJUSTMENT_START', 60), // % - when to start reducing max loan
        ],
    ],

    'queue' => [
        'retry_after' => env('QUEUE_RETRY_AFTER', 90), // seconds
        'max_tries' => env('QUEUE_MAX_TRIES', 3),
        'queues' => [
            'high' => 'high',
            'default' => 'default',
            'low' => 'low',
        ],
    ],

    'audit' => [
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 365),
        'critical_events' => [
            'application.approved',
            'application.rejected',
            'override.requested',
            'override.approved',
            'loan.created',
            'loan.defaulted',
            'user.created',
            'user.deleted',
            'institution.settings.updated',
        ],
    ],

    'permissions' => [
        'groups' => [
            'users' => 'User Management',
            'institutions' => 'Institution Management',
            'products' => 'Loan Products',
            'customers' => 'Customer Management',
            'applications' => 'Applications',
            'underwriting' => 'Underwriting',
            'loans' => 'Loan Management',
            'monitoring' => 'Portfolio Monitoring',
            'collections' => 'Collections',
            'reports' => 'Reports',
            'audit' => 'Audit Logs',
        ],
    ],

];

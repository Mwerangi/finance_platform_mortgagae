<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // User Management
            ['group' => 'users', 'name' => 'View Users', 'slug' => 'users.view', 'description' => 'View user list and details'],
            ['group' => 'users', 'name' => 'Create Users', 'slug' => 'users.create', 'description' => 'Create new users'],
            ['group' => 'users', 'name' => 'Edit Users', 'slug' => 'users.edit', 'description' => 'Edit existing users'],
            ['group' => 'users', 'name' => 'Delete Users', 'slug' => 'users.delete', 'description' => 'Delete users'],
            ['group' => 'users', 'name' => 'Manage Roles', 'slug' => 'users.manage-roles', 'description' => 'Assign and remove user roles'],

            // Institution Management
            ['group' => 'institutions', 'name' => 'View Institutions', 'slug' => 'institutions.view', 'description' => 'View institution details'],
            ['group' => 'institutions', 'name' => 'Create Institutions', 'slug' => 'institutions.create', 'description' => 'Create new institutions'],
            ['group' => 'institutions', 'name' => 'Edit Institutions', 'slug' => 'institutions.edit', 'description' => 'Edit institution settings'],
            ['group' => 'institutions', 'name' => 'Delete Institutions', 'slug' => 'institutions.delete', 'description' => 'Delete institutions'],
            ['group' => 'institutions', 'name' => 'Manage Branding', 'slug' => 'institutions.manage-branding', 'description' => 'Configure institution branding'],

            // Loan Products
            ['group' => 'loan-products', 'name' => 'View Loan Products', 'slug' => 'loan-products.view', 'description' => 'View loan product list and details'],
            ['group' => 'loan-products', 'name' => 'Create Loan Products', 'slug' => 'loan-products.create', 'description' => 'Create new loan products'],
            ['group' => 'loan-products', 'name' => 'Edit Loan Products', 'slug' => 'loan-products.edit', 'description' => 'Edit loan product configuration'],
            ['group' => 'loan-products', 'name' => 'Delete Loan Products', 'slug' => 'loan-products.delete', 'description' => 'Delete loan products'],
            ['group' => 'loan-products', 'name' => 'Activate/Deactivate Products', 'slug' => 'loan-products.toggle-status', 'description' => 'Activate or deactivate loan products'],

            // Customer Management
            ['group' => 'customers', 'name' => 'View Customers', 'slug' => 'customers.view', 'description' => 'View customer list and profiles'],
            ['group' => 'customers', 'name' => 'Create Customers', 'slug' => 'customers.create', 'description' => 'Create new customers'],
            ['group' => 'customers', 'name' => 'Edit Customers', 'slug' => 'customers.edit', 'description' => 'Edit customer information'],
            ['group' => 'customers', 'name' => 'Delete Customers', 'slug' => 'customers.delete', 'description' => 'Delete customers'],
            ['group' => 'customers', 'name' => 'Manage KYC', 'slug' => 'customers.manage-kyc', 'description' => 'Upload and verify KYC documents'],

            // Applications & Underwriting
            ['group' => 'applications', 'name' => 'View Applications', 'slug' => 'applications.view', 'description' => 'View loan applications'],
            ['group' => 'applications', 'name' => 'Create Applications', 'slug' => 'applications.create', 'description' => 'Create new applications'],
            ['group' => 'applications', 'name' => 'Edit Applications', 'slug' => 'applications.edit', 'description' => 'Edit application details'],
            ['group' => 'applications', 'name' => 'Delete Applications', 'slug' => 'applications.delete', 'description' => 'Delete applications'],
            ['group' => 'applications', 'name' => 'Upload Bank Statements', 'slug' => 'applications.upload-statements', 'description' => 'Upload bank statement files'],
            ['group' => 'applications', 'name' => 'Run Analytics', 'slug' => 'applications.run-analytics', 'description' => 'Trigger bank statement analytics'],
            ['group' => 'applications', 'name' => 'Run Eligibility', 'slug' => 'applications.run-eligibility', 'description' => 'Run eligibility assessments'],
            ['group' => 'applications', 'name' => 'Make Decisions', 'slug' => 'applications.make-decisions', 'description' => 'Approve or reject applications'],
            ['group' => 'applications', 'name' => 'Request Overrides', 'slug' => 'applications.request-overrides', 'description' => 'Request policy overrides'],
            ['group' => 'applications', 'name' => 'Approve Overrides', 'slug' => 'applications.approve-overrides', 'description' => 'Approve or reject override requests'],

            // Loan Management
            ['group' => 'loans', 'name' => 'View Loans', 'slug' => 'loans.view', 'description' => 'View loan list and details'],
            ['group' => 'loans', 'name' => 'Create Loans', 'slug' => 'loans.create', 'description' => 'Create loans from approved applications'],
            ['group' => 'loans', 'name' => 'Edit Loans', 'slug' => 'loans.edit', 'description' => 'Edit loan details'],
            ['group' => 'loans', 'name' => 'Close Loans', 'slug' => 'loans.close', 'description' => 'Close or settle loans'],
            ['group' => 'loans', 'name' => 'View Schedule', 'slug' => 'loans.view-schedule', 'description' => 'View loan repayment schedules'],

            // Repayments & Portfolio
            ['group' => 'repayments', 'name' => 'Upload Repayments', 'slug' => 'repayments.upload', 'description' => 'Upload repayment statements'],
            ['group' => 'repayments', 'name' => 'View Repayments', 'slug' => 'repayments.view', 'description' => 'View repayment history'],
            ['group' => 'repayments', 'name' => 'View Portfolio Metrics', 'slug' => 'repayments.view-metrics', 'description' => 'View PAR, NPL, and portfolio metrics'],

            // Collections
            ['group' => 'collections', 'name' => 'View Collections Queue', 'slug' => 'collections.view-queue', 'description' => 'View collections queue'],
            ['group' => 'collections', 'name' => 'Log Collections Actions', 'slug' => 'collections.log-actions', 'description' => 'Log collections activities'],
            ['group' => 'collections', 'name' => 'Manage PTP', 'slug' => 'collections.manage-ptp', 'description' => 'Create and manage promises to pay'],
            ['group' => 'collections', 'name' => 'View Collections Reports', 'slug' => 'collections.view-reports', 'description' => 'View collections performance reports'],

            // Reports & Analytics
            ['group' => 'reports', 'name' => 'View Reports', 'slug' => 'reports.view', 'description' => 'View and generate reports'],
            ['group' => 'reports', 'name' => 'Export Data', 'slug' => 'reports.export', 'description' => 'Export data to Excel/PDF'],
            ['group' => 'reports', 'name' => 'View Dashboards', 'slug' => 'reports.view-dashboards', 'description' => 'View executive dashboards'],

            // Audit & Compliance
            ['group' => 'audit', 'name' => 'View Audit Logs', 'slug' => 'audit.view-logs', 'description' => 'View system audit logs'],
            ['group' => 'audit', 'name' => 'Export Audit Logs', 'slug' => 'audit.export-logs', 'description' => 'Export audit logs'],
            ['group' => 'audit', 'name' => 'View Compliance Reports', 'slug' => 'audit.view-compliance', 'description' => 'View compliance reports'],

            // System Administration
            ['group' => 'system', 'name' => 'System Settings', 'slug' => 'system.settings', 'description' => 'Manage system-wide settings'],
            ['group' => 'system', 'name' => 'View All Institutions', 'slug' => 'system.view-all-institutions', 'description' => 'View all institutions (Provider Super Admin)'],
            ['group' => 'system', 'name' => 'Deployment Registry', 'slug' => 'system.deployment-registry', 'description' => 'Manage deployment registry'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $this->command->info('Permissions seeded successfully!');
    }
}

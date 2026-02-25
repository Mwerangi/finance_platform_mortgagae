<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\CollectionsQueue;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class ExportService
{
    /**
     * Export applications to Excel.
     */
    public function exportApplications(int $institutionId, array $filters = []): string
    {
        $query = Application::where('institution_id', $institutionId)
            ->with(['customer', 'loanProduct', 'underwritingDecision']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        $applications = $query->get();

        $data = $applications->map(function ($app) {
            return [
                'Application Number' => $app->application_number,
                'Customer Name' => $app->customer->full_name,
                'Customer ID' => $app->customer->customer_number,
                'Phone' => $app->customer->phone,
                'Email' => $app->customer->email,
                'Product' => $app->loanProduct->name ?? 'N/A',
                'Requested Amount' => $app->requested_amount,
                'Requested Tenure' => $app->requested_tenure_months,
                'Status' => $app->status,
                'Decision' => $app->underwritingDecision->final_decision ?? 'Pending',
                'Approved Amount' => $app->underwritingDecision->approved_amount ?? 0,
                'Risk Grade' => $app->eligibilityAssessments->first()->risk_grade ?? 'N/A',
                'Application Date' => $app->created_at->format('Y-m-d'),
                'Decision Date' => $app->underwritingDecision?->created_at->format('Y-m-d') ?? 'N/A',
            ];
        });

        return $this->generateExcel($data, 'applications_export');
    }

    /**
     * Export loans to Excel.
     */
    public function exportLoans(int $institutionId, array $filters = []): string
    {
        $query = Loan::where('institution_id', $institutionId)
            ->with(['customer', 'loanProduct', 'application']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('disbursement_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('disbursement_date', '<=', $filters['end_date']);
        }

        $loans = $query->get();

        $data = $loans->map(function ($loan) {
            return [
                'Loan Account' => $loan->loan_account_number,
                'Customer Name' => $loan->customer->full_name,
                'Customer ID' => $loan->customer->customer_number,
                'Product' => $loan->loanProduct->name,
                'Principal' => $loan->approved_amount,
                'Interest Rate' => $loan->interest_rate,
                'Tenure (Months)' => $loan->approved_tenure_months,
                'Monthly Installment' => $loan->monthly_installment,
                'Total Repayment' => $loan->total_repayment,
                'Disbursement Date' => $loan->disbursement_date?->format('Y-m-d') ?? 'N/A',
                'Maturity Date' => $loan->maturity_date?->format('Y-m-d') ?? 'N/A',
                'Status' => $loan->status,
                'Days Past Due' => $loan->days_past_due,
                'Amount Paid' => $loan->total_paid,
                'Outstanding' => $loan->total_outstanding,
                'Arrears' => $loan->arrears_amount,
                'Aging Bucket' => $loan->aging_bucket,
                'Risk Classification' => $loan->risk_classification,
            ];
        });

        return $this->generateExcel($data, 'loans_export');
    }

    /**
     * Export repayments to Excel.
     */
    public function exportRepayments(int $institutionId, array $filters = []): string
    {
        $query = Repayment::where('institution_id', $institutionId)
            ->with(['loan', 'customer', 'importBatch']);

        // Apply filters
        if (!empty($filters['start_date'])) {
            $query->where('payment_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->where('payment_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['loan_id'])) {
            $query->where('loan_id', $filters['loan_id']);
        }

        $repayments = $query->orderBy('payment_date', 'desc')->get();

        $data = $repayments->map(function ($payment) {
            return [
                'Payment Date' => $payment->payment_date->format('Y-m-d'),
                'Loan Account' => $payment->loan->loan_account_number,
                'Customer Name' => $payment->customer->full_name,
                'Transaction Reference' => $payment->transaction_reference,
                'Payment Amount' => $payment->payment_amount,
                'Principal' => $payment->principal_amount,
                'Interest' => $payment->interest_amount,
                'Penalties' => $payment->penalty_amount,
                'Fees' => $payment->fees_amount,
                'Payment Method' => $payment->payment_method,
                'Status' => $payment->status,
                'Is Reversed' => $payment->is_reversed ? 'Yes' : 'No',
                'Import Batch' => $payment->importBatch->batch_reference ?? 'Manual',
                'Recorded At' => $payment->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return $this->generateExcel($data, 'repayments_export');
    }

    /**
     * Export collections queue to Excel.
     */
    public function exportCollectionsQueue(int $institutionId, array $filters = []): string
    {
        $query = CollectionsQueue::where('institution_id', $institutionId)
            ->with(['loan', 'customer', 'assignedTo']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority_level'])) {
            $query->where('priority_level', $filters['priority_level']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        $queueItems = $query->orderByPriority()->get();

        $data = $queueItems->map(function ($item) {
            return [
                'Loan Account' => $item->loan->loan_account_number,
                'Customer Name' => $item->customer->full_name,
                'Phone' => $item->customer_phone,
                'Days Past Due' => $item->days_past_due,
                'Total Arrears' => $item->total_arrears,
                'Principal Arrears' => $item->principal_arrears,
                'Interest Arrears' => $item->interest_arrears,
                'Priority Level' => strtoupper($item->priority_level),
                'Priority Score' => $item->priority_score,
                'Delinquency Bucket' => $item->delinquency_bucket,
                'Status' => $item->status,
                'Assigned To' => $item->assignedTo->name ?? 'Unassigned',
                'Contact Attempts' => $item->contact_attempts,
                'Successful Contacts' => $item->successful_contacts,
                'Broken Promises' => $item->broken_promises,
                'Has Active PTP' => $item->has_active_ptp ? 'Yes' : 'No',
                'Next Action Due' => $item->next_action_due?->format('Y-m-d') ?? 'N/A',
                'Last Action' => $item->last_action_at?->format('Y-m-d') ?? 'N/A',
            ];
        });

        return $this->generateExcel($data, 'collections_queue_export');
    }

    /**
     * Export portfolio summary to Excel.
     */
    public function exportPortfolioSummary(int $institutionId): string
    {
        $loans = Loan::where('institution_id', $institutionId)
            ->with(['customer', 'loanProduct'])
            ->get();

        // Summary sheet
        $summary = [
            ['Metric', 'Value'],
            ['Total Loans', $loans->count()],
            ['Active Loans', $loans->where('status', 'active')->count()],
            ['Total Disbursed', $loans->sum('disbursed_amount')],
            ['Total Outstanding', $loans->sum('total_outstanding')],
            ['Total Collected', $loans->sum('total_paid')],
            ['Loans in Arrears', $loans->where('days_past_due', '>', 0)->count()],
            ['Total Arrears', $loans->sum('arrears_amount')],
            ['NPL Count', $loans->where('days_past_due', '>=', 90)->count()],
            ['NPL Amount', $loans->where('days_past_due', '>=', 90)->sum('total_outstanding')],
        ];

        // Aging distribution sheet
        $agingData = [
            ['Aging Bucket', 'Count', 'Outstanding Amount'],
            ['Current', $loans->where('aging_bucket', 'current')->count(), $loans->where('aging_bucket', 'current')->sum('total_outstanding')],
            ['1-30 Days', $loans->where('aging_bucket', 'bucket_30')->count(), $loans->where('aging_bucket', 'bucket_30')->sum('total_outstanding')],
            ['31-60 Days', $loans->where('aging_bucket', 'bucket_60')->count(), $loans->where('aging_bucket', 'bucket_60')->sum('total_outstanding')],
            ['61-90 Days', $loans->where('aging_bucket', 'bucket_90')->count(), $loans->where('aging_bucket', 'bucket_90')->sum('total_outstanding')],
            ['90+ Days (NPL)', $loans->where('aging_bucket', 'npl')->count(), $loans->where('aging_bucket', 'npl')->sum('total_outstanding')],
        ];

        return $this->generateExcelMultiSheet([
            'Summary' => collect($summary),
            'Aging Distribution' => collect($agingData),
        ], 'portfolio_summary_export');
    }

    /**
     * Generate Excel file from data.
     */
    protected function generateExcel(Collection $data, string $baseFilename): string
    {
        $filename = $baseFilename . '_' . now()->format('Ymd_His') . '.xlsx';
        $path = storage_path("app/exports/{$filename}");

        // Ensure exports directory exists
        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        Excel::store(new \App\Exports\GenericExport($data), "exports/{$filename}");

        return $path;
    }

    /**
     * Generate multi-sheet Excel file.
     */
    protected function generateExcelMultiSheet(array $sheets, string $baseFilename): string
    {
        $filename = $baseFilename . '_' . now()->format('Ymd_His') . '.xlsx';
        $path = storage_path("app/exports/{$filename}");

        // Ensure exports directory exists
        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        Excel::store(new \App\Exports\MultiSheetExport($sheets), "exports/{$filename}");

        return $path;
    }
}

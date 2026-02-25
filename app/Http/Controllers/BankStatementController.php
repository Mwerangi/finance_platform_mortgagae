<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Jobs\ParseBankStatementJob;
use App\Models\BankStatementImport;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BankStatementController extends Controller
{
    /**
     * Display a listing of bank statement imports.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $customerId = $request->input('customer_id');
        $status = $request->input('status');
        
        $user = $request->user();
        
        $query = BankStatementImport::with(['customer', 'uploader', 'application']);
        
        // Scope to institution
        if (!$user->hasRole('provider-super-admin')) {
            $query->where('institution_id', $user->institution_id);
        } elseif ($request->has('institution_id')) {
            $query->where('institution_id', $request->institution_id);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($status) {
            $query->where('import_status', $status);
        }

        $imports = $query->latest()->paginate($perPage);

        return response()->json($imports);
    }

    /**
     * Upload and queue bank statement for processing.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'application_id' => ['nullable', 'exists:applications,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:51200'], // 50MB max
        ]);

        // Verify customer belongs to user's institution
        $customer = Customer::findOrFail($validated['customer_id']);
        
        if (!$user->hasRole('provider-super-admin') && $customer->institution_id !== $user->institution_id) {
            return response()->json([
                'message' => 'Unauthorized to upload for this customer.',
            ], 403);
        }

        // Store the file
        $file = $request->file('file');
        $filename = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
        $path = $file->storeAs(
            "bank-statements/{$customer->institution_id}/{$customer->id}",
            $filename,
            'private'
        );

        // Create import record
        $import = BankStatementImport::create([
            'customer_id' => $customer->id,
            'institution_id' => $customer->institution_id,
            'application_id' => $validated['application_id'] ?? null,
            'uploaded_by' => $user->id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'import_status' => ImportStatus::PENDING,
        ]);

        // Dispatch job to process the file
        ParseBankStatementJob::dispatch($import);

        return response()->json([
            'message' => 'Bank statement uploaded successfully. Processing will begin shortly.',
            'import' => $import->load('customer'),
        ], 201);
    }

    /**
     * Display the specified import.
     */
    public function show(BankStatementImport $bankStatementImport): JsonResponse
    {
        $bankStatementImport->load([
            'customer',
            'institution',
            'application',
            'uploader',
            'transactions' => function ($query) {
                $query->orderBy('transaction_date', 'desc')->limit(100);
            },
            'analytics',
        ]);

        return response()->json([
            'import' => $bankStatementImport,
        ]);
    }

    /**
     * Get transactions for an import.
     */
    public function transactions(Request $request, BankStatementImport $bankStatementImport): JsonResponse
    {
        $perPage = $request->input('per_page', 50);
        $type = $request->input('type'); // income, expense, debt_payment
        $flagged = $request->input('flagged');
        
        $query = $bankStatementImport->transactions()->orderBy('transaction_date', 'desc');

        if ($type === 'income') {
            $query->where('is_income', true);
        } elseif ($type === 'expense') {
            $query->where('is_expense', true);
        } elseif ($type === 'debt_payment') {
            $query->where('is_debt_payment', true);
        }

        if ($flagged) {
            $query->where('is_flagged', filter_var($flagged, FILTER_VALIDATE_BOOLEAN));
        }

        $transactions = $query->paginate($perPage);

        return response()->json($transactions);
    }

    /**
     * Get analytics for an import.
     */
    public function analytics(BankStatementImport $bankStatementImport): JsonResponse
    {
        $analytics = $bankStatementImport->analytics()
            ->with(['customer', 'application'])
            ->first();

        if (!$analytics) {
            return response()->json([
                'message' => 'Analytics not yet computed for this import.',
            ], 404);
        }

        return response()->json([
            'analytics' => $analytics,
        ]);
    }

    /**
     * Re-compute analytics for an import.
     */
    public function recomputeAnalytics(BankStatementImport $bankStatementImport): JsonResponse
    {
        if (!$bankStatementImport->isCompleted()) {
            return response()->json([
                'message' => 'Cannot recompute analytics. Import is not completed.',
            ], 422);
        }

        // Dispatch analytics job
        \App\Jobs\ComputeAnalyticsJob::dispatch($bankStatementImport);

        return response()->json([
            'message' => 'Analytics recomputation queued successfully.',
        ]);
    }

    /**
     * Delete an import and its transactions.
     */
    public function destroy(BankStatementImport $bankStatementImport): JsonResponse
    {
        // Delete the file from storage
        if (Storage::disk('private')->exists($bankStatementImport->file_path)) {
            Storage::disk('private')->delete($bankStatementImport->file_path);
        }

        $bankStatementImport->delete();

        return response()->json([
            'message' => 'Bank statement import deleted successfully.',
        ]);
    }

    /**
     * Download the original uploaded file.
     */
    public function download(BankStatementImport $bankStatementImport)
    {
        if (!Storage::disk('private')->exists($bankStatementImport->file_path)) {
            return response()->json([
                'message' => 'File not found.',
            ], 404);
        }

        return Storage::disk('private')->download(
            $bankStatementImport->file_path,
            $bankStatementImport->file_name
        );
    }

    /**
     * Get import statistics for a customer.
     */
    public function customerStats(Customer $customer): JsonResponse
    {
        $stats = [
            'total_imports' => $customer->bankStatementImports()->count(),
            'completed_imports' => $customer->bankStatementImports()->completed()->count(),
            'pending_imports' => $customer->bankStatementImports()->withStatus(ImportStatus::PENDING)->count(),
            'failed_imports' => $customer->bankStatementImports()->withStatus(ImportStatus::FAILED)->count(),
            'latest_import' => $customer->bankStatementImports()
                ->with('analytics')
                ->latest()
                ->first(),
        ];

        return response()->json($stats);
    }
}

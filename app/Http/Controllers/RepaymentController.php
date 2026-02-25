<?php

namespace App\Http\Controllers;

use App\Jobs\ImportRepaymentStatementJob;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\RepaymentImportBatch;
use App\Services\RepaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RepaymentController extends Controller
{
    protected RepaymentService $repaymentService;

    public function __construct(RepaymentService $repaymentService)
    {
        $this->repaymentService = $repaymentService;
    }

    /**
     * Upload repayment statement for batch processing
     * POST /api/repayments/upload
     */
    public function uploadStatement(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Store file
        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();
        $filePath = $file->store('repayment_imports/' . $institutionId, 'local');

        // Create import batch
        $batch = RepaymentImportBatch::create([
            'institution_id' => $institutionId,
            'original_filename' => $originalFilename,
            'file_path' => $filePath,
            'status' => 'pending',
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'total_amount' => 0,
            'matched_amount' => 0,
            'unmatched_amount' => 0,
            'uploaded_by' => $request->user()->id,
            'notes' => $request->notes,
        ]);

        // Dispatch job for async processing
        ImportRepaymentStatementJob::dispatch($batch->id);

        return response()->json([
            'message' => 'File uploaded successfully. Processing has started.',
            'batch' => $this->formatBatchSummary($batch),
        ], 202); // 202 Accepted
    }

    /**
     * Get import batch status
     * GET /api/repayments/batches/{batch}
     */
    public function getBatchStatus(Request $request, RepaymentImportBatch $batch)
    {
        // Ensure user has access to this institution's data
        if ($batch->institution_id !== $request->user()->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'batch' => $this->formatBatchDetails($batch),
        ]);
    }

    /**
     * Get import batch history
     * GET /api/repayments/batches
     */
    public function getBatchHistory(Request $request)
    {
        $institutionId = $request->user()->institution_id;

        $query = RepaymentImportBatch::forInstitution($institutionId)
            ->with('uploadedBy')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->status) {
            $query->withStatus($request->status);
        }

        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }

        if ($request->uploaded_by) {
            $query->uploadedBy($request->uploaded_by);
        }

        // Pagination
        $perPage = $request->per_page ?? 20;
        $batches = $query->paginate($perPage);

        return response()->json([
            'batches' => $batches->map(fn($batch) => $this->formatBatchSummary($batch)),
            'pagination' => [
                'total' => $batches->total(),
                'per_page' => $batches->perPage(),
                'current_page' => $batches->currentPage(),
                'last_page' => $batches->lastPage(),
            ],
        ]);
    }

    /**
     * Get repayment history for a loan
     * GET /api/loans/{loan}/repayments
     */
    public function getLoanRepayments(Request $request, Loan $loan)
    {
        // Ensure user has access to this institution's data
        if ($loan->institution_id !== $request->user()->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'payment_method' => $request->payment_method,
        ];

        $repayments = $this->repaymentService->getRepaymentHistory($loan, array_filter($filters));
        $summary = $this->repaymentService->getRepaymentSummary($loan);

        return response()->json([
            'repayments' => $repayments->map(fn($r) => $this->formatRepaymentDetails($r)),
            'summary' => $summary,
        ]);
    }

    /**
     * Reverse a payment
     * POST /api/repayments/{repayment}/reverse
     */
    public function reversePayment(Request $request, Repayment $repayment)
    {
        // Ensure user has access to this institution's data
        if ($repayment->institution_id !== $request->user()->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($repayment->is_reversed) {
            return response()->json([
                'message' => 'Payment has already been reversed',
            ], 400);
        }

        try {
            $offsetting = $this->repaymentService->reversePayment(
                $repayment,
                $request->user()->id,
                $request->reason
            );

            return response()->json([
                'message' => 'Payment reversed successfully',
                'original_payment' => $this->formatRepaymentDetails($repayment->fresh()),
                'reversal_entry' => $this->formatRepaymentDetails($offsetting),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to reverse payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get repayment details
     * GET /api/repayments/{repayment}
     */
    public function show(Request $request, Repayment $repayment)
    {
        // Ensure user has access to this institution's data
        if ($repayment->institution_id !== $request->user()->institution_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'repayment' => $this->formatRepaymentDetails($repayment->load(['loan', 'customer', 'loanSchedule', 'importBatch', 'recordedBy', 'reversedBy'])),
        ]);
    }

    /**
     * Format batch summary for list view
     */
    protected function formatBatchSummary(RepaymentImportBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'original_filename' => $batch->original_filename,
            'status' => $batch->status,
            'status_color' => $batch->status_color,
            'total_rows' => $batch->total_rows,
            'processed_rows' => $batch->processed_rows,
            'successful_rows' => $batch->successful_rows,
            'failed_rows' => $batch->failed_rows,
            'success_rate' => $batch->success_rate,
            'progress_percentage' => $batch->progress_percentage,
            'total_amount' => (float) $batch->total_amount,
            'matched_amount' => (float) $batch->matched_amount,
            'unmatched_amount' => (float) $batch->unmatched_amount,
            'matched_percentage' => $batch->matched_percentage,
            'uploaded_by' => [
                'id' => $batch->uploadedBy?->id,
                'name' => $batch->uploadedBy?->name,
            ],
            'created_at' => $batch->created_at?->toDateTimeString(),
            'started_at' => $batch->started_at?->toDateTimeString(),
            'completed_at' => $batch->completed_at?->toDateTimeString(),
            'processing_duration' => $batch->formatted_duration,
        ];
    }

    /**
     * Format batch details
     */
    protected function formatBatchDetails(RepaymentImportBatch $batch): array
    {
        return array_merge($this->formatBatchSummary($batch), [
            'notes' => $batch->notes,
            'errors' => $batch->errors ?? [],
            'error_count' => $batch->error_count,
        ]);
    }

    /**
     * Format repayment details
     */
    protected function formatRepaymentDetails(Repayment $repayment): array
    {
        return [
            'id' => $repayment->id,
            'receipt_number' => $repayment->receipt_number,
            'loan' => [
                'id' => $repayment->loan?->id,
                'account_number' => $repayment->loan?->loan_account_number,
            ],
            'customer' => [
                'id' => $repayment->customer?->id,
                'name' => $repayment->customer?->full_name,
            ],
            'transaction_reference' => $repayment->transaction_reference,
            'payment_date' => $repayment->payment_date?->format('Y-m-d'),
            'amount' => (float) $repayment->amount,
            'payment_method' => $repayment->payment_method,
            'payment_method_display' => $repayment->payment_method_display,
            'payment_channel' => $repayment->payment_channel,
            'allocation' => $repayment->allocation_breakdown,
            'allocation_percentages' => $repayment->allocation_percentages,
            'status' => $repayment->status,
            'status_color' => $repayment->status_color,
            'flags' => [
                'is_partial_payment' => $repayment->is_partial_payment,
                'is_advance_payment' => $repayment->is_advance_payment,
                'is_overpayment' => $repayment->is_overpayment,
                'is_reversed' => $repayment->is_reversed,
            ],
            'loan_state_at_payment' => [
                'installment_number' => $repayment->installment_number,
                'days_past_due' => $repayment->days_past_due_at_payment,
                'outstanding_before' => (float) $repayment->outstanding_before_payment,
                'outstanding_after' => (float) $repayment->outstanding_after_payment,
                'balance_impact' => $repayment->balance_impact,
            ],
            'reversal' => $repayment->is_reversed ? [
                'reversed_at' => $repayment->reversed_at?->toDateTimeString(),
                'reversed_by' => [
                    'id' => $repayment->reversedBy?->id,
                    'name' => $repayment->reversedBy?->name,
                ],
                'reason' => $repayment->reversal_reason,
            ] : null,
            'import_batch' => $repayment->import_batch_id ? [
                'id' => $repayment->importBatch?->id,
                'batch_number' => $repayment->importBatch?->batch_number,
            ] : null,
            'recorded_by' => [
                'id' => $repayment->recordedBy?->id,
                'name' => $repayment->recordedBy?->name,
            ],
            'notes' => $repayment->notes,
            'created_at' => $repayment->created_at?->toDateTimeString(),
        ];
    }
}

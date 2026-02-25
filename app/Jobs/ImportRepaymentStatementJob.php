<?php

namespace App\Jobs;

use App\Models\RepaymentImportBatch;
use App\Services\RepaymentService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportRepaymentStatementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes
    public int $tries = 3;

    protected int $batchId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle(RepaymentService $repaymentService): void
    {
        $batch = RepaymentImportBatch::findOrFail($this->batchId);

        try {
            // Mark as processing
            $batch->startProcessing();

            Log::info("Starting repayment import for batch {$batch->batch_number}");

            // Read Excel file
            $rows = $this->readExcelFile($batch->file_path);

            // Update total rows
            $batch->update(['total_rows' => count($rows)]);

            $successCount = 0;
            $failCount = 0;
            $matchedAmount = 0;
            $unmatchedAmount = 0;

            // Process each row
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 for header and 0-index

                try {
                    // Validate row
                    $validatedRow = $this->validateRow($row, $rowNumber);

                    // Match transaction to loan
                    $loan = $repaymentService->matchTransaction(
                        $batch->institution_id,
                        $validatedRow['loan_account_number'],
                        $validatedRow['amount'],
                        $validatedRow['payment_date'],
                        $validatedRow['transaction_reference']
                    );

                    if ($loan) {
                        // Allocate payment
                        $repayment = $repaymentService->allocatePayment(
                            $loan,
                            $validatedRow['amount'],
                            $validatedRow['payment_date'],
                            [
                                'transaction_reference' => $validatedRow['transaction_reference'],
                                'payment_method' => $validatedRow['payment_method'] ?? 'bank_transfer',
                                'payment_channel' => $validatedRow['payment_channel'] ?? null,
                                'notes' => "Imported from batch {$batch->batch_number}",
                                'metadata' => [
                                    'import_batch_id' => $batch->id,
                                    'row_number' => $rowNumber,
                                ],
                            ]
                        );

                        // Link repayment to batch
                        $repayment->update(['import_batch_id' => $batch->id]);

                        $successCount++;
                        $matchedAmount += $validatedRow['amount'];

                        Log::info("Row {$rowNumber}: Successfully allocated {$validatedRow['amount']} to loan {$loan->loan_account_number}");
                    } else {
                        // Record as unmatched
                        $repaymentService->recordUnmatchedTransaction(
                            $batch->institution_id,
                            [
                                'reference' => $validatedRow['transaction_reference'] ?? null,
                                'date' => $validatedRow['payment_date'],
                                'amount' => $validatedRow['amount'],
                                'method' => $validatedRow['payment_method'] ?? null,
                                'channel' => $validatedRow['payment_channel'] ?? null,
                                'loan_account_number' => $validatedRow['loan_account_number'],
                            ],
                            $batch->id
                        );

                        $failCount++;
                        $unmatchedAmount += $validatedRow['amount'];

                        $batch->recordError(
                            (string) $rowNumber,
                            "Loan account not found: {$validatedRow['loan_account_number']}",
                            $row
                        );

                        Log::warning("Row {$rowNumber}: Loan not found for account {$validatedRow['loan_account_number']}");
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    
                    $batch->recordError(
                        (string) $rowNumber,
                        $e->getMessage(),
                        $row
                    );

                    Log::error("Row {$rowNumber}: {$e->getMessage()}", [
                        'row_data' => $row,
                        'exception' => $e,
                    ]);
                }

                // Update progress
                $batch->updateStatistics([
                    'processed_rows' => $successCount + $failCount,
                    'successful_rows' => $successCount,
                    'failed_rows' => $failCount,
                    'matched_amount' => $matchedAmount,
                    'unmatched_amount' => $unmatchedAmount,
                ]);
            }

            // Calculate total amount from rows
            $totalAmount = collect($rows)->sum(fn($row) => $this->parseAmount($row['amount'] ?? 0));

            // Mark as completed or partially completed
            if ($failCount === 0) {
                $batch->update(['total_amount' => $totalAmount]);
                $batch->markCompleted();
                Log::info("Batch {$batch->batch_number} completed successfully. {$successCount} payments processed.");
            } else if ($successCount > 0) {
                $batch->update(['total_amount' => $totalAmount]);
                $batch->markPartiallyCompleted();
                Log::info("Batch {$batch->batch_number} partially completed. {$successCount} succeeded, {$failCount} failed.");
            } else {
                $batch->update(['total_amount' => $totalAmount]);
                $batch->markFailed("All payments failed to process");
                Log::error("Batch {$batch->batch_number} failed. No payments processed.");
            }

        } catch (\Exception $e) {
            Log::error("Import job failed for batch {$batch->batch_number}: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            $batch->markFailed("Import failed: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Read Excel file and return rows
     */
    protected function readExcelFile(string $filePath): array
    {
        $fullPath = Storage::path($filePath);

        if (!file_exists($fullPath)) {
            throw new \Exception("File not found: {$filePath}");
        }

        // Read Excel file using Maatwebsite Excel
        $data = Excel::toArray([], $fullPath);

        if (empty($data) || empty($data[0])) {
            throw new \Exception("Excel file is empty");
        }

        // First sheet, skip header row
        $rows = $data[0];
        $header = array_shift($rows);

        // Convert to associative arrays
        $result = [];
        foreach ($rows as $row) {
            $associative = [];
            foreach ($header as $index => $columnName) {
                $associative[$this->normalizeColumnName($columnName)] = $row[$index] ?? null;
            }
            $result[] = $associative;
        }

        return $result;
    }

    /**
     * Normalize column name to lowercase with underscores
     */
    protected function normalizeColumnName(string $name): string
    {
        return strtolower(trim(preg_replace('/\s+/', '_', $name)));
    }

    /**
     * Validate a row from the Excel file
     */
    protected function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        // Required: loan_account_number
        if (empty($row['loan_account_number']) && empty($row['account_number']) && empty($row['loan_account'])) {
            $errors[] = 'Loan account number is required';
        }

        // Required: payment_date
        if (empty($row['payment_date']) && empty($row['date']) && empty($row['transaction_date'])) {
            $errors[] = 'Payment date is required';
        }

        // Required: amount
        if (!isset($row['amount']) && !isset($row['payment_amount']) && !isset($row['transaction_amount'])) {
            $errors[] = 'Amount is required';
        }

        if (!empty($errors)) {
            throw new \Exception("Row validation failed: " . implode(', ', $errors));
        }

        // Parse and validate data
        $loanAccountNumber = $row['loan_account_number'] ?? $row['account_number'] ?? $row['loan_account'];
        $amount = $this->parseAmount($row['amount'] ?? $row['payment_amount'] ?? $row['transaction_amount']);
        $paymentDate = $this->parseDate($row['payment_date'] ?? $row['date'] ?? $row['transaction_date']);

        if ($amount <= 0) {
            throw new \Exception("Invalid amount: {$amount}");
        }

        return [
            'loan_account_number' => trim($loanAccountNumber),
            'amount' => $amount,
            'payment_date' => $paymentDate,
            'transaction_reference' => $row['transaction_reference'] ?? $row['reference'] ?? $row['trans_ref'] ?? null,
            'payment_method' => $this->normalizePaymentMethod($row['payment_method'] ?? $row['method'] ?? 'bank_transfer'),
            'payment_channel' => $row['payment_channel'] ?? $row['channel'] ?? $row['bank'] ?? null,
        ];
    }

    /**
     * Parse amount from various formats
     */
    protected function parseAmount($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove currency symbols and commas
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        return (float) $cleaned;
    }

    /**
     * Parse date from various formats
     */
    protected function parseDate($value): Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            throw new \Exception("Invalid date format: {$value}");
        }
    }

    /**
     * Normalize payment method to enum value
     */
    protected function normalizePaymentMethod(string $method): string
    {
        $normalized = strtolower(trim($method));

        $mapping = [
            'bank_transfer' => 'bank_transfer',
            'bank transfer' => 'bank_transfer',
            'transfer' => 'bank_transfer',
            'cheque' => 'cheque',
            'check' => 'cheque',
            'cash' => 'cash',
            'mobile_money' => 'mobile_money',
            'mobile money' => 'mobile_money',
            'mpesa' => 'mobile_money',
            'm-pesa' => 'mobile_money',
            'direct_debit' => 'direct_debit',
            'direct debit' => 'direct_debit',
            'standing_order' => 'standing_order',
            'standing order' => 'standing_order',
        ];

        return $mapping[$normalized] ?? 'other';
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $batch = RepaymentImportBatch::find($this->batchId);

        if ($batch) {
            $batch->markFailed("Job failed after {$this->tries} attempts: {$exception->getMessage()}");
        }

        Log::error("Import job permanently failed for batch ID {$this->batchId}", [
            'exception' => $exception,
        ]);
    }
}

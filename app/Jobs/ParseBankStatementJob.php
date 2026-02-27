<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\BankStatementImport;
use App\Models\BankTransaction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ParseBankStatementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public BankStatementImport $import
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting bank statement parse for import #{$this->import->id}");

            // Mark as processing
            $this->import->markAsProcessing();

            // Get file path
            $filePath = Storage::disk('private')->path($this->import->file_path);

            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$this->import->file_path}");
            }

            // Load Excel file
            $data = Excel::toArray([], $filePath)[0];

            if (empty($data)) {
                throw new \Exception("Excel file is empty");
            }

            // Find the header row (look for a row containing date-related columns)
            $headerRow = null;
            $headerIndex = 0;
            foreach ($data as $index => $row) {
                // Normalize column names: trim, lowercase, collapse multiple spaces
                $rowLower = array_map(function($v) {
                    return preg_replace('/\s+/', ' ', strtolower(trim($v ?? '')));
                }, $row);
                // Look for rows containing transaction/date headers
                if (in_array('date', $rowLower) || 
                    in_array('trans date', $rowLower) || 
                    in_array('transaction date', $rowLower)) {
                    $headerRow = $rowLower;
                    $headerIndex = $index;
                    break;
                }
            }

            if (!$headerRow) {
                throw new \Exception("Could not find header row. Expected columns: Date, Description, Debit, Credit, Balance");
            }

            // Map flexible column names to standard names
            $columnMap = [
                'date' => ['date', 'trans date', 'transaction date', 'posting date', 'txn date'],
                'description' => ['description', 'details', 'narration', 'particulars', 'transaction details'],
                'debit' => ['debit', 'withdrawal', 'dr', 'debit amount'],
                'credit' => ['credit', 'deposit', 'cr', 'credit amount'],
                'balance' => ['balance', 'book balance', 'running balance', 'closing balance'],
            ];

            // Find column indexes by matching flexible names
            $columnIndexes = [];
            foreach ($columnMap as $standard => $alternatives) {
                foreach ($alternatives as $alt) {
                    $index = array_search($alt, $headerRow);
                    if ($index !== false) {
                        $columnIndexes[$standard] = $index;
                        break;
                    }
                }
                
                // Check if required column was found
                if (!isset($columnIndexes[$standard])) {
                    throw new \Exception("Missing required column: {$standard}. Found columns: " . implode(', ', array_filter($headerRow)));
                }
            }

            // Get column indexes
            $dateIndex = $columnIndexes['date'];
            $descIndex = $columnIndexes['description'];
            $debitIndex = $columnIndexes['debit'];
            $creditIndex = $columnIndexes['credit'];
            $balanceIndex = $columnIndexes['balance'];

            // Remove rows before and including header
            $data = array_slice($data, $headerIndex + 1);

            $rowsTotal = count($data);
            $rowsProcessed = 0;
            $rowsFailed = 0;
            $errors = [];
            $transactions = [];

            $minDate = null;
            $maxDate = null;

            // Process each row
            foreach ($data as $index => $row) {
                try {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Parse date
                    $dateValue = $row[$dateIndex] ?? null;
                    if (empty($dateValue)) {
                        throw new \Exception("Missing date");
                    }

                    // Handle Excel date serial or string date
                    if (is_numeric($dateValue)) {
                        // Excel date serial
                        $transactionDate = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue));
                    } else {
                        $transactionDate = Carbon::parse($dateValue);
                    }

                    // Validate date is not in future
                    if ($transactionDate->isFuture()) {
                        throw new \Exception("Future date not allowed: {$transactionDate->format('Y-m-d')}");
                    }

                    // Track date range
                    if (!$minDate || $transactionDate->lt($minDate)) {
                        $minDate = $transactionDate->copy();
                    }
                    if (!$maxDate || $transactionDate->gt($maxDate)) {
                        $maxDate = $transactionDate->copy();
                    }

                    // Parse amounts
                    $description = trim($row[$descIndex] ?? '');
                    $debit = $this->parseAmount($row[$debitIndex] ?? 0);
                    $credit = $this->parseAmount($row[$creditIndex] ?? 0);
                    $balance = $this->parseAmount($row[$balanceIndex] ?? null);

                    if (empty($description)) {
                        throw new \Exception("Missing description");
                    }

                    // Create transaction hash for deduplication
                    // Use import_id instead of customer_id to allow same file to be imported multiple times
                    $hash = md5(
                        $this->import->id .
                        $transactionDate->format('Y-m-d') .
                        $description .
                        $debit .
                        $credit
                    );

                    // Check if transaction already exists
                    $existing = BankTransaction::where('transaction_hash', $hash)->first();
                    if ($existing) {
                        Log::info("Skipping duplicate transaction: {$hash}");
                        $rowsProcessed++;
                        continue;
                    }

                    // Create transaction
                    $transactions[] = [
                        'bank_statement_import_id' => $this->import->id,
                        'customer_id' => $this->import->customer_id,
                        'institution_id' => $this->import->institution_id,
                        'transaction_date' => $transactionDate->format('Y-m-d'),
                        'description' => $description,
                        'transaction_hash' => $hash,
                        'debit' => $debit,
                        'credit' => $credit,
                        'balance' => $balance,
                        'is_income' => $credit > 0,
                        'is_expense' => $debit > 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $rowsProcessed++;

                    // Batch insert every 100 rows (reduced from 500 to save memory)
                    if (count($transactions) >= 100) {
                        BankTransaction::insertOrIgnore($transactions);
                        $transactions = [];
                        
                        // Free memory
                        gc_collect_cycles();
                    }

                } catch (\Exception $e) {
                    $rowsFailed++;
                    $errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
                    Log::warning("Failed to process row " . ($index + 2) . ": " . $e->getMessage());
                }
            }

            // Insert remaining transactions
            if (count($transactions) > 0) {
                BankTransaction::insertOrIgnore($transactions);
            }

            // Calculate statement months
            $statementMonths = null;
            if ($minDate && $maxDate) {
                $statementMonths = $minDate->diffInMonths($maxDate) + 1;
            }

            // Update import record
            $this->import->update([
                'rows_total' => $rowsTotal,
                'rows_processed' => $rowsProcessed,
                'rows_failed' => $rowsFailed,
                'statement_start_date' => $minDate,
                'statement_end_date' => $maxDate,
                'statement_months' => $statementMonths,
                'error_log' => $errors,
            ]);

            // Mark as completed
            $this->import->markAsCompleted();

            Log::info("Completed bank statement parse for import #{$this->import->id}. Processed: {$rowsProcessed}, Failed: {$rowsFailed}");

            // Dispatch analytics computation
            ComputeAnalyticsJob::dispatch($this->import);

        } catch (\Exception $e) {
            Log::error("Failed to parse bank statement import #{$this->import->id}: " . $e->getMessage());
            
            $this->import->markAsFailed([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Parse amount from string or numeric value.
     */
    private function parseAmount($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        // Remove common formatting
        $value = str_replace([',', ' ', 'TZS', 'TSh'], '', $value);
        
        return (float) $value;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ParseBankStatementJob failed for import #{$this->import->id}: " . $exception->getMessage());
        
        $this->import->markAsFailed([
            'error' => $exception->getMessage(),
            'failed_at' => now()->toDateTimeString(),
        ]);
    }
}

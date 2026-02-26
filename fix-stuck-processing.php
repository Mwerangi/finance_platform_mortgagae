<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BankStatementImport;
use App\Models\Prospect;
use Illuminate\Support\Facades\DB;

echo "=== Bank Statement Processing Diagnostic & Fix ===" . PHP_EOL . PHP_EOL;

// Check recent imports
echo "Recent Bank Statement Imports:" . PHP_EOL;
echo str_repeat("-", 80) . PHP_EOL;

$imports = BankStatementImport::orderBy('created_at', 'desc')->take(5)->get();

foreach ($imports as $import) {
    echo "ID: {$import->id}" . PHP_EOL;
    echo "  File: {$import->file_name}" . PHP_EOL;
    echo "  Status: {$import->import_status->value}" . PHP_EOL;
    echo "  Progress: {$import->rows_processed}/{$import->rows_total}" . PHP_EOL;
    echo "  Created: {$import->created_at->format('Y-m-d H:i:s')}" . PHP_EOL;
    
    // Check if stuck (status is pending or processing but created more than 10 min ago)
    $minutesAgo = $import->created_at->diffInMinutes(now());
    if (in_array($import->import_status->value, ['pending', 'processing']) && $minutesAgo > 10) {
        echo "  ⚠️  STUCK! Created {$minutesAgo} minutes ago" . PHP_EOL;
    }
    
    // Check for transactions
    $transactionCount = DB::table('bank_transactions')
        ->where('bank_statement_import_id', $import->id)
        ->count();
    echo "  Transactions: {$transactionCount}" . PHP_EOL;
    
    // Check for associated prospect
    $prospect = Prospect::where('bank_statement_import_id', $import->id)->first();
    if ($prospect) {
        echo "  Prospect: {$prospect->first_name} {$prospect->last_name} (ID: {$prospect->id})" . PHP_EOL;
    }
    
    echo PHP_EOL;
}

// Check queue status
echo PHP_EOL . "Queue Status:" . PHP_EOL;
echo str_repeat("-", 80) . PHP_EOL;
$pendingJobs = DB::table('jobs')->count();
$failedJobs = DB::table('failed_jobs')->count();
echo "Pending Jobs: {$pendingJobs}" . PHP_EOL;
echo "Failed Jobs: {$failedJobs}" . PHP_EOL;

if ($failedJobs > 0) {
    echo PHP_EOL . "Recent Failed Jobs:" . PHP_EOL;
    $failed = DB::table('failed_jobs')
        ->orderBy('failed_at', 'desc')
        ->take(2)
        ->get();
    
    foreach ($failed as $job) {
        echo "  Job ID: {$job->id}" . PHP_EOL;
        $exceptionPreview = substr($job->exception, 0, 150);
        echo "  Error: {$exceptionPreview}..." . PHP_EOL;
        echo PHP_EOL;
    }
}

// Offer to fix
echo PHP_EOL . str_repeat("=", 80) . PHP_EOL;
echo "Do you want to:" . PHP_EOL;
echo "1. Reset stuck imports (will reset pending/processing imports older than 10 min)" . PHP_EOL;
echo "2. Clear failed jobs" . PHP_EOL;
echo "3. Both" . PHP_EOL;
echo "4. Exit" . PHP_EOL;
echo PHP_EOL;
echo "Enter choice (1-4): ";

$choice = trim(fgets(STDIN));

switch ($choice) {
    case '1':
    case '3':
        echo PHP_EOL . "Resetting stuck imports..." . PHP_EOL;
        $stuckImports = BankStatementImport::whereIn('import_status', ['pending', 'processing'])
            ->where('created_at', '<', now()->subMinutes(10))
            ->get();
        
        foreach ($stuckImports as $import) {
            echo "  Resetting import #{$import->id} ({$import->file_name})" . PHP_EOL;
            
            // Delete transactions if any
            $deleted = DB::table('bank_transactions')
                ->where('bank_statement_import_id', $import->id)
                ->delete();
            if ($deleted > 0) {
                echo "    Deleted {$deleted} transactions" . PHP_EOL;
            }
            
            // Reset import status
            $import->update([
                'import_status' => 'pending',
                'rows_processed' => 0,
                'rows_total' => 0,
                'processing_started_at' => null,
                'processing_completed_at' => null,
            ]);
            
            echo "    ✓ Reset to pending" . PHP_EOL;
        }
        
        if ($choice === '1') break;
        // Fall through to case 2 if choice was 3
        
    case '2':
        echo PHP_EOL . "Clearing failed jobs..." . PHP_EOL;
        $deleted = DB::table('failed_jobs')->delete();
        echo "  ✓ Cleared {$deleted} failed jobs" . PHP_EOL;
        break;
        
    case '4':
        echo "Exiting..." . PHP_EOL;
        exit(0);
        
    default:
        echo "Invalid choice" . PHP_EOL;
        exit(1);
}

echo PHP_EOL . "✓ Done!" . PHP_EOL;
echo PHP_EOL . "Next steps:" . PHP_EOL;
echo "1. Make sure queue worker is running: php artisan queue:work --tries=3 --timeout=600 --memory=512 &" . PHP_EOL;
echo "2. Try uploading the bank statement again" . PHP_EOL;
echo "3. Or trigger processing manually: php artisan tinker --execute=\"\\App\\Jobs\\ParseBankStatementJob::dispatch(\\App\\Models\\BankStatementImport::find(5));\"" . PHP_EOL;

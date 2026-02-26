<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Import #5 Status ===" . PHP_EOL;
$import = App\Models\BankStatementImport::find(5);
echo "Status: " . $import->import_status->value . PHP_EOL;
echo "Rows: " . $import->rows_processed . "/" . $import->rows_total . PHP_EOL;
echo "Transactions: " . App\Models\BankTransaction::where('bank_statement_import_id', 5)->count() . PHP_EOL;

echo PHP_EOL . "=== Analytics ===" . PHP_EOL;
$analytics = App\Models\StatementAnalytics::where('bank_statement_import_id', 5)->first();
if ($analytics) {
    echo "Analytics found!" . PHP_EOL;
    echo "Avg Monthly Inflow: " . number_format($analytics->avg_monthly_inflow ?: 0, 2) . PHP_EOL;
    echo "Avg Monthly Outflow: " . number_format($analytics->avg_monthly_outflow ?: 0, 2) . PHP_EOL;
    echo "Estimated Net Income: " . number_format($analytics->estimated_net_income ?: 0, 2) . PHP_EOL;
} else {
    echo "No analytics found" . PHP_EOL;
}

echo PHP_EOL . "=== Prospect Status ===" . PHP_EOL;
$prospect = App\Models\Prospect::where('bank_statement_import_id', 5)->first();
if ($prospect) {
    echo "ID: " . $prospect->id . PHP_EOL;
    echo "Status: " . $prospect->status . PHP_EOL;
    echo "Eligibility Score: " . ($prospect->eligibility_score ?? 'NULL') . PHP_EOL;
    echo "Statement Import ID: " . ($prospect->bank_statement_import_id ?? 'NULL') . PHP_EOL;
} else {
    echo "No prospect found" . PHP_EOL;
}

echo PHP_EOL . "=== Failed Jobs ===" . PHP_EOL;
$failed = Illuminate\Support\Facades\DB::table('failed_jobs')->get();
echo "Failed jobs: " . $failed->count() . PHP_EOL;
if ($failed->count() > 0) {
    foreach ($failed as $job) {
        echo "  - " . substr($job->exception, 0, 100) . "..." . PHP_EOL;
    }
}

echo PHP_EOL . "=== Queue Worker ===" . PHP_EOL;
exec('ps aux | grep "queue:work" | grep -v grep', $output);
echo count($output) > 0 ? "Running" : "NOT RUNNING!" . PHP_EOL;

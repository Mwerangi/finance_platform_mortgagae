<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Eligibility Assessment for Prospect #2 ===" . PHP_EOL . PHP_EOL;

$assessment = App\Models\EligibilityAssessment::where('prospect_id', 2)
    ->orderBy('created_at', 'desc')
    ->first();

if ($assessment) {
    echo "Assessment found!" . PHP_EOL;
    echo "ID: " . $assessment->id . PHP_EOL;
    echo "Assessment Result: " . ($assessment->assessment_result ?? 'NULL') . PHP_EOL;
    echo "Income Classification: " . ($assessment->income_classification ?? 'NULL') . PHP_EOL;
    echo "Estimated Income: " . number_format($assessment->estimated_monthly_income ?: 0, 2) . PHP_EOL;
    echo "DTI Ratio: " . ($assessment->debt_to_income_ratio ?: 0) . PHP_EOL;
    echo "Approved Amount: " . number_format($assessment->approved_amount ?: 0, 2) . PHP_EOL;
    echo "Approved Tenure: " . ($assessment->approved_tenure_months ?? 'NULL') . PHP_EOL;
    echo "Recommendation: " . ($assessment->recommendation ?? 'NULL') . PHP_EOL;
    echo "Created At: " . $assessment->created_at . PHP_EOL;
    
    echo PHP_EOL . "Raw data: " . PHP_EOL;
    print_r($assessment->getAttributes());
} else {
    echo "No eligibility assessment found for prospect #2" . PHP_EOL;
}

echo PHP_EOL . "=== Prospect #2 Details ===" . PHP_EOL;
$prospect = App\Models\Prospect::find(2);
echo "Status: " . $prospect->status . PHP_EOL;
echo "Created: " . $prospect->created_at . PHP_EOL;
echo "Updated: " . $prospect->updated_at . PHP_EOL;

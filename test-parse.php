<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BankStatementImport;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

$import = BankStatementImport::find(5);

echo "=== Manual Bank Statement Processing Test ===" . PHP_EOL . PHP_EOL;
echo "Import ID: {$import->id}" . PHP_EOL;
echo "File: {$import->file_name}" . PHP_EOL;
echo "Path: {$import->file_path}" . PHP_EOL . PHP_EOL;

try {
    $filePath = Storage::disk('private')->path($import->file_path);
    echo "Full path: {$filePath}" . PHP_EOL;
    echo "File exists: " . (file_exists($filePath) ? 'YES' : 'NO') . PHP_EOL . PHP_EOL;
    
    echo "Loading Excel file..." . PHP_EOL;
    $data = Excel::toArray([], $filePath)[0];
    echo "✓ Loaded " . count($data) . " rows" . PHP_EOL . PHP_EOL;
    
    // Show first few rows
    echo "First 5 rows:" . PHP_EOL;
    foreach (array_slice($data, 0, 5) as $i => $row) {
        echo "Row {$i}: " . implode(' | ', array_slice($row, 0, 6)) . PHP_EOL;
    }
    echo PHP_EOL;
    
    // Find header
    echo "Finding header row..." . PHP_EOL;
    $headerRow = null;
    $headerIndex = 0;
    foreach ($data as $index => $row) {
        $rowLower = array_map('strtolower', array_map(fn($v) => trim($v ?? ''), $row));
        if (in_array('date', $rowLower) || 
            in_array('trans date', $rowLower) || 
            in_array('transaction date', $rowLower)) {
            $headerRow = $rowLower;
            $headerIndex = $index;
            echo "✓ Found header at row {$index}" . PHP_EOL;
            echo "  Headers: " . implode(' | ', array_filter($headerRow)) . PHP_EOL;
            break;
        }
    }
    
    if (!$headerRow) {
        echo "✗ Could not find header row!" . PHP_EOL;
        exit(1);
    }
    
    echo PHP_EOL;
    
    // Map columns
    echo "Mapping columns..." . PHP_EOL;
    $columnMap = [
        'date' => ['date', 'trans date', 'transaction date', 'posting date', 'txn date'],
        'description' => ['description', 'details', 'narration', 'particulars', 'transaction details'],
        'debit' => ['debit', 'withdrawal', 'dr', 'debit amount'],
        'credit' => ['credit', 'deposit', 'cr', 'credit amount'],
        'balance' => ['balance', 'book balance', 'running balance', 'closing balance'],
    ];
    
    $columnIndexes = [];
    foreach ($columnMap as $standard => $alternatives) {
        foreach ($alternatives as $alt) {
            $index = array_search($alt, $headerRow);
            if ($index !== false) {
                $columnIndexes[$standard] = $index;
                echo "  ✓ {$standard} => column {$index} ('{$alt}')" . PHP_EOL;
                break;
            }
        }
        
        if (!isset($columnIndexes[$standard])) {
            echo "  ✗ Missing column: {$standard}" . PHP_EOL;
        }
    }
    
    if (count($columnIndexes) < 5) {
        echo PHP_EOL . "✗ Missing required columns!" . PHP_EOL;
        exit(1);
    }
    
    echo PHP_EOL;
    
    // Try to process a few rows
    echo "Processing first 3 data rows..." . PHP_EOL;
    $dataRows = array_slice($data, $headerIndex + 1, 3);
    
    foreach ($dataRows as $i => $row) {
        try {
            echo "Row " . ($i + 1) . ": ";
            
            if (empty(array_filter($row))) {
                echo "EMPTY - skipped" . PHP_EOL;
                continue;
            }
            
            $dateVal = $row[$columnIndexes['date']] ?? null;
            $desc = $row[$columnIndexes['description']] ?? null;
            $debit = $row[$columnIndexes['debit']] ?? '';
            $credit = $row[$columnIndexes['credit']] ?? '';
            $balance = $row[$columnIndexes['balance']] ?? '';
            
            echo "Date={$dateVal} | Desc={$desc} | Debit={$debit} | Credit={$credit} | Balance={$balance}" . PHP_EOL;
            
        } catch (\Exception $e) {
            echo "ERROR: " . $e->getMessage() . PHP_EOL;
        }
    }
    
    echo PHP_EOL . "✓ Test completed successfully!" . PHP_EOL;
    
} catch (\Exception $e) {
    echo PHP_EOL . "✗ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
}

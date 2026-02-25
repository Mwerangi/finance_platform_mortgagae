<?php

namespace Database\Factories;

use App\Enums\ImportStatus;
use App\Models\Application;
use App\Models\BankStatementImport;
use App\Models\Customer;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankStatementImport>
 */
class BankStatementImportFactory extends Factory
{
    protected $model = BankStatementImport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statementMonths = fake()->numberBetween(3, 12);
        $startDate = now()->subMonths($statementMonths);
        $endDate = now();

        return [
            'customer_id' => Customer::factory(),
            'institution_id' => Institution::factory(),
            'application_id' => Application::factory(),
            'uploaded_by' => User::factory(),
            'file_path' => 'bank-statements/' . fake()->uuid() . '.xlsx',
            'file_name' => 'bank_statement_' . fake()->date() . '.xlsx',
            'file_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'file_size' => fake()->numberBetween(50000, 5000000),
            'import_status' => ImportStatus::PENDING,
            'rows_total' => fake()->numberBetween(100, 5000),
            'rows_processed' => 0,
            'rows_failed' => 0,
            'statement_start_date' => $startDate,
            'statement_end_date' => $endDate,
            'statement_months' => $statementMonths,
            'processing_started_at' => null,
            'processing_completed_at' => null,
            'error_log' => null,
            'processing_notes' => null,
        ];
    }

    /**
     * Indicate that the import is completed.
     */
    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            $rowsTotal = $attributes['rows_total'] ?? 100;
            $rowsFailed = fake()->numberBetween(0, (int)($rowsTotal * 0.05)); // Max 5% failure
            
            return [
                'import_status' => ImportStatus::COMPLETED,
                'rows_processed' => $rowsTotal - $rowsFailed,
                'rows_failed' => $rowsFailed,
                'processing_started_at' => now()->subMinutes(10),
                'processing_completed_at' => now()->subMinutes(5),
            ];
        });
    }

    /**
     * Indicate that the import is processing.
     */
    public function processing(): Factory
    {
        return $this->state(function (array $attributes) {
            $rowsTotal = $attributes['rows_total'] ?? 100;
            $rowsProcessed = fake()->numberBetween(1, $rowsTotal - 1);
            
            return [
                'import_status' => ImportStatus::PROCESSING,
                'rows_processed' => $rowsProcessed,
                'processing_started_at' => now()->subMinutes(5),
            ];
        });
    }

    /**
     * Indicate that the import has failed.
     */
    public function failed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'import_status' => ImportStatus::FAILED,
                'processing_started_at' => now()->subMinutes(10),
                'processing_completed_at' => now()->subMinutes(9),
                'error_log' => json_encode([
                    'error' => 'Invalid file format',
                    'message' => 'The uploaded file does not match the expected template.',
                ]),
            ];
        });
    }
}

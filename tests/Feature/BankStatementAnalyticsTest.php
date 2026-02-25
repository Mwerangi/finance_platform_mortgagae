<?php

namespace Tests\Feature;

use App\Enums\ImportStatus;
use App\Models\BankStatementImport;
use App\Models\Customer;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BankStatementAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('private');
    }

    /** @test */
    public function it_can_upload_bank_statement()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $customer = Customer::factory()->create(['institution_id' => $institution->id]);

        // Create a sample Excel file
        $file = $this->createSampleBankStatementFile();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bank-statements', [
                'customer_id' => $customer->id,
                'file' => $file,
                'application_id' => null,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer_id',
                    'institution_id',
                    'file_name',
                    'file_size',
                    'import_status',
                    'rows_total',
                    'rows_processed',
                    'rows_failed',
                ],
            ]);

        $this->assertDatabaseHas('bank_statement_imports', [
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'import_status' => ImportStatus::PENDING->value,
        ]);

        Storage::disk('private')->assertExists(
            BankStatementImport::first()->file_path
        );
    }

    /** @test */
    public function it_can_list_bank_statements()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $customer = Customer::factory()->create(['institution_id' => $institution->id]);

        BankStatementImport::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/bank-statements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'customer_id',
                        'file_name',
                        'import_status',
                    ],
                ],
                'meta' => ['current_page', 'total'],
            ]);
    }

    /** @test */
    public function it_can_view_single_import_with_transactions()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $customer = Customer::factory()->create(['institution_id' => $institution->id]);

        $import = BankStatementImport::factory()->create([
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/bank-statements/{$import->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'customer',
                    'institution',
                    'file_name',
                    'import_status',
                    'transactions',
                ],
            ]);
    }

    /** @test */
    public function it_validates_file_type_on_upload()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $customer = Customer::factory()->create(['institution_id' => $institution->id]);

        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bank-statements', [
                'customer_id' => $customer->id,
                'file' => $invalidFile,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_file_size_on_upload()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $customer = Customer::factory()->create(['institution_id' => $institution->id]);

        // Create a file larger than 50MB
        $largeFile = UploadedFile::fake()->create('statement.xlsx', 60000);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/bank-statements', [
                'customer_id' => $customer->id,
                'file' => $largeFile,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_can_get_customer_statistics()
    {
        $institution = Institution::factory()->create();
        $user = User::factory()->create(['institution_id' => $institution->id]);
        $customer = Customer::factory()->create(['institution_id' => $institution->id]);

        BankStatementImport::factory()->count(5)->create([
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'import_status' => ImportStatus::COMPLETED,
        ]);

        BankStatementImport::factory()->count(2)->create([
            'customer_id' => $customer->id,
            'institution_id' => $institution->id,
            'import_status' => ImportStatus::PENDING,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/bank-statements/customers/{$customer->id}/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'total_imports',
                    'completed_imports',
                    'pending_imports',
                    'failed_imports',
                    'latest_import',
                ],
            ])
            ->assertJson([
                'data' => [
                    'total_imports' => 7,
                    'completed_imports' => 5,
                    'pending_imports' => 2,
                    'failed_imports' => 0,
                ],
            ]);
    }

    /**
     * Create a sample bank statement Excel file for testing.
     */
    private function createSampleBankStatementFile(): UploadedFile
    {
        // Create a simple CSV file (Excel alternative for testing)
        $csv = [
            ['Date', 'Description', 'Debit', 'Credit', 'Balance'],
            ['2026-01-01', 'Opening Balance', '', '', '1000000.00'],
            ['2026-01-05', 'Salary Payment from ABC Corp', '', '3500000.00', '4500000.00'],
            ['2026-01-10', 'Rent Payment', '800000.00', '', '3700000.00'],
            ['2026-01-15', 'Utility - TANESCO', '150000.00', '', '3550000.00'],
            ['2026-01-20', 'Grocery Shopping', '250000.00', '', '3300000.00'],
            ['2026-01-25', 'Loan Repayment - AZAM Bank', '500000.00', '', '2800000.00'],
            ['2026-02-05', 'Salary Payment from ABC Corp', '', '3500000.00', '6300000.00'],
            ['2026-02-10', 'Rent Payment', '800000.00', '', '5500000.00'],
            ['2026-02-15', 'Utility - TANESCO', '150000.00', '', '5350000.00'],
            ['2026-02-25', 'Loan Repayment - AZAM Bank', '500000.00', '', '4850000.00'],
        ];

        $filename = 'test_bank_statement.csv';
        $tmpPath = sys_get_temp_dir() . '/' . $filename;
        
        $file = fopen($tmpPath, 'w');
        foreach ($csv as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        return new UploadedFile($tmpPath, $filename, 'text/csv', null, true);
    }
}

<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Institution;
use App\Models\Application;
use App\Models\BankStatementImport;
use App\Models\StatementAnalytics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Integration tests for bank statement import and analysis workflow
 * Tests: Upload → Parse → Analyze → Generate Analytics
 */
class BankStatementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Institution $institution;
    protected Customer $customer;
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->institution = Institution::factory()->create();
        $this->user = User::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => 'active',
        ]);
        $this->customer = Customer::factory()->create([
            'institution_id' => $this->institution->id,
        ]);
        $this->application = Application::factory()->create([
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function user_can_upload_bank_statement_excel_file()
    {
        $file = UploadedFile::fake()->createWithContent(
            'bank_statement.xlsx',
            file_get_contents(__DIR__ . '/../../fixtures/sample_bank_statement.xlsx')
        );

        $response = $this->actingAs($this->user)
            ->postJson('/api/bank-statements/import', [
                'application_id' => $this->application->id,
                'customer_id' => $this->customer->id,
                'file' => $file,
                'bank_name' => 'CRDB Bank',
                'account_number' => '0123456789',
            ]);

        $response->assertStatus(202); // Accepted for processing
        $response->assertJsonStructure([
            'data' => [
                'import_id',
                'status',
                'message',
            ],
        ]);

        $this->assertDatabaseHas('bank_statement_imports', [
            'customer_id' => $this->customer->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function bank_statement_import_validates_file_format()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($this->user)
            ->postJson('/api/bank-statements/import', [
                'application_id' => $this->application->id,
                'customer_id' => $this->customer->id,
                'file' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function imported_statement_can_be_analyzed()
    {
        // Create a completed import
        $import = BankStatementImport::factory()->create([
            'institution_id' => $this->institution->id,
            'customer_id' => $this->customer->id,
            'application_id' => $this->application->id,
            'status' => 'completed',
            'rows_processed' => 150,
        ]);

        // Trigger analysis
        $response = $this->actingAs($this->user)
            ->postJson("/api/bank-statements/{$import->id}/analyze");

        $response->assertStatus(202);

        // Verify analytics job was queued
        $this->assertDatabaseHas('bank_statement_imports', [
            'id' => $import->id,
            'analysis_status' => 'pending',
        ]);
    }

    /** @test */
    public function statement_analytics_are_generated_correctly()
    {
        $import = BankStatementImport::factory()->create([
            'institution_id' => $this->institution->id,
            'customer_id' => $this->customer->id,
            'application_id' => $this->application->id,
            'status' => 'completed',
        ]);

        // Create analytics
        $analytics = StatementAnalytics::factory()->create([
            'bank_statement_import_id' => $import->id,
            'application_id' => $this->application->id,
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
            'avg_monthly_inflow' => 2500000,
            'avg_monthly_outflow' => 1800000,
            'estimated_net_income' => 1750000,
            'income_classification' => 'salary',
            'income_stability_score' => 85.5,
        ]);

        // Retrieve analytics
        $response = $this->actingAs($this->user)
            ->getJson("/api/applications/{$this->application->id}/analytics");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'avg_monthly_inflow',
                'avg_monthly_outflow',
                'estimated_net_income',
                'income_classification',
                'income_stability_score',
            ],
        ]);

        $this->assertEquals(2500000, $response->json('data.avg_monthly_inflow'));
    }

    /** @test */
    public function analytics_detects_debt_obligations()
    {
        $analytics = StatementAnalytics::factory()->create([
            'application_id' => $this->application->id,
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
            'detected_debts' => [
                ['lender' => 'Bank A', 'monthly_payment' => 250000, 'pattern' => 'regular'],
                ['lender' => 'MFI B', 'monthly_payment' => 150000, 'pattern' => 'regular'],
            ],
            'estimated_monthly_debt' => 400000,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/applications/{$this->application->id}/analytics");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data.detected_debts'));
        $this->assertEquals(400000, $response->json('data.estimated_monthly_debt'));
    }

    /** @test */
    public function analytics_identifies_income_type()
    {
        $salaryAnalytics = StatementAnalytics::factory()->salary()->create([
            'application_id' => $this->application->id,
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/applications/{$this->application->id}/analytics");

        $response->assertStatus(200);
        $this->assertEquals('salary', $response->json('data.income_classification'));
    }

    /** @test */
    public function risk_flags_are_detected_in_statement()
    {
        $analytics = StatementAnalytics::factory()->create([
            'application_id' => $this->application->id,
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
            'bounce_count' => 3,
            'negative_balance_days' => 8,
            'gambling_transaction_count' => 5,
            'risk_flags' => [
                'multiple_bounced_checks',
                'frequent_negative_balance',
                'gambling_detected',
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/applications/{$this->application->id}/analytics");

        $response->assertStatus(200);
        $this->assertGreaterThan(0, count($response->json('data.risk_flags')));
        $this->assertContains('multiple_bounced_checks', $response->json('data.risk_flags'));
    }
}

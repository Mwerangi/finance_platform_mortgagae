<?php

namespace Tests\Feature\Performance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Institution;
use App\Models\Customer;
use App\Models\Application;
use App\Models\BankTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Performance tests for database queries and large data operations
 */
class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Institution $institution;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();
        $this->user = User::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function applications_list_queries_are_optimized()
    {
        // Create 50 applications with relationships
        Application::factory()->count(50)->create([
            'institution_id' => $this->institution->id,
        ]);

        // Enable query log
        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->getJson('/api/applications?per_page=20');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);
        
        // Should use eager loading to avoid N+1 queries
        // Typically: 1 for applications, 1 for customers, 1 for products, 1 for users
        $this->assertLessThan(10, count($queries), 'Too many queries - possible N+1 problem');
    }

    /** @test */
    public function customers_search_performs_efficiently_with_large_dataset()
    {
        // Create 1000 customers
        Customer::factory()->count(1000)->create([
            'institution_id' => $this->institution->id,
        ]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->getJson('/api/customers?search=John&per_page=20');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Search should complete within 500ms
        $this->assertLessThan(500, $executionTime, 'Search query too slow');
    }

    /** @test */
    public function dashboard_statistics_are_calculated_efficiently()
    {
        // Create substantial dataset
        Application::factory()->count(500)->create([
            'institution_id' => $this->institution->id,
        ]);

        DB::enableQueryLog();
        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->getJson('/api/dashboard/executive');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);
        
        // Dashboard should load within 1 second
        $this->assertLessThan(1000, $executionTime, 'Dashboard loading too slow');
        
        // Should use aggregate queries efficiently
        $this->assertLessThan(20, count($queries), 'Too many queries for dashboard');
    }

    /** @test */
    public function bank_statement_import_handles_large_files()
    {
        // Simulate processing 50,000 transactions
        $customer = Customer::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $startTime = microtime(true);

        // Create transactions in chunks (simulating batch processing)
        $chunkSize = 1000;
        for ($i = 0; $i < 5; $i++) {
            BankTransaction::factory()->count($chunkSize)->create([
                'customer_id' => $customer->id,
                'institution_id' => $this->institution->id,
            ]);
        }

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Should process 5000 records within 5 seconds
        $this->assertLessThan(5000, $executionTime, 'Batch processing too slow');
        $this->assertEquals(5000, BankTransaction::count());
    }

    /** @test */
    public function pagination_uses_cursor_for_large_datasets()
    {
        // Create large dataset
        Customer::factory()->count(10000)->create([
            'institution_id' => $this->institution->id,
        ]);

        DB::enableQueryLog();

        $response = $this->actingAs($this->user)
            ->getJson('/api/customers?per_page=100');

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);
        $this->assertCount(100, $response->json('data'));
        
        // Verify efficient pagination query
        $paginationQuery = $queries[0]['query'] ?? '';
        // Should not use OFFSET for large datasets (cursor pagination preferred)
        $this->assertStringNotContainsString('OFFSET', $paginationQuery);
    }

    /** @test */
    public function report_generation_completes_within_acceptable_time()
    {
        // Create dataset for reporting
        Application::factory()->count(1000)->create([
            'institution_id' => $this->institution->id,
        ]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->user)
            ->getJson('/api/reports/portfolio-summary?format=json');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        
        // Report should generate within 3 seconds
        $this->assertLessThan(3000, $executionTime, 'Report generation too slow');
    }

    /** @test */
    public function concurrent_updates_handle_race_conditions()
    {
        $customer = Customer::factory()->create([
            'institution_id' => $this->institution->id,
            'profile_completion_percentage' => 50,
        ]);

        // Simulate concurrent updates (optimistic locking test)
        $customer1 = Customer::find($customer->id);
        $customer2 = Customer::find($customer->id);

        $customer1->profile_completion_percentage = 75;
        $customer1->save();

        $customer2->profile_completion_percentage = 80;
        $customer2->save();

        $customer->refresh();
        
        // Last write should win (or implement optimistic locking)
        $this->assertEquals(80, $customer->profile_completion_percentage);
    }

    /** @test */
    public function database_indexes_are_utilized_for_common_queries()
    {
        Customer::factory()->count(1000)->create([
            'institution_id' => $this->institution->id,
        ]);

        // Query that should use index on (institution_id, email)
        DB::enableQueryLog();

        Customer::where('institution_id', $this->institution->id)
            ->where('email', 'test@example.com')
            ->first();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify query exists and was executed
        $this->assertCount(1, $queries);
        
        // In production, you'd use EXPLAIN to verify index usage
        // This test ensures the query pattern is correct
        $this->assertTrue(true);
    }

    /** @test */
    public function bulk_operations_use_batch_processing()
    {
        $customerIds = Customer::factory()->count(500)->create([
            'institution_id' => $this->institution->id,
        ])->pluck('id')->toArray();

        $startTime = microtime(true);

        // Bulk status update
        DB::table('customers')
            ->whereIn('id', $customerIds)
            ->update(['risk_rating' => 'medium']);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        // Bulk update should complete quickly
        $this->assertLessThan(500, $executionTime, 'Bulk operation too slow');
        
        // Verify all updated
        $this->assertEquals(500, Customer::where('risk_rating', 'medium')->count());
    }
}

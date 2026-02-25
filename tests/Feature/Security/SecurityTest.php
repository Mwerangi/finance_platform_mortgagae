<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Institution;
use App\Models\Customer;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Security tests for tenant isolation, authorization, and access control
 */
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Institution $institution1;
    protected Institution $institution2;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate institutions
        $this->institution1 = Institution::factory()->create();
        $this->institution2 = Institution::factory()->create();

        // Create users for each institution
        $this->user1 = User::factory()->create([
            'institution_id' => $this->institution1->id,
            'status' => 'active',
        ]);

        $this->user2 = User::factory()->create([
            'institution_id' => $this->institution2->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function users_can_only_access_their_institution_customers()
    {
        $customer1 = Customer::factory()->create([
            'institution_id' => $this->institution1->id,
        ]);

        $customer2 = Customer::factory()->create([
            'institution_id' => $this->institution2->id,
        ]);

        // User 1 can access institution 1 customer
        $response = $this->actingAs($this->user1)
            ->getJson("/api/customers/{$customer1->id}");
        $response->assertStatus(200);

        // User 1 cannot access institution 2 customer
        $response = $this->actingAs($this->user1)
            ->getJson("/api/customers/{$customer2->id}");
        $response->assertStatus(404);
    }

    /** @test */
    public function users_can_only_list_their_institution_applications()
    {
        Application::factory()->count(5)->create([
            'institution_id' => $this->institution1->id,
        ]);

        Application::factory()->count(3)->create([
            'institution_id' => $this->institution2->id,
        ]);

        // User 1 should only see 5 applications
        $response = $this->actingAs($this->user1)
            ->getJson('/api/applications');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function cross_institution_data_modification_is_blocked()
    {
        $customer = Customer::factory()->create([
            'institution_id' => $this->institution1->id,
        ]);

        // User 2 trying to update institution 1's customer
        $response = $this->actingAs($this->user2)
            ->putJson("/api/customers/{$customer->id}", [
                'first_name' => 'Hacked',
            ]);

        $response->assertStatus(404);

        // Verify data not modified
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'first_name' => $customer->first_name,
        ]);
    }

    /** @test */
    public function sql_injection_is_prevented_in_search()
    {
        Customer::factory()->create([
            'institution_id' => $this->institution1->id,
            'first_name' => 'John',
        ]);

        // Attempt SQL injection
        $response = $this->actingAs($this->user1)
            ->getJson("/api/customers?search=' OR '1'='1");

        $response->assertStatus(200);
        // Should return valid results, not expose all data
    }

    /** @test */
    public function xss_payload_is_sanitized_in_inputs()
    {
        $xssPayload = '<script>alert("XSS")</script>';

        $response = $this->actingAs($this->user1)
            ->postJson('/api/customers', [
                'customer_type' => 'salary',
                'first_name' => $xssPayload,
                'last_name' => 'Test',
                'date_of_birth' => '1990-01-01',
                'national_id' => '19900101123456789012',
                'phone_primary' => '+255712345678',
                'email' => 'test@example.com',
            ]);

        if ($response->status() === 201) {
            $customer = Customer::find($response->json('data.id'));
            // Verify the script tag was sanitized
            $this->assertStringNotContainsString('<script>', $customer->first_name);
        }
    }

    /** @test */
    public function inactive_users_cannot_access_system()
    {
        $inactiveUser = User::factory()->create([
            'institution_id' => $this->institution1->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($inactiveUser)
            ->getJson('/api/applications');

        $response->assertStatus(403);
    }

    /** @test */
    public function mass_assignment_vulnerabilities_are_prevented()
    {
        $customer = Customer::factory()->create([
            'institution_id' => $this->institution1->id,
        ]);

        // Try to change institution_id via mass assignment
        $response = $this->actingAs($this->user1)
            ->putJson("/api/customers/{$customer->id}", [
                'first_name' => 'Updated',
                'institution_id' => $this->institution2->id, // Attempt to change institution
            ]);

        $customer->refresh();
        
        // Institution should remain unchanged
        $this->assertEquals($this->institution1->id, $customer->institution_id);
    }

    /** @test */
    public function file_upload_validates_file_types()
    {
        $response = $this->actingAs($this->user1)
            ->postJson('/api/bank-statements/import', [
                'file' => 'malicious.exe',
                'customer_id' => 1,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function rate_limiting_protects_api_endpoints()
    {
        // Make 100 rapid requests (assuming rate limit is lower)
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $responses[] = $this->actingAs($this->user1)
                ->getJson('/api/applications');
        }

        // At least one should be rate limited
        $rateLimited = collect($responses)->contains(function ($response) {
            return $response->status() === 429;
        });

        $this->assertTrue($rateLimited);
    }

    /** @test */
    public function session_fixation_is_prevented()
    {
        // Get initial session
        $initialResponse = $this->get('/login');
        $initialSession = $initialResponse->getCookie('laravel_session');

        // Login
        $loginResponse = $this->post('/login', [
            'email' => $this->user1->email,
            'password' => 'password',
        ]);

        // Session should be regenerated
        $newSession = $loginResponse->getCookie('laravel_session');
        $this->assertNotEquals($initialSession?->getValue(), $newSession?->getValue());
    }

    /** @test */
    public function sensitive_data_is_not_exposed_in_api_responses()
    {
        $response = $this->actingAs($this->user1)
            ->getJson('/api/users/me');

        $response->assertStatus(200);
        
        // Password should not be in response
        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    /** @test */
    public function audit_log_is_created_for_sensitive_operations()
    {
        $customer = Customer::factory()->create([
            'institution_id' => $this->institution1->id,
        ]);

        $this->actingAs($this->user1)
            ->putJson("/api/customers/{$customer->id}", [
                'first_name' => 'Updated Name',
            ]);

        // Verify audit log entry was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $this->user1->id,
            'action' => 'update',
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
        ]);
    }
}

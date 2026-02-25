<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Institution;
use App\Models\LoanProduct;
use App\Models\Application;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for the full application workflow
 * Tests: Draft → Submit → Review → Approve/Reject → Disbursement
 */
class ApplicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $creditOfficer;
    protected User $supervisor;
    protected Institution $institution;
    protected Customer $customer;
    protected LoanProduct $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up institution
        $this->institution = Institution::factory()->create();

        // Create roles
        $creditRole = Role::create(['name' => 'Credit Officer', 'slug' => 'credit-officer']);
        $supervisorRole = Role::create(['name' => 'Supervisor', 'slug' => 'supervisor']);

        // Create users
        $this->creditOfficer = User::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => 'active',
        ]);
        $this->creditOfficer->roles()->attach($creditRole);

        $this->supervisor = User::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => 'active',
        ]);
        $this->supervisor->roles()->attach($supervisorRole);

        // Create customer and product
        $this->customer = Customer::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $this->product = LoanProduct::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function credit_officer_can_create_draft_application()
    {
        $response = $this->actingAs($this->creditOfficer)
            ->postJson('/api/applications', [
                'customer_id' => $this->customer->id,
                'loan_product_id' => $this->product->id,
                'requested_amount' => 5000000,
                'requested_tenure_months' => 24,
                'property_value' => 8000000,
                'property_type' => 'residential',
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'application_number',
                'status',
                'customer_id',
                'loan_product_id',
            ],
        ]);

        $this->assertDatabaseHas('applications', [
            'customer_id' => $this->customer->id,
            'status' => 'draft',
            'requested_amount' => 5000000,
        ]);
    }

    /** @test */
    public function application_progresses_through_full_lifecycle()
    {
        // 1. Create draft application
        $application = Application::factory()->create([
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
            'loan_product_id' => $this->product->id,
            'created_by' => $this->creditOfficer->id,
            'status' => 'draft',
            'requested_amount' => 5000000,
            'requested_tenure_months' => 24,
        ]);

        $this->assertEquals('draft', $application->fresh()->status->value);

        // 2. Submit application
        $response = $this->actingAs($this->creditOfficer)
            ->postJson("/api/applications/{$application->id}/submit");

        $response->assertStatus(200);
        $this->assertEquals('submitted', $application->fresh()->status->value);
        $this->assertNotNull($application->fresh()->submitted_at);

        // 3. Assign to reviewer (supervisor)
        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/applications/{$application->id}/assign", [
                'reviewer_id' => $this->supervisor->id,
            ]);

        $response->assertStatus(200);

        // 4. Move to under_review
        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/applications/{$application->id}/start-review");

        $response->assertStatus(200);
        $this->assertEquals('under_review', $application->fresh()->status->value);

        // 5. Approve application
        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/applications/{$application->id}/approve", [
                'approved_amount' => 4500000,
                'approved_tenure_months' => 24,
                'notes' => 'Approved with reduced amount',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('approved', $application->fresh()->status->value);
        $this->assertNotNull($application->fresh()->approved_at);
        $this->assertEquals($this->supervisor->id, $application->fresh()->approved_by);
    }

    /** @test */
    public function supervisor_can_reject_application_with_reason()
    {
        $application = Application::factory()->create([
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
            'loan_product_id' => $this->product->id,
            'status' => 'under_review',
            'requested_amount' => 15000000,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->postJson("/api/applications/{$application->id}/reject", [
                'rejection_reason' => 'Insufficient income documentation',
                'notes' => 'Customer needs to provide 6 months bank statements',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('rejected', $application->fresh()->status->value);
        $this->assertNotNull($application->fresh()->rejected_at);
    }

    /** @test */
    public function cannot_approve_application_without_proper_authorization()
    {
        $application = Application::factory()->create([
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
            'status' => 'under_review',
        ]);

        // Credit officer trying to approve (should fail)
        $response = $this->actingAs($this->creditOfficer)
            ->postJson("/api/applications/{$application->id}/approve", [
                'approved_amount' => 5000000,
                'approved_tenure_months' => 24,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function application_state_transitions_are_validated()
    {
        $application = Application::factory()->create([
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
            'status' => 'approved',
        ]);

        // Cannot submit an already approved application
        $response = $this->actingAs($this->creditOfficer)
            ->postJson("/api/applications/{$application->id}/submit");

        $response->assertStatus(422);
    }

    /** @test */
    public function tenant_isolation_is_enforced_for_applications()
    {
        $otherInstitution = Institution::factory()->create();
        $otherUser = User::factory()->create([
            'institution_id' => $otherInstitution->id,
        ]);

        $application = Application::factory()->create([
            'customer_id' => $this->customer->id,
            'institution_id' => $this->institution->id,
        ]);

        // User from another institution cannot access
        $response = $this->actingAs($otherUser)
            ->getJson("/api/applications/{$application->id}");

        $response->assertStatus(404);
    }
}

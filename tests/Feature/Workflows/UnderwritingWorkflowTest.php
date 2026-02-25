<?php

namespace Tests\Feature\Workflows;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Institution;
use App\Models\Application;
use App\Models\LoanProduct;
use App\Models\StatementAnalytics;
use App\Models\EligibilityAssessment;
use App\Models\UnderwritingDecision;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration tests for underwriting workflow
 * Tests: Analytics → Eligibility Check → Underwriting Decision → Override (if needed)
 */
class UnderwritingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $underwriter;
    protected User $manager;
    protected Institution $institution;
    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institution = Institution::factory()->create();
        
        $this->underwriter = User::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => 'active',
        ]);

        $this->manager = User::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => 'active',
        ]);

        $customer = Customer::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $product = LoanProduct::factory()->create([
            'institution_id' => $this->institution->id,
            'max_dsr_salary_percentage' => 50,
            'max_dti_percentage' => 45,
            'max_ltv_percentage' => 80,
        ]);

        $this->application = Application::factory()->create([
            'customer_id' => $customer->id,
            'institution_id' => $this->institution->id,
            'loan_product_id' => $product->id,
            'status' => 'under_review',
            'requested_amount' => 10000000,
            'requested_tenure_months' => 36,
            'property_value' => 15000000,
        ]);
    }

    /** @test */
    public function eligibility_assessment_can_be_triggered()
    {
        // Create statement analytics first
        StatementAnalytics::factory()->create([
            'application_id' => $this->application->id,
            'customer_id' => $this->application->customer_id,
            'institution_id' => $this->institution->id,
            'avg_monthly_inflow' => 3000000,
            'estimated_net_income' => 2100000,
            'estimated_monthly_debt' => 300000,
            'income_classification' => 'salary',
        ]);

        $response = $this->actingAs($this->underwriter)
            ->postJson("/api/applications/{$this->application->id}/assess-eligibility");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'system_decision',
                'dti_ratio',
                'dsr_ratio',
                'ltv_ratio',
                'risk_grade',
                'max_loan_from_affordability',
            ],
        ]);
    }

    /** @test */
    public function application_within_policy_is_recommended()
    {
        StatementAnalytics::factory()->create([
            'application_id' => $this->application->id,
            'customer_id' => $this->application->customer_id,
            'institution_id' => $this->institution->id,
            'avg_monthly_inflow' => 4000000,
            'estimated_net_income' => 2800000,
            'estimated_monthly_debt' => 200000,
            'income_stability_score' => 90,
            'income_classification' => 'salary',
        ]);

        $response = $this->actingAs($this->underwriter)
            ->postJson("/api/applications/{$this->application->id}/assess-eligibility");

        $response->assertStatus(200);
        $this->assertEquals('eligible', $response->json('data.system_decision'));
        $this->assertTrue($response->json('data.is_recommendable'));
    }

    /** @test */
    public function application_outside_policy_requires_override()
    {
        // High debt, low income scenario
        StatementAnalytics::factory()->create([
            'application_id' => $this->application->id,
            'customer_id' => $this->application->customer_id,
            'institution_id' => $this->institution->id,
            'avg_monthly_inflow' => 1500000,
            'estimated_net_income' => 1000000,
            'estimated_monthly_debt' => 800000, // Very high debt
            'income_classification' => 'salary',
        ]);

        $response = $this->actingAs($this->underwriter)
            ->postJson("/api/applications/{$this->application->id}/assess-eligibility");

        $response->assertStatus(200);
        $this->assertEquals('outside_policy', $response->json('data.system_decision'));
        $this->assertGreaterThan(0, count($response->json('data.policy_breaches')));
    }

    /** @test */
    public function underwriter_can_create_decision()
    {
        $eligibility = EligibilityAssessment::factory()->create([
            'application_id' => $this->application->id,
            'institution_id' => $this->institution->id,
            'system_decision' => 'eligible',
            'dti_ratio' => 35.5,
            'dsr_ratio' => 42.0,
            'ltv_ratio' => 66.67,
            'risk_grade' => 'B',
        ]);

        $response = $this->actingAs($this->underwriter)
            ->postJson("/api/applications/{$this->application->id}/underwriting-decision", [
                'decision' => 'recommend_approval',
                'recommended_amount' => 9500000,
                'recommended_tenure_months' => 36,
                'notes' => 'Good income stability and low risk profile',
                'conditions' => [
                    'Provide employment letter',
                    'Update KYC documents',
                ],
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('underwriting_decisions', [
            'application_id' => $this->application->id,
            'decision' => 'recommend_approval',
            'recommended_amount' => 9500000,
        ]);
    }

    /** @test */
    public function manager_can_override_outside_policy_application()
    {
        $eligibility = EligibilityAssessment::factory()->create([
            'application_id' => $this->application->id,
            'system_decision' => 'outside_policy',
            'policy_breaches' => [
                ['rule' => 'max_dsr_exceeded', 'threshold' => 50, 'actual' => 58],
            ],
        ]);

        $response = $this->actingAs($this->manager)
            ->postJson("/api/applications/{$this->application->id}/override", [
                'override_decision' => 'approve',
                'override_reason' => 'Strong employment history with government sector',
                'override_justification' => 'Customer has 10 years continuous employment, low volatility',
                'override_amount' => 8000000,
                'override_tenure_months' => 30,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('underwriting_decisions', [
            'application_id' => $this->application->id,
            'has_override' => true,
            'override_by' => $this->manager->id,
        ]);
    }

    /** @test */
    public function stress_test_can_be_performed()
    {
        StatementAnalytics::factory()->create([
            'application_id' => $this->application->id,
            'customer_id' => $this->application->customer_id,
            'institution_id' => $this->institution->id,
            'estimated_net_income' => 2500000,
        ]);

        $response = $this->actingAs($this->underwriter)
            ->postJson("/api/applications/{$this->application->id}/stress-test", [
                'income_shock_percent' => 20, // 20% income drop
                'rate_increase_percent' => 5, // 5% rate increase
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'scenario',
                'stressed_installment',
                'stressed_surplus',
                'passes',
            ],
        ]);
    }

    /** @test */
    public function underwriting_history_is_maintained()
    {
        // Create multiple decisions/assessments
        EligibilityAssessment::factory()->count(2)->create([
            'application_id' => $this->application->id,
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->actingAs($this->underwriter)
            ->getJson("/api/applications/{$this->application->id}/underwriting-history");

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
    }
}

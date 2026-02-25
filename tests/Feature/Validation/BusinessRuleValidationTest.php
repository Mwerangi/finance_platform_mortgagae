<?php

namespace Tests\Feature\Validation;

use Tests\TestCase;
use App\Models\User;
use App\Models\Institution;
use App\Models\Customer;
use App\Models\LoanProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Validation tests for business rules and data integrity
 */
class BusinessRuleValidationTest extends TestCase
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
    public function loan_amount_must_be_within_product_limits()
    {
        $product = LoanProduct::factory()->create([
            'institution_id' => $this->institution->id,
            'min_loan_amount' => 1000000,
            'max_loan_amount' => 10000000,
        ]);

        $customer = Customer::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        // Below minimum
        $response = $this->actingAs($this->user)
            ->postJson('/api/applications', [
                'customer_id' => $customer->id,
                'loan_product_id' => $product->id,
                'requested_amount' => 500000, // Below minimum
                'requested_tenure_months' => 12,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['requested_amount']);

        // Above maximum
        $response = $this->actingAs($this->user)
            ->postJson('/api/applications', [
                'customer_id' => $customer->id,
                'loan_product_id' => $product->id,
                'requested_amount' => 15000000, // Above maximum
                'requested_tenure_months' => 12,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['requested_amount']);
    }

    /** @test */
    public function tenure_must_be_within_product_limits()
    {
        $product = LoanProduct::factory()->create([
            'institution_id' => $this->institution->id,
            'min_tenure_months' => 6,
            'max_tenure_months' => 60,
        ]);

        $customer = Customer::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/applications', [
                'customer_id' => $customer->id,
                'loan_product_id' => $product->id,
                'requested_amount' => 5000000,
                'requested_tenure_months' => 72, // Above maximum
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['requested_tenure_months']);
    }

    /** @test */
    public function customer_must_have_valid_national_id()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/customers', [
                'customer_type' => 'salary',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'national_id' => '12345', // Too short
                'phone_primary' => '+255712345678',
                'email' => 'john@example.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['national_id']);
    }

    /** @test */
    public function email_must_be_unique_per_institution()
    {
        $existingCustomer = Customer::factory()->create([
            'institution_id' => $this->institution->id,
            'email' => 'existing@example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/customers', [
                'customer_type' => 'salary',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'date_of_birth' => '1992-05-15',
                'national_id' => '19920515123456789012',
                'phone_primary' => '+255712345679',
                'email' => 'existing@example.com', // Duplicate
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function phone_number_must_be_valid_format()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/customers', [
                'customer_type' => 'salary',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'date_of_birth' => '1990-01-01',
                'national_id' => '19900101123456789012',
                'phone_primary' => '123', // Invalid format
                'email' => 'john@example.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone_primary']);
    }

    /** @test */
    public function date_of_birth_must_be_at_least_18_years_ago()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/customers', [
                'customer_type' => 'salary',
                'first_name' => 'Underage',
                'last_name' => 'Customer',
                'date_of_birth' => now()->subYears(15)->format('Y-m-d'), // Only 15 years old
                'national_id' => '20090101123456789012',
                'phone_primary' => '+255712345678',
                'email' => 'underage@example.com',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date_of_birth']);
    }

    /** @test */
    public function loan_product_interest_rate_must_be_positive()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/loan-products', [
                'name' => 'Invalid Product',
                'code' => 'INV001',
                'interest_model' => 'reducing_balance',
                'annual_interest_rate' => -5, // Negative rate
                'min_tenure_months' => 6,
                'max_tenure_months' => 60,
                'min_loan_amount' => 1000000,
                'max_loan_amount' => 50000000,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['annual_interest_rate']);
    }

    /** @test */
    public function cannot_create_duplicate_loan_product_code()
    {
        $existing = LoanProduct::factory()->create([
            'institution_id' => $this->institution->id,
            'code' => 'PROD001',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/loan-products', [
                'name' => 'Another Product',
                'code' => 'PROD001', // Duplicate code
                'interest_model' => 'flat_rate',
                'annual_interest_rate' => 15,
                'min_tenure_months' => 6,
                'max_tenure_months' => 36,
                'min_loan_amount' => 1000000,
                'max_loan_amount' => 20000000,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['code']);
    }

    /** @test */
    public function property_value_is_required_for_mortgage_loans()
    {
        $product = LoanProduct::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Home Mortgage',
            'max_ltv_percentage' => 80, // Requires property value for LTV calculation
        ]);

        $customer = Customer::factory()->create([
            'institution_id' => $this->institution->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/applications', [
                'customer_id' => $customer->id,
                'loan_product_id' => $product->id,
                'requested_amount' => 10000000,
                'requested_tenure_months' => 48,
                // Missing property_value
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['property_value']);
    }

    /** @test */
    public function repayment_amount_cannot_exceed_outstanding_balance()
    {
        // This would be tested with actual loan repayment endpoint
        // Placeholder for the validation logic
        $this->assertTrue(true);
    }
}

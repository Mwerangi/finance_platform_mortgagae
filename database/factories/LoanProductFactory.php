<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\LoanProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LoanProduct>
 */
class LoanProductFactory extends Factory
{
    protected $model = LoanProduct::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'name' => fake()->randomElement(['Home Loan', 'Personal Loan', 'Business Loan', 'Auto Loan']),
            'code' => fake()->unique()->bothify('PROD-###'),
            'description' => fake()->sentence(),
            'interest_model' => fake()->randomElement(['reducing_balance', 'flat_rate']),
            'annual_interest_rate' => fake()->randomFloat(2, 12, 30),
            'rate_type' => 'fixed',
            'min_tenure_months' => 6,
            'max_tenure_months' => fake()->randomElement([12, 24, 36, 48, 60]),
            'min_loan_amount' => 1000000,
            'max_loan_amount' => fake()->randomElement([10000000, 20000000, 50000000]),
            'max_ltv_percentage' => fake()->randomFloat(2, 70, 90),
            'max_dsr_salary_percentage' => fake()->randomFloat(2, 40, 60),
            'max_dti_percentage' => fake()->randomFloat(2, 40, 50),
            'business_safety_factor' => 0.70,
            'max_dsr_business_percentage' => fake()->randomFloat(2, 40, 60),
            'fees' => [
                'processing_fee' => ['type' => 'percentage', 'value' => 1.0],
                'legal_fee' => ['type' => 'flat', 'value' => 500000],
            ],
            'penalties' => [
                'late_payment_fee' => ['type' => 'percentage', 'value' => 2.0],
            ],
            'credit_policy' => [
                'min_credit_score' => 600,
                'max_dpd_allowed' => 30,
            ],
            'status' => 'active',
            'activated_at' => now(),
        ];
    }

    /**
     * Indicate that the product uses reducing balance.
     */
    public function reducingBalance(): static
    {
        return $this->state(fn (array $attributes) => [
            'interest_model' => 'reducing_balance',
        ]);
    }

    /**
     * Indicate that the product uses flat rate.
     */
    public function flatRate(): static
    {
        return $this->state(fn (array $attributes) => [
            'interest_model' => 'flat_rate',
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Customer;
use App\Models\Institution;
use App\Models\LoanProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'institution_id' => Institution::factory(),
            'loan_product_id' => LoanProduct::factory(),
            'created_by' => User::factory(),
            'application_number' => 'APP-' . fake()->unique()->numerify('######'),
            'status' => 'draft',
            'requested_amount' => fake()->randomFloat(2, 1000000, 20000000),
            'requested_tenure_months' => fake()->randomElement([12, 24, 36, 48, 60]),
            'property_type' => fake()->optional()->randomElement(['residential', 'commercial', 'land']),
            'property_value' => fake()->optional()->randomFloat(2, 5000000, 50000000),
            'property_address' => fake()->optional()->address(),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the application has been submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    /**
     * Indicate that the application is under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_review',
            'submitted_at' => now()->subDays(2),
            'reviewed_at' => now()->subDay(),
            'reviewed_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the application has been approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'submitted_at' => now()->subDays(5),
            'reviewed_at' => now()->subDays(3),
            'approved_at' => now()->subDay(),
            'reviewed_by' => User::factory(),
            'approved_by' => User::factory(),
        ]);
    }

    /**
     * Indicate that the application has been rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'submitted_at' => now()->subDays(5),
            'reviewed_at' => now()->subDays(3),
            'rejected_at' => now()->subDay(),
            'reviewed_by' => User::factory(),
        ]);
    }
}

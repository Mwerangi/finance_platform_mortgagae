<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'customer_code' => 'CUST' . fake()->unique()->numberBetween(10000, 99999),
            'customer_type' => fake()->randomElement(['salary', 'business', 'mixed']),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'date_of_birth' => fake()->date('Y-m-d', '-25 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced', 'widowed']),
            'national_id' => fake()->numerify('####################'),
            'tin' => fake()->optional()->numerify('##########'),
            'passport_number' => fake()->optional()->bothify('??######'),
            'phone_primary' => fake()->phoneNumber(),
            'phone_secondary' => fake()->optional()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'physical_address' => fake()->address(),
            'city' => fake()->city(),
            'region' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country' => 'Tanzania',
            'employer_name' => fake()->optional()->company(),
            'business_name' => fake()->optional()->company(),
            'occupation' => fake()->jobTitle(),
            'industry' => fake()->randomElement(['Banking', 'Manufacturing', 'Retail', 'Services', 'Agriculture']),
            'employment_start_date' => fake()->optional()->date('Y-m-d', '-5 years'),
            'next_of_kin_name' => fake()->name(),
            'next_of_kin_relationship' => fake()->randomElement(['spouse', 'parent', 'sibling', 'friend']),
            'next_of_kin_phone' => fake()->phoneNumber(),
            'next_of_kin_address' => fake()->address(),
            'profile_completion_percentage' => fake()->numberBetween(60, 100),
            'kyc_verified' => fake()->boolean(70),
            'kyc_verified_at' => fake()->optional()->dateTimeBetween('-1 year'),
            'notes' => fake()->optional()->paragraph(),
            'status' => 'active',
            'deactivated_at' => null,
        ];
    }

    /**
     * Indicate that the customer is a salary worker.
     */
    public function salary(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'salary',
        ]);
    }

    /**
     * Indicate that the customer is a business owner.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_type' => 'business',
        ]);
    }

    /**
     * Indicate that the customer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);
    }
}

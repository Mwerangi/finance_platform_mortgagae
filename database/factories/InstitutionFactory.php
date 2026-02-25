<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Institution>
 */
class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company() . ' Finance';
        
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'code' => fake()->unique()->bothify('INS###'),
            'description' => fake()->optional()->sentence(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'country' => 'Tanzania',
            'timezone' => 'Africa/Dar_es_Salaam',
            'currency' => 'TZS',
            'date_format' => 'Y-m-d',
            'branding' => null,
            'settings' => null,
            'status' => 'active',
            'activated_at' => now(),
            'deactivated_at' => null,
        ];
    }

    /**
     * Indicate that the institution is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);
    }
}

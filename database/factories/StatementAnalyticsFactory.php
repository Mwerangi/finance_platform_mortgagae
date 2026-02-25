<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\StatementAnalytics;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StatementAnalytics>
 */
class StatementAnalyticsFactory extends Factory
{
    protected $model = StatementAnalytics::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $avgMonthlyInflow = fake()->randomFloat(2, 1500000, 5000000);
        $avgMonthlyOutflow = fake()->randomFloat(2, 1000000, 3000000);
        $estimatedNetIncome = $avgMonthlyInflow * 0.7;
        
        return [
            'application_id' => Application::factory(),
            'statement_start_date' => now()->subMonths(6),
            'statement_end_date' => now(),
            'months_analyzed' => 6,
            'avg_monthly_inflow' => $avgMonthlyInflow,
            'avg_monthly_outflow' => $avgMonthlyOutflow,
            'estimated_net_income' => $estimatedNetIncome,
            'estimated_monthly_debt' => fake()->randomFloat(2, 100000, 500000),
            'income_classification' => fake()->randomElement(['salary', 'business', 'mixed']),
            'income_stability_score' => fake()->randomFloat(2, 50, 95),
            'cash_flow_volatility_score' => fake()->randomFloat(2, 10, 60),
            'negative_balance_days' => fake()->numberBetween(0, 5),
            'bounce_count' => fake()->numberBetween(0, 2),
            'detected_debts' => [
                ['lender' => 'Bank A', 'monthly_payment' => 150000],
                ['lender' => 'Bank B', 'monthly_payment' => 100000],
            ],
            'salary_deposits' => [
                ['date' => now()->subMonth()->format('Y-m-d'), 'amount' => 2000000],
                ['date' => now()->subMonths(2)->format('Y-m-d'), 'amount' => 2000000],
            ],
            'business_deposits' => [],
            'analyzed_at' => now(),
        ];
    }

    /**
     * Indicate salary income type.
     */
    public function salary(): static
    {
        return $this->state(fn (array $attributes) => [
            'income_classification' => 'salary',
            'income_stability_score' => fake()->randomFloat(2, 75, 95),
        ]);
    }

    /**
     * Indicate business income type.
     */
    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'income_classification' => 'business',
            'cash_flow_volatility_score' => fake()->randomFloat(2, 30, 70),
        ]);
    }
}

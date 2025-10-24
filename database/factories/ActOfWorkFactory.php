<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActOfWork>
 */
class ActOfWorkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => fake()->unique()->numerify('##########'),
            'status' => fake()->randomElement(['pending', 'processing', 'done', 'cancelled']),
            'period' => [
                'type' => 'month',
                'year' => fake()->year(),
                'month' => fake()->monthName(),
            ],
            'period_type' => fake()->randomElement(['day', 'week', 'month', 'quarter', 'year']),
            'period_year' => fake()->year(),
            'period_month' => fake()->monthName(),
            'user_id' => \App\Models\User::factory(),
            'date' => fake()->date(),
            'description' => fake()->optional()->paragraph(),
            'total_amount' => fake()->randomFloat(2, 100, 50000),
            'paid_amount' => fake()->randomFloat(2, 0, 25000),
            'file_excel' => fake()->optional()->url(),
            'sort' => fake()->numberBetween(0, 100),
            'telegram_status' => fake()->randomElement(['pending', 'send', 'error']),
            'type' => fake()->randomElement(['act', 'income', 'new_project']),
        ];
    }
}

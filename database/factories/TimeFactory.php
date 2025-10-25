<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\Time;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Time>
 */
class TimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'coefficient' => fake()->randomElement([0.5, 0.8, 1.0, 1.2, 1.5, 1.8, 2.0]),
            'duration' => fake()->numberBetween(900, 28800), // 15 minutes to 8 hours in seconds
            'status' => fake()->randomElement([
                Time::STATUS_NEW,
                Time::STATUS_IN_PROGRESS,
                Time::STATUS_COMPLETED,
                Time::STATUS_PLANNED,
            ]),
            'report_status' => fake()->randomElement(['not_submitted', 'submitted', 'approved']),
            'is_archived' => fake()->boolean(20), // 20% chance of being archived
        ];
    }

    /**
     * Indicate that the time record is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Time::STATUS_COMPLETED,
            'report_status' => 'submitted',
        ]);
    }

    /**
     * Indicate that the time record is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Time::STATUS_IN_PROGRESS,
        ]);
    }

    /**
     * Indicate that the time record is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => true,
        ]);
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActOfWorkDetail>
 */
class ActOfWorkDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'act_of_work_id' => \App\Models\ActOfWork::factory(),
            'time_id' => fake()->optional()->numberBetween(1, 1000),
            'task_gid' => fake()->optional()->numerify('####'),
            'project_gid' => fake()->optional()->numerify('##'),
            'project' => fake()->words(3, true),
            'task' => fake()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'amount' => fake()->randomFloat(2, 10, 5000),
            'hours' => fake()->randomFloat(2, 0.1, 40),
        ];
    }
}

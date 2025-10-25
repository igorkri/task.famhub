<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gid' => fake()->unique()->numerify('##########'),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'workspace_id' => Workspace::factory(),
        ];
    }
}

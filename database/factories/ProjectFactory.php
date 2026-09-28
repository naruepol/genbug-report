<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
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
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'project_code' => Str::upper(fake()->unique()->bothify('PRJ-####')),
            'name' => $name,
            'description' => fake()->paragraph(),
            'image_url' => null,
            'demo_url' => 'https://example.com/demo',
            'repository_url' => 'https://example.com/repository',
            'technology_stack' => 'Laravel, React, PostgreSQL',
            'presentation_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'status' => ProjectStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Draft]);
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Published]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Closed]);
    }

    public function reportingDisabled(): static
    {
        return $this->state(fn () => ['bug_reporting_enabled' => false]);
    }
}

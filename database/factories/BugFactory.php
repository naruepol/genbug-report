<?php

namespace Database\Factories;

use App\Enums\BugCategory;
use App\Enums\BugPriority;
use App\Enums\BugSeverity;
use App\Enums\BugStatus;
use App\Enums\VerificationStatus;
use App\Models\Bug;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bug>
 */
class BugFactory extends Factory
{
    /**
     * A new public report: Pending + Open, not assessed yet.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(BugCategory::cases()),
            'steps_to_reproduce' => "1. Open the page\n2. Click the button",
            'expected_result' => 'It works.',
            'actual_result' => 'Nothing happens.',
            'page_screen' => 'Home',
            'browser' => 'Chrome 140',
            'operating_system' => 'Android 16',
            'device' => 'Mobile',
            'reporter_name' => null,
            'reporter_email' => null,
            'reporter_ip' => fake()->ipv4(),
            'verification_status' => VerificationStatus::Pending,
            'status' => BugStatus::Open,
        ];
    }

    public function withReporter(string $name = 'Somchai Reporter', string $email = 'reporter@example.com'): static
    {
        return $this->state(fn () => ['reporter_name' => $name, 'reporter_email' => $email]);
    }

    public function verified(): static
    {
        return $this->state(fn () => ['verification_status' => VerificationStatus::Verified]);
    }

    public function assessed(BugSeverity $severity = BugSeverity::High, BugPriority $priority = BugPriority::High, int $score = 8): static
    {
        return $this->state(fn () => [
            'verification_status' => VerificationStatus::Verified,
            'severity' => $severity,
            'priority' => $priority,
            'score' => $score,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'google_id' => (string) fake()->unique()->numberBetween(100000000000, 999999999999),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'avatar_url' => null,
        ];
    }

    /**
     * A user whose email is in the ADMIN_EMAILS allowlist.
     */
    public function admin(?string $email = null): static
    {
        return $this->state(fn () => [
            'email' => $email ?? config('admin.emails.0', 'admin@example.com'),
        ]);
    }
}

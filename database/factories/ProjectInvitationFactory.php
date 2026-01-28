<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectInvitation>
 */
class ProjectInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement(['Developer', 'Viewer']),
            'token' => Str::uuid(),
            'status' => 'pending',
            'invited_by' => User::factory(),
            'expires_at' => fake()->optional()->dateTimeBetween('+1 day', '+7 days'),
            'accepted_at' => null,
        ];
    }
}

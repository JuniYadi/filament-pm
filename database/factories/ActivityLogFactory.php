<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $task = Task::inRandomOrder()->first() ?? Task::factory()->create();
        $actions = ['created', 'status_changed', 'assigned', 'title_updated', 'description_updated'];

        return [
            'task_id' => $task->id,
            'user_id' => User::inRandomOrder()->first()?->id,
            'action' => fake()->randomElement($actions),
            'old_values' => fake()->randomElement([
                null,
                ['status' => 'todo'],
                ['assigned_to' => null],
            ]),
            'new_values' => fake()->randomElement([
                ['status' => 'in_progress'],
                ['assigned_to' => 1],
            ]),
        ];
    }
}

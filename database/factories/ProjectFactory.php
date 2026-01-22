<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'owner_id' => User::factory(),
            'name' => $name,
            'slug' => str($name)->slug(),
            'description' => fake()->paragraph(),
            'status_workflow' => null, // Will use default
        ];
    }
}

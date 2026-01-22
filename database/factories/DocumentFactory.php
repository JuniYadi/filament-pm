<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $title = fake()->words(4, true);

        return [
            'created_by' => User::factory(),
            'project_id' => null,
            'title' => $title,
            'slug' => str($title)->slug(),
            'content' => fake()->paragraphs(5, true),
            'embedding' => null,
        ];
    }
}

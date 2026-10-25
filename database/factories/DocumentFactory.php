<?php

namespace Files13\Database\Factories;

use Files13\Models\Document;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    use HasStates;

    protected $model = Document::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
        ];
    }

    public function template(): static
    {
        return $this->state(fn (): array => ['template' => true]);
    }
}

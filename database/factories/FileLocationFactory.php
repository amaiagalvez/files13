<?php

namespace Files13\Database\Factories;

use Files13\Models\FileLocation;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileLocation>
 */
class FileLocationFactory extends Factory
{
    use HasStates;

    protected $model = FileLocation::class;

    /**
     * Every language is filled with a value no other record of this factory has used, so a test can
     * count on the four unique active indexes not getting in each other's way.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_eu' => fake()->unique()->words(2, true),
            'name_es' => fake()->unique()->words(2, true),
            'name_fr' => fake()->unique()->words(2, true),
            'name_en' => fake()->unique()->words(2, true),
        ];
    }
}

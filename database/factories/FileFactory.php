<?php

namespace Files13\Database\Factories;

use Files13\Models\File;
use Files13\Models\FileType;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<File>
 */
class FileFactory extends Factory
{
    use HasStates;

    protected $model = File::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'number' => fake()->optional()->numerify('####/##'),
            'type_id' => FileType::factory(),
        ];
    }
}

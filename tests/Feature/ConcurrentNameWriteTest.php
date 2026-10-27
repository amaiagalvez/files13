<?php

namespace Files13\Tests\Feature;

use PDOException;
use Files13\Models\File;
use Files13\Models\Phase;
use Files13\Tests\TestCase;
use Files13\Models\FileType;
use Files13\Models\FileEnding;
use Files13\Models\FileStatus;
use Files13\Models\FileSubject;
use Files13\Models\FileLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

/**
 * A write that passed validation and still hits the unique active index lost a race against a
 * concurrent one: it must answer with the name error (or the restore notice) and change nothing.
 *
 * @phpstan-type FilesModel FileSubject|FileType|FileStatus|FileEnding|FileLocation|Phase|File
 */
class ConcurrentNameWriteTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{0: class-string<FilesModel>, 1: string, 2: string, 3: string, 4: string}>
     */
    public static function writes(): array
    {
        $resources = [
            'subject' => [FileSubject::class, 'file-subjects', 'file_subjects', 'name_eu'],
            'type' => [FileType::class, 'file-types', 'file_types', 'name_eu'],
            'status' => [FileStatus::class, 'file-statuses', 'file_statuses', 'name_eu'],
            'ending' => [FileEnding::class, 'file-endings', 'file_endings', 'name_eu'],
            'location' => [FileLocation::class, 'file-locations', 'file_locations', 'name_eu'],
            'phase' => [Phase::class, 'phases', 'file_phases', 'name_eu'],
            'file' => [File::class, 'files', 'files', 'name'],
        ];

        $writes = [];
        foreach ($resources as $resource => $arguments) {
            foreach (['store', 'update', 'restore'] as $operation) {
                $writes["{$resource} {$operation}"] = [...$arguments, $operation];
            }
        }

        return $writes;
    }

    /**
     * @param  class-string<FilesModel>  $model
     * @param  'store'|'update'|'restore'  $operation
     */
    #[DataProvider('writes')]
    public function test_unique_constraint_race_returns_error_without_changing_record(string $model, string $prefix, string $module, string $field, string $operation): void
    {
        $this->actingAs($this->holding([$module.'.manage']));
        $record = $model::factory()->create();
        $originalName = $record->getAttribute($field);
        $payload = $this->payload($record, $field);
        $failure = static function (): never {
            $exception = new PDOException('Duplicate name');
            $exception->errorInfo = ['23000', 19, 'UNIQUE constraint failed'];

            throw new QueryException('sqlite', 'write', [], $exception);
        };

        if ($operation === 'restore') {
            $record->delete();
            $model::updating($failure);

            $this->patch(route($prefix.'.trash.restore', $record->getKey()))
                ->assertRedirect(route($prefix.'.trash.index'))
                ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));
            $this->assertSoftDeleted($record);
        } elseif ($operation === 'update') {
            $model::updating($failure);

            $this->put(route($prefix.'.update', $record), $payload)
                ->assertSessionHasErrors([$field => __('validation.unique', ['attribute' => __(ucfirst($field))])]);
            $this->assertDatabaseHas($record->getTable(), ['id' => $record->getKey(), $field => $originalName]);
        } else {
            $model::creating($failure);

            $this->post(route($prefix.'.store'), $payload)
                ->assertSessionHasErrors([$field => __('validation.unique', ['attribute' => __(ucfirst($field))])]);
        }

        $this->assertSame(1, $model::withTrashed()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Model $record, string $field): array
    {
        return match (true) {
            $record instanceof File => [$field => 'Concurrent name', 'type_id' => $record->type_id],
            $record instanceof Phase => [$field => 'Concurrent name', 'order' => 99],
            default => [$field => 'Concurrent name'],
        };
    }
}

<?php

namespace Files13\Tests\Feature\Files;

use Files13\Models\File;
use Files13\Tests\TestCase;
use Files13\Models\FileType;
use Files13\Models\FileEnding;
use Files13\Models\FileStatus;
use Files13\Models\FileSubject;
use Files13\Models\FileLocation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CatalogInUseTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_catalogue_entry_a_file_uses_goes_to_the_trash_but_is_not_deleted_for_good(): void
    {
        $this->actingAs($this->holding(['file_types.manage', 'file_statuses.manage', 'file_subjects.manage', 'file_endings.manage', 'file_locations.manage']));
        $file = File::factory()->create([
            'status_id' => FileStatus::factory()->create()->id,
            'subject_id' => FileSubject::factory()->create()->id,
            'ending_id' => FileEnding::factory()->create()->id,
            'location_id' => FileLocation::factory()->create()->id,
        ]);

        $catalogues = [
            'file-types' => FileType::query()->findOrFail($file->type_id),
            'file-statuses' => FileStatus::query()->findOrFail($file->status_id),
            'file-subjects' => FileSubject::query()->findOrFail($file->subject_id),
            'file-endings' => FileEnding::query()->findOrFail($file->ending_id),
            'file-locations' => FileLocation::query()->findOrFail($file->location_id),
        ];

        foreach ($catalogues as $prefix => $record) {
            $this->delete(route($prefix.'.destroy', $record))->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
            $this->delete(route($prefix.'.trash.destroy', $record->id))
                ->assertSessionHas('error', __('basics13::messages.cannot_force_delete_related'));
            $this->assertDatabaseHas($record->getTable(), ['id' => $record->id]);
        }
    }
}

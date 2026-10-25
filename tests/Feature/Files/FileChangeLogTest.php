<?php

namespace Files13\Tests\Feature\Files;

use Files13\Models\File;
use Files13\Tests\TestCase;
use Files13\Models\FileStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileChangeLogTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_status_change_logs_the_previous_status(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        [$open, $closed] = FileStatus::factory()->count(2)->create();
        $file = File::factory()->create(['status_id' => $open->id]);

        $this->put(route('files.update', $file), ['name' => $file->name, 'type_id' => $file->type_id, 'status_id' => $closed->id])
            ->assertSessionHasNoErrors();

        $change = $file->changes()->sole();
        $this->assertEquals(['status_id' => $open->id], $change->previous);
        $this->get(route('files.show', $file))->assertOk()->assertSee('status_id');
    }

    public function test_saving_without_changes_and_archiving_log_nothing(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $file = File::factory()->create(['number' => '7']);

        $this->put(route('files.update', $file), ['name' => $file->name, 'type_id' => $file->type_id, 'number' => '7'])
            ->assertSessionHasNoErrors();
        $file->touch();
        $this->patch(route('files.archive', $file));

        $this->assertSame(0, $file->changes()->count());
    }
}

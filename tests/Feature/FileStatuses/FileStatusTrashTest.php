<?php

namespace Files13\Tests\Feature\FileStatuses;

use App\Models\User;
use Files13\Tests\TestCase;
use Files13\Models\FileStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileStatusTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_trashed_statuses_are_listed_in_the_trash_only(): void
    {
        $this->actingAs($this->manager());
        FileStatus::factory()->create(['name_eu' => 'Bizirik']);
        FileStatus::factory()->trashed()->create(['name_eu' => 'Ezabatua']);

        $this->get(route('file-statuses.index'))->assertOk()->assertSee('Bizirik')->assertDontSee('Ezabatua');
        $this->get(route('file-statuses.trash.index'))->assertOk()->assertSee('Ezabatua')->assertDontSee('Bizirik');
    }

    public function test_a_trashed_status_is_restored(): void
    {
        $this->actingAs($this->manager());
        $status = FileStatus::factory()->trashed()->create();

        $this->patch(route('file-statuses.trash.restore', $status->id))
            ->assertRedirect(route('file-statuses.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));
        $this->assertNotSoftDeleted($status);
    }

    public function test_a_status_is_not_restored_while_a_live_one_uses_any_of_its_names(): void
    {
        $this->actingAs($this->manager());
        $status = FileStatus::factory()->trashed()->create(['name_eu' => 'Bat', 'name_fr' => 'Un']);
        FileStatus::factory()->create(['name_eu' => 'Beste bat', 'name_fr' => 'Un']);

        $this->patch(route('file-statuses.trash.restore', $status->id))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));
        $this->assertSoftDeleted($status);
    }

    public function test_a_trashed_status_is_deleted_permanently(): void
    {
        $this->actingAs($this->manager());
        $status = FileStatus::factory()->trashed()->create();

        $this->delete(route('file-statuses.trash.destroy', $status->id))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));
        $this->assertDatabaseMissing('FIL_statuses', ['id' => $status->id]);
    }

    public function test_the_trash_actions_need_their_own_permissions(): void
    {
        $status = FileStatus::factory()->trashed()->create();
        $this->actingAs($this->holding(['file_statuses.view', 'file_statuses.delete']));

        $this->get(route('file-statuses.trash.index'))->assertOk();
        $this->patch(route('file-statuses.trash.restore', $status->id))->assertForbidden();
        $this->delete(route('file-statuses.trash.destroy', $status->id))->assertForbidden();
        $this->assertSoftDeleted($status);
    }

    private function manager(): User
    {
        return $this->holding(['file_statuses.manage']);
    }
}

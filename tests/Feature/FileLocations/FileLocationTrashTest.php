<?php

namespace Files13\Tests\Feature\FileLocations;

use App\Models\User;
use Files13\Tests\TestCase;
use Files13\Models\FileLocation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileLocationTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_trashed_locations_are_listed_in_the_trash_only(): void
    {
        $this->actingAs($this->manager());
        FileLocation::factory()->create(['name_eu' => 'Bizirik']);
        FileLocation::factory()->trashed()->create(['name_eu' => 'Ezabatua']);

        $this->get(route('file-locations.index'))->assertOk()->assertSee('Bizirik')->assertDontSee('Ezabatua');
        $this->get(route('file-locations.trash.index'))->assertOk()->assertSee('Ezabatua')->assertDontSee('Bizirik');
    }

    public function test_a_trashed_location_is_restored(): void
    {
        $this->actingAs($this->manager());
        $location = FileLocation::factory()->trashed()->create();

        $this->patch(route('file-locations.trash.restore', $location->id))
            ->assertRedirect(route('file-locations.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));
        $this->assertNotSoftDeleted($location);
    }

    public function test_a_location_is_not_restored_while_a_live_one_uses_any_of_its_names(): void
    {
        $this->actingAs($this->manager());
        $location = FileLocation::factory()->trashed()->create(['name_eu' => 'Bat', 'name_fr' => 'Un']);
        FileLocation::factory()->create(['name_eu' => 'Beste bat', 'name_fr' => 'Un']);

        $this->patch(route('file-locations.trash.restore', $location->id))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));
        $this->assertSoftDeleted($location);
    }

    public function test_a_trashed_location_is_deleted_permanently(): void
    {
        $this->actingAs($this->manager());
        $location = FileLocation::factory()->trashed()->create();

        $this->delete(route('file-locations.trash.destroy', $location->id))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));
        $this->assertDatabaseMissing('FIL_locations', ['id' => $location->id]);
    }

    public function test_the_trash_actions_need_their_own_permissions(): void
    {
        $location = FileLocation::factory()->trashed()->create();
        $this->actingAs($this->holding(['file_locations.view', 'file_locations.delete']));

        $this->get(route('file-locations.trash.index'))->assertOk();
        $this->patch(route('file-locations.trash.restore', $location->id))->assertForbidden();
        $this->delete(route('file-locations.trash.destroy', $location->id))->assertForbidden();
        $this->assertSoftDeleted($location);
    }

    private function manager(): User
    {
        return $this->holding(['file_locations.manage']);
    }
}

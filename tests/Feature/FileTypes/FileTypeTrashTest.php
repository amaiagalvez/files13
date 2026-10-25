<?php

namespace Files13\Tests\Feature\FileTypes;

use App\Models\User;
use Files13\Tests\TestCase;
use Files13\Models\FileType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileTypeTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_trashed_types_are_listed_in_the_trash_only(): void
    {
        $this->actingAs($this->manager());
        FileType::factory()->create(['name_eu' => 'Bizirik']);
        FileType::factory()->trashed()->create(['name_eu' => 'Ezabatua']);

        $this->get(route('file-types.index'))->assertOk()->assertSee('Bizirik')->assertDontSee('Ezabatua');
        $this->get(route('file-types.trash.index'))->assertOk()->assertSee('Ezabatua')->assertDontSee('Bizirik');
    }

    public function test_a_trashed_type_is_restored(): void
    {
        $this->actingAs($this->manager());
        $type = FileType::factory()->trashed()->create();

        $this->patch(route('file-types.trash.restore', $type->id))
            ->assertRedirect(route('file-types.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));
        $this->assertNotSoftDeleted($type);
    }

    public function test_a_type_is_not_restored_while_a_live_one_uses_any_of_its_names(): void
    {
        $this->actingAs($this->manager());
        $type = FileType::factory()->trashed()->create(['name_eu' => 'Bat', 'name_fr' => 'Un']);
        FileType::factory()->create(['name_eu' => 'Beste bat', 'name_fr' => 'Un']);

        $this->patch(route('file-types.trash.restore', $type->id))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));
        $this->assertSoftDeleted($type);
    }

    public function test_a_trashed_type_is_deleted_permanently(): void
    {
        $this->actingAs($this->manager());
        $type = FileType::factory()->trashed()->create();

        $this->delete(route('file-types.trash.destroy', $type->id))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));
        $this->assertDatabaseMissing('FIL_types', ['id' => $type->id]);
    }

    public function test_the_trash_actions_need_their_own_permissions(): void
    {
        $type = FileType::factory()->trashed()->create();
        $this->actingAs($this->holding(['file_types.view', 'file_types.delete']));

        $this->get(route('file-types.trash.index'))->assertOk();
        $this->patch(route('file-types.trash.restore', $type->id))->assertForbidden();
        $this->delete(route('file-types.trash.destroy', $type->id))->assertForbidden();
        $this->assertSoftDeleted($type);
    }

    private function manager(): User
    {
        return $this->holding(['file_types.manage']);
    }
}

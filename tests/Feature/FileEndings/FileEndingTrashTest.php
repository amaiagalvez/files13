<?php

namespace Files13\Tests\Feature\FileEndings;

use App\Models\User;
use Files13\Tests\TestCase;
use Files13\Models\FileEnding;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileEndingTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_trashed_endings_are_listed_in_the_trash_only(): void
    {
        $this->actingAs($this->manager());
        FileEnding::factory()->create(['name_eu' => 'Bizirik']);
        FileEnding::factory()->trashed()->create(['name_eu' => 'Ezabatua']);

        $this->get(route('file-endings.index'))->assertOk()->assertSee('Bizirik')->assertDontSee('Ezabatua');
        $this->get(route('file-endings.trash.index'))->assertOk()->assertSee('Ezabatua')->assertDontSee('Bizirik');
    }

    public function test_a_trashed_ending_is_restored(): void
    {
        $this->actingAs($this->manager());
        $ending = FileEnding::factory()->trashed()->create();

        $this->patch(route('file-endings.trash.restore', $ending->id))
            ->assertRedirect(route('file-endings.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));
        $this->assertNotSoftDeleted($ending);
    }

    public function test_a_ending_is_not_restored_while_a_live_one_uses_any_of_its_names(): void
    {
        $this->actingAs($this->manager());
        $ending = FileEnding::factory()->trashed()->create(['name_eu' => 'Bat', 'name_fr' => 'Un']);
        FileEnding::factory()->create(['name_eu' => 'Beste bat', 'name_fr' => 'Un']);

        $this->patch(route('file-endings.trash.restore', $ending->id))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));
        $this->assertSoftDeleted($ending);
    }

    public function test_a_trashed_ending_is_deleted_permanently(): void
    {
        $this->actingAs($this->manager());
        $ending = FileEnding::factory()->trashed()->create();

        $this->delete(route('file-endings.trash.destroy', $ending->id))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));
        $this->assertDatabaseMissing('FIL_endings', ['id' => $ending->id]);
    }

    public function test_the_trash_actions_need_their_own_permissions(): void
    {
        $ending = FileEnding::factory()->trashed()->create();
        $this->actingAs($this->holding(['file_endings.view', 'file_endings.delete']));

        $this->get(route('file-endings.trash.index'))->assertOk();
        $this->patch(route('file-endings.trash.restore', $ending->id))->assertForbidden();
        $this->delete(route('file-endings.trash.destroy', $ending->id))->assertForbidden();
        $this->assertSoftDeleted($ending);
    }

    private function manager(): User
    {
        return $this->holding(['file_endings.manage']);
    }
}

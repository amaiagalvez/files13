<?php

namespace Files13\Tests\Feature\Files;

use Files13\Models\File;
use Files13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileOwnershipTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_user_limited_to_own_files_cannot_write_on_another_one(): void
    {
        $owner = $this->holding(['files.manage']);
        $this->actingAs($owner);
        $foreign = File::factory()->create(['name' => 'Besteena']);

        $this->actingAs($this->holding(['files.view', 'files.manage_own']));

        $this->put(route('files.update', $foreign), ['name' => 'Aldatua', 'type_id' => $foreign->type_id])->assertForbidden();
        $this->delete(route('files.destroy', $foreign))->assertForbidden();
        $this->patch(route('files.archive', $foreign))->assertForbidden();
        $this->put(route('files.phases.update', $foreign), ['phases' => ''])->assertForbidden();
        $this->assertSame('Besteena', $foreign->refresh()->name);
    }

    public function test_another_file_is_shown_read_only(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $foreign = File::factory()->create(['name' => 'Besteena']);

        $this->actingAs($this->holding(['files.view', 'files.manage_own']));

        $this->get(route('files.show', $foreign))->assertOk()
            ->assertSee(__('You can view this file but not change it.'))
            ->assertDontSee('file-phases-form', false);
        $this->get(route('files.index'))->assertOk()
            ->assertSee('file-view-'.$foreign->id, false)
            ->assertDontSee('file-edit-'.$foreign->id, false);
    }

    public function test_own_files_are_the_created_and_the_assigned_ones(): void
    {
        $user = $this->holding(['files.manage_own']);
        $this->actingAs($user);
        $created = File::factory()->create(['name' => 'Nik sortua']);

        $this->actingAs($this->holding(['files.manage']));
        $assigned = File::factory()->create(['name' => 'Niri esleitua']);
        $assigned->users()->attach($user);
        File::factory()->create(['name' => 'Besteena']);

        $this->actingAs($user);

        $this->get(route('files.index'))->assertOk()->assertSee('Nik sortua')->assertSee('Niri esleitua')->assertDontSee('Besteena');
        $this->put(route('files.update', $created), ['name' => 'Nik sortua', 'type_id' => $created->type_id, 'number' => '1'])->assertSessionHasNoErrors();
        $this->put(route('files.update', $assigned), ['name' => 'Niri esleitua', 'type_id' => $assigned->type_id, 'number' => '2'])->assertSessionHasNoErrors();
    }

    public function test_my_files_narrows_the_list_for_anybody(): void
    {
        $user = $this->holding(['files.manage']);
        $this->actingAs($user);
        File::factory()->create(['name' => 'Nirea']);

        $this->actingAs($this->holding(['files.manage']));
        File::factory()->create(['name' => 'Besteena']);

        $this->actingAs($user);
        $this->get(route('files.index'))->assertSee('Nirea')->assertSee('Besteena');
        $this->get(route('files.mine'))->assertSee('Nirea')->assertDontSee('Besteena');
    }

    public function test_a_user_who_may_only_view_own_files_cannot_open_another(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $foreign = File::factory()->create();

        $this->actingAs($this->holding(['files.view_own']));
        $this->get(route('files.show', $foreign))->assertForbidden();
    }
}

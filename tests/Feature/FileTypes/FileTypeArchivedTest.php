<?php

namespace Files13\Tests\Feature\FileTypes;

use Files13\Tests\TestCase;
use Files13\Models\FileType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileTypeArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_type_is_archived_and_activated_again(): void
    {
        $this->actingAs($this->holding(['file_types.manage']));
        $type = FileType::factory()->create(['name_eu' => 'Artxibagarria']);

        $this->patch(route('file-types.archive', $type))
            ->assertRedirect(route('file-types.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($type->refresh()->active);
        $this->get(route('file-types.index'))->assertOk()->assertDontSee('Artxibagarria');
        $this->get(route('file-types.archived.index'))->assertOk()->assertSee('Artxibagarria');

        $this->patch(route('file-types.archived.activate', $type))
            ->assertRedirect(route('file-types.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($type->refresh()->active);
    }

    public function test_archiving_needs_the_archive_permission(): void
    {
        $this->actingAs($this->holding(['file_types.view', 'file_types.edit']));
        $type = FileType::factory()->create();

        $this->patch(route('file-types.archive', $type))->assertForbidden();
        $this->assertTrue($type->refresh()->active);
    }
}

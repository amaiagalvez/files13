<?php

namespace Files13\Tests\Feature\FileEndings;

use Files13\Tests\TestCase;
use Files13\Models\FileEnding;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileEndingArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_ending_is_archived_and_activated_again(): void
    {
        $this->actingAs($this->holding(['file_endings.manage']));
        $ending = FileEnding::factory()->create(['name_eu' => 'Artxibagarria']);

        $this->patch(route('file-endings.archive', $ending))
            ->assertRedirect(route('file-endings.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($ending->refresh()->active);
        $this->get(route('file-endings.index'))->assertOk()->assertDontSee('Artxibagarria');
        $this->get(route('file-endings.archived.index'))->assertOk()->assertSee('Artxibagarria');

        $this->patch(route('file-endings.archived.activate', $ending))
            ->assertRedirect(route('file-endings.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($ending->refresh()->active);
    }

    public function test_archiving_needs_the_archive_permission(): void
    {
        $this->actingAs($this->holding(['file_endings.view', 'file_endings.edit']));
        $ending = FileEnding::factory()->create();

        $this->patch(route('file-endings.archive', $ending))->assertForbidden();
        $this->assertTrue($ending->refresh()->active);
    }
}

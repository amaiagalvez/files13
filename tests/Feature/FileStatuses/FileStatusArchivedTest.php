<?php

namespace Files13\Tests\Feature\FileStatuses;

use Files13\Tests\TestCase;
use Files13\Models\FileStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileStatusArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_status_is_archived_and_activated_again(): void
    {
        $this->actingAs($this->holding(['file_statuses.manage']));
        $status = FileStatus::factory()->create(['name_eu' => 'Artxibagarria']);

        $this->patch(route('file-statuses.archive', $status))
            ->assertRedirect(route('file-statuses.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($status->refresh()->active);
        $this->get(route('file-statuses.index'))->assertOk()->assertDontSee('Artxibagarria');
        $this->get(route('file-statuses.archived.index'))->assertOk()->assertSee('Artxibagarria');

        $this->patch(route('file-statuses.archived.activate', $status))
            ->assertRedirect(route('file-statuses.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($status->refresh()->active);
    }

    public function test_archiving_needs_the_archive_permission(): void
    {
        $this->actingAs($this->holding(['file_statuses.view', 'file_statuses.edit']));
        $status = FileStatus::factory()->create();

        $this->patch(route('file-statuses.archive', $status))->assertForbidden();
        $this->assertTrue($status->refresh()->active);
    }
}

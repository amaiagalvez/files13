<?php

namespace Files13\Tests\Feature\FileLocations;

use Files13\Tests\TestCase;
use Files13\Models\FileLocation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileLocationArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_location_is_archived_and_activated_again(): void
    {
        $this->actingAs($this->holding(['file_locations.manage']));
        $location = FileLocation::factory()->create(['name_eu' => 'Artxibagarria']);

        $this->patch(route('file-locations.archive', $location))
            ->assertRedirect(route('file-locations.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($location->refresh()->active);
        $this->get(route('file-locations.index'))->assertOk()->assertDontSee('Artxibagarria');
        $this->get(route('file-locations.archived.index'))->assertOk()->assertSee('Artxibagarria');

        $this->patch(route('file-locations.archived.activate', $location))
            ->assertRedirect(route('file-locations.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($location->refresh()->active);
    }

    public function test_archiving_needs_the_archive_permission(): void
    {
        $this->actingAs($this->holding(['file_locations.view', 'file_locations.edit']));
        $location = FileLocation::factory()->create();

        $this->patch(route('file-locations.archive', $location))->assertForbidden();
        $this->assertTrue($location->refresh()->active);
    }
}

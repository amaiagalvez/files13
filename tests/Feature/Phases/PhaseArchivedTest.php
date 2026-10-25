<?php

namespace Files13\Tests\Feature\Phases;

use Files13\Models\Phase;
use Files13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class PhaseArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_phase_is_archived_and_activated_again(): void
    {
        $this->actingAs($this->holding(['file_phases.manage']));
        $phase = Phase::factory()->create(['name_eu' => 'Artxibagarria']);

        $this->patch(route('phases.archive', $phase))
            ->assertRedirect(route('phases.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($phase->refresh()->active);
        $this->get(route('phases.index'))->assertOk()->assertDontSee('Artxibagarria');
        $this->get(route('phases.archived.index'))->assertOk()->assertSee('Artxibagarria');

        $this->patch(route('phases.archived.activate', $phase))
            ->assertRedirect(route('phases.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($phase->refresh()->active);
    }

    public function test_archiving_needs_the_archive_permission(): void
    {
        $this->actingAs($this->holding(['file_phases.view', 'file_phases.edit']));
        $phase = Phase::factory()->create();

        $this->patch(route('phases.archive', $phase))->assertForbidden();
        $this->assertTrue($phase->refresh()->active);
    }
}

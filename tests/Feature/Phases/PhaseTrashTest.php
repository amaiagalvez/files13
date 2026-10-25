<?php

namespace Files13\Tests\Feature\Phases;

use App\Models\User;
use Files13\Models\Phase;
use Files13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class PhaseTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_trashed_phases_are_listed_in_the_trash_only(): void
    {
        $this->actingAs($this->manager());
        Phase::factory()->create(['name_eu' => 'Bizirik']);
        Phase::factory()->trashed()->create(['name_eu' => 'Ezabatua']);

        $this->get(route('phases.index'))->assertOk()->assertSee('Bizirik')->assertDontSee('Ezabatua');
        $this->get(route('phases.trash.index'))->assertOk()->assertSee('Ezabatua')->assertDontSee('Bizirik');
    }

    public function test_a_trashed_phase_is_restored(): void
    {
        $this->actingAs($this->manager());
        $phase = Phase::factory()->trashed()->create();

        $this->patch(route('phases.trash.restore', $phase->id))
            ->assertRedirect(route('phases.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));
        $this->assertNotSoftDeleted($phase);
    }

    public function test_a_phase_is_not_restored_while_a_live_one_uses_any_of_its_names(): void
    {
        $this->actingAs($this->manager());
        $phase = Phase::factory()->trashed()->create(['name_eu' => 'Bat', 'name_fr' => 'Un']);
        Phase::factory()->create(['name_eu' => 'Beste bat', 'name_fr' => 'Un']);

        $this->patch(route('phases.trash.restore', $phase->id))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));
        $this->assertSoftDeleted($phase);
    }

    public function test_a_trashed_phase_is_deleted_permanently(): void
    {
        $this->actingAs($this->manager());
        $phase = Phase::factory()->trashed()->create();

        $this->delete(route('phases.trash.destroy', $phase->id))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));
        $this->assertDatabaseMissing('FIL_phases', ['id' => $phase->id]);
    }

    public function test_the_trash_actions_need_their_own_permissions(): void
    {
        $phase = Phase::factory()->trashed()->create();
        $this->actingAs($this->holding(['file_phases.view', 'file_phases.delete']));

        $this->get(route('phases.trash.index'))->assertOk();
        $this->patch(route('phases.trash.restore', $phase->id))->assertForbidden();
        $this->delete(route('phases.trash.destroy', $phase->id))->assertForbidden();
        $this->assertSoftDeleted($phase);
    }

    private function manager(): User
    {
        return $this->holding(['file_phases.manage']);
    }
}

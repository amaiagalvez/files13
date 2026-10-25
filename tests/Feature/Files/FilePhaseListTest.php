<?php

namespace Files13\Tests\Feature\Files;

use Files13\Models\File;
use Files13\Models\Phase;
use Files13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FilePhaseListTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_list_shows_the_phases_of_visible_files_and_narrows_to_mine(): void
    {
        $user = $this->holding(['files.manage']);
        $this->actingAs($user);
        $own = File::factory()->create(['name' => 'Nire espedientea']);
        $own->filePhases()->create(['phase_id' => Phase::factory()->create(['name_eu' => 'Nire fasea'])->id]);

        $this->actingAs($this->holding(['files.manage']));
        $other = File::factory()->create(['name' => 'Besteen espedientea']);
        $other->filePhases()->create(['phase_id' => Phase::factory()->create(['name_eu' => 'Besteen fasea'])->id]);

        $this->actingAs($user);
        $this->get(route('file-phases.index'))->assertOk()->assertSee('Nire fasea')->assertSee('Besteen fasea');
        $this->get(route('file-phases.mine'))->assertOk()->assertSee('Nire fasea')->assertDontSee('Besteen fasea');
        $this->get(route('file-phases.index', ['search' => 'Nire']))->assertOk()->assertDontSee('Besteen fasea');
    }

    public function test_trashed_phases_and_files_are_left_out(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $file = File::factory()->create();
        $trashed = Phase::factory()->create(['name_eu' => 'Zakarrontziko fasea']);
        $file->filePhases()->create(['phase_id' => $trashed->id]);
        $trashed->delete();
        $gone = File::factory()->create();
        $gone->filePhases()->create(['phase_id' => Phase::factory()->create(['name_eu' => 'Ezabatutako espedientearen fasea'])->id]);
        $gone->delete();

        $this->get(route('file-phases.index'))->assertOk()
            ->assertDontSee('Zakarrontziko fasea')
            ->assertDontSee('Ezabatutako espedientearen fasea');
        $this->get(route('files.index'))->assertOk();
    }

    public function test_a_user_limited_to_own_files_only_sees_their_phases(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        File::factory()->create()->filePhases()->create(['phase_id' => Phase::factory()->create(['name_eu' => 'Arrotza'])->id]);

        $this->actingAs($this->holding(['files.manage_own']));
        $this->get(route('file-phases.index'))->assertOk()->assertDontSee('Arrotza');
    }
}

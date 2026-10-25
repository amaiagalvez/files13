<?php

namespace Files13\Tests\Feature\Home;

use Files13\Models\File;
use Files13\Models\Phase;
use Files13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FileHomeTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_home_lists_only_the_files_and_alerts_of_the_user(): void
    {
        $user = $this->holding(['files.manage']);
        $this->actingAs($user);
        $own = File::factory()->create(['name' => 'Nire espedientea']);
        $own->filePhases()->create(['phase_id' => Phase::factory()->create(['name_eu' => 'Nire abisua'])->id])->forceFill(['alert_date' => '2026-12-01'])->save();

        $this->actingAs($this->holding(['files.manage']));
        File::factory()->create(['name' => 'Besteen espedientea']);

        $this->actingAs($user);
        $this->get(route('files-home'))->assertOk()
            ->assertSee('Nire espedientea')
            ->assertSee('Nire abisua')
            ->assertDontSee('Besteen espedientea');
    }

    public function test_the_calendar_shows_the_visible_phases_of_the_period_only(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $file = File::factory()->create();
        $shown = Phase::factory()->create(['name_eu' => 'Egutegikoa', 'show_calendar' => true, 'color' => '#00ff00']);
        $hidden = Phase::factory()->create(['name_eu' => 'Ezkutukoa', 'show_calendar' => false]);
        $file->filePhases()->create(['phase_id' => $shown->id, 'start_date' => '2026-05-04', 'end_date' => '2026-05-06']);
        $file->filePhases()->create(['phase_id' => $hidden->id, 'start_date' => '2026-05-04']);
        $other = File::factory()->create();
        $other->filePhases()->create(['phase_id' => $shown->id, 'start_date' => '2026-08-01']);

        $response = $this->get(route('files-calendar', ['month' => '2026-05']))->assertOk();

        $response->assertSee('Egutegikoa')->assertSee('#00ff00', false)->assertDontSee('Ezkutukoa');
        $response->assertSee('data-test="files-calendar-event-'.$file->filePhases()->where('phase_id', $shown->id)->value('id').'"', false);
        $this->get(route('files-calendar', ['month' => '2026-08']))->assertOk()->assertSee('Egutegikoa');
        $this->get(route('files-calendar', ['month' => '2026-13']))->assertSessionHasErrors('month');
    }

    public function test_a_user_limited_to_own_files_sees_only_their_phases_in_the_calendar(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        File::factory()->create()->filePhases()->create(['phase_id' => Phase::factory()->create(['name_eu' => 'Arrotza', 'show_calendar' => true])->id, 'start_date' => now()->toDateString()]);

        $this->actingAs($this->holding(['files.manage_own']));
        $this->get(route('files-calendar'))->assertOk()->assertDontSee('Arrotza');
    }

    public function test_the_calendar_runs_a_fixed_number_of_queries(): void
    {
        $this->actingAs($this->holding(['files.manage']));
        $phase = Phase::factory()->create(['show_calendar' => true]);
        $this->get(route('files-calendar'));

        $count = function (): int {
            $queries = 0;
            DB::listen(static function (QueryExecuted $query) use (&$queries): void {
                $queries++;
            });
            $this->get(route('files-calendar'))->assertOk();

            return $queries;
        };

        File::factory()->create()->filePhases()->create(['phase_id' => $phase->id, 'start_date' => now()->toDateString()]);
        $one = $count();
        foreach (range(1, 5) as $index) {
            File::factory()->create()->filePhases()->create(['phase_id' => Phase::factory()->create(['show_calendar' => true])->id, 'start_date' => now()->toDateString()]);
        }

        $this->assertSame($one, $count());
    }
}

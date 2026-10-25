<?php

namespace Files13\Tests\Feature\Files;

use Files13\Models\File;
use Files13\Models\Phase;
use Files13\Tests\TestCase;
use Files13\Models\FilePhase;
use Files13\Models\FileSubject;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FilePhaseDatesTest extends TestCase
{
    use LazilyRefreshDatabase;

    private File $file;

    private FilePhase $first;

    private FilePhase $second;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->holding(['files.manage']));
        $this->file = File::factory()->create();
        $this->first = $this->filePhase($this->accumulating(1, alert: 10));
        $this->second = $this->filePhase($this->accumulating(2, alert: 5));
    }

    public function test_setting_the_start_sets_the_alert_date(): void
    {
        $this->save([['row' => $this->first, 'start_date' => '2026-03-01'], ['row' => $this->second]]);

        $this->assertSame('2026-03-11', $this->first->refresh()->alert_date?->toDateString());
    }

    public function test_the_pending_days_are_the_alert_minus_the_days_spent(): void
    {
        $this->save([['row' => $this->first, 'start_date' => '2026-03-01', 'end_date' => '2026-03-05'], ['row' => $this->second, 'start_date' => '2026-04-01']]);

        $this->assertSame(6, $this->first->refresh()->accumulate_pending_days);
    }

    public function test_an_overrun_leaves_no_pending_days(): void
    {
        $short = $this->filePhase($this->accumulating(3, alert: 3));
        $this->save([['row' => $this->first], ['row' => $this->second], ['row' => $short, 'start_date' => '2026-03-01', 'end_date' => '2026-03-08']]);

        $this->assertSame(0, $short->refresh()->accumulate_pending_days);
    }

    public function test_ending_a_phase_starts_the_next_one_with_its_alert_date(): void
    {
        $this->save([['row' => $this->first, 'start_date' => '2026-03-01', 'end_date' => '2026-03-05'], ['row' => $this->second]]);

        $next = $this->second->refresh();
        $this->assertSame('2026-03-06', $next->start_date?->toDateString());
        $this->assertSame('2026-03-11', $next->alert_date?->toDateString());
    }

    public function test_a_started_next_phase_keeps_its_start(): void
    {
        $this->save([['row' => $this->first, 'start_date' => '2026-03-01', 'end_date' => '2026-03-05'], ['row' => $this->second, 'start_date' => '2026-03-20']]);

        $this->assertSame('2026-03-20', $this->second->refresh()->start_date?->toDateString());
    }

    public function test_ending_the_last_phase_starts_nothing(): void
    {
        $this->save([['row' => $this->first], ['row' => $this->second, 'start_date' => '2026-03-01', 'end_date' => '2026-03-03']]);

        $this->assertSame(3, $this->second->refresh()->accumulate_pending_days);
        $this->assertNull($this->first->refresh()->start_date);
    }

    public function test_a_phase_that_does_not_accumulate_gets_no_alert_date(): void
    {
        $plain = $this->filePhase(Phase::factory()->create(['order' => 9]));
        $this->save([['row' => $this->first], ['row' => $this->second], ['row' => $plain, 'start_date' => '2026-03-01', 'end_date' => '2026-03-02']]);

        $plain->refresh();
        $this->assertNull($plain->alert_date);
        $this->assertNull($plain->accumulate_pending_days);
    }

    public function test_rows_of_another_file_are_refused(): void
    {
        $foreign = File::factory()->create()->filePhases()->create(['phase_id' => Phase::factory()->create()->id]);

        $this->put(route('files.phases.update', $this->file), ['phases' => [['id' => $foreign->id, 'phase_id' => $foreign->phase_id]]])
            ->assertSessionHasErrors('phases.0.id');
    }

    public function test_a_phase_used_by_a_file_is_not_deleted_for_good(): void
    {
        $phase = $this->first->phase;
        $phase->delete();
        $this->actingAs($this->holding(['file_phases.manage']));

        $this->delete(route('phases.trash.destroy', $phase->id))
            ->assertSessionHas('error', __('basics13::messages.cannot_force_delete_related'));
        $this->assertDatabaseHas('FIL_phases', ['id' => $phase->id]);
    }

    private function accumulating(int $order, int $alert): Phase
    {
        $phase = Phase::factory()->create(['order' => $order, 'accumulate_days' => true, 'accumulate_days_order' => min($order, 2)]);
        $phase->subjects()->attach(FileSubject::factory()->create(), ['alert' => 0]);
        $phase->subjects()->attach(FileSubject::factory()->create(), ['alert' => $alert]);

        return $phase;
    }

    private function filePhase(Phase $phase): FilePhase
    {
        return $this->file->filePhases()->create(['phase_id' => $phase->id]);
    }

    /**
     * @param  list<array{row: FilePhase, start_date?: string, end_date?: string}>  $rows
     */
    private function save(array $rows): void
    {
        $this->put(route('files.phases.update', $this->file), ['phases' => array_map(static fn (array $row): array => [
            'id' => $row['row']->id,
            'phase_id' => $row['row']->phase_id,
            'start_date' => $row['start_date'] ?? '',
            'end_date' => $row['end_date'] ?? '',
        ], $rows)])->assertSessionHasNoErrors();
    }
}

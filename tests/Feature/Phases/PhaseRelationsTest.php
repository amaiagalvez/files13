<?php

namespace Files13\Tests\Feature\Phases;

use Files13\Models\Phase;
use Files13\Tests\TestCase;
use Files13\Models\FileType;
use Files13\Models\FileSubject;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class PhaseRelationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_types_and_subjects_are_saved_with_the_phase_in_one_submit(): void
    {
        $this->actingAs($this->holding(['file_phases.manage']));
        [$licence, $works] = FileType::factory()->count(2)->create();
        $urbanism = FileSubject::factory()->create();

        $this->post(route('phases.store'), [
            'name_eu' => 'Hasiera',
            'order' => 1,
            'color' => '#ff0000',
            'show_calendar' => '1',
            'accumulate_days' => '1',
            'accumulate_days_order' => 1,
            'type_ids' => [$licence->id, $works->id],
            'subjects' => [['subject_id' => $urbanism->id, 'alert' => 10, 'alert_unit' => 'days'], ['subject_id' => '']],
        ])->assertSessionHasNoErrors();

        $phase = Phase::query()->sole();
        $this->assertSame([$licence->id, $works->id], $phase->types->pluck('id')->sort()->values()->all());
        $this->assertSame(10, (int) $phase->subjects->sole()->getRelation('pivot')->getAttribute('alert'));
        $this->assertTrue($phase->show_calendar);
        $this->assertTrue($phase->accumulatesDays());

        $this->put(route('phases.update', $phase), [
            'name_eu' => 'Hasiera',
            'order' => 1,
            'type_ids' => [$works->id],
            'subjects' => [],
        ])->assertSessionHasNoErrors();

        $phase->refresh();
        $this->assertSame([$works->id], $phase->types->pluck('id')->all());
        $this->assertCount(0, $phase->subjects);
        $this->assertFalse($phase->show_calendar);
    }

    public function test_an_archived_type_cannot_be_added_but_an_assigned_one_is_kept(): void
    {
        $this->actingAs($this->holding(['file_phases.manage']));
        $archived = FileType::factory()->archived()->create();
        $phase = Phase::factory()->create();

        $this->put(route('phases.update', $phase), ['name_eu' => $phase->name_eu, 'order' => 1, 'type_ids' => [$archived->id]])
            ->assertSessionHasErrors('type_ids.0');

        $phase->types()->attach($archived);

        $this->put(route('phases.update', $phase), ['name_eu' => $phase->name_eu, 'order' => 1, 'type_ids' => [$archived->id]])
            ->assertSessionHasNoErrors();
    }

    public function test_a_subject_appears_once_and_its_alert_is_validated(): void
    {
        $this->actingAs($this->holding(['file_phases.manage']));
        $subject = FileSubject::factory()->create();

        $this->post(route('phases.store'), [
            'name_eu' => 'Hasiera',
            'order' => 1,
            'color' => 'gorria',
            'subjects' => [
                ['subject_id' => $subject->id, 'alert' => 5, 'alert_unit' => 'days'],
                ['subject_id' => $subject->id, 'alert' => -1, 'alert_unit' => 'weeks'],
            ],
        ])->assertSessionHasErrors(['color', 'subjects.0.subject_id', 'subjects.1.subject_id', 'subjects.1.alert', 'subjects.1.alert_unit']);
    }

    public function test_a_type_or_subject_used_by_a_phase_is_not_deleted_for_good(): void
    {
        $this->actingAs($this->holding(['file_types.manage', 'file_subjects.manage']));
        $type = FileType::factory()->create();
        $subject = FileSubject::factory()->create();
        Phase::factory()->create()->types()->attach($type);
        Phase::factory()->create()->subjects()->attach($subject, ['alert' => 1]);
        $type->delete();
        $subject->delete();

        $this->delete(route('file-types.trash.destroy', $type->id))
            ->assertSessionHas('error', __('basics13::messages.cannot_force_delete_related'));
        $this->delete(route('file-subjects.trash.destroy', $subject->id))
            ->assertSessionHas('error', __('basics13::messages.cannot_force_delete_related'));
        $this->assertDatabaseHas('FIL_types', ['id' => $type->id]);
        $this->assertDatabaseHas('FIL_subjects', ['id' => $subject->id]);
    }

    public function test_deleting_a_phase_for_good_takes_its_rows(): void
    {
        $phase = Phase::factory()->create();
        $phase->types()->attach(FileType::factory()->create());
        $phase->subjects()->attach(FileSubject::factory()->create(), ['alert' => 1]);

        $phase->forceDelete();

        $this->assertDatabaseCount('FIL_phase_type', 0);
        $this->assertDatabaseCount('FIL_phase_subject', 0);
    }

    public function test_the_foreign_key_refuses_a_type_in_use_even_without_the_controller(): void
    {
        $type = FileType::factory()->create();
        Phase::factory()->create()->types()->attach($type);

        $this->expectException(QueryException::class);
        $type->forceDelete();
    }
}

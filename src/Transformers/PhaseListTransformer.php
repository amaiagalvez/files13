<?php

namespace Files13\Transformers;

use Files13\Models\Phase;
use Files13\Models\FileSubject;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<Phase>
 */
class PhaseListTransformer extends ListTransformer
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function columns(Model $record, array $extra): array
    {
        return [
            'id' => $record->id,
            'nameEu' => $record->name_eu,
            'nameEs' => $record->name_es,
            'nameFr' => $record->name_fr,
            'nameEn' => $record->name_en,
            'order' => $record->order,
            'color' => $record->color,
            'showCalendar' => $record->show_calendar,
            'types' => $record->types->pluck('name_eu')->join(', '),
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    /**
     * A phase in use may still go to the trash; only the permanent delete waits until nothing
     * points at it.
     */
    protected function getDeleteBlockCondition(Model $record): bool
    {
        return false;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return false;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return __('basics13::messages.cannot_force_delete_related');
    }

    /**
     * @param  Phase  $record
     * @return array<string, mixed>
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name_eu', 'name_es', 'name_fr', 'name_en', 'order', 'color', 'show_calendar', 'accumulate_days', 'accumulate_days_order', 'notes']),
            'type_ids' => $record->types->pluck('id')->map(static fn (mixed $id): string => (string) $id)->values()->all(),
            'subjects' => $record->subjects->map(static fn (FileSubject $subject): array => [
                'subject_id' => (string) $subject->id,
                'alert' => (int) $subject->getRelation('pivot')->getAttribute('alert'),
                'alert_unit' => (string) $subject->getRelation('pivot')->getAttribute('alert_unit'),
            ])->values()->all(),
        ];
    }

    /**
     * A phase has no single name: it is named by its Basque one, the only required translation.
     *
     * @param  Phase  $record
     */
    protected function recordName(Model $record): string
    {
        return $record->name_eu;
    }

    protected function resourceLabel(): string
    {
        return __('Phases');
    }

    protected function resourceKey(): string
    {
        return 'phase';
    }
}

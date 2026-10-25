<?php

namespace Files13\Transformers;

use Files13\Models\LocationDay;
use Files13\Models\FileLocation;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<FileLocation>
 */
class FileLocationListTransformer extends ListTransformer
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
            'daysCount' => $record->days->count(),
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    /**
     * A location in use may still go to the trash; only the permanent delete waits until nothing
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
     * @param  FileLocation  $record
     * @return array{id: int, name_eu: string, name_es: string|null, name_fr: string|null, name_en: string|null, notes: string|null, days: array<int, array{id: int, date: string, name: string}>}
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name_eu', 'name_es', 'name_fr', 'name_en', 'notes']),
            'days' => $record->days->map(static fn (LocationDay $day): array => [
                'id' => $day->id,
                'date' => $day->date->toDateString(),
                'name' => $day->name,
            ])->values()->all(),
        ];
    }

    /**
     * A location has no single name: it is named by its Basque one, the only required translation.
     *
     * @param  FileLocation  $record
     */
    protected function recordName(Model $record): string
    {
        return $record->name_eu;
    }

    protected function resourceLabel(): string
    {
        return __('Locations');
    }

    protected function resourceKey(): string
    {
        return 'file-location';
    }
}

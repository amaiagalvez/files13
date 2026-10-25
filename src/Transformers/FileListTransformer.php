<?php

namespace Files13\Transformers;

use Files13\Models\File;
use Files13\Models\FileApplicant;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<File>
 */
class FileListTransformer extends ListTransformer
{
    /**
     * A row the user may not change shows its data and a link to the read-only sheet, without
     * the write actions.
     *
     * @param  File  $record
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function columns(Model $record, array $extra): array
    {
        return [
            'id' => $record->id,
            'name' => $record->name,
            'number' => $record->number ?? '',
            'type' => $record->type->name_eu,
            'status' => $record->status->name_eu ?? '',
            'startDate' => $record->start_date?->toDateString() ?? '',
            'endDate' => $record->end_date?->toDateString() ?? '',
            'showUrl' => route('files.show', $record),
            'writable' => Gate::allows('update', $record),
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    /**
     * A file in use may still go to the trash; what it holds goes with it.
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
     * @param  File  $record
     * @return array<string, mixed>
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name', 'number', 'impugn', 'type_id', 'status_id', 'subject_id', 'ending_id', 'location_id', 'district_id', 'notes']),
            'start_date' => $record->start_date?->toDateString() ?? '',
            'end_date' => $record->end_date?->toDateString() ?? '',
            'user_ids' => $record->users->map(static fn (Model $user): string => (string) $user->getKey())->values()->all(),
            'applicants' => $record->applicants->map(static fn (FileApplicant $applicant): array => [
                'applicant_type' => $applicant->applicant_type,
                'applicant_id' => (string) $applicant->applicant_id,
            ])->values()->all(),
        ];
    }

    protected function resourceLabel(): string
    {
        return __('Case files');
    }

    protected function resourceKey(): string
    {
        return 'file';
    }
}

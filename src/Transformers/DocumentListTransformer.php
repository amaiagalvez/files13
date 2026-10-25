<?php

namespace Files13\Transformers;

use Files13\Models\Document;
use Files13\Models\FileSubject;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<Document>
 */
class DocumentListTransformer extends ListTransformer
{
    /**
     * @param  Document  $record
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function columns(Model $record, array $extra): array
    {
        return [
            'id' => $record->id,
            'name' => $record->name,
            'extension' => $record->extension ?? '',
            'subjects' => $record->subjects->pluck('name_eu')->join(', '),
            'editing' => $record->editing,
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    /**
     * A template open in the editor is archived instead of deleted.
     */
    protected function getDeleteBlockCondition(Model $record): bool
    {
        return (bool) $record->getAttribute('editing');
    }

    /**
     * A template copies were made from is kept: the copies point at it.
     */
    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return (int) $record->getAttribute('copies_count') > 0;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return __('basics13::messages.cannot_force_delete_related');
    }

    /**
     * @param  Document  $record
     * @return array<string, mixed>
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name', 'notes']),
            'subject_ids' => $record->subjects->map(static fn (FileSubject $subject): string => (string) $subject->id)->values()->all(),
        ];
    }

    protected function resourceLabel(): string
    {
        return __('Document templates');
    }

    protected function resourceKey(): string
    {
        return 'document';
    }
}

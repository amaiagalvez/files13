<?php

namespace Files13\Queries\Phases;

use Files13\Models\FileType;
use Files13\Models\FileSubject;

/**
 * The choices of the phase form. Types and subjects are short catalogues, so every active one is
 * offered at once instead of through a search.
 */
final class PhaseFormOptionsQuery
{
    /**
     * @return array{types: array<int, array{id: string, text: string}>, subjects: array<int, array{id: string, text: string}>}
     */
    public function options(): array
    {
        return [
            'types' => $this->catalogue(FileType::class),
            'subjects' => $this->catalogue(FileSubject::class),
        ];
    }

    /**
     * @param  class-string<FileType|FileSubject>  $model
     * @return array<int, array{id: string, text: string}>
     */
    private function catalogue(string $model): array
    {
        return $model::query()
            ->where('active', true)
            ->orderBy('name_eu')
            ->get(['id', 'name_eu'])
            ->map(static fn (FileType|FileSubject $record): array => ['id' => (string) $record->id, 'text' => $record->name_eu])
            ->values()
            ->all();
    }
}

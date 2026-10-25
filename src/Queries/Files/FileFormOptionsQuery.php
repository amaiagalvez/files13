<?php

namespace Files13\Queries\Files;

use Files13\Models\Phase;
use Illuminate\Support\Str;
use Files13\Models\FileType;
use Files13\Models\FileEnding;
use Files13\Models\FileStatus;
use Files13\Models\FileSubject;
use Files13\Models\FileLocation;
use Locations13\Models\District;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\ResolvesUserModel;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * The choices of the file form. The catalogues are short, so every active entry is offered at once;
 * the applicant types are the morph keys the host registers.
 */
final class FileFormOptionsQuery
{
    use ResolvesUserModel;

    /**
     * @return array<string, array<int, array{id: string, text: string}>>
     */
    public function options(): array
    {
        return [
            'types' => $this->catalogue(FileType::class, 'name_eu'),
            'statuses' => $this->catalogue(FileStatus::class, 'name_eu'),
            'subjects' => $this->catalogue(FileSubject::class, 'name_eu'),
            'endings' => $this->catalogue(FileEnding::class, 'name_eu'),
            'locations' => $this->catalogue(FileLocation::class, 'name_eu'),
            'districts' => $this->catalogue(District::class, 'name'),
            'phases' => $this->catalogue(Phase::class, 'name_eu'),
            'users' => $this->catalogue($this->userModelClass(), 'name'),
            'applicantTypes' => array_map(
                static fn (string $key): array => ['id' => $key, 'text' => Str::headline($key)],
                array_keys(Relation::morphMap()),
            ),
        ];
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<int, array{id: string, text: string}>
     */
    private function catalogue(string $model, string $label): array
    {
        return $model::query()
            ->where('active', true)
            ->orderBy($label)
            ->get(['id', $label])
            ->map(static fn (Model $record): array => ['id' => (string) $record->getKey(), 'text' => (string) $record->getAttribute($label)])
            ->values()
            ->all();
    }
}

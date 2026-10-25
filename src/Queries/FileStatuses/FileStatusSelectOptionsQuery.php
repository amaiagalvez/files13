<?php

namespace Files13\Queries\FileStatuses;

use Files13\Models\FileStatus;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends ListQueryBase<FileStatus>
 */
final class FileStatusSelectOptionsQuery extends ListQueryBase
{
    /**
     * Search columns with table prefix for the query builder.
     *
     * @var list<string>
     */
    private const NAME_COLUMNS = [
        'FIL_statuses.name_eu',
        'FIL_statuses.name_es',
        'FIL_statuses.name_fr',
        'FIL_statuses.name_en',
    ];

    /**
     * Options for the selects that pick a status. Euskera is the text every option shows, while
     * the term the user types is matched against all four languages, so a status can be found by
     * any of the names it has.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = FileStatus::query()
            ->where('active', true)
            ->orderBy('FIL_statuses.name_eu')
            ->orderBy('FIL_statuses.id');

        if ($search !== '') {
            $pattern = $this->searchPattern($search);

            $query->where(function (Builder $query) use ($pattern): void {
                foreach (self::NAME_COLUMNS as $column) {
                    $query->orWhereRaw($column.' LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'', [$pattern]);
                }
            });
        }

        return $query->limit(self::PER_PAGE)
            ->get(['id', 'name_eu'])
            ->map(fn (FileStatus $fileStatus): array => [
                'id' => $fileStatus->id,
                'text' => $fileStatus->name_eu,
            ]);
    }

    /**
     * @return class-string<FileStatus>
     */
    protected function getModelClass(): string
    {
        return FileStatus::class;
    }

    /**
     * Statuses have no integration, so the base hook gets a no-op that appends nothing.
     */
    protected function integration(): object
    {
        return new class
        {
            /**
             * @param  non-empty-list<literal-string>  $columns
             * @return non-empty-list<literal-string>
             */
            public function searchColumns(string $scope, array $columns): array
            {
                return $columns;
            }
        };
    }

    /**
     * Select-option queries do not expose list filters.
     *
     * @return array{}
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }
}

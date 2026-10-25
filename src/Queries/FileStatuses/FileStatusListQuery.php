<?php

namespace Files13\Queries\FileStatuses;

use Files13\Models\FileStatus;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends ListQueryBase<FileStatus>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class FileStatusListQuery extends ListQueryBase
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
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, FileStatus>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            (FileStatus::query()
                ->where('FIL_statuses.active', true)
                ->orderBy('FIL_statuses.name_eu')
                ->orderBy('FIL_statuses.id')),
            $search,
            searchColumns: self::NAME_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, FileStatus>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            (FileStatus::query()
                ->where('FIL_statuses.active', false)
                ->orderBy('FIL_statuses.name_eu')
                ->orderBy('FIL_statuses.id')),
            $search,
            searchColumns: [...self::NAME_COLUMNS, 'FIL_statuses.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, FileStatus>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            (FileStatus::onlyTrashed()
                ->latest('deleted_at')
                ->orderBy('id')),
            $search,
            searchColumns: [...self::NAME_COLUMNS, 'FIL_statuses.deleted_at'],
            filters: $filters,
            scope: 'trashed',
        );
    }

    /**
     * Get the searchable field definitions for the current scope.
     *
     * @param  string  $scope  One of 'active', 'archived', 'trashed'
     * @return array<string, array{column: string, label: string, type: string, operators?: array<int,string>, default_operator?: string, visible?: bool, order?: int, group?: string, nullable?: bool, placeholder?: string, help?: string, condition?: callable, options?: array<int,string>|callable, multiple?: bool}>
     */
    public function searchableFields(string $scope): array
    {
        // Statuses are a short catalogue: the list search is enough, no advanced filters.
        return [];
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
     * A trashed status is found by its euskera name, which is the one the create form names first and
     * the only name a status is required to have.
     *
     * @param  Builder<FileStatus>  $query
     */
    protected function firstTrashedByName(Builder $query, string $name): ?Model
    {
        return $query->where('name_eu', $name)->latest('deleted_at')->first();
    }
}

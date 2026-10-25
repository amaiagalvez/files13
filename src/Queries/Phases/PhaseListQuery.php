<?php

namespace Files13\Queries\Phases;

use Files13\Models\Phase;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends ListQueryBase<Phase>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class PhaseListQuery extends ListQueryBase
{
    /**
     * Search columns with table prefix for the query builder.
     *
     * @var list<string>
     */
    private const NAME_COLUMNS = [
        'FIL_phases.name_eu',
        'FIL_phases.name_es',
        'FIL_phases.name_fr',
        'FIL_phases.name_en',
    ];

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Phase>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            (Phase::query()
                ->with(['types', 'subjects'])
                ->where('FIL_phases.active', true)
                ->orderBy('FIL_phases.order')
                ->orderBy('FIL_phases.name_eu')
                ->orderBy('FIL_phases.id')),
            $search,
            searchColumns: self::NAME_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Phase>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            (Phase::query()
                ->with(['types', 'subjects'])
                ->where('FIL_phases.active', false)
                ->orderBy('FIL_phases.order')
                ->orderBy('FIL_phases.name_eu')
                ->orderBy('FIL_phases.id')),
            $search,
            searchColumns: [...self::NAME_COLUMNS, 'FIL_phases.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Phase>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            (Phase::onlyTrashed()
                ->with(['types', 'subjects'])
                ->latest('deleted_at')
                ->orderBy('id')),
            $search,
            searchColumns: [...self::NAME_COLUMNS, 'FIL_phases.deleted_at'],
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
        // Phases are a short catalogue: the list search is enough, no advanced filters.
        return [];
    }

    /**
     * @return class-string<Phase>
     */
    protected function getModelClass(): string
    {
        return Phase::class;
    }

    /**
     * Phases have no integration, so the base hook gets a no-op that appends nothing.
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
     * A trashed phase is found by its euskera name, which is the one the create form names first and
     * the only name a phase is required to have.
     *
     * @param  Builder<Phase>  $query
     */
    protected function firstTrashedByName(Builder $query, string $name): ?Model
    {
        return $query->where('name_eu', $name)->latest('deleted_at')->first();
    }
}

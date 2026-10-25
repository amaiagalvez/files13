<?php

namespace Files13\Queries\Phases;

use Files13\Models\Phase;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends ListQueryBase<Phase>
 */
final class PhaseSelectOptionsQuery extends ListQueryBase
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
     * Options for the selects that pick a phase. Euskera is the text every option shows, while
     * the term the user types is matched against all four languages, so a phase can be found by
     * any of the names it has.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = Phase::query()
            ->where('active', true)
            ->orderBy('FIL_phases.name_eu')
            ->orderBy('FIL_phases.id');

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
            ->map(fn (Phase $phase): array => [
                'id' => $phase->id,
                'text' => $phase->name_eu,
            ]);
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
     * Select-option queries do not expose list filters.
     *
     * @return array{}
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }
}

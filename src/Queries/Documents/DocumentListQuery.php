<?php

namespace Files13\Queries\Documents;

use Files13\Models\Document;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * The document templates. The documents of the files are listed on each file sheet instead.
 *
 * @extends ListQueryBase<Document>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class DocumentListQuery extends ListQueryBase
{
    /**
     * @var non-empty-list<literal-string>
     */
    private const SEARCH_COLUMNS = ['FIL_documents.name'];

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Document>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Document::query()->where('FIL_documents.active', true))->orderBy('FIL_documents.name')->orderBy('FIL_documents.id'),
            $search,
            searchColumns: self::SEARCH_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Document>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Document::query()->where('FIL_documents.active', false))->orderBy('FIL_documents.name')->orderBy('FIL_documents.id'),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'FIL_documents.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Document>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->listed(Document::onlyTrashed())->latest('deleted_at')->orderBy('id'),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'FIL_documents.deleted_at'],
            filters: $filters,
            scope: 'trashed',
        );
    }

    /**
     * @return array{active: int, archived: int, trashed: int}
     */
    public function templateCounts(): array
    {
        return [
            'active' => Document::query()->where('template', true)->where('active', true)->count(),
            'archived' => Document::query()->where('template', true)->where('active', false)->count(),
            'trashed' => Document::onlyTrashed()->where('template', true)->count(),
        ];
    }

    /**
     * The active templates a file may copy, the ones open in the editor flagged.
     *
     * @return list<array{id: string, text: string, editing: bool}>
     */
    public function copyable(): array
    {
        return array_values(Document::query()
            ->where('template', true)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'editing'])
            ->map(static fn (Document $document): array => ['id' => (string) $document->id, 'text' => $document->name, 'editing' => $document->editing])
            ->all());
    }

    /**
     * Templates are searched by name; the list search is enough, no advanced filters.
     *
     * @return array<string, array{column: string, label: string, type: string}>
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }

    /**
     * Templates share names freely, so a trashed one never blocks a new one.
     *
     * @param  Builder<Document>  $query
     */
    protected function firstTrashedByName(Builder $query, string $name): ?Document
    {
        return null;
    }

    /**
     * @return class-string<Document>
     */
    protected function getModelClass(): string
    {
        return Document::class;
    }

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
     * @param  Builder<Document>  $query
     * @return Builder<Document>
     */
    private function listed(Builder $query): Builder
    {
        return $query->where('FIL_documents.template', true)
            ->with('subjects:id,name_eu')
            ->withCount(['copies' => static fn (Builder $copies): Builder => $copies->withoutGlobalScope(SoftDeletingScope::class)]);
    }
}

<?php

namespace Files13\Queries\Files;

use Files13\Models\File;
use Basics13\Queries\ListQueryBase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * @extends ListQueryBase<File>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class FileListQuery extends ListQueryBase
{
    /**
     * @var non-empty-list<literal-string>
     */
    private const SEARCH_COLUMNS = ['FIL_files.name', 'FIL_files.number'];

    /**
     * What a file row shows, loaded once for the whole page.
     *
     * @var list<string>
     */
    private const ROW_RELATIONS = ['type:id,name_eu,active', 'status:id,name_eu,active', 'users:id', 'applicants'];

    /**
     * The active files the user may see; with `$mine`, only the ones they created or are assigned to.
     *
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, File>
     */
    public function active(string $search, ?User $viewer, bool $mine = false, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->visibleTo(File::query()->with(self::ROW_RELATIONS)->where('FIL_files.active', true), $viewer, $mine)
                ->orderBy('FIL_files.name')
                ->orderBy('FIL_files.id'),
            $search,
            searchColumns: self::SEARCH_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, File>
     */
    public function archived(string $search, ?User $viewer, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->visibleTo(File::query()->with(self::ROW_RELATIONS)->where('FIL_files.active', false), $viewer)
                ->orderBy('FIL_files.name')
                ->orderBy('FIL_files.id'),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'FIL_files.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, File>
     */
    public function trashed(string $search, ?User $viewer, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->visibleTo(File::onlyTrashed()->with(self::ROW_RELATIONS), $viewer)->latest('deleted_at')->orderBy('id'),
            $search,
            searchColumns: [...self::SEARCH_COLUMNS, 'FIL_files.deleted_at'],
            filters: $filters,
            scope: 'trashed',
        );
    }

    /**
     * The tab counts of the files the user may see.
     *
     * @return array{active: int, archived: int, trashed: int}
     */
    public function visibleCounts(?User $viewer): array
    {
        return [
            'active' => $this->visibleTo(File::query()->where('FIL_files.active', true), $viewer)->count(),
            'archived' => $this->visibleTo(File::query()->where('FIL_files.active', false), $viewer)->count(),
            'trashed' => $this->visibleTo(File::onlyTrashed(), $viewer)->count(),
        ];
    }

    /**
     * Everything the file sheet shows, in a fixed number of queries.
     */
    public function sheet(File $file): File
    {
        return $file->load([
            'type', 'status', 'subject', 'ending', 'location', 'district',
            'users', 'applicants.applicant', 'filePhases.phase', 'changes', 'documents.tracks',
        ]);
    }

    /**
     * Files are searched by name and number; the list search is enough, no advanced filters.
     *
     * @return array<string, array{column: string, label: string, type: string}>
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }

    /**
     * Scope a query to the files the user may see: every file with `files.view`, otherwise the ones
     * they created or are assigned to. `$mine` asks for the latter for anybody.
     *
     * @param  Builder<File>  $query
     * @return Builder<File>
     */
    public static function visibleTo(Builder $query, ?User $viewer, bool $mine = false): Builder
    {
        if (! $mine && $viewer !== null && Gate::forUser($viewer)->allows('viewAll', File::class)) {
            return $query;
        }

        $userId = $viewer === null ? 0 : (int) $viewer->getAuthIdentifier();

        return $query->where(static function (Builder $own) use ($userId): void {
            $own->where('FIL_files.created_by', $userId)
                ->orWhereHas('users', static fn (Builder $users): Builder => $users->whereKey($userId));
        });
    }

    /**
     * @return class-string<File>
     */
    protected function getModelClass(): string
    {
        return File::class;
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
}

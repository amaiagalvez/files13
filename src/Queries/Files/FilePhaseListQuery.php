<?php

namespace Files13\Queries\Files;

use Files13\Models\File;
use Files13\Models\FilePhase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * The phases of the active files the user may see, the nearest alert first. Phases and files in
 * the trash are left out.
 */
final class FilePhaseListQuery
{
    public const PER_PAGE = 25;

    /**
     * @return LengthAwarePaginator<int, FilePhase>
     */
    public function list(string $search, ?User $viewer, bool $mine): LengthAwarePaginator
    {
        return FilePhase::query()
            ->with(['file:id,name', 'phase:id,name_eu,color', 'responsible:id,name'])
            ->whereHas('file', static function (Builder $files) use ($viewer, $mine, $search): void {
                $files->where('FIL_files.active', true)->whereNull('FIL_files.deleted_at');
                FileListQuery::visibleTo($files, $viewer, $mine);

                if ($search !== '') {
                    $files->where('FIL_files.name', 'like', '%'.addcslashes($search, '%_\\').'%');
                }
            })
            ->whereHas('phase', static fn (Builder $phases): Builder => $phases->whereNull('FIL_phases.deleted_at'))
            ->orderByRaw('CASE WHEN alert_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('alert_date')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Whether the user sees the phases of every file.
     */
    public function seesAll(?User $viewer): bool
    {
        return $viewer !== null && $viewer->can('viewAll', File::class);
    }
}

<?php

namespace Files13\Queries\Home;

use Files13\Models\File;
use Files13\Models\FilePhase;
use Files13\Queries\Files\FileListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * The home of the files: the user's own active files and the alerts of their phases still open.
 */
final class FileHomeQuery
{
    public const LIMIT = 10;

    /**
     * The active files the user created or is assigned to, the latest changed first.
     *
     * @return Collection<int, File>
     */
    public function myFiles(?User $viewer): Collection
    {
        return FileListQuery::visibleTo(File::query()->where('FIL_files.active', true), $viewer, mine: true)
            ->with('type:id,name_eu')
            ->latest('updated_at')
            ->limit(self::LIMIT)
            ->get();
    }

    /**
     * The open phases of those files that have an alert date, the nearest first.
     *
     * @return Collection<int, FilePhase>
     */
    public function upcomingAlerts(?User $viewer): Collection
    {
        return FilePhase::query()
            ->with(['file:id,name', 'phase:id,name_eu,color'])
            ->whereNotNull('alert_date')
            ->whereNull('end_date')
            ->whereHas('file', static function (Builder $files) use ($viewer): void {
                $files->where('FIL_files.active', true)->whereNull('FIL_files.deleted_at');
                FileListQuery::visibleTo($files, $viewer, mine: true);
            })
            ->whereHas('phase', static fn (Builder $phases): Builder => $phases->whereNull('FIL_phases.deleted_at'))
            ->orderBy('alert_date')
            ->limit(self::LIMIT)
            ->get();
    }
}

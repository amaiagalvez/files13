<?php

namespace Files13\Queries\Calendar;

use Carbon\CarbonImmutable;
use Files13\Models\FilePhase;
use Illuminate\Support\Collection;
use Files13\Queries\Files\FileListQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * The file phases shown in the calendar: the ones of a phase that shows in the calendar, of the
 * active files the user may see, that touch the requested period. A phase without an end lasts
 * one day, its start.
 */
final class PhaseCalendarQuery
{
    /**
     * @return Collection<int, array{id: int, file_id: int, file: string, phase: string, color: string|null, start: CarbonImmutable, end: CarbonImmutable}>
     */
    public function events(CarbonImmutable $from, CarbonImmutable $to, ?User $viewer): Collection
    {
        return FilePhase::query()
            ->with(['file:id,name', 'phase:id,name_eu,color'])
            ->whereNotNull('start_date')
            ->where('start_date', '<=', $to->toDateString())
            ->where(static fn (Builder $query): Builder => $query
                ->where('end_date', '>=', $from->toDateString())
                ->orWhere(static fn (Builder $open): Builder => $open->whereNull('end_date')->where('start_date', '>=', $from->toDateString())))
            ->whereHas('phase', static fn (Builder $phases): Builder => $phases->where('show_calendar', true)->whereNull('FIL_phases.deleted_at'))
            ->whereHas('file', static function (Builder $files) use ($viewer): void {
                $files->where('FIL_files.active', true)->whereNull('FIL_files.deleted_at');
                FileListQuery::visibleTo($files, $viewer);
            })
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->map(static function (FilePhase $row): array {
                $start = $row->start_date ?? CarbonImmutable::today();

                return [
                    'id' => $row->id,
                    'file_id' => $row->file_id,
                    'file' => $row->file->name,
                    'phase' => $row->phase->name_eu,
                    'color' => $row->phase->color,
                    'start' => $start,
                    'end' => $row->end_date ?? $start,
                ];
            })
            ->values();
    }
}

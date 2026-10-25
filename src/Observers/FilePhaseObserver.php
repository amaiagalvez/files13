<?php

namespace Files13\Observers;

use Files13\Models\Phase;
use Files13\Models\FilePhase;

/**
 * Keeps the dates of the phases of a file consistent, as the legacy package did:
 *
 * - setting or changing the start of an accumulating phase sets its alert date;
 * - setting the end of an accumulating phase sets its pending days;
 * - setting the end of any phase starts the next phase of the file the day after, when that phase
 *   has no start yet. That start is saved like a hand-made one, so its alert date follows.
 *
 * It runs inside the transaction of the request that saves the phases.
 */
class FilePhaseObserver
{
    public function updating(FilePhase $filePhase): void
    {
        if (! $filePhase->isDirty('start_date')) {
            return;
        }

        $phase = $filePhase->phase;

        if ($phase->accumulatesDays()) {
            $filePhase->alert_date = FilePhase::alertDateFor($filePhase->start_date, $this->alertOf($phase));
        }
    }

    public function updated(FilePhase $filePhase): void
    {
        if (! $filePhase->wasChanged('end_date')) {
            return;
        }

        $phase = $filePhase->phase;

        if ($phase->accumulatesDays()) {
            $filePhase->accumulate_pending_days = FilePhase::pendingDaysFor(
                $filePhase->start_date,
                $filePhase->end_date,
                $this->alertOf($phase),
            );
            $filePhase->saveQuietly();
        }

        if ($filePhase->end_date === null) {
            return;
        }

        $next = $this->nextPhaseOf($filePhase);

        if ($next !== null && $next->start_date === null) {
            $next->start_date = $filePhase->end_date->addDay();
            $next->save();
        }
    }

    private function alertOf(Phase $phase): int
    {
        return FilePhase::alertOf($phase->subjects()
            ->orderBy('FIL_phase_subject.id')
            ->pluck('FIL_phase_subject.alert')
            ->map(static fn (mixed $alert): int => (int) $alert));
    }

    /**
     * The phase of the same file with the next higher order.
     */
    private function nextPhaseOf(FilePhase $filePhase): ?FilePhase
    {
        return FilePhase::query()
            ->where('FIL_file_phase.file_id', $filePhase->file_id)
            ->join('FIL_phases', 'FIL_phases.id', '=', 'FIL_file_phase.phase_id')
            ->where('FIL_phases.order', '>', $filePhase->phase->order)
            ->orderBy('FIL_phases.order')
            ->orderBy('FIL_file_phase.id')
            ->select('FIL_file_phase.*')
            ->first();
    }
}

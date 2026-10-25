<?php

namespace Files13\Http\Controllers;

use Files13\Models\File;
use Files13\Models\FilePhase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\Controller;
use Files13\Http\Requests\FilePhasesRequest;

/**
 * The phases of a file, saved from the file sheet. They are rows of the file, not a resource: no
 * list, trash or permissions of their own.
 */
class FilePhaseController extends Controller
{
    public function update(FilePhasesRequest $request, File $file): RedirectResponse
    {
        /** @var list<array{id?: int|null, phase_id: int, user_id?: int|null, start_date?: string|null, end_date?: string|null, notes?: string|null}> $rows */
        $rows = $request->validated()['phases'];

        // The rows and every date the observer derives from them are written in one transaction.
        DB::transaction(static fn () => self::syncPhases($file, $rows));

        return redirect()->route('files.show', $file)->with('status', __('basics13::messages.updated'));
    }

    /**
     * The form sends the whole list of phases of the file. The rows are saved from the last phase to
     * the first: ending a phase may start the next one, and saving that next row afterwards with the
     * empty start the form still holds would undo it.
     *
     * @param  list<array{id?: int|null, phase_id: int, user_id?: int|null, start_date?: string|null, end_date?: string|null, notes?: string|null}>  $rows
     */
    private static function syncPhases(File $file, array $rows): void
    {
        $current = $file->filePhases()->get()->keyBy('id');
        $kept = array_values(array_filter(array_map(static fn (array $row): ?int => isset($row['id']) ? (int) $row['id'] : null, $rows)));
        FilePhase::query()->where('file_id', $file->id)->whereNotIn('id', $kept)->delete();

        foreach (array_reverse($rows) as $row) {
            $attributes = [
                'phase_id' => (int) $row['phase_id'],
                'user_id' => $row['user_id'] ?? null,
                'start_date' => $row['start_date'] ?? null,
                'end_date' => $row['end_date'] ?? null,
                'notes' => $row['notes'] ?? null,
            ];

            $filePhase = isset($row['id']) ? $current->get((int) $row['id'])?->refresh() : null;

            if ($filePhase === null) {
                $file->filePhases()->create($attributes);

                continue;
            }

            $filePhase->fill($attributes);

            if ($filePhase->isDirty()) {
                $filePhase->save();
            }
        }
    }
}

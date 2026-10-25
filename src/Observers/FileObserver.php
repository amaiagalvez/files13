<?php

namespace Files13\Observers;

use Files13\Models\File;
use Illuminate\Support\Facades\Auth;

/**
 * Keeps the change log of a file: after each update that changed a data column, one row holds the
 * values those columns had before. Who touched the row, when, and archiving are not data changes.
 *
 * It runs inside the transaction of the update, so a change that is rolled back leaves no row.
 */
class FileObserver
{
    public function updated(File $file): void
    {
        $changed = array_diff(array_keys($file->getChanges()), File::UNLOGGED_COLUMNS);

        if ($changed === []) {
            return;
        }

        $previous = [];

        foreach ($changed as $column) {
            $previous[$column] = $file->getRawOriginal($column);
        }

        $file->changes()->create([
            'previous' => $previous,
            'changed_by' => Auth::id(),
        ]);
    }
}

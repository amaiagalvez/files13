<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Files13\Http\Requests\FileStatusListRequest;
use Files13\Http\Requests\FileStatusDestroyRequest;
use Files13\Http\Requests\FileStatusRestoreRequest;
use Files13\Transformers\FileStatusListTransformer;
use Files13\Queries\FileStatuses\FileStatusListQuery;

/**
 * @extends TrashController<FileStatus>
 */
class FileStatusTrashController extends TrashController
{
    public function index(
        FileStatusListRequest $request,
        FileStatusListQuery $query,
        FileStatusListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileStatuses = $query->trashed($search);

        return $this->listView($request, 'files13::file-statuses.list', [
            'fileStatuses' => $fileStatuses,
            'list' => $transformer->trash(
                $fileStatuses,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $fileStatuses->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(FileStatusRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(FileStatusDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  FileStatus  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A status is named in four languages and restoring it puts all four back, so any of them being
     * taken by a live status makes the restore impossible.
     *
     * @param  FileStatus  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        foreach (FileStatus::NAME_FIELDS as $column) {
            $name = $record->getAttribute($column);

            if ($name !== null && FileStatus::query()->where($column, $name)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function recordClass(): string
    {
        return FileStatus::class;
    }

    protected function trashRoute(): string
    {
        return 'file-statuses.trash.index';
    }
}

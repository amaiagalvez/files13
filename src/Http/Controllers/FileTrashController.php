<?php

namespace Files13\Http\Controllers;

use Files13\Models\File;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Files13\Queries\Files\FileListQuery;
use Files13\Http\Requests\FileListRequest;
use Files13\Http\Requests\FileDestroyRequest;
use Files13\Http\Requests\FileRestoreRequest;
use Files13\Transformers\FileListTransformer;
use Basics13\Http\Controllers\TrashController;

/**
 * @extends TrashController<File>
 */
class FileTrashController extends TrashController
{
    public function index(
        FileListRequest $request,
        FileListQuery $query,
        FileListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $files = $query->trashed($search, $request->user());

        return $this->listView($request, 'files13::files.list', [
            'files' => $files,
            'list' => $transformer->trash(
                $files,
                $search,
                $query->visibleCounts($request->user()),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(FileRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(FileDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  File  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A file name must be free among the records that are not in the trash, so it cannot be put
     * back when a record nobody deleted took the same name in between.
     *
     * @param  File  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        return $this->takenBy(File::query(), $record->name);
    }

    protected function recordClass(): string
    {
        return File::class;
    }

    protected function trashRoute(): string
    {
        return 'files.trash.index';
    }
}

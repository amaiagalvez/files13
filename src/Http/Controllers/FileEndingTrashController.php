<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileEnding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Files13\Http\Requests\FileEndingListRequest;
use Files13\Http\Requests\FileEndingDestroyRequest;
use Files13\Http\Requests\FileEndingRestoreRequest;
use Files13\Transformers\FileEndingListTransformer;
use Files13\Queries\FileEndings\FileEndingListQuery;

/**
 * @extends TrashController<FileEnding>
 */
class FileEndingTrashController extends TrashController
{
    public function index(
        FileEndingListRequest $request,
        FileEndingListQuery $query,
        FileEndingListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileEndings = $query->trashed($search);

        return $this->listView($request, 'files13::file-endings.list', [
            'fileEndings' => $fileEndings,
            'list' => $transformer->trash(
                $fileEndings,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $fileEndings->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(FileEndingRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(FileEndingDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  FileEnding  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A ending is named in four languages and restoring it puts all four back, so any of them being
     * taken by a live ending makes the restore impossible.
     *
     * @param  FileEnding  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        foreach (FileEnding::NAME_FIELDS as $column) {
            $name = $record->getAttribute($column);

            if ($name !== null && FileEnding::query()->where($column, $name)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function recordClass(): string
    {
        return FileEnding::class;
    }

    protected function trashRoute(): string
    {
        return 'file-endings.trash.index';
    }
}

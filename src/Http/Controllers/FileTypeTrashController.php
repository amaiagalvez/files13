<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Files13\Http\Requests\FileTypeListRequest;
use Files13\Queries\FileTypes\FileTypeListQuery;
use Files13\Http\Requests\FileTypeDestroyRequest;
use Files13\Http\Requests\FileTypeRestoreRequest;
use Files13\Transformers\FileTypeListTransformer;

/**
 * @extends TrashController<FileType>
 */
class FileTypeTrashController extends TrashController
{
    public function index(
        FileTypeListRequest $request,
        FileTypeListQuery $query,
        FileTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileTypes = $query->trashed($search);

        return $this->listView($request, 'files13::file-types.list', [
            'fileTypes' => $fileTypes,
            'list' => $transformer->trash(
                $fileTypes,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $fileTypes->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(FileTypeRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(FileTypeDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  FileType  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A type is named in four languages and restoring it puts all four back, so any of them being
     * taken by a live type makes the restore impossible.
     *
     * @param  FileType  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        foreach (FileType::NAME_FIELDS as $column) {
            $name = $record->getAttribute($column);

            if ($name !== null && FileType::query()->where($column, $name)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function recordClass(): string
    {
        return FileType::class;
    }

    protected function trashRoute(): string
    {
        return 'file-types.trash.index';
    }
}

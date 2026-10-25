<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Files13\Http\Requests\FileLocationListRequest;
use Files13\Http\Requests\FileLocationDestroyRequest;
use Files13\Http\Requests\FileLocationRestoreRequest;
use Files13\Transformers\FileLocationListTransformer;
use Files13\Queries\FileLocations\FileLocationListQuery;

/**
 * @extends TrashController<FileLocation>
 */
class FileLocationTrashController extends TrashController
{
    public function index(
        FileLocationListRequest $request,
        FileLocationListQuery $query,
        FileLocationListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileLocations = $query->trashed($search);

        return $this->listView($request, 'files13::file-locations.list', [
            'fileLocations' => $fileLocations,
            'list' => $transformer->trash(
                $fileLocations,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $fileLocations->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(FileLocationRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(FileLocationDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  FileLocation  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A location is named in four languages and restoring it puts all four back, so any of them being
     * taken by a live location makes the restore impossible.
     *
     * @param  FileLocation  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        foreach (FileLocation::NAME_FIELDS as $column) {
            $name = $record->getAttribute($column);

            if ($name !== null && FileLocation::query()->where($column, $name)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function recordClass(): string
    {
        return FileLocation::class;
    }

    protected function trashRoute(): string
    {
        return 'file-locations.trash.index';
    }
}

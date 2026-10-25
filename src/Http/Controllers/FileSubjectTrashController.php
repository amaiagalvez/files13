<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileSubject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Files13\Http\Requests\FileSubjectListRequest;
use Files13\Http\Requests\FileSubjectDestroyRequest;
use Files13\Http\Requests\FileSubjectRestoreRequest;
use Files13\Transformers\FileSubjectListTransformer;
use Files13\Queries\FileSubjects\FileSubjectListQuery;

/**
 * @extends TrashController<FileSubject>
 */
class FileSubjectTrashController extends TrashController
{
    public function index(
        FileSubjectListRequest $request,
        FileSubjectListQuery $query,
        FileSubjectListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileSubjects = $query->trashed($search);

        return $this->listView($request, 'files13::file-subjects.list', [
            'fileSubjects' => $fileSubjects,
            'list' => $transformer->trash(
                $fileSubjects,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $fileSubjects->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(FileSubjectRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(FileSubjectDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  FileSubject  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A subject is named in four languages and restoring it puts all four back, so any of them being
     * taken by a live subject makes the restore impossible.
     *
     * @param  FileSubject  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        foreach (FileSubject::NAME_FIELDS as $column) {
            $name = $record->getAttribute($column);

            if ($name !== null && FileSubject::query()->where($column, $name)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function recordClass(): string
    {
        return FileSubject::class;
    }

    protected function trashRoute(): string
    {
        return 'file-subjects.trash.index';
    }
}

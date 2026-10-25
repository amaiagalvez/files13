<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileSubject;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\ArchivedController;
use Files13\Http\Requests\FileSubjectListRequest;
use Files13\Transformers\FileSubjectListTransformer;
use Files13\Queries\FileSubjects\FileSubjectListQuery;

class FileSubjectArchivedController extends ArchivedController
{
    public function index(
        FileSubjectListRequest $request,
        FileSubjectListQuery $query,
        FileSubjectListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileSubjects = $query->archived($search);

        return $this->listView($request, 'files13::file-subjects.list', [
            'fileSubjects' => $fileSubjects,
            'list' => $transformer->archived(
                $fileSubjects,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $fileSubjects->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(FileSubject $fileSubject): RedirectResponse
    {
        return $this->archiveRecord($fileSubject);
    }

    public function activate(FileSubject $fileSubject): RedirectResponse
    {
        return $this->activateRecord($fileSubject);
    }

    protected function activeRoute(): string
    {
        return 'file-subjects.index';
    }

    protected function archivedRoute(): string
    {
        return 'file-subjects.archived.index';
    }
}

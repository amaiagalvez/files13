<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileStatus;
use Illuminate\Http\RedirectResponse;
use Files13\Http\Requests\FileStatusListRequest;
use Basics13\Http\Controllers\ArchivedController;
use Files13\Transformers\FileStatusListTransformer;
use Files13\Queries\FileStatuses\FileStatusListQuery;

class FileStatusArchivedController extends ArchivedController
{
    public function index(
        FileStatusListRequest $request,
        FileStatusListQuery $query,
        FileStatusListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileStatuses = $query->archived($search);

        return $this->listView($request, 'files13::file-statuses.list', [
            'fileStatuses' => $fileStatuses,
            'list' => $transformer->archived(
                $fileStatuses,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $fileStatuses->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(FileStatus $fileStatus): RedirectResponse
    {
        return $this->archiveRecord($fileStatus);
    }

    public function activate(FileStatus $fileStatus): RedirectResponse
    {
        return $this->activateRecord($fileStatus);
    }

    protected function activeRoute(): string
    {
        return 'file-statuses.index';
    }

    protected function archivedRoute(): string
    {
        return 'file-statuses.archived.index';
    }
}

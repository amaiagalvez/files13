<?php

namespace Files13\Http\Controllers;

use Files13\Models\File;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Files13\Queries\Files\FileListQuery;
use Files13\Http\Requests\FileListRequest;
use Files13\Transformers\FileListTransformer;
use Basics13\Http\Controllers\ArchivedController;

class FileArchivedController extends ArchivedController
{
    public function index(
        FileListRequest $request,
        FileListQuery $query,
        FileListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $files = $query->archived($search, $request->user());

        return $this->listView($request, 'files13::files.list', [
            'files' => $files,
            'list' => $transformer->archived(
                $files,
                $search,
                $query->visibleCounts($request->user()),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(File $file): RedirectResponse
    {
        return $this->archiveRecord($file);
    }

    public function activate(File $file): RedirectResponse
    {
        return $this->activateRecord($file);
    }

    protected function activeRoute(): string
    {
        return 'files.index';
    }

    protected function archivedRoute(): string
    {
        return 'files.archived.index';
    }
}

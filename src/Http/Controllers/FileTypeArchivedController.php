<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileType;
use Illuminate\Http\RedirectResponse;
use Files13\Http\Requests\FileTypeListRequest;
use Files13\Queries\FileTypes\FileTypeListQuery;
use Basics13\Http\Controllers\ArchivedController;
use Files13\Transformers\FileTypeListTransformer;

class FileTypeArchivedController extends ArchivedController
{
    public function index(
        FileTypeListRequest $request,
        FileTypeListQuery $query,
        FileTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileTypes = $query->archived($search);

        return $this->listView($request, 'files13::file-types.list', [
            'fileTypes' => $fileTypes,
            'list' => $transformer->archived(
                $fileTypes,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $fileTypes->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(FileType $fileType): RedirectResponse
    {
        return $this->archiveRecord($fileType);
    }

    public function activate(FileType $fileType): RedirectResponse
    {
        return $this->activateRecord($fileType);
    }

    protected function activeRoute(): string
    {
        return 'file-types.index';
    }

    protected function archivedRoute(): string
    {
        return 'file-types.archived.index';
    }
}

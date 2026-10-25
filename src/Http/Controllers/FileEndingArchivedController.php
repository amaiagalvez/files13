<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileEnding;
use Illuminate\Http\RedirectResponse;
use Files13\Http\Requests\FileEndingListRequest;
use Basics13\Http\Controllers\ArchivedController;
use Files13\Transformers\FileEndingListTransformer;
use Files13\Queries\FileEndings\FileEndingListQuery;

class FileEndingArchivedController extends ArchivedController
{
    public function index(
        FileEndingListRequest $request,
        FileEndingListQuery $query,
        FileEndingListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileEndings = $query->archived($search);

        return $this->listView($request, 'files13::file-endings.list', [
            'fileEndings' => $fileEndings,
            'list' => $transformer->archived(
                $fileEndings,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $fileEndings->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(FileEnding $fileEnding): RedirectResponse
    {
        return $this->archiveRecord($fileEnding);
    }

    public function activate(FileEnding $fileEnding): RedirectResponse
    {
        return $this->activateRecord($fileEnding);
    }

    protected function activeRoute(): string
    {
        return 'file-endings.index';
    }

    protected function archivedRoute(): string
    {
        return 'file-endings.archived.index';
    }
}

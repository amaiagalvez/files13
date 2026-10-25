<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\FileLocation;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\ArchivedController;
use Files13\Http\Requests\FileLocationListRequest;
use Files13\Transformers\FileLocationListTransformer;
use Files13\Queries\FileLocations\FileLocationListQuery;

class FileLocationArchivedController extends ArchivedController
{
    public function index(
        FileLocationListRequest $request,
        FileLocationListQuery $query,
        FileLocationListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $fileLocations = $query->archived($search);

        return $this->listView($request, 'files13::file-locations.list', [
            'fileLocations' => $fileLocations,
            'list' => $transformer->archived(
                $fileLocations,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $fileLocations->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(FileLocation $fileLocation): RedirectResponse
    {
        return $this->archiveRecord($fileLocation);
    }

    public function activate(FileLocation $fileLocation): RedirectResponse
    {
        return $this->activateRecord($fileLocation);
    }

    protected function activeRoute(): string
    {
        return 'file-locations.index';
    }

    protected function archivedRoute(): string
    {
        return 'file-locations.archived.index';
    }
}

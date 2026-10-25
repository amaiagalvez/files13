<?php

namespace Files13\Http\Controllers;

use Files13\Models\Phase;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Files13\Queries\Phases\PhaseListQuery;
use Files13\Http\Requests\PhaseListRequest;
use Files13\Transformers\PhaseListTransformer;
use Basics13\Http\Controllers\ArchivedController;

class PhaseArchivedController extends ArchivedController
{
    public function index(
        PhaseListRequest $request,
        PhaseListQuery $query,
        PhaseListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $phases = $query->archived($search);

        return $this->listView($request, 'files13::phases.list', [
            'phases' => $phases,
            'list' => $transformer->archived(
                $phases,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $phases->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(Phase $phase): RedirectResponse
    {
        return $this->archiveRecord($phase);
    }

    public function activate(Phase $phase): RedirectResponse
    {
        return $this->activateRecord($phase);
    }

    protected function activeRoute(): string
    {
        return 'phases.index';
    }

    protected function archivedRoute(): string
    {
        return 'phases.archived.index';
    }
}

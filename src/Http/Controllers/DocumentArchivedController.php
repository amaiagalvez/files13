<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\Document;
use Illuminate\Http\RedirectResponse;
use Files13\Http\Requests\DocumentListRequest;
use Files13\Queries\Documents\DocumentListQuery;
use Basics13\Http\Controllers\ArchivedController;
use Files13\Transformers\DocumentListTransformer;

class DocumentArchivedController extends ArchivedController
{
    public function index(
        DocumentListRequest $request,
        DocumentListQuery $query,
        DocumentListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $documents = $query->archived($search);

        return $this->listView($request, 'files13::documents.list', [
            'documents' => $documents,
            'list' => $transformer->archived(
                $documents,
                $search,
                $query->templateCounts(),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(Document $document): RedirectResponse
    {
        return $this->archiveRecord($document);
    }

    public function activate(Document $document): RedirectResponse
    {
        return $this->activateRecord($document);
    }

    protected function activeRoute(): string
    {
        return 'documents.index';
    }

    protected function archivedRoute(): string
    {
        return 'documents.archived.index';
    }
}

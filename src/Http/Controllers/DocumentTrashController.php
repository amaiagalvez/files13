<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\Document;
use Files13\Models\DocumentTrack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Files13\Http\Requests\DocumentListRequest;
use Files13\Queries\Documents\DocumentListQuery;
use Files13\Http\Requests\DocumentDestroyRequest;
use Files13\Http\Requests\DocumentRestoreRequest;
use Files13\Transformers\DocumentListTransformer;

/**
 * @extends TrashController<Document>
 */
class DocumentTrashController extends TrashController
{
    public function index(
        DocumentListRequest $request,
        DocumentListQuery $query,
        DocumentListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $documents = $query->trashed($search);

        return $this->listView($request, 'files13::documents.list', [
            'documents' => $documents,
            'list' => $transformer->trash(
                $documents,
                $search,
                $query->templateCounts(),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(DocumentRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(DocumentDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  Document  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
        DocumentTrack::record($record, 'restore');
    }

    /**
     * @param  Document  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        // Documents share names freely: a template is named after what it is for, and every copy
        // keeps the name of its template.
        return false;
    }

    protected function recordClass(): string
    {
        return Document::class;
    }

    protected function trashRoute(): string
    {
        return 'documents.trash.index';
    }
}

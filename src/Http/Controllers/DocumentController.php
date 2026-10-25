<?php

namespace Files13\Http\Controllers;

use Illuminate\View\View;
use Files13\Models\Document;
use Files13\Models\DocumentTrack;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\Controller;
use Files13\Http\Requests\DocumentRequest;
use Files13\Http\Requests\DocumentListRequest;
use Files13\Queries\Documents\DocumentListQuery;
use Files13\Queries\Phases\PhaseFormOptionsQuery;
use Files13\Transformers\DocumentListTransformer;

/**
 * The document templates files copy their documents from.
 */
class DocumentController extends Controller
{
    public function index(
        DocumentListRequest $request,
        DocumentListQuery $query,
        DocumentListTransformer $transformer,
        PhaseFormOptionsQuery $options,
    ): View|string {
        $search = $request->search();
        $documents = $query->active($search);

        return $this->listView($request, 'files13::documents.list', [
            'documents' => $documents,
            'list' => $transformer->active($documents, $search, $query->templateCounts()),
            'options' => ['subjects' => $options->options()['subjects']],
            'scope' => 'active',
        ]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        DB::transaction(static function () use ($request): void {
            $document = new Document($request->safe()->only(['name', 'notes']));
            $document->template = true;

            if ($request->hasFile('content')) {
                $document->storeUpload($request->file('content'));
            }

            $document->save();
            $document->subjects()->sync($request->validated()['subject_ids'] ?? []);
            DocumentTrack::record($document, 'create');
        });

        return $this->backToList('documents.index')->with('status', __('basics13::messages.created'));
    }

    public function update(DocumentRequest $request, Document $document): RedirectResponse
    {
        DB::transaction(static function () use ($request, $document): void {
            $document->fill($request->safe()->only(['name', 'notes']));

            if ($request->hasFile('content')) {
                $document->deleteContent();
                $document->storeUpload($request->file('content'));
            }

            $document->save();
            $document->subjects()->sync($request->validated()['subject_ids'] ?? []);
        });

        return $this->backToList('documents.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        // The deleting hook refuses a document that is open in the editor.
        $deleted = DB::transaction(static fn (): bool => $document->delete() !== false);

        if (! $deleted) {
            return $this->backToList('documents.index')->with('error', __('A document open in the editor cannot be deleted.'));
        }

        return $this->backToList('documents.index')->with('status', __('basics13::messages.moved_to_trash'));
    }
}

<?php

namespace Files13\Http\Controllers;

use Files13\Models\File;
use Files13\Models\Folder;
use Files13\Models\Document;
use Files13\Models\DocumentTrack;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\Controller;
use Files13\Http\Requests\FileDocumentRequest;
use Files13\Http\Requests\FileDocumentFolderRequest;

/**
 * The documents panel of the file sheet. Uploads and template copies have side effects outside the
 * file form, so each action has its own endpoint, authorized by the file's `update` ability.
 */
class FileDocumentController extends Controller
{
    public function store(FileDocumentRequest $request, File $file): RedirectResponse
    {
        DB::transaction(static function () use ($request, $file): void {
            $templateId = $request->validated()['template_id'] ?? null;
            $document = $templateId !== null
                ? Document::query()->whereKey($templateId)->firstOrFail()->copyFromTemplate()
                : new Document;

            $name = $request->validated()['name'] ?? null;

            if (is_string($name)) {
                $document->name = $name;
            }

            if ($request->hasFile('content')) {
                $document->deleteContent();
                $document->storeUpload($request->file('content'));
            }

            $document->save();
            $file->documents()->attach($document, ['folder_id' => Folder::named($request->validated()['folder'] ?? null)?->id]);
            DocumentTrack::record($document, 'create');
        });

        return redirect()->route('files.show', $file)->with('status', __('basics13::messages.created'));
    }

    public function update(FileDocumentFolderRequest $request, File $file, Document $document): RedirectResponse
    {
        $file->documents()->updateExistingPivot($document->id, [
            'folder_id' => Folder::named($request->validated()['folder'] ?? null)?->id,
        ]);

        return redirect()->route('files.show', $file)->with('status', __('basics13::messages.updated'));
    }

    public function destroy(FileDocumentFolderRequest $request, File $file, Document $document): RedirectResponse
    {
        // The deleting hook refuses a document that is open in the editor.
        $deleted = DB::transaction(static fn (): bool => $document->delete() !== false);

        if (! $deleted) {
            return redirect()->route('files.show', $file)->with('error', __('A document open in the editor cannot be deleted.'));
        }

        return redirect()->route('files.show', $file)->with('status', __('basics13::messages.moved_to_trash'));
    }
}

<?php

namespace Files13\Http\Requests;

use Files13\Models\File;
use Files13\Models\Document;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Opening a document in the editor. A template is reached through the template permissions; a
 * document of a file through its file: whoever may view the file reads it, and whoever may change
 * the file edits it.
 */
class DocumentEditorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        if (! $document instanceof Document) {
            return false;
        }

        if ($document->template) {
            return $this->user()?->can('viewAny', Document::class) ?? false;
        }

        $file = $this->fileOf($document);

        return $file !== null && ($this->user()?->can('view', $file) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Whether the user edits the document or only reads it. The server decides; the editor only
     * shows what the signed configuration says.
     */
    public function writable(Document $document): bool
    {
        if ($document->template) {
            return $this->user()?->can('update', $document) ?? false;
        }

        $file = $this->fileOf($document);

        return $file !== null && ($this->user()?->can('update', $file) ?? false);
    }

    private function fileOf(Document $document): ?File
    {
        return $document->files()->first();
    }
}

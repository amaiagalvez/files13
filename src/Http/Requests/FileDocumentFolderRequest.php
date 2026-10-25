<?php

namespace Files13\Http\Requests;

use Files13\Models\File;
use Files13\Models\Document;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Moving a document of a file to another folder, named by the user. The document has to be one of
 * the documents of that file.
 */
class FileDocumentFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $file = $this->route('file');
        $document = $this->route('document');

        return $file instanceof File
            && $document instanceof Document
            && $file->documents()->whereKey($document->id)->exists()
            && ($this->user()?->can('update', $file) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'folder' => ['nullable', 'string', 'max:'.MaxLength::string()],
        ];
    }
}

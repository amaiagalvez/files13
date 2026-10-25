<?php

namespace Files13\Http\Requests;

use Files13\Models\File;
use Files13\Models\Document;
use Illuminate\Validation\Rule;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A document added to a file: copied from a template, uploaded, or blank, and placed in a folder
 * named by the user.
 */
class FileDocumentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['template_id', 'folder', 'name'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    public function authorize(): bool
    {
        $file = $this->route('file');

        return $file instanceof File && ($this->user()?->can('update', $file) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'template_id' => ['nullable', 'integer', Rule::exists(Document::class, 'id')->where('template', true)->where('active', true)->where('editing', false)->whereNull('deleted_at')],
            'name' => ['required_without:template_id', 'nullable', 'string', 'min:2', 'max:'.MaxLength::string()],
            'content' => ['nullable', 'file', 'max:'.(int) config('files13.max_upload_kb'), 'extensions:'.implode(',', Document::EXTENSIONS)],
            'folder' => ['nullable', 'string', 'max:'.MaxLength::string()],
        ];
    }
}

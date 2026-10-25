<?php

namespace Files13\Http\Requests;

use Files13\Models\File;
use Illuminate\Foundation\Http\FormRequest;

class FileShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        $file = $this->route('file');

        return $file instanceof File && ($this->user()?->can('view', $file) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}

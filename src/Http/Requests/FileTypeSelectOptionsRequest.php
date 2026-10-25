<?php

namespace Files13\Http\Requests;

use Files13\Models\FileType;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class FileTypeSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FileType::class) ?? false;
    }
}

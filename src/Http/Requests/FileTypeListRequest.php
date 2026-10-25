<?php

namespace Files13\Http\Requests;

use Files13\Models\FileType;
use Basics13\Http\Requests\SearchableListRequest;

class FileTypeListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FileType::class) ?? false;
    }
}

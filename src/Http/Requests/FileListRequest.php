<?php

namespace Files13\Http\Requests;

use Files13\Models\File;
use Basics13\Http\Requests\SearchableListRequest;

class FileListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', File::class) ?? false;
    }
}

<?php

namespace Files13\Http\Requests;

use Files13\Models\FileLocation;
use Basics13\Http\Requests\SearchableListRequest;

class FileLocationListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FileLocation::class) ?? false;
    }
}

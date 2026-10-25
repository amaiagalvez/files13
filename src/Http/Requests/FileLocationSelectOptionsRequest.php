<?php

namespace Files13\Http\Requests;

use Files13\Models\FileLocation;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class FileLocationSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FileLocation::class) ?? false;
    }
}

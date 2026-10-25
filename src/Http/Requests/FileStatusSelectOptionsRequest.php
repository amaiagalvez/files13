<?php

namespace Files13\Http\Requests;

use Files13\Models\FileStatus;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class FileStatusSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FileStatus::class) ?? false;
    }
}

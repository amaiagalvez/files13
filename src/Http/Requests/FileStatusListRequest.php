<?php

namespace Files13\Http\Requests;

use Files13\Models\FileStatus;
use Basics13\Http\Requests\SearchableListRequest;

class FileStatusListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FileStatus::class) ?? false;
    }
}

<?php

namespace Files13\Http\Requests;

use Files13\Models\FileSubject;
use Basics13\Http\Requests\SearchableListRequest;

class FileSubjectListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FileSubject::class) ?? false;
    }
}

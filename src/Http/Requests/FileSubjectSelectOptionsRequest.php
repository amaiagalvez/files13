<?php

namespace Files13\Http\Requests;

use Files13\Models\FileSubject;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class FileSubjectSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FileSubject::class) ?? false;
    }
}

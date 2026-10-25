<?php

namespace Files13\Http\Requests;

use Files13\Models\FileEnding;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class FileEndingSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', FileEnding::class) ?? false;
    }
}

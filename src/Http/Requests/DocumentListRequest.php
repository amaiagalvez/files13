<?php

namespace Files13\Http\Requests;

use Files13\Models\Document;
use Basics13\Http\Requests\SearchableListRequest;

class DocumentListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Document::class) ?? false;
    }
}

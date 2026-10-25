<?php

namespace Files13\Http\Requests;

use Files13\Models\Phase;
use Basics13\Http\Requests\SearchableListRequest;

class PhaseListRequest extends SearchableListRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Phase::class) ?? false;
    }
}

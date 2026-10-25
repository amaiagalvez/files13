<?php

namespace Files13\Http\Requests;

use Files13\Models\Phase;
use Basics13\Http\Requests\SearchableSelectOptionsRequest;

class PhaseSelectOptionsRequest extends SearchableSelectOptionsRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Phase::class) ?? false;
    }
}

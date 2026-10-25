<?php

namespace Files13\Http\Requests;

use Files13\Models\Phase;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<Phase>
 */
final class PhaseDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return Phase
     */
    protected function findTrashed(): Model
    {
        return Phase::onlyTrashed()
            ->findOrFail((int) $this->route('phase'));
    }
}

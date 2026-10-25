<?php

namespace Files13\Http\Requests;

use Files13\Models\FileEnding;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<FileEnding>
 */
final class FileEndingDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return FileEnding
     */
    protected function findTrashed(): Model
    {
        return FileEnding::onlyTrashed()
            ->findOrFail((int) $this->route('file_ending'));
    }
}

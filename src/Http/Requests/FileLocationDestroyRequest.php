<?php

namespace Files13\Http\Requests;

use Files13\Models\FileLocation;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<FileLocation>
 */
final class FileLocationDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return FileLocation
     */
    protected function findTrashed(): Model
    {
        return FileLocation::onlyTrashed()
            ->findOrFail((int) $this->route('file_location'));
    }
}

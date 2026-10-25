<?php

namespace Files13\Http\Requests;

use Files13\Models\FileLocation;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<FileLocation>
 */
final class FileLocationRestoreRequest extends RestoreRequest
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

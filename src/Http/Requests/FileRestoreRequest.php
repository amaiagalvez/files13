<?php

namespace Files13\Http\Requests;

use Files13\Models\File;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<File>
 */
final class FileRestoreRequest extends RestoreRequest
{
    /**
     * @return File
     */
    protected function findTrashed(): Model
    {
        return File::onlyTrashed()
            ->findOrFail((int) $this->route('file'));
    }
}

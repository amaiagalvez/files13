<?php

namespace Files13\Http\Requests;

use Files13\Models\FileEnding;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<FileEnding>
 */
final class FileEndingRestoreRequest extends RestoreRequest
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

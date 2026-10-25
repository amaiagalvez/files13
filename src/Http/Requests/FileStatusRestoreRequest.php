<?php

namespace Files13\Http\Requests;

use Files13\Models\FileStatus;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<FileStatus>
 */
final class FileStatusRestoreRequest extends RestoreRequest
{
    /**
     * @return FileStatus
     */
    protected function findTrashed(): Model
    {
        return FileStatus::onlyTrashed()
            ->findOrFail((int) $this->route('file_status'));
    }
}

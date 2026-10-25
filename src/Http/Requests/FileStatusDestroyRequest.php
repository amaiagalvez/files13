<?php

namespace Files13\Http\Requests;

use Files13\Models\FileStatus;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<FileStatus>
 */
final class FileStatusDestroyRequest extends TrashDestroyRequest
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

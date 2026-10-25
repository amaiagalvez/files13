<?php

namespace Files13\Http\Requests;

use Files13\Models\FileType;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<FileType>
 */
final class FileTypeRestoreRequest extends RestoreRequest
{
    /**
     * @return FileType
     */
    protected function findTrashed(): Model
    {
        return FileType::onlyTrashed()
            ->findOrFail((int) $this->route('file_type'));
    }
}

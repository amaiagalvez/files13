<?php

namespace Files13\Http\Requests;

use Files13\Models\FileType;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<FileType>
 */
final class FileTypeDestroyRequest extends TrashDestroyRequest
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

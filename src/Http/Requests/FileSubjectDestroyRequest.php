<?php

namespace Files13\Http\Requests;

use Files13\Models\FileSubject;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\TrashDestroyRequest;

/**
 * @extends TrashDestroyRequest<FileSubject>
 */
final class FileSubjectDestroyRequest extends TrashDestroyRequest
{
    /**
     * @return FileSubject
     */
    protected function findTrashed(): Model
    {
        return FileSubject::onlyTrashed()
            ->findOrFail((int) $this->route('file_subject'));
    }
}

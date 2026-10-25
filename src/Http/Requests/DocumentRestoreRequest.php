<?php

namespace Files13\Http\Requests;

use Files13\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Requests\RestoreRequest;

/**
 * @extends RestoreRequest<Document>
 */
final class DocumentRestoreRequest extends RestoreRequest
{
    /**
     * @return Document
     */
    protected function findTrashed(): Model
    {
        return Document::onlyTrashed()->where('template', true)
            ->findOrFail((int) $this->route('document'));
    }
}

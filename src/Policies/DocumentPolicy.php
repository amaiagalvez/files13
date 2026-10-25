<?php

namespace Files13\Policies;

use Files13\Models\Document;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Document template abilities, each one granted by its `file_documents.{action}` permission.
 */
class DocumentPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'file_documents.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'file_documents.create');
    }

    public function update(User $user, Document $document): bool
    {
        return $this->permits($user, 'file_documents.edit');
    }

    public function delete(User $user, Document $document): bool
    {
        return $this->permits($user, 'file_documents.delete');
    }

    public function restore(User $user, Document $document): bool
    {
        return $this->permits($user, 'file_documents.restore');
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $this->permits($user, 'file_documents.force_delete');
    }

    public function archive(User $user, Document $document): bool
    {
        return $this->permits($user, 'file_documents.archive');
    }

    public function activate(User $user, Document $document): bool
    {
        return $this->permits($user, 'file_documents.archive');
    }
}

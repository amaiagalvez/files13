<?php

namespace Files13\Policies;

use Files13\Models\FileSubject;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Subject abilities, each one granted by its `file_subjects.{action}` permission.
 */
class FileSubjectPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'file_subjects.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'file_subjects.create');
    }

    public function update(User $user, FileSubject $fileSubject): bool
    {
        return $this->permits($user, 'file_subjects.edit');
    }

    public function delete(User $user, FileSubject $fileSubject): bool
    {
        return $this->permits($user, 'file_subjects.delete');
    }

    public function restore(User $user, FileSubject $fileSubject): bool
    {
        return $this->permits($user, 'file_subjects.restore');
    }

    public function forceDelete(User $user, FileSubject $fileSubject): bool
    {
        return $this->permits($user, 'file_subjects.force_delete');
    }

    public function archive(User $user, FileSubject $fileSubject): bool
    {
        return $this->permits($user, 'file_subjects.archive');
    }

    public function activate(User $user, FileSubject $fileSubject): bool
    {
        return $this->permits($user, 'file_subjects.archive');
    }
}

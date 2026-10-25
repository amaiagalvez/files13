<?php

namespace Files13\Policies;

use Files13\Models\FileStatus;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Status abilities, each one granted by its `file_statuses.{action}` permission.
 */
class FileStatusPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'file_statuses.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'file_statuses.create');
    }

    public function update(User $user, FileStatus $fileStatus): bool
    {
        return $this->permits($user, 'file_statuses.edit');
    }

    public function delete(User $user, FileStatus $fileStatus): bool
    {
        return $this->permits($user, 'file_statuses.delete');
    }

    public function restore(User $user, FileStatus $fileStatus): bool
    {
        return $this->permits($user, 'file_statuses.restore');
    }

    public function forceDelete(User $user, FileStatus $fileStatus): bool
    {
        return $this->permits($user, 'file_statuses.force_delete');
    }

    public function archive(User $user, FileStatus $fileStatus): bool
    {
        return $this->permits($user, 'file_statuses.archive');
    }

    public function activate(User $user, FileStatus $fileStatus): bool
    {
        return $this->permits($user, 'file_statuses.archive');
    }
}

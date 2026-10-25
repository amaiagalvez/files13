<?php

namespace Files13\Policies;

use Files13\Models\FileType;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Type abilities, each one granted by its `file_types.{action}` permission.
 */
class FileTypePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'file_types.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'file_types.create');
    }

    public function update(User $user, FileType $fileType): bool
    {
        return $this->permits($user, 'file_types.edit');
    }

    public function delete(User $user, FileType $fileType): bool
    {
        return $this->permits($user, 'file_types.delete');
    }

    public function restore(User $user, FileType $fileType): bool
    {
        return $this->permits($user, 'file_types.restore');
    }

    public function forceDelete(User $user, FileType $fileType): bool
    {
        return $this->permits($user, 'file_types.force_delete');
    }

    public function archive(User $user, FileType $fileType): bool
    {
        return $this->permits($user, 'file_types.archive');
    }

    public function activate(User $user, FileType $fileType): bool
    {
        return $this->permits($user, 'file_types.archive');
    }
}

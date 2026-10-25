<?php

namespace Files13\Policies;

use Files13\Models\FileLocation;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Location abilities, each one granted by its `file_locations.{action}` permission.
 */
class FileLocationPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'file_locations.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'file_locations.create');
    }

    public function update(User $user, FileLocation $fileLocation): bool
    {
        return $this->permits($user, 'file_locations.edit');
    }

    public function delete(User $user, FileLocation $fileLocation): bool
    {
        return $this->permits($user, 'file_locations.delete');
    }

    public function restore(User $user, FileLocation $fileLocation): bool
    {
        return $this->permits($user, 'file_locations.restore');
    }

    public function forceDelete(User $user, FileLocation $fileLocation): bool
    {
        return $this->permits($user, 'file_locations.force_delete');
    }

    public function archive(User $user, FileLocation $fileLocation): bool
    {
        return $this->permits($user, 'file_locations.archive');
    }

    public function activate(User $user, FileLocation $fileLocation): bool
    {
        return $this->permits($user, 'file_locations.archive');
    }
}

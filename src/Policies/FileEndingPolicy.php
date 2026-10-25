<?php

namespace Files13\Policies;

use Files13\Models\FileEnding;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Ending abilities, each one granted by its `file_endings.{action}` permission.
 */
class FileEndingPolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'file_endings.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'file_endings.create');
    }

    public function update(User $user, FileEnding $fileEnding): bool
    {
        return $this->permits($user, 'file_endings.edit');
    }

    public function delete(User $user, FileEnding $fileEnding): bool
    {
        return $this->permits($user, 'file_endings.delete');
    }

    public function restore(User $user, FileEnding $fileEnding): bool
    {
        return $this->permits($user, 'file_endings.restore');
    }

    public function forceDelete(User $user, FileEnding $fileEnding): bool
    {
        return $this->permits($user, 'file_endings.force_delete');
    }

    public function archive(User $user, FileEnding $fileEnding): bool
    {
        return $this->permits($user, 'file_endings.archive');
    }

    public function activate(User $user, FileEnding $fileEnding): bool
    {
        return $this->permits($user, 'file_endings.archive');
    }
}

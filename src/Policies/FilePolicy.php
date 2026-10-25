<?php

namespace Files13\Policies;

use Files13\Models\File;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * File abilities, each one granted by its `files.{action}` permission.
 *
 * `files.view_own` and `files.manage_own` grant the same abilities over the files the user created
 * or is assigned to: the global permission is checked first, and the own one only when it is
 * missing. A user who may only manage their own files sees the other files they may view read-only.
 */
class FilePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'files.view')
            || $this->permits($user, 'files.view_own')
            || $this->permits($user, 'files.manage_own');
    }

    /**
     * Whether the user sees every file, or only their own ones.
     */
    public function viewAll(User $user): bool
    {
        return $this->permits($user, 'files.view');
    }

    public function view(User $user, File $file): bool
    {
        return $this->permits($user, 'files.view')
            || (($this->permits($user, 'files.view_own') || $this->permits($user, 'files.manage_own')) && $this->owns($user, $file));
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'files.create') || $this->permits($user, 'files.manage_own');
    }

    public function update(User $user, File $file): bool
    {
        return $this->allows($user, $file, 'files.edit');
    }

    public function delete(User $user, File $file): bool
    {
        return $this->allows($user, $file, 'files.delete');
    }

    public function restore(User $user, File $file): bool
    {
        return $this->allows($user, $file, 'files.restore');
    }

    public function forceDelete(User $user, File $file): bool
    {
        return $this->allows($user, $file, 'files.force_delete');
    }

    public function archive(User $user, File $file): bool
    {
        return $this->allows($user, $file, 'files.archive');
    }

    public function activate(User $user, File $file): bool
    {
        return $this->allows($user, $file, 'files.archive');
    }

    /**
     * The global permission, or `files.manage_own` over a file of the user.
     */
    private function allows(User $user, File $file, string $permission): bool
    {
        return $this->permits($user, $permission)
            || ($this->permits($user, 'files.manage_own') && $this->owns($user, $file));
    }

    private function owns(User $user, File $file): bool
    {
        return $file->belongsToUser((int) $user->getAuthIdentifier());
    }
}

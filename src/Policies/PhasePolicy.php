<?php

namespace Files13\Policies;

use Files13\Models\Phase;
use Basics13\Concerns\ChecksPermissions;
use Illuminate\Contracts\Auth\Authenticatable as User;

/**
 * Phase abilities, each one granted by its `phases.{action}` permission.
 */
class PhasePolicy
{
    use ChecksPermissions;

    public function viewAny(User $user): bool
    {
        return $this->permits($user, 'file_phases.view');
    }

    public function create(User $user): bool
    {
        return $this->permits($user, 'file_phases.create');
    }

    public function update(User $user, Phase $phase): bool
    {
        return $this->permits($user, 'file_phases.edit');
    }

    public function delete(User $user, Phase $phase): bool
    {
        return $this->permits($user, 'file_phases.delete');
    }

    public function restore(User $user, Phase $phase): bool
    {
        return $this->permits($user, 'file_phases.restore');
    }

    public function forceDelete(User $user, Phase $phase): bool
    {
        return $this->permits($user, 'file_phases.force_delete');
    }

    public function archive(User $user, Phase $phase): bool
    {
        return $this->permits($user, 'file_phases.archive');
    }

    public function activate(User $user, Phase $phase): bool
    {
        return $this->permits($user, 'file_phases.archive');
    }
}

<?php

namespace Files13\Tests\Unit\Policies;

use Files13\Models\FileStatus;
use PHPUnit\Framework\TestCase;
use Files13\Policies\FileStatusPolicy;
use Basics13\Support\Testing\PermissionsActor;

class FileStatusPolicyTest extends TestCase
{
    public function test_each_ability_needs_its_own_permission(): void
    {
        $policy = new FileStatusPolicy;
        $record = (new FileStatus)->forceFill(['id' => 1, 'name_eu' => 'Gaia']);

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['file_statuses.view'])));
        $this->assertTrue($policy->create(PermissionsActor::holding(['file_statuses.create'])));
        $this->assertTrue($policy->update(PermissionsActor::holding(['file_statuses.edit']), $record));
        $this->assertTrue($policy->delete(PermissionsActor::holding(['file_statuses.delete']), $record));
        $this->assertTrue($policy->restore(PermissionsActor::holding(['file_statuses.restore']), $record));
        $this->assertTrue($policy->forceDelete(PermissionsActor::holding(['file_statuses.force_delete']), $record));
        $this->assertTrue($policy->archive(PermissionsActor::holding(['file_statuses.archive']), $record));
        $this->assertTrue($policy->activate(PermissionsActor::holding(['file_statuses.archive']), $record));
    }

    public function test_manage_grants_every_ability_and_view_grants_only_the_list(): void
    {
        $policy = new FileStatusPolicy;
        $record = (new FileStatus)->forceFill(['id' => 1, 'name_eu' => 'Gaia']);
        $manager = PermissionsActor::holding(['file_statuses.manage']);
        $viewer = PermissionsActor::holding(['file_statuses.view']);

        foreach (['update', 'delete', 'restore', 'forceDelete', 'archive', 'activate'] as $ability) {
            $this->assertTrue($policy->{$ability}($manager, $record), $ability);
            $this->assertFalse($policy->{$ability}($viewer, $record), $ability);
        }

        $this->assertTrue($policy->create($manager));
        $this->assertFalse($policy->create($viewer));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding(['file_types.manage'])));
    }
}

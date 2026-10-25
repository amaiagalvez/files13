<?php

namespace Files13\Tests\Unit\Policies;

use Files13\Models\FileEnding;
use PHPUnit\Framework\TestCase;
use Files13\Policies\FileEndingPolicy;
use Basics13\Support\Testing\PermissionsActor;

class FileEndingPolicyTest extends TestCase
{
    public function test_each_ability_needs_its_own_permission(): void
    {
        $policy = new FileEndingPolicy;
        $record = (new FileEnding)->forceFill(['id' => 1, 'name_eu' => 'Gaia']);

        $this->assertTrue($policy->viewAny(PermissionsActor::holding(['file_endings.view'])));
        $this->assertTrue($policy->create(PermissionsActor::holding(['file_endings.create'])));
        $this->assertTrue($policy->update(PermissionsActor::holding(['file_endings.edit']), $record));
        $this->assertTrue($policy->delete(PermissionsActor::holding(['file_endings.delete']), $record));
        $this->assertTrue($policy->restore(PermissionsActor::holding(['file_endings.restore']), $record));
        $this->assertTrue($policy->forceDelete(PermissionsActor::holding(['file_endings.force_delete']), $record));
        $this->assertTrue($policy->archive(PermissionsActor::holding(['file_endings.archive']), $record));
        $this->assertTrue($policy->activate(PermissionsActor::holding(['file_endings.archive']), $record));
    }

    public function test_manage_grants_every_ability_and_view_grants_only_the_list(): void
    {
        $policy = new FileEndingPolicy;
        $record = (new FileEnding)->forceFill(['id' => 1, 'name_eu' => 'Gaia']);
        $manager = PermissionsActor::holding(['file_endings.manage']);
        $viewer = PermissionsActor::holding(['file_endings.view']);

        foreach (['update', 'delete', 'restore', 'forceDelete', 'archive', 'activate'] as $ability) {
            $this->assertTrue($policy->{$ability}($manager, $record), $ability);
            $this->assertFalse($policy->{$ability}($viewer, $record), $ability);
        }

        $this->assertTrue($policy->create($manager));
        $this->assertFalse($policy->create($viewer));
        $this->assertFalse($policy->viewAny(PermissionsActor::holding(['file_types.manage'])));
    }
}

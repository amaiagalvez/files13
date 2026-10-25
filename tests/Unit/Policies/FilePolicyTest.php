<?php

namespace Files13\Tests\Unit\Policies;

use Files13\Models\File;
use PHPUnit\Framework\TestCase;
use Files13\Policies\FilePolicy;
use Illuminate\Database\Eloquent\Collection;
use Basics13\Support\Testing\PermissionsActor;

class FilePolicyTest extends TestCase
{
    public function test_the_global_permissions_reach_every_file(): void
    {
        $policy = new FilePolicy;
        $file = $this->file(createdBy: 99);
        $manager = PermissionsActor::holding(['files.manage']);

        foreach (['view', 'update', 'delete', 'restore', 'forceDelete', 'archive', 'activate'] as $ability) {
            $this->assertTrue($policy->{$ability}($manager, $file), $ability);
        }

        $this->assertTrue($policy->viewAll(PermissionsActor::holding(['files.view'])));
        $this->assertFalse($policy->viewAll(PermissionsActor::holding(['files.manage_own'])));
    }

    public function test_manage_own_reaches_only_the_created_or_assigned_files(): void
    {
        $policy = new FilePolicy;
        $actor = PermissionsActor::holding(['files.manage_own']);
        $id = (int) $actor->getAuthIdentifier();

        $this->assertTrue($policy->update($actor, $this->file(createdBy: $id)));
        $this->assertTrue($policy->delete($actor, $this->file(createdBy: 99, userIds: [$id])));
        $this->assertFalse($policy->update($actor, $this->file(createdBy: 99)));
        $this->assertFalse($policy->view($actor, $this->file(createdBy: 99)));
        $this->assertTrue($policy->create($actor));
        $this->assertTrue($policy->viewAny($actor));
    }

    public function test_view_own_reads_but_never_writes(): void
    {
        $policy = new FilePolicy;
        $actor = PermissionsActor::holding(['files.view_own']);
        $own = $this->file(createdBy: (int) $actor->getAuthIdentifier());

        $this->assertTrue($policy->view($actor, $own));
        $this->assertFalse($policy->update($actor, $own));
        $this->assertFalse($policy->create($actor));
    }

    /**
     * @param  list<int>  $userIds
     */
    private function file(int $createdBy, array $userIds = []): File
    {
        $file = (new File)->forceFill(['id' => 1, 'name' => 'Espedientea', 'created_by' => $createdBy]);
        $file->setRelation('users', new Collection(array_map(
            static fn (int $id): PermissionsActor => PermissionsActor::holding([], $id),
            $userIds,
        )));

        return $file;
    }
}

<?php

namespace Files13\Tests\Feature\Permissions;

use Files13\Tests\TestCase;
use Basics13\Models\Permission;
use Illuminate\Support\Facades\DB;
use Files13\Database\Seeders\FilesAccessSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FilesAccessSeedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeding_twice_duplicates_nothing(): void
    {
        $permissions = Permission::query()->count();
        $menus = DB::table('BAS_menus')->count();

        $this->seed(FilesAccessSeeder::class);

        $this->assertSame($permissions, Permission::query()->count());
        $this->assertSame($menus, DB::table('BAS_menus')->count());
    }

    public function test_every_module_and_the_own_permissions_are_seeded(): void
    {
        foreach (FilesAccessSeeder::MODULES as $module) {
            $this->assertTrue(Permission::query()->where('slug', $module.'.view')->exists(), $module);
        }

        foreach (FilesAccessSeeder::OWN_SLUGS as $slug) {
            $this->assertTrue(Permission::query()->where('slug', $slug)->exists(), $slug);
        }
    }

    public function test_every_seeded_module_is_checked_by_a_policy(): void
    {
        $policies = collect(glob(dirname(__DIR__, 3).'/src/Policies/*.php') ?: [])->map(static fn (string $path): string => (string) file_get_contents($path))->join("\n");

        foreach (FilesAccessSeeder::MODULES as $module) {
            $this->assertStringContainsString("'{$module}.", $policies, $module);
        }
    }

    public function test_every_menu_route_exists(): void
    {
        foreach (DB::table('BAS_menus')->where('package', 'files13')->whereNotNull('route')->pluck('route') as $route) {
            $this->assertTrue(app('router')->has($route), $route);
        }
    }
}

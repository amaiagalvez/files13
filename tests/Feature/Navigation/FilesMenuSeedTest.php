<?php

namespace Files13\Tests\Feature\Navigation;

use Files13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Basics13\Support\NavigationMenu;
use Files13\Database\Seeders\FilesAccessSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FilesMenuSeedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_working_lists_hang_from_the_root_and_the_catalogs_from_the_auxiliary_subgroup(): void
    {
        $this->assertSame(
            [
                'documents' => 'files-auxiliary',
                'file-endings' => 'files-auxiliary',
                'file-locations' => 'files-auxiliary',
                'file-phases' => 'files13',
                'file-statuses' => 'files-auxiliary',
                'file-subjects' => 'files-auxiliary',
                'file-types' => 'files-auxiliary',
                'files' => 'files13',
                'files-auxiliary' => 'files13',
                'files-calendar' => 'files13',
                'files-home' => 'files13',
                'files-mine' => 'files13',
                'phases' => 'files-auxiliary',
            ],
            DB::table('BAS_menus')->where('package', 'files13')->whereNotNull('parent_key')->orderBy('key')->pluck('parent_key', 'key')->all(),
        );
    }

    public function test_an_account_that_only_views_files_does_not_see_the_auxiliary_subgroup(): void
    {
        $group = collect(NavigationMenu::for($this->holding(['files.view'])))->firstWhere('item.key', 'files13');

        $this->assertNotNull($group);
        $this->assertNotContains('files-auxiliary', array_column(array_column($group['children'], 'item'), 'key'));
    }

    public function test_reseeding_drops_the_items_it_no_longer_lists(): void
    {
        DB::table('BAS_menus')->insert(['key' => 'files-stale', 'package' => 'files13', 'label' => 'Stale', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()]);

        $this->seed(FilesAccessSeeder::class);

        $this->assertFalse(DB::table('BAS_menus')->where('key', 'files-stale')->exists());
    }
}

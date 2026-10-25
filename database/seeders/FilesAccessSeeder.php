<?php

namespace Files13\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The permissions the files13 policies check and the files group of the sidebar. `admin` gets full
 * access to every module. Runs after the basics13 access seeder, which creates `admin`; running it
 * again duplicates nothing and takes no permission away from a role.
 */
class FilesAccessSeeder extends Seeder
{
    /** @var list<string> */
    public const MODULES = ['file_subjects', 'file_types', 'file_statuses', 'file_endings', 'file_locations', 'file_phases'];

    /**
     * @var list<array{key: string, label: string, sort: int, parent?: string, icon?: string, route?: string, active?: string, except?: string, permissions?: list<string>}>
     */
    private const MENUS = [
        ['key' => 'files13', 'label' => 'Files', 'sort' => 200],
        ['key' => 'file-subjects', 'parent' => 'files13', 'label' => 'Subjects', 'sort' => 110, 'icon' => 'tag', 'route' => 'file-subjects.index', 'active' => 'file-subjects.*', 'permissions' => ['file_subjects.view']],
        ['key' => 'file-types', 'parent' => 'files13', 'label' => 'Types', 'sort' => 120, 'icon' => 'squares-2x2', 'route' => 'file-types.index', 'active' => 'file-types.*', 'permissions' => ['file_types.view']],
        ['key' => 'file-statuses', 'parent' => 'files13', 'label' => 'Statuses', 'sort' => 130, 'icon' => 'signal', 'route' => 'file-statuses.index', 'active' => 'file-statuses.*', 'permissions' => ['file_statuses.view']],
        ['key' => 'file-endings', 'parent' => 'files13', 'label' => 'Endings', 'sort' => 140, 'icon' => 'flag', 'route' => 'file-endings.index', 'active' => 'file-endings.*', 'permissions' => ['file_endings.view']],
        ['key' => 'file-locations', 'parent' => 'files13', 'label' => 'Locations', 'sort' => 150, 'icon' => 'calendar-days', 'route' => 'file-locations.index', 'active' => 'file-locations.*', 'permissions' => ['file_locations.view']],
        ['key' => 'phases', 'parent' => 'files13', 'label' => 'Phases', 'sort' => 100, 'icon' => 'queue-list', 'route' => 'phases.index', 'active' => 'phases.*', 'permissions' => ['file_phases.view']],
    ];

    public function run(): void
    {
        seedPermissions(self::MODULES, array_map(static fn (string $module): string => $module.'.manage', self::MODULES));
        seedMenus('files13', self::MENUS);
    }
}

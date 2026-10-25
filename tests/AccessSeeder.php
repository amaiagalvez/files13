<?php

namespace Files13\Tests;

use Illuminate\Database\Seeder;
use Files13\Database\Seeders\FilesAccessSeeder;
use Basics13\Database\Seeders\BasicsAccessSeeder;

/**
 * What `db:seed` gives the application for this package: basics13 first, then files13.
 */
class AccessSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([BasicsAccessSeeder::class, FilesAccessSeeder::class]);
    }
}

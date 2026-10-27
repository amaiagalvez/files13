<?php

namespace Files13\Tests\Feature\Documents;

use Files13\Models\Folder;
use Files13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class FolderNamedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_new_name_creates_the_folder_and_an_existing_one_is_reused(): void
    {
        $folder = Folder::named('  Contracts  ');

        $this->assertNotNull($folder);
        $this->assertSame('Contracts', $folder->name);
        $this->assertSame($folder->id, Folder::named('Contracts')?->id);
        $this->assertSame(1, Folder::query()->count());
    }

    public function test_an_empty_name_means_no_folder(): void
    {
        $this->assertNull(Folder::named(null));
        $this->assertNull(Folder::named('  '));
        $this->assertNull(Folder::named('--'));
        $this->assertSame(0, Folder::query()->count());
    }

    /**
     * Another request inserts the same folder while this one is creating it: the unique index
     * rejects the second insert and the folder the other request wrote is returned.
     */
    public function test_a_folder_created_concurrently_is_returned_instead_of_failing(): void
    {
        $raced = false;
        Folder::creating(static function (Folder $folder) use (&$raced): void {
            if (! $raced) {
                $raced = true;
                DB::table('FIL_folders')->insert(['name' => $folder->name, 'created_at' => now(), 'updated_at' => now()]);
            }
        });

        $folder = Folder::named('Invoices');

        $this->assertNotNull($folder);
        $this->assertSame(1, Folder::query()->where('name', 'Invoices')->count());
        $this->assertSame(Folder::query()->where('name', 'Invoices')->value('id'), $folder->id);
    }
}

<?php

namespace Files13\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

/**
 * A folder documents of files are placed in. Folders are shared by every file and found by name;
 * the application never renames or deletes them.
 *
 * @property int $id
 * @property string $name
 */
#[Fillable(['name'])]
class Folder extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'FIL_folders';

    /**
     * The folder with this name, created when no folder has it yet. An empty name means no folder.
     */
    public static function named(?string $name): ?self
    {
        $name = trim((string) $name);

        if ($name === '' || $name === '--') {
            return null;
        }

        // Insert first: a concurrent request that created the same folder ends in a read, not a unique violation.
        return self::query()->createOrFirst(['name' => $name]);
    }
}

<?php

namespace Files13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

/**
 * One entry of the change log of a file: the values the changed columns had before the change.
 *
 * @property int $id
 * @property int $file_id
 * @property array<string, mixed> $previous
 * @property int|null $changed_by
 * @property-read CarbonImmutable|null $created_at
 */
#[Fillable(['previous', 'changed_by'])]
class FileChange extends Model
{
    public const UPDATED_AT = null;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'FIL_file_changes';

    /**
     * @return array{previous: 'array'}
     */
    protected function casts(): array
    {
        return [
            'previous' => 'array',
        ];
    }
}

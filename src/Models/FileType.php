<?php

namespace Files13\Models;

use Carbon\CarbonImmutable;
use Files13\Policies\FileTypePolicy;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\SoftDeletes;
use Files13\Database\Factories\FileTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;

/**
 * The kind of a file (mota). A type is meant for files of companies, of people or of both, and
 * brings its phases to every file created with it.
 *
 * @property int $id
 * @property string $name_eu
 * @property string|null $name_es
 * @property string|null $name_fr
 * @property string|null $name_en
 * @property bool $company
 * @property bool $person
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read CarbonImmutable|null $deleted_at
 */
#[Fillable(['name_eu', 'name_es', 'name_fr', 'name_en', 'company', 'person', 'notes'])]
#[UsePolicy(FileTypePolicy::class)]
#[UseFactory(FileTypeFactory::class)]
class FileType extends Model
{
    /** @use HasFactory<FileTypeFactory> */
    use HasFactory, SoftDeletes, TracksAuditColumns, TransactionalDelete;

    /**
     * Language columns for the name.
     *
     * @var list<string>
     */
    public const NAME_FIELDS = ['name_eu', 'name_es', 'name_fr', 'name_en'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'FIL_types';

    /**
     * @var array{active: bool, company: bool, person: bool}
     */
    protected $attributes = [
        'active' => true,
        'company' => false,
        'person' => false,
    ];

    /**
     * @return array{active: 'boolean', company: 'boolean', person: 'boolean'}
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'company' => 'boolean',
            'person' => 'boolean',
        ];
    }
}

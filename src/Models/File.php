<?php

namespace Files13\Models;

use Carbon\CarbonImmutable;
use Files13\Policies\FilePolicy;
use Locations13\Models\District;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\ResolvesUserModel;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Files13\Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A case file (espedientea): what the package is about. It has a type, may have a status,
 * subject, ending, location calendar and district, goes through phases, is assigned to users and
 * may have applicants of any model of the host.
 *
 * @property int $id
 * @property string $name
 * @property string|null $number
 * @property bool $impugn
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 * @property int $type_id
 * @property int|null $status_id
 * @property int|null $subject_id
 * @property int|null $ending_id
 * @property int|null $location_id
 * @property int|null $district_id
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read CarbonImmutable|null $deleted_at
 * @property-read FileType $type
 * @property-read FileStatus|null $status
 * @property-read FileSubject|null $subject
 * @property-read FileEnding|null $ending
 * @property-read FileLocation|null $location
 * @property-read District|null $district
 * @property-read Collection<int, Model> $users
 * @property-read Collection<int, FilePhase> $filePhases
 * @property-read Collection<int, FileApplicant> $applicants
 * @property-read Collection<int, FileChange> $changes
 * @property-read Collection<int, Document> $documents
 */
#[Fillable(['name', 'number', 'impugn', 'start_date', 'end_date', 'type_id', 'status_id', 'subject_id', 'ending_id', 'location_id', 'district_id', 'notes'])]
#[UsePolicy(FilePolicy::class)]
#[UseFactory(FileFactory::class)]
class File extends Model
{
    /** @use HasFactory<FileFactory> */
    use HasFactory, ResolvesUserModel, SoftDeletes, TracksAuditColumns, TransactionalDelete;

    /**
     * The columns the change log ignores: who touched the row and when, and the archive flag.
     *
     * @var list<string>
     */
    public const UNLOGGED_COLUMNS = ['created_by', 'updated_by', 'deleted_by', 'created_at', 'updated_at', 'deleted_at', 'active'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'FIL_files';

    /**
     * @var array{active: bool, impugn: bool}
     */
    protected $attributes = [
        'active' => true,
        'impugn' => false,
    ];

    /**
     * @return array{active: 'boolean', impugn: 'boolean', start_date: 'immutable_date', end_date: 'immutable_date'}
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'impugn' => 'boolean',
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
        ];
    }

    /**
     * Whether the account created the file or is one of its users: what makes a file its own.
     */
    public function belongsToUser(int $userId): bool
    {
        if ($this->created_by === $userId) {
            return true;
        }

        return $this->relationLoaded('users')
            ? $this->users->contains(static fn (Model $user): bool => (int) $user->getKey() === $userId)
            : $this->users()->whereKey($userId)->exists();
    }

    /**
     * @return BelongsTo<FileType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(FileType::class, 'type_id')->withTrashed();
    }

    /**
     * @return BelongsTo<FileStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(FileStatus::class, 'status_id')->withTrashed();
    }

    /**
     * @return BelongsTo<FileSubject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(FileSubject::class, 'subject_id')->withTrashed();
    }

    /**
     * @return BelongsTo<FileEnding, $this>
     */
    public function ending(): BelongsTo
    {
        return $this->belongsTo(FileEnding::class, 'ending_id')->withTrashed();
    }

    /**
     * @return BelongsTo<FileLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(FileLocation::class, 'location_id')->withTrashed();
    }

    /**
     * @return BelongsTo<District, $this>
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id')->withTrashed();
    }

    /**
     * The accounts the file is assigned to, over the model the host configures.
     *
     * @return BelongsToMany<Model, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany($this->userModelClass(), 'FIL_file_user', 'file_id', 'user_id')->withTimestamps();
    }

    /**
     * The phases of the file, in the order of their phase.
     *
     * @return HasMany<FilePhase, $this>
     */
    public function filePhases(): HasMany
    {
        return $this->hasMany(FilePhase::class, 'file_id')
            ->join('FIL_phases', 'FIL_phases.id', '=', 'FIL_file_phase.phase_id')
            ->orderBy('FIL_phases.order')
            ->orderBy('FIL_file_phase.id')
            ->select('FIL_file_phase.*');
    }

    /**
     * @return HasMany<FileApplicant, $this>
     */
    public function applicants(): HasMany
    {
        return $this->hasMany(FileApplicant::class, 'file_id');
    }

    /**
     * The documents of the file, with the folder each one is placed in.
     *
     * @return BelongsToMany<Document, $this>
     */
    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'FIL_file_document', 'file_id', 'document_id')
            ->withPivot('folder_id')
            ->withTimestamps();
    }

    /**
     * The change log, the latest change first.
     *
     * @return HasMany<FileChange, $this>
     */
    public function changes(): HasMany
    {
        return $this->hasMany(FileChange::class, 'file_id')->latest('id');
    }
}

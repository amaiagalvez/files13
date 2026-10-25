<?php

namespace Files13\Models;

use Carbon\CarbonImmutable;
use Files13\Policies\PhasePolicy;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Files13\Database\Factories\PhaseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A step a file goes through (fasea). Phases are ordered, belong to the file types that use them
 * and may carry subjects whose alert days set the deadline of the step.
 *
 * @property int $id
 * @property string $name_eu
 * @property string|null $name_es
 * @property string|null $name_fr
 * @property string|null $name_en
 * @property int $order
 * @property string|null $color
 * @property bool $show_calendar
 * @property bool $accumulate_days
 * @property int|null $accumulate_days_order
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read CarbonImmutable|null $deleted_at
 * @property-read Collection<int, FileType> $types
 * @property-read Collection<int, FileSubject> $subjects
 */
#[Fillable(['name_eu', 'name_es', 'name_fr', 'name_en', 'order', 'color', 'show_calendar', 'accumulate_days', 'accumulate_days_order', 'notes'])]
#[UsePolicy(PhasePolicy::class)]
#[UseFactory(PhaseFactory::class)]
class Phase extends Model
{
    /** @use HasFactory<PhaseFactory> */
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
    protected $table = 'FIL_phases';

    /**
     * The units an alert of a phase subject is counted in.
     *
     * @var list<string>
     */
    public const ALERT_UNITS = ['days', 'year'];

    /**
     * @var array{active: bool, order: int, show_calendar: bool, accumulate_days: bool}
     */
    protected $attributes = [
        'active' => true,
        'order' => 0,
        'show_calendar' => false,
        'accumulate_days' => false,
    ];

    /**
     * @return array{active: 'boolean', order: 'integer', show_calendar: 'boolean', accumulate_days: 'boolean', accumulate_days_order: 'integer'}
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'order' => 'integer',
            'show_calendar' => 'boolean',
            'accumulate_days' => 'boolean',
            'accumulate_days_order' => 'integer',
        ];
    }

    /**
     * Whether the phase counts its alert days towards a deadline: only the accumulating phases of
     * order 1 and 2 do.
     */
    public function accumulatesDays(): bool
    {
        return $this->accumulate_days && in_array($this->accumulate_days_order, [1, 2], true);
    }

    /**
     * @return BelongsToMany<FileType, $this>
     */
    public function types(): BelongsToMany
    {
        return $this->belongsToMany(FileType::class, 'FIL_phase_type', 'phase_id', 'type_id')->withTimestamps();
    }

    /**
     * @return BelongsToMany<FileSubject, $this>
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(FileSubject::class, 'FIL_phase_subject', 'phase_id', 'subject_id')
            ->withPivot(['alert', 'alert_unit'])
            ->withTimestamps();
    }
}

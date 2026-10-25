<?php

namespace Files13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A named day of a location calendar. It has no list, trash or permissions of its own: it is
 * edited from the form of its location and goes away with it.
 *
 * @property int $id
 * @property int $location_id
 * @property CarbonImmutable $date
 * @property string $name
 */
#[Fillable(['date', 'name'])]
class LocationDay extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'FIL_location_days';

    /**
     * @return array{date: 'immutable_date'}
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
        ];
    }

    /**
     * @return BelongsTo<FileLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(FileLocation::class, 'location_id');
    }
}

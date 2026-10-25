<?php

namespace Files13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Basics13\Concerns\ResolvesUserModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A phase as a file goes through it: who is responsible, when it started and ended, and the alert
 * date and pending days of an accumulating phase. It is edited from the file form; FilePhaseObserver
 * keeps the dates of the file's phases consistent with each other.
 *
 * @property int $id
 * @property int $file_id
 * @property int $phase_id
 * @property int|null $user_id
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 * @property CarbonImmutable|null $alert_date
 * @property int|null $accumulate_pending_days
 * @property string|null $notes
 * @property-read File $file
 * @property-read Phase $phase
 * @property-read Model|null $responsible
 */
#[Fillable(['phase_id', 'user_id', 'start_date', 'end_date', 'notes'])]
class FilePhase extends Model
{
    use ResolvesUserModel;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'FIL_file_phase';

    /**
     * @return array{start_date: 'immutable_date', end_date: 'immutable_date', alert_date: 'immutable_date', accumulate_pending_days: 'integer'}
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'alert_date' => 'immutable_date',
            'accumulate_pending_days' => 'integer',
        ];
    }

    /**
     * The alert of a phase: the days of the first of its subjects with an alert above zero, or zero
     * when none has one.
     *
     * @param  iterable<int, int>  $subjectAlerts  The alerts of the phase subjects, in their order
     */
    public static function alertOf(iterable $subjectAlerts): int
    {
        foreach ($subjectAlerts as $alert) {
            if ($alert > 0) {
                return $alert;
            }
        }

        return 0;
    }

    /**
     * The alert date of a phase that starts on the given day: the start plus the alert, or none
     * without a start.
     */
    public static function alertDateFor(?CarbonImmutable $start, int $alert): ?CarbonImmutable
    {
        return $start?->addDays($alert);
    }

    /**
     * The pending days of a phase once it ended: the alert minus the calendar days between start and
     * end, never below zero; none until the phase has an end.
     */
    public static function pendingDaysFor(?CarbonImmutable $start, ?CarbonImmutable $end, int $alert): ?int
    {
        if ($end === null) {
            return null;
        }

        $elapsed = $start === null ? 0 : (int) $start->diffInDays($end);

        return max(0, $alert - $elapsed);
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Phase, $this>
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class, 'phase_id')->withTrashed();
    }

    /**
     * The account responsible for the phase, over the model the host configures.
     *
     * @return BelongsTo<Model, $this>
     */
    public function responsible(): BelongsTo
    {
        return $this->belongsTo($this->userModelClass(), 'user_id');
    }
}

<?php

namespace Files13\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An applicant of a file: a record of any model the host registers in its morph map. files13 never
 * names those models; the type is the morph key the host chose.
 *
 * @property int $id
 * @property int $file_id
 * @property string $applicant_type
 * @property int $applicant_id
 * @property-read Model|null $applicant
 */
#[Fillable(['applicant_type', 'applicant_id'])]
class FileApplicant extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'FIL_file_applicant';

    /**
     * @return MorphTo<Model, $this>
     */
    public function applicant(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }
}

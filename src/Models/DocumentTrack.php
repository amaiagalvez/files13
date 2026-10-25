<?php

namespace Files13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

/**
 * What happened to a document and who did it. A track outlives its document, so it keeps the name.
 *
 * @property int $id
 * @property int|null $document_id
 * @property string $document_name
 * @property int|null $user_id
 * @property string $action
 * @property-read CarbonImmutable|null $created_at
 */
#[Fillable(['document_id', 'document_name', 'user_id', 'action'])]
class DocumentTrack extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    public const ACTIONS = ['create', 'read', 'write', 'delete', 'restore', 'destroy'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'FIL_document_tracks';

    /**
     * Record an action on a document, by the signed-in user unless another one is given.
     */
    public static function record(Document $document, string $action, ?int $userId = null): self
    {
        return self::query()->create([
            'document_id' => $document->exists ? $document->id : null,
            'document_name' => $document->name,
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
        ]);
    }
}

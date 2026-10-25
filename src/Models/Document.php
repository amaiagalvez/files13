<?php

namespace Files13\Models;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Files13\Policies\DocumentPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Basics13\Concerns\TracksAuditColumns;
use Basics13\Concerns\TransactionalDelete;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Files13\Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A document (dokumentua): a template, or a document of a file, copied from a template or
 * uploaded. Its content lives on the private disk of the package under a generated name, and is
 * edited with OnlyOffice.
 *
 * @property int $id
 * @property string $name
 * @property bool $template
 * @property int|null $parent_id
 * @property string|null $path
 * @property string|null $extension
 * @property string|null $mime
 * @property int|null $size
 * @property string|null $key
 * @property bool $editing
 * @property string|null $notes
 * @property bool $active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read CarbonImmutable|null $created_at
 * @property-read CarbonImmutable|null $updated_at
 * @property-read CarbonImmutable|null $deleted_at
 * @property-read Document|null $parent
 * @property-read Collection<int, FileSubject> $subjects
 * @property-read Collection<int, File> $files
 * @property-read Collection<int, DocumentTrack> $tracks
 */
#[Fillable(['name', 'notes'])]
#[UsePolicy(DocumentPolicy::class)]
#[UseFactory(DocumentFactory::class)]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory, SoftDeletes, TracksAuditColumns, TransactionalDelete;

    /**
     * The formats OnlyOffice edits or shows that may be uploaded. The web formats it reads (html,
     * mht, xml) are left out: a stored page could run in the browser of whoever downloads it.
     *
     * @var list<string>
     */
    public const EXTENSIONS = [
        'doc', 'docm', 'docx', 'dot', 'dotm', 'dotx', 'epub', 'fodt', 'odt', 'ott', 'rtf', 'txt',
        'csv', 'fods', 'ods', 'ots', 'xls', 'xlsb', 'xlsm', 'xlsx', 'xlt', 'xltm', 'xltx',
        'fodp', 'odp', 'otp', 'pot', 'potm', 'potx', 'pps', 'ppsm', 'ppsx', 'ppt', 'pptm', 'pptx', 'pdf',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'FIL_documents';

    /**
     * @var array{active: bool, template: bool, editing: bool}
     */
    protected $attributes = [
        'active' => true,
        'template' => false,
        'editing' => false,
    ];

    /**
     * @return array{active: 'boolean', template: 'boolean', editing: 'boolean', size: 'integer'}
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'template' => 'boolean',
            'editing' => 'boolean',
            'size' => 'integer',
        ];
    }

    /**
     * Store an uploaded file as the content of the document, under a generated name.
     */
    public function storeUpload(UploadedFile $upload): void
    {
        $this->forceFill([
            'path' => $upload->store('files13', ['disk' => self::disk()]),
            'extension' => strtolower($upload->getClientOriginalExtension()),
            'mime' => $upload->getMimeType(),
            'size' => $upload->getSize(),
            'key' => Str::random(40),
        ]);
    }

    /**
     * A new document of a file, copied from this template. A template without content gives a blank
     * document.
     */
    public function copyFromTemplate(): self
    {
        $copy = new self(['name' => $this->name]);
        $copy->forceFill([
            'parent_id' => $this->id,
            'extension' => $this->extension,
            'mime' => $this->mime,
            'size' => $this->size,
            'key' => Str::random(40),
        ]);

        if ($this->path !== null && Storage::disk(self::disk())->exists($this->path)) {
            $path = 'files13/'.Str::random(40).($this->extension ? '.'.$this->extension : '');
            Storage::disk(self::disk())->copy($this->path, $path);
            $copy->path = $path;
        }

        return $copy;
    }

    /**
     * Remove the stored content, when there is any.
     */
    public function deleteContent(): void
    {
        if ($this->path !== null) {
            Storage::disk(self::disk())->delete($this->path);
        }
    }

    public static function disk(): string
    {
        return (string) config('files13.disk');
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id')->withTrashed();
    }

    /**
     * The copies made from this template.
     *
     * @return HasMany<Document, $this>
     */
    public function copies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsToMany<FileSubject, $this>
     */
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(FileSubject::class, 'FIL_document_subject', 'document_id', 'subject_id')->withTimestamps();
    }

    /**
     * @return BelongsToMany<File, $this>
     */
    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'FIL_file_document', 'document_id', 'file_id')
            ->withPivot('folder_id')
            ->withTimestamps();
    }

    /**
     * @return HasMany<DocumentTrack, $this>
     */
    public function tracks(): HasMany
    {
        return $this->hasMany(DocumentTrack::class, 'document_id')->latest('id');
    }
}

<?php

namespace Files13\Http\Requests;

use Files13\Models\Document;
use Files13\Models\FileSubject;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * A document template: its name, the subjects it is for and, optionally, its content.
 */
class DocumentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $notes = $this->input('notes');

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'notes' => is_string($notes) && trim($notes) === '' ? null : $notes,
            'subject_ids' => array_values(array_filter((array) $this->input('subject_ids', []), static fn (mixed $id): bool => $id !== '' && $id !== null)),
        ]);
    }

    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document
            ? ($this->user()?->can('update', $document) ?? false)
            : ($this->user()?->can('create', Document::class) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:'.MaxLength::string()],
            'content' => ['nullable', 'file', 'max:'.(int) config('files13.max_upload_kb'), 'extensions:'.implode(',', Document::EXTENSIONS)],
            'subject_ids' => ['array'],
            'subject_ids.*' => ['integer', 'distinct', $this->selectableSubject()],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
        ];
    }

    private function selectableSubject(): Exists
    {
        $document = $this->route('document');
        $assigned = $document instanceof Document
            ? $document->subjects()->pluck('FIL_subjects.id')->map(static fn (mixed $id): int => (int) $id)->values()->all()
            : [];

        return Rule::exists(FileSubject::class, 'id')
            ->whereNull('deleted_at')
            ->where(static function (QueryBuilder $query) use ($assigned): void {
                $query->where('active', true);

                if ($assigned !== []) {
                    $query->orWhereIn('id', $assigned);
                }
            });
    }
}

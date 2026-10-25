<?php

namespace Files13\Http\Requests;

use Files13\Models\Phase;
use Files13\Models\FileType;
use Files13\Models\FileSubject;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\Builder as QueryBuilder;

class PhaseRequest extends FormRequest
{
    /**
     * Trim every name, and turn a blank translation into an absent one: a language with no name is
     * stored as NULL so the unique index of that column does not see an empty string as a value.
     */
    protected function prepareForValidation(): void
    {
        $names = [];

        foreach (Phase::NAME_FIELDS as $field) {
            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);
            $names[$field] = $value === '' && $field !== 'name_eu' ? null : $value;
        }

        $color = $this->input('color');

        // A checkbox left unticked sends nothing, so an absent flag is a false one.
        $this->merge([
            ...$names,
            'color' => is_string($color) && trim($color) !== '' ? trim($color) : null,
            'show_calendar' => $this->boolean('show_calendar'),
            'accumulate_days' => $this->boolean('accumulate_days'),
            'accumulate_days_order' => $this->filled('accumulate_days_order') ? $this->input('accumulate_days_order') : null,
            'type_ids' => array_values(array_filter((array) $this->input('type_ids', []), static fn (mixed $id): bool => $id !== '' && $id !== null)),
            'subjects' => array_values(array_filter((array) $this->input('subjects', []), static fn (mixed $row): bool => ! is_array($row) || ($row['subject_id'] ?? '') !== '')),
        ]);
    }

    public function authorize(): bool
    {
        $phase = $this->route('phase');

        return $phase
            ? ($this->user()?->can('update', $phase) ?? false)
            : ($this->user()?->can('create', Phase::class) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name_eu' => [
                'required',
                'string',
                'min:2',
                'max:'.MaxLength::string(),
                $this->uniqueName('name_eu'),
            ],
            'name_es' => ['nullable', 'string', 'max:'.MaxLength::string(), $this->uniqueName('name_es')],
            'name_fr' => ['nullable', 'string', 'max:'.MaxLength::string(), $this->uniqueName('name_fr')],
            'name_en' => ['nullable', 'string', 'max:'.MaxLength::string(), $this->uniqueName('name_en')],
            'order' => ['required', 'integer', 'min:0'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'show_calendar' => ['boolean'],
            'accumulate_days' => ['boolean'],
            'accumulate_days_order' => ['nullable', 'integer', 'min:0'],
            'type_ids' => ['array'],
            'type_ids.*' => ['integer', 'distinct', $this->selectable(FileType::class, $this->assignedTypeIds())],
            'subjects' => ['array'],
            'subjects.*' => ['array:subject_id,alert,alert_unit'],
            'subjects.*.subject_id' => ['required', 'integer', 'distinct', $this->selectable(FileSubject::class, $this->assignedSubjectIds())],
            'subjects.*.alert' => ['required', 'integer', 'min:0'],
            'subjects.*.alert_unit' => ['required', Rule::in(Phase::ALERT_UNITS)],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The name has to be free among the records that are not in the trash, in its own language only.
     * A name taken by a trashed record can be used again, which is what the create form offers to
     * restore instead.
     */
    private function uniqueName(string $column): Unique
    {
        return Rule::unique(Phase::class, $column)
            ->ignore($this->route('phase'))
            ->whereNull('deleted_at');
    }

    /**
     * A type or subject is chosen among the active ones; the ones the phase already has stay valid
     * after they have been archived.
     *
     * @param  class-string<Model>  $model
     * @param  array<int, int>  $assigned
     */
    private function selectable(string $model, array $assigned): Exists
    {
        return Rule::exists($model, 'id')
            ->whereNull('deleted_at')
            ->where(static function (QueryBuilder $query) use ($assigned): void {
                $query->where('active', true);

                if ($assigned !== []) {
                    $query->orWhereIn('id', $assigned);
                }
            });
    }

    /**
     * @return array<int, int>
     */
    private function assignedTypeIds(): array
    {
        $phase = $this->route('phase');

        return $phase instanceof Phase ? $phase->types()->pluck('FIL_types.id')->map(static fn (mixed $id): int => (int) $id)->values()->all() : [];
    }

    /**
     * @return array<int, int>
     */
    private function assignedSubjectIds(): array
    {
        $phase = $this->route('phase');

        return $phase instanceof Phase ? $phase->subjects()->pluck('FIL_subjects.id')->map(static fn (mixed $id): int => (int) $id)->values()->all() : [];
    }
}

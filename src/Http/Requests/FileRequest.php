<?php

namespace Files13\Http\Requests;

use Files13\Models\File;
use Files13\Models\FileType;
use Files13\Models\FileEnding;
use Files13\Models\FileStatus;
use Files13\Models\FileSubject;
use Illuminate\Validation\Rule;
use Files13\Models\FileLocation;
use Locations13\Models\District;
use Illuminate\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Exists;
use Basics13\Concerns\ResolvesUserModel;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

class FileRequest extends FormRequest
{
    use ResolvesUserModel;

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'type_id' => __('Type'),
            'status_id' => __('Status'),
            'subject_id' => __('Subject'),
            'ending_id' => __('Ending'),
            'location_id' => __('Location calendar'),
            'district_id' => __('District'),
        ];
    }

    /**
     * Trim the texts, turn the blank optional fields into absent ones, and drop the rows the form
     * left empty.
     */
    protected function prepareForValidation(): void
    {
        $merged = ['impugn' => $this->boolean('impugn')];

        foreach (['name', 'number', 'notes'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $value = trim($value);
                $merged[$field] = $value === '' && $field !== 'name' ? null : $value;
            }
        }

        foreach (['start_date', 'end_date', 'status_id', 'subject_id', 'ending_id', 'location_id', 'district_id'] as $field) {
            if ($this->input($field) === '') {
                $merged[$field] = null;
            }
        }

        foreach (['user_ids', 'applicants'] as $list) {
            if ($this->has($list)) {
                $merged[$list] = array_values(array_filter(
                    (array) $this->input($list),
                    static fn (mixed $row): bool => is_array($row)
                        ? array_filter($row, static fn (mixed $value): bool => $value !== null && $value !== '') !== []
                        : $row !== null && $row !== '',
                ));
            }
        }

        $this->merge($merged);
    }

    public function authorize(): bool
    {
        $file = $this->route('file');

        return $file
            ? ($this->user()?->can('update', $file) ?? false)
            : ($this->user()?->can('create', File::class) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:'.MaxLength::string(),
                Rule::unique(File::class)->ignore($this->route('file'))->whereNull('deleted_at'),
            ],
            'number' => ['nullable', 'string', 'max:'.MaxLength::string()],
            'impugn' => ['boolean'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'type_id' => ['required', 'integer', $this->selectable(FileType::class, 'type_id')],
            'status_id' => ['nullable', 'integer', $this->selectable(FileStatus::class, 'status_id')],
            'subject_id' => ['nullable', 'integer', $this->selectable(FileSubject::class, 'subject_id')],
            'ending_id' => ['nullable', 'integer', $this->selectable(FileEnding::class, 'ending_id')],
            'location_id' => ['nullable', 'integer', $this->selectable(FileLocation::class, 'location_id')],
            'district_id' => ['nullable', 'integer', $this->selectable(District::class, 'district_id')],
            'user_ids' => ['sometimes', 'array'],
            'user_ids.*' => ['integer', 'distinct', Rule::exists($this->userModelClass(), 'id')->whereNull('deleted_at')],
            'applicants' => ['sometimes', 'array'],
            'applicants.*' => ['array:applicant_type,applicant_id'],
            'applicants.*.applicant_type' => ['required', 'string', Rule::in(array_keys(Relation::morphMap()))],
            'applicants.*.applicant_id' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The rules that look at several rows together: an applicant appears once, and a user who is
     * responsible for one of the phases of the file stays assigned to it.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $seen = [];

                foreach ((array) $this->input('applicants', []) as $index => $applicant) {
                    $key = is_array($applicant) ? ($applicant['applicant_type'] ?? '').'#'.($applicant['applicant_id'] ?? '') : '';

                    if (isset($seen[$key])) {
                        $validator->errors()->add("applicants.{$index}.applicant_id", __('validation.distinct', ['attribute' => __('Applicant')]));
                    }

                    $seen[$key] = true;
                }
            },
            function (Validator $validator): void {
                $file = $this->route('file');

                if (! $file instanceof File || ! $this->has('user_ids')) {
                    return;
                }

                $kept = array_map('intval', (array) $this->input('user_ids', []));
                $removed = array_diff($file->users()->pluck('user_id')->map(static fn (mixed $id): int => (int) $id)->all(), $kept);

                if ($removed === []) {
                    return;
                }

                $responsible = $file->filePhases()->whereIn('FIL_file_phase.user_id', $removed)->exists();

                if ($responsible) {
                    $validator->errors()->add('user_ids', __('A user responsible for a phase of the file cannot be removed from it.'));
                }
            },
        ];
    }

    /**
     * A catalogue entry is chosen among the active ones; the one the file already has stays valid
     * after it has been archived.
     *
     * @param  class-string<Model>  $model
     */
    private function selectable(string $model, string $column): Exists
    {
        $file = $this->route('file');
        $assigned = $file instanceof File ? $file->getAttribute($column) : null;

        return Rule::exists($model, 'id')
            ->whereNull('deleted_at')
            ->where(static function (QueryBuilder $query) use ($assigned): void {
                $query->where('active', true);

                if (is_int($assigned)) {
                    $query->orWhere('id', $assigned);
                }
            });
    }
}

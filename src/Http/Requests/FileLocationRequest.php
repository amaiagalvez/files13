<?php

namespace Files13\Http\Requests;

use Files13\Models\LocationDay;
use Illuminate\Validation\Rule;
use Files13\Models\FileLocation;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;

class FileLocationRequest extends FormRequest
{
    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['days.*.date' => __('Date'), 'days.*.name' => __('Name'), 'days.*.id' => __('Days')];
    }

    /**
     * Trim every name, and turn a blank translation into an absent one: a language with no name is
     * stored as NULL so the unique index of that column does not see an empty string as a value.
     */
    protected function prepareForValidation(): void
    {
        $names = [];

        foreach (FileLocation::NAME_FIELDS as $field) {
            $value = $this->input($field);

            if (! is_string($value)) {
                continue;
            }

            $value = trim($value);
            $names[$field] = $value === '' && $field !== 'name_eu' ? null : $value;
        }

        $days = $this->input('days');

        // Rows left completely empty in the form are dropped instead of failing validation.
        if (is_array($days)) {
            $days = array_values(array_filter(
                array_map(static fn (mixed $day): mixed => is_array($day)
                    ? [...$day, 'name' => is_string($day['name'] ?? null) ? trim($day['name']) : ($day['name'] ?? null)]
                    : $day, $days),
                static fn (mixed $day): bool => ! is_array($day) || ($day['date'] ?? '') !== '' || ($day['name'] ?? '') !== '',
            ));
        }

        $this->merge([...$names, 'days' => $days]);
    }

    public function authorize(): bool
    {
        $fileLocation = $this->route('file_location');

        return $fileLocation
            ? ($this->user()?->can('update', $fileLocation) ?? false)
            : ($this->user()?->can('create', FileLocation::class) ?? false);
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
            'days' => ['nullable', 'array'],
            'days.*' => ['array:id,date,name'],
            'days.*.id' => ['nullable', 'integer', $this->ownDayRule()],
            'days.*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
            'days.*.name' => ['required', 'string', 'max:'.MaxLength::string()],
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
        return Rule::unique(FileLocation::class, $column)
            ->ignore($this->route('file_location'))
            ->whereNull('deleted_at');
    }

    /**
     * A day row that names an id has to be a day of this location: a new location has none, so any
     * id it sends is refused.
     */
    private function ownDayRule(): Exists
    {
        $location = $this->route('file_location');

        return Rule::exists(LocationDay::class, 'id')
            ->where('location_id', $location instanceof FileLocation ? $location->getKey() : 0);
    }
}

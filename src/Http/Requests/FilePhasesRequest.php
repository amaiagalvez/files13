<?php

namespace Files13\Http\Requests;

use Files13\Models\File;
use Files13\Models\Phase;
use Files13\Models\FilePhase;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Basics13\Concerns\ResolvesUserModel;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * The phases of a file, edited from the file sheet: who is responsible, the dates and the notes of
 * each phase, and which phases the file goes through.
 */
class FilePhasesRequest extends FormRequest
{
    use ResolvesUserModel;

    protected function prepareForValidation(): void
    {
        $rows = array_values(array_filter(
            (array) $this->input('phases', []),
            static fn (mixed $row): bool => ! is_array($row) || ($row['phase_id'] ?? '') !== '',
        ));

        $this->merge(['phases' => array_map(static function (mixed $row): mixed {
            if (! is_array($row)) {
                return $row;
            }

            foreach (['id', 'user_id', 'start_date', 'end_date', 'notes'] as $field) {
                if (($row[$field] ?? null) === '') {
                    $row[$field] = null;
                }
            }

            return $row;
        }, $rows)]);
    }

    public function authorize(): bool
    {
        $file = $this->route('file');

        return $file instanceof File && ($this->user()?->can('update', $file) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'phases' => ['present', 'array'],
            'phases.*' => ['array:id,phase_id,user_id,start_date,end_date,notes'],
            'phases.*.id' => ['nullable', 'integer', $this->ownRow()],
            'phases.*.phase_id' => ['required', 'integer', 'distinct', $this->selectablePhase()],
            'phases.*.user_id' => ['nullable', 'integer', Rule::exists($this->userModelClass(), 'id')],
            'phases.*.start_date' => ['nullable', 'date_format:Y-m-d'],
            'phases.*.end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:phases.*.start_date'],
            'phases.*.notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
        ];
    }

    /**
     * A phase is added among the active ones; a phase the file already goes through stays valid.
     */
    private function selectablePhase(): Exists
    {
        $file = $this->route('file');
        $assigned = $file instanceof File
            ? FilePhase::query()->where('file_id', $file->id)->pluck('phase_id')->map(static fn (mixed $id): int => (int) $id)->values()->all()
            : [];

        return Rule::exists(Phase::class, 'id')
            ->whereNull('deleted_at')
            ->where(static function (QueryBuilder $query) use ($assigned): void {
                $query->where('active', true);

                if ($assigned !== []) {
                    $query->orWhereIn('id', $assigned);
                }
            });
    }

    /**
     * A row that names an id has to be a phase of this file.
     */
    private function ownRow(): Exists
    {
        $file = $this->route('file');

        return Rule::exists(FilePhase::class, 'id')->where('file_id', $file instanceof File ? $file->getKey() : 0);
    }
}

<?php

namespace Files13\Http\Requests;

use Files13\Models\File;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The month the phase calendar shows, the current one when none is asked for.
 */
class FileCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', File::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'month' => ['nullable', 'date_format:Y-m'],
        ];
    }

    public function month(): CarbonImmutable
    {
        $month = $this->validated()['month'] ?? null;

        return is_string($month)
            ? CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')?->startOfMonth() ?? CarbonImmutable::today()->startOfMonth()
            : CarbonImmutable::today()->startOfMonth();
    }
}

<?php

namespace Files13\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The document server downloading the content to edit. The caller has already been checked by its
 * address and by the signed token of the download URL.
 */
class OnlyOfficeDownloadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}

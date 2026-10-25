<?php

namespace Files13\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * What the document server reports about an editing session. The caller has already been checked
 * by its address and by the signed token of the callback URL.
 *
 * @see https://api.onlyoffice.com/editors/callback
 */
class OnlyOfficeCallbackRequest extends FormRequest
{
    /** The document is being edited. */
    public const EDITING = 1;

    /** The document is ready to be saved. */
    public const READY_FOR_SAVING = 2;

    /** The document was closed with no changes. */
    public const CLOSED_WITHOUT_CHANGES = 4;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'integer'],
            'url' => ['nullable', 'string', 'url'],
            'users' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
        ];
    }

    public function readOnly(): bool
    {
        $payload = $this->attributes->get('onlyoffice');

        return ! is_array($payload) || (bool) ($payload['readonly'] ?? true);
    }

    public function userId(): ?int
    {
        $payload = $this->attributes->get('onlyoffice');

        return is_array($payload) && isset($payload['user']) ? (int) $payload['user'] : null;
    }
}

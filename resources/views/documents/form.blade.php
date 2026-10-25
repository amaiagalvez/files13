{{-- The shared form does not send files on its own, so the field switches the form it lives in to
     multipart. --}}
<x-basics13::forms.tracked-resource prefix="document" context-field="_document_form" id-field="_document_id">
    <flux:field x-init="$el.closest('form').enctype = 'multipart/form-data'">
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\DocumentRequest::class" field="name" test="document-name-info" for="document-name">
            {{ __('Name') }}
        </x-basics13::forms.field-label>
        <flux:input name="name" x-model="form.name" minlength="2" required autofocus
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}" id="document-name" data-test="document-name" />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\DocumentRequest::class" field="content" test="document-content-info" for="document-content">
            {{ __('Content') }}
        </x-basics13::forms.field-label>
        <input type="file" name="content" id="document-content" data-test="document-content"
            accept="{{ collect(\Files13\Models\Document::EXTENSIONS)->map(fn (string $extension): string => '.'.$extension)->join(',') }}"
            x-on:change="$el.closest('form').dispatchEvent(new Event('input', { bubbles: true }))" />
        <flux:text class="text-xs">{{ __('Up to :size MB.', ['size' => intdiv((int) config('files13.max_upload_kb'), 1024)]) }}</flux:text>
        <flux:error name="content" />
    </flux:field>

    <flux:fieldset>
        <flux:legend>{{ __('Subjects') }}</flux:legend>
        <div class="flex max-h-48 flex-col gap-1 overflow-y-auto" data-test="document-subjects">
            <template x-for="subject in options.subjects" :key="subject.id">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="subject_ids[]" x-bind:value="subject.id" x-model="form.subject_ids"
                        x-bind:data-test="`document-subject-${subject.id}`" />
                    <span x-text="subject.text"></span>
                </label>
            </template>
        </div>
        <flux:error name="subject_ids" />
    </flux:fieldset>

    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\DocumentRequest::class" field="notes" test="document-notes-info" for="document-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}" id="document-notes" data-test="document-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>

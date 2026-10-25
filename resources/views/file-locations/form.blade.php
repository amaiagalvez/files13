@php
    // Euskera is the language a type always has a name in, so it is the only field that is asked
    // for and always shown; the other three are optional translations, shown only when the
    // `languages` setting enables them.
    $nameFields = [
        'name_eu' => __('Name (eu)'),
        'name_es' => __('Name (es)'),
        'name_fr' => __('Name (fr)'),
        'name_en' => __('Name (en)'),
    ];
    $enabledLanguages = \Basics13\Enums\Language::enabledCodes();
    $nameFields = array_filter($nameFields, static fn (string $field): bool => $field === 'name_eu'
        || in_array(substr($field, 5), $enabledLanguages, true), ARRAY_FILTER_USE_KEY);
@endphp

<x-basics13::forms.tracked-resource prefix="file-location" context-field="_file_location_form"
    id-field="_file_location_id">
    @foreach ($nameFields as $field => $label)
        <flux:field>
            <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileLocationRequest::class" :field="$field" :test="'file-location-' . $field . '-info'"
                :for="'file-location-' . $field" :labels="$nameFields">
                {{ $label }}
            </x-basics13::forms.field-label>
            <flux:input name="{{ $field }}" x-model="form.{{ $field }}"
                :minlength="$field === 'name_eu' ? 2 : null" :required="$field === 'name_eu'"
                :autofocus="$field === 'name_eu'"
                maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
                id="file-location-{{ $field }}" data-test="file-location-{{ $field }}" />
            <flux:error name="{{ $field }}" />
        </flux:field>
    @endforeach

    {{-- The days of the calendar travel with the location: removing a row only takes effect when the
         form is saved, and cancelling the form discards it. --}}
    <flux:fieldset>
        <flux:legend>{{ __('Days') }}</flux:legend>

        <div class="flex flex-col gap-2" data-test="file-location-days">
            <template x-for="(day, index) in form.days" :key="index">
                <div class="flex items-end gap-2">
                    <input type="hidden" x-bind:name="`days[${index}][id]`" x-bind:value="day.id ?? ''"
                        x-bind:disabled="! day.id" />
                    <flux:input type="date" x-model="day.date" x-bind:name="`days[${index}][date]`" required
                        :label="__('Date')" x-bind:data-test="`file-location-day-date-${index}`" />
                    <flux:input x-model="day.name" x-bind:name="`days[${index}][name]`" required
                        maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}" :label="__('Name')"
                        class="flex-1" x-bind:data-test="`file-location-day-name-${index}`" />
                    <flux:button type="button" variant="ghost" size="sm" icon="trash"
                        x-on:click="form.days.splice(index, 1)" :aria-label="__('Remove day')"
                        x-bind:data-test="`file-location-day-remove-${index}`" />
                </div>
            </template>

            <div>
                <flux:button type="button" size="sm" icon="plus"
                    x-on:click="form.days.push({ id: null, date: '', name: '' })" data-test="file-location-day-add">
                    {{ __('Add day') }}
                </flux:button>
            </div>

            @error('days.*')
                <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror
        </div>
    </flux:fieldset>

    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileLocationRequest::class" field="notes"
            test="file-location-notes-info" for="file-location-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="file-location-notes" data-test="file-location-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>

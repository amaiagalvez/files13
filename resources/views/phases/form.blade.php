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

<x-basics13::forms.tracked-resource prefix="phase" context-field="_phase_form"
    id-field="_phase_id">
    @foreach ($nameFields as $field => $label)
        <flux:field>
            <x-basics13::forms.field-label :request="\Files13\Http\Requests\PhaseRequest::class" :field="$field" :test="'phase-' . $field . '-info'"
                :for="'phase-' . $field" :labels="$nameFields">
                {{ $label }}
            </x-basics13::forms.field-label>
            <flux:input name="{{ $field }}" x-model="form.{{ $field }}"
                :minlength="$field === 'name_eu' ? 2 : null" :required="$field === 'name_eu'"
                :autofocus="$field === 'name_eu'"
                maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
                id="phase-{{ $field }}" data-test="phase-{{ $field }}" />
            <flux:error name="{{ $field }}" />
        </flux:field>
    @endforeach

    <div class="grid grid-cols-2 gap-4">
        <flux:field>
            <x-basics13::forms.field-label :request="\Files13\Http\Requests\PhaseRequest::class" field="order"
                test="phase-order-info" for="phase-order">
                {{ __('Order') }}
            </x-basics13::forms.field-label>
            <flux:input type="number" min="0" name="order" x-model="form.order" required id="phase-order"
                data-test="phase-order" />
            <flux:error name="order" />
        </flux:field>

        <flux:field>
            <x-basics13::forms.field-label :request="\Files13\Http\Requests\PhaseRequest::class" field="color"
                test="phase-color-info" for="phase-color">
                {{ __('Color') }}
            </x-basics13::forms.field-label>
            <flux:input type="color" name="color" x-model="form.color" id="phase-color" data-test="phase-color" />
            <flux:error name="color" />
        </flux:field>
    </div>

    <flux:field>
        <flux:checkbox name="show_calendar" value="1" x-model="form.show_calendar" :label="__('Show in the calendar')"
            id="phase-show-calendar" data-test="phase-show-calendar" />
        <flux:error name="show_calendar" />
    </flux:field>

    <div class="grid grid-cols-2 items-end gap-4">
        <flux:field>
            <flux:checkbox name="accumulate_days" value="1" x-model="form.accumulate_days" :label="__('Accumulates days')"
                id="phase-accumulate-days" data-test="phase-accumulate-days" />
            <flux:error name="accumulate_days" />
        </flux:field>

        <flux:field>
            <x-basics13::forms.field-label :request="\Files13\Http\Requests\PhaseRequest::class" field="accumulate_days_order"
                test="phase-accumulate-days-order-info" for="phase-accumulate-days-order">
                {{ __('Accumulation order') }}
            </x-basics13::forms.field-label>
            <flux:input type="number" min="0" name="accumulate_days_order" x-model="form.accumulate_days_order"
                id="phase-accumulate-days-order" data-test="phase-accumulate-days-order" />
            <flux:error name="accumulate_days_order" />
        </flux:field>
    </div>

    <flux:fieldset>
        <flux:legend>{{ __('Types') }}</flux:legend>
        <div class="flex flex-col gap-1" data-test="phase-types">
            <template x-for="type in options.types" :key="type.id">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="type_ids[]" x-bind:value="type.id" x-model="form.type_ids"
                        x-bind:data-test="`phase-type-${type.id}`" />
                    <span x-text="type.text"></span>
                </label>
            </template>
        </div>
        <flux:error name="type_ids" />
        @error('type_ids.*')
            <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
        @enderror
    </flux:fieldset>

    {{-- Each subject carries the alert that sets the deadline of the phase. Removing a row only takes
         effect when the form is saved. --}}
    <flux:fieldset>
        <flux:legend>{{ __('Subjects') }}</flux:legend>
        <div class="flex flex-col gap-2" data-test="phase-subjects">
            <template x-for="(subject, index) in form.subjects" :key="index">
                <div class="flex items-end gap-2">
                    <flux:select x-model="subject.subject_id" x-bind:name="`subjects[${index}][subject_id]`" required
                        :label="__('Subject')" class="flex-1" x-bind:data-test="`phase-subject-${index}`">
                        <option value="">{{ __('Choose a subject') }}</option>
                        <template x-for="option in options.subjects" :key="option.id">
                            <option x-bind:value="option.id" x-text="option.text"
                                x-bind:selected="String(option.id) === String(subject.subject_id)"></option>
                        </template>
                    </flux:select>
                    <flux:input type="number" min="0" x-model="subject.alert" x-bind:name="`subjects[${index}][alert]`"
                        required :label="__('Alert')" class="w-24" x-bind:data-test="`phase-subject-alert-${index}`" />
                    <flux:select x-model="subject.alert_unit" x-bind:name="`subjects[${index}][alert_unit]`"
                        :label="__('Unit')" class="w-28" x-bind:data-test="`phase-subject-unit-${index}`">
                        <option value="days">{{ __('Days') }}</option>
                        <option value="year">{{ __('Years') }}</option>
                    </flux:select>
                    <flux:button type="button" variant="ghost" size="sm" icon="trash"
                        x-on:click="form.subjects.splice(index, 1)" :aria-label="__('Remove subject')"
                        x-bind:data-test="`phase-subject-remove-${index}`" />
                </div>
            </template>

            <div>
                <flux:button type="button" size="sm" icon="plus"
                    x-on:click="form.subjects.push({ subject_id: '', alert: 0, alert_unit: 'days' })"
                    data-test="phase-subject-add">
                    {{ __('Add subject') }}
                </flux:button>
            </div>

            @error('subjects.*')
                <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror
        </div>
    </flux:fieldset>

    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\PhaseRequest::class" field="notes"
            test="phase-notes-info" for="phase-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="phase-notes" data-test="phase-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>

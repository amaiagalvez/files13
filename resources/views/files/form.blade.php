{{-- The rows of the file (users, applicants) travel with it: removing one only takes effect when the
     form is saved. The phases and the documents are edited from the file sheet. --}}
<x-basics13::forms.tracked-resource prefix="file" context-field="_file_form" id-field="_file_id">
    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="name" test="file-name-info" for="file-name">
            {{ __('Name') }}
        </x-basics13::forms.field-label>
        <flux:input name="name" x-model="form.name" minlength="2" required autofocus
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}" id="file-name" data-test="file-name" />
        <flux:error name="name" />
    </flux:field>

    <div class="grid grid-cols-2 items-end gap-4">
        <flux:field>
            <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="number" test="file-number-info" for="file-number">
                {{ __('Number') }}
            </x-basics13::forms.field-label>
            <flux:input name="number" x-model="form.number" maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
                id="file-number" data-test="file-number" />
            <flux:error name="number" />
        </flux:field>

        <flux:field>
            <flux:checkbox name="impugn" value="1" x-model="form.impugn" :label="__('Impugned')" id="file-impugn" data-test="file-impugn" />
            <flux:error name="impugn" />
        </flux:field>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <flux:field>
            <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="start_date" test="file-start_date-info" for="file-start_date">
                {{ __('Start') }}
            </x-basics13::forms.field-label>
            <flux:input type="date" id="file-start_date" name="start_date" x-model="form.start_date" data-test="file-start_date" />
            <flux:error name="start_date" />
        </flux:field>
        <flux:field>
            <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="end_date" test="file-end_date-info" for="file-end_date">
                {{ __('End') }}
            </x-basics13::forms.field-label>
            <flux:input type="date" id="file-end_date" name="end_date" x-model="form.end_date" data-test="file-end_date" />
            <flux:error name="end_date" />
        </flux:field>
    </div>

    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="type_id" test="file-type_id-info" for="file-type_id">
            {{ __('Type') }}
        </x-basics13::forms.field-label>
        <flux:select id="file-type_id" name="type_id" x-model="form.type_id" required data-test="file-type_id">
            <option value="">{{ __('Choose a type') }}</option>
            <template x-for="option in options.types" :key="option.id">
                <option x-bind:value="option.id" x-text="option.text" x-bind:selected="String(option.id) === String(form.type_id)"></option>
            </template>
        </flux:select>
        <flux:error name="type_id" />
    </flux:field>
    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="status_id" test="file-status_id-info" for="file-status_id">
            {{ __('Status') }}
        </x-basics13::forms.field-label>
        <flux:select id="file-status_id" name="status_id" x-model="form.status_id" data-test="file-status_id">
            <option value="">{{ __('—') }}</option>
            <template x-for="option in options.statuses" :key="option.id">
                <option x-bind:value="option.id" x-text="option.text" x-bind:selected="String(option.id) === String(form.status_id)"></option>
            </template>
        </flux:select>
        <flux:error name="status_id" />
    </flux:field>
    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="subject_id" test="file-subject_id-info" for="file-subject_id">
            {{ __('Subject') }}
        </x-basics13::forms.field-label>
        <flux:select id="file-subject_id" name="subject_id" x-model="form.subject_id" data-test="file-subject_id">
            <option value="">{{ __('—') }}</option>
            <template x-for="option in options.subjects" :key="option.id">
                <option x-bind:value="option.id" x-text="option.text" x-bind:selected="String(option.id) === String(form.subject_id)"></option>
            </template>
        </flux:select>
        <flux:error name="subject_id" />
    </flux:field>
    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="ending_id" test="file-ending_id-info" for="file-ending_id">
            {{ __('Ending') }}
        </x-basics13::forms.field-label>
        <flux:select id="file-ending_id" name="ending_id" x-model="form.ending_id" data-test="file-ending_id">
            <option value="">{{ __('—') }}</option>
            <template x-for="option in options.endings" :key="option.id">
                <option x-bind:value="option.id" x-text="option.text" x-bind:selected="String(option.id) === String(form.ending_id)"></option>
            </template>
        </flux:select>
        <flux:error name="ending_id" />
    </flux:field>
    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="location_id" test="file-location_id-info" for="file-location_id">
            {{ __('Location') }}
        </x-basics13::forms.field-label>
        <flux:select id="file-location_id" name="location_id" x-model="form.location_id" data-test="file-location_id">
            <option value="">{{ __('—') }}</option>
            <template x-for="option in options.locations" :key="option.id">
                <option x-bind:value="option.id" x-text="option.text" x-bind:selected="String(option.id) === String(form.location_id)"></option>
            </template>
        </flux:select>
        <flux:error name="location_id" />
    </flux:field>
    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="district_id" test="file-district_id-info" for="file-district_id">
            {{ __('District') }}
        </x-basics13::forms.field-label>
        <flux:select id="file-district_id" name="district_id" x-model="form.district_id" data-test="file-district_id">
            <option value="">{{ __('—') }}</option>
            <template x-for="option in options.districts" :key="option.id">
                <option x-bind:value="option.id" x-text="option.text" x-bind:selected="String(option.id) === String(form.district_id)"></option>
            </template>
        </flux:select>
        <flux:error name="district_id" />
    </flux:field>

    <flux:fieldset>
        <flux:legend>{{ __('Users') }}</flux:legend>
        <div class="flex max-h-48 flex-col gap-1 overflow-y-auto" data-test="file-users">
            <template x-for="user in options.users" :key="user.id">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="user_ids[]" x-bind:value="user.id" x-model="form.user_ids"
                        x-bind:data-test="`file-user-${user.id}`" />
                    <span x-text="user.text"></span>
                </label>
            </template>
        </div>
        {{-- An empty list still has to reach the server, or unticking every user would keep them. --}}
        <input type="hidden" name="user_ids" value="" x-bind:disabled="form.user_ids.length > 0" />
        <flux:error name="user_ids" />
    </flux:fieldset>

    <flux:fieldset x-show="options.applicantTypes.length > 0">
        <flux:legend>{{ __('Applicants') }}</flux:legend>
        <div class="flex flex-col gap-2" data-test="file-applicants">
            <template x-for="(applicant, index) in form.applicants" :key="index">
                <div class="flex items-end gap-2">
                    <flux:select x-model="applicant.applicant_type" x-bind:name="`applicants[${index}][applicant_type]`" required
                        :label="__('Kind')" class="flex-1" x-bind:data-test="`file-applicant-type-${index}`">
                        <template x-for="option in options.applicantTypes" :key="option.id">
                            <option x-bind:value="option.id" x-text="option.text"
                                x-bind:selected="option.id === applicant.applicant_type"></option>
                        </template>
                    </flux:select>
                    <flux:input type="number" min="1" x-model="applicant.applicant_id" x-bind:name="`applicants[${index}][applicant_id]`"
                        required :label="__('Identifier')" class="w-32" x-bind:data-test="`file-applicant-id-${index}`" />
                    <flux:button type="button" variant="ghost" size="sm" icon="trash" x-on:click="form.applicants.splice(index, 1)"
                        :aria-label="__('Remove applicant')" x-bind:data-test="`file-applicant-remove-${index}`" />
                </div>
            </template>
            <div>
                <flux:button type="button" size="sm" icon="plus" data-test="file-applicant-add"
                    x-on:click="form.applicants.push({ applicant_type: options.applicantTypes[0]?.id ?? '', applicant_id: '' })">
                    {{ __('Add applicant') }}
                </flux:button>
            </div>
            <input type="hidden" name="applicants" value="" x-bind:disabled="form.applicants.length > 0" />
            @error('applicants.*')
                <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror
        </div>
    </flux:fieldset>

    <flux:field>
        <x-basics13::forms.field-label :request="\Files13\Http\Requests\FileRequest::class" field="notes" test="file-notes-info" for="file-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}" id="file-notes" data-test="file-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>

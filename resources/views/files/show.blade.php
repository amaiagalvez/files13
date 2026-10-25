@php
    /** @var \Files13\Models\File $file */
    $phaseRows = $file->filePhases->map(fn (\Files13\Models\FilePhase $row): array => [
        'id' => (string) $row->id,
        'phase_id' => (string) $row->phase_id,
        'user_id' => (string) ($row->user_id ?? ''),
        'start_date' => $row->start_date?->toDateString() ?? '',
        'end_date' => $row->end_date?->toDateString() ?? '',
        'notes' => $row->notes ?? '',
    ])->values()->all();
    $details = [
        __('Number') => $file->number,
        __('Type') => $file->type->name_eu,
        __('Status') => $file->status?->name_eu,
        __('Subject') => $file->subject?->name_eu,
        __('Ending') => $file->ending?->name_eu,
        __('Location') => $file->location?->name_eu,
        __('District') => $file->district?->name,
        __('Start') => $file->start_date?->toDateString(),
        __('End') => $file->end_date?->toDateString(),
        __('Impugned') => $file->impugn ? __('Yes') : __('No'),
    ];
@endphp

<x-layouts::app :title="$file->name">
    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-3">
            <div class="min-w-0">
                <flux:heading level="1" size="xl" data-test="file-heading">{{ $file->name }}</flux:heading>
                @unless ($writable)
                    <flux:text class="mt-1 text-sm" data-test="file-read-only">{{ __('You can view this file but not change it.') }}</flux:text>
                @endunless
            </div>
            <flux:button variant="ghost" icon="arrow-left" :href="route('files.index')" data-test="file-back">
                {{ __('Files') }}
            </flux:button>
        </div>

        <x-basics13::list.flash prefix="file" />

        <section aria-labelledby="file-details-heading">
            <flux:heading id="file-details-heading" level="2" size="lg">{{ __('Details') }}</flux:heading>
            <dl class="mt-3 grid gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3" data-test="file-details">
                @foreach ($details as $label => $value)
                    <div>
                        <dt class="text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}</dt>
                        <dd>{{ $value ?: '—' }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="mt-3">
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Users') }}</flux:text>
                <p data-test="file-users">{{ $file->users->pluck('name')->join(', ') ?: '—' }}</p>
            </div>
            <div class="mt-3">
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Applicants') }}</flux:text>
                {{-- An applicant whose record the host no longer has is left out instead of failing. --}}
                <p data-test="file-applicants">
                    {{ $file->applicants->filter(fn ($applicant) => $applicant->applicant !== null)->map(fn ($applicant) => \Illuminate\Support\Str::headline($applicant->applicant_type).' #'.$applicant->applicant_id)->join(', ') ?: '—' }}
                </p>
            </div>
        </section>

        <section aria-labelledby="file-phases-heading">
            <flux:heading id="file-phases-heading" level="2" size="lg">{{ __('Phases') }}</flux:heading>

            @if ($writable)
                <form method="POST" action="{{ route('files.phases.update', $file) }}" class="mt-3 flex flex-col gap-3"
                    x-data="{ options: @js($options), phases: @js(array_values((array) old('phases', $phaseRows))) }"
                    data-test="file-phases-form">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="phases" value="" x-bind:disabled="phases.length > 0" />

                    <template x-for="(row, index) in phases" :key="index">
                        <div class="grid items-end gap-2 sm:grid-cols-[2fr_2fr_1fr_1fr_2fr_auto]">
                            <input type="hidden" x-bind:name="`phases[${index}][id]`" x-bind:value="row.id ?? ''" x-bind:disabled="! row.id" />
                            <flux:select x-model="row.phase_id" x-bind:name="`phases[${index}][phase_id]`" required :label="__('Phase')"
                                x-bind:data-test="`file-phase-phase-${index}`">
                                <option value="">{{ __('Choose a phase') }}</option>
                                <template x-for="option in options.phases" :key="option.id">
                                    <option x-bind:value="option.id" x-text="option.text" x-bind:selected="String(option.id) === String(row.phase_id)"></option>
                                </template>
                            </flux:select>
                            <flux:select x-model="row.user_id" x-bind:name="`phases[${index}][user_id]`" :label="__('Responsible')"
                                x-bind:data-test="`file-phase-user-${index}`">
                                <option value="">—</option>
                                <template x-for="option in options.users" :key="option.id">
                                    <option x-bind:value="option.id" x-text="option.text" x-bind:selected="String(option.id) === String(row.user_id)"></option>
                                </template>
                            </flux:select>
                            <flux:input type="date" x-model="row.start_date" x-bind:name="`phases[${index}][start_date]`" :label="__('Start')"
                                x-bind:data-test="`file-phase-start-${index}`" />
                            <flux:input type="date" x-model="row.end_date" x-bind:name="`phases[${index}][end_date]`" :label="__('End')"
                                x-bind:data-test="`file-phase-end-${index}`" />
                            <flux:input x-model="row.notes" x-bind:name="`phases[${index}][notes]`" :label="__('Notes')"
                                maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}" x-bind:data-test="`file-phase-notes-${index}`" />
                            <flux:button type="button" variant="ghost" size="sm" icon="trash" x-on:click="phases.splice(index, 1)"
                                :aria-label="__('Remove phase')" x-bind:data-test="`file-phase-remove-${index}`" />
                        </div>
                    </template>

                    @error('phases.*')
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror

                    <div class="flex gap-2">
                        <flux:button type="button" size="sm" icon="plus" data-test="file-phase-add"
                            x-on:click="phases.push({ id: null, phase_id: '', user_id: '', start_date: '', end_date: '', notes: '' })">
                            {{ __('Add phase') }}
                        </flux:button>
                        <flux:button type="submit" size="sm" variant="primary" data-test="file-phases-submit">{{ __('Save phases') }}</flux:button>
                    </div>
                </form>
            @endif

            <flux:table class="mt-3" data-test="file-phases">
                <flux:table.columns>
                    <flux:table.column>{{ __('Phase') }}</flux:table.column>
                    <flux:table.column>{{ __('Start') }}</flux:table.column>
                    <flux:table.column>{{ __('End') }}</flux:table.column>
                    <flux:table.column>{{ __('Alert date') }}</flux:table.column>
                    <flux:table.column>{{ __('Pending days') }}</flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @forelse ($file->filePhases as $row)
                        <flux:table.row :key="$row->id">
                            <flux:table.cell>{{ $row->phase->name_eu }}</flux:table.cell>
                            <flux:table.cell>{{ $row->start_date?->toDateString() ?? '—' }}</flux:table.cell>
                            <flux:table.cell>{{ $row->end_date?->toDateString() ?? '—' }}</flux:table.cell>
                            <flux:table.cell data-test="file-phase-alert-{{ $row->id }}">{{ $row->alert_date?->toDateString() ?? '—' }}</flux:table.cell>
                            <flux:table.cell data-test="file-phase-pending-{{ $row->id }}">{{ $row->accumulate_pending_days ?? '—' }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">{{ __('The file has no phases.') }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </section>

        <section aria-labelledby="file-documents-heading" x-data="{ removing: null }">
            <div class="flex items-center justify-between gap-3">
                <flux:heading id="file-documents-heading" level="2" size="lg">{{ __('Documents') }}</flux:heading>
                @if ($writable)
                    <flux:modal.trigger name="file-document-add">
                        <flux:button size="sm" icon="plus" data-test="file-document-add">{{ __('Add document') }}</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>

            @php
                $byFolder = $file->documents->groupBy(fn ($document) => $folders[$document->getRelation('pivot')->getAttribute('folder_id')] ?? '');
            @endphp

            <div class="mt-3 flex flex-col gap-4" data-test="file-documents">
                @forelse ($byFolder->sortKeys() as $folder => $documents)
                    <div>
                        <flux:heading level="3" size="sm">{{ $folder !== '' ? $folder : __('Without folder') }}</flux:heading>
                        <ul class="mt-2 flex flex-col gap-2">
                            @foreach ($documents as $document)
                                <li class="flex flex-wrap items-center gap-3" data-test="file-document-{{ $document->id }}">
                                    <span class="font-medium">{{ $document->name }}</span>
                                    @if ($document->editing)
                                        <flux:badge size="sm" color="amber">{{ __('Open in the editor') }}</flux:badge>
                                    @endif
                                    @if (\Illuminate\Support\Facades\Route::has('documents.editor'))
                                    <a class="text-sm hover:underline" href="{{ route('documents.editor', $document) }}" data-test="file-document-open-{{ $document->id }}">
                                        {{ $writable ? __('Edit in the editor') : __('Open') }}
                                    </a>
                                    @endif
                                    @if ($writable)
                                        <form method="POST" action="{{ route('files.documents.update', [$file, $document]) }}" class="flex items-center gap-2">
                                            @csrf
                                            @method('PATCH')
                                            <flux:input size="sm" name="folder" list="file-folders" :value="$folder" :placeholder="__('Folder')"
                                                :aria-label="__('Folder')" maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
                                                data-test="file-document-folder-{{ $document->id }}" />
                                            <flux:button size="sm" type="submit" variant="ghost">{{ __('Move') }}</flux:button>
                                        </form>
                                        <flux:modal.trigger name="file-document-remove">
                                            <flux:button size="sm" variant="ghost" icon="trash" :aria-label="__('Remove document')"
                                                x-on:click="removing = @js(['action' => route('files.documents.destroy', [$file, $document]), 'name' => $document->name])"
                                                data-test="file-document-remove-{{ $document->id }}" />
                                        </flux:modal.trigger>
                                    @endif
                                    <details class="w-full text-sm">
                                        <summary class="cursor-pointer text-zinc-500 dark:text-zinc-400">{{ __('Tracking') }}</summary>
                                        <ul class="mt-1" data-test="file-document-tracks-{{ $document->id }}">
                                            @foreach ($document->tracks as $track)
                                                <li>{{ $track->created_at?->toDateTimeString() }} · {{ __('track.'.$track->action) }}</li>
                                            @endforeach
                                        </ul>
                                    </details>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <flux:text>{{ __('The file has no documents.') }}</flux:text>
                @endforelse
            </div>

            @if ($writable)
                <datalist id="file-folders">
                    @foreach ($folders as $name)
                        <option value="{{ $name }}"></option>
                    @endforeach
                </datalist>

                <flux:modal name="file-document-add" class="max-w-lg">
                    <form method="POST" action="{{ route('files.documents.store', $file) }}" enctype="multipart/form-data"
                        class="flex flex-col gap-4" data-test="file-document-form">
                        @csrf
                        <flux:heading size="lg">{{ __('Add document') }}</flux:heading>
                        <flux:field>
                            <flux:label>{{ __('Template') }}</flux:label>
                            <flux:select name="template_id" data-test="file-document-template">
                                <option value="">{{ __('No template') }}</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template['id'] }}" @disabled($template['editing'])>
                                        {{ $template['text'] }}{{ $template['editing'] ? ' ('.__('Open in the editor').')' : '' }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error name="template_id" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Name') }}</flux:label>
                            <flux:input name="name" maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}" data-test="file-document-name" />
                            <flux:description>{{ __('Leave it empty to keep the name of the template.') }}</flux:description>
                            <flux:error name="name" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Content') }}</flux:label>
                            <input type="file" name="content" data-test="file-document-content"
                                accept="{{ collect(\Files13\Models\Document::EXTENSIONS)->map(fn (string $extension): string => '.'.$extension)->join(',') }}" />
                            <flux:error name="content" />
                        </flux:field>
                        <flux:field>
                            <flux:label>{{ __('Folder') }}</flux:label>
                            <flux:input name="folder" list="file-folders" maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}" data-test="file-document-new-folder" />
                            <flux:error name="folder" />
                        </flux:field>
                        <div class="flex justify-end gap-2">
                            <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                            <flux:button type="submit" variant="primary" data-test="file-document-submit">{{ __('Add document') }}</flux:button>
                        </div>
                    </form>
                </flux:modal>

                <flux:modal name="file-document-remove" class="max-w-md">
                    <form method="POST" x-bind:action="removing?.action" class="flex flex-col gap-4" data-test="file-document-remove-form">
                        @csrf
                        @method('DELETE')
                        <flux:heading size="lg">{{ __('Remove document') }}</flux:heading>
                        <flux:text x-text="removing?.name"></flux:text>
                        <flux:text>{{ __('basics13::messages.you_can_restore_from_trash') }}</flux:text>
                        <div class="flex justify-end gap-2">
                            <flux:modal.close><flux:button variant="ghost">{{ __('Cancel') }}</flux:button></flux:modal.close>
                            <flux:button type="submit" variant="danger" data-test="file-document-remove-confirm">{{ __('Remove document') }}</flux:button>
                        </div>
                    </form>
                </flux:modal>
            @endif
        </section>

        <section aria-labelledby="file-changes-heading">
            <flux:heading id="file-changes-heading" level="2" size="lg">{{ __('Change log') }}</flux:heading>
            <ul class="mt-3 flex flex-col gap-2 text-sm" data-test="file-changes">
                @forelse ($file->changes as $change)
                    <li>
                        <span class="text-zinc-500 dark:text-zinc-400">{{ $change->created_at?->toDateTimeString() }}</span>
                        @foreach ($change->previous as $column => $value)
                            <span class="ms-2">{{ $column }}: {{ $value === null || $value === '' ? '—' : $value }}</span>
                        @endforeach
                    </li>
                @empty
                    <li>{{ __('No changes yet.') }}</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts::app>

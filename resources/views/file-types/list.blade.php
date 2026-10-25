@php
    $editingFileType = $list['create'] && str_starts_with(old('_file_type_form', ''), 'edit-');
    $deletedFileTypeConflict = session('deleted_file_type_conflict');

    // The "create new" button of the conflict modal posts the four names back, so a type can be
    // created with the same euskera name once the user decides not to restore the trashed one.
    $conflictCreateFields = [];

    foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $conflictField) {
        $conflictCreateFields[$conflictField] = old(
            $conflictField,
            $deletedFileTypeConflict[$conflictField] ?? '',
        );
    }

    $initialForm = $list['create']
        ? [
            'id' => $editingFileType ? old('_file_type_id') : null,
            'name_eu' => old('name_eu', ''),
            'name_es' => old('name_es', ''),
            'name_fr' => old('name_fr', ''),
            'name_en' => old('name_en', ''),
            'company' => (bool) old('company', false),
            'person' => (bool) old('person', false),
            'notes' => old('notes', ''),
            'context' => $editingFileType ? old('_file_type_form') : 'create',
            'method' => $editingFileType ? 'PUT' : 'POST',
            'action' => $editingFileType
                ? route('file-types.update', old('_file_type_id'))
                : route('file-types.store'),
            'title' => $editingFileType ? __('Edit type') : __('New type'),
            'submitLabel' => $editingFileType ? __('Update type') : __('Save type'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('file-types.store')),
        updateUrl: @js(route('file-types.update', '__RECORD__')),
        createType() {
            this.form = {
                id: null,
                name_eu: '',
                name_es: '',
                name_fr: '',
                name_en: '',
                company: false,
                person: false,
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New type')),
                submitLabel: @js(__('Save type')),
            };
        },
        editType(fileType) {
            this.form = {
                ...fileType,
                context: `edit-${fileType.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__RECORD__', fileType.id),
                title: @js(__('Edit type')),
                submitLabel: @js(__('Update type')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-type-form' }))"
        @elseif ($deletedFileTypeConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-type-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createType(); $nextTick(() => $dispatch('modal-show', { name: 'file-type-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="file-type" :description="__('Kinds of file, for companies, for people or for both.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="file-type" :label="__('New type')"
                        click="createType()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="file-type" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$fileTypes->total()" :scope="$scope">
                <x-basics13::list.table prefix="file-type" :paginator="$fileTypes">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="file-type" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name (eu)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (es)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (fr)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (en)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('For') }}</flux:table.column>
                        <flux:table.column scope="col" align="end" sticky class="text-end">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($list['rows'] as $row)
                            <flux:table.row :key="$row['id']"
                                class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <flux:table.cell class="max-w-[16rem]">
                                    <span class="block max-w-full truncate" title="{{ $row['nameEu'] }}"
                                        data-test="file-type-name-{{ $row['id'] }}">
                                        {{ $row['nameEu'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['nameEs'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['nameFr'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['nameEn'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell>
                                    {{ collect([$row['company'] ? __('Companies') : null, $row['person'] ? __('People') : null])->filter()->join(', ') ?: '—' }}
                                </flux:table.cell>
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="file-type"
                                        edit-handler="editType" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="6" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="file-type-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createType()"
                                            data-test="file-type-empty-create">
                                            {{ __('New type') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                @endif
                            </x-basics13::list.empty-state>
                        @endforelse
                    </flux:table.rows>
                </x-basics13::list.table>
            </x-basics13::list.searchable-results>
        @endfragment

        @if ($list['create'])
            <flux:modal name="file-type-form" aria-labelledby="file-type-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('files13::file-types.form')
            </flux:modal>

            @if ($deletedFileTypeConflict)
                <x-basics13::name-conflict-modal name="file-type-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedFileTypeConflict['name_eu'],
                    ])" :create-action="route('file-types.store')" :restore-action="route(
                        'file-types.trash.restore',
                        $deletedFileTypeConflict['id'],
                    )" :create-fields="$conflictCreateFields"
                    :create-label="__('Create a new type')" :restore-label="__('Restore the deleted record instead')"
                    create-test="file-type-conflict-create-new"
                    restore-test="file-type-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="file-type" />
    </div>
</x-layouts::app>
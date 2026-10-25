@php
    $editingFileEnding = $list['create'] && str_starts_with(old('_file_ending_form', ''), 'edit-');
    $deletedFileEndingConflict = session('deleted_file_ending_conflict');

    // The "create new" button of the conflict modal posts the four names back, so a type can be
    // created with the same euskera name once the user decides not to restore the trashed one.
    $conflictCreateFields = [];

    foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $conflictField) {
        $conflictCreateFields[$conflictField] = old(
            $conflictField,
            $deletedFileEndingConflict[$conflictField] ?? '',
        );
    }

    $initialForm = $list['create']
        ? [
            'id' => $editingFileEnding ? old('_file_ending_id') : null,
            'name_eu' => old('name_eu', ''),
            'name_es' => old('name_es', ''),
            'name_fr' => old('name_fr', ''),
            'name_en' => old('name_en', ''),
            'notes' => old('notes', ''),
            'context' => $editingFileEnding ? old('_file_ending_form') : 'create',
            'method' => $editingFileEnding ? 'PUT' : 'POST',
            'action' => $editingFileEnding
                ? route('file-endings.update', old('_file_ending_id'))
                : route('file-endings.store'),
            'title' => $editingFileEnding ? __('Edit ending') : __('New ending'),
            'submitLabel' => $editingFileEnding ? __('Update ending') : __('Save ending'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('file-endings.store')),
        updateUrl: @js(route('file-endings.update', '__RECORD__')),
        createEnding() {
            this.form = {
                id: null,
                name_eu: '',
                name_es: '',
                name_fr: '',
                name_en: '',
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New ending')),
                submitLabel: @js(__('Save ending')),
            };
        },
        editEnding(fileEnding) {
            this.form = {
                ...fileEnding,
                context: `edit-${fileEnding.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__RECORD__', fileEnding.id),
                title: @js(__('Edit ending')),
                submitLabel: @js(__('Update ending')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-ending-form' }))"
        @elseif ($deletedFileEndingConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-ending-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createEnding(); $nextTick(() => $dispatch('modal-show', { name: 'file-ending-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="file-ending" :description="__('How a file was closed.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="file-ending" :label="__('New ending')"
                        click="createEnding()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="file-ending" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$fileEndings->total()" :scope="$scope">
                <x-basics13::list.table prefix="file-ending" :paginator="$fileEndings">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="file-ending" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name (eu)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (es)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (fr)') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Name (en)') }}</flux:table.column>
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
                                        data-test="file-ending-name-{{ $row['id'] }}">
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
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="file-ending"
                                        edit-handler="editEnding" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="5" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="file-ending-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createEnding()"
                                            data-test="file-ending-empty-create">
                                            {{ __('New ending') }}
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
            <flux:modal name="file-ending-form" aria-labelledby="file-ending-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('files13::file-endings.form')
            </flux:modal>

            @if ($deletedFileEndingConflict)
                <x-basics13::name-conflict-modal name="file-ending-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedFileEndingConflict['name_eu'],
                    ])" :create-action="route('file-endings.store')" :restore-action="route(
                        'file-endings.trash.restore',
                        $deletedFileEndingConflict['id'],
                    )" :create-fields="$conflictCreateFields"
                    :create-label="__('Create a new ending')" :restore-label="__('Restore the deleted record instead')"
                    create-test="file-ending-conflict-create-new"
                    restore-test="file-ending-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="file-ending" />
    </div>
</x-layouts::app>
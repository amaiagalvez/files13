@php
    $editingFileStatus = $list['create'] && str_starts_with(old('_file_status_form', ''), 'edit-');
    $deletedFileStatusConflict = session('deleted_file_status_conflict');

    // The "create new" button of the conflict modal posts the four names back, so a type can be
    // created with the same euskera name once the user decides not to restore the trashed one.
    $conflictCreateFields = [];

    foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $conflictField) {
        $conflictCreateFields[$conflictField] = old(
            $conflictField,
            $deletedFileStatusConflict[$conflictField] ?? '',
        );
    }

    $initialForm = $list['create']
        ? [
            'id' => $editingFileStatus ? old('_file_status_id') : null,
            'name_eu' => old('name_eu', ''),
            'name_es' => old('name_es', ''),
            'name_fr' => old('name_fr', ''),
            'name_en' => old('name_en', ''),
            'notes' => old('notes', ''),
            'context' => $editingFileStatus ? old('_file_status_form') : 'create',
            'method' => $editingFileStatus ? 'PUT' : 'POST',
            'action' => $editingFileStatus
                ? route('file-statuses.update', old('_file_status_id'))
                : route('file-statuses.store'),
            'title' => $editingFileStatus ? __('Edit status') : __('New status'),
            'submitLabel' => $editingFileStatus ? __('Update status') : __('Save status'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('file-statuses.store')),
        updateUrl: @js(route('file-statuses.update', '__RECORD__')),
        createStatus() {
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
                title: @js(__('New status')),
                submitLabel: @js(__('Save status')),
            };
        },
        editStatus(fileStatus) {
            this.form = {
                ...fileStatus,
                context: `edit-${fileStatus.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__RECORD__', fileStatus.id),
                title: @js(__('Edit status')),
                submitLabel: @js(__('Update status')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-status-form' }))"
        @elseif ($deletedFileStatusConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-status-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createStatus(); $nextTick(() => $dispatch('modal-show', { name: 'file-status-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="file-status" :description="__('Where a file stands.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="file-status" :label="__('New status')"
                        click="createStatus()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="file-status" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$fileStatuses->total()" :scope="$scope">
                <x-basics13::list.table prefix="file-status" :paginator="$fileStatuses">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="file-status" :scope="$scope" />
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
                                        data-test="file-status-name-{{ $row['id'] }}">
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
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="file-status"
                                        edit-handler="editStatus" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="5" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="file-status-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createStatus()"
                                            data-test="file-status-empty-create">
                                            {{ __('New status') }}
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
            <flux:modal name="file-status-form" aria-labelledby="file-status-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('files13::file-statuses.form')
            </flux:modal>

            @if ($deletedFileStatusConflict)
                <x-basics13::name-conflict-modal name="file-status-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedFileStatusConflict['name_eu'],
                    ])" :create-action="route('file-statuses.store')" :restore-action="route(
                        'file-statuses.trash.restore',
                        $deletedFileStatusConflict['id'],
                    )" :create-fields="$conflictCreateFields"
                    :create-label="__('Create a new status')" :restore-label="__('Restore the deleted record instead')"
                    create-test="file-status-conflict-create-new"
                    restore-test="file-status-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="file-status" />
    </div>
</x-layouts::app>
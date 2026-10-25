@php
    $editingFileSubject = $list['create'] && str_starts_with(old('_file_subject_form', ''), 'edit-');
    $deletedFileSubjectConflict = session('deleted_file_subject_conflict');

    // The "create new" button of the conflict modal posts the four names back, so a type can be
    // created with the same euskera name once the user decides not to restore the trashed one.
    $conflictCreateFields = [];

    foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $conflictField) {
        $conflictCreateFields[$conflictField] = old(
            $conflictField,
            $deletedFileSubjectConflict[$conflictField] ?? '',
        );
    }

    $initialForm = $list['create']
        ? [
            'id' => $editingFileSubject ? old('_file_subject_id') : null,
            'name_eu' => old('name_eu', ''),
            'name_es' => old('name_es', ''),
            'name_fr' => old('name_fr', ''),
            'name_en' => old('name_en', ''),
            'notes' => old('notes', ''),
            'context' => $editingFileSubject ? old('_file_subject_form') : 'create',
            'method' => $editingFileSubject ? 'PUT' : 'POST',
            'action' => $editingFileSubject
                ? route('file-subjects.update', old('_file_subject_id'))
                : route('file-subjects.store'),
            'title' => $editingFileSubject ? __('Edit subject') : __('New subject'),
            'submitLabel' => $editingFileSubject ? __('Update subject') : __('Save subject'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('file-subjects.store')),
        updateUrl: @js(route('file-subjects.update', '__RECORD__')),
        createSubject() {
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
                title: @js(__('New subject')),
                submitLabel: @js(__('Save subject')),
            };
        },
        editSubject(fileSubject) {
            this.form = {
                ...fileSubject,
                context: `edit-${fileSubject.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__RECORD__', fileSubject.id),
                title: @js(__('Edit subject')),
                submitLabel: @js(__('Update subject')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-subject-form' }))"
        @elseif ($deletedFileSubjectConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-subject-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createSubject(); $nextTick(() => $dispatch('modal-show', { name: 'file-subject-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="file-subject" :description="__('Matters the files deal with.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="file-subject" :label="__('New subject')"
                        click="createSubject()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="file-subject" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$fileSubjects->total()" :scope="$scope">
                <x-basics13::list.table prefix="file-subject" :paginator="$fileSubjects">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="file-subject" :scope="$scope" />
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
                                        data-test="file-subject-name-{{ $row['id'] }}">
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
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="file-subject"
                                        edit-handler="editSubject" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="5" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="file-subject-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createSubject()"
                                            data-test="file-subject-empty-create">
                                            {{ __('New subject') }}
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
            <flux:modal name="file-subject-form" aria-labelledby="file-subject-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('files13::file-subjects.form')
            </flux:modal>

            @if ($deletedFileSubjectConflict)
                <x-basics13::name-conflict-modal name="file-subject-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', [
                        'name' => $deletedFileSubjectConflict['name_eu'],
                    ])" :create-action="route('file-subjects.store')" :restore-action="route(
                        'file-subjects.trash.restore',
                        $deletedFileSubjectConflict['id'],
                    )" :create-fields="$conflictCreateFields"
                    :create-label="__('Create a new subject')" :restore-label="__('Restore the deleted record instead')"
                    create-test="file-subject-conflict-create-new"
                    restore-test="file-subject-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="file-subject" />
    </div>
</x-layouts::app>
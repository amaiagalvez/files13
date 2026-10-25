@php
    $editingFile = $list['create'] && str_starts_with(old('_file_form', ''), 'edit-');
    $deletedFileConflict = session('deleted_file_conflict');

    $initialForm = $list['create']
        ? [
            'id' => $editingFile ? old('_file_id') : null,
            'name' => old('name', ''),
            'number' => old('number', ''),
            'impugn' => (bool) old('impugn', false),
            'start_date' => old('start_date', ''),
            'end_date' => old('end_date', ''),
            'type_id' => (string) old('type_id', ''),
            'status_id' => (string) old('status_id', ''),
            'subject_id' => (string) old('subject_id', ''),
            'ending_id' => (string) old('ending_id', ''),
            'location_id' => (string) old('location_id', ''),
            'district_id' => (string) old('district_id', ''),
            'user_ids' => array_values(array_map('strval', (array) old('user_ids', []))),
            'applicants' => array_values((array) old('applicants', [])),
            'notes' => old('notes', ''),
            'context' => $editingFile ? old('_file_form') : 'create',
            'method' => $editingFile ? 'PUT' : 'POST',
            'action' => $editingFile ? route('files.update', old('_file_id')) : route('files.store'),
            'title' => $editingFile ? __('Edit file') : __('New file'),
            'submitLabel' => $editingFile ? __('Update file') : __('Save file'),
        ]
        : null;
    $scope = $scope ?? 'active';
    $mine = $mine ?? false;
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        options: @js($options ?? []),
        storeUrl: @js(route('files.store')),
        updateUrl: @js(route('files.update', '__RECORD__')),
        blankFile() {
            return {
                id: null, name: '', number: '', impugn: false, start_date: '', end_date: '',
                type_id: '', status_id: '', subject_id: '', ending_id: '', location_id: '', district_id: '',
                user_ids: [], applicants: [], notes: '',
            };
        },
        createFile() {
            this.form = {
                ...this.blankFile(),
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New file')),
                submitLabel: @js(__('Save file')),
            };
        },
        editFile(file) {
            this.form = {
                ...this.blankFile(),
                ...Object.fromEntries(Object.entries(file).map(([key, value]) => [key, value === null ? '' : value])),
                impugn: Boolean(file.impugn),
                context: `edit-${file.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__RECORD__', file.id),
                title: @js(__('Edit file')),
                submitLabel: @js(__('Update file')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-form' }))"
        @elseif ($deletedFileConflict)
            x-init="$nextTick(() => $dispatch('modal-show', { name: 'file-name-conflict' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createFile(); $nextTick(() => $dispatch('modal-show', { name: 'file-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="file" :description="__('Case files, their phases and their documents.')">
            <x-slot:actions>
                @if ($scope === 'active')
                    <flux:button size="sm" variant="ghost" :href="$mine ? route('files.index') : route('files.mine')"
                        :icon="$mine ? 'squares-2x2' : 'user'" data-test="file-mine-toggle">
                        {{ $mine ? __('All files') : __('My files') }}
                    </flux:button>
                @endif
                @if ($list['create'])
                    @can('create', \Files13\Models\File::class)
                        <x-basics13::list.create-action prefix="file" :label="__('New file')" click="createFile()" />
                    @endcan
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="file" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$files->total()" :scope="$scope">
                <x-basics13::list.table prefix="file" :paginator="$files">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="file" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Number') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Type') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Status') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Start date') }}</flux:table.column>
                        <flux:table.column scope="col" align="end" sticky class="text-end">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($list['rows'] as $row)
                            <flux:table.row :key="$row['id']" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <flux:table.cell class="max-w-[20rem]">
                                    <a href="{{ $row['showUrl'] }}" class="block max-w-full truncate font-medium hover:underline"
                                        title="{{ $row['name'] }}" data-test="file-name-{{ $row['id'] }}">{{ $row['name'] }}</a>
                                </flux:table.cell>
                                <flux:table.cell>{{ $row['number'] ?: '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $row['type'] }}</flux:table.cell>
                                <flux:table.cell>{{ $row['status'] ?: '—' }}</flux:table.cell>
                                <flux:table.cell>{{ $row['startDate'] ?: '—' }}</flux:table.cell>
                                <flux:table.cell align="end" sticky class="bg-white dark:bg-zinc-900">
                                    @if ($row['writable'])
                                        <x-basics13::list.row-actions :actions="$row['actions']" prefix="file" edit-handler="editFile" />
                                    @else
                                        <flux:button size="sm" variant="ghost" icon="eye" :href="$row['showUrl']"
                                            :aria-label="__('View')" data-test="file-view-{{ $row['id'] }}" />
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="6" :message="$list['emptyMessage']" />
                        @endforelse
                    </flux:table.rows>
                </x-basics13::list.table>
            </x-basics13::list.searchable-results>
        @endfragment

        @if ($list['create'])
            <flux:modal name="file-form" aria-labelledby="file-form-heading" flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)" class="form-drawer max-w-none">
                @include('files13::files.form')
            </flux:modal>

            @if ($deletedFileConflict)
                <x-basics13::name-conflict-modal name="file-name-conflict" :title="__('Record Name already in trash')"
                    :message="__('A deleted record already uses the name :name.', ['name' => $deletedFileConflict['name']])"
                    :create-action="route('files.store')" :restore-action="route('files.trash.restore', $deletedFileConflict['id'])"
                    :create-fields="array_filter(collect(['name', 'number', 'impugn', 'start_date', 'end_date', 'type_id', 'status_id', 'subject_id', 'ending_id', 'location_id', 'district_id', 'notes'])->mapWithKeys(fn (string $field): array => [$field => old($field, $field === 'name' ? $deletedFileConflict['name'] : '')])->all(), fn (mixed $value): bool => is_scalar($value))"
                    :create-label="__('Create a new file')" :restore-label="__('Restore the deleted record instead')"
                    create-test="file-conflict-create-new" restore-test="file-conflict-restore" />
            @endif
        @endif

        <x-basics13::list.confirm-modal prefix="file" />
    </div>
</x-layouts::app>

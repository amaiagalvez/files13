@php
    $editingDocument = $list['create'] && str_starts_with(old('_document_form', ''), 'edit-');

    $initialForm = $list['create']
        ? [
            'id' => $editingDocument ? old('_document_id') : null,
            'name' => old('name', ''),
            'subject_ids' => array_values(array_map('strval', (array) old('subject_ids', []))),
            'notes' => old('notes', ''),
            'context' => $editingDocument ? old('_document_form') : 'create',
            'method' => $editingDocument ? 'PUT' : 'POST',
            'action' => $editingDocument ? route('documents.update', old('_document_id')) : route('documents.store'),
            'title' => $editingDocument ? __('Edit template') : __('New template'),
            'submitLabel' => $editingDocument ? __('Update template') : __('Save template'),
        ]
        : null;
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        options: @js($options ?? ['subjects' => []]),
        storeUrl: @js(route('documents.store')),
        updateUrl: @js(route('documents.update', '__RECORD__')),
        createDocument() {
            this.form = {
                id: null, name: '', subject_ids: [], notes: '',
                context: 'create', method: 'POST', action: this.storeUrl,
                title: @js(__('New template')), submitLabel: @js(__('Save template')),
            };
        },
        editDocument(document) {
            this.form = {
                ...document,
                notes: document.notes ?? '',
                context: `edit-${document.id}`, method: 'PUT',
                action: this.updateUrl.replace('__RECORD__', document.id),
                title: @js(__('Edit template')), submitLabel: @js(__('Update template')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'document-form' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createDocument(); $nextTick(() => $dispatch('modal-show', { name: 'document-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="document" :description="__('Documents the files are started from.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="document" :label="__('New template')" click="createDocument()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="document" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$documents->total()" :scope="$scope">
                <x-basics13::list.table prefix="document" :paginator="$documents">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="document" :scope="$scope" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Format') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Subjects') }}</flux:table.column>
                        <flux:table.column scope="col" align="end" sticky class="text-end">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($list['rows'] as $row)
                            <flux:table.row :key="$row['id']" class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <flux:table.cell class="max-w-[20rem]">
                                    <span class="block max-w-full truncate" title="{{ $row['name'] }}" data-test="document-name-{{ $row['id'] }}">
                                        {{ $row['name'] }}
                                        @if ($row['editing'])
                                            <flux:badge size="sm" color="amber">{{ __('Open in the editor') }}</flux:badge>
                                        @endif
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell>{{ $row['extension'] ?: '—' }}</flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    <span class="block max-w-full truncate">{{ $row['subjects'] ?: '—' }}</span>
                                </flux:table.cell>
                                <flux:table.cell align="end" sticky class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="document" edit-handler="editDocument" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="4" :message="$list['emptyMessage']" />
                        @endforelse
                    </flux:table.rows>
                </x-basics13::list.table>
            </x-basics13::list.searchable-results>
        @endfragment

        @if ($list['create'])
            <flux:modal name="document-form" aria-labelledby="document-form-heading" flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)" class="form-drawer max-w-none">
                @include('files13::documents.form')
            </flux:modal>
        @endif

        <x-basics13::list.confirm-modal prefix="document" />
    </div>
</x-layouts::app>
